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

use Cake\Database\Query;

/**
 * Delete Query forward compatibility shim.
 */
class DeleteQuery extends Query
{
    /**
     * Type of this query (select, insert, update, delete).
     *
     * @var string
     */
    protected $_type = 'delete';

    /**
     * @inheritDoc
     */
    public function select($fields = [], bool $overwrite = false)
    {
        $this->_deprecatedMethod('select()', 'Create your query with selectQuery() instead.');

        return parent::select($fields, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function distinct($on = [], $overwrite = false)
    {
        $this->_deprecatedMethod('distint()');

        return parent::distinct($on, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function modifier($modifiers, $overwrite = false)
    {
        $this->_deprecatedMethod('modifier()');

        return parent::modifier($modifiers, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function order($fields, $overwrite = false)
    {
        $this->_deprecatedMethod('order()');

        return parent::order($fields, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function orderAsc($field, $overwrite = false)
    {
        $this->_deprecatedMethod('orderAsc()');

        return parent::orderAsc($field, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function orderDesc($field, $overwrite = false)
    {
        $this->_deprecatedMethod('orderDesc()');

        return parent::orderDesc($field, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function group($fields, $overwrite = false)
    {
        $this->_deprecatedMethod('group()');

        return parent::group($fields, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function having($conditions = null, $types = [], $overwrite = false)
    {
        $this->_deprecatedMethod('having()');

        return parent::having($conditions, $types, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function andHaving($conditions, $types = [])
    {
        $this->_deprecatedMethod('andHaving()');

        return parent::andHaving($conditions, $types);
    }

    /**
     * @inheritDoc
     */
    public function page(int $num, ?int $limit = null)
    {
        $this->_deprecatedMethod('page()');

        return parent::page($num, $limit);
    }

    /**
     * @inheritDoc
     */
    public function limit($limit)
    {
        $this->_deprecatedMethod('limit()');

        return parent::limit($limit);
    }

    /**
     * @inheritDoc
     */
    public function offset($offset)
    {
        $this->_deprecatedMethod('offset()');

        return parent::offset($offset);
    }

    /**
     * @inheritDoc
     */
    public function union($query, $overwrite = false)
    {
        $this->_deprecatedMethod('union()');

        return parent::union($query, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function unionAll($query, $overwrite = false)
    {
        $this->_deprecatedMethod('unionAll()');

        return parent::unionAll($query, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function insert(array $columns, array $types = [])
    {
        $this->_deprecatedMethod('insert()', 'Create your query with insertQuery() instead.');

        return parent::insert($columns, $types);
    }

    /**
     * @inheritDoc
     */
    public function into(string $table)
    {
        $this->_deprecatedMethod('into()');

        return parent::into($table);
    }

    /**
     * @inheritDoc
     */
    public function values($data)
    {
        $this->_deprecatedMethod('values()');

        return parent::values($data);
    }

    /**
     * @inheritDoc
     */
    public function update($table)
    {
        $this->_deprecatedMethod('update()', 'Create your query with updateQuery() instead.');

        return parent::update($table);
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value = null, $types = [])
    {
        $this->_deprecatedMethod('set()');

        return parent::set($key, $value, $types);
    }
}
