<?php
declare(strict_types=1);

namespace Cake\Utility\Cast;

use Cake\I18n\Date;
use Cake\I18n\DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use function is_int;

class DateCast
{
    /**
     * @param mixed $value
     */
    public static function from(mixed $value): Date
    {
        if ($value instanceof Date) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return Date::parse($value->format('Y-m-d'));
        }
        if (is_int($value)) {
            if ($value < 0) {
                throw new InvalidArgumentException('Invalid timestamp');
            }

            return new Date(DateTime::createFromTimestamp($value));
        }

        return Date::parse(StringCast::from($value));
    }

    /**
     * @param mixed $value
     */
    public static function tryFrom(mixed $value): ?Date
    {
        try {
            return self::from($value);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
}
