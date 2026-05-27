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

use Cake\ORM\Table;

/**
 * Factory class for generating instances of Select, Insert, Update, Delete queries.
 */
class QueryFactory
{
    /**
     * Create a new Query instance.
     *
     * @param \Cake\ORM\Table $table The table this query is starting on.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array>
     */
    public function select(Table $table): SelectQuery
    {
        return new SelectQuery($table);
    }

    /**
     * Create a new non-hydrating UnhydratedSelectQuery instance.
     *
     * This is an independent construction seam, like select()/insert()/etc.
     * Applications that override select() to return a custom SelectQuery
     * subclass and want the same custom behavior on the non-hydrating path
     * should override this method too (returning their own
     * UnhydratedSelectQuery subclass).
     *
     * @param \Cake\ORM\Table $table The table this query is starting on.
     * @return \Cake\ORM\Query\UnhydratedSelectQuery
     * @since 5.4.0
     */
    public function unhydratedSelect(Table $table): UnhydratedSelectQuery
    {
        return new UnhydratedSelectQuery($table);
    }

    /**
     * Create a new InsertQuery instance.
     *
     * @param \Cake\ORM\Table $table The table this query is starting on.
     * @return \Cake\ORM\Query\InsertQuery
     */
    public function insert(Table $table): InsertQuery
    {
        return new InsertQuery($table);
    }

    /**
     * Create a new UpdateQuery instance.
     *
     * @param \Cake\ORM\Table $table The table this query is starting on.
     * @return \Cake\ORM\Query\UpdateQuery
     */
    public function update(Table $table): UpdateQuery
    {
        return new UpdateQuery($table);
    }

    /**
     * Create a new DeleteQuery instance.
     *
     * @param \Cake\ORM\Table $table The table this query is starting on.
     * @return \Cake\ORM\Query\DeleteQuery
     */
    public function delete(Table $table): DeleteQuery
    {
        return new DeleteQuery($table);
    }
}
