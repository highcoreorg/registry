<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Exception;

use Highcore\Component\Registry\ServiceMethodItem;

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
     * @param array<string, ServiceMethodItem> $existingServices
     */
    public static function createFromContextAndTypeAndService(string $context, string $type, string $service, array $existingServices): self
    {
        return new self(sprintf(
            '%s "%s" with id "%s" does not exist, available %ss: "%s"',
            ucfirst($context),
            $type,
            $service,
            $context,
            implode('", "', self::formatServiceMethodItems($existingServices))
        ));
    }

    /**
     * @param array<string, ServiceMethodItem> $existingServices
     */
    public static function formatServiceMethodItems(array $existingServices): array
    {
        $results = [];

        foreach ($existingServices as $identifier => $services) {
            /** @var ServiceMethodItem $service */
            foreach ($services as $service) {
                $results[] = is_int($identifier)
                    ? \sprintf('%s::%s', $service->service, $service->method)
                    : \sprintf('[%s] - %s::%s', $identifier, $service->service, $service->method);
            }
        }

        return $results;
    }
}
