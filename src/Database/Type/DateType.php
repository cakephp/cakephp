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
use Cake\I18n\Date;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;

/**
 * Class DateType
 */
class DateType extends BaseType implements BatchCastingInterface
{
    /**
     * @var string
     */
    protected string $_format = 'Y-m-d';

    /**
     * @var array<string>
     */
    protected array $_marshalFormats = [
        'Y-m-d',
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
     * See `Cake\I18n\Date::parseDate()` for accepted formats.
     *
     * @var string|int|null
     */
    protected string|int|null $_localeMarshalFormat = null;

    /**
     * The classname to use when creating objects.
     *
     * @var class-string<\Cake\Chronos\ChronosDate>
     */
    protected string $_className;

    /**
     * @inheritDoc
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        $this->_className = class_exists(Date::class) ? Date::class : ChronosDate::class;
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

        assert(is_object($value) && method_exists($value, 'format'));

        return $value->format($this->_format);
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $value Value to be converted to PHP equivalent
     * @param \Cake\Database\Driver $driver Object from which database preferences and configuration will be extracted
     * @return \Cake\Chronos\ChronosDate|null
     */
    public function toPHP(mixed $value, Driver $driver): ?ChronosDate
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
            $instance = new $class($value);
        }

        return $instance;
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
                $instance = new $class($value);
            }

            $values[$field] = $instance;
        }

        return $values;
    }

    /**
     * Convert request data into a datetime object.
     *
     * @param mixed $value Request data
     * @return \Cake\Chronos\ChronosDate|null
     */
    public function marshal(mixed $value): ?ChronosDate
    {
        if ($value instanceof $this->_className) {
            return $value;
        }

        /** @phpstan-ignore-next-line */
        if ($value instanceof DateTimeInterface || $value instanceof ChronosDate) {
            return new $this->_className($value->format($this->_format));
        }

        $class = $this->_className;
        try {
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                return new $class('@' . $value);
            }

            if (is_string($value)) {
                if ($this->_useLocaleMarshal) {
                    return $this->_parseLocaleValue($value);
                }

                return $this->_parseValue($value);
            }
        } catch (Exception) {
            return null;
        }

        if (
            !is_array($value) ||
            !isset($value['year'], $value['month'], $value['day']) ||
            !is_numeric($value['year']) || !is_numeric($value['month']) || !is_numeric($value['day'])
        ) {
            return null;
        }

        $format = sprintf('%d-%02d-%02d', $value['year'], $value['month'], $value['day']);

        return new $class($format);
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
        if (is_a($this->_className, Date::class, true)) {
            $this->_useLocaleMarshal = $enable;

            return $this;
        }
        throw new DatabaseException(
            sprintf('Cannot use locale parsing with %s', $this->_className)
        );
    }

    /**
     * Sets the locale-aware format used by `marshal()` when parsing strings.
     *
     * See `Cake\I18n\Date::parseDate()` for accepted formats.
     *
     * @param string|int $format The locale-aware format
     * @see \Cake\I18n\Date::parseDate()
     * @return $this
     */
    public function setLocaleFormat(string|int $format)
    {
        $this->_localeMarshalFormat = $format;

        return $this;
    }

    /**
     * Get the classname used for building objects.
     *
     * @return class-string<\Cake\Chronos\ChronosDate>
     */
    public function getDateClassName(): string
    {
        return $this->_className;
    }

    /**
     * @param string $value
     * @return \Cake\I18n\Date|null
     */
    protected function _parseLocaleValue(string $value): ?Date
    {
        /** @var class-string<\Cake\I18n\Date> $class */
        $class = $this->_className;

        return $class::parseDate($value, $this->_localeMarshalFormat);
    }

    /**
     * Converts a string into a DateTime object after parsing it using the
     * formats in `_marshalFormats`.
     *
     * @param string $value The value to parse and convert to an object.
     * @return \Cake\Chronos\ChronosDate|null
     */
    protected function _parseValue(string $value): ?ChronosDate
    {
        $class = $this->_className;
        foreach ($this->_marshalFormats as $format) {
            try {
                return $class::createFromFormat($format, $value);
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return null;
    }
}
