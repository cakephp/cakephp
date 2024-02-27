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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Core\Exception\CakeException;
use Cake\Database\Driver;
use Cake\Utility\Text;
use PDO;

/**
 * Binary UUID type converter.
 *
 * Use to convert binary uuid data between PHP and the database types.
 */
class BinaryUuidType extends BaseType
{
    /**
     * Convert binary uuid data into the database format.
     *
     * Binary data is not altered before being inserted into the database.
     * As PDO will handle reading file handles.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return mixed
     */
    public function toDatabase(mixed $value, Driver $driver): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $length = strlen($value);
        if ($length !== 36 && $length !== 32) {
            return null;
        }

        return $this->convertStringToBinaryUuid($value);
    }

    /**
     * Generate a new binary UUID
     *
     * @return string A new primary key value.
     */
    public function newId(): string
    {
        return Text::uuid();
    }

    /**
     * Convert binary uuid into resource handles
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return resource|string|null
     * @throws \Cake\Core\Exception\CakeException
     */
    public function toPHP(mixed $value, Driver $driver): mixed
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return $this->convertBinaryUuidToString($value);
        }
        if (is_resource($value)) {
            return $value;
        }

        throw new CakeException(sprintf('Unable to convert %s into binary uuid.', gettype($value)));
    }

    /**
     * @inheritDoc
     */
    public function toStatement(mixed $value, Driver $driver): int
    {
        return PDO::PARAM_LOB;
    }

    /**
     * Marshals flat data into PHP objects.
     *
     * Most useful for converting request data into PHP objects
     * that make sense for the rest of the ORM/Database layers.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Converts a binary uuid to a string representation
     *
     * @param mixed $binary The value to convert.
     * @return string Converted value.
     */
    protected function convertBinaryUuidToString(mixed $binary): string
    {
        $string = unpack('H*', $binary);
        assert($string !== false, 'Could not unpack uuid');

        /** @var array<int, string> $string */
        $string = preg_replace(
            '/([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})/',
            '$1-$2-$3-$4-$5',
            $string
        );

        return $string[1];
    }

    /**
     * Converts a string UUID (36 or 32 char) to a binary representation.
     *
     * @param string $string The value to convert.
     * @return string Converted value.
     */
    protected function convertStringToBinaryUuid(string $string): string
    {
        $string = str_replace('-', '', $string);

        return pack('H*', $string);
    }
}
