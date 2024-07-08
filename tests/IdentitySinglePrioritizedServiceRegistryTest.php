<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Tests;

use Highcore\Component\Registry\Exception\NonExistingServiceException;
use Highcore\Component\Registry\IdentitySinglePrioritizedServiceRegistry;

final class IdentitySinglePrioritizedServiceRegistryTest extends AbstractTestCase
{
    public function test_register_service_with_interface(): void
    {
        $services = [];

        $registry = new IdentitySinglePrioritizedServiceRegistry(TestServiceInterface::class);
        $registry->register('test_service_1', $services['test_service_1'] = new TestService());

        foreach ($registry->all() as $index => $registryService) {
            self::assertInstanceOf(TestServiceInterface::class, $registryService);
            self::assertObjectEqualsByHash($services[$index], $registryService);
        }
    }

    public function test_register_service_without_interface(): void
    {
        $services = [];

        $registry = new IdentitySinglePrioritizedServiceRegistry();
        $registry->register('test_service_1', $services['test_service_1'] = new TestService());

        foreach ($registry->all() as $index => $registryService) {
            self::assertObjectEqualsByHash($services[$index], $registryService);
        }
    }

    public function test_incorrect_register_service_with_interface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(\sprintf(
            '/^Service needs to be of type "%s", ".*" given\.$/',
            addcslashes(TestServiceInterface::class, '\\')
        ));

        $registry = new IdentitySinglePrioritizedServiceRegistry(TestServiceInterface::class);
        $registry->register(TestService::class, new TestService());
        $registry->register('test_service_2', new class {
        });
    }

    public function test_unregister_unregistered_service(): void
    {
        $this->expectException(NonExistingServiceException::class);
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service "%s" does not exist, available services: ".*"$/',
            addcslashes(TestService::class, '\\')
        ));

        $registry = new IdentitySinglePrioritizedServiceRegistry();
        $registry->register('test_service_1', new class {
        });
        $registry->unregister(TestService::class);
    }

    public function test_correct_unregister_service(): void
    {
        $registry = new IdentitySinglePrioritizedServiceRegistry();
        $registry->register('test_service_1', new class {
        });
        $registry->register('test_service_2', new TestService());

        self::assertCount(2, [...$registry->all()]);
        $registry->unregister('test_service_2');
        self::assertCount(1, [...$registry->all()]);
        self::assertFalse($registry->has('test_service_2'));
    }

    public function test_correct_using_only(): void
    {
        $registry = new IdentitySinglePrioritizedServiceRegistry();
        $registry->register('test_service_1', new class {
        });
        $registry->register('test_service_2', new TestService());
        $registry->register('test_service_3', new TestService());
        $registry->register('test_service_4', new TestService());
        $registry->register('test_service_5', new TestService());

        $onlyIds = [
            'test_service_1',
            'test_service_2',
            'test_service_5',
        ];

        $result = [...$registry->only($onlyIds)];

        self::assertSame($onlyIds, array_keys($result));
    }

    public function test_correct_using_all(): void
    {
        $registry = new IdentitySinglePrioritizedServiceRegistry();
        $ids = [
            'test_service_1',
            'test_service_2',
            'test_service_3',
            'test_service_4',
            'test_service_5',
        ];

        foreach ($ids as $id) {
            $service = random_int(0, 100) > 50
                ? new class {}
                : new TestService();
            $registry->register($id, $service);
        }

        $result = [...$registry->all()];
        self::assertSame($ids, array_keys($result));
    }

    public function test_correct_all_using_only(): void
    {
        $registry = new IdentitySinglePrioritizedServiceRegistry();
        $ids = [
            'test_service_1',
            'test_service_2',
            'test_service_3',
            'test_service_4',
            'test_service_5',
        ];

        foreach ($ids as $id) {
            $service = random_int(0, 100) > 50
                ? new class {}
                : new TestService();
            $registry->register($id, $service);
        }

        $result = [...$registry->only($ids)];
        self::assertSame($ids, array_keys($result));
    }

    public function test_sort_by_priority(): void
    {
        $services = $this->createServiceListWithPriority();
        $registry = new IdentitySinglePrioritizedServiceRegistry();

        $keys = \array_keys($services);
        shuffle($keys);
        foreach ($keys as $key) {
            [$service, $priority] = $services[$key];
            $registry->register($key, $service, $priority);
        }

        $serviceIds = array_keys($services);
        $registryServicesHashes = \array_keys([...$registry->all()]);

        self::assertEquals($serviceIds, $registryServicesHashes);
    }

    public function test_get_item_by_id(): void
    {
        $services = $this->createServiceListWithPriority();
        $registry = new IdentitySinglePrioritizedServiceRegistry();

        $keys = \array_keys($services);
        shuffle($keys);

        $expectedKey = reset($keys);
        [$expectedService] = $services[$expectedKey];

        foreach ($keys as $key) {
            [$service, $priority] = $services[$key];
            $registry->register($key, $service, $priority);
        }

        self::assertObjectEqualsByHash($expectedService, $registry->get($expectedKey));
    }

    public function test_get_first_item(): void
    {
        $services = $this->createServiceListWithPriority();
        $registry = new IdentitySinglePrioritizedServiceRegistry();

        $keys = \array_keys($services);
        $firstKey = reset($keys);
        [$expectedService] = $services[$firstKey];

        shuffle($keys);
        foreach ($keys as $key) {
            [$service, $priority] = $services[$key];
            $registry->register($key, $service, $priority);
        }

        self::assertObjectEqualsByHash($expectedService, $registry->first());
    }

    public function test_get_last_item(): void
    {
        $services = $this->createServiceListWithPriority();
        $registry = new IdentitySinglePrioritizedServiceRegistry();

        $keys = \array_keys($services);
        $lastKey = end($keys);
        [$expectedService] = $services[$lastKey];

        shuffle($keys);
        foreach ($keys as $key) {
            [$service, $priority] = $services[$key];
            $registry->register($key, $service, $priority);
        }

        self::assertObjectEqualsByHash($expectedService, $registry->last());
    }

    public function createServiceListWithPriority(): array
    {
        $services = [];
        for ($i = 10; $i < 100; $i += 10) {
            $service = ($i % 20 === 0)
                ? new class {
                }
                : new TestService();

            $priority = random_int($i - 9, $i - 1);
            $services[md5((string) $priority)] = [$service, $priority];
        }

        return $services;
    }
}