<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;

/**
 * @template T
 * @template C
 *
 * @psalm-type IdentityServiceItem = array<string, array{
 *     C: T,
 *     priority: int
 * }>
 *
 * @template-implements SinglePrioritizedServiceRegistryInterface<T>
 */
final class IdentityPrioritizedServiceRegistry implements IdentityPrioritizedServiceRegistryInterface
{
    use ServiceInterfaceImplementsTrait;

    /** @var array<string, IdentityServiceItem[]> */
    private array $registry = [];

    /** @var string[] */
    private array $sorted = [];

    /**
     * @property null|class-string<T> $interface
     * @property C $context
     */
    public function __construct(
        private readonly ?string $interface = null,
        private readonly string $context = 'service',
    ) {
    }

    public function createServiceId(object $service): string
    {
        return sprintf('%s:%s', get_class($service), spl_object_hash($service));
    }

    /**
     * @return string[]
     */
    public function getAvailableIds(): array
    {
        return array_keys([...$this->all()]);
    }

    /**
     * @return iterable<string, T>
     */
    public function all(): iterable
    {
        $items = [];
        foreach ($this->registry as $groupItems) {
            $items[] = $groupItems;
        }

        foreach ($this->sortItems(array_merge(...$items)) as $record) {
            yield $record[$this->context];
        }
    }

    /**
     * @return \Generator<IdentityServiceItem>
     */
    public function getItemsById(string $identifier): \Generator
    {
        if (!$this->has($identifier)) {
            throw NonExistingServiceException::createFromContextAndType($this->context, $identifier, $this->getAvailableIds());
        }

        $this->sortItemsById($identifier);

        foreach ($this->registry[$identifier] as $record) {
            yield $record[$this->context];
        }
    }

    public function register(string $identifier, object $service, int $priority = 0): void
    {
        if ($this->hasService($identifier, $service)) {
            throw ExistingServiceException::createFromContextAndTypeAndServiceId(
                $this->context,
                $identifier,
                $this->createServiceId($service),
            );
        }

        $this->assertServiceIsInstanceOfServiceType($this->context, $service);

        $this->registry[$identifier] ??= [];
        $this->registry[$identifier][$this->createServiceId($service)] = [
            $this->context => $service,
            'priority' => $priority,
        ];

        unset($this->sorted[$identifier]);
    }

    public function unregisterService(string $identifier, object $service): void
    {
        if (!$this->has($identifier) || !$this->hasService($identifier, $service)) {
            throw NonExistingServiceException::createFromContextAndTypeAndService(
                $this->context,
                $identifier,
                $service,
                $this->getAvailableItemsById($identifier),
            );
        }

        unset($this->registry[$identifier][$this->createServiceId($service)]);

        if ([] === $this->registry[$identifier]) {
            $this->unregister($identifier);
        }
    }

    public function unregister(string $identifier): void
    {
        if (!$this->has($identifier)) {
            throw NonExistingServiceException::createFromContextAndType(
                $this->context,
                $identifier,
                $this->getAvailableIds(),
            );
        }

        unset($this->registry[$identifier]);
    }

    public function has(string $identifier): bool
    {
        return isset($this->registry[$identifier]);
    }

    public function hasService(string $identifier, object $service): bool
    {
        return $this->has($identifier) && isset($this->registry[$identifier][$this->createServiceId($service)]);
    }

    public function only(array $identifiers = []): iterable
    {
        $items = [];
        foreach ($identifiers as $identifier) {
            if (!$this->has($identifier)) {
                throw NonExistingServiceException::createFromContextAndType($this->context, $identifier, $this->getAvailableIds());
            }

            $items[] = array_values($this->registry[$identifier]);
        }

        foreach ($this->sortItems(array_merge(...$items)) as $record) {
            yield $record[$this->context];
        }
    }

    private function sortItemsById(string $identifier): void
    {
        if (isset($this->sorted[$identifier]) || !$this->has($identifier)) {
            return;
        }

        $this->registry[$identifier] = $this->sortItems($this->registry[$identifier]);
        $this->sorted[$identifier] = true;
    }

    /**
     * @param string $identifier
     *
     * @return string[]
     */
    public function getAvailableItemsById(string $identifier): array
    {
        $availableItems = $this->has($identifier)
            ? $this->getItemsById($identifier)
            : [];

        /** @var T $item */
        return array_map(fn ($item) => $this->createServiceId($item), [...$availableItems]);
    }

    public function sortItems(array $items): array
    {
        uasort($items, static function (array $a, array $b): int {
            return $b['priority'] <=> $a['priority'];
        });

        return $items;
    }
}