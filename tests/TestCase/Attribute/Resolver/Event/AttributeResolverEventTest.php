<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Attribute\Resolver\Event;

use Cake\Attribute\Resolver;
use Cake\Attribute\Resolver\AttributeCollection;
use Cake\Attribute\Resolver\Event\AfterArtifactsClearEvent;
use Cake\Attribute\Resolver\Event\AfterResolveEvent;
use Cake\Attribute\Resolver\Event\AfterScanEvent;
use Cake\Attribute\Resolver\Event\BeforeArtifactsClearEvent;
use Cake\Attribute\Resolver\Event\BeforeResolveEvent;
use Cake\Attribute\Resolver\Event\BeforeScanEvent;
use Cake\Event\Event;
use Cake\TestSuite\TestCase;

/**
 * Attribute Resolver Event Test Case
 *
 * Tests all attribute resolver events for proper structure and functionality.
 */
class AttributeResolverEventTest extends TestCase
{
    /**
     * Test BeforeResolveEvent structure and methods
     */
    public function testBeforeResolveEvent(): void
    {
        $resolver = $this->createStub(Resolver::class);
        $event = new BeforeResolveEvent($resolver);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame(BeforeResolveEvent::NAME, $event->getName());
        $this->assertSame('Attribute.Resolver.beforeResolve', $event->getName());
        $this->assertSame($resolver, $event->getSubject());
    }

    /**
     * Test AfterResolveEvent structure and methods
     */
    public function testAfterResolveEvent(): void
    {
        $resolver = $this->createStub(Resolver::class);
        $collection = new AttributeCollection([]);
        $event = new AfterResolveEvent($resolver, $collection);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame(AfterResolveEvent::NAME, $event->getName());
        $this->assertSame('Attribute.Resolver.afterResolve', $event->getName());
        $this->assertSame($resolver, $event->getSubject());
        $this->assertSame($collection, $event->getCollection());
    }

    /**
     * Test BeforeScanEvent structure and methods
     */
    public function testBeforeScanEvent(): void
    {
        $resolver = $this->createStub(Resolver::class);
        $event = new BeforeScanEvent($resolver);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame(BeforeScanEvent::NAME, $event->getName());
        $this->assertSame('Attribute.Resolver.beforeScan', $event->getName());
        $this->assertSame($resolver, $event->getSubject());
    }

    /**
     * Test AfterScanEvent structure and methods
     */
    public function testAfterScanEvent(): void
    {
        $resolver = $this->createStub(Resolver::class);
        $collection = new AttributeCollection([]);
        $scannedFiles = ['file1.php', 'file2.php'];
        $event = new AfterScanEvent($resolver, $collection, $scannedFiles);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame(AfterScanEvent::NAME, $event->getName());
        $this->assertSame('Attribute.Resolver.afterScan', $event->getName());
        $this->assertSame($resolver, $event->getSubject());
        $this->assertSame($collection, $event->getCollection());
        $this->assertSame($scannedFiles, $event->getScannedFiles());
        $this->assertSame(2, $event->getFileCount());
        $this->assertSame(0, $event->getAttributeCount());
    }

    /**
     * Test BeforeArtifactsClearEvent structure and methods
     */
    public function testBeforeArtifactsClearEvent(): void
    {
        $resolver = $this->createStub(Resolver::class);
        $event = new BeforeArtifactsClearEvent($resolver);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame(BeforeArtifactsClearEvent::NAME, $event->getName());
        $this->assertSame('Attribute.Resolver.beforeArtifactsClear', $event->getName());
        $this->assertSame($resolver, $event->getSubject());
    }

    /**
     * Test AfterArtifactsClearEvent structure and methods
     */
    public function testAfterArtifactsClearEvent(): void
    {
        $resolver = $this->createStub(Resolver::class);
        $event = new AfterArtifactsClearEvent($resolver, true);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame(AfterArtifactsClearEvent::NAME, $event->getName());
        $this->assertSame('Attribute.Resolver.afterArtifactsClear', $event->getName());
        $this->assertSame($resolver, $event->getSubject());
        $this->assertTrue($event->isSuccess());

        $failEvent = new AfterArtifactsClearEvent($resolver, false);
        $this->assertFalse($failEvent->isSuccess());
    }

    /**
     * Test that Before* events can be stopped
     */
    public function testBeforeEventsAreStoppable(): void
    {
        $resolver = $this->createStub(Resolver::class);

        $beforeResolve = new BeforeResolveEvent($resolver);
        $this->assertFalse($beforeResolve->isStopped());
        $beforeResolve->stopPropagation();
        $this->assertTrue($beforeResolve->isStopped());

        $beforeScan = new BeforeScanEvent($resolver);
        $this->assertFalse($beforeScan->isStopped());
        $beforeScan->stopPropagation();
        $this->assertTrue($beforeScan->isStopped());

        $beforeClear = new BeforeArtifactsClearEvent($resolver);
        $this->assertFalse($beforeClear->isStopped());
        $beforeClear->stopPropagation();
        $this->assertTrue($beforeClear->isStopped());
    }

    /**
     * Test that After* events can be stopped (though it has no effect)
     */
    public function testAfterEventsCanBeStopped(): void
    {
        $resolver = $this->createStub(Resolver::class);
        $collection = new AttributeCollection([]);

        $afterResolve = new AfterResolveEvent($resolver, $collection);
        $this->assertFalse($afterResolve->isStopped());
        $afterResolve->stopPropagation();
        $this->assertTrue($afterResolve->isStopped());

        $afterScan = new AfterScanEvent($resolver, $collection, []);
        $this->assertFalse($afterScan->isStopped());
        $afterScan->stopPropagation();
        $this->assertTrue($afterScan->isStopped());

        $afterClear = new AfterArtifactsClearEvent($resolver, true);
        $this->assertFalse($afterClear->isStopped());
        $afterClear->stopPropagation();
        $this->assertTrue($afterClear->isStopped());
    }

    /**
     * Test event name constants follow naming convention
     */
    public function testEventNameConstants(): void
    {
        $this->assertSame('Attribute.Resolver.beforeResolve', BeforeResolveEvent::NAME);
        $this->assertSame('Attribute.Resolver.afterResolve', AfterResolveEvent::NAME);
        $this->assertSame('Attribute.Resolver.beforeScan', BeforeScanEvent::NAME);
        $this->assertSame('Attribute.Resolver.afterScan', AfterScanEvent::NAME);
        $this->assertSame('Attribute.Resolver.beforeArtifactsClear', BeforeArtifactsClearEvent::NAME);
        $this->assertSame('Attribute.Resolver.afterArtifactsClear', AfterArtifactsClearEvent::NAME);
    }
}
