<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\NonExistingServiceException;

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
    /** @var array<array-key, ServiceItem> */
    private array $registry = [];

    private bool $sorted = true;

    /**
     * @property class-string<T>
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
    public function register($service, int $priority = 0): void
    {
        $this->assertServiceIsInstanceOfServiceType($service);

        $this->registry[] = [$this->context => $service, 'priority' => $priority];
        $this->sorted = false;
    }

    public function unregister($service): void
    {
        $context = $this->context;

        if (!$this->has($service)) {
            throw NonExistingServiceException::createFromContextAndType(
                $this->context,
                get_class($service),
                array_map('get_class', array_column($this->registry, $context))
            );
        }

        $this->registry = array_filter($this->registry, static function (array $record) use ($context, $service): bool {
            return get_class($record[$context]) !== get_class($service);
        });
    }

    public function has($service): bool
    {
        $this->assertServiceIsInstanceOfServiceType($service);

        foreach ($this->registry as $record) {
            if ($record[$this->context] === $service) {
                return true;
            }
        }

        return false;
    }

    private function assertServiceIsInstanceOfServiceType(object $service): void
    {
        if (null !== $this->interface && !$service instanceof $this->interface) {
            throw new \InvalidArgumentException(sprintf(
                '%s needs to implements "%s", "%s" given.',
                $this->context,
                $this->interface,
                get_class($service)
            ));
        }
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
        usort($registry, static function (array $a, array $b): int {
            return $b['priority'] <=> $a['priority'];
        });

        $this->sorted = true;
        $this->registry = array_values($registry);
    }
}
