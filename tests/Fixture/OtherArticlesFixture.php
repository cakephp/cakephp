<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\FixtureInterface;

/**
 * A fixture attached to the non-default connection
 * that implements the interface with none of the safe-guards
 * from TestFixture.
 */
class OtherArticlesFixture implements FixtureInterface
{
    public $table = 'other_articles';

    public function create(ConnectionInterface $connection): bool
    {
        return true;
    }

    public function drop(ConnectionInterface $connection): bool
    {
        return true;
    }

    public function insert(ConnectionInterface $connection): void
    {
    }

    public function createConstraints(ConnectionInterface $connection): bool
    {
        return true;
    }

    public function dropConstraints(ConnectionInterface $connection): bool
    {
        return true;
    }

    public function truncate(ConnectionInterface $connection): bool
    {
        return true;
    }

    public function connection(): string
    {
        return 'other';
    }

    public function sourceName(): string
    {
        return 'other_articles';
    }
}
