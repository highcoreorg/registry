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

    public static function createFromContextAndTypeAndService(string $context, string $type, string $service): self
    {
        return new self(sprintf('%s of type "%s" and service "%s" already exists.', ucfirst($context), $type, $service));
    }
}
