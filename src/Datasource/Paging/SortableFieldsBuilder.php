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
use InvalidArgumentException;

/**
 * Builder for creating complete sortable fields configurations.
 *
 * Provides interface for building sortable fields with multiple sort keys and fields.
 * Also handles resolution of sort keys to database ORDER BY clauses.
 */
class SortableFieldsBuilder
{
    /**
     * @var array<string, array<\Cake\Datasource\Paging\SortField|string>|string> The sortable fields map being built
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
     * @return static|null Builder instance or null if no config
     */
    public static function create(array|Closure|null $config): ?static
    {
        if ($config === null) {
            return null;
        }

        if ($config instanceof Closure) {
            return static::fromCallable($config);
        }

        return static::fromArray($config);
    }

    /**
     * Create builder from array configuration.
     *
     * Handles both simple array format (['field1', 'field2']) and
     * associative map format (['key' => 'field', ...]).
     *
     * @param array<mixed> $config Array configuration
     * @return static
     */
    public static function fromArray(array $config): static
    {
        $builder = new static();
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
                    $builder->add($value, $value);
                } else {
                    // String key: use as-is
                    $builder->set($key, $value);
                }
            }
        } else {
            // Associative map format
            foreach ($config as $key => $value) {
                $builder->set($key, $value);
            }
        }

        return $builder;
    }

    /**
     * Create builder from callable factory.
     *
     * @param \Closure $factory Closure that receives builder and returns it
     * @return static
     */
    public static function fromCallable(Closure $factory): static
    {
        $builder = new static();
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
     * Set a sort key with type-safe validation.
     *
     * Internal method used by fromArray() to ensure type safety while preserving
     * backward compatibility with string and array representations.
     *
     * @param string $sortKey The sort key name
     * @param mixed $value The sort field(s) - can be string, SortField, or array
     * @return $this
     */
    protected function set(string $sortKey, mixed $value)
    {
        if (is_string($value)) {
            $this->map[$sortKey] = $value;
        } elseif ($value instanceof SortField) {
            $this->map[$sortKey] = [$value];
        } elseif (is_array($value)) {
            $this->add($sortKey, ...$value);
        } else {
            throw new InvalidArgumentException(sprintf(
                'Invalid sortable field value type for key `%s`. Expected string, array, or SortField, got `%s`.',
                $sortKey,
                get_debug_type($value),
            ));
        }

        return $this;
    }

    /**
     * Return the complete sortable fields map.
     *
     * @return array<string, array<\Cake\Datasource\Paging\SortField|string>|string>
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
        $shouldInvert = $directionSpecified && $direction === SortField::DESC;

        foreach ($fields as $key => $value) {
            if ($value instanceof SortField) {
                // SortField object with locked/default directions
                $field = $value->getField();
                $fieldDirection = $value->getDirection($direction, $directionSpecified);
                $order[$field] = $fieldDirection;
            } elseif (is_int($key)) {
                // Numeric array: ['field1', 'field2'] - use requested direction
                $order[$value] = $direction;
            } elseif (is_string($value)) {
                // Associative array with default directions per field
                // Format: ['field1' => 'ASC', 'field2' => 'DESC']
                $defaultDirection = strtolower($value);

                if ($shouldInvert) {
                    // Invert the direction when toggling to desc
                    $fieldDirection = $defaultDirection === SortField::ASC ? SortField::DESC : SortField::ASC;
                } else {
                    // Use default direction (for asc or no direction specified)
                    $fieldDirection = $defaultDirection;
                }

                $order[$key] = $fieldDirection;
            } else {
                // Fallback for other cases
                $order[$key] = $direction;
            }
        }

        return $order;
    }
}
