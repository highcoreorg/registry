<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;

/**
 * @template T
 *
 * @template-implements CallableRegistryInterface<CallableItem<T>>
 */
final class CallableRegistry implements CallableRegistryInterface
{
    use ServiceInterfaceImplementsTrait;

    /** @var array<string, array<string, CallableItem<T>> */
    private array $services = [];

    /**
     * @property null|class-string<T> $className
     */
    public function __construct(
        private readonly ?string $interface = null,
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

        if ($this->has($identifier)) {
            throw ExistingServiceException::createFromContextAndIdAndServiceAndMethod(
                $this->context, $identifier, $serviceClass, $method
            );
        }

        $this->assertServiceIsInstanceOfServiceType($this->context, $service);

        $this->services[$identifier] = new CallableItem(service: $service, method: $method);
    }

    public function get(string $identifier)
    {
        if (!$this->has($identifier)) {
            throw NonExistingServiceException::createFromCallableContextAndId(
                $this->context, $identifier, $this->services
            );
        }

        return $this->services[$identifier];
    }

    public function has(string $identifier): bool
    {
        return isset($this->services[$identifier]);
    }

    public function unregister(string $identifier): void
    {
        if (!$this->has($identifier)) {
            throw NonExistingServiceException::createFromCallableContextAndId(
                $this->context, $identifier, $this->services
            );
        }

        unset($this->services[$identifier]);
    }
}
