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
namespace Cake\Test\TestCase\ORM\QueryTests;

use Cake\ORM\Query\DeleteQuery;
use Cake\ORM\Query\InsertQuery;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Query\UpdateQuery;
use Cake\ORM\Table;
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
            [fn (Table $table) => new DeleteQuery($table->getConnection(), $table), 'delete'],
            [fn (Table $table) => new InsertQuery($table->getConnection(), $table), 'insert'],
            [fn (Table $table) => new UpdateQuery($table->getConnection(), $table), 'update'],
            [fn (Table $table) => new SelectQuery($table->getConnection(), $table), 'select'],
        ];
    }

    /**
     * @dataProvider queryProvider
     */
    public function testAsInsert($queryFactory)
    {
        $table = $this->fetchTable('Articles');
        $query = $queryFactory($table);
        $scenario = function () use ($query, $table) {
            $statement = $query
                ->insert(['author_id', 'title', 'body', 'published'])
                ->into($table->getTable())
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
        $table = $this->fetchTable('Articles');
        $query = $queryFactory($table);
        $scenario = function () use ($query, $table) {
            $statement = $query
                ->update($table->getTable())
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
        $table = $this->fetchTable('Articles');
        $query = $queryFactory($table);
        $this->deprecated(function () use ($query, $table) {
            $statement = $query
                ->delete($table->getTable())
                ->where(['title' => 'First Article'])
                ->execute();
            $this->assertEquals(1, $statement->rowCount());
            $statement->closeCursor();
        });
    }

    /**
     * @dataProvider queryProvider
     */
    public function testAsSelect($queryFactory)
    {
        $table = $this->fetchTable('Articles');
        $query = $queryFactory($table);
        $scenario = function () use ($query, $table) {
            $statement = $query
                ->select(['title', 'body'])
                ->from(['Articles' => $table->getTable()])
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

    /**
     * @dataProvider queryProvider
     */
    public function testAsSelectWithContain($queryFactory)
    {
        $table = $this->fetchTable('Articles');
        $table->belongsTo('Authors');

        $query = $queryFactory($table);
        $scenario = function () use ($query) {
            $results = $query
                ->select()
                ->where(['title' => 'First Article'])
                ->contain('Authors')
                ->all();
            $this->assertCount(1, $results);
            $this->assertNotEmpty($results->first()->author);
        };
        if ($query instanceof SelectQuery) {
            return $scenario();
        }
        $this->deprecated($scenario);
    }

    /**
     * @dataProvider queryProvider
     */
    public function testAsSelectWithMatching($queryFactory)
    {
        $table = $this->fetchTable('Articles');
        $table->belongsTo('Authors');

        $query = $queryFactory($table);
        $scenario = function () use ($query) {
            $results = $query
                ->select()
                ->matching('Authors', function ($q) {
                    return $q->where(['Authors.id' => 1]);
                })
                ->all();
            $this->assertCount(2, $results);
            $this->assertNotEmpty($results->first()->_matchingData);
        };
        if ($query instanceof SelectQuery) {
            return $scenario();
        }
        $this->deprecated($scenario);
    }
}
