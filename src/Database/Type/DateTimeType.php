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

/**
 * Datetime type converter.
 *
 * Use to convert datetime instances to strings & back.
 */
class DateTimeType extends \Cake\Database\Type
{

    /**
     * The class to use for representing date objects
     *
     * @var string
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
     * {@inheritDoc}
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        if (!class_exists(static::$dateTimeClass)) {
            static::$dateTimeClass = 'DateTime';
        }
    }

    /**
     * Convert DateTime instance into strings.
     *
     * @param string|int|\DateTime $value The value to convert.
     * @param Driver $driver The driver instance to convert with.
     * @return string
     */
    public function toDatabase($value, Driver $driver)
    {
        if ($value === null || is_string($value)) {
            return $value;
        }
        if (is_int($value)) {
            $value = new static::$dateTimeClass('@' . $value);
        }
        return $value->format($this->_format);
    }

    /**
     * Convert strings into DateTime instances.
     *
     * @param string $value The value to convert.
     * @param Driver $driver The driver instance to convert with.
     * @return \Carbon\Carbon
     */
    public function toPHP($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        }
        list($value) = explode('.', $value);
        $class = static::$dateTimeClass;
        return $class::createFromFormat($this->_format, $value);
    }

    /**
     * Convert request data into a datetime object.
     *
     * @param mixed $value Request data
     * @return \Carbon\Carbon
     */
    public function marshal($value)
    {
        if ($value instanceof \DateTime) {
            return $value;
        }

        $class = static::$dateTimeClass;
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
        } catch (\Exception $e) {
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

        return new $class($format);
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
        if (static::$dateTimeClass === 'Cake\I18n\Time' ||
            is_subclass_of(static::$dateTimeClass, 'Cake\I18n\Time')
        ) {
            $this->_useLocaleParser = $enable;
            return $this;
        }
        throw new RuntimeException(
            sprintf('Cannot use locale parsing with the %s class', static::$dateTimeClass)
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
     * Converts a string into a DateTime object after parseing it using the locale
     * aware parser with the specified format.
     *
     * @param string $value The value to parse and convert to an object.
     * @return \Cake\I18n\Time|null
     */
    protected function _parseValue($value)
    {
        $class = static::$dateTimeClass;
        return $class::parseDateTime($value, $this->_localeFormat);
    }
}
