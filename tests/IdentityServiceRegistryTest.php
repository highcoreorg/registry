<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Tests;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;
use Highcore\Component\Registry\IdentityServiceRegistry;
use PHPUnit\Framework\TestCase;

final class IdentityServiceRegistryTest extends TestCase
{
    public function test_fail_get_unregistered_service(): void
    {
        $registry = new IdentityServiceRegistry(TestServiceInterface::class);

        $this->expectException(NonExistingServiceException::class);
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service "%s" does not exist, available service services: ("[a-z0-9_]+"(, )?)+$/',
            addcslashes(TestService::class, '\\')
        ));

        $registry->register('test_service', new TestService());
        $registry->register('test_service_3', new TestService());

        $registry->get(TestService::class);
    }

    public function test_get_registered_service(): void
    {
        $registry = new IdentityServiceRegistry(TestServiceInterface::class);

        $service1 = new TestService();
        $service1->test = 'test';

        $registry->register(TestService::class, $service1);
        $registry->register('test_service_3', new TestService());

        self::assertEquals($service1->test, $registry->get(TestService::class)->test);
    }

    public function test_correct_register_with_interface(): void
    {
        $registry = new IdentityServiceRegistry(TestServiceInterface::class);

        $services=  [];
        $services[TestService::class] = new TestService();
        $services['test_service'] = new TestService();

        foreach ($services as $key => $service) {
            $registry->register($key, $service);

            self::assertTrue($registry->has($key));
            self::assertInstanceOf(TestServiceInterface::class, $registry->get($key));
        }
    }

    public function test_correct_register_without_interface(): void
    {
        $registry = new IdentityServiceRegistry();

        $services=  [];
        $services[TestService::class] = new TestService();
        $services['test_service'] = new TestService();

        foreach ($services as $key => $service) {
            $registry->register($key, $service);

            self::assertTrue($registry->has($key));
            self::assertInstanceOf(get_class($service), $registry->get($key));
        }
    }

    public function test_double_register_service(): void
    {
        $this->expectException(ExistingServiceException::class);
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service of type "%s" already exists\.$/',
            addcslashes(TestService::class, '\\')
        ));

        $registry = new IdentityServiceRegistry(TestServiceInterface::class);
        $registry->register(TestService::class, new TestService());
        $registry->register(TestService::class, new TestService());
    }

    public function test_incorrect_register_with_interface(): void
    {
        $registry = new IdentityServiceRegistry(TestServiceInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(\sprintf(
            '/^Service needs to be of type "%s", ".*" given\.$/',
            addcslashes(TestServiceInterface::class, '\\')
        ));

        $registry->register('test_anonymous', new class {});
    }

    public function test_has_unregistered_service(): void
    {
        $registry = new IdentityServiceRegistry(TestServiceInterface::class);
        self::assertFalse($registry->has(TestService::class));
        self::assertFalse($registry->has('test_service'));
    }

    public function test_has_registered_service(): void
    {
        $registry = new IdentityServiceRegistry(TestServiceInterface::class);
        $registry->register(TestService::class, new TestService());
        $registry->register('test_service', new TestService());

        self::assertTrue($registry->has('test_service'));
        self::assertTrue($registry->has(TestService::class));
    }

    public function test_fail_unregister_unregistered_service(): void
    {
        $registry = new IdentityServiceRegistry(TestServiceInterface::class);

        $this->expectException(NonExistingServiceException::class);
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service "%s" does not exist, available service services: ("[a-z0-9_]+"(, )?)+$/',
            addcslashes(TestService::class, '\\')
        ));

        $registry->register('test_service', new TestService());
        $registry->register('test_service_2', new TestService());
        $registry->unregister(TestService::class);
    }
}
