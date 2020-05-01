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
     * @var array
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
    protected $fieldOrValue;

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
        $this->setFieldOrValue($fieldOrValue);
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
            $fOrV = $this->getFieldOrValue();
            if ($fOrV instanceof ExpressionInterface) {
                $sql .= $fOrV->sql($generator);
            } else {
                $fOrV = $fOrV->format('Y-m-d H:i:s.u');
                $ph = $generator->placeholder('interval');
                $generator->bind($ph, $fOrV, 'datetimefractional');
                $sql .= $ph;
            }
            $sql .= ' + ' . $this->generateIntervalSql();
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
        if ($this->getFieldOrValue() instanceof ExpressionInterface) {
            $this->getFieldOrValue()->traverse($callable);
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
     * the use of en expression (usually a function) instead of an interval
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
     * @param \DateTimeInterface|\Cake\Database\ExpressionInterface $fov A DateTimeInterface or ExpressionInterface object.
     * @return self
     */
    public function setFieldOrValue($fov): self
    {
        if (!($fov instanceof DateTimeInterface) && !($fov instanceof ExpressionInterface)) {
            throw new Exception(
                'Value must be ' . DateTimeInterface::class . ' or ' . ExpressionInterface::class
            );
        }
        $this->fieldOrValue = $fov;

        return $this;
    }

    /**
     * Method that returns the field or value object.
     *
     * @return \DateTimeInterface|\Cake\Database\ExpressionInterface
     */
    public function getFieldOrValue()
    {
        return $this->fieldOrValue;
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
            $interval->h + $interval->i + $interval->s + $interval->f) != 0;
        if ($isValid == false) {
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
     * @return array Converted & transformed interval object.
     */
    public static function transformForDatabase(DateInterval $di): array
    {
        $intervalAry = array_filter(
            array_combine(
                static::KEYS_TRANSFORM,
                array_intersect_key((array) $di, static::KEYS_REQUIRED)
            )
        );
        $intervalAry['SECOND'] = $intervalAry['SECOND'] + $intervalAry['MICROSECOND'];
        unset($intervalAry['MICROSECOND']);
        return $intervalAry;
    }

    /**
     * Method that returns the formatted SQL for interval statements.
     *
     * @return string
     */
    private function generateIntervalSql(): string
    {
        $interval = self::transformForDatabase($this->getInterval());
        $sign = $this->getIntervalSign();
        $intervalAry = [];
        $options = $this->getIntervalSqlOptions();
        if (1 == 0) {
            $options['glue'] = ' ';
            $options['sql-prefix'] = 'INTERVAL';
            $options['prefix'] = $options['suffix'] = '\'';
        }
        foreach ($interval as $iUnit => $iValue) {
            $intervalAry[] = sprintf(
                $options['format'],
                $options['prefix'],
                ("${sign}1" * $iValue) . ' ' . $iUnit,
                $options['suffix']
            );
        }
        return $options['sql-prefix'] . implode($options['glue'], $intervalAry);
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
             'glue'          => ' + ',
             'format'        => '%s%s%s',
             'prefix'        => 'INTERVAL ',
             'suffix'        => '',
             'sql-prefix'    => '',
        ]);
        return $this;
    }
}
