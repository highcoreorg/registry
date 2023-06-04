<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;

/**
 * @template T
 *
 * @psalm-type RegistryItem = array{service: T, method: string>
 */
interface CallableRegistryInterface
{
    /**
     * @return array<string, RegistryItem>
     */
    public function all(): array;

    /**
     * @param T $service
     *
     * @throws ExistingServiceException
     * @throws \InvalidArgumentException
     */
    public function register(string $identifier, object $service, string $method): void;

    /**
     * @return T
     *
     * @throws NonExistingServiceException
     */
    public function get(string $identifier);

    public function has(string $identifier): bool;

    /**
     * @throws NonExistingServiceException
     */
    public function unregister(string $identifier): void;
}
