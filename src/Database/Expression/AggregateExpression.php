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

use Cake\Database\ValueBinder;
use Closure;

/**
 * This represents a SQL aggregate function expression in a SQL statement.
 * Calls can be constructed by passing the name of the function and a list of params.
 * For security reasons, all params passed are quoted by default unless
 * explicitly told otherwise.
 */
class AggregateExpression extends FunctionExpression
{
    /**
     * @var \Cake\Database\Expression\WindowExpression
     */
    protected $window;

    /**
     * Adds an empty `OVER()` window expression.
     *
     * If the window expression for this aggregate is already
     * initialized, this does nothing.
     *
     * @return $this
     */
    public function over()
    {
        if ($this->window === null) {
            $this->window = new WindowExpression();
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $generator): string
    {
        $sql = parent::sql($generator);
        if ($this->window !== null) {
            $sql .= ' ' . $this->window->sql($generator);
        }

        return $sql;
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $visitor)
    {
        parent::traverse($visitor);
        if ($this->window !== null) {
            $this->window->traverse($visitor);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        $count = parent::count();
        if ($this->window !== null) {
            $count = $count + 1;
        }

        return $count;
    }
}
