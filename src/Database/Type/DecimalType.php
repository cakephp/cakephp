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

use Cake\Database\Driver;
use Cake\Database\Type;
use Cake\Database\TypeInterface;
use PDO;
use RuntimeException;

/**
 * Decimal type converter.
 *
 * Use to convert decimal data between PHP and the database types.
 */
class DecimalType extends Type implements TypeInterface
{

    /**
     * Identifier name for this type
     *
     * @var string|null
     */
    protected $_name = null;

    /**
     * Constructor
     *
     * @param string|null $name The name identifying this type
     */
    public function __construct($name = null)
    {
        $this->_name = $name;
    }

    /**
     * The class to use for representing number objects
     *
     * @var string
     */
    public static $numberClass = 'Cake\I18n\Number';

    /**
     * Whether numbers should be parsed using a locale aware parser
     * when marshalling string inputs.
     *
     * @var bool
     */
    protected $_useLocaleParser = false;

    /**
     * Convert integer data into the database format.
     *
     * @param string|resource $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|null
     */
    public function toDatabase($value, Driver $driver)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return '1';
        }
        if (is_string($value) && is_numeric($value)) {
            return $value;
        }

        return sprintf('%F', (float)$value);
    }

    /**
     * Convert float values to PHP integers
     *
     * @param null|string|resource $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return resource
     * @throws \Cake\Core\Exception\Exception
     */
    public function toPHP($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return 1;
        }

        return (float)$value;
    }

    /**
     * Get the correct PDO binding type for integer data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\Driver $driver The driver.
     * @return int
     */
    public function toStatement($value, Driver $driver)
    {
        return PDO::PARAM_STR;
    }

    /**
     * Marshalls request data into PHP floats.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    public function marshal($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        if (is_string($value) && $this->_useLocaleParser) {
            return $this->_parseValue($value);
        }
        if (is_array($value)) {
            return 1;
        }

        return $value;
    }

    /**
     * Sets whether or not to parse numbers passed to the marshal() function
     * by using a locale aware parser.
     *
     * @param bool $enable Whether or not to enable
     * @return $this
     */
    public function useLocaleParser($enable = true)
    {
        if ($enable === false) {
            $this->_useLocaleParser = $enable;

            return $this;
        }
        if (static::$numberClass === 'Cake\I18n\Number' ||
            is_subclass_of(static::$numberClass, 'Cake\I18n\Number')
        ) {
            $this->_useLocaleParser = $enable;

            return $this;
        }
        throw new RuntimeException(
            sprintf('Cannot use locale parsing with the %s class', static::$numberClass)
        );
    }

    /**
     * Converts a string into a float point after parseing it using the locale
     * aware parser.
     *
     * @param string $value The value to parse and convert to an float.
     * @return float
     */
    protected function _parseValue($value)
    {
        $class = static::$numberClass;

        return $class::parseFloat($value);
    }
}
