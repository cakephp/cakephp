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
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\FlashMessage;
use Cake\Http\Session;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * FlashMessageTest class
 */
class FlashMessageTest extends TestCase
{
    /**
     * @var \Cake\Http\FlashMessage
     */
    protected $Flash;

    /**
     * @var \Cake\Http\Session
     */
    protected $Session;

    public function setUp(): void
    {
        parent::setUp();

        static::setAppNamespace();
        $this->Session = new Session();
        $this->Flash = new FlashMessage($this->Session);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->Session->destroy();
    }

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

        $this->Flash->set(
            'This is a test message',
            ['element' => 'test', 'params' => ['foo' => 'bar']]
        );
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

    public function testDefaultParamsOverriding(): void
    {
        $this->Flash = new FlashMessage(
            $this->Session,
            ['params' => ['foo' => 'bar']]
        );

        $this->Flash->set(
            'This is a test message',
            ['params' => ['username' => 'ADmad']]
        );
        $expected[] = [
            'message' => 'This is a test message',
            'key' => 'flash',
            'element' => 'flash/default',
            'params' => ['username' => 'ADmad'],
        ];
        $result = $this->Session->read('Flash.flash');
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

        $this->Flash->set(
            'This is a <b>test</b> message',
            ['escape' => false, 'params' => ['foo' => 'bar']]
        );
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

        $this->Flash->set(
            'This is a test message',
            ['key' => 'escaped', 'escape' => false, 'params' => ['foo' => 'bar', 'escape' => true]]
        );
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

    public function testSetWithPlugin(): void
    {
        $this->Flash->set('This is a test message', ['plugin' => 'FooBar']);
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'FooBar.flash/default',
                'params' => [],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);

        // Value of 'plugin' will override the plugin name used in 'element'
        $this->Flash->set('This is a test message', [
            'key' => 'msg',
            'element' => 'Plugin.success',
            'plugin' => 'FooBar',
        ]);
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'msg',
                'element' => 'FooBar.flash/success',
                'params' => [],
            ],
        ];
        $result = $this->Session->read('Flash.msg');
        $this->assertEquals($expected, $result);
    }

    public function testSetExceptionMessage(): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->setExceptionMessage(new Exception('This is a test message', 404));
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

        $this->Flash->setExceptionMessage(
            new Exception('This is a test message'),
            ['element' => 'default', 'clear' => true]
        );
        $expected = [
            [
                'message' => 'This is a test message',
                'key' => 'flash',
                'element' => 'flash/default',
                'params' => ['code' => null],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
    }

    public function testSetWithConstructorConfiguration(): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $flash = new FlashMessage($this->Session, ['element' => 'test']);
        $flash->set('This is a test message');
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
     * @dataProvider convenienceMethods
     */
    public function testConvenienceMethods(string $type): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->{$type}('It worked');
        $expected = [
            [
                'message' => 'It worked',
                'key' => 'flash',
                'element' => 'flash/' . $type,
                'params' => [],
            ],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result);
    }

    public function convenienceMethods(): array
    {
        return [
            ['success'],
            ['error'],
            ['warning'],
            ['info'],
        ];
    }

    public function testSuccessWithClear(): void
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

    public function testError(): void
    {
        $this->assertNull($this->Session->read('Flash.flash'));

        $this->Flash->error('It did not work', ['element' => 'error_thing']);

        $expected[] = [
            'message' => 'It did not work',
            'key' => 'flash',
            'element' => 'flash/error',
            'params' => [],
        ];
        $result = $this->Session->read('Flash.flash');
        $this->assertEquals($expected, $result, 'Element is ignored in convenience method call.');
    }
}
