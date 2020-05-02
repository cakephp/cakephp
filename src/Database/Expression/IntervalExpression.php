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
use DateTimeInterface;

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
    protected const KEYS_REQUIRED = ['y' => 1, 'm' => 1, 'd' => 1, 'h' => 1, 'i' => 1, 's' => 1, 'f' => 1];

    /**
     * SQL unit values for DateInterval array conversion.
     *
     * @var string[]
     */
    protected const KEYS_TRANSFORM = ['YEAR', 'MONTH', 'DAY', 'HOUR', 'MINUTE', 'SECOND', 'MICROSECOND'];

    /**
     * The interval object used to construct the interval SQL statement.
     *
     * @var \DateInterval
     */
    protected $interval;

    /**
     * Overriding ExpressionInterface object for server-specific SQL.
     *
     * @var \Cake\Database\ExpressionInterface|null
     */
    protected $overrideExpression;

    /**
     * The DateTimeInterface or ExpressionInterface object used as the starting
     * point for the interval.
     *
     * @var \DateTimeInterface|\Cake\Database\ExpressionInterface
     */
    protected $subject;

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
     */
    public function __construct($fieldOrValue, DateInterval $interval)
    {
        $this->setSubject($fieldOrValue);
        $this->setInterval($interval)->reset();
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $generator): string
    {
        $exp = $this->getOverrideExpression();
        $sql = '';
        if ($exp) {
            $sql = $exp->sql($generator);
            $this->setOverrideExpression(null);
        } else {
            $sql .= $this->generateIntervalSql($generator);
        }
        $this->reset();

        return $sql;
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callable): self
    {
        if ($this->getOverrideExpression() instanceof ExpressionInterface) {
            $this->getOverrideExpression()->traverse($callable);
        }
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
     * @return self
     */
    public function combineIntervalSqlOptions(array $options): self
    {
        $this->intervalSqlOptions = array_merge($this->getIntervalSqlOptions(), $options);

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
     * Method that sets an overriding expression for servers that require
     * the use of an expression (usually a function) instead of an interval
     * statement.
     *
     * @param \Cake\Database\ExpressionInterface|null $exp An ExpressionInterface object or null value.
     * @return self
     */
    public function setOverrideExpression(?ExpressionInterface $exp): self
    {
        $this->overrideExpression = $exp;

        return $this;
    }

    /**
     * Method that returns the overriding expression.
     *
     * @return \Cake\Database\ExpressionInterface|null
     */
    public function getOverrideExpression(): ?ExpressionInterface
    {
        return $this->overrideExpression;
    }

    /**
     * Method that accepts a date or expression object that serves as the
     * value the interval will be applied to.
     *
     * @param \DateTimeInterface|\Cake\Database\ExpressionInterface $subject A DateTimeInterface or ExpressionInterface object.
     * @return self
     */
    public function setSubject($subject): self
    {
        if (!($subject instanceof DateTimeInterface) && !($subject instanceof ExpressionInterface)) {
            throw new Exception(
                'Value must be ' . DateTimeInterface::class . ' or ' . ExpressionInterface::class
            );
        }
        $this->subject = $subject;

        return $this;
    }

    /**
     * Method that returns the field or value object.
     *
     * @return \DateTimeInterface|\Cake\Database\ExpressionInterface
     */
    public function getSubject()
    {
        return $this->subject;
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
     * @return self
     */
    public function setInterval(DateInterval $interval): self
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
     * Method that returns the sign (+ or -) of the interval object.
     *
     * @return string Either + or - depending on the state of the interval object's invert property.
     */
    public function getIntervalSign(): string
    {
        return $this->getInterval()->invert ? '-' : '+';
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
    public static function transformForDatabase(DateInterval $di, bool $combineMicro = true): array
    {
        $intervalAry = array_filter(
            array_combine(
                static::KEYS_TRANSFORM,
                array_intersect_key((array)$di, static::KEYS_REQUIRED)
            )
        );
        if ($combineMicro) {
            $intervalAry['SECOND'] = $intervalAry['SECOND'] + $intervalAry['MICROSECOND'];
            unset($intervalAry['MICROSECOND']);
        } else {
            $intervalAry['MICROSECOND'] *= 1000000;
        }

        return $intervalAry;
    }

    /**
     * Method that returns the formatted SQL for interval statements.
     *
     * @param \Cake\Database\ValueBinder $generator ValueBinder for date
     * @return string
     */
    private function generateIntervalSql(ValueBinder $generator): string
    {
        $preSql = '';
        $interval = self::transformForDatabase($this->getInterval());
        $sign = $this->getIntervalSign();
        $intervalAry = [];
        $options = $this->getIntervalSqlOptions();
        $subject = $this->getSubject();
        if ($subject instanceof ExpressionInterface) {
            $preSql .= '(' . $subject->sql($generator) . ')';
        } else {
            $subject = $subject->format('Y-m-d H:i:s.u');
            $ph = $generator->placeholder('interval');
            $generator->bind($ph, $subject, 'datetimefractional');
            $preSql .= Hash::get($options, 'wrap.date.prefix') . $ph . Hash::get($options, 'wrap.date.suffix');
        }
        foreach ($interval as $iUnit => $iValue) {
            $intervalAry[] = Hash::get($options, 'wrap.inner.prefix') . ("${sign}1" * $iValue) . ' ' .
                $iUnit . Hash::get($options, 'wrap.inner.suffix');
        }

        return $preSql . Hash::get($options, 'wrap.prefix') . implode($options['glue'], $intervalAry) .
            Hash::get($options, 'wrap.suffix');
    }

    /**
     * Method that resets properties to their defaults.
     *
     * @return self
     */
    public function reset(): self
    {
        $this->setOverrideExpression(null);
        $this->combineIntervalSqlOptions([
             'glue' => ' + ',
             'wrap' => [
                 'prefix' => ' + ',
                 'suffix' => '',
                 'inner' => ['prefix' => 'INTERVAL ', 'suffix' => ''],
                 'date' => ['prefix' => '', 'suffix' => ''],
             ],
        ]);

        return $this;
    }
}
