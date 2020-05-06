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
     * @param \DateInterval $interval The interval value as a DateInterval object.
     * @throws \Exception Thrown when interval object is invalid.
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
        $override = Hash::get($options, 'overrideCallback');
        if ($override instanceof Closure) {
            $override = $override($this, $interval, $generator);
        }
        if ($override instanceof ExpressionInterface) {
            $sql = $override->sql($generator);
        } elseif (is_string($override) && !empty($override)) {
            $sql = $override;
        } else {
            $intervalAry = [];
            if ($interval['YEAR'] || $interval['MONTH']) {
                $intervalAry = array_merge($intervalAry, static::format('YEAR_MONTH', $options, $interval));
            }
            unset($interval['YEAR'], $interval['MONTH']);
            if (!empty($interval)) {
                $intervalAry = array_merge($intervalAry, static::format('DAY_MICROSECOND', $options, $interval));
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
        return $this;
    }

    /**
     * Method that accepts and overwrites the options array with the passed
     * parameter.
     *
     * @param array $options An array of options.
     * @param bool $replace Boolean value to indicate if the entire options array should be replaced.
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
    protected function getIntervalSqlOptions(): array
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
    protected function setInterval(DateInterval $interval)
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
    protected function getInterval(): DateInterval
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
            return static::setNumericSign($di->invert == true, $number);
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
                 'default' => "%s%s%s",
                 'inner' => [
                     'default' => "%d %s",
                     'YEAR_MONTH' => "'%d-%d' %s",
                     'DAY_MICROSECOND' => "'%02d %02d:%02d:%09.6f' %s",
                 ],
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
     * Method to ensure the proper numeric positive/negative is used.
     *
     * @param bool $negative Boolean value indicating if $number is supposed to be negative.
     * @param int|float $number The number that may need to be flipped between positive and negative.
     * @return int|float
     */
    private static function setNumericSign(bool $negative, $number)
    {
        return $negative && $number >= 0 ? (-1 * $number) : $number;
    }

    /**
     * Method to format the return value based on a key utilizing the
     * formatting properties within the sql options array.
     *
     * @param string $key The key to identify what field is being formatted.
     * @param array $options The options sql options array.
     * @param array $interval The processed interval values array.
     * @return string[]
     */
    private static function format(string $key, array $options, array $interval): array
    {
        if ($key == 'YEAR_MONTH') {
            return [
                sprintf(
                    Hash::get($options, 'format.default'),
                    Hash::get($options, 'wrap.inner.prefix'),
                    sprintf(
                        Hash::get($options, 'format.inner.YEAR_MONTH'),
                        $interval['YEAR'],
                        $interval['MONTH'],
                        $key
                    ),
                    Hash::get($options, 'wrap.inner.suffix')
                ),
            ];
        } elseif ($key == 'DAY_MICROSECOND') {
            return [
                sprintf(
                    Hash::get($options, 'format.default'),
                    Hash::get($options, 'wrap.inner.prefix'),
                    sprintf(
                        Hash::get($options, 'format.inner.DAY_MICROSECOND'),
                        $interval['DAY'],
                        $interval['HOUR'],
                        $interval['MINUTE'],
                        $interval['SECOND'],
                        $key
                    ),
                    Hash::get($options, 'wrap.inner.suffix')
                ),
            ];
        } elseif (isset($interval[$key])) {
            return [
                sprintf(
                    Hash::get($options, 'format.default'),
                    Hash::get($options, 'wrap.inner.prefix'),
                    sprintf(Hash::get($options, 'format.inner.default'), $interval[$key], $key),
                    Hash::get($options, 'wrap.inner.suffix')
                ),
            ];
        }

        return [];
    }
}
