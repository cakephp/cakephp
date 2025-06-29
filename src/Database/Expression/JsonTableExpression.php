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
 * @since         5.x.x
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\ValueBinder;
use Closure;

/**
 * Represents a JSON_TABLE expression or its equivalents in different SQL dialects.
 */
class JsonTableExpression implements ExpressionInterface
{
    /**
     * The JSON column or source expression.
     *
     * @var string|\Cake\Database\ExpressionInterface
     */
    protected string|ExpressionInterface $_source;

    /**
     * The root path for the JSON data.
     *
     * @var string
     */
    protected string $_rootPath;

    /**
     * Array of column definitions.
     * Each column definition is an array with keys:
     * - name: (string) The alias for the column.
     * - type: (string) The SQL data type (e.g., VARCHAR(255), INT).
     * - path: (string) The JSON path to the value.
     * - ordinality: (bool, optional) True if this column is FOR ORDINALITY.
     * - default: (mixed, optional) Default value.
     * - onEmpty: (string, optional) Behavior on empty (e.g., 'NULL ON EMPTY').
     * - onError: (string, optional) Behavior on error.
     * - nested: (JsonTableExpression|array, optional) For NESTED PATH.
     *
     * @var array
     */
    protected array $_columns = [];

    /**
     * The alias for this JSON_TABLE expression in the query.
     *
     * @var string
     */
    protected string $_alias;

    /**
     * Constructor.
     *
     * @param string|\Cake\Database\ExpressionInterface $source The JSON column or an expression evaluating to JSON.
     * @param string $rootPath The root JSON path to extract data from (e.g., '$.items[*]').
     * @param array $columns The column definitions for the table.
     * @param string $alias The alias for this JSON table in the main query.
     */
    public function __construct(string|ExpressionInterface $source, string $rootPath, array $columns, string $alias)
    {
        $this->_source = $source;
        $this->_rootPath = $rootPath;
        $this->_alias = $alias; // It's important the alias is known to the expression for some dialects.
        $this->setColumns($columns);
    }

    /**
     * Sets the columns for the JSON table.
     *
     * @param array $columns Column definitions.
     * @return $this
     */
    public function setColumns(array $columns)
    {
        $this->_columns = [];
        foreach ($columns as $name => $definition) {
            $this->addColumn($name, $definition);
        }

        return $this;
    }

    /**
     * Adds a column to the JSON table definition.
     *
     * @param string $name The name (alias) of the column.
     * @param array|JsonTableExpression $definition An array defining the column (type, path, etc.)
     *                                            or another JsonTableExpression for a NESTED path.
     * @return $this
     * @throws \InvalidArgumentException If the definition is not valid.
     */
    public function addColumn(string $name, array|JsonTableExpression $definition)
    {
        if (is_array($definition)) {
            if (!isset($definition['path']) && !isset($definition['ordinality'])) {
                throw new \InvalidArgumentException(
                    "Column '{$name}' definition must include a 'path' or 'ordinality' key."
                );
            }
            if (!isset($definition['type']) && !isset($definition['ordinality']) && !($definition['nested'] ?? null instanceof JsonTableExpression)) {
                 // Type can be inferred by some DBs, or not needed for ordinality/nested
                 // but often required. Let's make it optional for now.
            }
        } elseif (!$definition instanceof JsonTableExpression && !($definition['nested'] ?? null instanceof JsonTableExpression)) {
            // Allow JsonTableExpression directly for nested structures
             throw new \InvalidArgumentException(
                "Column '{$name}' definition must be an array or JsonTableExpression for nested structures."
            );
        }

        $this->_columns[$name] = $definition;

        return $this;
    }

    /**
     * Gets the source JSON column or expression.
     *
     * @return string|\Cake\Database\ExpressionInterface
     */
    public function getSource(): string|ExpressionInterface
    {
        return $this->_source;
    }

    /**
     * Gets the root JSON path.
     *
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->_rootPath;
    }

    /**
     * Gets the column definitions.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->_columns;
    }

    /**
     * Gets the alias for this JSON_TABLE.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return $this->_alias;
    }

    /**
     * Converts the expression into a SQL string fragment.
     *
     * This method is a placeholder and typically should not be called directly
     * on this specific expression. The actual SQL generation is handled by the
     * QueryCompiler for the specific database dialect, which knows how to
     * interpret this JsonTableExpression.
     *
     * @param \Cake\Database\ValueBinder $binder Parameter binder.
     * @return string
     * @throws \LogicException Always, as this should be handled by the compiler.
     */
    public function sql(ValueBinder $binder): string
    {
        // The actual SQL generation is handled by the dialect-specific QueryCompiler.
        // This expression object primarily serves as a structured container for the JSON_TABLE definition.
        // However, to satisfy the interface and potentially be part of other expressions,
        // we return a placeholder or a representation that the compiler can identify.
        // For now, let's make it clear this shouldn't be directly converted to SQL here.
        throw new \LogicException(
            sprintf(
                '"%s" is a virtual expression and cannot be directly converted to SQL. ' .
                'It should be compiled by a dialect-specific QueryCompiler.',
                static::class
            )
        );
    }

    /**
     * Traverses the expression with a callback.
     *
     * @param \Closure $callback The callback to invoke.
     * @return $this
     */
    public function traverse(Closure $callback)
    {
        if ($this->_source instanceof ExpressionInterface) {
            $callback($this->_source);
            $this->_source->traverse($callback);
        }

        foreach ($this->_columns as $column) {
            if (is_array($column) && isset($column['nested']) && $column['nested'] instanceof ExpressionInterface) {
                $callback($column['nested']);
                $column['nested']->traverse($callback);
            } elseif ($column instanceof ExpressionInterface) {
                $callback($column);
                $column->traverse($callback);
            }
            // Potentially traverse other expression parts within column definitions if they exist
        }

        return $this;
    }

    /**
     * Clones the expression.
     *
     * @return void
     */
    public function __clone()
    {
        if ($this->_source instanceof ExpressionInterface) {
            $this->_source = clone $this->_source;
        }
        foreach ($this->_columns as $key => $column) {
            if (is_array($column) && isset($column['nested']) && $column['nested'] instanceof ExpressionInterface) {
                $this->_columns[$key]['nested'] = clone $column['nested'];
            } elseif ($column instanceof ExpressionInterface) {
                 $this->_columns[$key] = clone $column;
            }
        }
    }
}
