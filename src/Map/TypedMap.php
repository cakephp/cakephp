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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Map;

use Cake\I18n\Date;
use Cake\I18n\DateTime;
use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Map that stores keys, values, and the types of those values. Ensures type
 * safety by validating the types of values before storing them.
 */
class TypedMap implements MapInterface
{
    /**
     * @var array
     */
    protected array $map = [];

    /**
     * @var array
     */
    protected array $types = [];

    /**
     * TODO: Consider expanding this list and having children override it to narrow acceptable types
     *
     * @var array|string[]
     */
    protected array $supportedTypes = [
        'string',
        'int',
        'float',
        'bool',
        'DateTime',
        'Date',
    ];

    /**
     * Sets a value in the map with the given key and type.
     *
     * @param string $key
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function set(string $key, mixed $value): void
    {
        $type = $this->detectType($value);
        if (!$this->isValidType($type)) {
            throw new InvalidArgumentException("Cannot store unsupported type `${type}` in Map object. " .
                "Supported types are: " . implode(', ', $this->supportedTypes));
        }

        $this->map[$key] = $value;
        $this->types[$key] = $type;
    }

    /**
     * Gets a value from the map by the given key.
     *
     * @param string $key
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function get(string $key): mixed
    {
        if (!array_key_exists($key, $this->map)) {
            throw new OutOfBoundsException("No value found for key {$key}");
        }

        return $this->map[$key];
    }

    /**
     * Gets the type of a value stored in the map by the given key.
     *
     * @param string $key
     * @return string
     * @throws \OutOfBoundsException
     */
    public function getType(string $key): string
    {
        if (!array_key_exists($key, $this->types)) {
            throw new OutOfBoundsException("No type found for key {$key}");
        }

        return $this->types[$key];
    }

    /**
     * Detects the type of a value.
     *
     * @param mixed $value
     * @return string
     */
    protected function detectType(mixed $value): string
    {
        return match (true) {
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_string($value) => 'string',
            is_bool($value) => 'bool',
            is_array($value) => 'array',
            is_object($value) => 'object',
            is_callable($value) => 'callable',
            is_iterable($value) => 'iterable',
            get_class($value) === DateTime::class => 'DateTime',
            get_class($value) === Date::class => 'Date',
            default => 'mixed',
        };
    }

    /**
     * Checks if the given type is one of the supported types.
     *
     * @param string $type
     * @return bool
     */
    protected function isValidType(string $type): bool
    {
        return in_array($type, $this->supportedTypes);
    }
}
