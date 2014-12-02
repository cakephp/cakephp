<?php
/**
 * Created by PhpStorm.
 * User: havok
 * Date: 23/11/14
 * Time: 09:26
 */

namespace Cake\Database;

use Cake\Database\Expression\Comparison;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TableNameExpression;

/**
 * Contains all the logic related to prefixing table names in a Query object
 *
 * @internal
 *
 */
class TableNamePrefixer {

    /**
     * The ValueBinder instance used in the current query
     *
     * @var \Cake\Database\ValueBinder
     */
    protected $_binder;

    /**
     * The driver instance used to do the table names prefixing
     *
     * @var \Cake\Database\Driver
     */
    protected $_driver;

    /**
     * List of the query parts to prefix
     *
     * @var array
     */
    protected $_partsToPrefix = ['select', 'from', 'join', 'where', 'group', 'having', 'order', 'update', 'insert'];

    /**
     * Constructor
     *
     * @param \Cake\Database\Driver $driver The driver instance used to do the identifier quoting
     */
    public function __construct(Driver $driver) {
        $this->_driver = $driver;
    }

    /**
     * Iterates over each of the clauses in a query looking for table names and
     * prefix them
     *
     * @param \Cake\Database\Query $query The query to have its table names prefixed quoted
     * @return \Cake\Database\Query
     */
    public function prefix(Query $query) {
        $this->_binder = $query->valueBinder();
        $query->valueBinder(false);

        $this->_prefixParts($query);

        $query->valueBinder($this->_binder);
        return $query;
    }

    /**
     * Quotes all identifiers in each of the clauses of a query
     *
     * @param \Cake\Database\Query $query The query to quote.
     * @return void
     */
    protected function _prefixParts(Query $query) {
        foreach ($this->_partsToPrefix as $part) {
            $contents = $query->clause($part);

            if (empty($contents)) {
                continue;
            }

            $methodName = '_prefix' . ucfirst($part) . 'Parts';
            if (method_exists($this, $methodName)) {
                $this->{$methodName}($query, $contents);
            }
        }
    }

    /**
     * Prefixes the table name in the "update" clause
     *
     * @param \Cake\Database\Query $query Query instance
     * @param array $parts the parts of the query to prefix
     * @return array
     */
    protected function _prefixInsertParts($query, $parts) {
        $parts = $query->connection()->fullTableName($parts);
        $query->into($parts[0]);
        return;
    }

    /**
     * Prefixes the table name in the "update" clause
     *
     * @param \Cake\Database\Query $query Query instance
     * @param array $parts the parts of the query to prefix
     * @return array
     */
    protected function _prefixUpdateParts($query, $parts) {
        $parts = $query->connection()->fullTableName($parts);
        $query->update($parts[0]);
        return;
    }

    /**
     * Prefixes the table name in clause of the Query having a basic forms
     *
     * @param \Cake\Database\Query $query Query instance
     * @param array $parts the parts of the query to prefix
     * @return array
     */
    protected function _prefixFromParts($query, $parts) {
        $parts = $query->connection()->fullTableName($parts);
        $query->from($parts, true);
        return;
    }

    /**
     * Prefixes the table names for the "select" clause
     *
     * @param \Cake\Database\Query $query Query instance
     * @param array $parts The parts of the query to prefix
     *
     * @return void
     */
    protected function _prefixSelectParts($query, $parts) {
        if (!empty($parts)) {
            foreach ($parts as $alias => $part) {
                if ($query->hasTableName($part) === true) {
                    $parts[$alias] = $query->connection()->fullFieldName($part, $query->tablesNames);
                }
            }

            $query->select($parts, true);
        }

        return;
    }

    /**
     * Prefixes the table names for the "join" clause
     *
     * @param \Cake\Database\Query $query Query instance
     * @param array $parts The parts of the query to prefix
     *
     * @return void
     */
    protected function _prefixJoinParts($query, $parts) {
        if (!empty($parts)) {
            foreach ($parts as $alias => $join) {
                if ($join['conditions'] instanceof QueryExpression) {
                    $join['conditions']->iterateParts(function ($condition, $key) use ($query) {
                        if (is_string($condition)) {
                            $condition = $query->connection()->applyFullTableName($condition, $query->tablesNames);
                        }
                        return $condition;
                    });
                }

                $join['table'] = $query->connection()->fullTableName($join['table']);

                $parts[$alias] = $join;
            }

            $query->join($parts, [], true);
        }

        return;
    }

    /**
     * Prefixes the table names for the "where" clause
     *
     * @param \Cake\Database\Query $query Query instance
     * @param array $parts The parts of the query to prefix
     *
     * @return void
     */
    protected function _prefixWhereParts($query, $parts) {
        $parts->traverse(function ($condition) use ($query) {
            if ($condition instanceof ExpressionInterface) {
                switch (get_class($condition)) {
                    case 'Cake\Database\Expression\Comparison':
                    case 'Cake\Database\Expression\BetweenExpression':
                        $field = $condition->getField();

                        if (is_string($field) && strpos($field, '.') !== false && $query->hasTableName($field) === true) {
                            $field = $query->connection()->fullFieldName($field, $query->tablesNames);
                            $condition->field($field);
                        }

                        break;
                    case 'Cake\Database\Expression\UnaryExpression':
                        $value = $condition->getValue();

                        if (is_string($value) && strpos($value, '.') !== false && $query->hasTableName($value) === true) {
                            $value = $query->connection()->fullFieldName($value, $query->tablesNames);
                            $condition->value($value);
                        }
                        break;
                    case 'Cake\Database\Expression\IdentifierExpression':
                        $identifier = $condition->getIdentifier();

                        if (is_string($identifier) && strpos($identifier, '.') !== false && $query->hasTableName($identifier) === true) {
                            $identifier = $query->connection()->fullFieldName($identifier, $query->tablesNames);
                            $condition->setIdentifier($identifier);
                        }

                        break;
                    case 'Cake\Database\Expression\QueryExpression':
                        $condition->iterateParts(function ($queryExpCondition) use ($query) {
                            if (is_string($queryExpCondition)) {
                                $queryExpCondition = new TableNameExpression(
                                    $queryExpCondition,
                                    $query->connection()->getPrefix(),
                                    true,
                                    $query->tablesNames
                                );
                            }
                            return $queryExpCondition;
                        });
                        break;
                    default:
                        break;
                }
            }

            return $condition;
        });

        return;
    }

    /**
     * Prefixes the table names for the "join" clause
     *
     * @param \Cake\Database\Query $query Query instance
     * @param array $parts The parts of the query to prefix
     *
     * @return void
     */
    protected function _prefixGroupParts($query, $parts) {
        if (!empty($parts)) {
            foreach ($parts as $key => $part) {
                if ($query->hasTableName($part) === true) {
                    $parts[$key] = $query->connection()->fullFieldName($part, $query->tablesNames);
                }
            }
        }

        $query->group($parts, true);
        return;
    }

    /**
     * Prefixes the table names for the "having" clause
     *
     * @param \Cake\Database\Query $query Query instance
     * @param array $parts The parts of the query to prefix
     *
     * @return void
     */
    protected function _prefixHavingParts($query, $parts) {
        if ($parts instanceof ExpressionInterface) {
            $parts->traverse(function ($condition) use ($query) {
                if ($condition instanceof Comparison) {
                    $field = $condition->getField();

                    if (is_string($field) && strpos($field, '.') !== false && $query->hasTableName($field) === true) {
                        $field = $query->connection()->fullFieldName($field, $query->tablesNames);
                        $condition->field($field);
                    }
                } elseif ($condition instanceof QueryExpression) {
                    $condition->iterateParts(function ($condition) use ($query) {
                        if ($query->hasTableName($condition) === true) {
                            return $query->connection()->applyFullTableName($condition, $query->tablesNames);
                        }

                        return $condition;
                    });
                }

                return $condition;
            });
        }

        $query->having($parts, [], true);
        return;
    }

    /**
     * Prefixes the table names for the "order" clause
     *
     * @param \Cake\Database\Query $query Query instance
     * @param array $parts The parts of the query to prefix
     *
     * @return void
     */
    protected function _prefixOrderParts($query, $parts) {
        $binder = $this->_binder;
        if ($parts instanceof ExpressionInterface) {
            $parts->iterateParts(function ($condition, &$key) use ($parts, $query, $binder) {
                if ($query->hasTableName($key) === true && $query->connection()->isTableNamePrefixed($key) === false) {
                    $key = $query->connection()->fullFieldName($key, $query->tablesNames);

                    if ($key instanceof ExpressionInterface) {
                        $key = $key->sql($binder);
                    }
                }
                return $condition;
            });
        }

        return;
    }

}