<?php
declare(strict_types=1);

/**
 * EventTest file
 *
 * Test Case for Event class
 *
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         2.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Event;

use ArrayObject;
use Cake\Core\Exception\CakeException;
use Cake\Event\Event;
use Cake\TestSuite\TestCase;

/**
 * Tests the Cake\Event\Event class functionality
 */
class EventTest extends TestCase
{
    /**
     * Tests the name() method
     *
     * @triggers fake.event
     */
    public function testName(): void
    {
        $event = new Event('fake.event');
        $this->assertSame('fake.event', $event->getName());
    }

    /**
     * Tests the subject() method
     *
     * @triggers fake.event $this
     * @triggers fake.event
     */
    public function testSubject(): void
    {
        $event = new Event('fake.event', $this);
        $this->assertSame($this, $event->getSubject());

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('No subject set for this event');

        $event = new Event('fake.event');
        $this->assertNull($event->getSubject());
    }

    /**
     * Tests the event propagation stopping property
     *
     * @triggers fake.event
     */
    public function testPropagation(): void
    {
        $event = new Event('fake.event');
        $this->assertFalse($event->isStopped());
        $event->stopPropagation();
        $this->assertTrue($event->isStopped());
    }

    /**
     * Tests that it is possible to get/set custom data in a event
     *
     * @triggers fake.event $this, array('some' => 'data')
     */
    public function testEventData(): void
    {
        $event = new Event('fake.event', $this, ['some' => 'data']);
        $this->assertEquals(['some' => 'data'], $event->getData());

        $this->assertSame('data', $event->getData('some'));
        $this->assertNull($event->getData('undef'));
    }

    /**
     * Tests that it is possible to get/set custom data in a event
     *
     * @triggers fake.event $this, array('some' => 'data')
     */
    public function testEventDataObject(): void
    {
        $data = new ArrayObject(['some' => 'data']);
        $event = new Event('fake.event', $this, $data);
        $this->assertEquals(['some' => 'data'], $event->getData());

        $this->assertSame('data', $event->getData('some'));
        $this->assertNull($event->getData('undef'));
    }

    /**
     * Tests that it is possible to get the name and subject directly
     *
     * @triggers fake.event $this
     */
    public function testEventDirectPropertyAccess(): void
    {
        $event = new Event('fake.event', $this);
        $this->assertEquals($this, $event->getSubject());
        $this->assertSame('fake.event', $event->getName());
    }
}
