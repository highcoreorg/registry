<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;

/**
 * @template T
 */
interface SinglePrioritizedServiceRegistryInterface
{
    /**
     * @return iterable<T>
     */
    public function all(): iterable;

    /**
     * @param T $service
     *
     * @throws ExistingServiceException
     * @throws \InvalidArgumentException
     */
    public function register(object $service, int $priority = 0): void;

    /**
     * @param T $service
     *
     * @throws NonExistingServiceException
     */
    public function unregister(object $service): void;

    /**
     * @param T $service
     */
    public function has(object $service): bool;
}
