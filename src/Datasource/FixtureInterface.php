<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

/**
 * Defines the interface that testing fixtures use.
 */
interface FixtureInterface
{
    /**
     * Create the fixture schema/mapping/definition
     *
     * @param \Cake\Datasource\ConnectionInterface $db An instance of the connection the fixture should be created on.
     * @return bool True on success, false on failure.
     */
    public function create(ConnectionInterface $db);

    /**
     * Run after all tests executed, should remove the table/collection from the connection.
     *
     * @param \Cake\Datasource\ConnectionInterface $db An instance of the connection the fixture should be removed from.
     * @return bool True on success, false on failure.
     */
    public function drop(ConnectionInterface $db);

    /**
     * Run before each test is executed.
     *
     * Should insert all the records into the test database.
     *
     * @param \Cake\Datasource\ConnectionInterface $db An instance of the connection into which the records will be inserted.
     * @return bool on success or if there are no records to insert, or false on failure.
     */
    public function insert(ConnectionInterface $db);

    /**
     * Build and execute SQL queries necessary to create the constraints for the
     * fixture
     *
     * @param \Cake\Datasource\ConnectionInterface $db An instance of the database into which the constraints will be created
     * @return bool on success or if there are no constraints to create, or false on failure
     */
    public function createConstraints(ConnectionInterface $db);

    /**
     * Build and execute SQL queries necessary to drop the constraints for the
     * fixture
     *
     * @param \Cake\Datasource\ConnectionInterface $db An instance of the database into which the constraints will be dropped
     * @return bool on success or if there are no constraints to drop, or false on failure
     */
    public function dropConstraints(ConnectionInterface $db);

    /**
     * Truncates the current fixture.
     *
     * @param \Cake\Datasource\ConnectionInterface $db A reference to a db instance
     * @return bool
     */
    public function truncate(ConnectionInterface $db);

    /**
     * Get the connection name this fixture should be inserted into.
     *
     * @return string
     */
    public function connection();

    /**
     * Get the table/collection name for this fixture.
     *
     * @return string
     */
    public function sourceName();
}
