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
        $this->_deprecatedException('delete()', 'Remove this method call.');
    }

    /**
     * @inheritDoc
     */
    public function cache($key, $config = 'default')
    {
        $this->_deprecatedException('cache()', 'Use execute() instead.');
    }

    /**
     * @inheritDoc
     */
    public function all(): ResultSetInterface
    {
        $this->_deprecatedException('all()', 'Use execute() instead.');
    }

    /**
     * @inheritDoc
     */
    public function select($fields = [], bool $overwrite = false)
    {
        $this->_deprecatedException('select()');
    }

    /**
     * @inheritDoc
     */
    public function distinct($on = [], $overwrite = false)
    {
        $this->_deprecatedException('distinct()');
    }

    /**
     * @inheritDoc
     */
    public function modifier($modifiers, $overwrite = false)
    {
        $this->_deprecatedException('modifier()');
    }

    /**
     * @inheritDoc
     */
    public function join($tables, $types = [], $overwrite = false)
    {
        $this->_deprecatedException('join()');
    }

    /**
     * @inheritDoc
     */
    public function removeJoin(string $name)
    {
        $this->_deprecatedException('removeJoin()');
    }

    /**
     * @inheritDoc
     */
    public function leftJoin($table, $conditions = [], $types = [])
    {
        $this->_deprecatedException('leftJoin()');
    }

    /**
     * @inheritDoc
     */
    public function rightJoin($table, $conditions = [], $types = [])
    {
        $this->_deprecatedException('rightJoin()');
    }

    /**
     * @inheritDoc
     */
    public function innerJoin($table, $conditions = [], $types = [])
    {
        $this->_deprecatedException('innerJoin()');
    }

    /**
     * @inheritDoc
     */
    public function group($fields, $overwrite = false)
    {
        $this->_deprecatedException('group()');
    }

    /**
     * @inheritDoc
     */
    public function having($conditions = null, $types = [], $overwrite = false)
    {
        $this->_deprecatedException('having()');
    }

    /**
     * @inheritDoc
     */
    public function andHaving($conditions, $types = [])
    {
        $this->_deprecatedException('andHaving()');
    }

    /**
     * @inheritDoc
     */
    public function page(int $num, ?int $limit = null)
    {
        $this->_deprecatedException('page()');
    }

    /**
     * @inheritDoc
     */
    public function union($query, $overwrite = false)
    {
        $this->_deprecatedException('union()');
    }

    /**
     * @inheritDoc
     */
    public function unionAll($query, $overwrite = false)
    {
        $this->_deprecatedException('union()');
    }

    /**
     * @inheritDoc
     */
    public function insert(array $columns, array $types = [])
    {
        $this->_deprecatedException('insert()');
    }

    /**
     * @inheritDoc
     */
    public function into(string $table)
    {
        $this->_deprecatedException('into()', 'Use from() instead.');
    }

    /**
     * @inheritDoc
     */
    public function values($data)
    {
        $this->_deprecatedException('values()');
    }

    /**
     * @inheritDoc
     */
    public function update($table = null)
    {
        $this->_deprecatedException('update()');
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value = null, $types = [])
    {
        $this->_deprecatedException('set()');
    }
}
