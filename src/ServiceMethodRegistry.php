<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;

/**
 * @template T
 *
 * @template-implements ServiceMethodRegistryInterface<ServiceMethodItem<T>>
 */
final class ServiceMethodRegistry implements ServiceMethodRegistryInterface
{
    /** @var array<string, array<string, ServiceMethodItem<T>> */
    private array $services = [];

    /**
     * @property null|class-string<T> $className
     */
    public function __construct(
        private readonly ?string $className = null,
        private readonly string $context = 'service',
    ) {
    }

    public function all(): array
    {
        return $this->services;
    }

    /**
     * @param T $service
     */
    public function register(string $identifier, object $service, string $method): void
    {
        $serviceClass = get_class($service);

        if ($this->has($identifier, $serviceClass)) {
            throw ExistingServiceException::createFromContextAndTypeAndService($this->context, $identifier, $serviceClass);
        }

        if (null !== $this->className && !$service instanceof $this->className) {
            throw new \InvalidArgumentException(sprintf(
                '%s needs to be of type "%s", "%s" given.',
                ucfirst($this->context), $this->className, $serviceClass
            ));
        }

        $this->services[$identifier][$serviceClass] = new ServiceMethodItem(service: $service, method: $method);
    }

    public function get(string $identifier, string $service)
    {
        if (!$this->has($identifier, $service)) {
            throw NonExistingServiceException::createFromContextAndTypeAndService(
                $this->context, $identifier, $service, $this->services
            );
        }

        return $this->services[$identifier][$service];
    }

    public function has(string $identifier, string $service): bool
    {
        return isset($this->services[$identifier][$service]);
    }

    public function hasIdentifier(string $identifier): bool
    {
        return isset($this->services[$identifier]);
    }

    public function unregister(string $identifier, string $service): void
    {
        if (!$this->has($identifier, $service)) {
            throw NonExistingServiceException::createFromContextAndTypeAndService(
                $this->context, $identifier, $service, $this->services
            );
        }

        unset($this->services[$identifier][$service]);
    }

    public function unregisterAllById(string $identifier): bool
    {
        if ($this->hasIdentifier($identifier)) {
            throw NonExistingServiceException::createFromContextAndType(
                $this->context, $identifier, array_keys($this->services)
            );
        }

        unset($this->services[$identifier]);
    }

    /**
     * @param string $identifier
     * @return ServiceMethodItem<T>[]
     */
    public function getAllById(string $identifier): array
    {
        if ($this->hasIdentifier($identifier)) {
            throw NonExistingServiceException::createFromContextAndType(
                $this->context, $identifier, array_keys($this->services)
            );
        }

        return $this->services[$identifier];
    }
}
