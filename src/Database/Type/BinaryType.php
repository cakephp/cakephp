<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Core\Exception\Exception;
use Cake\Database\Driver;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Type;
use PDO;

/**
 * Binary type converter.
 *
 * Use to convert binary data between PHP and the database types.
 */
class BinaryType extends Type
{

    /**
     * Convert binary data into the database format.
     *
     * Binary data is not altered before being inserted into the database.
     * As PDO will handle reading file handles.
     *
     * @param string|resource $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|resource
     */
    public function toDatabase($value, Driver $driver)
    {
        return $value;
    }

    /**
     * Convert binary into resource handles
     *
     * @param null|string|resource $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return resource|null
     * @throws \Cake\Core\Exception\Exception
     */
    public function toPHP($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value) && $driver instanceof Sqlserver) {
            $value = pack('H*', $value);
        }
        if (is_string($value)) {
            return fopen('data:text/plain;base64,' . base64_encode($value), 'rb');
        }
        if (is_resource($value)) {
            return $value;
        }
        throw new Exception(sprintf('Unable to convert %s into binary.', gettype($value)));
    }

    /**
     * Get the correct PDO binding type for Binary data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\Driver $driver The driver.
     * @return int
     */
    public function toStatement($value, Driver $driver)
    {
        return PDO::PARAM_LOB;
    }
}
