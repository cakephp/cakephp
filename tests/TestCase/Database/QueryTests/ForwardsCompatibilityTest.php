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
namespace Cake\Test\TestCase\Database\QueryTests;

use Cake\Database\Connection;
use Cake\Database\Query\DeleteQuery;
use Cake\Database\Query\InsertQuery;
use Cake\Database\Query\SelectQuery;
use Cake\Database\Query\UpdateQuery;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

class ForwardsCompatibilityTest extends TestCase
{
    protected $fixtures = [
        'core.Articles',
        'core.Authors',
    ];

    public static function queryProvider()
    {
        return [
            [fn (Connection $connection) => new DeleteQuery($connection), 'delete'],
            [fn (Connection $connection) => new InsertQuery($connection), 'insert'],
            [fn (Connection $connection) => new SelectQuery($connection), 'select'],
            [fn (Connection $connection) => new UpdateQuery($connection), 'update'],
        ];
    }

    /**
     * @dataProvider queryProvider
     */
    public function testAsInsert($queryFactory)
    {
        $query = $queryFactory(ConnectionManager::get('test'));
        $scenario = function () use ($query) {
            $statement = $query
                ->insert(['author_id', 'title', 'body', 'published'])
                ->into('articles')
                ->values([1, 'custom article', 'so long'])
                ->execute();
            $this->assertEquals(1, $statement->rowCount());
            $statement->closeCursor();
        };
        if ($query instanceof InsertQuery) {
            return $scenario();
        }
        $this->deprecated($scenario);
    }

    /**
     * @dataProvider queryProvider
     */
    public function testAsUpdate($queryFactory)
    {
        $query = $queryFactory(ConnectionManager::get('test'));
        $scenario = function () use ($query) {
            $statement = $query
                ->update('articles')
                ->set(['title' => 'Updated'])
                ->where(['title' => 'First Article'])
                ->execute();
            $this->assertEquals(1, $statement->rowCount());
            $statement->closeCursor();
        };

        if ($query instanceof UpdateQuery) {
            return $scenario();
        }
        $this->deprecated($scenario);
    }

    /**
     * @dataProvider queryProvider
     */
    public function testAsDelete($queryFactory)
    {
        $query = $queryFactory(ConnectionManager::get('test'));
        $scenario = function () use ($query) {
            $statement = $query
                ->delete('articles')
                ->where(['title' => 'First Article'])
                ->epilog('')
                ->execute();
            $this->assertEquals(1, $statement->rowCount());
            $statement->closeCursor();
        };
        if ($query instanceof DeleteQuery) {
            return $scenario();
        }
        $this->deprecated($scenario);
    }

    /**
     * @dataProvider queryProvider
     */
    public function testAsSelect($queryFactory)
    {
        $query = $queryFactory(ConnectionManager::get('test'));
        $scenario = function () use ($query) {
            $statement = $query
                ->select(['title', 'body'])
                ->from(['Articles' => 'articles'])
                ->where(['title' => 'First Article'])
                ->execute();
            $this->assertEquals(1, $statement->rowCount());
            $statement->closeCursor();
        };
        if ($query instanceof SelectQuery) {
            return $scenario();
        }
        $this->deprecated($scenario);
    }
}
