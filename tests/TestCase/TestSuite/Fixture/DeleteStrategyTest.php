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
        $rows = $statement->fetchAll();
        $statement->closeCursor();

        return $rows;
    }
}
