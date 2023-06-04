<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Exception;

class ExistingServiceException extends \InvalidArgumentException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function createFromContextAndType(string $context, string $type): self
    {
        return new self(sprintf('%s of type "%s" already exists.', ucfirst($context), $type));
    }

    public static function createFromContextAndIdAndServiceAndMethod(string $context, string $id, string $serviceClass, string $method): self
    {
        return new self(sprintf('%s with id "%s" and callable "%s::%s()" already exists.', ucfirst($context), $id, $serviceClass, $method));
    }
}
