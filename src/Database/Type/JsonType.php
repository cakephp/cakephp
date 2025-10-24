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

use Cake\Database\Driver;
use InvalidArgumentException;
use PDO;

/**
 * JSON type converter.
 *
 * Used to convert JSON data between PHP and the database types.
 */
class JsonType extends BaseType implements BatchCastingInterface
{
    /**
     * @var int
     */
    protected int $_encodingOptions = 0;

    /**
     * Flags for json_decode()
     *
     * @var int
     */
    protected int $_decodingOptions = JSON_OBJECT_AS_ARRAY;

    /**
     * Convert a value data into a JSON string
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|null
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    public function toDatabase(mixed $value, Driver $driver): ?string
    {
        if (is_resource($value)) {
            throw new InvalidArgumentException('Cannot convert a resource value to JSON');
        }

        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_THROW_ON_ERROR | $this->_encodingOptions);
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return mixed
     */
    public function toPHP(mixed $value, Driver $driver): mixed
    {
        if (!is_string($value)) {
            return null;
        }

        return json_decode($value, flags: $this->_decodingOptions);
    }

    /**
     * @inheritDoc
     */
    public function manyToPHP(array $values, array $fields, Driver $driver): array
    {
        foreach ($fields as $field) {
            if (!isset($values[$field])) {
                continue;
            }

            $values[$field] = json_decode($values[$field], flags: $this->_decodingOptions);
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public function toStatement(mixed $value, Driver $driver): int
    {
        return PDO::PARAM_STR;
    }

    /**
     * Marshals request data into a JSON compatible structure.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal(mixed $value): mixed
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

    /**
     * Set json_decode() options.
     *
     * By default, the value is `JSON_OBJECT_AS_ARRAY`.
     *
     * @param int $options Decoding flags. Use JSON_* flags. Set `0` to reset.
     * @return $this
     */
    public function setDecodingOptions(int $options)
    {
        $this->_decodingOptions = $options;

        return $this;
    }
}
