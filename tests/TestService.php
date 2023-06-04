<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class TestService implements TestServiceInterface
{
    public string $test = '';

    public function testMethod(): string
    {
        return 'test';
    }
}