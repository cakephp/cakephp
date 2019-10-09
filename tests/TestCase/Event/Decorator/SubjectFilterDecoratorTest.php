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

use Cake\Event\Decorator\SubjectFilterDecorator;
use Cake\Event\Event;
use Cake\Event\EventInterface;
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
        $callable = function (EventInterface $event) {
            return 'success';
        };

        $decorator = new SubjectFilterDecorator($callable, [
            'allowedSubject' => self::class,
        ]);

        $this->assertTrue($decorator->canTrigger($event));
        $this->assertSame('success', $decorator($event));

        $decorator = new SubjectFilterDecorator($callable, [
            'allowedSubject' => '\Some\Other\Class',
        ]);

        $this->assertFalse($decorator->canTrigger($event));
        $this->assertFalse($decorator($event));
    }
}
