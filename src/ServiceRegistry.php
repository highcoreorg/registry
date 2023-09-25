<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;
use Highcore\Component\Registry\Exception\ServiceRegistryException;

/**
 * @template T
 *
 * @template-implements ServiceRegistryInterface<T>
 */
final class ServiceRegistry implements ServiceRegistryInterface
{
    use ServiceInterfaceImplementsTrait;

    /** @var array<string, T> */
    private array $services = [];

    /**
     * @property class-string<T>
     */
    public function __construct(
        private readonly ?string $interface = null,
        private readonly string $context = 'service',
    ) {
    }

    public function all(): array
    {
        return array_values($this->services);
    }

    /**
     * @param T $service
     */
    public function register(object $service): void
    {
        $serviceId = $this->createServiceId($service);

        if ($this->has($service)) {
            throw ExistingServiceException::createFromContextAndType($this->context, $serviceId);
        }

        $this->assertServiceIsInstanceOfServiceType($this->context, $service);

        $this->services[$serviceId] = $service;
    }

    public function unregister(string|object $service): void
    {
        if (!$this->has($service)) {
            throw NonExistingServiceException::createFromContextAndType($this->context, $this->createServiceId($service), array_keys($this->services));
        }

        unset($this->services[$this->createServiceId($service)]);
    }

    /**
     * @param T $service
     *
     * @return bool
     */
    public function has(string|object $service): bool
    {
        return isset($this->services[$this->createServiceId($service)]);
    }

    /**
     * @param T|string $service
     *
     * @noinspection PhpMissingParamTypeInspection
     */
    private function createServiceId($service): string
    {
        return is_string($service) ? $service : get_class($service);
    }
}
