<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\ServiceProvider;

use Cake\Container\Container;
use Cake\Container\Exception\ContainerException;
use Cake\Container\ServiceProvider\AbstractServiceProvider;
use Cake\Container\ServiceProvider\BootableServiceProviderInterface;
use Cake\Container\ServiceProvider\ServiceProviderAggregate;
use Cake\Container\ServiceProvider\ServiceProviderInterface;
use PHPUnit\Framework\TestCase;

class ServiceProviderAggregateTest extends TestCase
{
    protected function getServiceProvider(): ServiceProviderInterface
    {
        return new class extends AbstractServiceProvider implements BootableServiceProviderInterface {
            public $booted = 0;
            public $registered = 0;

            public function provides(string $id): bool
            {
                return in_array($id, [
                    'SomeService',
                    'AnotherService',
                ], true);
            }

            public function boot(): void
            {
                $this->booted++;
            }

            public function register(): void
            {
                $this->registered++;

                $this->getContainer()->add('SomeService', function ($arg) {
                    return $arg;
                });
            }
        };
    }

    public function testAggregateAddsClassNameServiceProvider(): void
    {
        $container = new Container();
        $aggregate = new ServiceProviderAggregate();
        $aggregate->setContainer($container);
        $aggregate->add($this->getServiceProvider());
        self::assertTrue($aggregate->provides('SomeService'));
        self::assertTrue($aggregate->provides('AnotherService'));
    }

    public function testAggregateThrowsWhenRegisteringForServiceThatIsNotAdded(): void
    {
        $this->expectException(ContainerException::class);
        $container = new Container();
        $aggregate = (new ServiceProviderAggregate())->setContainer($container);
        $aggregate->register('SomeService');
    }

    public function testAggregateInvokesCorrectRegisterMethodOnlyOnce(): void
    {
        $container = new Container();
        $aggregate = (new ServiceProviderAggregate())->setContainer($container);
        $provider = $this->getServiceProvider();
        $aggregate->add($provider);
        $aggregate->register('SomeService');
        $aggregate->register('AnotherService');
        self::assertSame(1, $provider->registered);
    }

    public function testAggregateSkipsExistingProviders(): void
    {
        $container = new Container();
        $aggregate = (new ServiceProviderAggregate())->setContainer($container);
        $provider = $this->getServiceProvider();
        $aggregate->add($provider);
        $aggregate->add($provider);

        // assert after adding provider multiple times, that it
        // was only aggregated and booted once
        self::assertSame(
            [$provider],
            iterator_to_array($aggregate->getIterator()),
        );

        self::assertSame(1, $provider->booted);
    }
}
