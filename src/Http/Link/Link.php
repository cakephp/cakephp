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

use Psr\Link\EvolvableLinkInterface;
use Stringable;

/**
 * PSR-13 Link implementation.
 *
 * Represents a hypermedia link as defined in RFC 5988 and RFC 8288.
 *
 * ### Usage
 *
 * ```php
 * $link = new Link('/api/users', 'self');
 * $link = $link->withRel('collection');
 * $link = $link->withAttribute('type', 'application/json');
 * ```
 */
class Link implements EvolvableLinkInterface
{
    /**
     * The link relations.
     *
     * @var array<string>
     */
    private array $rels;

    /**
     * The link attributes.
     *
     * @var array<string, string|bool|int|float|array<string>>
     */
    private array $attributes;

    /**
     * Constructor.
     *
     * @param string $href The link URI.
     * @param array<string>|string $rels The link relation(s).
     * @param array<string, string|bool|int|float|array<string>> $attributes Additional attributes.
     */
    public function __construct(
        private string $href = '',
        string|array $rels = [],
        array $attributes = [],
    ) {
        $this->rels = is_string($rels) ? [$rels] : $rels;
        $this->attributes = $attributes;
    }

    /**
     * @inheritDoc
     */
    public function getHref(): string
    {
        return $this->href;
    }

    /**
     * @inheritDoc
     */
    public function isTemplated(): bool
    {
        return str_contains($this->href, '{') && str_contains($this->href, '}');
    }

    /**
     * @inheritDoc
     */
    public function getRels(): array
    {
        return $this->rels;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function withHref(string|Stringable $href): static
    {
        $new = clone $this;
        $new->href = (string)$href;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withRel(string $rel): static
    {
        $new = clone $this;
        if (!in_array($rel, $new->rels, true)) {
            $new->rels[] = $rel;
        }

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withoutRel(string $rel): static
    {
        $new = clone $this;
        $new->rels = array_values(array_filter(
            $new->rels,
            fn(string $r): bool => $r !== $rel,
        ));

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withAttribute(string $attribute, string|Stringable|int|float|bool|array $value): static
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value instanceof Stringable ? (string)$value : $value;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute(string $attribute): static
    {
        $new = clone $this;
        unset($new->attributes[$attribute]);

        return $new;
    }
}
