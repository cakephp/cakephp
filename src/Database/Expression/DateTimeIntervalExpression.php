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
 * An expression object to generate the SQL for an interval expression involving
 * a datetime object.
 */
class DateTimeIntervalExpression extends IntervalExpression implements ExpressionInterface
{
    /**
     * The DateTimeInterface or ExpressionInterface object used as the starting
     * point for the interval.
     *
     * @var \DateTimeInterface|\Cake\Database\ExpressionInterface
     */
    protected $subject;

    /**
     * Constructor that takes the field identifier, expression, or date
     * object as well as an interval object to use as the values to construct
     * the interval SQL.
     *
     * @param mixed $fieldOrValue The value or identifier to be modified.
     * @param \DateInterval $interval The interval value as a DateInterval object.
     * @throws \Exception When interval object is invalid.
     */
    public function __construct($fieldOrValue, DateInterval $interval)
    {
        $this->setSubject($fieldOrValue);
        parent::__construct($interval);
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $generator): string
    {
        $sql = '';
        $options = $this->getIntervalSqlOptions();
        if (!($options['overrideCallback'] instanceof Closure)) {
            $subject = $this->getSubject();
            if ($subject instanceof ExpressionInterface) {
                $sql .= '(' . $subject->sql($generator) . ')';
            } else {
                $subject = $subject->format('Y-m-d H:i:s.u');
                $ph = $generator->placeholder('intervalParam');
                $generator->bind($ph, $subject, 'datetimefractional');
                $sql .= Hash::get($options, 'wrap.date.prefix') . $ph .
                    Hash::get($options, 'wrap.date.suffix') . $options['glue'];
            }
        }

        return $sql . parent::sql($generator);
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callable)
    {
        $subject = $this->getSubject();
        if ($subject instanceof ExpressionInterface) {
            $subject->traverse($callable);
        }

        return parent::traverse($callable);
    }

    /**
     * Method that accepts a date or expression object that serves as the
     * value the interval will be applied to.
     *
     * @param \DateTimeInterface|\Cake\Database\ExpressionInterface $subject A DateTimeInterface or ExpressionInterface object.
     * @return $this
     */
    public function setSubject($subject)
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
     * Method that resets properties to their defaults.
     *
     * @return $this
     */
    protected function reset()
    {
        parent::reset();
        $this->combineIntervalSqlOptions([
             'multiple' => true,
             'wrap' => [
                 'date' => ['prefix' => '', 'suffix' => ''],
             ],
        ]);

        return $this;
    }
}
