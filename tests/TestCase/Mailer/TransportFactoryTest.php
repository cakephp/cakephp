<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer;

use BadMethodCallException;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * TransportFactory Test class
 */
class TransportFactoryTest extends TestCase
{
    /**
     * @var array
     */
    protected $transports;

    public function setUp(): void
    {
        parent::setUp();
        $this->transports = [
            'debug' => [
                'className' => 'Debug',
            ],
            'badClassName' => [
                'className' => 'TestFalse',
            ],
        ];
        TransportFactory::setConfig($this->transports);
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        TransportFactory::drop('debug');
        TransportFactory::drop('badClassName');
        TransportFactory::drop('test_smtp');
    }

    /**
     * Test that using misconfigured transports fails.
     */
    public function testGetMissingClassName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transport config `debug` is invalid, the required `className` option is missing');

        TransportFactory::drop('debug');
        TransportFactory::setConfig('debug', []);

        TransportFactory::get('debug');
    }

    /**
     * Test configuring a transport.
     */
    public function testSetConfig(): void
    {
        $settings = [
            'className' => 'Debug',
            'log' => true,
        ];
        TransportFactory::drop('debug');
        TransportFactory::setConfig('debug', $settings);

        $result = TransportFactory::getConfig('debug');
        $this->assertEquals($settings, $result);
    }

    /**
     * Test configuring multiple transports.
     */
    public function testSetConfigMultiple(): void
    {
        $settings = [
            'debug' => [
                'className' => 'Debug',
                'log' => true,
            ],
            'test_smtp' => [
                'className' => 'Smtp',
                'username' => 'mark',
                'password' => 'password',
                'host' => 'example.com',
            ],
        ];
        TransportFactory::drop('debug');
        TransportFactory::setConfig($settings);
        $this->assertEquals($settings['debug'], TransportFactory::getConfig('debug'));
        $this->assertEquals($settings['test_smtp'], TransportFactory::getConfig('test_smtp'));
    }

    /**
     * Test that exceptions are raised when duplicate transports are configured.
     */
    public function testSetConfigErrorOnDuplicate(): void
    {
        $this->expectException(BadMethodCallException::class);
        $settings = [
            'className' => 'Debug',
            'log' => true,
        ];
        TransportFactory::setConfig('debug', $settings);
        TransportFactory::setConfig('debug', $settings);
        TransportFactory::drop('debug');
    }

    /**
     * Test configTransport with an instance.
     */
    public function testSetConfigInstance(): void
    {
        TransportFactory::drop('debug');
        $instance = new DebugTransport();
        TransportFactory::setConfig('debug', $instance);
        $this->assertEquals(['className' => $instance], TransportFactory::getConfig('debug'));
    }

    /**
     * Test enumerating all transport configurations
     */
    public function testConfigured(): void
    {
        $result = TransportFactory::configured();
        $this->assertIsArray($result, 'Should have config keys');
        foreach (array_keys($this->transports) as $key) {
            $this->assertContains($key, $result, 'Loaded transports should be present.');
        }
    }

    /**
     * Test dropping a transport configuration
     */
    public function testDrop(): void
    {
        $result = TransportFactory::getConfig('debug');
        $this->assertIsArray($result, 'Should have config data');
        TransportFactory::drop('debug');
        $this->assertNull(TransportFactory::getConfig('debug'), 'Should not exist.');
    }
}
