<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Tests;

use Highcore\Component\Registry\Exception\ExistingServiceException;
use Highcore\Component\Registry\Exception\NonExistingServiceException;
use Highcore\Component\Registry\CallableServiceRegistry;
use PHPUnit\Framework\TestCase;

final class CallableRegistryTest extends TestCase
{
    public function test_fail_get_unregistered_service(): void
    {
        $registry = new CallableServiceRegistry(TestServiceInterface::class);

        $this->expectException(NonExistingServiceException::class);
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service with id "%s" does not exist, available services: (?:".* - .*::(?:[a-zA-Z]+)"(?:, )?)+$/',
            addcslashes(TestService::class, '\\'),
        ));

        $registry->register('test_service', new TestService(), 'testMethod');
        $registry->register('test_service_3', new TestService(), 'testMethod');

        $registry->get(TestService::class);
    }

    public function test_get_registered_service(): void
    {
        $registry = new CallableServiceRegistry(TestServiceInterface::class);

        $service1 = new TestService();
        $service1->test = 'test';

        $registry->register(TestService::class, $service1, 'testMethod');
        $registry->register('test_service_3', new TestService(), 'testMethod');

        $item = $registry->get(TestService::class);

        self::assertEquals($service1->test, $item->service->test);
        self::assertEquals('testMethod', $item->method);
    }

    public function test_correct_register_with_interface(): void
    {
        $registry = new CallableServiceRegistry(TestServiceInterface::class);

        $services=  [];
        $services[TestService::class] = new TestService();
        $services['test_service'] = new TestService();

        foreach ($services as $key => $service) {
            $registry->register($key, $service, 'testMethod');

            self::assertTrue($registry->has($key));
            $item = $registry->get($key);
            self::assertInstanceOf(TestServiceInterface::class, $item->service);
            self::assertEquals('testMethod', $item->method);
        }
    }

    public function test_correct_register_without_interface(): void
    {
        $registry = new CallableServiceRegistry();

        $services=  [];
        $services[TestService::class] = new TestService();
        $services['test_service'] = new TestService();

        foreach ($services as $key => $service) {
            $registry->register($key, $service, 'testMethod');

            self::assertTrue($registry->has($key));

            $item = $registry->get($key);
            self::assertInstanceOf(TestServiceInterface::class, $item->service);
            self::assertEquals('testMethod', $item->method);
        }
    }

    public function test_double_register_service(): void
    {
        $serviceIdentifier = 'test_service_1';
        $method = 'testMethod';

        $this->expectException(ExistingServiceException::class);
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service with id "%s" and callable "%s::%s\(\)" already exists\.$/',
            $serviceIdentifier,
            addcslashes(TestService::class, '\\'),
            $method,
        ));

        $registry = new CallableServiceRegistry(TestServiceInterface::class);
        $registry->register($serviceIdentifier, new TestService(), $method);
        $registry->register($serviceIdentifier, new TestService(), $method);
    }

    public function test_incorrect_register_with_interface(): void
    {
        $registry = new CallableServiceRegistry(TestServiceInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(\sprintf(
            '/^Service needs to be of type "%s", ".*" given\.$/',
            addcslashes(TestServiceInterface::class, '\\')
        ));

        $registry->register('test_anonymous', new class {}, 'testMethod');
    }

    public function test_has_unregistered_service(): void
    {
        $registry = new CallableServiceRegistry(TestServiceInterface::class);
        self::assertFalse($registry->has(TestService::class));
        self::assertFalse($registry->has('test_service'));
    }

    public function test_has_registered_service(): void
    {
        $registry = new CallableServiceRegistry(TestServiceInterface::class);
        $registry->register(TestService::class, new TestService(), 'testMethod');
        $registry->register('test_service', new TestService(), 'testMethod');

        self::assertTrue($registry->has('test_service'));
        self::assertTrue($registry->has(TestService::class));
    }

    public function test_fail_unregister_unregistered_service(): void
    {
        $registry = new CallableServiceRegistry(TestServiceInterface::class);

        $this->expectException(NonExistingServiceException::class);
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service with id "%s" does not exist, available services: (?:".* - .*::(?:[a-zA-Z]+)"(?:, )?)+$/',
            addcslashes(TestService::class, '\\'),
        ));

        $registry->register('test_service', new TestService(), 'testMethod');
        $registry->register('test_service_2', new TestService(), 'testMethod');
        $registry->unregister(TestService::class);
    }
}
