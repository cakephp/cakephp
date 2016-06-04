<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Event;

use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;

class TestEventDispatcherObject
{
    use EventDispatcherTrait;

    /**
     * @var bool
     */
    public $handlerCalled = false;

    public function trueCondition()
    {
        return true;
    }

    public function falseCondition()
    {
        return false;
    }
    
    public function handler()
    {
        $this->handlerCalled = true;
    }
}

/**
 * EventDispatcherTrait test case
 *
 */
class EventDispatcherTraitTest extends TestCase
{
    /**
     * @var TestEventDispatcherObject
     */
    private $subject;

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->subject = new TestEventDispatcherObject();
    }

    /**
     * testIsInitiallyEmpty
     *
     * @return void
     */
    public function testIsInitiallyEmpty()
    {
        $this->assertAttributeEmpty('_eventManager', $this->subject);
    }

    /**
     * testSettingEventManager
     *
     * @covers \Cake\Event\EventDispatcherTrait::eventManager
     * @return void
     */
    public function testSettingEventManager()
    {
        $eventManager = new EventManager();

        $this->subject->eventManager($eventManager);

        $this->assertSame($eventManager, $this->subject->eventManager());
    }

    /**
     * testDispatchEvent
     *
     * @return void
     */
    public function testDispatchEvent()
    {
        $event = $this->subject->dispatchEvent('some.event', ['foo' => 'bar']);

        $this->assertInstanceOf('Cake\Event\Event', $event);
        $this->assertSame($this->subject, $event->subject);
        $this->assertEquals('some.event', $event->name);
        $this->assertEquals(['foo' => 'bar'], $event->data);
    }

    public static function onProvider()
    {
        return [
            [[], true],
            [['if' => 'trueCondition'], true],
            [['if' => 'falseCondition'], false],
            [['unless' => 'trueCondition'], false],
            [['unless' => 'falseCondition'], true],
            [['if' => ['trueCondition', 'trueCondition']], true],
            [['if' => ['trueCondition', 'falseCondition']], false],
            [['unless' => ['trueCondition', 'trueCondition']], false],
            [['unless' => ['trueCondition', 'falseCondition']], true],
        ];
    }

    /**
     * Test that simplified callables work correctly.
     *
     * @param array $options
     * @param bool $expected
     * @dataProvider onProvider
     */
    public function testOn($options, $expected)
    {
        $this->subject->on('some.event', 'handler', $options);
        $this->subject->dispatchEvent('some.event');

        $this->assertEquals($expected, $this->subject->handlerCalled);
    }
}
