<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\FlashComponent;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\TestSuite\TestCase;

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
        Configure::write('App.namespace', 'TestApp');
        $this->Controller = new Controller(new Request(['session' => new Session()]));
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

    /**
     * Test a set() call with the reset flag to true
     *
     * @return void
     * @covers \Cake\Controller\Component\FlashComponent::set
     */
    public function testSetWithReset()
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

        $this->Flash->set('This is another test message', ['reset' => true]);
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

    public function testEdit()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $firstIndex = $this->Flash->set('This is a test message', ['key' => 'mykey', 'params' => ['title' => 'A title', 'subtitle' => 'A subtitle']]);
        $secondIndex = $this->Flash->set('This is another test message', ['key' => 'mykey']);
        $thirdIndex = $this->Flash->set('This is a third test message', ['key' => 'mykey']);

        $this->Flash->edit($secondIndex, 'Edited', 'mykey');
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'mykey',
                'element' => 'Flash/default',
                'params' => ['title' => 'A title', 'subtitle' => 'A subtitle']
            ],
            [
                'message' => 'Edited',
                'key' => 'mykey',
                'element' => 'Flash/default',
                'params' => []
            ],
            [
                'message' => 'This is a third test message',
                'key' => 'mykey',
                'element' => 'Flash/default',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.mykey');
        $this->assertEquals($expected, $result);

        $this->Flash->edit($firstIndex, ['message' => 'Edited'], 'mykey');
        $expected = [
            [
                'message' => 'Edited',
                'key' => 'mykey',
                'element' => 'Flash/default',
                'params' => ['title' => 'A title', 'subtitle' => 'A subtitle']
            ],
            [
                'message' => 'Edited',
                'key' => 'mykey',
                'element' => 'Flash/default',
                'params' => []
            ],
            [
                'message' => 'This is a third test message',
                'key' => 'mykey',
                'element' => 'Flash/default',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.mykey');
        $this->assertEquals($expected, $result);

        $this->Flash->edit(
            $firstIndex,
            [
                'message' => 'Edited',
                'element' => 'edited',
                'params' => ['title' => 'Some title']
            ],
            'mykey'
        );
        $expected = [
            [
                'message' => 'Edited',
                'key' => 'mykey',
                'element' => 'Flash/edited',
                'params' => ['title' => 'Some title', 'subtitle' => 'A subtitle']
            ],
            [
                'message' => 'Edited',
                'key' => 'mykey',
                'element' => 'Flash/default',
                'params' => []
            ],
            [
                'message' => 'This is a third test message',
                'key' => 'mykey',
                'element' => 'Flash/default',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.mykey');
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

        $this->Flash->set(new \Exception('This is a test message', 404));
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
     * Tests that set() can stack messages
     *
     * @return void
     */
    public function testSetReturnIndex()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->config('stacking.enabled', true);

        $firstIndex = $this->Flash->set('This is a test message');
        $secondIndex = $this->Flash->set('This is another test message');

        $this->assertEquals($firstIndex, 0);
        $this->assertEquals($secondIndex, 1);
    }

    /**
     * Tests that set() can stack messages after a first message
     * has already been set
     *
     * @return void
     */
    public function testSetWithLimit()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->config('stackLimit', 2);

        $this->Flash->set('This is a test message');
        $this->Flash->set('This is another test message');
        $this->assertEquals(2, count($this->Session->read('Flash.flash')));

        $this->Flash->set('This is a third test message');
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals(2, count($result));
        $expected = [
            [
                'message' => 'This is another test message',
                'key' => 'flash',
                'element' => 'Flash/default',
                'params' => []
            ],
            [
                'message' => 'This is a third test message',
                'key' => 'flash',
                'element' => 'Flash/default',
                'params' => []
            ]
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that delete() can remove the flash message
     *
     * @return void
     */
    public function testDelete()
    {
        $this->assertNull($this->Session->read('Flash.flash'));
        $this->Flash->set('This is a test message');
        $this->Flash->delete();
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->set('This is a test message');
        $this->Flash->set('This is a test message', ['key' => 'mykey']);
        $this->Flash->delete();
        $this->assertNull($this->Session->read('Flash.flash'));

        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'mykey',
                'element' => 'Flash/default',
                'params' => []
            ]
        ];
        $this->assertEquals($expected, $this->Session->read('Flash.mykey'));
        $this->Flash->delete('mykey');

        $this->Flash->set('This is a test message');
        $secondIndex = $this->Flash->set('This is another test message');
        $this->Flash->set('This is a third test message');

        $this->Flash->delete('', $secondIndex);
        $expected = [
            0 => [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'Flash/default',
                'params' => []
            ],
            2 => [
                'message' => 'This is a third test message',
                'key' => 'flash',
                'element' => 'Flash/default',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals(2, count($result));
        $this->assertEquals($expected, $result);

        $newThirdIndex = $this->Flash->set('This is the new third test message');
        $expected[3] = [
            'message' => 'This is the new third test message',
            'key' => 'flash',
            'element' => 'Flash/default',
            'params' => []
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals(3, $newThirdIndex);
        $this->assertEquals($expected, $result);

        $this->Flash->delete();
        $result = $this->Session->read('Flash.flash');
        $this->assertNull($result);
    }

    /**
     * Tests that clear() can remove all of the flash messages
     * of the same type when flash are set with a magic call
     *
     * @return void
     */
    public function testClear()
    {
        $this->assertNull($this->Session->read('Flash.flash'));
        $this->Flash->set('This is a test message');
        $this->Flash->clear('default');
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->set('This is a test message', ['element' => 'test']);
        $this->Flash->set('This is another test message', ['element' => 'success']);
        $this->Flash->set('This is a third test message', ['element' => 'success']);
        $this->Flash->set('This is a test message with another key', ['element' => 'success', 'key' => 'mykey']);
        $this->Flash->set('This is second test message with another key', ['element' => 'success', 'key' => 'mykey']);
        $this->Flash->clear('success');

        $expected = [
            0 => [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'Flash/test',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        $expected = [
            0 => [
                'message' => 'This is a test message with another key',
                'key' => 'mykey',
                'element' => 'Flash/success',
                'params' => []
            ],
            1 => [
                'message' => 'This is second test message with another key',
                'key' => 'mykey',
                'element' => 'Flash/success',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.mykey');
        $this->assertEquals($expected, $result);
        $this->Flash->clear('success', 'mykey');
        $this->assertNull($this->Session->read('Flash.mykey'));
    }

    /**
     * Tests that delete() can remove flash messages from a stack
     *
     * @return void
     */
    public function testDeleteAll()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $firstIndex = $this->Flash->set('This is a test message');
        $secondIndex = $this->Flash->set('This is another test message');
        $thirdIndex = $this->Flash->set('This is a third test message');

        $this->Flash->delete('', $firstIndex);
        $this->Flash->delete('', $secondIndex);

        $result = $this->Session->read('Flash.flash');
        $this->assertEquals(1, count($result));

        $this->Flash->delete('', $thirdIndex);
        $result = $this->Session->read('Flash.flash');
        $this->assertNull($result);
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
     * Test a set() call with the reset flag to true
     *
     * @return void
     * @covers \Cake\Controller\Component\FlashComponent::set
     */
    public function testCallWithReset()
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

        $this->Flash->success('It worked too', ['reset' => true]);
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

    /**
     * Test magic call method with stacking enabled.
     *
     * @covers \Cake\Controller\Component\FlashComponent::__call
     * @return void
     */
    public function testCallReturnIndex()
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $firstIndex = $this->Flash->success('It worked');
        $secondIndex = $this->Flash->error('It did not work');
        $thirdIndex = $this->Flash->warning('Something unexpected occurred', ['plugin' => 'MyPlugin']);

        $this->assertEquals($firstIndex, 0);
        $this->assertEquals($secondIndex, 1);
        $this->assertEquals($thirdIndex, 2);
    }

    /**
     * Tests that delete() removes messages from a stack if stacking
     * is enabled and when messages are set with the magic call
     *
     * @return void
     */
    public function testDeleteWithCall()
    {
        $this->assertNull($this->Session->read('Flash.flash'));
        $this->Flash->success('This is a test message');
        $this->Flash->delete();
        $this->assertNull($this->Session->read('Flash.flash'));

        $firstIndex = $this->Flash->success('It worked');
        $secondIndex = $this->Flash->error('It did not work');
        $thirdIndex = $this->Flash->warning('Something unexpected occurred', ['plugin' => 'MyPlugin']);

        $this->Flash->delete('', $secondIndex);
        $result = $this->Session->read('Flash.flash');
        $expected = [
            0 => [
                'message' => 'It worked',
                'key' => 'flash',
                'element' => 'Flash/success',
                'params' => []
            ],
            2 => [
                'message' => 'Something unexpected occurred',
                'key' => 'flash',
                'element' => 'MyPlugin.Flash/warning',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals(2, count($result));
        $this->assertEquals($expected, $result);

        $newThirdIndex = $this->Flash->warning('Something unexpected occurred', ['plugin' => 'MyPlugin']);
        $expected[3] = [
            'message' => 'Something unexpected occurred',
            'key' => 'flash',
            'element' => 'MyPlugin.Flash/warning',
            'params' => []
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals(3, $newThirdIndex);
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that clear() can remove all of the flash messages
     * of the same type when flash are set with a magic call
     *
     * @return void
     */
    public function testClearWithCall()
    {
        $this->assertNull($this->Session->read('Flash.flash'));
        $this->Flash->success('This is a test message');
        $this->Flash->clear('success');
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->config('stacking', ['enabled' => true]);
        $this->Flash->success('It worked');
        $this->Flash->success('It worked too');
        $this->Flash->error('It did not work');
        $this->Flash->success('That worked as well');
        $this->Flash->warning('Something unexpected occurred', ['plugin' => 'MyPlugin']);
        $this->Flash->clear('success');

        $expected = [
            2 => [
                'message' => 'It did not work',
                'key' => 'flash',
                'element' => 'Flash/error',
                'params' => []
            ],
            4 => [
                'message' => 'Something unexpected occurred',
                'key' => 'flash',
                'element' => 'MyPlugin.Flash/warning',
                'params' => []
            ]
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
    }
}
