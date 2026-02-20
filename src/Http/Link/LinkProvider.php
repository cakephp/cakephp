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
namespace Cake\Http\Link;

use Psr\Link\EvolvableLinkProviderInterface;
use Psr\Link\LinkInterface;
use Traversable;

/**
 * PSR-13 LinkProvider implementation.
 *
 * Collects and provides hypermedia links.
 *
 * ### Usage
 *
 * ```php
 * $provider = new LinkProvider([
 *     new Link('/api/users', 'self'),
 *     new Link('/api/users?page=2', 'next'),
 * ]);
 *
 * foreach ($provider->getLinks() as $link) {
 *     echo $link->getHref();
 * }
 * ```
 */
class LinkProvider implements EvolvableLinkProviderInterface
{
    /**
     * The links.
     *
     * @var array<\Psr\Link\LinkInterface>
     */
    private array $links;

    /**
     * Constructor.
     *
     * @param iterable<\Psr\Link\LinkInterface> $links Initial links.
     */
    public function __construct(iterable $links = [])
    {
        $this->links = $links instanceof Traversable
            ? iterator_to_array($links)
            : $links;
    }

    /**
     * @inheritDoc
     */
    public function getLinks(): iterable
    {
        return $this->links;
    }

    /**
     * @inheritDoc
     */
    public function getLinksByRel(string $rel): iterable
    {
        return array_filter(
            $this->links,
            fn(LinkInterface $link): bool => in_array($rel, $link->getRels(), true),
        );
    }

    /**
     * @inheritDoc
     */
    public function withLink(LinkInterface $link): static
    {
        $new = clone $this;

        // Check if link already exists (by reference)
        foreach ($new->links as $existing) {
            if ($existing === $link) {
                return $new;
            }
        }

        $new->links[] = $link;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withoutLink(LinkInterface $link): static
    {
        $new = clone $this;
        $new->links = array_values(array_filter(
            $new->links,
            fn(LinkInterface $l): bool => $l !== $link,
        ));

        return $new;
    }
}
