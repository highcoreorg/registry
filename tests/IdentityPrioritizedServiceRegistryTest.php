<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Tests;

use Highcore\Component\Registry\Exception\NonExistingServiceException;
use Highcore\Component\Registry\Exception\ServiceRegistryException;
use Highcore\Component\Registry\IdentityPrioritizedServiceRegistry;
use PHPUnit\Framework\TestCase;

final class IdentityPrioritizedServiceRegistryTest extends TestCase
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
            self::assertEquals(spl_object_hash($services[$index]), spl_object_hash($registryService));
        }
    }

    public function test_register_service_without_interface(): void
    {
        $services = [];

        $registry = new IdentityPrioritizedServiceRegistry();
        $registry->register('test.group', $services[] = new TestService());

        foreach ($registry->all() as $index => $registryService) {
            self::assertEquals(spl_object_hash($services[$index]), spl_object_hash($registryService));
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

    public function test_sort_by_priority(): void
    {
        $services = $this->createServiceListWithPriority();
        $registry = new IdentityPrioritizedServiceRegistry();

        $clonedServices = $services;
        shuffle($clonedServices);
        foreach ($clonedServices as [$service, $priority]) {
            $registry->register('some.group', $service, $priority);
        }

        $registryServices = [...$registry->getItemsById('some.group')];
        $servicesHashes = \array_map(static fn(array $service) => spl_object_id($service[0]), $services);
        $registryServicesHashes = \array_map(static fn(object $service) => spl_object_id($service), $registryServices);

        self::assertEquals($servicesHashes, $registryServicesHashes);
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
        $expectedServiceHashes = array_map(static fn (array $s) => spl_object_hash($s[0]), $expectedServices);

        // Mixin unexpected service with unexpected group to check if only works
        $registry->register('test_service_group_3', new TestService(), 10);
        $registry->register('test_service_group_4', new TestService(), 11);

        $result = array_map(static fn($s) => spl_object_hash($s), [...$registry->only($ids)]);
        self::assertSame($expectedServiceHashes, $result);
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