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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Query;

use Cake\Database\Connection;
use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * Factory class for generating instances of Select, Insert, Update, Delete queries.
 */
class QueryFactory
{
    /**
     * Constructor
     *
     * @param \Cake\Database\Connection $connection Connection instance.
     * @param \Cake\ORM\Table $table The table the query instanced created will be starting on.
     */
    public function __construct(
        protected Connection $connection,
        protected Table $table
    ) {
    }

    /**
     * Create a new Query instance.
     *
     * @return \Cake\ORM\Query
     */
    public function select(): Query
    {
        return new Query($this->connection, $this->table);
    }

    /**
     * Create a new InsertQuery instance.
     *
     * @return \Cake\ORM\Query\InsertQuery
     */
    public function insert(): InsertQuery
    {
        return new InsertQuery($this->connection, $this->table);
    }

    /**
     * Create a new UpdateQuery instance.
     *
     * @return \Cake\ORM\Query\UpdateQuery
     */
    public function update(): UpdateQuery
    {
        return new UpdateQuery($this->connection, $this->table);
    }

    /**
     * Create a new DeleteQuery instance.
     *
     * @return \Cake\ORM\Query\DeleteQuery
     */
    public function delete(): DeleteQuery
    {
        return new DeleteQuery($this->connection, $this->table);
    }
}
