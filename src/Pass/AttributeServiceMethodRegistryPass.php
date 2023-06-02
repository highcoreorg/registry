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

use Highcore\Component\Registry\Attribute\AttributeMethodReflection;
use Highcore\Component\Registry\Attribute\IdentityServiceAttributeInterface;
use Highcore\Component\Registry\Attribute\ServiceMethodAttributeInterface;
use Highcore\Component\Registry\ServiceMethodRegistry;
use Spiral\Attributes\AttributeReader;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @template A
 * @template B
 */
final class AttributeServiceMethodRegistryPass implements CompilerPassInterface
{
    /**
     * @property class-string<A> $targetAttributeClass
     * @property class-string<B> $targetAttributeMethod
     */
    public function __construct(
        private readonly string $definition,
        private readonly string $targetClassAttribute,
        private readonly string $targetMethodAttribute,
        private readonly ?string $interface = null,
        private readonly ?\Closure $identifierResolver = null,
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
        if (!($attributeInstance instanceof ServiceMethodAttributeInterface)) {
            throw new \LogicException(sprintf(
                'Attribute "%s" should implements "%s"',
                $attribute, ServiceMethodAttributeInterface::class
            ));
        }

        $registryDefinition = $container->getDefinition($this->definition);

        $container->registerAttributeForAutoconfiguration($this->targetClassAttribute, function (
            ChildDefinition $definition,
            ServiceMethodAttributeInterface $attribute,
            \Reflector $reflector,
        ) use ($registryDefinition): void {
            static $attributeReader = new AttributeReader();
            if (!($reflector instanceof \ReflectionClass)) {
                throw new \LogicException(sprintf(
                    'Attribute "%s" target should be only "%s::TARGET_CLASS"',
                    get_class($attribute), \Attribute::class
                ));
            }

            $attributeMethodReflection = new AttributeMethodReflection($reflector, $attributeReader);
            foreach ($attributeMethodReflection->getMethodsHasAttribute($this->targetMethodAttribute) as $method) {
                $methodAttribute = $attributeReader->firstFunctionMetadata($method, $this->targetMethodAttribute);

                $identifier = null;
                if ($methodAttribute instanceof IdentityServiceAttributeInterface) {
                    $identifier = $methodAttribute->getIdentifier();
                }
                if (null !== $this->identifierResolver) {
                    $identifier = $this->identifierResolver->call($this, $reflector, $method);
                }

                if (null === $identifier) {
                    throw new \LogicException(sprintf(
                        'Method "%s::%s" must implement "%s" or %s("%s") must have a resolver identifier.',
                        $reflector->getName(), $method->getName(), IdentityServiceAttributeInterface::class,
                        self::class, $this->definition,
                    ));
                }

                $registryDefinition->addMethodCall('register', [
                    $identifier, $definition, $method->getName(),
                ]);
            }
        });
    }

    public function setupRegistryDefinition(ContainerBuilder $container): void
    {
        $definitionArgs = null === $this->targetClassAttribute ? [] : [$this->interface];

        if (!$container->hasDefinition($this->definition)) {
            $container->setDefinition($this->definition, new Definition(ServiceMethodRegistry::class, $definitionArgs));
        }
    }
}