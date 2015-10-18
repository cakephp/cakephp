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
 * @since         3.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Database\Driver;
use Cake\Database\TypeInterface;
use Cake\Database\TypeTrait;
use Cake\Datasource\Type\BinaryType;
use PDO;

/**
 * Binary type converter.
 *
 * Use to convert binary data between PHP and the SQL server types.
 */
class SqlserverBinaryType extends BinaryType implements TypeInterface
{

    use TypeTrait;

    /**
     * Convert binary into resource handles
     *
     * @param null|string|resource $value The value to convert.
     * @return resource|null
     *
     * @throws \Cake\Core\Exception\Exception
     */
    public function toPHP($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $value = pack('H*', $value);
        }

        return parent::toPHP($value);
    }

    /**
     * Get the correct PDO binding type for Binary data.
     *
     * @param mixed $value The value being bound.
     * @param Driver $driver The driver.
     *
     * @return int
     */
    public function toStatement($value, Driver $driver)
    {
        return PDO::PARAM_LOB;
    }
}
