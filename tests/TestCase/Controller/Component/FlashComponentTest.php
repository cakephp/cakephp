<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\Component\FlashComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * FlashComponentTest class
 */
class FlashComponentTest extends TestCase
{
    /**
     * @var \Cake\Controller\Component\FlashComponent
     */
    protected $Flash;

    /**
     * @var \Cake\Http\Session
     */
    protected $Session;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
        $this->Controller = new Controller(new ServerRequest(['session' => new Session()]));
        $this->ComponentRegistry = new ComponentRegistry($this->Controller);
        $this->Flash = new FlashComponent($this->ComponentRegistry);
        $this->Session = new Session();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->Session->destroy();
    }

    /**
     * testSet method
     *
     * @return void
     * @covers \Cake\Controller\Component\FlashComponent::set
     */
    public function testSet(): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->set('This is a test message');
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'flash/default',
                'params' => [],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->set('This is a test message', ['element' => 'test', 'params' => ['foo' => 'bar']]);
        $expected[] = [
            'message' => 'This is a test message',
            'key' => 'flash',
            'element' => 'flash/test',
            'params' => ['foo' => 'bar'],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->set('This is a test message', ['element' => 'MyPlugin.alert']);
        $expected[] = [
            'message' => 'This is a test message',
            'key' => 'flash',
            'element' => 'MyPlugin.flash/alert',
            'params' => [],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->set('This is a test message', ['key' => 'foobar']);
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'foobar',
                'element' => 'flash/default',
                'params' => [],
            ],
        ];
        $result = $this->Session->read('Flash.foobar');
        $this->assertEquals($expected, $result);
    }

    public function testDuplicateIgnored(): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->setConfig('duplicate', false);
        $this->Flash->set('This test message should appear once only');
        $this->Flash->set('This test message should appear once only');
        $result = $this->Session->read('Flash.flash');
        $this->assertCount(1, $result);
    }

    public function testSetEscape(): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->set('This is a <b>test</b> message', ['escape' => false, 'params' => ['foo' => 'bar']]);
        $expected = [
            [
                'message' => 'This is a <b>test</b> message',
                'key' => 'flash',
                'element' => 'flash/default',
                'params' => ['foo' => 'bar', 'escape' => false],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->set('This is a test message', ['key' => 'escaped', 'escape' => false, 'params' => ['foo' => 'bar', 'escape' => true]]);
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'escaped',
                'element' => 'flash/default',
                'params' => ['foo' => 'bar', 'escape' => true],
            ],
        ];
        $result = $this->Session->read('Flash.escaped');
        $this->assertEquals($expected, $result);
    }

    /**
     * test setting messages with using the clear option
     *
     * @return void
     * @covers \Cake\Controller\Component\FlashComponent::set
     */
    public function testSetWithClear(): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->set('This is a test message');
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'flash/default',
                'params' => [],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->set('This is another test message', ['clear' => true]);
        $expected = [
            [
                'message' => 'This is another test message',
                'key' => 'flash',
                'element' => 'flash/default',
                'params' => [],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
    }

    /**
     * testSetWithException method
     *
     * @return void
     * @covers \Cake\Controller\Component\FlashComponent::set
     */
    public function testSetWithException(): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->set(new Exception('This is a test message', 404));
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'flash/error',
                'params' => ['code' => 404],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
    }

    /**
     * testSetWithComponentConfiguration method
     *
     * @return void
     */
    public function testSetWithComponentConfiguration(): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Controller->loadComponent('Flash', ['element' => 'test']);
        $this->Controller->Flash->set('This is a test message');
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'flash/test',
                'params' => [],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test magic call method.
     *
     * @covers \Cake\Controller\Component\FlashComponent::__call
     * @return void
     */
    public function testCall(): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->success('It worked');
        $expected = [
            [
                'message' => 'It worked',
                'key' => 'flash',
                'element' => 'flash/success',
                'params' => [],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->error('It did not work', ['element' => 'error_thing']);

        $expected[] = [
            'message' => 'It did not work',
            'key' => 'flash',
            'element' => 'flash/error',
            'params' => [],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result, 'Element is ignored in magic call.');

        $this->Flash->success('It worked', ['plugin' => 'MyPlugin']);

        $expected[] = [
            'message' => 'It worked',
            'key' => 'flash',
            'element' => 'MyPlugin.flash/success',
            'params' => [],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test a magic call with the "clear" flag to true
     *
     * @return void
     * @covers \Cake\Controller\Component\FlashComponent::set
     */
    public function testCallWithClear(): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));
        $this->Flash->success('It worked');
        $expected = [
            [
                'message' => 'It worked',
                'key' => 'flash',
                'element' => 'flash/success',
                'params' => [],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
        $this->Flash->success('It worked too', ['clear' => true]);
        $expected = [
            [
                'message' => 'It worked too',
                'key' => 'flash',
                'element' => 'flash/success',
                'params' => [],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
    }
}
