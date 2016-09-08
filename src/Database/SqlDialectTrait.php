<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\Expression\Comparison;

/**
 * Sql dialect trait
 */
trait SqlDialectTrait
{

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words
     *
     * @param string $identifier The identifier to quote.
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        $identifier = trim($identifier);

        if ($identifier === '*') {
            return '*';
        }

        if ($identifier === '') {
            return '';
        }

        // string
        if (preg_match('/^[\w-]+$/', $identifier)) {
            return $this->_startQuote . $identifier . $this->_endQuote;
        }

        if (preg_match('/^[\w-]+\.[^ \*]*$/', $identifier)) {
// string.string
            $items = explode('.', $identifier);

            return $this->_startQuote . implode($this->_endQuote . '.' . $this->_startQuote, $items) . $this->_endQuote;
        }

        if (preg_match('/^[\w-]+\.\*$/', $identifier)) {
// string.*
            return $this->_startQuote . str_replace('.*', $this->_endQuote . '.*', $identifier);
        }

        if (preg_match('/^([\w-]+)\((.*)\)$/', $identifier, $matches)) {
// Functions
            return $matches[1] . '(' . $this->quoteIdentifier($matches[2]) . ')';
        }

        // Alias.field AS thing
        if (preg_match('/^([\w-]+(\.[\w-]+|\(.*\))*)\s+AS\s*([\w-]+)$/i', $identifier, $matches)) {
            return $this->quoteIdentifier($matches[1]) . ' AS ' . $this->quoteIdentifier($matches[3]);
        }

        if (preg_match('/^[\w-_\s]*[\w-_]+/', $identifier)) {
            return $this->_startQuote . $identifier . $this->_endQuote;
        }

        return $identifier;
    }

    /**
     * Returns a callable function that will be used to transform a passed Query object.
     * This function, in turn, will return an instance of a Query object that has been
     * transformed to accommodate any specificities of the SQL dialect in use.
     *
     * @param string $type the type of query to be transformed
     * (select, insert, update, delete)
     * @return callable
     */
    public function queryTranslator($type)
    {
        return function ($query) use ($type) {
            if ($this->autoQuoting()) {
                $query = (new IdentifierQuoter($this))->quote($query);
            }

            $query = $this->{'_' . $type . 'QueryTranslator'}($query);
            $translators = $this->_expressionTranslators();
            if (!$translators) {
                return $query;
            }

            $query->traverseExpressions(function ($expression) use ($translators, $query) {
                foreach ($translators as $class => $method) {
                    if ($expression instanceof $class) {
                        $this->{$method}($expression, $query);
                    }
                }
            });

            return $query;
        };
    }

    /**
     * Returns an associative array of methods that will transform Expression
     * objects to conform with the specific SQL dialect. Keys are class names
     * and values a method in this class.
     *
     * @return array
     */
    protected function _expressionTranslators()
    {
        return [];
    }

    /**
     * Apply translation steps to select queries.
     *
     * @param \Cake\Database\Query $query The query to translate
     * @return \Cake\Database\Query The modified query
     */
    protected function _selectQueryTranslator($query)
    {
        return $this->_transformDistinct($query);
    }

    /**
     * Returns the passed query after rewriting the DISTINCT clause, so that drivers
     * that do not support the "ON" part can provide the actual way it should be done
     *
     * @param \Cake\Database\Query $query The query to be transformed
     * @return \Cake\Database\Query
     */
    protected function _transformDistinct($query)
    {
        if (is_array($query->clause('distinct'))) {
            $query->group($query->clause('distinct'), true);
            $query->distinct(false);
        }

        return $query;
    }

    /**
     * Apply translation steps to delete queries.
     *
     * Chops out aliases on delete query conditions as most database dialects do not
     * support aliases in delete queries. This also removes aliases
     * in table names as they frequently don't work either.
     *
     * We are intentionally not supporting deletes with joins as they have even poorer support.
     *
     * @param \Cake\Database\Query $query The query to translate
     * @return \Cake\Database\Query The modified query
     */
    protected function _deleteQueryTranslator($query)
    {
        $hadAlias = false;
        $tables = [];
        foreach ($query->clause('from') as $alias => $table) {
            if (is_string($alias)) {
                $hadAlias = true;
            }
            $tables[] = $table;
        }
        if ($hadAlias) {
            $query->from($tables, true);
        }

        if (!$hadAlias) {
            return $query;
        }

        return $this->_removeAliasesFromConditions($query);
    }

    /**
     * Apply translation steps to update queries.
     *
     * Chops out aliases on update query conditions as not all database dialects do support
     * aliases in update queries.
     *
     * Just like for delete queries, joins are currently not supported for update queries.
     *
     * @param \Cake\Database\Query $query The query to translate
     * @return \Cake\Database\Query The modified query
     */
    protected function _updateQueryTranslator($query)
    {
        return $this->_removeAliasesFromConditions($query);
    }

    /**
     * Removes aliases from the `WHERE` clause of a query.
     *
     * @param \Cake\Database\Query $query The query to process.
     * @return \Cake\Database\Query The modified query.
     */
    protected function _removeAliasesFromConditions($query)
    {
        if (!empty($query->clause('join'))) {
            trigger_error(
                'Aliases are being removed from conditions for UPDATE/DELETE queries, ' .
                'this can break references to joined tables.',
                E_USER_NOTICE
            );
        }

        $conditions = $query->clause('where');
        if ($conditions) {
            $conditions->traverse(function ($condition) {
                if (!($condition instanceof Comparison)) {
                    return $condition;
                }

                $field = $condition->getField();
                if ($field instanceof ExpressionInterface || strpos($field, '.') === false) {
                    return $condition;
                }

                list(, $field) = explode('.', $field);
                $condition->setField($field);

                return $condition;
            });
        }

        return $query;
    }

    /**
     * Apply translation steps to insert queries.
     *
     * @param \Cake\Database\Query $query The query to translate
     * @return \Cake\Database\Query The modified query
     */
    protected function _insertQueryTranslator($query)
    {
        return $query;
    }

    /**
     * Returns a SQL snippet for creating a new transaction savepoint
     *
     * @param string $name save point name
     * @return string
     */
    public function savePointSQL($name)
    {
        return 'SAVEPOINT LEVEL' . $name;
    }

    /**
     * Returns a SQL snippet for releasing a previously created save point
     *
     * @param string $name save point name
     * @return string
     */
    public function releaseSavePointSQL($name)
    {
        return 'RELEASE SAVEPOINT LEVEL' . $name;
    }

    /**
     * Returns a SQL snippet for rollbacking a previously created save point
     *
     * @param string $name save point name
     * @return string
     */
    public function rollbackSavePointSQL($name)
    {
        return 'ROLLBACK TO SAVEPOINT LEVEL' . $name;
    }
}
