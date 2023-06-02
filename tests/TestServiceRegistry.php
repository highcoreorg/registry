<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Tests;

use Highcore\Component\Registry\ServiceRegistry;
use PHPUnit\Framework\TestCase;

final class TestServiceRegistry extends TestCase
{
    public function test_correct_register_with_interface(): void
    {
        $registry = new ServiceRegistry(TestServiceInterface::class);
        $registry->register($services[] = new TestService());

        self::assertTrue($registry->has(TestService::class));
        self::assertTrue($registry->has(new TestService()));

        self::assertEquals(
            array_map(static fn ($s) => spl_object_hash($s), $services),
            array_map(static fn ($s) => spl_object_hash($s), $registry->all()),
        );
    }

    public function test_correct_register_without_interface(): void
    {
        $registry = new ServiceRegistry();
        $registry->register($services[] = new TestService());

        self::assertTrue($registry->has(TestService::class));
        self::assertTrue($registry->has(new TestService()));

        self::assertEquals(
            array_map(static fn ($s) => spl_object_hash($s), $services),
            array_map(static fn ($s) => spl_object_hash($s), $registry->all()),
        );
    }

    public function test_incorrect_register_with_interface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(\sprintf(
            '/^Service needs to be of type "%s", ".*" given\.$/',
            addcslashes(TestServiceInterface::class, '\\')
        ));

        $registry = new ServiceRegistry(TestServiceInterface::class);
        $registry->register(new class{});
    }

    public function test_double_register_service(): void
    {
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service of type "%s" already exists\.$/',
            addcslashes(TestService::class, '\\')
        ));

        $registry = new ServiceRegistry(TestServiceInterface::class);
        $registry->register(new TestService());
        $registry->register(new TestService());
    }

    public function test_has_unregistered_service(): void
    {
        $registry = new ServiceRegistry(TestServiceInterface::class);
        self::assertFalse($registry->has(new TestService()));
        self::assertFalse($registry->has(TestService::class));
    }

    public function test_has_registered_service(): void
    {
        $registry = new ServiceRegistry(TestServiceInterface::class);
        $registry->register(new TestService());

        self::assertTrue($registry->has(new TestService()));
        self::assertTrue($registry->has(TestService::class));
    }

    public function test_fail_unregister_undefined_service(): void
    {
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service "%s" does not exist, available service services: ""$/',
            addcslashes(TestService::class, '\\')
        ));

        $registry = new ServiceRegistry(TestServiceInterface::class);
        $registry->unregister(new TestService());
    }

    public function test_fail_unregister_undefined_service_but_dont_empty_services(): void
    {
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service "%s" does not exist, available service services: ".+"$/',
            addcslashes(TestService::class, '\\')
        ));

        $registry = new ServiceRegistry(TestServiceInterface::class);
        $registry->register(new class implements TestServiceInterface {});
        $registry->unregister(new TestService());
    }

    public function test_all_method_registry(): void
    {
        $registry = new ServiceRegistry(TestServiceInterface::class);
        $registry->register(new class implements TestServiceInterface {});
        $registry->register(new TestService());

        self::assertCount(2, $registry->all());

        foreach ($registry->all() as $key => $value) {
            self::assertIsInt($key);
            self::assertIsObject($value);
        }
    }
}