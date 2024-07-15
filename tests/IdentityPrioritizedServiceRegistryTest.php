<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Tests;

use Highcore\Component\Registry\Exception\NonExistingServiceException;
use Highcore\Component\Registry\Exception\ServiceRegistryException;
use Highcore\Component\Registry\IdentityPrioritizedServiceRegistry;

final class IdentityPrioritizedServiceRegistryTest extends AbstractTestCase
{
    public function test_register_service_with_interface(): void
    {
        $services = [];

        $registry = new IdentityPrioritizedServiceRegistry(TestServiceInterface::class);
        $registry->register('test.group', $services[] = new TestService());
        $registry->register('test.group', $services[] = new TestService());
        $registry->register('test.group', $services[] = new TestService());

        foreach ($registry->all() as $index => $registryService) {
            self::assertInstanceOf(TestServiceInterface::class, $registryService);
            self::assertObjectEqualsByHash($services[$index], $registryService);
        }
    }

    public function test_register_service_without_interface(): void
    {
        $services = [];

        $registry = new IdentityPrioritizedServiceRegistry();
        $registry->register('test.group', $services[] = new TestService());

        foreach ($registry->all() as $index => $registryService) {
            self::assertObjectEqualsByHash($services[$index], $registryService);
        }
    }

    public function test_incorrect_register_service_with_interface(): void
    {
        $this->expectException(ServiceRegistryException::class);
        $this->expectExceptionMessageMatches(\sprintf(
            '/^Service needs to be of type "%s", ".*" given\.$/',
            addcslashes(TestServiceInterface::class, '\\')
        ));

        $registry = new IdentityPrioritizedServiceRegistry(TestServiceInterface::class);
        $registry->register('test.group', new TestService(), 10);
        $registry->register('test.group', new class {}, 5);
    }

    public function test_unregister_unregistered_service(): void
    {
        $this->expectException(NonExistingServiceException::class);
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service "%s" does not exist inside the "test\.group" group, available services: ".*"$/',
            addcslashes(TestService::class, '\\')
        ));

        $registry = new IdentityPrioritizedServiceRegistry();
        $registry->register('test.group', new class {});
        $registry->unregisterService('test.group', new TestService());
    }

    public function test_correct_unregister_service(): void
    {
        $registry = new IdentityPrioritizedServiceRegistry();
        $registry->register('test.group', $anonymousService = new class {});
        $registry->register('test.group', $testService = new TestService());

        self::assertCount(2, [...$registry->all()]);

        $registry->unregisterService('test.group', $testService);
        self::assertCount(1, [...$registry->all()]);
        self::assertFalse($registry->hasService('test.group', $testService));

        $registry->unregisterService('test.group', $anonymousService);
        self::assertCount(0, [...$registry->all()]);
        self::assertFalse($registry->has('test.group'));
    }

    public function test_sort_services_within_identifier_by_priority(): void
    {
        $expectedServices = $this->createServiceListWithPriority();
        $registry = new IdentityPrioritizedServiceRegistry();

        $clonedServices = $expectedServices;
        shuffle($clonedServices);
        foreach ($clonedServices as [$service, $priority]) {
            $registry->register('some.group', $service, $priority);
        }

        $expectedServices = \array_map(static fn(array $service) => $service[0], $expectedServices);
        self::assertObjectsEqualsByHash($expectedServices, [...$registry->allById('some.group')]);
    }

    public function test_correct_get_first_service_within_identifier_by_priority(): void
    {
        $services = $this->createServiceListWithPriority();
        $registry = new IdentityPrioritizedServiceRegistry();

        $serviceId = 'some.group';
        [$expectedService] = reset($services);

        shuffle($services);
        foreach ($services as [$service, $priority]) {
            $registry->register($serviceId, $service, $priority);
        }

        self::assertObjectEqualsByHash($expectedService, $registry->first($serviceId));
    }

    public function test_correct_get_all_first_services_within_identifier_by_priority(): void
    {
        $registry = new IdentityPrioritizedServiceRegistry();

        $expectedServices = [];
        foreach (['some.group', 'some.second_group', 'third.group', 'some.fourth.group'] as $identifier) {
            $expectedServices[$identifier] = $services = $this->createServiceListWithPriority();
            shuffle($services);

            foreach ($services as [$service, $priority]) {
                $registry->register($identifier, $service, $priority);
            }
        }

        foreach ($registry->allFirst() as $identifier => $service) {
            [$expected] = reset($expectedServices[$identifier]);

            self::assertObjectEqualsByHash($expected, $service);
        }
    }

    public function test_correct_get_all_last_services_within_identifier_by_priority(): void
    {
        $registry = new IdentityPrioritizedServiceRegistry();

        $expectedServices = [];
        foreach (['some.group', 'some.second_group', 'third.group', 'some.fourth.group'] as $identifier) {
            $expectedServices[$identifier] = $services = $this->createServiceListWithPriority();
            shuffle($services);

            foreach ($services as [$service, $priority]) {
                $registry->register($identifier, $service, $priority);
            }
        }

        foreach ($registry->allLast() as $identifier => $service) {
            [$expected] = end($expectedServices[$identifier]);
            self::assertObjectEqualsByHash($expected, $service);
        }
    }

    public function test_correct_get_last_service_within_identifier_by_priority(): void
    {
        $services = $this->createServiceListWithPriority();
        $registry = new IdentityPrioritizedServiceRegistry();

        $serviceId = 'some.group';
        [$expectedService] = end($services);
        reset($expectedService);

        shuffle($services);
        foreach ($services as [$service, $priority]) {
            $registry->register($serviceId, $service, $priority);
        }

        self::assertObjectEqualsByHash($expectedService, $registry->last($serviceId));
    }

    public function test_correct_all_using_only(): void
    {
        $registry = new IdentityPrioritizedServiceRegistry();
        $ids = [
            'test_service_group_1',
            'test_service_group_2',
        ];

        $expectedServices = [];

        foreach ($ids as $id) {
            $services = $this->createServiceListWithPriority();
            $expectedServices[] = $services;
            shuffle($services);
            foreach ($services as [$service, $priority]) {
                $registry->register($id, $service, $priority);
            }
        }

        $expectedServices = array_merge(...$expectedServices);
        usort($expectedServices, static fn ($a, $b) => $b[1] <=> $a[1]);
        $expectedServices = array_map(static fn (array $s) => $s[0], $expectedServices);

        // Mixin unexpected service with unexpected group to check if only works
        $registry->register('test_service_group_3', new TestService(), 10);
        $registry->register('test_service_group_4', new TestService(), 11);

        self::assertObjectsEqualsByHash($expectedServices, [...$registry->only($ids)]);
    }

    public function test_correct_all_using_all(): void
    {
        $registry = new IdentityPrioritizedServiceRegistry();
        $ids = [
            'test_service_group_1',
            'test_service_group_2',
            'test_service_group_3',
        ];

        $expectedServices = [];

        foreach ($ids as $id) {
            $services = $this->createServiceListWithPriority();
            $expectedServices[] = $services;
            shuffle($services);
            foreach ($services as [$service, $priority]) {
                $registry->register($id, $service, $priority);
            }
        }

        $expectedServices = array_merge(...$expectedServices);
        usort($expectedServices, static fn ($a, $b) => $b[1] <=> $a[1]);
        $expectedServiceHashes = array_map(static fn (array $s) => spl_object_hash($s[0]), $expectedServices);

        $result = array_map(static fn($s) => spl_object_hash($s), [...$registry->all()]);
        self::assertSame($expectedServiceHashes, $result);
    }

    public function createServiceListWithPriority(): array
    {
        $services = [];
        for ($i = 100; $i > 10; $i -= 10) {
            $service = ($i % 20 === 0)
                ? new class {
                }
                : new TestService();

            $priority = random_int($i - 9, $i - 1);
            $services[] = [$service, $priority];
        }

        return $services;
    }
}