<?php
declare(strict_types=1);

/**
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Event\Decorator;

use Cake\Event\Decorator\ConditionDecorator;
use Cake\Event\Event;
use Cake\Event\EventInterface;
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
        $callable = function (EventInterface $event) {
            return 'success';
        };

        $decorator = new ConditionDecorator($callable, [
            'if' => function (EventInterface $event) {
                return $event->getData('canTrigger');
            },
        ]);

        $event = new Event('decorator.test', $this);
        $this->assertFalse($decorator->canTrigger($event));

        $result = $decorator($event);
        $this->assertNull($result);

        $event = new Event('decorator.test', $this, ['canTrigger' => true]);
        $this->assertTrue($decorator->canTrigger($event));

        $result = $decorator($event);
        $this->assertSame('success', $result);
    }

    /**
     * testCascadingEvents
     *
     * @return void
     */
    public function testCascadingEvents()
    {
        $callable = function (EventInterface $event) {
            $event->setData('counter', $event->getData('counter') + 1);

            return $event;
        };

        $listener1 = new ConditionDecorator($callable, [
            'if' => function (EventInterface $event) {
                return false;
            },
        ]);

        $listener2 = function (EventInterface $event) {
            $event->setData('counter', $event->getData('counter') + 1);

            return $event;
        };

        EventManager::instance()->on('decorator.test2', $listener1);
        EventManager::instance()->on('decorator.test2', $listener2);

        $event = new Event('decorator.test2', $this, [
            'counter' => 1,
        ]);

        EventManager::instance()->dispatch($event);
        $this->assertSame(2, $event->getData('counter'));
    }

    /**
     * testCallableRuntimeException
     */
    public function testCallableRuntimeException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cake\Event\Decorator\ConditionDecorator the `if` condition is not a callable!');
        $callable = function (EventInterface $event) {
            return 'success';
        };

        $decorator = new ConditionDecorator($callable, [
            'if' => 'not a callable',
        ]);

        $event = new Event('decorator.test', $this, []);
        $decorator($event);
    }
}
