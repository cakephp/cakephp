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
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\Exception;
use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Cake\Utility\Hash;
use Closure;
use DateInterval;

/**
 * An expression object to generate the SQL for an interval expression.
 */
class IntervalExpression implements ExpressionInterface
{
    /**
     * Key mappings for DateInterval array conversion.
     *
     * @var array
     */
    private const KEYS_REQUIRED = ['y' => 1, 'm' => 1, 'd' => 1, 'h' => 1, 'i' => 1, 's' => 1, 'f' => 1];

    /**
     * SQL unit values for DateInterval array conversion.
     *
     * @var string[]
     */
    private const KEYS_TRANSFORM = ['YEAR', 'MONTH', 'DAY', 'HOUR', 'MINUTE', 'SECOND', 'MICROSECOND'];

    /**
     * The interval object used to construct the interval SQL statement.
     *
     * @var \DateInterval
     */
    protected $interval;

    /**
     * Array of options used to construct SQL for servers that specify
     * intervals as an expression rather than a function.
     *
     * @var array
     */
    protected $intervalSqlOptions = [];

    /**
     * Constructor that takes the field identifier, expression, or date
     * object as well as an interval object to use as the values to construct
     * the interval SQL.
     *
     * @param mixed $fieldOrValue The value or identifier to be modified.
     * @param \DateInterval $interval The interval value as a DateInterval object.
     * @throws \Exception When interval object is invalid.
     */
    public function __construct(DateInterval $interval)
    {
        $this->setInterval($interval)->reset();
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $generator): string
    {
        $interval = self::transformForDatabase($this->getInterval());
        $options = $this->getIntervalSqlOptions();
        if ($options['overrideCallback'] instanceof \Closure) {
            $options['overrideCallback'] = $options['overrideCallback']($this, $interval, $generator);
        }
        if ($options['overrideCallback'] instanceof ExpressionInterface) {
            $sql = $options['overrideCallback']->sql($generator);
        } elseif (is_string($options['overrideCallback']) && !empty($options['overrideCallback'])) {
            $sql = $options['overrideCallback'];
        } else {
            $intervalAry = [];
            if ($interval['YEAR'] || $interval['MONTH']) {
                $formatFnc = Hash::get($options, 'format.YEAR_MONTH');
                $intervalAry = array_merge(
                    $intervalAry,
                    $formatFnc instanceof \Closure ?
                        $formatFnc($options, $interval) : self::formatYearMonth($options, $interval)
                );
            }
            unset($interval['YEAR'], $interval['MONTH']);
            if (!empty($interval)) {
                $formatFnc = Hash::get($options, 'format.DAY_HOUR_MINUTE_SECOND');
                $intervalAry = array_merge(
                    $intervalAry,
                    $formatFnc instanceof \Closure ?
                        $formatFnc($options, $interval) : self::formatDayHourMinuteSecond($options, $interval)
                );
            }
            $sql = Hash::get($options, 'wrap.prefix') . implode($options['glue'], $intervalAry) .
                Hash::get($options, 'wrap.suffix');
        }
        $this->reset();

        return $sql;
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callable)
    {
        if ($this->getSubject() instanceof ExpressionInterface) {
            $this->getSubject()->traverse($callable);
        }

        return $this;
    }

    /**
     * Method that accepts and overwrites the options array with the passed
     * parameter.
     *
     * @param array $options An array of options.
     * @return $this
     */
    public function combineIntervalSqlOptions(array $options, $replace = false)
    {
        if ($replace) {
            $this->intervalSqlOptions = $options;
        } else {
            $this->intervalSqlOptions = array_replace_recursive($this->getIntervalSqlOptions(), $options);
        }

        return $this;
    }

    /**
     * Method that returns the interval options array.
     *
     * @return array
     */
    public function getIntervalSqlOptions(): array
    {
        return $this->intervalSqlOptions;
    }

    /**
     * Method accepts an interval object that serves as the values to add
     * or subtract from the value or expression provided.
     *
     * An exception is thrown if the interval object is invalid (has no
     * usable values / is a relative or special interval). Intervals can only be
     * strict addition or subtraction of units (years, months, days, etc.).
     * Relative or special intervals such as "Second Tuesday in January" are
     * invalid. This is because it is impossible to pull any usable value from
     * the interval object and not all database servers support relative/special
     * intervals.
     *
     * @param \DateInterval $interval An interval object.
     * @throws \Exception When interval value is not valid.
     * @return $this
     */
    public function setInterval(DateInterval $interval)
    {
        $isValid = ($interval->y + $interval->m + $interval->d +
            $interval->h + $interval->i + $interval->s + $interval->f) !== 0;
        if (!$isValid) {
            throw new Exception(
                'Interval needs to be greater than zero. Note that relative intervals are not supported.'
            );
        }
        $this->interval = $interval;

        return $this;
    }

    /**
     * Method that returns the interval object.
     *
     * @return \DateInterval
     */
    public function getInterval(): DateInterval
    {
        return $this->interval;
    }

    /**
     * Method accepts an interval object and converts to an array and applies
     * the SQL transform values. For instance, the 'y' key becomes 'YEAR', 'm'
     * becomes 'MONTH', 'h' becomes 'HOUR', etc.
     *
     * @param \DateInterval $di An interval object.
     * @param bool $combineMicro Determines if the microseconds are combined into seconds.
     * @return array Converted & transformed interval object.
     */
    private static function transformForDatabase(DateInterval $di, bool $combineMicro = true): array
    {
        $intervalAry = array_combine(
            self::KEYS_TRANSFORM,
            array_intersect_key((array)$di, self::KEYS_REQUIRED)
        );
        if ($combineMicro) {
            $intervalAry['SECOND'] = $intervalAry['SECOND'] + $intervalAry['MICROSECOND'];
            unset($intervalAry['MICROSECOND']);
        } else {
            $intervalAry['MICROSECOND'] *= 1000000;
        }

        return array_map(function ($number) use ($di) {
            return static::setNumericSign($di->invert, $number);
        }, $intervalAry);
    }

    /**
     * Method that resets properties to their defaults.
     *
     * @return $this
     */
    protected function reset()
    {
        $this->combineIntervalSqlOptions([
             'glue' => ' + ',
             'multiple' => false,
             'overrideCallback' => null,
             'format' => [
                 'YEAR_MONTH' => null,
                 'DAY_HOUR_MINUTE_SECOND' => null,
             ],
             'wrap' => [
                 'prefix' => '',
                 'suffix' => '',
                 'inner' => ['prefix' => 'INTERVAL ', 'suffix' => ''],
             ],
        ], true);

        return $this;
    }

    /**
     * TODO: DOCUMENTATION
     */
    private static function setNumericSign($sign, $number)
    {
        return $sign === '-' && $number >= 0 ? (-1 * $number) : $number;
    }

    /**
     * TODO: DOCUMENTATION
     */
    private static function formatYearMonth(array $options, array $interval): array
    {
        return [
            sprintf(
                "%s'%s' %s%s",
                Hash::get($options, 'wrap.inner.prefix'),
                $interval['YEAR'] . '-' . $interval['MONTH'],
                'YEAR_MONTH',
                Hash::get($options, 'wrap.inner.suffix')
            ),
        ];
    }

    /**
     * TODO: DOCUMENTATION
     */
    private static function formatDayHourMinuteSecond(array $options, array $interval): array
    {
        return [
            sprintf(
                "%s'%s' %s%s",
                Hash::get($options, 'wrap.inner.prefix'),
                sprintf(
                    '%02d %02d:%02d:%09.6f',
                    $interval['DAY'],
                    $interval['HOUR'],
                    $interval['MINUTE'],
                    $interval['SECOND']
                ),
                'DAY_MICROSECOND',
                Hash::get($options, 'wrap.inner.suffix')
            ),
        ];
    }
}
