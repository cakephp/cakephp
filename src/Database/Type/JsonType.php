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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Database\DriverInterface;
use InvalidArgumentException;
use PDO;

/**
 * JSON type converter.
 *
 * Use to convert JSON data between PHP and the database types.
 */
class JsonType extends BaseType implements BatchCastingInterface
{
    /**
     * @var int
     */
    protected $_encodingOptions = 0;

    /**
     * Convert a value data into a JSON string
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\DriverInterface $driver The driver instance to convert with.
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function toDatabase($value, DriverInterface $driver): ?string
    {
        if (is_resource($value)) {
            throw new InvalidArgumentException('Cannot convert a resource value to JSON');
        }

        if ($value === null) {
            return null;
        }

        return json_encode($value, $this->_encodingOptions);
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\DriverInterface $driver The driver instance to convert with.
     * @return array|string|null
     */
    public function toPHP($value, DriverInterface $driver)
    {
        if (!is_string($value)) {
            return null;
        }

        return json_decode($value, true);
    }

    /**
     * @inheritDoc
     */
    public function manyToPHP(array $values, array $fields, DriverInterface $driver): array
    {
        foreach ($fields as $field) {
            if (!isset($values[$field])) {
                continue;
            }

            $values[$field] = json_decode($values[$field], true);
        }

        return $values;
    }

    /**
     * Get the correct PDO binding type for string data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\DriverInterface $driver The driver.
     * @return int
     */
    public function toStatement($value, DriverInterface $driver): int
    {
        return PDO::PARAM_STR;
    }

    /**
     * Marshals request data into a JSON compatible structure.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal($value)
    {
        return $value;
    }

    /**
     * Set json_encode options.
     *
     * @param int $options Encoding flags. Use JSON_* flags. Set `0` to reset.
     * @return $this
     * @see https://www.php.net/manual/en/function.json-encode.php
     */
    public function setEncodingOptions(int $options)
    {
        $this->_encodingOptions = $options;

        return $this;
    }
}
