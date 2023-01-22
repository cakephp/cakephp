<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Connection;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Query;
use Cake\Database\QueryCompiler;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests Query class
 */
class QueryCompilerTest extends TestCase
{
    use QueryAssertsTrait;

    protected array $fixtures = [
        'core.Articles',
    ];

    protected Connection|ConnectionInterface $connection;

    protected QueryCompiler $compiler;

    protected ValueBinder $binder;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->compiler = $this->connection->getDriver()->newCompiler();
        $this->binder = new ValueBinder();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->compiler);
        unset($this->binder);
    }

    protected function newQuery(string $type): Query
    {
        return match ($type) {
            Query::TYPE_SELECT => new Query\SelectQuery($this->connection),
            Query::TYPE_INSERT => new Query\InsertQuery($this->connection),
            Query::TYPE_UPDATE => new Query\UpdateQuery($this->connection),
            Query::TYPE_DELETE => new Query\DeleteQuery($this->connection),
        };
    }

    public function testSelectFrom(): void
    {
        /** @var \Cake\Database\Query\SelectQuery $query */
        $query = $this->newQuery(Query::TYPE_SELECT);
        $query = $query->select('*')
            ->from('articles');
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('SELECT * FROM articles', $result);

        $result = $query->all();
        $this->assertCount(3, $result);
    }

    public function testSelectWhere(): void
    {
        /** @var \Cake\Database\Query\SelectQuery $query */
        $query = $this->newQuery(Query::TYPE_SELECT);
        $query = $query->select('*')
            ->from('articles')
            ->where(['author_id' => 1]);
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('SELECT * FROM articles WHERE author_id = :c0', $result);

        $result = $query->all();
        $this->assertCount(2, $result);
    }

    public function testSelectWithComment(): void
    {
        /** @var \Cake\Database\Query\SelectQuery $query */
        $query = $this->newQuery(Query::TYPE_SELECT);
        $query = $query->select('*')
            ->from('articles')
            ->comment('This is a test');
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('/* This is a test */ SELECT * FROM articles', $result);

        $result = $query->all();
        $this->assertCount(3, $result);
    }

    public function testInsert(): void
    {
        /** @var \Cake\Database\Query\InsertQuery $query */
        $query = $this->newQuery(Query::TYPE_INSERT);
        $query = $query->insert(['title'])
            ->into('articles')
            ->values(['title' => 'A new article']);
        $result = $this->compiler->compile($query, $this->binder);

        if ($this->connection->getDriver() instanceof Sqlserver) {
            $this->assertSame('INSERT INTO articles (title) OUTPUT INSERTED.* VALUES (:c0)', $result);
        } else {
            $this->assertSame('INSERT INTO articles (title) VALUES (:c0)', $result);
        }

        $result = $query->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $result->closeCursor();
    }

    public function testInsertWithComment(): void
    {
        /** @var \Cake\Database\Query\InsertQuery $query */
        $query = $this->newQuery(Query::TYPE_INSERT);
        $query = $query->insert(['title'])
            ->into('articles')
            ->values(['title' => 'A new article'])
            ->comment('This is a test');
        $result = $this->compiler->compile($query, $this->binder);

        if ($this->connection->getDriver() instanceof Sqlserver) {
            $this->assertSame('/* This is a test */ INSERT INTO articles (title) OUTPUT INSERTED.* VALUES (:c0)', $result);
        } else {
            $this->assertSame('/* This is a test */ INSERT INTO articles (title) VALUES (:c0)', $result);
        }

        $result = $query->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $result->closeCursor();
    }

    public function testUpdate(): void
    {
        /** @var \Cake\Database\Query\UpdateQuery $query */
        $query = $this->newQuery(Query::TYPE_UPDATE);
        $query = $query->update('articles')
            ->set('title', 'mark')
            ->where(['id' => 1]);
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('UPDATE articles SET title = :c0 WHERE id = :c1', $result);

        $result = $query->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $result->closeCursor();
    }

    public function testUpdateWithComment(): void
    {
        /** @var \Cake\Database\Query\UpdateQuery $query */
        $query = $this->newQuery(Query::TYPE_UPDATE);
        $query = $query->update('articles')
            ->set('title', 'mark')
            ->where(['id' => 1])
            ->comment('This is a test');
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('/* This is a test */ UPDATE articles SET title = :c0 WHERE id = :c1', $result);

        $result = $query->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $result->closeCursor();
    }

    public function testDelete(): void
    {
        /** @var \Cake\Database\Query\DeleteQuery $query */
        $query = $this->newQuery(Query::TYPE_DELETE);
        $query = $query->delete()
            ->from('articles')
            ->where(['id !=' => 1]);
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('DELETE FROM articles WHERE id != :c0', $result);

        $result = $query->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $result->closeCursor();
    }

    public function testDeleteWithComment(): void
    {
        /** @var \Cake\Database\Query\DeleteQuery $query */
        $query = $this->newQuery(Query::TYPE_DELETE);
        $query = $query->delete()
            ->from('articles')
            ->where(['id !=' => 1])
            ->comment('This is a test');
        $result = $this->compiler->compile($query, $this->binder);
        $this->assertSame('/* This is a test */ DELETE FROM articles WHERE id != :c0', $result);

        $result = $query->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $result->closeCursor();
    }
}
