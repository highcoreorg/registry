<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Attribute;


interface IdentityServiceAttributeInterface extends ServiceAttributeInterface
{
    public function getIdentifier(): string;
}
