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
     * Constructor
     *
     * @param string $_identifier The identifier this expression represents
     * @param string|null $collation The identifier collation
     */
    public function __construct(
        /**
         * Holds the identifier string
         */
        protected string $_identifier,
        protected ?string $collation = null
    )
    {
    }

    /**
     * Sets the identifier this expression represents
     *
     * @param string $identifier The identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->_identifier = $identifier;
    }

    /**
     * Returns the identifier this expression represents
     */
    public function getIdentifier(): string
    {
        return $this->_identifier;
    }

    /**
     * Sets the collation.
     *
     * @param string $collation Identifier collation
     */
    public function setCollation(string $collation): void
    {
        $this->collation = $collation;
    }

    /**
     * Returns the collation.
     */
    public function getCollation(): ?string
    {
        return $this->collation;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $sql = $this->_identifier;
        if ($this->collation) {
            $sql .= ' COLLATE ' . $this->collation;
        }

        return $sql;
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback): static
    {
        return $this;
    }
}
