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
use Cake\ORM\Query;

/**
 * Select Query forward compatibility shim.
 */
class SelectQuery extends Query
{
    /**
     * Type of this query (select, insert, update, delete).
     *
     * @var string
     */
    protected $_type = 'select';

    /**
     * @inheritDoc
     */
    public function delete(?string $table = null)
    {
        $this->_deprecatedMethod('delete()', 'Create your query with deleteQuery() instead.');

        return parent::delete($table);
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

    /**
     * Sets the connection role.
     *
     * @param string $role Connection role ('read' or 'write')
     * @return $this
     */
    public function setConnectionRole(string $role)
    {
        assert($role === Connection::ROLE_READ || $role === Connection::ROLE_WRITE);
        $this->connectionRole = $role;

        return $this;
    }

    /**
     * Sets the connection role to read.
     *
     * @return $this
     */
    public function useReadRole()
    {
        return $this->setConnectionRole(Connection::ROLE_READ);
    }

    /**
     * Sets the connection role to write.
     *
     * @return $this
     */
    public function useWriteRole()
    {
        return $this->setConnectionRole(Connection::ROLE_WRITE);
    }
}
