<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Attribute;

use Attribute;
use Spiral\Attributes\NamedArgumentConstructorAttribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class IdentityServiceRegistry implements NamedArgumentConstructorAttribute
{
    public function __construct(
        public readonly ?string $definition = null,
        public readonly string $attributeName = 'code'
    ) {
    }
}