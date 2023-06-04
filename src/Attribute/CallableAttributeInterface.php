<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Attribute;

interface CallableAttributeInterface extends ServiceAttributeInterface
{
    public function getMethod(): string;
}