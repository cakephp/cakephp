<?php
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
 * @since         3.1.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type;
use Cake\Database\TypeInterface;
use InvalidArgumentException;
use PDO;

/**
 * Bool type converter.
 *
 * Use to convert bool data between PHP and the database types.
 */
class BoolType extends Type implements TypeInterface
{
    /**
     * Identifier name for this type.
     *
     * (This property is declared here again so that the inheritance from
     * Cake\Database\Type can be removed in the future.)
     *
     * @var string|null
     */
    protected $_name;

    /**
     * Constructor.
     *
     * (This method is declared here again so that the inheritance from
     * Cake\Database\Type can be removed in the future.)
     *
     * @param string|null $name The name identifying this type
     */
    public function __construct($name = null)
    {
        $this->_name = $name;
    }

    /**
     * Convert bool data into the database format.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return bool|null
     */
    public function toDatabase($value, Driver $driver)
    {
        if ($value === true || $value === false || $value === null) {
            return $value;
        }

        if (in_array($value, [1, 0, '1', '0'], true)) {
            return (bool)$value;
        }

        throw new InvalidArgumentException('Cannot convert value to bool');
    }

    /**
     * Convert bool values to PHP booleans
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return bool|null
     */
    public function toPHP($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value) && !is_numeric($value)) {
            return strtolower($value) === 'true';
        }

        return !empty($value);
    }

    /**
     * Get the correct PDO binding type for bool data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\Driver $driver The driver.
     * @return int
     */
    public function toStatement($value, Driver $driver)
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_BOOL;
    }

    /**
     * Marshalls request data into PHP booleans.
     *
     * @param mixed $value The value to convert.
     * @return bool|null Converted value.
     */
    public function marshal($value)
    {
        if ($value === null) {
            return null;
        }
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }

        return !empty($value);
    }
}
