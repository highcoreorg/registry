<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;
use Highcore\Component\Registry\Exception\ServiceRegistryException;

/**
 * @template T
 *
 * @psalm-type ServiceItem = array{
 *     service: T,
 *     priority: int
 * }
 *
 * @template-implements SinglePrioritizedServiceRegistryInterface<T>
 */
final class SinglePrioritizedServiceRegistry implements SinglePrioritizedServiceRegistryInterface
{
    use ServiceInterfaceImplementsTrait;

    /** @var array<array-key, ServiceItem> */
    private array $registry = [];

    private bool $sorted = true;

    /**
     * @property null|class-string<T> $interface
     */
    public function __construct(
        private readonly ?string $interface = null,
        private readonly string $context = 'service',
    ) {
    }

    public function all(): iterable
    {
        foreach ($this->getSortedRegistry() as $record) {
            yield $record[$this->context];
        }
    }

    /**
     * @param T $service
     * @param int $priority
     */
    public function register(object $service, int $priority = 0): void
    {
        $this->assertServiceIsInstanceOfServiceType($this->context, $service);

        if ($this->has($service)) {
            throw ExistingServiceException::createFromContextAndType(
                $this->context,
                get_class($service),
            );
        }

        $this->registry[$this->createServiceId($service)] = [$this->context => $service, 'priority' => $priority];
        $this->sorted = false;
    }

    public function unregister(object $service): void
    {
        if (!$this->has($service)) {
            throw NonExistingServiceException::createFromContextAndType(
                $this->context,
                get_class($service),
                array_map('get_class', array_column($this->registry, $this->context))
            );
        }

        unset($this->registry[$this->createServiceId($service)]);
    }

    public function has(object $service): bool
    {
        $this->assertServiceIsInstanceOfServiceType($this->context, $service);

        return isset($this->registry[$this->createServiceId($service)]);
    }

    private function getSortedRegistry(): array
    {
        if (!$this->sorted) {
            $this->sortRegistry();
        }

        return $this->registry;
    }

    private function sortRegistry(): void
    {
        $registry = $this->registry;
        uasort($registry, static function (array $a, array $b): int {
            return $a['priority'] <=> $b['priority'];
        });

        $this->sorted = true;
        $this->registry = $registry;
    }

    public function createServiceId(object $service): string
    {
        return spl_object_hash($service);
    }
}
