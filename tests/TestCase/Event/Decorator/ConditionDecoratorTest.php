<?php
/**
 * EventTest file
 *
 * Test Case for Event class
 *
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

use Cake\Event\Event;
use Cake\Event\Decorator\ConditionDecorator;
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
        $callable = function(Event $event) {
            return 'success';
        };

        $decorator = new ConditionDecorator($callable, [
            'if' => function(Event $event) {
                if (isset($event->data['canTrigger'])) {
                    return true;
                }
                return false;
            }
        ]);

        $event = new Event('decorator.test', $this);
        $this->assertFalse($decorator->canTrigger($event));

        $result = $decorator($event);
        $this->assertFalse($result);

        $event = new Event('decorator.test', $this, ['canTrigger' => true]);
        $this->assertTrue($decorator->canTrigger($event));

        $result = $decorator($event);
        $this->assertEquals('success', $result);
    }

    /**
     * testCallableRuntimeException
     *
     * @expectedException \RuntimeException
     */
    public function testCallableRuntimeException()
    {
        $callable = function(Event $event) {
            return 'success';
        };

        $decorator = new ConditionDecorator($callable, [
            'if' => 'not a callable'
        ]);

        $event = new Event('decorator.test', $this, []);
        $decorator($event);
    }
}
