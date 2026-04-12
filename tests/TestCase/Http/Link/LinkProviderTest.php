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
use Cake\Http\Link\LinkProvider;
use Cake\TestSuite\TestCase;
use Psr\Link\EvolvableLinkProviderInterface;
use Psr\Link\LinkProviderInterface;

/**
 * LinkProviderTest class
 */
class LinkProviderTest extends TestCase
{
    /**
     * Test that LinkProvider implements PSR-13 interfaces.
     */
    public function testImplementsInterfaces(): void
    {
        $provider = new LinkProvider();
        $this->assertInstanceOf(LinkProviderInterface::class, $provider);
        $this->assertInstanceOf(EvolvableLinkProviderInterface::class, $provider);
    }

    /**
     * Test getLinks returns empty array by default.
     */
    public function testGetLinksEmpty(): void
    {
        $provider = new LinkProvider();
        $this->assertSame([], iterator_to_array($provider->getLinks()));
    }

    /**
     * Test getLinks with initial links.
     */
    public function testGetLinksWithInitial(): void
    {
        $link1 = new Link('/api/users', 'self');
        $link2 = new Link('/api/users?page=2', 'next');

        $provider = new LinkProvider([$link1, $link2]);
        $links = iterator_to_array($provider->getLinks());

        $this->assertCount(2, $links);
        $this->assertSame($link1, $links[0]);
        $this->assertSame($link2, $links[1]);
    }

    /**
     * Test getLinksByRel.
     */
    public function testGetLinksByRel(): void
    {
        $link1 = new Link('/api/users', 'self');
        $link2 = new Link('/api/users?page=2', 'next');
        $link3 = new Link('/api', 'self');

        $provider = new LinkProvider([$link1, $link2, $link3]);
        $selfLinks = iterator_to_array($provider->getLinksByRel('self'));

        $this->assertCount(2, $selfLinks);
        $this->assertContains($link1, $selfLinks);
        $this->assertContains($link3, $selfLinks);
    }

    /**
     * Test getLinksByRel with no matches.
     */
    public function testGetLinksByRelNoMatches(): void
    {
        $link = new Link('/api/users', 'self');
        $provider = new LinkProvider([$link]);

        $links = iterator_to_array($provider->getLinksByRel('next'));
        $this->assertEmpty($links);
    }

    /**
     * Test withLink adds link.
     */
    public function testWithLink(): void
    {
        $link = new Link('/api/users', 'self');
        $provider = new LinkProvider();

        $new = $provider->withLink($link);

        $this->assertNotSame($provider, $new);
        $this->assertEmpty(iterator_to_array($provider->getLinks()));

        $newLinks = iterator_to_array($new->getLinks());
        $this->assertCount(1, $newLinks);
        $this->assertSame($link, $newLinks[0]);
    }

    /**
     * Test withLink does not duplicate same link instance.
     */
    public function testWithLinkNoDuplicates(): void
    {
        $link = new Link('/api/users', 'self');
        $provider = new LinkProvider([$link]);

        $new = $provider->withLink($link);

        $links = iterator_to_array($new->getLinks());
        $this->assertCount(1, $links);
    }

    /**
     * Test withoutLink removes link.
     */
    public function testWithoutLink(): void
    {
        $link1 = new Link('/api/users', 'self');
        $link2 = new Link('/api/users?page=2', 'next');

        $provider = new LinkProvider([$link1, $link2]);
        $new = $provider->withoutLink($link1);

        $this->assertNotSame($provider, $new);

        $originalLinks = iterator_to_array($provider->getLinks());
        $this->assertCount(2, $originalLinks);

        $newLinks = iterator_to_array($new->getLinks());
        $this->assertCount(1, $newLinks);
        $this->assertSame($link2, $newLinks[0]);
    }

    /**
     * Test withoutLink with non-existent link.
     */
    public function testWithoutLinkNotFound(): void
    {
        $link1 = new Link('/api/users', 'self');
        $link2 = new Link('/api/users?page=2', 'next');

        $provider = new LinkProvider([$link1]);
        $new = $provider->withoutLink($link2);

        $links = iterator_to_array($new->getLinks());
        $this->assertCount(1, $links);
    }
}
