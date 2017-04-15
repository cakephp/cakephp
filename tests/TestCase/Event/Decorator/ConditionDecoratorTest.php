<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Event;

use Cake\Event\Decorator\ConditionDecorator;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;

/**
 * Tests the Cake\Event\Event class functionality
 */
class ConditionDecoratorTest extends TestCase
{

    /**
     * testCanTriggerIf
     *
     * @return void
     */
    public function testCanTriggerIf()
    {
        $callable = function (Event $event) {
            return 'success';
        };

        $decorator = new ConditionDecorator($callable, [
            'if' => function (Event $event) {
                return $event->data('canTrigger');
            }
        ]);

        $event = new Event('decorator.test', $this);
        $this->assertFalse($decorator->canTrigger($event));

        $result = $decorator($event);
        $this->assertNull($result);

        $event = new Event('decorator.test', $this, ['canTrigger' => true]);
        $this->assertTrue($decorator->canTrigger($event));

        $result = $decorator($event);
        $this->assertEquals('success', $result);
    }

    /**
     * testCascadingEvents
     *
     * @return void
     */
    public function testCascadingEvents()
    {
        $callable = function (Event $event) {
            $event->setData('counter', $event->data('counter') + 1);

            return $event;
        };

        $listener1 = new ConditionDecorator($callable, [
            'if' => function (Event $event) {
                return false;
            }
        ]);

        $listener2 = function (Event $event) {
            $event->setData('counter', $event->data('counter') + 1);

            return $event;
        };

        EventManager::instance()->on('decorator.test2', $listener1);
        EventManager::instance()->on('decorator.test2', $listener2);

        $event = new Event('decorator.test2', $this, [
            'counter' => 1
        ]);

        EventManager::instance()->dispatch($event);
        $this->assertEquals(2, $event->data('counter'));
    }

    /**
     * testCallableRuntimeException
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cake\Event\Decorator\ConditionDecorator the `if` condition is not a callable!
     */
    public function testCallableRuntimeException()
    {
        $callable = function (Event $event) {
            return 'success';
        };

        $decorator = new ConditionDecorator($callable, [
            'if' => 'not a callable'
        ]);

        $event = new Event('decorator.test', $this, []);
        $decorator($event);
    }
}
