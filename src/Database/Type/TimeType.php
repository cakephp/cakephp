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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Type;

use Cake\Chronos\ChronosTime;
use Cake\Core\Exception\CakeException;
use Cake\Database\Driver;
use Cake\I18n\Time;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Time type converter.
 *
 * Use to convert time instances to strings and back.
 */
class TimeType extends BaseType implements BatchCastingInterface
{
    /**
     * The PHP Time format used when converting to string.
     *
     * @var string
     */
    protected string $_format = 'H:i:s';

    /**
     * Whether `marshal()` should use locale-aware parser with `_localeMarshalFormat`.
     *
     * @var bool
     */
    protected bool $_useLocaleMarshal = false;

    /**
     * The locale-aware format `marshal()` uses when `_useLocaleParser` is true.
     *
     * See `Cake\I18n\Time::parseTime()` for accepted formats.
     *
     * @var string|int|null
     */
    protected string|int|null $_localeMarshalFormat = null;

    /**
     * The classname to use when creating objects.
     *
     * @var class-string<\Cake\Chronos\ChronosTime>
     */
    protected string $_className;

    /**
     * Constructor
     *
     * @param string|null $name The name identifying this type.
     * @param class-string<\Cake\Chronos\ChronosTime>|null $className Class name for time representation.
     */
    public function __construct(?string $name = null, ?string $className = null)
    {
        parent::__construct($name);

        if ($className === null) {
            $className = class_exists(Time::class) ? Time::class : ChronosTime::class;
        }

        $this->_className = $className;
    }

    /**
     * Convert request data into a datetime object.
     *
     * @param mixed $value Request data
     * @return \Cake\Chronos\ChronosTime|null
     */
    public function marshal(mixed $value): ?ChronosTime
    {
        if ($value instanceof $this->_className) {
            return $value;
        }

        if ($value instanceof DateTimeInterface || $value instanceof ChronosTime) {
            return new $this->_className($value->format($this->_format));
        }

        if (is_string($value)) {
            if ($this->_useLocaleMarshal) {
                return $this->_parseLocalTimeValue($value);
            }

            return $this->_parseTimeValue($value);
        }

        if (!is_array($value)) {
            return null;
        }

        $value += ['hour' => null, 'minute' => null, 'second' => 0, 'microsecond' => 0];
        if (
            !is_numeric($value['hour']) || !is_numeric($value['minute']) || !is_numeric($value['second']) ||
            !is_numeric($value['microsecond'])
        ) {
            return null;
        }

        if (isset($value['meridian']) && (int)$value['hour'] === 12) {
            $value['hour'] = 0;
        }
        if (isset($value['meridian'])) {
            $value['hour'] = strtolower($value['meridian']) === 'am' ? $value['hour'] : $value['hour'] + 12;
        }
        $format = sprintf(
            '%02d:%02d:%02d.%06d',
            $value['hour'],
            $value['minute'],
            $value['second'],
            $value['microsecond'],
        );

        return new $this->_className($format);
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

            $value = $values[$field];
            $instance = new $this->_className($value);
            $values[$field] = $instance;
        }

        return $values;
    }

    /**
     * Convert time data into the database time format.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return mixed
     */
    public function toDatabase(mixed $value, Driver $driver): mixed
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        assert(method_exists($value, 'format'));

        return $value->format($this->_format);
    }

    /**
     * Convert time values to PHP time instances
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return \Cake\Chronos\ChronosTime|null
     */
    public function toPHP(mixed $value, Driver $driver): ?ChronosTime
    {
        if ($value === null) {
            return null;
        }

        return new $this->_className($value);
    }

    /**
     * Get the classname used for building objects.
     *
     * @return class-string<\Cake\Chronos\ChronosTime>
     */
    public function getTimeClassName(): string
    {
        return $this->_className;
    }

    /**
     * Converts a string into a Time object
     *
     * @param string $value The value to parse and convert to an object.
     * @return \Cake\Chronos\ChronosTime|null
     */
    protected function _parseTimeValue(string $value): ?ChronosTime
    {
        try {
            return $this->_className::parse($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Converts a string into a Time object after parsing it using the locale
     * aware parser with the format set by `setLocaleFormat()`.
     *
     * @param string $value The value to parse and convert to an object.
     * @return \Cake\Chronos\ChronosTime|null
     */
    protected function _parseLocalTimeValue(string $value): ?ChronosTime
    {
        assert(is_a($this->_className, Time::class, true));

        return $this->_className::parseTime($value, $this->_localeMarshalFormat);
    }

    /**
     * Sets whether to parse strings passed to `marshal()` using
     * the locale-aware format set by `setLocaleFormat()`.
     *
     * @param bool $enable Whether to enable
     * @return $this
     */
    public function useLocaleParser(bool $enable = true)
    {
        if (
            $enable &&
            ($this->_className !== Time::class && !is_subclass_of($this->_className, Time::class))
        ) {
            throw new CakeException('You must install the `cakephp/i18n` package to use locale aware parsing.');
        }

        $this->_useLocaleMarshal = $enable;

        return $this;
    }

    /**
     * Sets the locale-aware format used by `marshal()` when parsing strings.
     *
     * See `Cake\I18n\Time::parseTime()` for accepted formats.
     *
     * @param string|int|null $format The locale-aware format
     * @see \Cake\I18n\Time::parseTime()
     * @return $this
     */
    public function setLocaleFormat(string|int|null $format)
    {
        $this->_localeMarshalFormat = $format;

        return $this;
    }
}
