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
use Cake\Event\Decorator\SubjectFilterDecorator;
use Cake\TestSuite\TestCase;

/**
 * Tests the Cake\Event\Event class functionality
 */
class SubjectFilterDecoratorTest extends TestCase
{

    /**
     * testCanTrigger
     *
     * @return void
     */
    public function testCanTrigger()
    {
        $event = new Event('decorator.test', $this);
        $callable = function(Event $event) {
            return 'success';
        };

        $decorator = new SubjectFilterDecorator($callable, [
            'allowedSubject' => self::class
        ]);

        $this->assertTrue($decorator->canTrigger($event));
        $this->assertEquals('success', $decorator($event));

        $decorator = new SubjectFilterDecorator($callable, [
            'allowedSubject' => '\Some\Other\Class'
        ]);

        $this->assertFalse($decorator->canTrigger($event));
        $this->assertFalse($decorator($event));
    }
}
