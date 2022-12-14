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

use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
use DateTime as NativeDateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;

/**
 * Time type converter.
 *
 * Use to convert time instances to strings & back.
 */
class TimeType extends DateTimeType
{
    /**
     * @inheritDoc
     */
    protected string $_format = 'H:i:s';

    /**
     * {@inheritDoc}
     *
     * @var array<string>
     */
    protected array $_marshalFormats = [
        'H:i:s',
        'H:i',
    ];

    /**
     * Convert request data into a datetime object.
     *
     * @param mixed $value Request data
     * @return \Cake\Chronos\Chronos|\DateTimeInterface|null
     */
    public function marshal(mixed $value): Chronos|DateTimeInterface|null
    {
        if ($value instanceof DateTimeInterface || $value instanceof Chronos) {
            if ($value instanceof NativeDateTime) {
                $value = clone $value;
            }

            return $value;
        }

        $class = $this->_className;
        try {
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                return new $class('@' . $value);
            }

            if (is_string($value)) {
                if ($this->_useLocaleMarshal) {
                    return $this->_parseLocaleTimeValue($value);
                } else {
                    return $this->_parseTimeValue($value);
                }
            }
        } catch (Exception $e) {
            return null;
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
            $value['microsecond']
        );

        return new $class($format);
    }

    /**
     * @inheritDoc
     */
    protected function _parseLocaleTimeValue(string $value): ?DateTime
    {
        /** @psalm-var class-string<\Cake\I18n\DateTime> $class */
        $class = $this->_className;

        /** @psalm-suppress PossiblyInvalidArgument */
        return $class::parseTime($value, $this->_localeMarshalFormat);
    }

    /**
     * Converts a string into a DateTime object after parsing it using the
     * formats in `_marshalFormats`.
     *
     * @param string $value The value to parse and convert to an object.
     * @return \Cake\I18n\DateTime|\DateTimeImmutable|null
     */
    protected function _parseTimeValue(string $value): DateTime|DateTimeImmutable|null
    {
        $class = $this->_className;
        foreach ($this->_marshalFormats as $format) {
            try {
                $dateTime = $class::createFromFormat($format, $value);
                // Check for false in case DateTime is used directly
                if ($dateTime !== false) {
                    return $dateTime;
                }
            } catch (InvalidArgumentException) {
                // Chronos wraps DateTime::createFromFormat and throws
                // exception if parse fails.
                continue;
            }
        }

        return null;
    }
}
