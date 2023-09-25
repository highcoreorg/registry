<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

use Highcore\Component\Registry\Exception\ServiceRegistryException;

trait ServiceInterfaceImplementsTrait
{
    /**
     * @return null|class-string
     */
    public function getInterfaceToImplements(): ?string
    {
        return $this->interface;
    }

    public function assertServiceIsInstanceOfServiceType(string $context, object $service): void
    {
        $interface = $this->getInterfaceToImplements();

        if (null !== $interface && !($service instanceof $interface)) {
            throw ServiceRegistryException::serviceIsNotImplementsInterface(
                $context, $interface, get_class($service)
            );
        }
    }
}