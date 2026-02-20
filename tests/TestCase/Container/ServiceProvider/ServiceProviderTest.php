<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\ServiceProvider;

use Cake\Container\ServiceProvider\AbstractServiceProvider;
use Cake\Container\ServiceProvider\BootableServiceProviderInterface;
use Cake\Container\ServiceProvider\ServiceProviderInterface;
use PHPUnit\Framework\TestCase;

class ServiceProviderTest extends TestCase
{
    protected function getServiceProvider(): ServiceProviderInterface
    {
        return new class extends AbstractServiceProvider implements BootableServiceProviderInterface
        {
            public function provides(string $id): bool
            {
                return in_array($id, [
                    'SomeService',
                    'AnotherService',
                ], true);
            }

            public function boot(): void
            {
            }

            public function register(): void
            {
                $this->getContainer()->add('SomeService', function ($arg) {
                    return $arg;
                });
            }
        };
    }

    public function testServiceProviderCorrectlyDeterminesWhatIsProvided(): void
    {
        $provider = $this->getServiceProvider()->setIdentifier('something');
        self::assertTrue($provider->provides('SomeService'));
        self::assertTrue($provider->provides('AnotherService'));
        self::assertFalse($provider->provides('NonService'));
    }
}
