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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Attribute\Resolver;

use Cake\Attribute\Resolver\Enum\AttributeTargetType;
use Cake\Attribute\Resolver\ValueObject\AttributeInfo;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;

/**
 * Collection class for working with AttributeInfo objects.
 *
 * Provides fluent filtering methods specific to attribute metadata
 * while maintaining full compatibility with CakePHP's Collection API.
 */
class AttributeCollection extends Collection
{
    /**
     * @inheritDoc
     */
    protected function newCollection(mixed ...$args): CollectionInterface
    {
        return new self(...$args);
    }

    /**
     * Override filter to return AttributeCollection instance.
     *
     * @param callable|null $callback Callback to filter elements
     * @return static
     */
    public function filter(?callable $callback = null): static
    {
        /** @var static */
        return new self(parent::filter($callback));
    }

    /**
     * Filter by attribute name(s).
     *
     * @param array<string>|string $names Attribute class name(s) to filter by
     * @return static
     */
    public function withAttribute(string|array $names): static
    {
        $names = (array)$names;

        return $this->filter(function (AttributeInfo $attr) use ($names) {
            return in_array($attr->attributeName, $names, true);
        });
    }

    /**
     * Filter by namespace pattern with wildcard support.
     *
     * Examples:
     * - 'App\\Controller\\*' - matches App\Controller\UsersController
     * - '*\\Controller\\*' - matches any namespace containing Controller
     * - 'App\\Model\\Entity\\User' - exact match
     *
     * @param string $pattern Namespace pattern with optional wildcards (*)
     * @return static
     */
    public function withNamespace(string $pattern): static
    {
        $regex = $this->wildcardToRegex($pattern);

        return $this->filter(function (AttributeInfo $attr) use ($regex) {
            return preg_match($regex, $attr->attributeName) === 1;
        });
    }

    /**
     * Filter by target type(s).
     *
     * @param \Cake\Attribute\Resolver\Enum\AttributeTargetType|array<\Cake\Attribute\Resolver\Enum\AttributeTargetType> $types Target type(s) to filter by
     * @return static
     */
    public function withTargetType(AttributeTargetType|array $types): static
    {
        $types = is_array($types) ? $types : [$types];

        return $this->filter(function (AttributeInfo $attr) use ($types) {
            return in_array($attr->target->type, $types, true);
        });
    }

    /**
     * Filter by class name(s).
     *
     * @param array<string>|string $names Class name(s) to filter by
     * @return static
     */
    public function withClassName(string|array $names): static
    {
        $names = (array)$names;

        return $this->filter(function (AttributeInfo $attr) use ($names) {
            return in_array($attr->className, $names, true);
        });
    }

    /**
     * Filter by partial attribute name match (case-sensitive).
     *
     * @param string $search String to search for in attribute names
     * @return static
     */
    public function withAttributeContains(string $search): static
    {
        return $this->filter(function (AttributeInfo $attr) use ($search) {
            return str_contains($attr->attributeName, $search);
        });
    }

    /**
     * Filter by partial class name match (case-sensitive).
     *
     * @param string $search String to search for in class names
     * @return static
     */
    public function withClassNameContains(string $search): static
    {
        return $this->filter(function (AttributeInfo $attr) use ($search) {
            return str_contains($attr->className, $search);
        });
    }

    /**
     * Filter by plugin name.
     *
     * @param string|null $pluginName Plugin name to filter by, or null for app-level attributes
     * @return static
     */
    public function withPlugin(?string $pluginName): static
    {
        return $this->filter(function (AttributeInfo $attr) use ($pluginName) {
            return $attr->pluginName === $pluginName;
        });
    }

    /**
     * Convert wildcard pattern to regex.
     *
     * @param string $pattern Pattern with * wildcards
     * @return string Regular expression pattern
     */
    protected function wildcardToRegex(string $pattern): string
    {
        $regex = preg_quote($pattern, '/');
        $regex = str_replace('\\*', '.*', $regex);

        return '/^' . $regex . '$/';
    }
}
