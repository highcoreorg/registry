<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;

/**
 * @template T
 */
interface IdentitySinglePrioritizedServiceRegistryInterface
{
    /**
     * @return iterable<T>
     */
    public function all(): iterable;

    /**
     * @param string[] $identifiers
     *
     * @return iterable<T>
     */
    public function only(array $identifiers = []): iterable;

    /**
     * @return T
     */
    public function first(): object;

    /**
     * @return T
     */
    public function last(): object;

    /**
     * @param T $service
     *
     * @throws ExistingServiceException
     * @throws \InvalidArgumentException
     */
    public function register(string $identifier, object $service, int $priority = 0): void;

    /**
     * @throws NonExistingServiceException
     */
    public function unregister(string $identifier): void;

    public function has(string $identifier): bool;
}
