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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Datasource\ConnectionInterface;

/**
 * Defines the interface for a fixture that needs to manage constraints.
 *
 * If an implementation of `Cake\Datasource\FixtureInterface` also implements
 * this interface, the FixtureManager will use these methods to manage
 * a fixtures constraints.
 */
interface ConstraintsInterface
{
    /**
     * Build and execute SQL queries necessary to create the constraints for the
     * fixture
     *
     * @param \Cake\Datasource\ConnectionInterface $connection An instance of the database
     *  into which the constraints will be created.
     * @return bool on success or if there are no constraints to create, or false on failure
     */
    public function createConstraints(ConnectionInterface $connection): bool;

    /**
     * Build and execute SQL queries necessary to drop the constraints for the
     * fixture
     *
     * @param \Cake\Datasource\ConnectionInterface $connection An instance of the database
     *  into which the constraints will be dropped.
     * @return bool on success or if there are no constraints to drop, or false on failure
     */
    public function dropConstraints(ConnectionInterface $connection): bool;
}
