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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite\Fixture;

use Cake\Core\Exception\CakeException;
use Cake\Database\Connection;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Test\Fixture\ArticlesFixture;
use Cake\TestSuite\Fixture\FixtureHelper;
use Cake\TestSuite\Fixture\TestFixture;
use Cake\TestSuite\TestCase;
use Company\TestPluginThree\Test\Fixture\ArticlesFixture as CompanyArticlesFixture;
use PDOException;
use TestApp\Datasource\FakeConnection;
use TestApp\Test\Fixture\ArticlesFixture as AppArticlesFixture;
use TestPlugin\Test\Fixture\ArticlesFixture as PluginArticlesFixture;
use TestPlugin\Test\Fixture\Blog\CommentsFixture as PluginCommentsFixture;
use UnexpectedValueException;

class FixtureHelperTest extends TestCase
{
    /**
     * A table holding a foreign key to itself, created on demand by the delete tests.
     */
    public const SELF_REFERENCING_TABLE = 'fixture_selves';

    /**
     * The chain of tables created on demand by the delete tests, parents first.
     *
     * @var array<string>
     */
    protected array $nestedTables = [
        'fixture_grandparents',
        'fixture_parents',
        'fixture_children',
    ];

    /**
     * Whether those tables have to be dropped on teardown.
     */
    protected bool $nestedTablesCreated = false;

    /**
     * `Orders` and `Products` are only used by the delete tests below, but they are
     * declared here so that the default truncate strategy resets their identity
     * counters afterwards. Deleting rows does not reset them, and the records of the
     * orders fixture have no explicit ids.
     *
     * @var array<string>
     */
    protected array $fixtures = ['core.Articles', 'core.Products', 'core.Orders'];

    /**
     * Clean up after test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
        ConnectionManager::dropAlias('test1');
        ConnectionManager::dropAlias('test2');
        ConnectionManager::drop('fake');
        $this->dropNestedTables();
    }

    /**
     * Tests loading fixtures.
     */
    public function testLoadFixtures(): void
    {
        $this->setAppNamespace('TestApp');
        $this->loadPlugins(['TestPlugin']);
        $fixtures = (new FixtureHelper())->loadFixtures([
            'core.Articles',
            'plugin.TestPlugin.Articles',
            'plugin.TestPlugin.Blog/Comments',
            'plugin.Company/TestPluginThree.Articles',
            'app.Articles',
        ]);
        $this->assertNotEmpty($fixtures);
        $this->assertInstanceOf(ArticlesFixture::class, $fixtures[ArticlesFixture::class]);
        $this->assertInstanceOf(PluginArticlesFixture::class, $fixtures[PluginArticlesFixture::class]);
        $this->assertInstanceOf(PluginCommentsFixture::class, $fixtures[PluginCommentsFixture::class]);
        $this->assertInstanceOf(CompanyArticlesFixture::class, $fixtures[CompanyArticlesFixture::class]);
        $this->assertInstanceOf(AppArticlesFixture::class, $fixtures[AppArticlesFixture::class]);
    }

    /**
     * Tests that possible table instances used in the fixture loading mechanism
     * do not remain in the table locator.
     */
    public function testLoadFixturesDoesNotPolluteTheTableLocator(): void
    {
        (new FixtureHelper())->loadFixtures([
            'core.Articles',
            'plugin.TestPlugin.Blog/Comments',
        ]);

        $this->assertFalse($this->getTableLocator()->exists('Articles'));
        $this->assertFalse($this->getTableLocator()->exists('Comments'));
    }

    /**
     * Tests loading missing fixtures.
     */
    public function testLoadMissingFixtures(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Could not find fixture `core.ThisIsMissing`');
        (new FixtureHelper())->loadFixtures(['core.ThisIsMissing']);
    }

    /**
     * Tests loading duplicate fixtures.
     */
    public function testLoadDulicateFixtures(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Found duplicate fixture `core.Articles`');
        (new FixtureHelper())->loadFixtures(['core.Articles','core.Articles']);
    }

    /**
     * Tests running callback per connection
     */
    public function testPerConnection(): void
    {
        $fixture1 = new class extends TestFixture {
            public function connection(): string
            {
                return 'test1';
            }

            protected function _schemaFromReflection(): void
            {
            }
        };
        $fixture2 = new class extends TestFixture {
            public function connection(): string
            {
                return 'test2';
            }

            protected function _schemaFromReflection(): void
            {
            }
        };

        ConnectionManager::alias('test', 'test1');
        ConnectionManager::alias('test', 'test2');

        $numCalls = 0;
        (new FixtureHelper())->runPerConnection(function () use (&$numCalls): void {
            ++$numCalls;
        }, [$fixture1, $fixture2]);
        $this->assertSame(2, $numCalls);
    }

    /**
     * Tests inserting fixtures.
     */
    public function testInsertFixtures(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        $connection->deleteQuery()->delete('articles')->execute()->closeCursor();
        $rows = $connection->selectQuery()->select('*')->from('articles')->execute();
        $this->assertEmpty($rows->fetchAll());
        $rows->closeCursor();

        $helper = new FixtureHelper();
        $helper->insert($helper->loadFixtures(['core.Articles']));
        $rows = $connection->selectQuery()->select('*')->from('articles')->execute();
        $this->assertNotEmpty($rows->fetchAll());
        $rows->closeCursor();
    }

    /**
     * Tests handling PDO errors when inserting rows.
     */
    public function testInsertFixturesException(): void
    {
        $fixture = new class extends TestFixture {
            public function connection(): string
            {
                return 'test';
            }

            protected function _schemaFromReflection(): void
            {
            }

            public function insert(ConnectionInterface $connection): bool
            {
                throw new PDOException('Missing key');
            }
        };

        $helper = new class extends FixtureHelper {
            public function sortByConstraint(Connection $connection, array $fixtures): array
            {
                return [new class extends TestFixture {
                    public function connection(): string
                    {
                        return 'test';
                    }

                    protected function _schemaFromReflection(): void
                    {
                    }

                    public function insert(ConnectionInterface $connection): bool
                    {
                        throw new PDOException('Missing key');
                    }
                }];
            }
        };

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Unable to insert rows for table `');
        $helper->insert([$fixture]);
    }

    /**
     * Tests truncating fixtures.
     */
    public function testTruncateFixtures(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        $rows = $connection->selectQuery()->select('*')->from('articles')->execute();
        $this->assertNotEmpty($rows->fetchAll());
        $rows->closeCursor();

        $helper = new FixtureHelper();
        $helper->truncate($helper->loadFixtures(['core.Articles']));
        $rows = $connection->selectQuery()->select('*')->from('articles')->execute();
        $this->assertEmpty($rows->fetchAll());
        $rows->closeCursor();
    }

    /**
     * Tests handling PDO errors when trucating rows.
     */
    public function testTruncateFixturesException(): void
    {
        $fixture = new class extends TestFixture {
            public function connection(): string
            {
                return 'test';
            }

            protected function _schemaFromReflection(): void
            {
            }

            public function truncate(ConnectionInterface $connection): bool
            {
                throw new PDOException('Missing key');
            }
        };

        $helper = new class extends FixtureHelper {
            public function sortByConstraint(Connection $connection, array $fixtures): array
            {
                return [new class extends TestFixture {
                    public function connection(): string
                    {
                        return 'test';
                    }

                    protected function _schemaFromReflection(): void
                    {
                    }

                    public function truncate(ConnectionInterface $connection): bool
                    {
                        throw new PDOException('Missing key');
                    }
                }];
            }
        };

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Unable to truncate table `');
        $helper->truncate([$fixture]);
    }

    /**
     * Tests deleting the rows of fixtures.
     */
    public function testDeleteFixtures(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        $rows = $connection->selectQuery()->select('*')->from('articles')->execute();
        $this->assertNotEmpty($rows->fetchAll());
        $rows->closeCursor();

        $helper = new FixtureHelper();
        $helper->delete($helper->loadFixtures(['core.Articles']));
        $rows = $connection->selectQuery()->select('*')->from('articles')->execute();
        $this->assertEmpty($rows->fetchAll());
        $rows->closeCursor();
    }

    /**
     * Tests handling PDO errors when deleting rows.
     */
    public function testDeleteFixturesException(): void
    {
        $fixture = new class extends TestFixture {
            public string $table = 'this_table_does_not_exist';

            public function connection(): string
            {
                return 'test';
            }

            protected function _schemaFromReflection(): void
            {
            }
        };

        $helper = new class extends FixtureHelper {
            protected function sortByConstraint(Connection $connection, array $fixtures): ?array
            {
                return $fixtures;
            }
        };

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Unable to delete rows from table `this_table_does_not_exist`.');
        $helper->delete([$fixture]);
    }

    /**
     * Connections which are not database connections have no delete query builder,
     * so they keep going through FixtureInterface::truncate().
     */
    public function testDeleteFixturesWithoutDatabaseConnection(): void
    {
        ConnectionManager::setConfig('fake', ['className' => FakeConnection::class]);

        $fixture = new class extends TestFixture {
            public bool $truncated = false;

            public function connection(): string
            {
                return 'fake';
            }

            protected function _schemaFromReflection(): void
            {
            }

            public function truncate(ConnectionInterface $connection): bool
            {
                $this->truncated = true;

                return true;
            }
        };

        (new FixtureHelper())->delete([$fixture]);
        $this->assertTrue($fixture->truncated);
    }

    /**
     * Tests that fixtures are deleted once per connection.
     */
    public function testDeleteFixturesPerConnection(): void
    {
        ConnectionManager::alias('test', 'test1');
        ConnectionManager::alias('test', 'test2');

        $articles = new class extends TestFixture {
            public string $table = 'articles';

            public function connection(): string
            {
                return 'test1';
            }
        };
        $orders = new class extends TestFixture {
            public string $table = 'orders';

            public function connection(): string
            {
                return 'test2';
            }
        };

        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        foreach (['articles', 'orders'] as $table) {
            $this->assertNotEmpty($this->readTable($connection, $table), "Table `{$table}` has no rows.");
        }

        (new FixtureHelper())->delete([$articles, $orders]);
        foreach (['articles', 'orders'] as $table) {
            $this->assertEmpty($this->readTable($connection, $table), "Table `{$table}` was not emptied.");
        }
    }

    /**
     * Tests that fixture tables referencing a table without foreign keys of its own
     * are emptied in the reverse of their insertion order, constraints left enabled.
     */
    public function testDeleteFixturesWithConstraints(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        foreach (['products', 'orders'] as $table) {
            $this->assertNotEmpty($this->readTable($connection, $table), "Table `{$table}` has no rows.");
        }

        // Orders references products, which references nothing, so the fixtures sort.
        $helper = new FixtureHelper();
        $helper->delete($helper->loadFixtures(['core.Orders', 'core.Products']));
        foreach (['products', 'orders'] as $table) {
            $this->assertEmpty($this->readTable($connection, $table), "Table `{$table}` was not emptied.");
        }
    }

    /**
     * sortByConstraint() only separates the tables which have foreign keys from the
     * tables which do not, so a table referencing another constrained table cannot be
     * ordered even though the graph is acyclic.
     */
    public function testSortByConstraintGivesUpOnNestedConstraints(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        $this->createNestedTables($connection);

        $helper = $this->sortingHelper();
        $fixtures = $this->nestedFixtures();
        $this->assertNull(
            $helper->sortFixtures($connection, $fixtures),
            'Nested constraints are expected to be reported as unsortable.',
        );

        // The same fixtures without the deepest table are one level only, and do sort.
        array_pop($fixtures);
        $this->assertNotNull($helper->sortFixtures($connection, $fixtures));
    }

    /**
     * Tests that fixtures which cannot be sorted are deleted with the constraints
     * disabled instead.
     */
    public function testDeleteFixturesWithNestedConstraints(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        $this->createNestedTables($connection);

        $helper = new FixtureHelper();
        $fixtures = $this->nestedFixtures();
        $helper->insert($fixtures);
        foreach ($this->nestedTables as $table) {
            $this->assertNotEmpty($this->readTable($connection, $table), "Table `{$table}` has no rows.");
        }

        $helper->delete($fixtures);
        foreach ($this->nestedTables as $table) {
            $this->assertEmpty($this->readTable($connection, $table), "Table `{$table}` was not emptied.");
        }
    }

    /**
     * A table holding a foreign key to itself cannot be sorted either, so its rows are
     * deleted with the constraints disabled.
     */
    public function testDeleteFixturesWithSelfReferencingConstraint(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        $this->createNestedTables($connection);

        $table = static::SELF_REFERENCING_TABLE;
        $fixture = new class extends TestFixture {
            public string $table = FixtureHelperTest::SELF_REFERENCING_TABLE;

            public array $records = [
                ['id' => 1, 'parent_id' => null],
                ['id' => 2, 'parent_id' => 1],
            ];

            public function connection(): string
            {
                return 'test';
            }
        };

        $this->assertNull(
            $this->sortingHelper()->sortFixtures($connection, [$fixture]),
            'A self referencing table is expected to be reported as unsortable.',
        );

        $helper = new FixtureHelper();
        $helper->insert([$fixture]);
        $this->assertCount(2, $this->readTable($connection, $table));

        $helper->delete([$fixture]);
        $this->assertEmpty($this->readTable($connection, $table), "Table `{$table}` was not emptied.");
    }

    /**
     * A helper exposing the protected sorting used to decide whether the constraints
     * have to be disabled.
     *
     * @return \Cake\TestSuite\Fixture\FixtureHelper
     */
    protected function sortingHelper(): FixtureHelper
    {
        return new class extends FixtureHelper {
            public function sortFixtures(Connection $connection, array $fixtures): ?array
            {
                return $this->sortByConstraint($connection, $fixtures);
            }
        };
    }

    /**
     * Builds a three level chain of tables, so that the middle table both has a foreign
     * key and is referenced by one, plus a table holding a foreign key to itself.
     *
     * @return array<\Cake\Database\Schema\TableSchema>
     */
    protected function nestedTableSchemas(): array
    {
        $schemas = [];
        $parent = null;
        foreach ($this->nestedTables as $table) {
            $columns = ['id' => ['type' => 'integer']];
            if ($parent !== null) {
                $columns['parent_id'] = ['type' => 'integer', 'null' => false];
            }

            $schema = new TableSchema($table, $columns);
            $schema->addConstraint('primary', ['type' => 'primary', 'columns' => ['id']]);
            if ($parent !== null) {
                // No cascades: sqlserver rejects them on self references, and the
                // point of these tables is that the rows cannot go without the
                // constraints being disabled.
                $schema->addConstraint("{$table}_parent_id_fk", [
                    'type' => 'foreign',
                    'columns' => ['parent_id'],
                    'references' => [$parent, 'id'],
                    'update' => 'noAction',
                    'delete' => 'noAction',
                ]);
            }

            $schemas[] = $schema;
            $parent = $table;
        }

        // A table referencing itself is reported as unsortable for the same reason.
        $self = new TableSchema(static::SELF_REFERENCING_TABLE, [
            'id' => ['type' => 'integer'],
            'parent_id' => ['type' => 'integer', 'null' => true],
        ]);
        $self->addConstraint('primary', ['type' => 'primary', 'columns' => ['id']]);
        $self->addConstraint(static::SELF_REFERENCING_TABLE . '_parent_id_fk', [
            'type' => 'foreign',
            'columns' => ['parent_id'],
            'references' => [static::SELF_REFERENCING_TABLE, 'id'],
            'update' => 'noAction',
            'delete' => 'noAction',
        ]);
        $schemas[] = $self;

        return $schemas;
    }

    /**
     * @param \Cake\Database\Connection $connection Test connection
     * @return void
     */
    protected function createNestedTables(Connection $connection): void
    {
        foreach ($this->nestedTableSchemas() as $schema) {
            foreach ($schema->createSql($connection) as $sql) {
                $connection->execute($sql);
            }
        }
        $this->nestedTablesCreated = true;
    }

    /**
     * @return void
     */
    protected function dropNestedTables(): void
    {
        if (!$this->nestedTablesCreated) {
            return;
        }

        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        foreach (array_reverse($this->nestedTableSchemas()) as $schema) {
            foreach ($schema->dropSql($connection) as $sql) {
                $connection->execute($sql);
            }
        }
        $this->nestedTablesCreated = false;
    }

    /**
     * One fixture per nested table, parents first.
     *
     * @return array<\Cake\Datasource\FixtureInterface>
     */
    protected function nestedFixtures(): array
    {
        $fixtures = [];
        $parent = null;
        foreach ($this->nestedTables as $table) {
            $record = ['id' => 1];
            if ($parent !== null) {
                $record['parent_id'] = 1;
            }

            $fixtures[] = new class ($table, $record) extends TestFixture {
                public function __construct(string $table, array $record)
                {
                    $this->table = $table;
                    $this->records = [$record];
                    parent::__construct();
                }

                public function connection(): string
                {
                    return 'test';
                }
            };
            $parent = $table;
        }

        return $fixtures;
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
