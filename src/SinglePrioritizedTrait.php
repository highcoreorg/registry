<?php

declare(strict_types=1);

namespace Highcore\Component\Registry;

/**
 * @template T
 */
trait SinglePrioritizedTrait
{
    abstract public function getSortedRegistry(): array;

    /**
     * @return T
     */
    public function first(): object
    {
        $this->getSortedRegistry();

        $firstItem = reset($this->registry);
        if (false === $firstItem) {
            throw new \InvalidArgumentException('Registry is empty, nothing to return.');
        }

        return $firstItem[$this->context];
    }

    public function last(): object
    {
        $this->getSortedRegistry();

        $lastItem = end($this->registry);
        reset($this->registry);

        if (false === $lastItem) {
            throw new \InvalidArgumentException('Registry is empty, nothing to return.');
        }

        return $lastItem[$this->context];
    }
}