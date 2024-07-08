<?php

declare(strict_types=1);

namespace Highcore\Component\Registry\Tests;

use Highcore\Component\Registry\Exception\NonExistingServiceException;
use Highcore\Component\Registry\Exception\ServiceRegistryException;
use Highcore\Component\Registry\SinglePrioritizedServiceRegistry;

final class SinglePrioritizedServiceRegistryTest extends AbstractTestCase
{
    public function test_register_service_with_interface(): void
    {
        $services = [];

        $registry = new SinglePrioritizedServiceRegistry(TestServiceInterface::class);
        $registry->register($services[] = new TestService());

        foreach ($registry->all() as $index => $registryService) {
            self::assertInstanceOf(TestServiceInterface::class, $registryService);
            self::assertObjectEqualsByHash($services[$index], $registryService);
        }
    }

    public function test_register_service_without_interface(): void
    {
        $services = [];

        $registry = new SinglePrioritizedServiceRegistry();
        $registry->register($services[] = new TestService());

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

        $registry = new SinglePrioritizedServiceRegistry(TestServiceInterface::class);
        $registry->register(new TestService());
        $registry->register(new class {});
    }

    public function test_unregister_unregistered_service(): void
    {
        $this->expectException(NonExistingServiceException::class);
        $this->expectExceptionMessageMatches(sprintf(
            '/^Service "%s" does not exist, available services: ".*"$/',
            addcslashes(TestService::class, '\\')
        ));

        $registry = new SinglePrioritizedServiceRegistry();
        $registry->register(new class {});
        $registry->unregister(new TestService());
    }

    public function test_correct_unregister_service(): void
    {
        $registry = new SinglePrioritizedServiceRegistry();
        $registry->register(new class {});
        $registry->register($testService = new TestService());

        self::assertCount(2, [...$registry->all()]);
        $registry->unregister($testService);
        self::assertCount(1, [...$registry->all()]);
        self::assertFalse($registry->has($testService));
    }

    public function test_sort_by_priority(): void
    {
        $services = $this->createServiceListWithPriority();
        $registry = new SinglePrioritizedServiceRegistry();

        $clonedServices = $services;
        shuffle($clonedServices);
        foreach ($clonedServices as [$service, $priority]) {
            $registry->register($service, $priority);
        }

        $expectedServiceHashes = \array_map(static fn(array $service) => $service[0], $services);
        self::assertObjectsEqualsByHash($expectedServiceHashes, [...$registry->all()]);
    }

    public function test_get_first_item(): void
    {
        $services = $this->createServiceListWithPriority();
        $registry = new SinglePrioritizedServiceRegistry();

        $keys = \array_keys($services);
        $firstKey = reset($keys);
        [$expectedService] = $services[$firstKey];

        shuffle($keys);
        foreach ($keys as $key) {
            [$service, $priority] = $services[$key];
            $registry->register($service, $priority);
        }

        self::assertObjectEqualsByHash($expectedService, $registry->first());
    }

    public function test_get_last_item(): void
    {
        $services = $this->createServiceListWithPriority();
        $registry = new SinglePrioritizedServiceRegistry();

        $keys = \array_keys($services);
        $lastKey = end($keys);
        [$expectedService] = $services[$lastKey];

        shuffle($keys);
        foreach ($keys as $key) {
            [$service, $priority] = $services[$key];
            $registry->register($service, $priority);
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
            $services[] = [$service, $priority];
        }

        return $services;
    }
}