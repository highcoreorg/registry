<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Exception;

use Highcore\Component\Registry\CallableItem;

class NonExistingServiceException extends \InvalidArgumentException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function createFromContextAndType(string $context, string $type, array $existingServices): self
    {
        return new self(sprintf(
            '%s "%s" does not exist, available %s services: "%s"',
            ucfirst($context),
            $type,
            $context,
            implode('", "', $existingServices)
        ));
    }

    /**
     * @param array<string, CallableItem> $existingServices
     */
    public static function createFromContextAndId(string $context, string $id, array $existingServices): self
    {
        return new self(sprintf(
            '%s with id "%s" does not exist, available %ss: "%s"',
            ucfirst($context),
            $id,
            $context,
            implode('", "', self::formatServiceMethodItems($existingServices))
        ));
    }

    /**
     * @param array<string, CallableItem> $existingServices
     */
    private static function formatServiceMethodItems(array $existingServices): array
    {
        $results = [];

        /** @var CallableItem $service */
        foreach ($existingServices as $identifier => $service) {
            $serviceClass = get_class($service->service);

            $results[] = is_int($identifier)
                ? \sprintf('%s::%s', $serviceClass, $service->method)
                : \sprintf('[%s] - %s::%s', $identifier, $serviceClass, $service->method);
        }

        return $results;
    }
}
