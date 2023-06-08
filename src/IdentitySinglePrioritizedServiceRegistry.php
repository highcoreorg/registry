<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;

/**
 * @template T
 *
 * @psalm-type ServiceItem = array{
 *     service: T,
 *     priority: int
 * }
 *
 * @template-implements IdentitySinglePrioritizedServiceRegistryInterface<T>
 */
final class IdentitySinglePrioritizedServiceRegistry implements IdentitySinglePrioritizedServiceRegistryInterface
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
        foreach ($this->getSortedRegistry() as $key => $record) {
            yield $key => $record[$this->context];
        }
    }

    /**
     * @param T $service
     *
     * @param int $priority
     */
    public function register(string $identifier, object $service, int $priority = 0): void
    {
        $this->assertServiceIsInstanceOfServiceType($service);

        if ($this->has($identifier)) {
            throw ExistingServiceException::createFromContextAndType(
                $this->context,
                get_class($service),
            );
        }

        $this->registry[$identifier] = [$this->context => $service, 'priority' => $priority];
        $this->sorted = false;
    }

    public function unregister(string $identifier): void
    {
        if (!$this->has($identifier)) {
            throw NonExistingServiceException::createFromContextAndType(
                $this->context,
                $identifier,
                array_keys($this->registry),
            );
        }

        unset($this->registry[$identifier]);
    }

    public function has(string $identifier): bool
    {
        return isset($this->registry[$identifier]);
    }

    private function assertServiceIsInstanceOfServiceType(object $service): void
    {
        if (null !== $this->interface && !$service instanceof $this->interface) {
            throw new \InvalidArgumentException(sprintf(
                '%s needs to be of type "%s", "%s" given.',
                ucfirst($this->context),
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
        uasort($registry, static function (array $a, array $b): int {
            return $a['priority'] <=> $b['priority'];
        });

        $this->sorted = true;
        $this->registry = $registry;
    }

    public function only(array $identifiers = []): iterable
    {
        foreach ($this->all() as $key => $service) {
            if (in_array($key, $identifiers, true)) {
                yield $key => $service;
            }
        }
    }
}
