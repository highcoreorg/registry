<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;

/**
 * @template T
 */
interface IdentityServiceRegistryInterface
{
    /**
     * @return array<string, T>
     */
    public function all(): array;

    /**
     * @param T $service
     *
     * @throws ExistingServiceException
     * @throws \InvalidArgumentException
     */
    public function register(string $identifier, $service): void;

    /**
     * @throws NonExistingServiceException
     */
    public function unregister(string $identifier): void;

    public function has(string $identifier): bool;

    /**
     * @return T
     *
     * @throws NonExistingServiceException
     */
    public function get(string $identifier);
}
