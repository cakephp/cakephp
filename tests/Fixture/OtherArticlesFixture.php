<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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

    public function create(ConnectionInterface $db)
    {
    }

    public function drop(ConnectionInterface $db)
    {
    }

    public function insert(ConnectionInterface $db)
    {
    }

    public function createConstraints(ConnectionInterface $db)
    {
    }

    public function dropConstraints(ConnectionInterface $db)
    {
    }

    public function truncate(ConnectionInterface $db)
    {
    }

    public function connection()
    {
        return 'other';
    }

    public function sourceName()
    {
        return 'other_articles';
    }
}
