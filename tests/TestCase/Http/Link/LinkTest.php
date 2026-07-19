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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Link;

use Cake\Http\Link\Link;
use Cake\TestSuite\TestCase;
use Psr\Link\EvolvableLinkInterface;
use Psr\Link\LinkInterface;

/**
 * LinkTest class
 */
class LinkTest extends TestCase
{
    /**
     * Test that Link implements PSR-13 interfaces.
     */
    public function testImplementsInterfaces(): void
    {
        $link = new Link('/api/users');
        $this->assertInstanceOf(LinkInterface::class, $link);
        $this->assertInstanceOf(EvolvableLinkInterface::class, $link);
    }

    /**
     * Test getHref.
     */
    public function testGetHref(): void
    {
        $link = new Link('/api/users');
        $this->assertSame('/api/users', $link->getHref());
    }

    /**
     * Test getRels with string.
     */
    public function testGetRelsString(): void
    {
        $link = new Link('/api/users', 'self');
        $this->assertSame(['self'], $link->getRels());
    }

    /**
     * Test getRels with array.
     */
    public function testGetRelsArray(): void
    {
        $link = new Link('/api/users', ['self', 'collection']);
        $this->assertSame(['self', 'collection'], $link->getRels());
    }

    /**
     * Test getAttributes.
     */
    public function testGetAttributes(): void
    {
        $link = new Link('/api/users', 'self', ['type' => 'application/json']);
        $this->assertSame(['type' => 'application/json'], $link->getAttributes());
    }

    /**
     * Test isTemplated returns false for non-templated URI.
     */
    public function testIsTemplatedFalse(): void
    {
        $link = new Link('/api/users');
        $this->assertFalse($link->isTemplated());
    }

    /**
     * Test isTemplated returns true for templated URI.
     */
    public function testIsTemplatedTrue(): void
    {
        $link = new Link('/api/users/{id}');
        $this->assertTrue($link->isTemplated());
    }

    /**
     * Test withHref returns new instance.
     */
    public function testWithHref(): void
    {
        $link = new Link('/api/users');
        $new = $link->withHref('/api/posts');

        $this->assertNotSame($link, $new);
        $this->assertSame('/api/users', $link->getHref());
        $this->assertSame('/api/posts', $new->getHref());
    }

    /**
     * Test withRel adds relation.
     */
    public function testWithRel(): void
    {
        $link = new Link('/api/users', 'self');
        $new = $link->withRel('collection');

        $this->assertNotSame($link, $new);
        $this->assertSame(['self'], $link->getRels());
        $this->assertSame(['self', 'collection'], $new->getRels());
    }

    /**
     * Test withRel does not duplicate relations.
     */
    public function testWithRelNoDuplicates(): void
    {
        $link = new Link('/api/users', 'self');
        $new = $link->withRel('self');

        $this->assertSame(['self'], $new->getRels());
    }

    /**
     * Test withoutRel removes relation.
     */
    public function testWithoutRel(): void
    {
        $link = new Link('/api/users', ['self', 'collection']);
        $new = $link->withoutRel('self');

        $this->assertNotSame($link, $new);
        $this->assertSame(['self', 'collection'], $link->getRels());
        $this->assertSame(['collection'], $new->getRels());
    }

    /**
     * Test withAttribute adds attribute.
     */
    public function testWithAttribute(): void
    {
        $link = new Link('/api/users');
        $new = $link->withAttribute('type', 'application/json');

        $this->assertNotSame($link, $new);
        $this->assertSame([], $link->getAttributes());
        $this->assertSame(['type' => 'application/json'], $new->getAttributes());
    }

    /**
     * Test withAttribute overwrites existing attribute.
     */
    public function testWithAttributeOverwrite(): void
    {
        $link = new Link('/api/users', [], ['type' => 'text/html']);
        $new = $link->withAttribute('type', 'application/json');

        $this->assertSame(['type' => 'application/json'], $new->getAttributes());
    }

    /**
     * Test withoutAttribute removes attribute.
     */
    public function testWithoutAttribute(): void
    {
        $link = new Link('/api/users', [], ['type' => 'application/json', 'title' => 'Users']);
        $new = $link->withoutAttribute('type');

        $this->assertNotSame($link, $new);
        $this->assertSame(['type' => 'application/json', 'title' => 'Users'], $link->getAttributes());
        $this->assertSame(['title' => 'Users'], $new->getAttributes());
    }
}
