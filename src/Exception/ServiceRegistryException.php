<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Exception;

use RuntimeException;

class ServiceRegistryException extends RuntimeException
{
    public static function serviceIsNotImplementsInterface(string $context, string $interface, string $service): self
    {
        return new self(sprintf(
            '%s needs to be of type "%s", "%s" given.',
            ucfirst($context),
            $interface,
            $service,
        ));
    }
}