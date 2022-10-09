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
namespace Cake\ORM\Query;

use Cake\Database\Connection;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query;
use Cake\ORM\Table;

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
     * Constructor
     *
     * @param \Cake\Database\Connection $connection The connection object
     * @param \Cake\ORM\Table $table The table this query is starting on
     */
    public function __construct(Connection $connection, Table $table)
    {
        parent::__construct($connection, $table);
        $this->from([$table->getAlias() => $table->getTable()]);
    }

    /**
     * @inheritDoc
     */
    public function delete(?string $table = null)
    {
        $this->_deprecatedMethod('delete()', 'Remove this method call.');

        return parent::delete($table);
    }

    /**
     * @inheritDoc
     */
    public function cache($key, $config = 'default')
    {
        $this->_deprecatedMethod('cache()', 'Use execute() instead.');

        return parent::cache($key, $config);
    }

    /**
     * @inheritDoc
     */
    public function all(): ResultSetInterface
    {
        $this->_deprecatedMethod('all()', 'Use execute() instead.');

        return parent::all();
    }

    /**
     * @inheritDoc
     */
    public function select($fields = [], bool $overwrite = false)
    {
        $this->_deprecatedMethod('select()');

        return parent::select($fields, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function distinct($on = [], $overwrite = false)
    {
        $this->_deprecatedMethod('distinct()');

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
    public function join($tables, $types = [], $overwrite = false)
    {
        $this->_deprecatedMethod('join()');

        return parent::join($tables, $types, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function removeJoin(string $name)
    {
        $this->_deprecatedMethod('removeJoin()');

        return parent::removeJoin($name);
    }

    /**
     * @inheritDoc
     */
    public function leftJoin($table, $conditions = [], $types = [])
    {
        $this->_deprecatedMethod('leftJoin()');

        return parent::leftJoin($table, $conditions, $types);
    }

    /**
     * @inheritDoc
     */
    public function rightJoin($table, $conditions = [], $types = [])
    {
        $this->_deprecatedMethod('rightJoin()');

        return parent::rightJoin($table, $conditions, $types);
    }

    /**
     * @inheritDoc
     */
    public function innerJoin($table, $conditions = [], $types = [])
    {
        $this->_deprecatedMethod('innerJoin()');

        return parent::innerJoin($table, $conditions, $types);
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
        $this->_deprecatedMethod('union()');

        return parent::unionAll($query, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function insert(array $columns, array $types = [])
    {
        $this->_deprecatedMethod('insert()');

        return parent::insert($columns, $types);
    }

    /**
     * @inheritDoc
     */
    public function into(string $table)
    {
        $this->_deprecatedMethod('into()', 'Use from() instead.');

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
    public function update($table = null)
    {
        $this->_deprecatedMethod('update()');

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
