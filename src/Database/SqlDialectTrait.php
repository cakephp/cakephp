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
namespace Cake\Database;

use Cake\Database\Expression\Comparison;
use Cake\Database\Expression\IdentifierExpression;
use Closure;
use RuntimeException;

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
    public function quoteIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if ($identifier === '*' || $identifier === '') {
            return $identifier;
        }

        // string
        if (preg_match('/^[\w-]+$/u', $identifier)) {
            return $this->_startQuote . $identifier . $this->_endQuote;
        }

        // string.string
        if (preg_match('/^[\w-]+\.[^ \*]*$/u', $identifier)) {
            $items = explode('.', $identifier);

            return $this->_startQuote . implode($this->_endQuote . '.' . $this->_startQuote, $items) . $this->_endQuote;
        }

        // string.*
        if (preg_match('/^[\w-]+\.\*$/u', $identifier)) {
            return $this->_startQuote . str_replace('.*', $this->_endQuote . '.*', $identifier);
        }

        // Functions
        if (preg_match('/^([\w-]+)\((.*)\)$/', $identifier, $matches)) {
            return $matches[1] . '(' . $this->quoteIdentifier($matches[2]) . ')';
        }

        // Alias.field AS thing
        if (preg_match('/^([\w-]+(\.[\w\s-]+|\(.*\))*)\s+AS\s*([\w-]+)$/ui', $identifier, $matches)) {
            return $this->quoteIdentifier($matches[1]) . ' AS ' . $this->quoteIdentifier($matches[3]);
        }

        // string.string with spaces
        if (preg_match('/^([\w-]+\.[\w][\w\s\-]*[\w])(.*)/u', $identifier, $matches)) {
            $items = explode('.', $matches[1]);
            $field = implode($this->_endQuote . '.' . $this->_startQuote, $items);

            return $this->_startQuote . $field . $this->_endQuote . $matches[2];
        }

        if (preg_match('/^[\w_\s-]*[\w_-]+/u', $identifier)) {
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
     * @return \Closure
     */
    public function queryTranslator(string $type): Closure
    {
        return function ($query) use ($type) {
            if ($this->isAutoQuotingEnabled()) {
                $query = (new IdentifierQuoter($this))->quote($query);
            }

            /** @var \Cake\ORM\Query $query */
            $query = $this->{'_' . $type . 'QueryTranslator'}($query);
            $translators = $this->_expressionTranslators();
            if (!$translators) {
                return $query;
            }

            $query->traverseExpressions(function ($expression) use ($translators, $query): void {
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
    protected function _expressionTranslators(): array
    {
        return [];
    }

    /**
     * Apply translation steps to select queries.
     *
     * @param \Cake\Database\Query $query The query to translate
     * @return \Cake\Database\Query The modified query
     */
    protected function _selectQueryTranslator(Query $query): Query
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
    protected function _transformDistinct(Query $query): Query
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
    protected function _deleteQueryTranslator(Query $query): Query
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
    protected function _updateQueryTranslator(Query $query): Query
    {
        return $this->_removeAliasesFromConditions($query);
    }

    /**
     * Removes aliases from the `WHERE` clause of a query.
     *
     * @param \Cake\Database\Query $query The query to process.
     * @return \Cake\Database\Query The modified query.
     * @throws \RuntimeException In case the processed query contains any joins, as removing
     *  aliases from the conditions can break references to the joined tables.
     */
    protected function _removeAliasesFromConditions(Query $query): Query
    {
        if ($query->clause('join')) {
            throw new RuntimeException(
                'Aliases are being removed from conditions for UPDATE/DELETE queries, ' .
                'this can break references to joined tables.'
            );
        }

        $conditions = $query->clause('where');
        if ($conditions) {
            $conditions->traverse(function ($expression) {
                if ($expression instanceof Comparison) {
                    $field = $expression->getField();
                    if (
                        is_string($field) &&
                        strpos($field, '.') !== false
                    ) {
                        [, $unaliasedField] = explode('.', $field, 2);
                        $expression->setField($unaliasedField);
                    }

                    return $expression;
                }

                if ($expression instanceof IdentifierExpression) {
                    $identifier = $expression->getIdentifier();
                    if (strpos($identifier, '.') !== false) {
                        [, $unaliasedIdentifier] = explode('.', $identifier, 2);
                        $expression->setIdentifier($unaliasedIdentifier);
                    }

                    return $expression;
                }

                return $expression;
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
    protected function _insertQueryTranslator(Query $query): Query
    {
        return $query;
    }

    /**
     * Returns a SQL snippet for creating a new transaction savepoint
     *
     * @param string|int $name save point name
     * @return string
     */
    public function savePointSQL($name): string
    {
        return 'SAVEPOINT LEVEL' . $name;
    }

    /**
     * Returns a SQL snippet for releasing a previously created save point
     *
     * @param string|int $name save point name
     * @return string
     */
    public function releaseSavePointSQL($name): string
    {
        return 'RELEASE SAVEPOINT LEVEL' . $name;
    }

    /**
     * Returns a SQL snippet for rollbacking a previously created save point
     *
     * @param string|int $name save point name
     * @return string
     */
    public function rollbackSavePointSQL($name): string
    {
        return 'ROLLBACK TO SAVEPOINT LEVEL' . $name;
    }
}
