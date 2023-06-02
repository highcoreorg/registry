<?php

declare(strict_types=1);

/*
 * This file is part of the Highcore group.
 *
 * (c) Roman Cherniakhovsky <bizrenay@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Highcore\Component\Registry\Pass;

use Highcore\Component\Registry\Attribute\IdentityServiceAttributeInterface;
use Highcore\Component\Registry\Attribute\ServiceAttributeInterface;
use Highcore\Component\Registry\IdentityServiceRegistry;
use Highcore\Component\Registry\ServiceMethodRegistry;
use Highcore\Component\Registry\ServiceRegistry;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @template A
 */
final class AttributeServiceRegistryPass implements CompilerPassInterface
{
    /**
     * @param class-string<A> $targetClassAttribute
     */
    public function __construct(
        private readonly string $definition,
        private readonly string $targetClassAttribute,
        private readonly bool $identifiable = false,
        private readonly ?string $interface = null,
    ) {
    }

    /**
     * @throws \ReflectionException
     */
    public function process(ContainerBuilder $container): void
    {
        $this->setupRegistryDefinition($container);

        foreach ($container->getDefinitions() as $definition) {
            if (!$this->accept($definition)) {
                continue;
            }

            $class = $container->getReflectionClass($definition->getClass(), false);
            $attributes = null === $class
                ? []
                : $class->getAttributes($this->targetClassAttribute, \ReflectionAttribute::IS_INSTANCEOF);

            if (!($class instanceof \ReflectionClass) || 0 === \count($attributes)) {
                continue;
            }

            $this->processClass($container, $attributes);
        }
    }

    public function accept(Definition $definition): bool
    {
        return $definition->isAutoconfigured() && !$definition->hasTag('container.ignore_attributes');
    }

    /**
     * @param \ReflectionAttribute[] $attributes
     */
    public function processClass(ContainerBuilder $container, array $attributes): void
    {
        if (1 !== count($attributes)) {
            throw new \LogicException(sprintf('Attribute #[%s] should be declared once only.', $this->targetClassAttribute));
        }

        $attribute = \array_pop($attributes);
        if ($attribute->getTarget() !== \Attribute::TARGET_CLASS) {
            throw new \LogicException(sprintf(
                'Attribute "%s" target should be only "%s::TARGET_CLASS"',
                $this->targetClassAttribute, \Attribute::class
            ));
        }

        $attributeInstance = $attribute->newInstance();
        if (!($attributeInstance instanceof ServiceAttributeInterface)) {
            throw new \LogicException(sprintf(
                'Attribute "%s" should implements "%s"',
                $attribute, ServiceAttributeInterface::class
            ));
        }

        $registryDefinition = $container->getDefinition($this->definition);
        $identifiable = $this->identifiable;

        $container->registerAttributeForAutoconfiguration($this->targetClassAttribute, static function (
            ChildDefinition $definition,
            ServiceAttributeInterface $attribute,
            \Reflector $reflector,
        ) use ($registryDefinition, $identifiable): void {
            if (!($reflector instanceof \ReflectionClass)) {
                throw new \LogicException(sprintf(
                    'Attribute "%s" target should be only "%s::TARGET_CLASS"',
                    get_class($attribute), \Attribute::class
                ));
            }

            if (!$identifiable) {
                $registryDefinition->addMethodCall('register', [$definition]);
                return;
            }

            $identifier = $attribute instanceof IdentityServiceAttributeInterface
                ? $attribute->getIdentifier()
                : $reflector->getName();

            $registryDefinition->addMethodCall('register', [
                $identifier, $definition,
            ]);
        });
    }

    public function setupRegistryDefinition(ContainerBuilder $container): void
    {
        $definitionArgs = null === $this->targetClassAttribute ? [] : [$this->interface];
        $definitionClass = $this->identifiable ? IdentityServiceRegistry::class : ServiceRegistry::class;

        if (!$container->hasDefinition($this->definition)) {
            $container->setDefinition($this->definition, new Definition($definitionClass, $definitionArgs));
        }
    }
}