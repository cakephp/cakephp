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
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Closure;

/**
 * Represents a single identifier name in the database.
 *
 * Identifier values are unsafe with user supplied data.
 * Values will be quoted when identifier quoting is enabled.
 *
 * @see \Cake\Database\Query::identifier()
 */
class IdentifierExpression implements ExpressionInterface
{
    /**
     * Holds the identifier string
     *
     * @var string
     */
    protected $_identifier;

    /**
     * Constructor
     *
     * @param string $identifier The identifier this expression represents
     */
    public function __construct(string $identifier)
    {
        $this->_identifier = $identifier;
    }

    /**
     * Sets the identifier this expression represents
     *
     * @param string $identifier The identifier
     * @return void
     */
    public function setIdentifier(string $identifier): void
    {
        $this->_identifier = $identifier;
    }

    /**
     * Returns the identifier this expression represents
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->_identifier;
    }

    /**
     * Converts the expression to its string representation
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator): string
    {
        return $this->_identifier;
    }

    /**
     * This method is a no-op, this is a leaf type of expression,
     * hence there is nothing to traverse
     *
     * @param \Closure $callable The callable to traverse with.
     * @return $this
     */
    public function traverse(Closure $callable)
    {
        return $this;
    }
}
