<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Attribute;


interface PrioritizedServiceAttributeInterface extends ServiceAttributeInterface
{

    public function getPriority(): int;
}
