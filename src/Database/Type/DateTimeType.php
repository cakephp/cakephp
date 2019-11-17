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

use Cake\Database\DriverInterface;
use Cake\I18n\FrozenTime;
use Cake\I18n\I18nDateTimeInterface;
use Cake\I18n\Time;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * Datetime type converter.
 *
 * Use to convert datetime instances to strings & back.
 */
class DateTimeType extends BaseType
{
    /**
     * Whether or not we want to override the time of the converted Time objects
     * so it points to the start of the day.
     *
     * This is primarily to avoid subclasses needing to re-implement the same functionality.
     *
     * @var bool
     */
    protected $setToDateStart = false;

    /**
     * The DateTime format used when converting to string.
     *
     * @var string
     */
    protected $_format = 'Y-m-d H:i:s';

    /**
     * The DateTime formats allowed by `marshal()`.
     *
     * @var array
     */
    protected $_marshalFormats = [
        'Y-m-d H:i:s',
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i:sP',
    ];

    /**
     * Whether `marshal()` should use locale-aware parser with `_localeMarshalFormat`.
     *
     * @var bool
     */
    protected $_useLocaleMarshal = false;

    /**
     * The locale-aware format `marshal()` uses when `_useLocaleParser` is true.
     *
     * See `Cake\I18n\Time::parseDateTime()` for accepted formats.
     *
     * @var string|array|int
     */
    protected $_localeMarshalFormat;

    /**
     * An instance of the configured dateTimeClass, used to quickly generate
     * new instances without calling the constructor.
     *
     * @var \DateTime|\DateTimeImmutable
     */
    protected $_datetimeInstance;

    /**
     * The classname to use when creating objects.
     *
     * @var string
     */
    protected $_className;

    /**
     * Timezone instance.
     *
     * @var \DateTimeZone|null
     */
    protected $dbTimezone;

    /**
     * {@inheritDoc}
     *
     * @param string|null $name The name identifying this type
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        $this->useImmutable();
    }

    /**
     * Convert DateTime instance into strings.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\DriverInterface $driver The driver instance to convert with.
     * @return string|null
     */
    public function toDatabase($value, DriverInterface $driver): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }
        if (is_int($value)) {
            $class = $this->_className;
            $value = new $class('@' . $value);
        }

        if (
            $this->dbTimezone !== null
            && $this->dbTimezone->getName() !== $value->getTimezone()->getName()
        ) {
            if (!$value instanceof DateTimeImmutable) {
                $value = clone $value;
            }
            $value = $value->setTimezone($this->dbTimezone);
        }

        return $value->format($this->_format);
    }

    /**
     * Set database timezone.
     *
     * Specified timezone will be set for DateTime objects before generating
     * datetime string for saving to database. If `null` no timezone conversion
     * will be done.
     *
     * @param string|\DateTimeZone|null $timezone Database timezone.
     * @return $this
     */
    public function setTimezone($timezone)
    {
        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }
        $this->dbTimezone = $timezone;

        $this->_datetimeInstance = new $this->_className(null, $this->dbTimezone);

        return $this;
    }

    /**
     * Convert strings into DateTime instances.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\DriverInterface $driver The driver instance to convert with.
     * @return \DateTimeInterface|null
     */
    public function toPHP($value, DriverInterface $driver)
    {
        if ($value === null) {
            return null;
        }

        $instance = clone $this->_datetimeInstance;
        if (is_int($value)) {
            $instance = $instance->setTimestamp($value);
        } else {
            if (strpos($value, '0000-00-00') === 0) {
                return null;
            }
            $instance = $instance->modify($value);
        }

        if ($instance->getTimezone()->getName() !== date_default_timezone_get()) {
            $instance = $instance->setTimezone(new DateTimeZone(date_default_timezone_get()));
        }

        if ($this->setToDateStart) {
            $instance = $instance->setTime(0, 0, 0);
        }

        return $instance;
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function manyToPHP(array $values, array $fields, DriverInterface $driver)
    {
        foreach ($fields as $field) {
            if (!isset($values[$field])) {
                continue;
            }

            if (strpos($values[$field], '0000-00-00') === 0) {
                $values[$field] = null;
                continue;
            }

            $instance = clone $this->_datetimeInstance;
            $instance = $instance->modify($values[$field]);
            if ($instance->getTimezone()->getName() !== date_default_timezone_get()) {
                $instance = $instance->setTimezone(new DateTimeZone(date_default_timezone_get()));
            }

            if ($this->setToDateStart) {
                $instance = $instance->setTime(0, 0, 0);
            }

            $values[$field] = $instance;
        }

        return $values;
    }

    /**
     * Convert request data into a datetime object.
     *
     * @param mixed $value Request data
     * @return \DateTimeInterface|null
     */
    public function marshal($value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        $class = $this->_className;
        try {
            $date = false;
            if ($value === '' || $value === null || is_bool($value)) {
                return null;
            }
            $isString = is_string($value);
            if (ctype_digit($value)) {
                /** @var \DateTimeInterface $date */
                $date = new $class('@' . $value);

                return $date;
            } elseif ($isString && $this->_useLocaleMarshal) {
                return $this->_parseLocaleValue($value);
            } elseif ($isString) {
                return $this->_parseValue($value);
            }
        } catch (Exception $e) {
            return null;
        }

        if (is_array($value) && implode('', $value) === '') {
            return null;
        }
        $value += ['hour' => 0, 'minute' => 0, 'second' => 0, 'microsecond' => 0];

        $format = '';
        if (
            isset($value['year'], $value['month'], $value['day']) &&
            (
                is_numeric($value['year']) &&
                is_numeric($value['month']) &&
                is_numeric($value['day'])
            )
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
            '%s%02d:%02d:%02d.%06d',
            empty($format) ? '' : ' ',
            $value['hour'],
            $value['minute'],
            $value['second'],
            $value['microsecond']
        );
        $tz = $value['timezone'] ?? null;

        /** @var \DateTimeInterface */
        return new $class($format, $tz);
    }

    /**
     * Sets whether or not to parse strings passed to `marshal()` using
     * the locale-aware format set by `setLocaleFormat()`.
     *
     * @param bool $enable Whether or not to enable
     * @return $this
     */
    public function useLocaleParser(bool $enable = true)
    {
        if ($enable === false) {
            $this->_useLocaleMarshal = $enable;

            return $this;
        }
        if ($this->_datetimeInstance instanceof I18nDateTimeInterface) {
            $this->_useLocaleMarshal = $enable;

            return $this;
        }
        throw new RuntimeException(
            sprintf('Cannot use locale parsing with the %s class', $this->_className)
        );
    }

    /**
     * Sets the locale-aware format used by `marshal()` when parsing strings.
     *
     * See `Cake\I18n\Time::parseDateTime()` for accepted formats.
     *
     * @param string|array $format The locale-aware format
     * @see \Cake\I18n\Time::parseDateTime()
     * @return $this
     */
    public function setLocaleFormat($format)
    {
        $this->_localeMarshalFormat = $format;

        return $this;
    }

    /**
     * Change the preferred class name to the FrozenTime implementation.
     *
     * @return $this
     */
    public function useImmutable()
    {
        $this->_setClassName(FrozenTime::class, DateTimeImmutable::class);

        return $this;
    }

    /**
     * Set the classname to use when building objects.
     *
     * @param string $class The classname to use.
     * @param string $fallback The classname to use when the preferred class does not exist.
     * @return void
     */
    protected function _setClassName(string $class, string $fallback): void
    {
        if (!class_exists($class)) {
            $class = $fallback;
        }
        $this->_className = $class;
        $this->_datetimeInstance = new $this->_className(null, $this->dbTimezone);
    }

    /**
     * Get the classname used for building objects.
     *
     * @return string
     */
    public function getDateTimeClassName(): string
    {
        return $this->_className;
    }

    /**
     * Change the preferred class name to the mutable Time implementation.
     *
     * @return $this
     */
    public function useMutable()
    {
        $this->_setClassName(Time::class, DateTime::class);

        return $this;
    }

    /**
     * Converts a string into a DateTime object after parsing it using the locale
     * aware parser with the format set by `setLocaleFormat()`.
     *
     * @param string $value The value to parse and convert to an object.
     * @return \Cake\I18n\I18nDateTimeInterface|null
     */
    protected function _parseLocaleValue(string $value)
    {
        /** @var \Cake\I18n\I18nDateTimeInterface $class */
        $class = $this->_className;

        return $class::parseDateTime($value, $this->_localeMarshalFormat);
    }

    /**
     * Converts a string into a DateTime object after parsing it using the
     * formats in `_marshalFormats`.
     *
     * @param string $value The value to parse and convert to an object.
     * @return \DateTimeInterface|null
     */
    protected function _parseValue(string $value)
    {
        /** @var \DateTime|\DateTimeImmutable $class */
        $class = $this->_className;

        foreach ($this->_marshalFormats as $format) {
            try {
                $dateTime = $class::createFromFormat($format, $value);
                // Check for false in case DateTime is used directly
                if ($dateTime !== false) {
                    return $dateTime;
                }
            } catch (InvalidArgumentException $e) {
                // Chronos wraps DateTime::createFromFormat and throws
                // exception if parse fails.
                continue;
            }
        }

        return null;
    }

    /**
     * Casts given value to Statement equivalent
     *
     * @param mixed $value value to be converted to PDO statement
     * @param \Cake\Database\DriverInterface $driver object from which database preferences and configuration will be extracted
     *
     * @return mixed
     */
    public function toStatement($value, DriverInterface $driver)
    {
        return PDO::PARAM_STR;
    }
}
