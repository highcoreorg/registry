<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;

/**
 * @template T
 */
interface IdentityPrioritizedServiceRegistryInterface
{
    /**
     * @return iterable<string, T>
     */
    public function all(): iterable;


    /**
     * @return iterable<array-key, T>
     */
    public function only(array $identifiers = []): iterable;

    /**
     * @return iterable<string, T>
     */
    public function getItemsById(string $identifier): iterable;

    /**
     * @param T $service
     *
     * @throws ExistingServiceException
     * @throws \InvalidArgumentException
     */
    public function register(string $identifier, object $service, int $priority = 0): void;

    /**
     * @param T $service
     *
     * @throws NonExistingServiceException
     */
    public function unregisterService(string $identifier, object $service): void;

    /**
     * @param T $service
     *
     * @throws NonExistingServiceException
     */
    public function unregister(string $identifier): void;

    public function has(string $identifier): bool;

    /**
     * @param T $service
     */
    public function hasService(string $identifier, object $service): bool;
}
