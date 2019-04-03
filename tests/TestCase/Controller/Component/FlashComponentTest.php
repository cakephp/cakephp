<?php
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

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\FlashComponent;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\Network\Session;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * FlashComponentTest class
 */
class FlashComponentTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
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
    public function tearDown()
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
    public function testSet()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->set('This is a test message');
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'Flash/default',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->set('This is a test message', ['element' => 'test', 'params' => ['foo' => 'bar']]);
        $expected[] = [
            'message' => 'This is a test message',
            'key' => 'flash',
            'element' => 'Flash/test',
            'params' => ['foo' => 'bar']
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->set('This is a test message', ['element' => 'MyPlugin.alert']);
        $expected[] = [
            'message' => 'This is a test message',
            'key' => 'flash',
            'element' => 'MyPlugin.Flash/alert',
            'params' => []
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->set('This is a test message', ['key' => 'foobar']);
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'foobar',
                'element' => 'Flash/default',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.foobar');
        $this->assertEquals($expected, $result);
    }

    public function testDuplicateIgnored()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->config('duplicate', false);
        $this->Flash->set('This test message should appear once only');
        $this->Flash->set('This test message should appear once only');
        $result = $this->Session->read('Flash.flash');
        $this->assertCount(1, $result);
    }

    /**
     * @return void
     */
    public function testSetEscape()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->set('This is a <b>test</b> message', ['escape' => false, 'params' => ['foo' => 'bar']]);
        $expected = [
            [
                'message' => 'This is a <b>test</b> message',
                'key' => 'flash',
                'element' => 'Flash/default',
                'params' => ['foo' => 'bar', 'escape' => false]
            ]
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->set('This is a test message', ['key' => 'escaped', 'escape' => false, 'params' => ['foo' => 'bar', 'escape' => true]]);
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'escaped',
                'element' => 'Flash/default',
                'params' => ['foo' => 'bar', 'escape' => true]
            ]
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
    public function testSetWithClear()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->set('This is a test message');
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'Flash/default',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->set('This is another test message', ['clear' => true]);
        $expected = [
            [
                'message' => 'This is another test message',
                'key' => 'flash',
                'element' => 'Flash/default',
                'params' => []
            ]
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
    public function testSetWithException()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->set(new Exception('This is a test message', 404));
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'Flash/default',
                'params' => ['code' => 404]
            ]
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
    }

    /**
     * testSetWithComponentConfiguration method
     *
     * @return void
     */
    public function testSetWithComponentConfiguration()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Controller->loadComponent('Flash', ['element' => 'test']);
        $this->Controller->Flash->set('This is a test message');
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'Flash/test',
                'params' => []
            ]
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
    public function testCall()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->success('It worked');
        $expected = [
            [
                'message' => 'It worked',
                'key' => 'flash',
                'element' => 'Flash/success',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $this->Flash->error('It did not work', ['element' => 'error_thing']);

        $expected[] = [
            'message' => 'It did not work',
            'key' => 'flash',
            'element' => 'Flash/error',
            'params' => []
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result, 'Element is ignored in magic call.');

        $this->Flash->success('It worked', ['plugin' => 'MyPlugin']);

        $expected[] = [
            'message' => 'It worked',
            'key' => 'flash',
            'element' => 'MyPlugin.Flash/success',
            'params' => []
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
    public function testCallWithClear()
    {
        $this->assertNull($this->Session->read('Flash.flash'));
        $this->Flash->success('It worked');
        $expected = [
            [
                'message' => 'It worked',
                'key' => 'flash',
                'element' => 'Flash/success',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
        $this->Flash->success('It worked too', ['clear' => true]);
        $expected = [
            [
                'message' => 'It worked too',
                'key' => 'flash',
                'element' => 'Flash/success',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
    }
}
