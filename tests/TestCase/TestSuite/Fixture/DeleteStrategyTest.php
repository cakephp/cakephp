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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite\Fixture;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\DeleteStrategy;
use Cake\TestSuite\TestCase;

class DeleteStrategyTest extends TestCase
{
    /**
     * The tables the strategy is exercised against are declared as fixtures so that
     * the default truncate strategy resets their identity counters once the test is
     * done. Deleting rows does not reset them, and the records of these fixtures
     * have no explicit ids.
     *
     * @var array<string>
     */
    protected array $fixtures = ['core.Articles', 'core.Products', 'core.Orders'];

    /**
     * Tests delete strategy.
     */
    public function testStrategy(): void
    {
        $connection = ConnectionManager::get('test');
        assert($connection instanceof Connection);
        $this->emptyTables($connection);

        $strategy = new DeleteStrategy();
        $strategy->setupTest(['core.Articles']);
        $this->assertNotEmpty($this->readTable($connection, 'articles'));

        $strategy->teardownTest();
        $this->assertEmpty($this->readTable($connection, 'articles'));
    }

    /**
     * Tests that fixtures referencing each other can be emptied.
     */
    public function testStrategyWithConstraints(): void
    {
        $connection = ConnectionManager::get('test');
        assert($connection instanceof Connection);
        $this->emptyTables($connection);

        // Orders has a foreign key to products, so it has to be emptied first.
        $strategy = new DeleteStrategy();
        $strategy->setupTest(['core.Orders', 'core.Products']);
        foreach (['products', 'orders'] as $table) {
            $this->assertNotEmpty($this->readTable($connection, $table), "Table `{$table}` has no rows.");
        }

        $strategy->teardownTest();
        foreach (['products', 'orders'] as $table) {
            $this->assertEmpty($this->readTable($connection, $table), "Table `{$table}` was not emptied.");
        }
    }

    /**
     * Tests that a test without fixtures is a no-op.
     */
    public function testStrategyWithoutFixtures(): void
    {
        $this->expectNotToPerformAssertions();

        $strategy = new DeleteStrategy();
        $strategy->setupTest([]);
        $strategy->teardownTest();
    }

    /**
     * Deleting rows does not reset identity counters, so fixtures which omit their
     * primary key get fresh ids on every cycle. This is the documented trade off of
     * the strategy, and the reason `TruncateStrategy` stays the default.
     */
    public function testStrategyDoesNotResetIdentityCounters(): void
    {
        $connection = ConnectionManager::get('test');
        assert($connection instanceof Connection);
        $this->emptyTables($connection);

        // The articles fixture records have no explicit ids.
        $strategy = new DeleteStrategy();
        $strategy->setupTest(['core.Articles']);
        $firstIds = $this->readIds($connection, 'articles');
        $strategy->teardownTest();

        $strategy->setupTest(['core.Articles']);
        $secondIds = $this->readIds($connection, 'articles');

        $this->assertCount(3, $firstIds);
        $this->assertCount(3, $secondIds);
        $this->assertGreaterThan(
            max($firstIds),
            min($secondIds),
            'The identity counter is expected to keep counting across setup cycles.',
        );

        // A row inserted without an id during the test continues from there rather
        // than colliding with the fixture records.
        $connection->insertQuery()
            ->insert(['author_id', 'title', 'body', 'published'])
            ->into('articles')
            ->values(['author_id' => 1, 'title' => 'Fourth', 'body' => 'Body', 'published' => 'Y'])
            ->execute()
            ->closeCursor();

        $ids = $this->readIds($connection, 'articles');
        $this->assertCount(4, $ids);
        $this->assertGreaterThan(max($secondIds), max($ids));

        $strategy->teardownTest();
        $this->assertEmpty($this->readTable($connection, 'articles'));
    }

    /**
     * Fixtures whose records carry explicit ids are unaffected, and come back
     * unchanged on every cycle.
     */
    public function testStrategyKeepsExplicitIdsStable(): void
    {
        $connection = ConnectionManager::get('test');
        assert($connection instanceof Connection);
        $this->emptyTables($connection);

        $strategy = new DeleteStrategy();
        $strategy->setupTest(['core.Products']);
        $firstIds = $this->readIds($connection, 'products');
        $strategy->teardownTest();

        $strategy->setupTest(['core.Products']);
        $secondIds = $this->readIds($connection, 'products');
        $strategy->teardownTest();

        $this->assertSame([1, 2, 3], $firstIds);
        $this->assertSame($firstIds, $secondIds);
    }

    /**
     * @param \Cake\Database\Connection $connection Test connection
     * @param string $table Table name
     * @return array<int>
     */
    protected function readIds(Connection $connection, string $table): array
    {
        $ids = array_map(intval(...), array_column($this->readTable($connection, $table), 'id'));
        sort($ids);

        return $ids;
    }

    /**
     * Removes the rows inserted by this test case's fixtures, children first.
     *
     * @param \Cake\Database\Connection $connection Test connection
     * @return void
     */
    protected function emptyTables(Connection $connection): void
    {
        foreach (['orders', 'products', 'articles'] as $table) {
            $connection->deleteQuery()->delete($table)->execute()->closeCursor();
            $this->assertEmpty($this->readTable($connection, $table));
        }
    }

    /**
     * @param \Cake\Database\Connection $connection Test connection
     * @param string $table Table name
     * @return array
     */
    protected function readTable(Connection $connection, string $table): array
    {
        $statement = $connection->selectQuery()->select('*')->from($table)->execute();
        $rows = $statement->fetchAll('assoc');
        $statement->closeCursor();

        return $rows;
    }
}
