<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Tests;

use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    public static function assertObjectEqualsByHash(object $expected, object $actual): void
    {
        self::assertEquals(spl_object_hash($expected), spl_object_hash($actual));
    }

    public static function assertObjectsEqualsByHash(array $expected, array $actual): void
    {
        self::assertSame(
            array_map(static fn($s) => spl_object_hash($s), $expected),
            array_map(static fn($s) => spl_object_hash($s), $actual),
        );
    }
}