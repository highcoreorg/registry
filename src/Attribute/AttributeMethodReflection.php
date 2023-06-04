<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Attribute;

use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\ReaderInterface;

final class AttributeMethodReflection
{
    private \ReflectionClass $reflection;

    public function __construct(string|\ReflectionClass $reflection, private ?ReaderInterface $reader = null)
    {
        if (is_string($reflection) && !class_exists($reflection)) {
            throw new \RuntimeException(sprintf('Class "%s" does not exists.', $reflection));
        }

        $this->reader ??= new AttributeReader();
        $this->reflection = $reflection instanceof \ReflectionClass ? $reflection : new \ReflectionClass($reflection);
    }

    /**
     * @param class-string $attributeClass
     *
     * @return \ReflectionMethod[]
     *
     * @throws \ReflectionException
     */
    public function getMethodsHasAttribute(string $attributeClass, int $filter = \ReflectionMethod::IS_PUBLIC): array
    {
        $methods = [];
        foreach ($this->reflection->getMethods($filter) as $method) {
            if (null !== $this->reader->firstFunctionMetadata($method, $attributeClass)) {
                $methods[] = $method->getName();
            }
        }

        return $methods;
    }
}