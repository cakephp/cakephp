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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Paging;

use Closure;

/**
 * Builder for creating complete sortable fields configurations.
 *
 * Provides interface for building sortable fields with multiple sort keys and fields.
 * Also handles resolution of sort keys to database ORDER BY clauses.
 */
final class SortableFieldsBuilder
{
    /**
     * @var array<string, array<\Cake\Datasource\Paging\SortField|string>> The sortable fields map being built
     */
    protected array $map = [];

    /**
     * @var bool Whether this builder represents a simple array format
     */
    protected bool $isSimpleArray = false;

    /**
     * Create builder from various sortableFields configurations.
     *
     * @param \Closure|array<mixed>|null $config The sortableFields configuration
     * @return self|null Builder instance or null if no config
     */
    public static function create(array|Closure|null $config): ?self
    {
        if ($config === null) {
            return null;
        }

        if ($config instanceof Closure) {
            return self::fromCallable($config);
        }

        return self::fromArray($config);
    }

    /**
     * Create builder from array configuration.
     *
     * Handles both simple array format (['field1', 'field2']) and
     * associative map format (['key' => 'field', ...]).
     *
     * @param array<mixed> $config Array configuration
     * @return self
     */
    public static function fromArray(array $config): self
    {
        $builder = new self();
        $hasNumericKeys = false;

        // Check if it's a simple array format
        foreach ($config as $key => $value) {
            if (is_int($key)) {
                $hasNumericKeys = true;
                break;
            }
        }

        if ($hasNumericKeys) {
            // Simple or mixed format - convert numeric keys
            $builder->isSimpleArray = true;
            foreach ($config as $key => $value) {
                if (is_int($key) && is_string($value)) {
                    // Numeric key with string value: 'field' becomes 'field' => ['field']
                    $builder->map[$value] = [$value];
                } else {
                    // String key: use as-is
                    $builder->map[$key] = $value;
                }
            }
        } else {
            // Associative map format
            $builder->map = $config;
        }

        return $builder;
    }

    /**
     * Create builder from callable factory.
     *
     * @param \Closure $factory Closure that receives builder and returns it
     * @return self
     */
    public static function fromCallable(Closure $factory): self
    {
        $builder = new self();
        $builder = $factory($builder);

        return $builder;
    }

    /**
     * Add a sort key with its associated SortField objects.
     *
     * @param string $sortKey The sort key name
     * @param \Cake\Datasource\Paging\SortField|string ...$fields The sort fields to add
     * @return $this
     */
    public function add(string $sortKey, SortField|string ...$fields)
    {
        if ($fields === []) {
            // If no fields provided, use the key as the field name
            $this->map[$sortKey] = [$sortKey];
        } else {
            $this->map[$sortKey] = $fields;
        }

        return $this;
    }

    /**
     * Return the complete sortable fields map.
     *
     * @return array<string, array<\Cake\Datasource\Paging\SortField|string>>
     */
    public function toArray(): array
    {
        return $this->map;
    }

    /**
     * Resolve a sort key to its corresponding ORDER BY clause.
     *
     * @param string $sortKey The sort key from URL
     * @param string $direction The requested direction (asc/desc)
     * @param bool $directionSpecified Whether direction was explicitly specified
     * @return array<string, string>|null Array of field => direction pairs, or null if invalid
     */
    public function resolve(
        string $sortKey,
        string $direction,
        bool $directionSpecified = true,
    ): ?array {
        // Check if sort key exists in map
        if (!isset($this->map[$sortKey])) {
            return null;
        }

        $mapping = $this->map[$sortKey];

        // Empty array means use key as field
        if ($mapping === []) {
            return [$sortKey => $direction];
        }

        return $this->resolveMapping($mapping, $direction, $directionSpecified);
    }

    /**
     * Resolve a mapping configuration to ORDER BY clause.
     *
     * @param mixed $mapping The mapping to resolve
     * @param string $direction The requested direction
     * @param bool $directionSpecified Whether direction was explicitly specified
     * @return array<string, string> Array of field => direction pairs
     */
    protected function resolveMapping(mixed $mapping, string $direction, bool $directionSpecified): array
    {
        // Single string: 'name' => 'Users.name'
        if (is_string($mapping)) {
            return [$mapping => $direction];
        }

        // Array of fields/SortField objects
        if (is_array($mapping)) {
            return $this->resolveArrayMapping($mapping, $direction, $directionSpecified);
        }

        return [];
    }

    /**
     * Resolve an array mapping to ORDER BY clause.
     *
     * @param array<mixed> $fields Array of fields or SortField objects
     * @param string $direction The requested direction
     * @param bool $directionSpecified Whether direction was explicitly specified
     * @return array<string, string> Array of field => direction pairs
     */
    protected function resolveArrayMapping(array $fields, string $direction, bool $directionSpecified): array
    {
        $order = [];

        foreach ($fields as $key => $value) {
            if ($value instanceof SortField) {
                // SortField object with locked/default directions
                $field = $value->getField();
                $fieldDirection = $value->getDirection($direction, $directionSpecified);
                $order[$field] = $fieldDirection;
            } elseif (is_int($key)) {
                // Numeric array: ['field1', 'field2'] - use requested direction
                $order[$value] = $direction;
            } elseif (!$directionSpecified) {
                // Default direction: 'field' => 'asc'
                $order[$key] = $value;
            } else {
                // Toggleable with default
                $order[$key] = $direction;
            }
        }

        return $order;
    }
}
