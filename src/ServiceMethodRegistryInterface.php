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
interface ServiceMethodRegistryInterface
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
    public function get(string $identifier, string $service);

    public function has(string $identifier, string $service): bool;

    public function hasIdentifier(string $identifier): bool;

    /**
     * @throws NonExistingServiceException
     */
    public function unregister(string $identifier, string $service): void;

    public function unregisterAllById(string $identifier): bool;

    /**
     * @return T[]
     *
     * @throws NonExistingServiceException
     */
    public function getAllById(string $identifier): array;
}
