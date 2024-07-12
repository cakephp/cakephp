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

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Map that stores keys, values, and value types for Cookies.
 */
class CookieMap extends TypedMap
{
    use TypeAwareMapTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $map = [];

    /**
     * @var array<string>
     */
    protected array $types = [];

    /**
     * @var array<string>
     */
    protected array $supportedTypes = [
        'string',
    ];

    /**
     * Class constructor.
     *
     * @param array<string, mixed> $cookies
     */
    public function __construct(array $cookies)
    {
        foreach ($cookies as $key => $value) {
            $this->set($key, $value);
        }
    }

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
                'Supported types are: ' . implode(', ', $this->supportedTypes));
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
}
