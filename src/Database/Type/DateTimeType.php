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
use DateTimeInterface;
use Exception;
use PDO;
use RuntimeException;

/**
 * Datetime type converter.
 *
 * Use to convert datetime instances to strings & back.
 */
class DateTimeType extends Type implements TypeInterface
{

    /**
     * The class to use for representing date objects
     *
     * This property can only be used before an instance of this type
     * class is constructed. After that use `useMutable()` or `useImmutable()` instead.
     *
     * @var string
     * @deprecated Use DateTimeType::useMutable() or DateTimeType::useImmutable() instead.
     */
    public static $dateTimeClass = 'Cake\I18n\Time';

    /**
     * String format to use for DateTime parsing
     *
     * @var string
     */
    protected $_format = 'Y-m-d H:i:s';

    /**
     * Whether dates should be parsed using a locale aware parser
     * when marshalling string inputs.
     *
     * @var bool
     */
    protected $_useLocaleParser = false;

    /**
     * The date format to use for parsing incoming dates for marshalling.
     *
     * @var string|array|int
     */
    protected $_localeFormat;

    /**
     * An instance of the configured dateTimeClass, used to quickly generate
     * new instances without calling the constructor.
     *
     * @var \DateTime
     */
    protected $_datetimeInstance;

    /**
     * The classname to use when creating objects.
     *
     * @var string
     */
    protected $_className;

    /**
     * Identifier name for this type
     *
     * @var string|null
     */
    protected $_name = null;

    /**
     * {@inheritDoc}
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->_setClassName(static::$dateTimeClass, 'DateTime');
    }

    /**
     * Convert DateTime instance into strings.
     *
     * @param string|int|\DateTime $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string
     */
    public function toDatabase($value, Driver $driver)
    {
        if ($value === null || is_string($value)) {
            return $value;
        }
        if (is_int($value)) {
            $class = $this->_className;
            $value = new $class('@' . $value);
        }

        return $value->format($this->_format);
    }

    /**
     * Convert strings into DateTime instances.
     *
     * @param string $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return \Cake\I18n\Time|\DateTime
     */
    public function toPHP($value, Driver $driver)
    {
        if ($value === null || strpos($value, '0000-00-00') === 0) {
            return null;
        }

        if (strpos($value, '.') !== false) {
            list($value) = explode('.', $value);
        }

        $instance = clone $this->_datetimeInstance;

        return $instance->modify($value);
    }

    /**
     * Convert request data into a datetime object.
     *
     * @param mixed $value Request data
     * @return \Cake\I18n\Time|\DateTime
     */
    public function marshal($value)
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        $class = $this->_className;
        try {
            $compare = $date = false;
            if ($value === '' || $value === null || $value === false || $value === true) {
                return null;
            } elseif (is_numeric($value)) {
                $date = new $class('@' . $value);
            } elseif (is_string($value) && $this->_useLocaleParser) {
                return $this->_parseValue($value);
            } elseif (is_string($value)) {
                $date = new $class($value);
                $compare = true;
            }
            if ($compare && $date && $date->format($this->_format) !== $value) {
                return $value;
            }
            if ($date) {
                return $date;
            }
        } catch (Exception $e) {
            return $value;
        }

        if (is_array($value) && implode('', $value) === '') {
            return null;
        }
        $value += ['hour' => 0, 'minute' => 0, 'second' => 0];

        $format = '';
        if (isset($value['year'], $value['month'], $value['day']) &&
            (is_numeric($value['year']) && is_numeric($value['month']) && is_numeric($value['day']))
        ) {
            $format .= sprintf('%d-%02d-%02d', $value['year'], $value['month'], $value['day']);
        }

        if (isset($value['meridian']) && (int)$value['hour'] === 12) {
            $value['hour'] = 0;
        }
        if (isset($value['meridian'])) {
            $value['hour'] = strtolower($value['meridian']) === 'am' ? $value['hour'] : $value['hour'] + 12;
        }
        $format .= sprintf(
            '%s%02d:%02d:%02d',
            empty($format) ? '' : ' ',
            $value['hour'],
            $value['minute'],
            $value['second']
        );
        $tz = isset($value['timezone']) ? $value['timezone'] : null;

        return new $class($format, $tz);
    }

    /**
     * Sets whether or not to parse dates passed to the marshal() function
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
        if (method_exists($this->_className, 'parseDateTime')) {
            $this->_useLocaleParser = $enable;

            return $this;
        }
        throw new RuntimeException(
            sprintf('Cannot use locale parsing with the %s class', $this->_className)
        );
    }

    /**
     * Sets the format string to use for parsing dates in this class. The formats
     * that are accepted are documented in the `Cake\I18n\Time::parseDateTime()`
     * function.
     *
     * @param string|array $format The format in which the string are passed.
     * @see \Cake\I18n\Time::parseDateTime()
     * @return $this
     */
    public function setLocaleFormat($format)
    {
        $this->_localeFormat = $format;

        return $this;
    }

    /**
     * Change the preferred class name to the FrozenTime implementation.
     *
     * @return $this
     */
    public function useImmutable()
    {
        $this->_setClassName('Cake\I18n\FrozenTime', 'DateTimeImmutable');

        return $this;
    }

    /**
     * Set the classname to use when building objects.
     *
     * @param string $class The classname to use.
     * @param string $fallback The classname to use when the preferred class does not exist.
     * @return void
     */
    protected function _setClassName($class, $fallback)
    {
        if (!class_exists($class)) {
            $class = $fallback;
        }
        $this->_className = $class;
        $this->_datetimeInstance = new $this->_className;
    }

    /**
     * Change the preferred class name to the mutable Time implementation.
     *
     * @return $this
     */
    public function useMutable()
    {
        $this->_setClassName('Cake\I18n\Time', 'DateTime');

        return $this;
    }

    /**
     * Converts a string into a DateTime object after parsing it using the locale
     * aware parser with the specified format.
     *
     * @param string $value The value to parse and convert to an object.
     * @return \Cake\I18n\Time|null
     */
    protected function _parseValue($value)
    {
        $class = $this->_className;

        return $class::parseDateTime($value, $this->_localeFormat);
    }

    /**
     * Casts given value to Statement equivalent
     *
     * @param mixed $value value to be converted to PDO statement
     * @param \Cake\Database\Driver $driver object from which database preferences and configuration will be extracted
     *
     * @return mixed
     */
    public function toStatement($value, Driver $driver)
    {
        return PDO::PARAM_STR;
    }
}
