<?php
declare(strict_types=1);

namespace Cake\Utility\Cast;

use Cake\I18n\DateTime;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use function is_int;

class DateTimeCast
{
    /**
     * @param mixed $value
     */
    public static function tryFrom(mixed $value, ?DateTimeZone $timezone = null): ?DateTime
    {
        try {
            return self::from($value, $timezone);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @param mixed $value
     */
    public static function from(mixed $value, ?DateTimeZone $timezone = null): DateTime
    {
        if ($value instanceof DateTime) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return DateTime::parse($value->format('Y-m-d H:i:s.u'), $value->getTimezone());
        }
        if (is_int($value)) {
            if ($value < 0) {
                throw new InvalidArgumentException('Invalid timestamp');
            }

            return DateTime::createFromTimestamp($value, $timezone);
        }

        return DateTime::parse(StringCast::from($value), $timezone);
    }
}
