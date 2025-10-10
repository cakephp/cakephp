<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Core\Attribute;

use Cake\Core\Configure;
use Cake\Core\Container;
use Cake\Test\TestCase\Core\Attribute\Configure\FakeClient;
use Cake\TestSuite\TestCase;
use League\Container\ReflectionContainer;
use TypeError;

class ConfigureTest extends TestCase
{
    /**
     * @return void
     */
    public function testValidConfig(): void
    {
        $apiKey = '987654321';
        Configure::write('Star.apiKey', $apiKey);
        $container = new Container();
        $container->delegate(new ReflectionContainer());
        $client = $container->get(FakeClient::class);
        $this->assertInstanceOf(FakeClient::class, $client);
        $this->assertSame($apiKey, $client->apiKey);
    }

    /**
     * @return void
     */
    public function testMissingConfig(): void
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer());
        $this->expectException(TypeError::class);
        $container->get(FakeClient::class);
    }
}
