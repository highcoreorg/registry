<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Attribute;

interface ServiceMethodAttributeInterface extends ServiceAttributeInterface
{
    public function getMethod(): string;
}