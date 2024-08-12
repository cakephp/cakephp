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

use Cake\Chronos\ChronosDate;
use Cake\Database\Driver;
use Cake\Database\Exception\DatabaseException;
use Cake\I18n\DateTime;
use DateTime as NativeDateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use PDO;

/**
 * Datetime type converter.
 *
 * Use to convert datetime instances to strings & back.
 */
class DateTimeType extends BaseType implements BatchCastingInterface
{
    /**
     * The DateTime format used when converting to string.
     *
     * @var string
     */
    protected string $_format = 'Y-m-d H:i:s';

    /**
     * The DateTime formats allowed by `marshal()`.
     *
     * @var array<string>
     */
    protected array $_marshalFormats = [
        'Y-m-d H:i',
        'Y-m-d H:i:s',
        'Y-m-d\TH:i',
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i:sP',
        'Y-m-d H:i:s.P',
        'Y-m-d H:i:s.u',
    ];

    /**
     * Whether `marshal()` should use locale-aware parser with `_localeMarshalFormat`.
     *
     * @var bool
     */
    protected bool $_useLocaleMarshal = false;

    /**
     * The locale-aware format `marshal()` uses when `_useLocaleParser` is true.
     *
     * See `Cake\I18n\Time::parseDateTime()` for accepted formats.
     *
     * @var array|string|int|null
     */
    protected array|string|int|null $_localeMarshalFormat = null;

    /**
     * The classname to use when creating objects.
     *
     * @var class-string<\Cake\I18n\DateTime>|class-string<\DateTimeImmutable>
     */
    protected string $_className;

    /**
     * Database time zone.
     *
     * @var \DateTimeZone|null
     */
    protected ?DateTimeZone $dbTimezone = null;

    /**
     * User time zone.
     *
     * @var \DateTimeZone|null
     */
    protected ?DateTimeZone $userTimezone = null;

    /**
     * Default time zone.
     *
     * @var \DateTimeZone
     */
    protected DateTimeZone $defaultTimezone;

    /**
     * Whether database time zone is kept when converting
     *
     * @var bool
     */
    protected bool $keepDatabaseTimezone = false;

    /**
     * {@inheritDoc}
     *
     * @param string|null $name The name identifying this type
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        $this->defaultTimezone = new DateTimeZone(date_default_timezone_get());
        $this->_className = class_exists(DateTime::class) ? DateTime::class : DateTimeImmutable::class;
    }

    /**
     * Convert DateTime instance into strings.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|null
     */
    public function toDatabase(mixed $value, Driver $driver): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }
        if (is_int($value)) {
            $class = $this->_className;
            $value = new $class('@' . $value);
        }

        if ($value instanceof ChronosDate) {
            return $value->format($this->_format);
        }

        if (!$value instanceof DateTimeInterface) {
            return null;
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
     * This is the time zone used when converting database strings to DateTime
     * instances and converting DateTime instances to database strings.
     *
     * @see DateTimeType::setKeepDatabaseTimezone
     * @param \DateTimeZone|string|null $timezone Database timezone.
     * @return $this
     */
    public function setDatabaseTimezone(DateTimeZone|string|null $timezone)
    {
        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }
        $this->dbTimezone = $timezone;

        return $this;
    }

    /**
     * Set user timezone.
     *
     * This is the time zone used when marshalling strings to DateTime instances.
     *
     * @param \DateTimeZone|string|null $timezone User timezone.
     * @return $this
     */
    public function setUserTimezone(DateTimeZone|string|null $timezone)
    {
        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }
        $this->userTimezone = $timezone;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $value Value to be converted to PHP equivalent
     * @param \Cake\Database\Driver $driver Object from which database preferences and configuration will be extracted
     * @return \Cake\I18n\DateTime|\DateTimeImmutable|null
     */
    public function toPHP(mixed $value, Driver $driver): DateTime|DateTimeImmutable|null
    {
        if ($value === null) {
            return null;
        }

        $class = $this->_className;
        if (is_int($value)) {
            $instance = new $class('@' . $value);
        } elseif (str_starts_with($value, '0000-00-00')) {
            return null;
        } else {
            $instance = new $class($value, $this->dbTimezone);
        }

        if (
            !$this->keepDatabaseTimezone
            && $instance->getTimezone()
            && $instance->getTimezone()->getName() !== $this->defaultTimezone->getName()
        ) {
            $instance = $instance->setTimezone($this->defaultTimezone);
        }

        return $instance;
    }

    /**
     * Set whether DateTime object created from database string is converted
     * to default time zone.
     *
     * If your database date times are in a specific time zone that you want
     * to keep in the DateTime instance then set this to true.
     *
     * When false, datetime timezones are converted to default time zone.
     * This is default behavior.
     *
     * @param bool $keep If true, database time zone is kept when converting
     *      to DateTime instances.
     * @return $this
     */
    public function setKeepDatabaseTimezone(bool $keep)
    {
        $this->keepDatabaseTimezone = $keep;

        return $this;
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

            $class = $this->_className;
            if (is_int($value)) {
                $instance = new $class('@' . $value);
            } elseif (str_starts_with($value, '0000-00-00')) {
                $values[$field] = null;
                continue;
            } else {
                $instance = new $class($value, $this->dbTimezone);
            }

            if (
                !$this->keepDatabaseTimezone
                && $instance->getTimezone()
                && $instance->getTimezone()->getName() !== $this->defaultTimezone->getName()
            ) {
                $instance = $instance->setTimezone($this->defaultTimezone);
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
    public function marshal(mixed $value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            if ($value instanceof NativeDateTime) {
                $value = clone $value;
            }

            /** @var \Datetime|\DateTimeImmutable $value */
            return $value->setTimezone($this->defaultTimezone);
        }
        if ($value instanceof ChronosDate) {
            return $value->toNative();
        }

        $class = $this->_className;
        try {
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $dateTime = new $class('@' . $value);

                return $dateTime->setTimezone($this->defaultTimezone);
            }

            if (is_string($value)) {
                if ($this->_useLocaleMarshal) {
                    $dateTime = $this->_parseLocaleValue($value);
                } else {
                    $dateTime = $this->_parseValue($value);
                }

                if ($dateTime) {
                    $dateTime = $dateTime->setTimezone($this->defaultTimezone);
                }

                return $dateTime;
            }
        } catch (Exception $e) {
            return null;
        }

        if (!is_array($value)) {
            return null;
        }

        $value += [
            'year' => null, 'month' => null, 'day' => null,
            'hour' => 0, 'minute' => 0, 'second' => 0, 'microsecond' => 0,
        ];
        if (
            !is_numeric($value['year']) || !is_numeric($value['month']) || !is_numeric($value['day']) ||
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
            '%d-%02d-%02d %02d:%02d:%02d.%06d',
            $value['year'],
            $value['month'],
            $value['day'],
            $value['hour'],
            $value['minute'],
            $value['second'],
            $value['microsecond']
        );

        $dateTime = new $class($format, $value['timezone'] ?? $this->userTimezone);

        return $dateTime->setTimezone($this->defaultTimezone);
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
        if ($enable === false) {
            $this->_useLocaleMarshal = $enable;

            return $this;
        }
        if (is_a($this->_className, DateTime::class, true)) {
            $this->_useLocaleMarshal = $enable;

            return $this;
        }
        throw new DatabaseException(
            sprintf('Cannot use locale parsing with the %s class', $this->_className)
        );
    }

    /**
     * Sets the locale-aware format used by `marshal()` when parsing strings.
     *
     * See `Cake\I18n\Time::parseDateTime()` for accepted formats.
     *
     * @param array|string $format The locale-aware format
     * @see \Cake\I18n\Time::parseDateTime()
     * @return $this
     */
    public function setLocaleFormat(array|string $format)
    {
        $this->_localeMarshalFormat = $format;

        return $this;
    }

    /**
     * Get the classname used for building objects.
     *
     * @return class-string<\Cake\I18n\DateTime>|class-string<\DateTimeImmutable>
     */
    public function getDateTimeClassName(): string
    {
        return $this->_className;
    }

    /**
     * Converts a string into a DateTime object after parsing it using the locale
     * aware parser with the format set by `setLocaleFormat()`.
     *
     * @param string $value The value to parse and convert to an object.
     * @return \Cake\I18n\DateTime|null
     */
    protected function _parseLocaleValue(string $value): ?DateTime
    {
        /** @var class-string<\Cake\I18n\DateTime> $class */
        $class = $this->_className;

        return $class::parseDateTime($value, $this->_localeMarshalFormat, $this->userTimezone);
    }

    /**
     * Converts a string into a DateTime object after parsing it using the
     * formats in `_marshalFormats`.
     *
     * @param string $value The value to parse and convert to an object.
     * @return \Cake\I18n\DateTime|\DateTimeImmutable|null
     */
    protected function _parseValue(string $value): DateTime|DateTimeImmutable|null
    {
        $class = $this->_className;

        foreach ($this->_marshalFormats as $format) {
            try {
                $dateTime = $class::createFromFormat($format, $value, $this->userTimezone);
                // Check for false in case DateTimeImmutable is used
                if ($dateTime !== false) {
                    return $dateTime;
                }
            } catch (InvalidArgumentException) {
                // Chronos wraps DateTimeImmutable::createFromFormat and throws
                // exception if parse fails.
                continue;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function toStatement(mixed $value, Driver $driver): int
    {
        return PDO::PARAM_STR;
    }
}
