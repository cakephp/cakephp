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

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Closure;
use RuntimeException;

/**
 * An expression that represents a common table expression definition.
 */
class CommonTableExpression implements ExpressionInterface
{
    /**
     * The CTE name.
     *
     * @var string|null
     */
    protected $name;

    /**
     * The field names to use for the CTE.
     *
     * @var \Cake\Database\Expression\IdentifierExpression[]
     */
    protected $fields = [];

    /**
     * The CTE query definition.
     *
     * @var \Cake\Database\ExpressionInterface|null
     */
    protected $query;

    /**
     * Whether the CTE is materialized or not materialized.
     *
     * @var string|null
     */
    protected $materialized = null;

    /**
     * Whether the CTE is recursive.
     *
     * @var bool
     */
    protected $recursive = false;

    /**
     * Constructor.
     *
     * @param string $name The CTE name.
     * @param \Closure|\Cake\Database\ExpressionInterface $query CTE query
     */
    public function __construct(?string $name = null, $query = null)
    {
        $this->name = $name;
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
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the query for this CTE.
     *
     * @param \Closure|\Cake\Database\ExpressionInterface $query CTE query
     * @return $this
     */
    public function query($query)
    {
        if ($query instanceof Closure) {
            $query = $query();
            if (!($query instanceof ExpressionInterface)) {
                throw new RuntimeException(
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
     * @param string|string[]|\Cake\Database\Expression\IdentifierExpression|\Cake\Database\Expression\IdentifierExpression[] $fields Field names
     * @return $this
     */
    public function field($fields)
    {
        $fields = (array)$fields;
        foreach ($fields as &$field) {
            if (!($field instanceof IdentifierExpression)) {
                $field = new IdentifierExpression($field);
            }
        }
        $this->fields = array_merge($this->fields, $fields);

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
    public function sql(ValueBinder $generator): string
    {
        $fields = '';
        if ($this->fields) {
            $expressions = array_map(function (IdentifierExpression $e) use ($generator) {
                return $e->sql($generator);
            }, $this->fields);
            $fields = sprintf('(%s)', implode(', ', $expressions));
        }

        $suffix = $this->materialized ? $this->materialized . ' ' : '';

        return sprintf(
            '%s%s AS %s(%s)',
            (string)$this->name,
            $fields,
            $suffix,
            $this->query ? $this->query->sql($generator) : ''
        );
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $visitor)
    {
        foreach ($this->fields as $field) {
            $visitor($field);
            $field->traverse($visitor);
        }

        if ($this->query) {
            $visitor($this->query);
            $this->query->traverse($visitor);
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
        if ($this->query) {
            $this->query = clone $this->query;
        }

        foreach ($this->fields as $key => $field) {
            $this->fields[$key] = clone $field;
        }
    }
}
