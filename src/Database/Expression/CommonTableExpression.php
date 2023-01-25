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

use Cake\Database\Exception\DatabaseException;
use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Closure;

/**
 * An expression that represents a common table expression definition.
 */
class CommonTableExpression implements ExpressionInterface
{
    /**
     * The CTE name.
     *
     * @var \Cake\Database\Expression\IdentifierExpression
     */
    protected IdentifierExpression $name;

    /**
     * The field names to use for the CTE.
     *
     * @var array<\Cake\Database\Expression\IdentifierExpression>
     */
    protected array $fields = [];

    /**
     * The CTE query definition.
     *
     * @var \Cake\Database\ExpressionInterface|null
     */
    protected ?ExpressionInterface $query = null;

    /**
     * Whether the CTE is materialized or not materialized.
     *
     * @var string|null
     */
    protected ?string $materialized = null;

    /**
     * Whether the CTE is recursive.
     *
     * @var bool
     */
    protected bool $recursive = false;

    /**
     * Constructor.
     *
     * @param string $name The CTE name.
     * @param \Cake\Database\ExpressionInterface|\Closure|null $query CTE query
     */
    public function __construct(string $name = '', ExpressionInterface|Closure|null $query = null)
    {
        $this->name = new IdentifierExpression($name);
        if ($query) {
            $this->query($query);
        }
    }

    /**
     * Sets the name of this CTE.
     *
     * This is the named you used to reference the expression
     * in select, insert, etc queries.
     *
     * @param string $name The CTE name.
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = new IdentifierExpression($name);

        return $this;
    }

    /**
     * Sets the query for this CTE.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure $query CTE query
     * @return $this
     */
    public function query(ExpressionInterface|Closure $query)
    {
        if ($query instanceof Closure) {
            $query = $query();
            if (!($query instanceof ExpressionInterface)) {
                throw new DatabaseException(
                    'You must return an `ExpressionInterface` from a Closure passed to `query()`.'
                );
            }
        }
        $this->query = $query;

        return $this;
    }

    /**
     * Adds one or more fields (arguments) to the CTE.
     *
     * @param \Cake\Database\Expression\IdentifierExpression|array<\Cake\Database\Expression\IdentifierExpression>|array<string>|string $fields Field names
     * @return $this
     */
    public function field(IdentifierExpression|array|string $fields)
    {
        $fields = (array)$fields;
        /** @var array<string|\Cake\Database\Expression\IdentifierExpression> $fields */
        foreach ($fields as &$field) {
            if (!($field instanceof IdentifierExpression)) {
                $field = new IdentifierExpression($field);
            }
        }
        /** @var array<\Cake\Database\Expression\IdentifierExpression> $mergedFields */
        $mergedFields = array_merge($this->fields, $fields);
        $this->fields = $mergedFields;

        return $this;
    }

    /**
     * Sets this CTE as materialized.
     *
     * @return $this
     */
    public function materialized()
    {
        $this->materialized = 'MATERIALIZED';

        return $this;
    }

    /**
     * Sets this CTE as not materialized.
     *
     * @return $this
     */
    public function notMaterialized()
    {
        $this->materialized = 'NOT MATERIALIZED';

        return $this;
    }

    /**
     * Gets whether this CTE is recursive.
     *
     * @return bool
     */
    public function isRecursive(): bool
    {
        return $this->recursive;
    }

    /**
     * Sets this CTE as recursive.
     *
     * @return $this
     */
    public function recursive()
    {
        $this->recursive = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $fields = '';
        if ($this->fields) {
            $expressions = array_map(fn (IdentifierExpression $e) => $e->sql($binder), $this->fields);
            $fields = sprintf('(%s)', implode(', ', $expressions));
        }

        $suffix = $this->materialized ? $this->materialized . ' ' : '';

        return sprintf(
            '%s%s AS %s(%s)',
            $this->name->sql($binder),
            $fields,
            $suffix,
            $this->query ? $this->query->sql($binder) : ''
        );
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        $callback($this->name);
        foreach ($this->fields as $field) {
            $callback($field);
            $field->traverse($callback);
        }

        if ($this->query) {
            $callback($this->query);
            $this->query->traverse($callback);
        }

        return $this;
    }

    /**
     * Clones the inner expression objects.
     *
     * @return void
     */
    public function __clone()
    {
        $this->name = clone $this->name;
        if ($this->query) {
            $this->query = clone $this->query;
        }

        foreach ($this->fields as $key => $field) {
            $this->fields[$key] = clone $field;
        }
    }
}
