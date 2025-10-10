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
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Query;

use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Closure;

/**
 * This class is used to generate UPDATE queries for the relational database.
 */
class UpdateQuery extends Query
{
    /**
     * Type of this query.
     *
     * @var string
     */
    protected string $_type = self::TYPE_UPDATE;

    /**
     * List of SQL parts that will be used to build this query.
     *
     * @var array<string, mixed>
     */
    protected array $_parts = [
        'comment' => null,
        'with' => [],
        'update' => [],
        'optimizerHint' => [],
        'modifier' => [],
        'join' => [],
        'set' => [],
        'where' => null,
        'order' => null,
        'limit' => null,
        'epilog' => null,
    ];

    /**
     * Create an update query.
     *
     * Can be combined with set() and where() methods to create update queries.
     *
     * @param \Cake\Database\ExpressionInterface|string $table The table you want to update.
     * @return $this
     */
    public function update(ExpressionInterface|string $table)
    {
        $this->_dirty();
        $this->_parts['update'][0] = $table;

        return $this;
    }

    /**
     * Set one or many fields to update.
     *
     * ### Examples
     *
     * Passing a string:
     *
     * ```
     * $query->update('articles')->set('title', 'The Title');
     * ```
     *
     * Passing an array:
     *
     * ```
     * $query->update('articles')->set(['title' => 'The Title'], ['title' => 'string']);
     * ```
     *
     * Passing a callback:
     *
     * ```
     * $query->update('articles')->set(function (ExpressionInterface $exp) {
     *   return $exp->eq('title', 'The title', 'string');
     * });
     * ```
     *
     * @param \Cake\Database\Expression\QueryExpression|\Closure|array|string $key The column name or array of keys
     *    + values to set. This can also be a QueryExpression containing a SQL fragment.
     *    It can also be a Closure, that is required to return an expression object.
     * @param mixed $value The value to update $key to. Can be null if $key is an
     *    array or QueryExpression. When $key is an array, this parameter will be
     *    used as $types instead.
     * @param array<string, string>|string $types The column types to treat data as.
     * @return $this
     */
    public function set(QueryExpression|Closure|array|string $key, mixed $value = null, array|string $types = [])
    {
        if (empty($this->_parts['set'])) {
            $this->_parts['set'] = $this->expr()->setConjunction(',');
        }

        if ($key instanceof Closure) {
            $exp = $this->expr()->setConjunction(',');
            /** @var \Cake\Database\Expression\QueryExpression $setExpr */
            $setExpr = $this->_parts['set'];
            $setExpr->add($key($exp));

            return $this;
        }

        if (is_array($key) && !isset($key[0])) {
            $typeMap = $this->getTypeMap()->setTypes($value ?? []);
            /** @var \Cake\Database\Expression\QueryExpression $setExpr */
            $setExpr = $this->_parts['set'];
            foreach ($key as $k => $v) {
                $setExpr->add(new ComparisonExpression($k, $v, $typeMap->type($k)));
            }

            return $this;
        }

        if (is_array($key) || $key instanceof ExpressionInterface) {
            $types = (array)$value;
            /** @var \Cake\Database\Expression\QueryExpression $setExpr */
            $setExpr = $this->_parts['set'];
            $setExpr->add($key, $types);

            return $this;
        }

        if (!is_string($types)) {
            $types = null;
        }
        /** @var \Cake\Database\Expression\QueryExpression $setExpr */
        $setExpr = $this->_parts['set'];
        $setExpr->eq($key, $value, $types);

        return $this;
    }
}
