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

use Cake\Datasource\RepositoryInterface;
use Cake\ORM\Table;

/**
 * Trait with common methods used by all ORM query classes.
 */
trait CommonQueryTrait
{
    /**
     * Instance of a repository/table object this query is bound to.
     *
     * @var \Cake\ORM\Table
     */
    protected Table $_repository;

    /**
     * Hints this object to associate the correct types when casting conditions
     * for the database. This is done by extracting the field types from the schema
     * associated to the passed table object. This prevents the user from repeating
     * themselves when specifying conditions.
     *
     * This method returns the same query object for chaining.
     *
     * @param \Cake\ORM\Table $table The table to pull types from
     * @return $this
     */
    public function addDefaultTypes(Table $table)
    {
        $alias = $table->getAlias();
        $map = $table->getSchema()->typeMap();
        $fields = [];
        foreach ($map as $f => $type) {
            $fields[$f] = $fields[$alias . '.' . $f] = $fields[$alias . '__' . $f] = $type;
        }
        $this->getTypeMap()->addDefaults($fields);

        return $this;
    }

    /**
     * Set the default Table object that will be used by this query
     * and form the `FROM` clause.
     *
     * @param \Cake\Datasource\RepositoryInterface $repository The default table object to use
     * @return $this
     */
    public function setRepository(RepositoryInterface $repository)
    {
        assert(
            $repository instanceof Table,
            '`$repository` must be an instance of `' . Table::class . '`.',
        );

        $this->_repository = $repository;

        return $this;
    }

    /**
     * Returns the default repository object that will be used by this query,
     * that is, the table that will appear in the "from" clause.
     *
     * @return \Cake\ORM\Table
     */
    public function getRepository(): Table
    {
        return $this->_repository;
    }
}
