<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

/**
 * @template T
 */
final class CallableItem
{
    /**
     * @param T $service
     */
    public function __construct(
        public readonly object $service,
        public readonly string $method,
    ) {
    }
}