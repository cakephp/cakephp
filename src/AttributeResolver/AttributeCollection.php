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
namespace Cake\AttributeResolver;

use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\AttributeResolver\ValueObject\AttributeInfo;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collection class for working with AttributeInfo objects.
 *
 * Provides fluent filtering methods specific to attribute metadata.
 * Supports lazy hydration from raw array data for optimal cache performance.
 *
 * Data can be provided as:
 * - Array of AttributeInfo objects (converted to arrays internally)
 * - Raw array data with optional pre-built indexes (from cache)
 */
class AttributeCollection implements IteratorAggregate, Countable
{
    /**
     * Raw array data for all items
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $data = [];

    /**
     * Pre-built indexes for fast filtering
     *
     * @var array<string, array<string, array<int>>>
     */
    protected array $indexes = [];

    /**
     * Active item IDs after filtering (null = all items)
     *
     * @var array<int>|null
     */
    protected ?array $activeIds = null;

    /**
     * Cache of hydrated AttributeInfo objects
     *
     * @var array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>
     */
    protected array $hydrated = [];

    /**
     * Constructor.
     *
     * @param iterable<\Cake\AttributeResolver\ValueObject\AttributeInfo|array> $items Items to populate the collection
     * @param array<string, array<string, array<int>>>|null $indexes Pre-built indexes (from cache)
     */
    public function __construct(iterable $items, ?array $indexes = null)
    {
        $i = 0;
        $needsIndexing = $indexes === null;

        if ($needsIndexing) {
            $this->indexes = [
                'byAttribute' => [],
                'byClassName' => [],
                'byTargetType' => [],
            ];
        } else {
            $this->indexes = $indexes;
        }

        foreach ($items as $item) {
            if ($item instanceof AttributeInfo) {
                // Convert AttributeInfo to array and cache the hydrated version
                $this->data[$i] = $item->toArray();
                $this->hydrated[$i] = $item;
            } else {
                // Already array format
                $this->data[$i] = $item;
            }

            if ($needsIndexing) {
                $this->indexItem($i, $this->data[$i]);
            }

            $i++;
        }
    }

    /**
     * Add item to indexes.
     *
     * @param int $id Item ID
     * @param array<string, mixed> $item Item data
     * @return void
     */
    protected function indexItem(int $id, array $item): void
    {
        $this->indexes['byAttribute'][$item['attributeName']][] = $id;
        $this->indexes['byClassName'][$item['className']][] = $id;

        $targetType = is_array($item['target'])
            ? $item['target']['type']
            : $item['target']->type->value;

        if ($targetType instanceof AttributeTargetType) {
            $targetType = $targetType->value;
        }

        $this->indexes['byTargetType'][$targetType][] = $id;
    }

    /**
     * Get the active item IDs (filtered or all).
     *
     * @return array<int>
     */
    protected function getActiveIds(): array
    {
        return $this->activeIds ?? array_keys($this->data);
    }

    /**
     * Hydrate a single item by ID.
     *
     * @param int $id Item ID
     * @return \Cake\AttributeResolver\ValueObject\AttributeInfo
     */
    protected function hydrate(int $id): AttributeInfo
    {
        if (!isset($this->hydrated[$id])) {
            $this->hydrated[$id] = AttributeInfo::fromArray($this->data[$id]);
        }

        return $this->hydrated[$id];
    }

    /**
     * Create a filtered clone with specific active IDs.
     *
     * @param array<int> $ids Active item IDs
     * @return static
     */
    protected function withActiveIds(array $ids): static
    {
        $clone = clone $this;
        $clone->activeIds = $ids;

        return $clone;
    }

    /**
     * Intersect matching IDs with currently active IDs.
     *
     * @param array<int> $matchingIds Matching item IDs from index
     * @return static
     */
    protected function intersectWithActive(array $matchingIds): static
    {
        $activeIds = $this->activeIds !== null
            ? array_values(array_intersect($this->getActiveIds(), $matchingIds))
            : $matchingIds;

        return $this->withActiveIds($activeIds);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        foreach ($this->getActiveIds() as $id) {
            yield $this->hydrate($id);
        }
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->getActiveIds());
    }

    /**
     * Get first item or null if empty.
     *
     * @return \Cake\AttributeResolver\ValueObject\AttributeInfo|null
     */
    public function first(): ?AttributeInfo
    {
        $ids = $this->getActiveIds();
        if ($ids === []) {
            return null;
        }

        return $this->hydrate(reset($ids));
    }

    /**
     * Convert collection to array of AttributeInfo objects.
     *
     * @return array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }

    /**
     * Convert collection to list of AttributeInfo objects.
     *
     * @return array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>
     */
    public function toList(): array
    {
        return array_values($this->toArray());
    }

    /**
     * Filter with a callback. Triggers full hydration.
     *
     * @param callable $callback Callback to filter elements
     * @return static
     */
    public function filter(callable $callback): static
    {
        $matchingIds = [];
        foreach ($this->getActiveIds() as $id) {
            if ($callback($this->hydrate($id))) {
                $matchingIds[] = $id;
            }
        }

        return $this->withActiveIds($matchingIds);
    }

    /**
     * Filter by attribute name(s). Uses index for fast lookup.
     *
     * @param array<string>|string $names Attribute class name(s) to filter by
     * @return static
     */
    public function withAttribute(string|array $names): static
    {
        $names = (array)$names;
        $matchingIds = [];

        foreach ($names as $name) {
            if (isset($this->indexes['byAttribute'][$name])) {
                $matchingIds = array_merge($matchingIds, $this->indexes['byAttribute'][$name]);
            }
        }

        return $this->intersectWithActive($matchingIds);
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
     * Filter by target type(s). Uses index for fast lookup.
     *
     * @param \Cake\AttributeResolver\Enum\AttributeTargetType|array<\Cake\AttributeResolver\Enum\AttributeTargetType> $types Target type(s) to filter by
     * @return static
     */
    public function withTargetType(AttributeTargetType|array $types): static
    {
        $types = is_array($types) ? $types : [$types];
        $matchingIds = [];

        foreach ($types as $type) {
            $typeValue = $type->value;
            if (isset($this->indexes['byTargetType'][$typeValue])) {
                $matchingIds = array_merge($matchingIds, $this->indexes['byTargetType'][$typeValue]);
            }
        }

        return $this->intersectWithActive($matchingIds);
    }

    /**
     * Filter by class name(s). Uses index for fast lookup.
     *
     * @param array<string>|string $names Class name(s) to filter by
     * @return static
     */
    public function withClassName(string|array $names): static
    {
        $names = (array)$names;
        $matchingIds = [];

        foreach ($names as $name) {
            if (isset($this->indexes['byClassName'][$name])) {
                $matchingIds = array_merge($matchingIds, $this->indexes['byClassName'][$name]);
            }
        }

        return $this->intersectWithActive($matchingIds);
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
     * Get cache-optimized data for storage.
     *
     * Returns raw arrays and indexes suitable for cache storage.
     * This format can be loaded directly without object construction overhead.
     *
     * @return array{data: array<int, array<string, mixed>>, indexes: array<string, array<string, array<int>>>}
     */
    public function getCacheData(): array
    {
        return [
            'data' => $this->data,
            'indexes' => $this->indexes,
        ];
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
