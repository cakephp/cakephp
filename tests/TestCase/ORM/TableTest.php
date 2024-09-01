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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use ArrayObject;
use AssertionError;
use BadMethodCallException;
use Cake\Collection\Collection;
use Cake\Core\Exception\CakeException;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Query as DbQuery;
use Cake\Database\Schema\TableSchema;
use Cake\Database\StatementInterface;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\I18n\DateTime;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\ORM\AssociationCollection;
use Cake\ORM\BehaviorRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Exception\MissingBehaviorException;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Marshaller;
use Cake\ORM\Query;
use Cake\ORM\Query\DeleteQuery;
use Cake\ORM\Query\InsertQuery;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Query\UpdateQuery;
use Cake\ORM\ResultSet;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use Closure;
use Exception;
use InvalidArgumentException;
use Mockery;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use TestApp\Model\Entity\Article;
use TestApp\Model\Entity\ArticlesTag;
use TestApp\Model\Entity\Author;
use TestApp\Model\Entity\ProtectedEntity;
use TestApp\Model\Entity\Tag;
use TestApp\Model\Entity\VirtualUser;
use TestApp\Model\Table\ArticlesTable;
use TestApp\Model\Table\UsersTable;
use TestPlugin\Model\Table\CommentsTable;

/**
 * Tests Table class
 */
class TableTest extends TestCase
{
    /**
     * @var string[]
     */
    protected array $fixtures = [
        'core.Articles',
        'core.Tags',
        'core.ArticlesTags',
        'core.Authors',
        'core.Categories',
        'core.Comments',
        'core.Sections',
        'core.SectionsMembers',
        'core.Members',
        'core.PolymorphicTagged',
        'core.SiteArticles',
        'core.Users',
    ];

    /**
     * Handy variable containing the next primary key that will be inserted in the
     * users table
     *
     * @var int
     */
    protected static $nextUserId = 5;

    /**
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $usersTypeMap;

    /**
     * @var \Cake\Database\TypeMap
     */
    protected $articlesTypeMap;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        static::setAppNamespace();

        $this->usersTypeMap = new TypeMap([
            'Users.id' => 'integer',
            'id' => 'integer',
            'Users__id' => 'integer',
            'Users.username' => 'string',
            'Users__username' => 'string',
            'username' => 'string',
            'Users.password' => 'string',
            'Users__password' => 'string',
            'password' => 'string',
            'Users.created' => 'timestamp',
            'Users__created' => 'timestamp',
            'created' => 'timestamp',
            'Users.updated' => 'timestamp',
            'Users__updated' => 'timestamp',
            'updated' => 'timestamp',
        ]);

        $config = $this->connection->config();
        if (str_contains($config['driver'], 'Postgres')) {
            $this->usersTypeMap = new TypeMap([
                'Users.id' => 'integer',
                'id' => 'integer',
                'Users__id' => 'integer',
                'Users.username' => 'string',
                'Users__username' => 'string',
                'username' => 'string',
                'Users.password' => 'string',
                'Users__password' => 'string',
                'password' => 'string',
                'Users.created' => 'timestampfractional',
                'Users__created' => 'timestampfractional',
                'created' => 'timestampfractional',
                'Users.updated' => 'timestampfractional',
                'Users__updated' => 'timestampfractional',
                'updated' => 'timestampfractional',
            ]);
        } elseif (str_contains($config['driver'], 'Sqlserver')) {
            $this->usersTypeMap = new TypeMap([
                'Users.id' => 'integer',
                'id' => 'integer',
                'Users__id' => 'integer',
                'Users.username' => 'string',
                'Users__username' => 'string',
                'username' => 'string',
                'Users.password' => 'string',
                'Users__password' => 'string',
                'password' => 'string',
                'Users.created' => 'datetimefractional',
                'Users__created' => 'datetimefractional',
                'created' => 'datetimefractional',
                'Users.updated' => 'datetimefractional',
                'Users__updated' => 'datetimefractional',
                'updated' => 'datetimefractional',
            ]);
        }

        $this->articlesTypeMap = new TypeMap([
            'Articles.id' => 'integer',
            'Articles__id' => 'integer',
            'id' => 'integer',
            'Articles.title' => 'string',
            'Articles__title' => 'string',
            'title' => 'string',
            'Articles.author_id' => 'integer',
            'Articles__author_id' => 'integer',
            'author_id' => 'integer',
            'Articles.body' => 'text',
            'Articles__body' => 'text',
            'body' => 'text',
            'Articles.published' => 'string',
            'Articles__published' => 'string',
            'published' => 'string',
        ]);
    }

    /**
     * teardown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * Tests query creation wrappers.
     */
    public function testTableQuery(): void
    {
        $table = new Table(['table' => 'users']);

        $query = $table->query();
        $this->assertEquals('users', $query->getRepository()->getTable());

        $query = $table->selectQuery();
        $this->assertEquals('users', $query->getRepository()->getTable());

        $query = $table->subquery();
        $this->assertEquals('users', $query->getRepository()->getTable());

        $sql = $query->select(['username'])->sql();
        $this->assertRegExpSql(
            'SELECT <username> FROM <users> <users>',
            $sql,
            !$this->connection->getDriver()->isAutoQuotingEnabled()
        );
    }

    /**
     * Tests subquery() disables aliasing.
     */
    public function testSubqueryAliasing(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $subquery = $articles->subquery();

        $subquery->select('Articles.field1');
        $this->assertRegExpSql(
            'SELECT <Articles>.<field1> FROM <articles> <Articles>',
            $subquery->sql(),
            !$this->connection->getDriver()->isAutoQuotingEnabled()
        );

        $subquery->select($articles, true);
        $this->assertEqualsSql('SELECT id, author_id, title, body, published FROM articles Articles', $subquery->sql());

        $subquery->selectAllExcept($articles, ['author_id'], true);
        $this->assertEqualsSql('SELECT id, title, body, published FROM articles Articles', $subquery->sql());
    }

    /**
     * Tests subquery() in where clause.
     */
    public function testSubqueryWhereClause(): void
    {
        $subquery = $this->getTableLocator()->get('Authors')->subquery()
            ->select(['Authors.id'])
            ->where(['Authors.name' => 'mariano']);

        $query = $this->getTableLocator()->get('Articles')->find()
            ->where(['Articles.author_id IN' => $subquery])
            ->orderBy(['Articles.id' => 'ASC']);

        $results = $query->all()->toList();
        $this->assertCount(2, $results);
        $this->assertEquals([1, 3], array_column($results, 'id'));
    }

    /**
     * Tests subquery() in join clause.
     */
    public function testSubqueryJoinClause(): void
    {
        $subquery = $this->getTableLocator()->get('Articles')->subquery()
            ->select(['author_id']);

        $query = $this->getTableLocator()->get('Authors')->find();
        $query
            ->select(['Authors.id', 'total_articles' => $query->func()->count('articles.author_id')])
            ->leftJoin(['articles' => $subquery], ['articles.author_id' => new IdentifierExpression('Authors.id')])
            ->groupBy(['Authors.id'])
            ->orderBy(['Authors.id' => 'ASC']);

        $results = $query->all()->toList();
        $this->assertEquals(1, $results[0]->id);
        $this->assertEquals(2, $results[0]->total_articles);
    }

    /**
     * Tests the table method
     */
    public function testTableMethod(): void
    {
        $table = new Table(['table' => 'users']);
        $this->assertSame('users', $table->getTable());

        $table = new UsersTable();
        $this->assertSame('users', $table->getTable());

        /** @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject $table */
        $table = $this->getMockBuilder(Table::class)
            ->onlyMethods(['find'])
            ->setMockClassName('SpecialThingsTable')
            ->getMock();
        $this->assertSame('special_things', $table->getTable());

        $table = new Table(['alias' => 'LoveBoats']);
        $this->assertSame('love_boats', $table->getTable());

        $table->setTable('other');
        $this->assertSame('other', $table->getTable());

        $table->setTable('database.other');
        $this->assertSame('database.other', $table->getTable());
    }

    /**
     * Tests the setAlias method
     */
    public function testSetAlias(): void
    {
        $table = new Table(['alias' => 'users']);
        $this->assertSame('users', $table->getAlias());

        $table = new Table(['table' => 'stuffs']);
        $this->assertSame('stuffs', $table->getAlias());

        $table = new UsersTable();
        $this->assertSame('Users', $table->getAlias());

        /** @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject $table */
        $table = $this->getMockBuilder(Table::class)
            ->onlyMethods(['find'])
            ->setMockClassName('SpecialThingTable')
            ->getMock();
        $this->assertSame('SpecialThing', $table->getAlias());

        $table->setAlias('AnotherOne');
        $this->assertSame('AnotherOne', $table->getAlias());
    }

    public function testGetAliasException(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('You must specify either the `alias` or the `table` option for the constructor.');

        $table = new Table();
        $table->getAlias();
    }

    public function testGetTableException(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('You must specify either the `alias` or the `table` option for the constructor.');

        $table = new Table();
        $table->getTable();
    }

    /**
     * Test that aliasField() works.
     */
    public function testAliasField(): void
    {
        $table = new Table(['alias' => 'Users']);
        $this->assertSame('Users.id', $table->aliasField('id'));

        $this->assertSame('Users.id', $table->aliasField('Users.id'));
    }

    /**
     * Tests setConnection method
     */
    public function testSetConnection(): void
    {
        $table = new Table(['table' => 'users']);
        $this->assertSame($this->connection, $table->getConnection());
        $this->assertSame($table, $table->setConnection($this->connection));
        $this->assertSame($this->connection, $table->getConnection());
    }

    /**
     * Tests primaryKey method
     */
    public function testSetPrimaryKey(): void
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'id' => ['type' => 'integer'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
            ],
        ]);
        $this->assertSame('id', $table->getPrimaryKey());
        $this->assertSame($table, $table->setPrimaryKey('thingID'));
        $this->assertSame('thingID', $table->getPrimaryKey());

        $table->setPrimaryKey(['thingID', 'user_id']);
        $this->assertEquals(['thingID', 'user_id'], $table->getPrimaryKey());
    }

    /**
     * Tests that name will be selected as a displayField
     */
    public function testDisplayFieldName(): void
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'foo' => ['type' => 'string'],
                'name' => ['type' => 'string'],
            ],
        ]);
        $this->assertSame('name', $table->getDisplayField());
    }

    /**
     * Tests that title will be selected as a displayField
     */
    public function testDisplayFieldTitle(): void
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'foo' => ['type' => 'string'],
                'title' => ['type' => 'string'],
            ],
        ]);
        $this->assertSame('title', $table->getDisplayField());
    }

    /**
     * Tests that label will be selected as a displayField
     */
    public function testDisplayFieldLabel(): void
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'foo' => ['type' => 'string'],
                'label' => ['type' => 'string'],
            ],
        ]);
        $this->assertSame('label', $table->getDisplayField());
    }

    /**
     * Tests that displayField will fallback to first *_name field
     */
    public function testDisplayNameFallback(): void
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'id' => ['type' => 'integer'],
                'custom_title' => ['type' => 'string'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
            ],
        ]);
        $this->assertSame('custom_title', $table->getDisplayField());

        $table = new Table([
            'table' => 'users',
            'schema' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'custom_title' => ['type' => 'string'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
            ],
        ]);
        $this->assertSame('name', $table->getDisplayField());

        $table = new Table([
            'table' => 'users',
            'schema' => [
                'id' => ['type' => 'integer'],
                'title_id' => ['type' => 'integer'],
                'custom_name' => ['type' => 'string'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
            ],
        ]);
        $this->assertSame('custom_name', $table->getDisplayField());

        $table = new Table([
            'table' => 'users',
            'schema' => [
                'id' => ['type' => 'integer'],
                'nullable_title' => ['type' => 'string', 'null' => true],
                'custom_name' => ['type' => 'string'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
            ],
        ]);
        $this->assertSame('custom_name', $table->getDisplayField());

        $table = new Table([
            'table' => 'users',
            'schema' => [
                'id' => ['type' => 'integer'],
                'nullable_title' => ['type' => 'string', 'null' => true],
                'password' => ['type' => 'string'],
                'user_secret' => ['type' => 'string'],
                'api_token' => ['type' => 'string'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
            ],
        ]);
        $this->assertSame('id', $table->getDisplayField());
    }

    /**
     * Tests that no displayField will fallback to primary key
     */
    public function testDisplayIdFallback(): void
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'id' => ['type' => 'string'],
                'foo' => ['type' => 'string'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
            ],
        ]);
        $this->assertSame('id', $table->getDisplayField());

        $table = $this->getTableLocator()->get('ArticlesTags');
        $this->assertSame(['article_id', 'tag_id'], $table->getDisplayField());
    }

    /**
     * Tests that displayField can be changed
     */
    public function testDisplaySet(): void
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'id' => ['type' => 'string'],
                'foo' => ['type' => 'string'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
            ],
        ]);
        $this->assertSame('id', $table->getDisplayField());
        $table->setDisplayField('foo');
        $this->assertSame('foo', $table->getDisplayField());
    }

    /**
     * Tests schema method
     */
    public function testSetSchema(): void
    {
        $schema = $this->connection->getSchemaCollection()->describe('users');
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $this->assertEquals($schema, $table->getSchema());

        $table = new Table(['table' => 'stuff']);
        $table->setSchema($schema);
        $this->assertSame($schema, $table->getSchema());

        $table = new Table(['table' => 'another']);
        $schema = ['id' => ['type' => 'integer']];
        $table->setSchema($schema);
        $this->assertEquals(
            new TableSchema('another', $schema),
            $table->getSchema()
        );
    }

    /**
     * Tests schema method with long identifiers
     */
    public function testSetSchemaLongIdentifiers(): void
    {
        $schema = new TableSchema('long_identifiers', [
            'this_is_invalid_because_it_is_very_very_very_long' => [
                'type' => 'string',
            ],
        ]);
        $table = new Table([
            'table' => 'very_long_alias_name',
            'connection' => $this->connection,
        ]);

        $maxAlias = $this->connection->getDriver()->getMaxAliasLength();
        if ($maxAlias && $maxAlias < 72) {
            $nameLength = $maxAlias - 2;
            $this->expectException(DatabaseException::class);
            $this->expectExceptionMessage(
                'ORM queries generate field aliases using the table name/alias and column name. ' .
                "The table alias `very_long_alias_name` and column `this_is_invalid_because_it_is_very_very_very_long` create an alias longer than ({$nameLength}). " .
                'You must change the table schema in the database and shorten either the table or column ' .
                'identifier so they fit within the database alias limits.'
            );
        }
        $this->assertNotNull($table->setSchema($schema));
    }

    public function testSchemaTypeOverrideInInitialize(): void
    {
        $table = new class (['alias' => 'Users', 'table' => 'users', 'connection' => $this->connection]) extends Table {
            public function initialize(array $config): void
            {
                $this->getSchema()->setColumnType('username', 'foobar');
            }
        };

        $result = $table->getSchema();
        $this->assertSame('foobar', $result->getColumnType('username'));
    }

    /**
     * Undocumented function
     *
     * @return void
     * @deprecated
     */
    #[WithoutErrorHandler]
    public function testFindAllOldStyleOptionsArray(): void
    {
        $this->deprecated(function (): void {
            $table = new Table([
                'table' => 'users',
                'connection' => $this->connection,
            ]);

            $query = $table->find('all', ['fields' => ['id']]);
            $this->assertSame(['id'], $query->clause('select'));
        });
    }

    /**
     * Tests that all fields for a table are added by default in a find when no
     * other fields are specified
     */
    public function testFindAllNoFieldsAndNoHydration(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $results = $table
            ->find('all')
            ->where(['id IN' => [1, 2]])
            ->orderBy('id')
            ->enableHydration(false)
            ->toArray();
        $expected = [
            [
                'id' => 1,
                'username' => 'mariano',
                'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
                'created' => new DateTime('2007-03-17 01:16:23'),
                'updated' => new DateTime('2007-03-17 01:18:31'),
            ],
            [
                'id' => 2,
                'username' => 'nate',
                'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
                'created' => new DateTime('2008-03-17 01:18:23'),
                'updated' => new DateTime('2008-03-17 01:20:31'),
            ],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to select only a few fields when finding over a table
     */
    public function testFindAllSomeFieldsNoHydration(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $results = $table->find('all')
            ->select(['username', 'password'])
            ->enableHydration(false)
            ->orderBy('username')->toArray();
        $expected = [
            ['username' => 'garrett', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
            ['username' => 'larry', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
            ['username' => 'mariano', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
            ['username' => 'nate', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
        ];
        $this->assertSame($expected, $results);

        $results = $table->find('all')
            ->select(['foo' => 'username', 'password'])
            ->orderBy('username')
            ->enableHydration(false)
            ->toArray();
        $expected = [
            ['foo' => 'garrett', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
            ['foo' => 'larry', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
            ['foo' => 'mariano', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
            ['foo' => 'nate', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
        ];
        $this->assertSame($expected, $results);
    }

    /**
     * Tests that the query will automatically casts complex conditions to the correct
     * types when the columns belong to the default table
     */
    public function testFindAllConditionAutoTypes(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $query = $table->find('all')
            ->select(['id', 'username'])
            ->where(['created >=' => new DateTime('2010-01-22 00:00')])
            ->enableHydration(false)
            ->orderBy('id');
        $expected = [
            ['id' => 3, 'username' => 'larry'],
            ['id' => 4, 'username' => 'garrett'],
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $table->find()
            ->enableHydration(false)
            ->select(['id', 'username'])
            ->where(['OR' => [
                'created >=' => new DateTime('2010-01-22 00:00'),
                'users.created' => new DateTime('2008-03-17 01:18:23'),
            ]])
            ->orderBy('id');
        $expected = [
            ['id' => 2, 'username' => 'nate'],
            ['id' => 3, 'username' => 'larry'],
            ['id' => 4, 'username' => 'garrett'],
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * Test that beforeFind events can mutate the query.
     */
    public function testFindBeforeFindEventMutateQuery(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $table->getEventManager()->on(
            'Model.beforeFind',
            function (EventInterface $event, $query, $options): void {
                $query->limit(1);
            }
        );

        $result = $table->find('all')->all();
        $this->assertCount(1, $result, 'Should only have 1 record, limit 1 applied.');
    }

    /**
     * Test that beforeFind events are fired and can stop the find and
     * return custom results.
     */
    public function testFindBeforeFindEventOverrideReturn(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $expected = ['One', 'Two', 'Three'];
        $table->getEventManager()->on(
            'Model.beforeFind',
            function (EventInterface $event, $query, $options) use ($expected): void {
                $query->setResult($expected);
                $event->stopPropagation();
            }
        );

        $query = $table->find('all')
            ->formatResults(function (ResultSet $results) {
                return $results;
            });
        $query->limit(1);
        $this->assertEquals($expected, $query->all()->toArray());
    }

    /**
     * Test that the getAssociation() method supports the dot syntax.
     */
    public function testAssociationDotSyntax(): void
    {
        $sections = $this->getTableLocator()->get('Sections');
        $members = $this->getTableLocator()->get('Members');
        $sectionsMembers = $this->getTableLocator()->get('SectionsMembers');

        $sections->belongsToMany('Members');
        $sections->hasMany('SectionsMembers');
        $sectionsMembers->belongsTo('Members');
        $members->belongsToMany('Sections');

        $association = $sections->getAssociation('SectionsMembers.Members.Sections');
        $this->assertInstanceOf(BelongsToMany::class, $association);
        $this->assertSame(
            $sections->getAssociation('SectionsMembers')->getAssociation('Members')->getAssociation('Sections'),
            $association
        );
    }

    public function testGetAssociationWithIncorrectCasing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "The `authors` association is not defined on `Articles`.\n"
            . 'Valid associations are: Authors, Tags, ArticlesTags'
        );

        $articles = $this->getTableLocator()->get('Articles', ['className' => ArticlesTable::class]);

        $articles->getAssociation('authors');
    }

    /**
     * Tests that the getAssociation() method throws an exception on nonexistent ones.
     */
    public function testGetAssociationNonExistent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `FooBar` association is not defined on `Sections`.');

        $this->getTableLocator()->get('Sections')->getAssociation('FooBar');
    }

    /**
     * Tests that belongsTo() creates and configures correctly the association
     */
    public function testBelongsTo(): void
    {
        $options = ['foreignKey' => 'fake_id', 'conditions' => ['a' => 'b']];
        $table = new Table(['table' => 'dates']);
        $belongsTo = $table->belongsTo('user', $options);
        $this->assertInstanceOf(BelongsTo::class, $belongsTo);
        $this->assertSame($belongsTo, $table->getAssociation('user'));
        $this->assertSame('user', $belongsTo->getName());
        $this->assertSame('fake_id', $belongsTo->getForeignKey());
        $this->assertEquals(['a' => 'b'], $belongsTo->getConditions());
        $this->assertSame($table, $belongsTo->getSource());
    }

    /**
     * Tests that hasOne() creates and configures correctly the association
     */
    public function testHasOne(): void
    {
        $table = new Table(['table' => 'users']);
        $hasOne = $table->hasOne('profile', ['conditions' => ['b' => 'c']]);
        $this->assertInstanceOf(HasOne::class, $hasOne);
        $this->assertSame($hasOne, $table->getAssociation('profile'));
        $this->assertSame('profile', $hasOne->getName());
        $this->assertSame('user_id', $hasOne->getForeignKey());
        $this->assertEquals(['b' => 'c'], $hasOne->getConditions());
        $this->assertSame($table, $hasOne->getSource());
    }

    /**
     * Test has one with a plugin model
     */
    public function testHasOnePlugin(): void
    {
        $table = new Table(['table' => 'users']);

        $hasOne = $table->hasOne('Comments', ['className' => 'TestPlugin.Comments']);
        $this->assertInstanceOf(HasOne::class, $hasOne);
        $this->assertSame('Comments', $hasOne->getName());

        $this->assertSame('Comments', $hasOne->getAlias());
        $this->assertSame('TestPlugin.Comments', $hasOne->getRegistryAlias());

        $table = new Table(['table' => 'users']);

        $hasOne = $table->hasOne('TestPlugin.Comments', ['className' => 'TestPlugin.Comments']);
        $this->assertInstanceOf(HasOne::class, $hasOne);
        $this->assertSame('Comments', $hasOne->getName());

        $this->assertSame('Comments', $hasOne->getAlias());
        $this->assertSame('TestPlugin.Comments', $hasOne->getRegistryAlias());
    }

    /**
     * testNoneUniqueAssociationsSameClass
     */
    public function testNoneUniqueAssociationsSameClass(): void
    {
        $Users = new Table(['table' => 'users']);
        $Users->hasMany('Comments');

        $Articles = new Table(['table' => 'articles']);
        $Articles->hasMany('Comments');

        $Categories = new Table(['table' => 'categories']);
        $options = ['className' => 'TestPlugin.Comments'];
        $Categories->hasMany('Comments', $options);

        $this->assertInstanceOf(Table::class, $Users->Comments->getTarget());
        $this->assertInstanceOf(Table::class, $Articles->Comments->getTarget());
        $this->assertInstanceOf(CommentsTable::class, $Categories->Comments->getTarget());
    }

    /**
     * Test associations which refer to the same table multiple times
     */
    public function testSelfJoinAssociations(): void
    {
        $Categories = $this->getTableLocator()->get('Categories');
        $options = ['className' => 'Categories'];
        $Categories->hasMany('Children', ['foreignKey' => 'parent_id'] + $options);
        $Categories->belongsTo('Parent', $options);

        $this->assertSame('categories', $Categories->Children->getTarget()->getTable());
        $this->assertSame('categories', $Categories->Parent->getTarget()->getTable());

        $this->assertSame('Children', $Categories->Children->getAlias());
        $this->assertSame('Children', $Categories->Children->getTarget()->getAlias());

        $this->assertSame('Parent', $Categories->Parent->getAlias());
        $this->assertSame('Parent', $Categories->Parent->getTarget()->getAlias());

        $expected = [
            'id' => 2,
            'parent_id' => 1,
            'name' => 'Category 1.1',
            'parent' => [
                'id' => 1,
                'parent_id' => 0,
                'name' => 'Category 1',
            ],
            'children' => [
                [
                    'id' => 7,
                    'parent_id' => 2,
                    'name' => 'Category 1.1.1',
                ],
                [
                    'id' => 8,
                    'parent_id' => 2,
                    'name' => 'Category 1.1.2',
                ],
            ],
        ];

        $fields = ['id', 'parent_id', 'name'];
        $result = $Categories->find('all')
            ->select(['Categories.id', 'Categories.parent_id', 'Categories.name'])
            ->contain(['Children' => ['fields' => $fields], 'Parent' => ['fields' => $fields]])
            ->where(['Categories.id' => 2])
            ->first()
            ->toArray();

        $this->assertSame($expected, $result);
    }

    /**
     * Tests that hasMany() creates and configures correctly the association
     */
    public function testHasMany(): void
    {
        $options = [
            'conditions' => ['b' => 'c'],
            'sort' => ['foo' => 'asc'],
        ];
        $table = new Table(['table' => 'authors']);
        $hasMany = $table->hasMany('article', $options);
        $this->assertInstanceOf(HasMany::class, $hasMany);
        $this->assertSame($hasMany, $table->getAssociation('article'));
        $this->assertSame('article', $hasMany->getName());
        $this->assertSame('author_id', $hasMany->getForeignKey());
        $this->assertEquals(['b' => 'c'], $hasMany->getConditions());
        $this->assertEquals(['foo' => 'asc'], $hasMany->getSort());
        $this->assertSame($table, $hasMany->getSource());
    }

    /**
     * testHasManyWithClassName
     */
    public function testHasManyWithClassName(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->hasMany('Comments', [
            'conditions' => ['published' => 'Y'],
        ]);

        $table->hasMany('UnapprovedComments', [
            'className' => 'Comments',
            'conditions' => ['published' => 'N'],
            'propertyName' => 'unaproved_comments',
        ]);

        $expected = [
            'id' => 1,
            'title' => 'First Article',
            'unaproved_comments' => [
                [
                    'id' => 4,
                    'article_id' => 1,
                    'comment' => 'Fourth Comment for First Article',
                ],
            ],
            'comments' => [
                [
                    'id' => 1,
                    'article_id' => 1,
                    'comment' => 'First Comment for First Article',
                ],
                [
                    'id' => 2,
                    'article_id' => 1,
                    'comment' => 'Second Comment for First Article',
                ],
                [
                    'id' => 3,
                    'article_id' => 1,
                    'comment' => 'Third Comment for First Article',
                ],
            ],
        ];
        $result = $table->find()
            ->select(['id', 'title'])
            ->contain([
                'Comments' => ['fields' => ['id', 'article_id', 'comment']],
                'UnapprovedComments' => ['fields' => ['id', 'article_id', 'comment']],
            ])
            ->where(['id' => 1])
            ->first();

        $this->assertSame($expected, $result->toArray());
    }

    /**
     * Ensure associations use the plugin-prefixed model
     */
    public function testHasManyPluginOverlap(): void
    {
        $this->getTableLocator()->get('Comments');
        $this->loadPlugins(['TestPlugin']);

        $table = new Table(['table' => 'authors']);

        $table->hasMany('TestPlugin.Comments');
        $comments = $table->Comments->getTarget();
        $this->assertInstanceOf(CommentsTable::class, $comments);
    }

    /**
     * Ensure associations use the plugin-prefixed model
     * even if specified with config
     */
    public function testHasManyPluginOverlapConfig(): void
    {
        $this->getTableLocator()->get('Comments');
        $this->loadPlugins(['TestPlugin']);

        $table = new Table(['table' => 'authors']);

        $table->hasMany('Comments', ['className' => 'TestPlugin.Comments']);
        $comments = $table->Comments->getTarget();
        $this->assertInstanceOf(CommentsTable::class, $comments);
    }

    /**
     * Tests that BelongsToMany() creates and configures correctly the association
     */
    public function testBelongsToMany(): void
    {
        $options = [
            'foreignKey' => 'thing_id',
            'joinTable' => 'things_tags',
            'conditions' => ['b' => 'c'],
            'sort' => ['foo' => 'asc'],
        ];
        $table = new Table(['table' => 'authors', 'connection' => $this->connection]);
        $belongsToMany = $table->belongsToMany('tag', $options);
        $this->assertInstanceOf(BelongsToMany::class, $belongsToMany);
        $this->assertSame($belongsToMany, $table->getAssociation('tag'));
        $this->assertSame('tag', $belongsToMany->getName());
        $this->assertSame('thing_id', $belongsToMany->getForeignKey());
        $this->assertEquals(['b' => 'c'], $belongsToMany->getConditions());
        $this->assertEquals(['foo' => 'asc'], $belongsToMany->getSort());
        $this->assertSame($table, $belongsToMany->getSource());
        $this->assertSame('things_tags', $belongsToMany->junction()->getTable());
    }

    /**
     * Test addAssociations()
     */
    public function testAddAssociations(): void
    {
        $params = [
            'belongsTo' => [
                'users' => ['foreignKey' => 'fake_id', 'conditions' => ['a' => 'b']],
            ],
            'hasOne' => ['profiles'],
            'hasMany' => ['authors'],
            'belongsToMany' => [
                'tags' => [
                    'joinTable' => 'things_tags',
                    'conditions' => [
                        'Tags.starred' => true,
                    ],
                ],
            ],
        ];

        $table = new Table(['table' => 'members']);
        $result = $table->addAssociations($params);
        $this->assertSame($table, $result);

        $associations = $table->associations();

        $belongsTo = $associations->get('users');
        $this->assertInstanceOf(BelongsTo::class, $belongsTo);
        $this->assertSame('users', $belongsTo->getName());
        $this->assertSame('fake_id', $belongsTo->getForeignKey());
        $this->assertEquals(['a' => 'b'], $belongsTo->getConditions());
        $this->assertSame($table, $belongsTo->getSource());

        $hasOne = $associations->get('profiles');
        $this->assertInstanceOf(HasOne::class, $hasOne);
        $this->assertSame('profiles', $hasOne->getName());

        $hasMany = $associations->get('authors');
        $this->assertInstanceOf(HasMany::class, $hasMany);
        $this->assertSame('authors', $hasMany->getName());

        $belongsToMany = $associations->get('tags');
        $this->assertInstanceOf(BelongsToMany::class, $belongsToMany);
        $this->assertSame('tags', $belongsToMany->getName());
        $this->assertSame('things_tags', $belongsToMany->junction()->getTable());
        $this->assertSame(['Tags.starred' => true], $belongsToMany->getConditions());
    }

    /**
     * Test basic multi row updates.
     */
    public function testUpdateAll(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $fields = ['username' => 'mark'];
        $result = $table->updateAll($fields, ['id <' => 4]);
        $this->assertSame(3, $result);

        $result = $table->find('all')
            ->select(['username'])
            ->orderBy(['id' => 'asc'])
            ->enableHydration(false)
            ->toArray();
        $expected = array_fill(0, 3, $fields);
        $expected[] = ['username' => 'garrett'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that exceptions from the Query bubble up.
     */
    public function testUpdateAllFailure(): void
    {
        $this->expectException(DatabaseException::class);
        $table = new class (['alias' => 'Users', 'table' => 'users']) extends Table {
            public function updateQuery(): UpdateQuery
            {
                return new class ($this) extends UpdateQuery {
                    public function execute(): StatementInterface
                    {
                        throw new DatabaseException('Not good');
                    }
                };
            }
        };

        $table->updateAll(['username' => 'mark'], []);
    }

    /**
     * Test deleting many records.
     */
    public function testDeleteAll(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $result = $table->deleteAll(['id <' => 4]);
        $this->assertSame(3, $result);

        $result = $table->find('all')->toArray();
        $this->assertCount(1, $result, 'Only one record should remain');
        $this->assertSame(4, $result[0]['id']);
    }

    /**
     * Test deleting many records with conditions using the alias
     */
    public function testDeleteAllAliasedConditions(): void
    {
        $table = new Table([
            'table' => 'users',
            'alias' => 'Managers',
            'connection' => $this->connection,
        ]);
        $result = $table->deleteAll(['Managers.id <' => 4]);
        $this->assertSame(3, $result);

        $result = $table->find('all')->toArray();
        $this->assertCount(1, $result, 'Only one record should remain');
        $this->assertSame(4, $result[0]['id']);
    }

    /**
     * Test that exceptions from the Query bubble up.
     */
    public function testDeleteAllFailure(): void
    {
        $this->expectException(DatabaseException::class);
        $table = new class (['alias' => 'Users', 'table' => 'users']) extends Table {
            public function deleteQuery(): DeleteQuery
            {
                return new class ($this) extends DeleteQuery {
                    public function execute(): StatementInterface
                    {
                        throw new DatabaseException('Not good');
                    }
                };
            }
        };

        $table->deleteAll(['id >' => 4]);
    }

    /**
     * Tests that array options are passed to the query object using applyOptions
     */
    public function testFindApplyOptions(): void
    {
        $table = new class (['alias' => 'Users', 'table' => 'users', 'connection' => $this->connection]) extends Table {
            public function selectQuery(): SelectQuery
            {
                return new class ($this) extends SelectQuery {
                    public function getOptions(): array
                    {
                        return [];
                    }

                    public function applyOptions(array $options)
                    {
                        if ($options !== ['fields' => ['a', 'b']]) {
                            throw new Exception('Options were not passed correctly');
                        }

                        return $options;
                    }
                };
            }
        };

        $this->assertInstanceOf(SelectQuery::class, $table->find('all', ...['fields' => ['a', 'b']]));
    }

    /**
     * Tests that extra arguments are passed to finders.
     */
    public function testFindTypedParameters(): void
    {
        $author = $this->getTableLocator()->get('Authors')->find('WithIdArgument', 2)->first();
        $this->assertSame(2, $author->id);

        $author = $this->getTableLocator()->get('Authors')->find('WithIdArgument', id: 2)->first();
        $this->assertSame(2, $author->id);
    }

    public function testFindTypedParameterCompatibility(): void
    {
        $articles = $this->fetchTable('Articles');
        $article = $articles->find('titled')->first();
        $this->assertNotEmpty($article);

        // Options arrays are deprecated but should work
        $article = $articles->find('titled', ['title' => 'Second Article'])->first();
        $this->assertNotEmpty($article);
        $this->assertEquals('Second Article', $article->title);

        // Named parameters should be compatible with options finders
        $article = $articles->find('titled', title: 'Second Article')->first();
        $this->assertNotEmpty($article);
        $this->assertEquals('Second Article', $article->title);
    }

    public function testFindForFinderVariadic(): void
    {
        $testTable = $this->fetchTable('Test');

        $testTable->find('variadic', foo: 'bar');
        $this->assertNull($testTable->first);
        $this->assertSame(['foo' => 'bar'], $testTable->variadic);

        $testTable->find('variadic', first: 'one', foo: 'bar');
        $this->assertSame('one', $testTable->first);
        $this->assertSame(['foo' => 'bar'], $testTable->variadic);

        $testTable->find('variadicOptions');
        $this->assertSame([], $testTable->variadicOptions);

        $testTable->find('variadicOptions', foo: 'bar');
        $this->assertSame(['foo' => 'bar'], $testTable->variadicOptions);
    }

    /**
     * Tests find('list')
     */
    public function testFindListNoHydration(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $table->setDisplayField('username');
        $query = $table->find('list')
            ->enableHydration(false)
            ->orderBy('id');
        $expected = [
            1 => 'mariano',
            2 => 'nate',
            3 => 'larry',
            4 => 'garrett',
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $table->find('list', fields: ['id', 'username'])
            ->enableHydration(false)
            ->orderBy('id');
        $expected = [
            1 => 'mariano',
            2 => 'nate',
            3 => 'larry',
            4 => 'garrett',
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $table->find('list', groupField: 'odd')
            ->select(['id', 'username', 'odd' => new QueryExpression('id % 2')])
            ->enableHydration(false)
            ->orderBy('id');
        $expected = [
            1 => [
                1 => 'mariano',
                3 => 'larry',
            ],
            0 => [
                2 => 'nate',
                4 => 'garrett',
            ],
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * Tests find('threaded')
     */
    public function testFindThreadedNoHydration(): void
    {
        $table = new Table([
            'table' => 'categories',
            'connection' => $this->connection,
        ]);
        $expected = [
            [
                'id' => 1,
                'parent_id' => 0,
                'name' => 'Category 1',
                'children' => [
                    [
                        'id' => 2,
                        'parent_id' => 1,
                        'name' => 'Category 1.1',
                        'children' => [
                            [
                                'id' => 7,
                                'parent_id' => 2,
                                'name' => 'Category 1.1.1',
                                'children' => [],
                            ],
                            [
                                'id' => 8,
                                'parent_id' => '2',
                                'name' => 'Category 1.1.2',
                                'children' => [],
                            ],
                        ],
                    ],
                    [
                        'id' => 3,
                        'parent_id' => '1',
                        'name' => 'Category 1.2',
                        'children' => [],
                    ],
                ],
            ],
            [
                'id' => 4,
                'parent_id' => 0,
                'name' => 'Category 2',
                'children' => [],
            ],
            [
                'id' => 5,
                'parent_id' => 0,
                'name' => 'Category 3',
                'children' => [
                    [
                        'id' => '6',
                        'parent_id' => '5',
                        'name' => 'Category 3.1',
                        'children' => [],
                    ],
                ],
            ],
        ];
        $results = $table->find('all')
            ->select(['id', 'parent_id', 'name'])
            ->enableHydration(false)
            ->find('threaded')
            ->toArray();

        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that finders can be stacked
     */
    public function testStackingFinders(): void
    {
        $table = new class (['alias' => 'Users']) extends Table {
            public bool $findIsCalled = false;
            public bool $findListIsCalled = false;

            public function find(string $type = 'all', mixed ...$args): SelectQuery
            {
                $this->findIsCalled = true;

                return new class ($this) extends SelectQuery {
                    public function addDefaultTypes(Table $table)
                    {
                        return $this;
                    }
                };
            }

            public function findList(
                SelectQuery $query,
                Closure|array|string|null $keyField = null,
                Closure|array|string|null $valueField = null,
                Closure|array|string|null $groupField = null,
                string $valueSeparator = ' '
            ): SelectQuery {
                $this->findListIsCalled = true;

                return $query;
            }
        };
        $table
            ->find('threaded', ['order' => ['name' => 'ASC']])
            ->find('list', keyField: 'id');
        $this->assertTrue($table->findIsCalled);
        $this->assertTrue($table->findListIsCalled);
    }

    /**
     * Tests find('threaded') with hydrated results
     */
    public function testFindThreadedHydrated(): void
    {
        $table = new Table([
            'table' => 'categories',
            'connection' => $this->connection,
        ]);
        $results = $table->find('all')
            ->find('threaded')
            ->select(['id', 'parent_id', 'name'])
            ->toArray();

        $this->assertSame(1, $results[0]->id);
        $expected = [
            'id' => 8,
            'parent_id' => 2,
            'name' => 'Category 1.1.2',
            'children' => [],
        ];
        $this->assertEquals($expected, $results[0]->children[0]->children[1]->toArray());
    }

    /**
     * Tests find('list') with hydrated records
     */
    public function testFindListHydrated(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $table->setDisplayField('username');
        $query = $table
            ->find('list', fields: ['id', 'username'])
            ->orderBy('id');
        $expected = [
            1 => 'mariano',
            2 => 'nate',
            3 => 'larry',
            4 => 'garrett',
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $table->find('list', groupField: 'odd')
            ->select(['id', 'username', 'odd' => new QueryExpression('id % 2')])
            ->enableHydration(true)
            ->orderBy('id');
        $expected = [
            1 => [
                1 => 'mariano',
                3 => 'larry',
            ],
            0 => [
                2 => 'nate',
                4 => 'garrett',
            ],
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * Test that find('list') only selects required fields.
     */
    public function testFindListSelectedFields(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $table->setDisplayField('username');

        $query = $table->find('list');
        $expected = ['id', 'username'];
        $this->assertSame($expected, $query->clause('select'));

        $query = $table->find('list', valueField: function ($row) {
            return $row->username;
        });
        $this->assertEmpty($query->clause('select'));

        $expected = ['odd' => new QueryExpression('id % 2'), 'id', 'username'];
        $query = $table->find('list', fields: $expected, groupField: 'odd');
        $this->assertSame($expected, $query->clause('select'));

        $articles = new Table([
            'table' => 'articles',
            'connection' => $this->connection,
        ]);

        $query = $articles->find('list', groupField: 'author_id');
        $expected = ['id', 'title', 'author_id'];
        $this->assertSame($expected, $query->clause('select'));

        $query = $articles->find('list', valueField: ['author_id', 'title'])
            ->orderBy('id');
        $expected = ['id', 'author_id', 'title'];
        $this->assertSame($expected, $query->clause('select'));

        $expected = [
            1 => '1 First Article',
            2 => '3 Second Article',
            3 => '1 Third Article',
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $articles->find('list', valueField: ['id', 'title'], valueSeparator: ' : ')
            ->orderBy('id');

        $expected = [
            1 => '1 : First Article',
            2 => '2 : Second Article',
            3 => '3 : Third Article',
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * Tests find(list) with backwards compatibile options
     */
    #[WithoutErrorHandler]
    public function testFindListArrayOptions(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $table->setDisplayField('username');
        $this->deprecated(function () use ($table): void {
            $query = $table
                ->find('list', ['fields' => ['id', 'username']])
                ->orderBy('id');
            $expected = [
                1 => 'mariano',
                2 => 'nate',
                3 => 'larry',
                4 => 'garrett',
            ];
            $this->assertSame($expected, $query->toArray());
        });
    }

    /**
     * test that find('list') does not auto add fields to select if using virtual properties
     */
    public function testFindListWithVirtualField(): void
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
            'entityClass' => VirtualUser::class,
        ]);
        $table->setDisplayField('bonus');

        $query = $table
            ->find('list')
            ->orderBy('id');
        $this->assertEmpty($query->clause('select'));

        $expected = [
            1 => 'bonus',
            2 => 'bonus',
            3 => 'bonus',
            4 => 'bonus',
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $table->find('list', groupField: 'odd');
        $this->assertEmpty($query->clause('select'));
    }

    /**
     * Test find('list') with value field from associated table
     */
    public function testFindListWithAssociatedTable(): void
    {
        $articles = new Table([
            'table' => 'articles',
            'connection' => $this->connection,
        ]);

        $articles->belongsTo('Authors');
        $query = $articles->find('list', valueField: 'author.name')
            ->contain(['Authors'])
            ->orderBy('articles.id');
        $this->assertEmpty($query->clause('select'));

        $expected = [
            1 => 'mariano',
            2 => 'larry',
            3 => 'mariano',
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * Test find('list') called with option array instead of named args for backwards compatility
     *
     * @return void
     * @deprecated
     */
    #[WithoutErrorHandler]
    public function testFindListWithArray(): void
    {
        $this->deprecated(function (): void {
            $articles = new Table([
                'table' => 'articles',
                'connection' => $this->connection,
            ]);

            $articles->belongsTo('Authors');
            $query = $articles->find('list', ['valueField' => 'author.name'])
                ->contain(['Authors'])
                ->orderBy('articles.id');
            $this->assertEmpty($query->clause('select'));

            $expected = [
                1 => 'mariano',
                2 => 'larry',
                3 => 'mariano',
            ];
            $this->assertSame($expected, $query->toArray());
        });
    }

    /**
     * Test the default entityClass.
     */
    public function testEntityClassDefault(): void
    {
        $table = new Table();
        $this->assertSame(Entity::class, $table->getEntityClass());
    }

    /**
     * Tests that using a simple string for entityClass will try to
     * load the class from the App namespace
     */
    public function testTableClassInApp(): void
    {
        $class = new class extends Entity {
        };

        if (!class_exists('TestApp\Model\Entity\TestUser')) {
            class_alias($class::class, 'TestApp\Model\Entity\TestUser');
        }

        $table = new Table();
        $this->assertSame($table, $table->setEntityClass('TestUser'));
        $this->assertSame('TestApp\Model\Entity\TestUser', $table->getEntityClass());
    }

    /**
     * Test that entity class inflection works for compound nouns
     */
    public function testEntityClassInflection(): void
    {
        $class = new class extends Entity {
        };

        if (!class_exists('TestApp\Model\Entity\CustomCookie')) {
            class_alias($class::class, 'TestApp\Model\Entity\CustomCookie');
        }

        $table = $this->getTableLocator()->get('CustomCookies');
        $this->assertSame('TestApp\Model\Entity\CustomCookie', $table->getEntityClass());

        if (!class_exists('TestApp\Model\Entity\Address')) {
            class_alias($class::class, 'TestApp\Model\Entity\Address');
        }

        $table = $this->getTableLocator()->get('Addresses');
        $this->assertSame('TestApp\Model\Entity\Address', $table->getEntityClass());
    }

    /**
     * Tests that using a simple string for entityClass will try to
     * load the class from the Plugin namespace when using plugin notation
     */
    public function testTableClassInPlugin(): void
    {
        $class = new class extends Entity {
        };

        if (!class_exists('MyPlugin\Model\Entity\SuperUser')) {
            class_alias($class::class, 'MyPlugin\Model\Entity\SuperUser');
        }

        $table = new Table();
        $this->assertSame($table, $table->setEntityClass('MyPlugin.SuperUser'));
        $this->assertSame(
            'MyPlugin\Model\Entity\SuperUser',
            $table->getEntityClass()
        );
    }

    /**
     * Tests that using a simple string for entityClass will throw an exception
     * when the class does not exist in the namespace
     */
    public function testTableClassNonExistent(): void
    {
        $this->expectException(MissingEntityException::class);
        $this->expectExceptionMessage('Entity class `FooUser` could not be found.');
        $table = new Table();
        $table->setEntityClass('FooUser');
    }

    /**
     * Tests getting the entityClass based on conventions for the entity
     * namespace
     */
    public function testTableClassConventionForAPP(): void
    {
        $table = new ArticlesTable();
        $this->assertSame(Article::class, $table->getEntityClass());
    }

    /**
     * Tests setting a entity class object using the setter method
     */
    public function testSetEntityClass(): void
    {
        $table = new Table();
        $entity = new class extends Entity {
        };
        $class = '\\' . $entity::class;
        $this->assertSame($table, $table->setEntityClass($class));
        $this->assertSame($class, $table->getEntityClass());
    }

    /**
     * Proves that associations, even though they are lazy loaded, will fetch
     * records using the correct table class and hydrate with the correct entity
     */
    public function testReciprocalBelongsToLoading(): void
    {
        $table = new ArticlesTable([
            'connection' => $this->connection,
        ]);
        $result = $table->find('all')->contain(['Authors'])->first();
        $this->assertInstanceOf(Author::class, $result->author);
    }

    /**
     * Proves that associations, even though they are lazy loaded, will fetch
     * records using the correct table class and hydrate with the correct entity
     */
    public function testReciprocalHasManyLoading(): void
    {
        $table = new ArticlesTable([
            'connection' => $this->connection,
        ]);
        $result = $table->find('all')->contain(['Authors' => ['Articles']])->first();
        $this->assertCount(2, $result->author->articles);
        foreach ($result->author->articles as $article) {
            $this->assertInstanceOf(Article::class, $article);
        }
    }

    /**
     * Tests that the correct table and entity are loaded for the join association in
     * a belongsToMany setup
     */
    public function testReciprocalBelongsToMany(): void
    {
        $table = new ArticlesTable([
            'connection' => $this->connection,
        ]);
        $result = $table->find('all')->contain(['Tags'])->first();
        $this->assertInstanceOf(Tag::class, $result->tags[0]);
        $this->assertInstanceOf(
            ArticlesTag::class,
            $result->tags[0]->_joinData
        );
    }

    /**
     * Tests that recently fetched entities are always clean
     */
    public function testFindCleanEntities(): void
    {
        $table = new ArticlesTable([
            'connection' => $this->connection,
        ]);
        $results = $table->find('all')->contain(['Tags', 'Authors'])->toArray();
        $this->assertCount(3, $results);
        foreach ($results as $article) {
            $this->assertFalse($article->isDirty('id'));
            $this->assertFalse($article->isDirty('title'));
            $this->assertFalse($article->isDirty('author_id'));
            $this->assertFalse($article->isDirty('body'));
            $this->assertFalse($article->isDirty('published'));
            $this->assertFalse($article->isDirty('author'));
            $this->assertFalse($article->author->isDirty('id'));
            $this->assertFalse($article->author->isDirty('name'));
            $this->assertFalse($article->isDirty('tag'));
            if ($article->tag) {
                $this->assertFalse($article->tag[0]->_joinData->isDirty('tag_id'));
            }
        }
    }

    /**
     * Tests that recently fetched entities are marked as not new
     */
    public function testFindPersistedEntities(): void
    {
        $table = new ArticlesTable([
            'connection' => $this->connection,
        ]);
        $results = $table->find('all')->contain(['Tags', 'Authors'])->toArray();
        $this->assertCount(3, $results);
        foreach ($results as $article) {
            $this->assertFalse($article->isNew());
            foreach ((array)$article->tag as $tag) {
                $this->assertFalse($tag->isNew());
                $this->assertFalse($tag->_joinData->isNew());
            }
        }
    }

    /**
     * Tests the exists function
     */
    public function testExists(): void
    {
        $table = $this->getTableLocator()->get('users');
        $this->assertTrue($table->exists(['id' => 1]));
        $this->assertFalse($table->exists(['id' => 501]));
        $this->assertTrue($table->exists(['id' => 3, 'username' => 'larry']));
    }

    /**
     * Test adding a behavior to a table.
     */
    public function testAddBehavior(): void
    {
        $behaviorReg = new BehaviorRegistry();

        $table = new Table([
            'table' => 'articles',
            'behaviors' => $behaviorReg,
        ]);
        $result = $table->addBehavior('Sluggable');
        $this->assertSame($table, $result);
    }

    /**
     * Test adding a plugin behavior to a table.
     */
    public function testAddBehaviorPlugin(): void
    {
        $table = new Table([
            'table' => 'articles',
        ]);
        $result = $table->addBehavior('TestPlugin.PersisterOne', ['some' => 'key']);

        $this->assertSame(['PersisterOne'], $result->behaviors()->loaded());
        $className = $result->behaviors()->get('PersisterOne')->getConfig('className');
        $this->assertSame('TestPlugin.PersisterOne', $className);
    }

    /**
     * Test adding a behavior that is a duplicate.
     */
    public function testAddBehaviorDuplicate(): void
    {
        $table = new Table(['table' => 'articles']);
        $this->assertSame($table, $table->addBehavior('Sluggable', ['test' => 'value']));
        $this->assertSame($table, $table->addBehavior('Sluggable', ['test' => 'value']));
        try {
            $table->addBehavior('Sluggable', ['thing' => 'thing']);
            $this->fail('No exception raised');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('The `Sluggable` alias has already been loaded', $e->getMessage());
        }
    }

    /**
     * Test removing a behavior from a table.
     */
    public function testRemoveBehavior(): void
    {
        $behaviorReg = new BehaviorRegistry(new Table(['table' => 'articles']));
        $behaviorReg->load('Sluggable', ['table' => 'articles']);

        $table = new Table([
            'table' => 'articles',
            'behaviors' => $behaviorReg,
        ]);
        $result = $table->removeBehavior('Sluggable');
        $this->assertSame($table, $result);
    }

    /**
     * Test removing a behavior from a table clears the method map for the behavior
     */
    public function testRemoveBehaviorMethodMapCleared(): void
    {
        $table = new Table(['table' => 'articles']);
        $table->addBehavior('Sluggable');
        $this->assertTrue($table->behaviors()->hasMethod('slugify'), 'slugify should be mapped');
        $this->assertSame('foo-bar', $table->slugify('foo bar'));

        $table->removeBehavior('Sluggable');
        $this->assertFalse($table->behaviors()->hasMethod('slugify'), 'slugify should not be callable');
    }

    /**
     * Test adding multiple behaviors to a table.
     */
    public function testAddBehaviors(): void
    {
        $table = new Table(['table' => 'comments']);
        $behaviors = [
            'Sluggable',
            'Timestamp' => [
                'events' => [
                    'Model.beforeSave' => [
                        'created' => 'new',
                        'updated' => 'always',
                    ],
                ],
            ],
        ];

        $this->assertSame($table, $table->addBehaviors($behaviors));
        $this->assertTrue($table->behaviors()->has('Sluggable'));
        $this->assertTrue($table->behaviors()->has('Timestamp'));
        $this->assertSame(
            $behaviors['Timestamp']['events'],
            $table->behaviors()->get('Timestamp')->getConfig('events')
        );
    }

    /**
     * Test getting a behavior instance from a table.
     */
    public function testBehaviors(): void
    {
        $table = $this->getTableLocator()->get('article');
        $result = $table->behaviors();
        $this->assertInstanceOf(BehaviorRegistry::class, $result);
    }

    /**
     * Test that the getBehavior() method retrieves a behavior from the table registry.
     */
    public function testGetBehavior(): void
    {
        $table = new Table(['table' => 'comments']);
        $table->addBehavior('Sluggable');
        $this->assertSame($table->behaviors()->get('Sluggable'), $table->getBehavior('Sluggable'));
    }

    /**
     * Test that the getBehavior() method will throw an exception when you try to
     * get a behavior that does not exist.
     */
    public function testGetBehaviorThrowsExceptionForMissingBehavior(): void
    {
        $table = new Table(['table' => 'comments']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `Sluggable` behavior is not defined on `' . $table::class . '`.');

        $this->assertFalse($table->hasBehavior('Sluggable'));
        $table->getBehavior('Sluggable');
    }

    /**
     * Ensure exceptions are raised on missing behaviors.
     */
    public function testAddBehaviorMissing(): void
    {
        $this->expectException(MissingBehaviorException::class);
        $table = $this->getTableLocator()->get('article');
        $this->assertNull($table->addBehavior('NopeNotThere'));
    }

    /**
     * Test mixin methods from behaviors.
     */
    public function testCallBehaviorMethod(): void
    {
        $table = $this->getTableLocator()->get('article');
        $table->addBehavior('Sluggable');
        $this->assertSame('some-value', $table->slugify('some value'));
    }

    /**
     * Test you can alias a behavior method
     */
    public function testCallBehaviorAliasedMethod(): void
    {
        $table = $this->getTableLocator()->get('article');
        $table->addBehavior('Sluggable', ['implementedMethods' => ['wednesday' => 'slugify']]);
        $this->assertSame('some-value', $table->wednesday('some value'));
    }

    /**
     * Test finder methods from behaviors.
     */
    public function testCallBehaviorFinder(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $table->addBehavior('Sluggable');

        $query = $table->find('noSlug');
        $this->assertInstanceOf(Query::class, $query);
        $this->assertNotEmpty($query->clause('where'));
    }

    /**
     * testCallBehaviorAliasedFinder
     */
    public function testCallBehaviorAliasedFinder(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $table->addBehavior('Sluggable', ['implementedFinders' => ['special' => 'findNoSlug']]);

        $query = $table->find('special');
        $this->assertInstanceOf(Query::class, $query);
        $this->assertNotEmpty($query->clause('where'));
    }

    /**
     * Tests that it is possible to insert a new row using the save method
     */
    public function testSaveNewEntity(): void
    {
        $entity = new Entity([
            'username' => 'superuser',
            'password' => 'root',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $table = $this->getTableLocator()->get('users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals($entity->id, self::$nextUserId);

        $row = $table->find()->where(['id' => self::$nextUserId])->first();
        $this->assertEquals($entity->toArray(), $row->toArray());
    }

    /**
     * Test that saving a new empty entity does nothing.
     */
    public function testSaveNewEmptyEntity(): void
    {
        $entity = new Entity();
        $table = $this->getTableLocator()->get('users');
        $this->assertFalse($table->save($entity));
    }

    /**
     * Test that saving a new empty entity does not call exists.
     */
    public function testSaveNewEntityNoExists(): void
    {
        $table = new class ([
            'connection' => $this->connection,
            'alias' => 'Users',
            'table' => 'users',
        ]) extends Table {
            public function exists($conditions): bool
            {
                throw new Exception('exists should not be called');
            }
        };
        $entity = $table->newEntity(['username' => 'mark']);
        $this->assertTrue($entity->isNew());
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Test that saving a new entity with a Primary Key set does call exists.
     */
    public function testSavePrimaryKeyEntityExists(): void
    {
        $this->skipIfSqlServer();
        $table = new class ([
            'connection' => $this->connection,
            'alias' => 'Users',
            'table' => 'users',
        ]) extends Table {
            public bool $existsCalled = false;
            public function exists($conditions): bool
            {
                $this->existsCalled = true;

                return true;
            }
        };
        $entity = $table->newEntity(['id' => 20, 'username' => 'mark']);
        $this->assertTrue($entity->isNew());
        $this->assertSame($entity, $table->save($entity));
        $this->assertTrue($table->existsCalled);
    }

    /**
     * Test that saving a new entity with a Primary Key set does not call exists when checkExisting is false.
     */
    public function testSavePrimaryKeyEntityNoExists(): void
    {
        $this->skipIfSqlServer();
        $table = new class ([
            'connection' => $this->connection,
            'alias' => 'Users',
            'table' => 'users',
        ]) extends Table {
            public function exists($conditions): bool
            {
                throw new Exception('exists should not be called');
            }
        };
        $entity = $table->newEntity(['id' => 20, 'username' => 'mark']);
        $this->assertTrue($entity->isNew());
        $this->assertSame($entity, $table->save($entity, ['checkExisting' => false]));
    }

    /**
     * Tests that saving an entity will filter out properties that
     * are not present in the table schema when saving
     */
    public function testSaveEntityOnlySchemaFields(): void
    {
        $entity = new Entity([
            'username' => 'superuser',
            'password' => 'root',
            'crazyness' => 'super crazy value',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $table = $this->getTableLocator()->get('users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals($entity->id, self::$nextUserId);

        $row = $table->find('all')->where(['id' => self::$nextUserId])->first();
        $entity->unset('crazyness');
        $this->assertEquals($entity->toArray(), $row->toArray());
    }

    /**
     * Tests that it is possible to modify data from the beforeSave callback
     */
    public function testBeforeSaveModifyData(): void
    {
        $table = $this->getTableLocator()->get('users');
        $data = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $listener = function ($event, EntityInterface $entity, $options) use ($data): void {
            $this->assertSame($data, $entity);
            $entity->set('password', 'foo');
        };
        $table->getEventManager()->on('Model.beforeSave', $listener);
        $this->assertSame($data, $table->save($data));
        $this->assertEquals($data->id, self::$nextUserId);
        $row = $table->find('all')->where(['id' => self::$nextUserId])->first();
        $this->assertSame('foo', $row->get('password'));
    }

    /**
     * Tests that it is possible to modify the options array in beforeSave
     */
    public function testBeforeSaveModifyOptions(): void
    {
        $table = $this->getTableLocator()->get('users');
        $data = new Entity([
            'username' => 'superuser',
            'password' => 'foo',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $listener1 = function ($event, $entity, $options): void {
            $options['crazy'] = true;
        };
        $listener2 = function ($event, $entity, $options): void {
            $this->assertTrue($options['crazy']);
        };
        $table->getEventManager()->on('Model.beforeSave', $listener1);
        $table->getEventManager()->on('Model.beforeSave', $listener2);
        $this->assertSame($data, $table->save($data));
        $this->assertEquals($data->id, self::$nextUserId);

        $row = $table->find('all')->where(['id' => self::$nextUserId])->first();
        $this->assertEquals($data->toArray(), $row->toArray());
    }

    /**
     * Tests that it is possible to stop the saving altogether, without implying
     * the save operation failed
     */
    public function testBeforeSaveStopEvent(): void
    {
        $table = $this->getTableLocator()->get('users');
        $data = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $listener = function (EventInterface $event, $entity) {
            $event->stopPropagation();

            return $entity;
        };
        $table->getEventManager()->on('Model.beforeSave', $listener);
        $this->assertSame($data, $table->save($data));
        $this->assertNull($data->id);
        $row = $table->find('all')->where(['id' => self::$nextUserId])->first();
        $this->assertNull($row);
    }

    /**
     * Tests that if beforeSave event is stopped and callback doesn't return any
     * value then save() returns false.
     */
    public function testBeforeSaveStopEventWithNoResult(): void
    {
        $table = $this->getTableLocator()->get('users');
        $data = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $listener = function (EventInterface $event, $entity): void {
            $event->stopPropagation();
        };
        $table->getEventManager()->on('Model.beforeSave', $listener);
        $this->assertFalse($table->save($data));
    }

    public function testBeforeSaveException(): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('The beforeSave callback must return `false` or `EntityInterface` instance. Got `int` instead.');

        $table = $this->getTableLocator()->get('users');
        $data = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $listener = function (EventInterface $event, $entity) {
            $event->stopPropagation();

            return 1;
        };
        $table->getEventManager()->on('Model.beforeSave', $listener);
        $table->save($data);
    }

    /**
     * Asserts that afterSave callback is called on successful save
     */
    public function testAfterSave(): void
    {
        $table = $this->getTableLocator()->get('users');
        $data = $table->get(1);

        $data->username = 'newusername';

        $called = false;
        $listener = function ($e, EntityInterface $entity, $options) use ($data, &$called): void {
            $this->assertSame($data, $entity);
            $this->assertTrue($entity->isDirty());
            $called = true;
        };
        $table->getEventManager()->on('Model.afterSave', $listener);

        $calledAfterCommit = false;
        $listenerAfterCommit = function ($e, EntityInterface $entity, $options) use ($data, &$calledAfterCommit): void {
            $this->assertSame($data, $entity);
            $this->assertTrue($entity->isDirty());
            $this->assertNotSame($data->get('username'), $data->getOriginal('username'));
            $calledAfterCommit = true;
        };
        $table->getEventManager()->on('Model.afterSaveCommit', $listenerAfterCommit);

        $this->assertSame($data, $table->save($data));
        $this->assertTrue($called);
        $this->assertTrue($calledAfterCommit);
    }

    /**
     * Asserts that afterSaveCommit is also triggered for non-atomic saves
     */
    public function testAfterSaveCommitForNonAtomic(): void
    {
        $table = $this->getTableLocator()->get('users');
        $data = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);

        $called = false;
        $listener = function ($e, $entity, $options) use ($data, &$called): void {
            $this->assertSame($data, $entity);
            $called = true;
        };
        $table->getEventManager()->on('Model.afterSave', $listener);

        $calledAfterCommit = false;
        $listenerAfterCommit = function ($e, $entity, $options) use (&$calledAfterCommit): void {
            $calledAfterCommit = true;
        };
        $table->getEventManager()->on('Model.afterSaveCommit', $listenerAfterCommit);

        $this->assertSame($data, $table->save($data, ['atomic' => false]));
        $this->assertEquals($data->id, self::$nextUserId);
        $this->assertTrue($called);
        $this->assertTrue($calledAfterCommit);
    }

    /**
     * Asserts the afterSaveCommit is not triggered if transaction is running.
     */
    public function testAfterSaveCommitWithTransactionRunning(): void
    {
        $table = $this->getTableLocator()->get('users');
        $data = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);

        $called = false;
        $listener = function ($e, $entity, $options) use (&$called): void {
            $called = true;
        };
        $table->getEventManager()->on('Model.afterSaveCommit', $listener);

        $this->connection->begin();
        $this->assertSame($data, $table->save($data));
        $this->assertFalse($called);
        $this->connection->commit();
    }

    /**
     * Asserts the afterSaveCommit is not triggered if transaction is running.
     */
    public function testAfterSaveCommitWithNonAtomicAndTransactionRunning(): void
    {
        $table = $this->getTableLocator()->get('users');
        $data = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);

        $called = false;
        $listener = function ($e, $entity, $options) use (&$called): void {
            $called = true;
        };
        $table->getEventManager()->on('Model.afterSaveCommit', $listener);

        $this->connection->begin();
        $this->assertSame($data, $table->save($data, ['atomic' => false]));
        $this->assertFalse($called);
        $this->connection->commit();
    }

    /**
     * Asserts that afterSave callback not is called on unsuccessful save
     */
    public function testAfterSaveNotCalled(): void
    {
        $table = new class (['alias' => 'Users', 'table' => 'users']) extends Table {
            public function insertQuery(): InsertQuery
            {
                return new class ($this) extends InsertQuery {
                    public function execute(): StatementInterface
                    {
                        $statement = Mockery::mock(StatementInterface::class);
                        $statement->shouldReceive('rowCount')->andReturn(0);

                        return $statement;
                    }
                };
            }
        };
        $data = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);

        $called = false;
        $listener = function ($e, $entity, $options) use (&$called): void {
            $called = true;
        };
        $table->getEventManager()->on('Model.afterSave', $listener);

        $calledAfterCommit = false;
        $listenerAfterCommit = function ($e, $entity, $options) use (&$calledAfterCommit): void {
            $calledAfterCommit = true;
        };
        $table->getEventManager()->on('Model.afterSaveCommit', $listenerAfterCommit);

        $this->assertFalse($table->save($data));
        $this->assertFalse($called);
        $this->assertFalse($calledAfterCommit);
    }

    /**
     * Asserts that afterSaveCommit callback is triggered only for primary table
     */
    public function testAfterSaveCommitTriggeredOnlyForPrimaryTable(): void
    {
        $entity = new Entity([
            'title' => 'A Title',
            'body' => 'A body',
        ]);
        $entity->author = new Entity([
            'name' => 'Jose',
        ]);

        $table = $this->getTableLocator()->get('articles');
        $table->belongsTo('authors');

        $calledForArticle = false;
        $listenerForArticle = function ($e, $entity, $options) use (&$calledForArticle): void {
            $calledForArticle = true;
        };
        $table->getEventManager()->on('Model.afterSaveCommit', $listenerForArticle);

        $calledForAuthor = false;
        $listenerForAuthor = function ($e, $entity, $options) use (&$calledForAuthor): void {
            $calledForAuthor = true;
        };
        $table->authors->getEventManager()->on('Model.afterSaveCommit', $listenerForAuthor);

        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->author->isNew());
        $this->assertTrue($calledForArticle);
        $this->assertFalse($calledForAuthor);
    }

    /**
     * Test that you cannot save rows without a primary key.
     */
    public function testSaveNewErrorOnNoPrimaryKey(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Cannot insert row in `users` table, it has no primary key');
        $entity = new Entity(['username' => 'superuser']);
        $table = $this->getTableLocator()->get('users', [
            'schema' => [
                'id' => ['type' => 'integer'],
                'username' => ['type' => 'string'],
            ],
        ]);
        $table->save($entity);
    }

    /**
     * Tests that save is wrapped around a transaction
     */
    public function testAtomicSave(): void
    {
        $config = ConnectionManager::getConfig('test');
        $connection = new class (['driver' => $this->connection->getDriver()] + $config) extends Connection {
            public bool $beginCalled = false;
            public bool $commitCalled = false;

            public function begin(): void
            {
                $this->beginCalled = true;
            }

            public function commit(): bool
            {
                $this->commitCalled = true;

                return true;
            }

            public function inTransaction(): bool
            {
                return true;
            }
        };

        $table = new Table(['table' => 'users', 'connection' => $connection]);
        $data = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $this->assertSame($data, $table->save($data));
    }

    /**
     * Tests that save will rollback the transaction in the case of an exception
     */
    public function testAtomicSaveRollback(): void
    {
        $this->expectException(PDOException::class);
        /** @var \Cake\Database\Connection|\PHPUnit\Framework\MockObject\MockObject $connection */
        $connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['begin', 'rollback'])
            ->setConstructorArgs([['driver' => $this->connection->getDriver()] + ConnectionManager::getConfig('test')])
            ->getMock();

        /** @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject $table */
        $table = $this->getMockBuilder(Table::class)
            ->onlyMethods(['insertQuery', 'getConnection'])
            ->setConstructorArgs([['table' => 'users']])
            ->getMock();
        $query = $this->getMockBuilder(InsertQuery::class)
            ->onlyMethods(['execute', 'addDefaultTypes'])
            ->setConstructorArgs([$table])
            ->getMock();
        $table->expects($this->any())->method('getConnection')
            ->willReturn($connection);

        $table->expects($this->once())->method('insertQuery')
            ->willReturn($query);

        $connection->expects($this->once())->method('begin');
        $connection->expects($this->once())->method('rollback');
        $query->expects($this->once())->method('execute')
            ->will($this->throwException(new PDOException()));

        $data = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $table->save($data);
    }

    /**
     * Tests that save will rollback the transaction in the case of an exception
     */
    public function testAtomicSaveRollbackOnFailure(): void
    {
        /** @var \Cake\Database\Connection|\PHPUnit\Framework\MockObject\MockObject $connection */
        $connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['begin', 'rollback'])
            ->setConstructorArgs([['driver' => $this->connection->getDriver()] + ConnectionManager::getConfig('test')])
            ->getMock();

        /** @var \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject $table */
        $table = $this->getMockBuilder(Table::class)
            ->onlyMethods(['insertQuery', 'getConnection', 'exists'])
            ->setConstructorArgs([['table' => 'users']])
            ->getMock();
        $query = $this->getMockBuilder(InsertQuery::class)
            ->onlyMethods(['execute', 'addDefaultTypes'])
            ->setConstructorArgs([$table])
            ->getMock();

        $table->expects($this->any())->method('getConnection')
            ->willReturn($connection);

        $table->expects($this->once())->method('insertQuery')
            ->willReturn($query);

        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);
        $connection->expects($this->once())->method('begin');
        $connection->expects($this->once())->method('rollback');
        $query->expects($this->once())
            ->method('execute')
            ->willReturn($statement);

        $data = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $table->save($data);
    }

    /**
     * Tests that only the properties marked as dirty are actually saved
     * to the database
     */
    public function testSaveOnlyDirtyProperties(): void
    {
        $entity = new Entity([
            'username' => 'superuser',
            'password' => 'root',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $entity->clean();
        $entity->setDirty('username', true);
        $entity->setDirty('created', true);
        $entity->setDirty('updated', true);

        $table = $this->getTableLocator()->get('users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals($entity->id, self::$nextUserId);

        $row = $table->find('all')->where(['id' => self::$nextUserId])->first();
        $entity->set('password', null);
        $this->assertEquals($entity->toArray(), $row->toArray());
    }

    /**
     * Tests that a recently saved entity is marked as clean
     */
    public function testASavedEntityIsClean(): void
    {
        $entity = new Entity([
            'username' => 'superuser',
            'password' => 'root',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $table = $this->getTableLocator()->get('users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isDirty('usermane'));
        $this->assertFalse($entity->isDirty('password'));
        $this->assertFalse($entity->isDirty('created'));
        $this->assertFalse($entity->isDirty('updated'));
    }

    /**
     * Tests that a recently saved entity is marked as not new
     */
    public function testASavedEntityIsNotNew(): void
    {
        $entity = new Entity([
            'username' => 'superuser',
            'password' => 'root',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ]);
        $table = $this->getTableLocator()->get('users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
    }

    /**
     * Tests that save can detect automatically if it needs to insert
     * or update a row
     */
    public function testSaveUpdateAuto(): void
    {
        $entity = new Entity([
            'id' => 2,
            'username' => 'baggins',
        ]);
        $table = $this->getTableLocator()->get('users');
        $original = $table->find('all')->where(['id' => 2])->first();
        $this->assertSame($entity, $table->save($entity));

        $row = $table->find('all')->where(['id' => 2])->first();
        $this->assertSame('baggins', $row->username);
        $this->assertEquals($original->password, $row->password);
        $this->assertEquals($original->created, $row->created);
        $this->assertEquals($original->updated, $row->updated);
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->isDirty('id'));
        $this->assertFalse($entity->isDirty('username'));
    }

    /**
     * Tests that beforeFind gets the correct isNew() state for the entity
     */
    public function testBeforeSaveGetsCorrectPersistance(): void
    {
        $entity = new Entity([
            'id' => 2,
            'username' => 'baggins',
        ]);
        $table = $this->getTableLocator()->get('users');
        $called = false;
        $listener = function (EventInterface $event, $entity) use (&$called): void {
            $this->assertFalse($entity->isNew());
            $called = true;
        };
        $table->getEventManager()->on('Model.beforeSave', $listener);
        $this->assertSame($entity, $table->save($entity));
        $this->assertTrue($called);
    }

    /**
     * Tests that marking an entity as already persisted will prevent the save
     * method from trying to infer the entity's actual status.
     */
    public function testSaveUpdateWithHint(): void
    {
        $table = new class ([
            'alias' => 'Users',
            'table' => 'users',
            'connection' => ConnectionManager::get('test'),
        ]) extends Table {
            public function exists($conditions): bool
            {
                throw new Exception('exists should not be called');
            }
        };
        $entity = new Entity([
            'id' => 2,
            'username' => 'baggins',
        ], ['markNew' => false]);
        $this->assertFalse($entity->isNew());
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests that when updating the primary key is not passed to the list of
     * attributes to change
     */
    public function testSaveUpdatePrimaryKeyNotModified(): void
    {
        $connection = new class (['driver' => $this->connection->getDriver()] + ConnectionManager::getConfig('test')) extends Connection {
            public function run(DbQuery $query): StatementInterface
            {
                $statement = Mockery::mock(StatementInterface::class);
                $statement->shouldReceive('errorCode')->andReturn('00000');

                return $statement;
            }
        };
        $table = $this->fetchTable('Users');
        $table->setConnection($connection);

        $entity = new Entity([
            'id' => 2,
            'username' => 'baggins',
        ], ['markNew' => false]);
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests that passing only the primary key to save will not execute any queries
     * but still return success
     */
    public function testUpdateNoChange(): void
    {
        $table = new class ([
            'alias' => 'Users',
            'table' => 'users',
            'connection' => $this->connection,
        ]) extends Table {
            public function query(): SelectQuery
            {
                throw new Exception('query should not be called');
            }
        };
        $entity = new Entity([
            'id' => 2,
        ], ['markNew' => false]);
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests that passing only the primary key to save will not execute any queries
     * but still return success
     */
    public function testUpdateDirtyNoActualChanges(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $entity = $table->get(1);

        $entity->setAccess('*', true);
        $entity->set($entity->toArray());
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests that failing to pass a primary key to save will result in exception
     */
    public function testUpdateNoPrimaryButOtherKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $table = new class ([
            'alias' => 'Users',
            'table' => 'users',
            'connection' => $this->connection,
        ]) extends Table {
            public function query(): SelectQuery
            {
                throw new Exception('query should not be called');
            }
        };
        $entity = new Entity([
            'username' => 'mariano',
        ], ['markNew' => false]);
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Test saveMany() with entities array
     */
    public function testSaveManyArray(): void
    {
        $entities = [
            new Entity(['name' => 'admad']),
            new Entity(['name' => 'dakota']),
        ];

        $timesCalled = 0;
        $listener = function ($e, $entity, $options) use (&$timesCalled): void {
            $timesCalled++;
        };
        $table = $this->getTableLocator()
            ->get('authors');

        $table->getEventManager()
            ->on('Model.afterSaveCommit', $listener);

        $result = $table->saveMany($entities);

        $this->assertSame($entities, $result);
        $this->assertTrue(isset($result[0]->id));
        foreach ($entities as $entity) {
            $this->assertFalse($entity->isNew());
        }
        $this->assertSame(2, $timesCalled);
    }

    /**
     * Test saveMany() with ResultSet instance
     */
    public function testSaveManyResultSet(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->Articles->setSort('Articles.id');

        $entities = $table->find()
            ->orderBy(['id' => 'ASC'])
            ->contain(['Articles'])
            ->all();
        $entities->first()->name = 'admad';
        $entities->first()->articles[0]->title = 'First Article Edited';

        $listener = function (EventInterface $event, EntityInterface $entity, $options): void {
            if ($entity->id === 1) {
                $this->assertTrue($entity->isDirty());

                $this->assertSame('admad', $entity->name);
                $this->assertSame('mariano', $entity->getOriginal('name'));

                $this->assertSame('First Article Edited', $entity->articles[0]->title);
                $this->assertSame('First Article', $entity->articles[0]->getOriginal('title'));
            } else {
                $this->assertFalse($entity->isDirty());
            }
        };
        $table = $this->getTableLocator()
            ->get('authors');

        $table->getEventManager()
            ->on('Model.afterSaveCommit', $listener);

        $result = $table->saveMany($entities);
        $this->assertSame($entities, $result);
        $this->assertFalse($result->first()->isDirty());
        $this->assertFalse($result->first()->articles[0]->isDirty());

        $first = $table->find()
            ->orderBy(['id' => 'ASC'])
            ->first();
        $this->assertSame('admad', $first->name);
    }

    /**
     * Test saveMany() with failed save
     */
    public function testSaveManyFailed(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $expectedCount = $table->find()->count();
        $entities = [
            new Entity(['name' => 'mark']),
            new Entity(['name' => 'jose']),
        ];
        $entities[1]->setErrors(['name' => ['message']]);
        $result = $table->saveMany($entities);

        $this->assertFalse($result);
        $this->assertSame($expectedCount, $table->find()->count());
        foreach ($entities as $entity) {
            $this->assertTrue($entity->isNew());
        }
    }

    /**
     * Test saveMany() with failed save due to an exception
     */
    public function testSaveManyFailedWithException(): void
    {
        $table = $this->getTableLocator()
            ->get('authors');
        $entities = [
            new Entity(['name' => 'mark']),
            new Entity(['name' => 'jose']),
        ];

        $table->getEventManager()->on('Model.beforeSave', function (EventInterface $event, EntityInterface $entity): void {
            if ($entity->name === 'jose') {
                throw new Exception('Oh noes');
            }
        });

        $this->expectException(Exception::class);

        try {
            $table->saveMany($entities);
        } finally {
            foreach ($entities as $entity) {
                $this->assertTrue($entity->isNew());
            }
        }
    }

    /**
     * Test saveManyOrFail() with entities array
     */
    public function testSaveManyOrFailArray(): void
    {
        $entities = [
            new Entity(['name' => 'admad']),
            new Entity(['name' => 'dakota']),
        ];

        $table = $this->getTableLocator()->get('authors');
        $result = $table->saveManyOrFail($entities);

        $this->assertSame($entities, $result);
        $this->assertTrue(isset($result[0]->id));
        foreach ($entities as $entity) {
            $this->assertFalse($entity->isNew());
        }
    }

    /**
     * Test saveManyOrFail() with ResultSet instance
     */
    public function testSaveManyOrFailResultSet(): void
    {
        $table = $this->getTableLocator()->get('authors');

        $entities = $table->find()
            ->orderBy(['id' => 'ASC'])
            ->all();
        $entities->first()->name = 'admad';

        $result = $table->saveManyOrFail($entities);
        $this->assertSame($entities, $result);

        $first = $table->find()
            ->orderBy(['id' => 'ASC'])
            ->first();
        $this->assertSame('admad', $first->name);
    }

    /**
     * Test saveManyOrFail() with failed save
     */
    public function testSaveManyOrFailFailed(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $entities = [
            new Entity(['name' => 'mark']),
            new Entity(['name' => 'jose']),
        ];
        $entities[1]->setErrors(['name' => ['message']]);

        $this->expectException(PersistenceFailedException::class);

        $table->saveManyOrFail($entities);
    }

    /**
     * Test simple delete.
     */
    public function testDelete(): void
    {
        $table = $this->getTableLocator()->get('users');
        $options = [
            'limit' => 1,
            'conditions' => [
                'username' => 'nate',
            ],
        ];
        $query = $table->find('all', ...$options);
        $entity = $query->first();
        $result = $table->delete($entity);
        $this->assertTrue($result);

        $query = $table->find('all', ...$options);
        $this->assertCount(0, $query->all(), 'Find should fail.');
    }

    /**
     * Test delete with dependent records
     */
    public function testDeleteDependent(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->Articles->setDependent(true);

        $entity = $table->get(1);
        $table->delete($entity);

        $articles = $table->getAssociation('Articles')->getTarget();
        $query = $articles->find('all', conditions: ['author_id' => $entity->id]);
        $this->assertNull($query->all()->first(), 'Should not find any rows.');
    }

    /**
     * Test delete with dependent records
     */
    public function testDeleteDependentHasMany(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->Articles
            ->setDependent(true)
            ->setCascadeCallbacks(true);

        $articles = $table->getAssociation('Articles')->getTarget();
        $articles->getEventManager()->on('Model.buildRules', function ($event, $rules): void {
            $rules->addDelete(function ($entity) {
                if ($entity->author_id === 3) {
                    return false;
                }

                return true;
            });
        });

        $entity = $table->get(1);
        $result = $table->delete($entity);
        $this->assertTrue($result);

        $query = $articles->find('all', conditions: ['author_id' => $entity->id]);
        $this->assertNull($query->all()->first(), 'Should not find any rows.');

        $entity = $table->get(3);
        $result = $table->delete($entity);
        $this->assertFalse($result);

        $query = $articles->find('all', conditions: ['author_id' => $entity->id]);
        $this->assertFalse($query->all()->isEmpty(), 'Should find some rows.');

        $table->associations()->get('Articles')->setCascadeCallbacks(false);
        $entity = $table->get(2);
        $result = $table->delete($entity);
        $this->assertTrue($result);
    }

    /**
     * Test delete with dependent = false does not cascade.
     */
    public function testDeleteNoDependentNoCascade(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->hasMany('article', [
            'dependent' => false,
        ]);

        $query = $table->find('all')->where(['id' => 1]);
        $entity = $query->first();
        $table->delete($entity);

        $articles = $table->getAssociation('Articles')->getTarget();
        $query = $articles->find('all')->where(['author_id' => $entity->id]);
        $this->assertCount(2, $query->all(), 'Should find rows.');
    }

    /**
     * Test delete with BelongsToMany
     */
    public function testDeleteBelongsToMany(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $table->belongsToMany('tag', [
            'foreignKey' => 'article_id',
            'joinTable' => 'articles_tags',
        ]);
        $query = $table->find('all')->where(['id' => 1]);
        $entity = $query->first();
        $table->delete($entity);

        $junction = $table->getAssociation('tag')->junction();
        $query = $junction->find('all')->where(['article_id' => 1]);
        $this->assertNull($query->all()->first(), 'Should not find any rows.');
    }

    /**
     * Test delete with dependent records belonging to an aliased
     * belongsToMany association.
     */
    public function testDeleteDependentAliased(): void
    {
        $Authors = $this->getTableLocator()->get('authors');
        $Authors->associations()->removeAll();
        $Articles = $this->getTableLocator()->get('articles');
        $Articles->associations()->removeAll();

        $Authors->hasMany('AliasedArticles', [
            'className' => 'Articles',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $Articles->belongsToMany('Tags');

        $author = $Authors->get(1);
        $result = $Authors->delete($author);

        $this->assertTrue($result);
    }

    /**
     * Test that cascading associations are deleted first.
     */
    public function testDeleteAssociationsCascadingCallbacksOrder(): void
    {
        $sections = $this->getTableLocator()->get('Sections');
        $members = $this->getTableLocator()->get('Members');
        $sectionsMembers = $this->getTableLocator()->get('SectionsMembers');

        $sections->belongsToMany('Members', [
            'joinTable' => 'sections_members',
        ]);
        $sections->hasMany('SectionsMembers', [
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $sectionsMembers->belongsTo('Members');
        $sectionsMembers->addBehavior('CounterCache', [
            'Members' => ['section_count'],
        ]);

        $member = $members->get(1);
        $this->assertSame(2, $member->section_count);

        $section = $sections->get(1);
        $sections->delete($section);

        $member = $members->get(1);
        $this->assertSame(1, $member->section_count);
    }

    /**
     * Test that primary record is not deleted if junction record deletion fails
     * when cascadeCallbacks is enabled.
     */
    public function testDeleteBelongsToManyDependentFailure(): void
    {
        $sections = $this->getTableLocator()->get('Sections');
        $sectionsMembers = $this->getTableLocator()->get('SectionsMembers');
        $sectionsMembers->getEventManager()->on('Model.buildRules', function ($event, $rules): void {
            $rules->addDelete(function () {
                return false;
            });
        });

        $sections->belongsToMany('Members', [
            'joinTable' => 'sections_members',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);

        $section = $sections->get(1, contain: 'Members');
        $this->assertSame(1, count($section->members));

        $this->assertFalse($sections->delete($section));

        $section = $sections->get(1, contain: 'Members');
        $this->assertSame(1, count($section->members));
    }

    /**
     * Test delete callbacks
     */
    public function testDeleteCallbacks(): void
    {
        $entity = new Entity(['id' => 1, 'name' => 'mark']);
        $options = new ArrayObject(['atomic' => true, 'checkRules' => false, '_primary' => true]);

        $mock = $this->getMockBuilder(EventManager::class)->getMock();

        $mock->expects($this->once())
            ->method('on');

        $mock->expects($this->exactly(4))
            ->method('dispatch')
            ->with(
                ...self::withConsecutive(
                    [$this->anything()],
                    [$this->callback(function (EventInterface $event) use ($entity, $options) {
                        return $event->getName() === 'Model.beforeDelete' &&
                        $event->getData() == ['entity' => $entity, 'options' => $options];
                    })],
                    [
                    $this->callback(function (EventInterface $event) use ($entity, $options) {
                        return $event->getName() === 'Model.afterDelete' &&
                            $event->getData() == ['entity' => $entity, 'options' => $options];
                    }),
                    ],
                    [$this->callback(function (EventInterface $event) use ($entity, $options) {
                        return $event->getName() === 'Model.afterDeleteCommit' &&
                        $event->getData() == ['entity' => $entity, 'options' => $options];
                    })]
                )
            );

        $table = $this->getTableLocator()->get('users', ['eventManager' => $mock]);
        $entity->setNew(false);
        $table->delete($entity, ['checkRules' => false]);
    }

    /**
     * Test afterDeleteCommit is also called for non-atomic delete
     */
    public function testDeleteCallbacksNonAtomic(): void
    {
        $table = $this->getTableLocator()->get('users');

        $data = $table->get(1);

        $called = false;
        $listener = function ($e, $entity, $options) use ($data, &$called): void {
            $this->assertSame($data, $entity);
            $called = true;
        };
        $table->getEventManager()->on('Model.afterDelete', $listener);

        $calledAfterCommit = false;
        $listenerAfterCommit = function ($e, $entity, $options) use (&$calledAfterCommit): void {
            $calledAfterCommit = true;
        };
        $table->getEventManager()->on('Model.afterDeleteCommit', $listenerAfterCommit);

        $table->delete($data, ['atomic' => false]);
        $this->assertTrue($called);
        $this->assertTrue($calledAfterCommit);
    }

    /**
     * Test that afterDeleteCommit is only triggered for primary table
     */
    public function testAfterDeleteCommitTriggeredOnlyForPrimaryTable(): void
    {
        $table = $this->getTableLocator()->get('authors');
        $table->Articles->setDependent(true);

        $called = false;
        $listener = function ($e, $entity, $options) use (&$called): void {
            $called = true;
        };
        $table->getEventManager()->on('Model.afterDeleteCommit', $listener);

        $called2 = false;
        $listener = function ($e, $entity, $options) use (&$called2): void {
            $called2 = true;
        };
        $table->Articles->getEventManager()->on('Model.afterDeleteCommit', $listener);

        $entity = $table->get(1);
        $this->assertTrue($table->delete($entity));

        $this->assertTrue($called);
        $this->assertFalse($called2);
    }

    /**
     * Test delete beforeDelete can abort the delete.
     */
    public function testDeleteBeforeDeleteAbort(): void
    {
        $entity = new Entity(['id' => 1, 'name' => 'mark']);
        $eventManager = new class extends EventManager
        {
            public function dispatch(EventInterface|string $event): EventInterface
            {
                $event->stopPropagation();

                return $event;
            }
        };

        $table = $this->getTableLocator()->get('users', ['eventManager' => $eventManager]);
        $entity->setNew(false);
        $result = $table->delete($entity, ['checkRules' => false]);
        $this->assertFalse($result);
    }

    /**
     * Test delete beforeDelete return result
     */
    public function testDeleteBeforeDeleteReturnResult(): void
    {
        $entity = new Entity(['id' => 1, 'name' => 'mark']);
        $eventManager = new class extends EventManager
        {
            public function dispatch(EventInterface|string $event): EventInterface
            {
                $event->stopPropagation();
                $event->setResult('got stopped');

                return $event;
            }
        };

        $table = $this->getTableLocator()->get('users', ['eventManager' => $eventManager]);
        $entity->setNew(false);
        $result = $table->delete($entity, ['checkRules' => false]);
        $this->assertTrue($result);
    }

    /**
     * Test deleting new entities does nothing.
     */
    public function testDeleteIsNew(): void
    {
        $entity = new Entity(['id' => 1, 'name' => 'mark']);
        $table = new class (['connection' => $this->connection]) extends Table {
            public function query(): Query
            {
                throw new Exception('query should not be called');
            }
        };

        $entity->setNew(true);
        $result = $table->delete($entity);
        $this->assertFalse($result);
    }

    /**
     * Test simple delete.
     */
    public function testDeleteMany(): void
    {
        $table = $this->getTableLocator()->get('users');
        $entities = $table->find()->limit(2)->all()->toArray();
        $this->assertCount(2, $entities);

        $result = $table->deleteMany($entities);
        $this->assertSame($entities, $result);

        $count = $table->find()->where(['id IN' => Hash::extract($entities, '{n}.id')])->count();
        $this->assertSame(0, $count, 'Find should not return > 0.');
    }

    /**
     * Test simple delete.
     */
    public function testDeleteManyOrFail(): void
    {
        $table = $this->getTableLocator()->get('users');
        $entities = $table->find()->limit(2)->all()->toArray();
        $this->assertCount(2, $entities);

        $result = $table->deleteManyOrFail($entities);
        $this->assertSame($entities, $result);

        $count = $table->find()->where(['id IN' => Hash::extract($entities, '{n}.id')])->count();
        $this->assertSame(0, $count, 'Find should not return > 0.');
    }

    /**
     * test hasField()
     */
    public function testHasField(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $this->assertFalse($table->hasField('nope'), 'Should not be there.');
        $this->assertTrue($table->hasField('title'), 'Should be there.');
        $this->assertTrue($table->hasField('body'), 'Should be there.');
    }

    /**
     * Tests that there exists a default validator
     */
    public function testValidatorDefault(): void
    {
        $table = new Table();
        $validator = $table->getValidator();
        $this->assertSame($table, $validator->getProvider('table'));
        $this->assertInstanceOf(Validator::class, $validator);
        $default = $table->getValidator('default');
        $this->assertSame($validator, $default);
    }

    /**
     * Tests that there exists a validator defined in a behavior.
     */
    public function testValidatorBehavior(): void
    {
        $table = new Table();
        $table->addBehavior('Validation');

        $validator = $table->getValidator('Behavior');
        $set = $validator->field('name');
        $this->assertArrayHasKey('behaviorRule', $set);
    }

    /**
     * Tests that a InvalidArgumentException is thrown if the custom validator method does not exist.
     */
    public function testValidatorWithMissingMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `Cake\ORM\Table::validationMissing()` validation method does not exists.');
        $table = new Table();
        $table->getValidator('missing');
    }

    /**
     * Tests that it is possible to set a custom validator under a name
     */
    public function testValidatorSetter(): void
    {
        $table = new Table();
        $validator = new Validator();
        $table->setValidator('other', $validator);
        $this->assertSame($validator, $table->getValidator('other'));
        $this->assertSame($table, $validator->getProvider('table'));
    }

    /**
     * Tests hasValidator method.
     */
    public function testHasValidator(): void
    {
        $table = new Table();
        $this->assertTrue($table->hasValidator('default'));
        $this->assertFalse($table->hasValidator('other'));

        $validator = new Validator();
        $table->setValidator('other', $validator);
        $this->assertTrue($table->hasValidator('other'));
    }

    /**
     * Tests that the source of an existing Entity is the same as a new one
     */
    public function testEntitySourceExistingAndNew(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $table = $this->getTableLocator()->get('TestPlugin.Authors');

        $existingAuthor = $table->find()->first();
        $newAuthor = $table->newEmptyEntity();

        $this->assertSame('TestPlugin.Authors', $existingAuthor->getSource());
        $this->assertSame('TestPlugin.Authors', $newAuthor->getSource());
    }

    /**
     * Tests that calling an entity with an empty array will run validation.
     */
    public function testNewEntityAndValidation(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->getValidator()->requirePresence('title');

        $entity = $table->newEntity([]);
        $errors = $entity->getErrors();
        $this->assertNotEmpty($errors['title']);
    }

    /**
     * Tests that creating an entity will not run any validation.
     */
    public function testCreateEntityAndValidation(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->getValidator()->requirePresence('title');

        $entity = $table->newEmptyEntity();
        $this->assertEmpty($entity->getErrors());
    }

    /**
     * Test magic findByXX method.
     */
    public function testMagicFindDefaultToAll(): void
    {
        $table = $this->getTableLocator()->get('Users');

        $result = $table->findByUsername('garrett');
        $this->assertInstanceOf(Query::class, $result);

        $expected = new QueryExpression(['Users.username' => 'garrett'], $this->usersTypeMap);
        $this->assertEquals($expected, $result->clause('where'));
    }

    /**
     * Test magic findByXX errors on missing arguments.
     */
    public function testMagicFindError(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Not enough arguments for magic finder. Got 0 required 1');
        $table = $this->getTableLocator()->get('Users');

        $table->findByUsername();
    }

    /**
     * Test magic findByXX errors on missing arguments.
     */
    public function testMagicFindErrorMissingField(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Not enough arguments for magic finder. Got 1 required 2');
        $table = $this->getTableLocator()->get('Users');

        $table->findByUsernameAndId('garrett');
    }

    /**
     * Test magic findByXX errors when there is a mix of or & and.
     */
    public function testMagicFindErrorMixOfOperators(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot mix "and" & "or" in a magic finder. Use find() instead.');
        $table = $this->getTableLocator()->get('Users');

        $table->findByUsernameAndIdOrPassword('garrett', 1, 'sekret');
    }

    /**
     * Test magic findByXX method.
     */
    public function testMagicFindFirstAnd(): void
    {
        $table = $this->getTableLocator()->get('Users');

        $result = $table->findByUsernameAndId('garrett', 4);
        $this->assertInstanceOf(Query::class, $result);

        $expected = new QueryExpression(['Users.username' => 'garrett', 'Users.id' => 4], $this->usersTypeMap);
        $this->assertEquals($expected, $result->clause('where'));
    }

    /**
     * Test magic findByXX method.
     */
    public function testMagicFindFirstOr(): void
    {
        $table = $this->getTableLocator()->get('Users');

        $result = $table->findByUsernameOrId('garrett', 4);
        $this->assertInstanceOf(Query::class, $result);

        $expected = new QueryExpression([], $this->usersTypeMap);
        $expected->add(
            [
                'OR' => [
                    'Users.username' => 'garrett',
                    'Users.id' => 4,
                ],
            ]
        );
        $this->assertEquals($expected, $result->clause('where'));
    }

    /**
     * Test magic findAllByXX method.
     */
    public function testMagicFindAll(): void
    {
        $table = $this->getTableLocator()->get('Articles');

        $result = $table->findAllByAuthorId(1);
        $this->assertInstanceOf(Query::class, $result);
        $this->assertNull($result->clause('limit'));

        $expected = new QueryExpression(['Articles.author_id' => 1], $this->articlesTypeMap);
        $this->assertEquals($expected, $result->clause('where'));
    }

    /**
     * Test magic findAllByXX method.
     */
    public function testMagicFindAllAnd(): void
    {
        $table = $this->getTableLocator()->get('Users');

        $result = $table->findAllByAuthorIdAndPublished(1, 'Y');
        $this->assertInstanceOf(Query::class, $result);
        $this->assertNull($result->clause('limit'));
        $expected = new QueryExpression(
            ['Users.author_id' => 1, 'Users.published' => 'Y'],
            $this->usersTypeMap
        );
        $this->assertEquals($expected, $result->clause('where'));
    }

    /**
     * Test magic findAllByXX method.
     */
    public function testMagicFindAllOr(): void
    {
        $table = $this->getTableLocator()->get('Users');

        $result = $table->findAllByAuthorIdOrPublished(1, 'Y');
        $this->assertInstanceOf(Query::class, $result);
        $this->assertNull($result->clause('limit'));
        $expected = new QueryExpression();
        $expected->getTypeMap()->setDefaults($this->usersTypeMap->toArray());
        $expected->add(
            ['or' => ['Users.author_id' => 1, 'Users.published' => 'Y']]
        );
        $this->assertEquals($expected, $result->clause('where'));
        $this->assertNull($result->clause('order'));
    }

    /**
     * Test the behavior method.
     */
    public function testBehaviorIntrospection(): void
    {
        $table = $this->getTableLocator()->get('users');

        $table->addBehavior('Timestamp');
        $this->assertTrue($table->hasBehavior('Timestamp'), 'should be true on loaded behavior');
        $this->assertFalse($table->hasBehavior('Tree'), 'should be false on unloaded behavior');
    }

    /**
     * Tests saving belongsTo association
     */
    public function testSaveBelongsTo(): void
    {
        $entity = new Entity([
            'title' => 'A Title',
            'body' => 'A body',
        ]);
        $entity->author = new Entity([
            'name' => 'Jose',
        ]);

        $table = $this->getTableLocator()->get('articles');
        $table->belongsTo('authors');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->author->isNew());
        $this->assertSame(5, $entity->author->id);
        $this->assertSame(5, $entity->get('author_id'));
    }

    /**
     * Tests saving hasOne association
     */
    public function testSaveHasOne(): void
    {
        $entity = new Entity([
            'name' => 'Jose',
        ]);
        $entity->article = new Entity([
            'title' => 'A Title',
            'body' => 'A body',
        ]);

        $table = $this->getTableLocator()->get('authors');
        $table->associations()->remove('Articles');
        $table->hasOne('Articles');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->article->isNew());
        $this->assertSame(4, $entity->article->id);
        $this->assertSame(5, $entity->article->get('author_id'));
        $this->assertFalse($entity->article->isDirty('author_id'));
    }

    /**
     * Tests saving associations only saves associations
     * if they are entities.
     */
    public function testSaveOnlySaveAssociatedEntities(): void
    {
        $entity = new Entity([
            'name' => 'Jose',
        ]);

        // Not an entity.
        $entity->article = [
            'title' => 'A Title',
            'body' => 'A body',
        ];

        $table = $this->getTableLocator()->get('authors');
        // $table->hasOne('articles');

        $table->save($entity);
        $this->assertFalse($entity->isNew());
        $this->assertIsArray($entity->article);
    }

    /**
     * Tests saving multiple entities in a hasMany association
     */
    public function testSaveHasMany(): void
    {
        $entity = new Entity([
            'name' => 'Jose',
        ]);
        $entity->articles = [
            new Entity([
                'title' => 'A Title',
                'body' => 'A body',
            ]),
            new Entity([
                'title' => 'Another Title',
                'body' => 'Another body',
            ]),
        ];

        $table = $this->getTableLocator()->get('authors');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->articles[0]->isNew());
        $this->assertFalse($entity->articles[1]->isNew());
        $this->assertSame(4, $entity->articles[0]->id);
        $this->assertSame(5, $entity->articles[1]->id);
        $this->assertSame(5, $entity->articles[0]->author_id);
        $this->assertSame(5, $entity->articles[1]->author_id);
    }

    /**
     * Tests overwriting hasMany associations in an integration scenario.
     */
    public function testSaveHasManyOverwrite(): void
    {
        $table = $this->getTableLocator()->get('authors');

        $entity = $table->get(3, contain: ['Articles']);
        $data = [
            'name' => 'big jose',
            'articles' => [
                [
                    'id' => 2,
                    'title' => 'New title',
                ],
            ],
        ];
        $entity = $table->patchEntity($entity, $data, ['associated' => 'Articles']);
        $this->assertSame($entity, $table->save($entity));

        $entity = $table->get(3, contain: ['Articles']);
        $this->assertSame('big jose', $entity->name, 'Author did not persist');
        $this->assertSame('New title', $entity->articles[0]->title, 'Article did not persist');
    }

    /**
     * Tests saving belongsToMany records
     */
    public function testSaveBelongsToMany(): void
    {
        $entity = new Entity([
            'title' => 'A Title',
            'body' => 'A body',
        ]);
        $entity->tags = [
            new Entity([
                'name' => 'Something New',
            ]),
            new Entity([
                'name' => 'Another Something',
            ]),
        ];
        $table = $this->getTableLocator()->get('Articles');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->tags[0]->isNew());
        $this->assertFalse($entity->tags[1]->isNew());
        $this->assertSame(4, $entity->tags[0]->id);
        $this->assertSame(5, $entity->tags[1]->id);
        $this->assertSame(4, $entity->tags[0]->_joinData->article_id);
        $this->assertSame(4, $entity->tags[1]->_joinData->article_id);
        $this->assertSame(4, $entity->tags[0]->_joinData->tag_id);
        $this->assertSame(5, $entity->tags[1]->_joinData->tag_id);
    }

    /**
     * Tests saving belongsToMany records when record exists.
     */
    public function testSaveBelongsToManyJoinDataOnExistingRecord(): void
    {
        $tags = $this->getTableLocator()->get('Tags');
        $table = $this->getTableLocator()->get('Articles');

        $entity = $table->find()->contain('Tags')->first();
        // not associated to the article already.
        $entity->tags[] = $tags->get(3);
        $entity->setDirty('tags', true);

        $this->assertSame($entity, $table->save($entity));

        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->tags[0]->isNew());
        $this->assertFalse($entity->tags[1]->isNew());
        $this->assertFalse($entity->tags[2]->isNew());

        $this->assertNotEmpty($entity->tags[0]->_joinData);
        $this->assertNotEmpty($entity->tags[1]->_joinData);
        $this->assertNotEmpty($entity->tags[2]->_joinData);
    }

    /**
     * Test that belongsToMany can be saved with _joinData data.
     */
    public function testSaveBelongsToManyJoinData(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $article = $articles->get(1, contain: ['Tags']);
        $data = [
            'tags' => [
                ['id' => 1, '_joinData' => ['highlighted' => 1]],
                ['id' => 3],
            ],
        ];
        $article = $articles->patchEntity($article, $data);
        $result = $articles->save($article);
        $this->assertSame($result, $article);
    }

    /**
     * Test to check that association condition are used when fetching existing
     * records to decide which records to unlink.
     */
    public function testPolymorphicBelongsToManySave(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->Tags->setThrough('PolymorphicTagged')
            ->setForeignKey('foreign_key')
            ->setConditions(['PolymorphicTagged.foreign_model' => 'Articles'])
            ->setSort(['PolymorphicTagged.position' => 'ASC']);

        $entity = $articles->get(1, contain: ['Tags']);
        $data = [
            'id' => 1,
            'tags' => [
                [
                    'id' => 1,
                    '_joinData' => [
                        'id' => 2,
                        'foreign_model' => 'Articles',
                        'position' => 2,
                    ],
                ],
                [
                    'id' => 2,
                    '_joinData' => [
                        'foreign_model' => 'Articles',
                        'position' => 1,
                    ],
                ],
            ],
        ];
        $entity = $articles->patchEntity($entity, $data, ['associated' => ['Tags._joinData']]);
        $entity = $articles->save($entity);

        $expected = [
            [
                'id' => 1,
                'tag_id' => 1,
                'foreign_key' => 1,
                'foreign_model' => 'Posts',
                'position' => 1,
            ],
            [
                'id' => 2,
                'tag_id' => 1,
                'foreign_key' => 1,
                'foreign_model' => 'Articles',
                'position' => 2,
            ],
            [
                'id' => 3,
                'tag_id' => 2,
                'foreign_key' => 1,
                'foreign_model' => 'Articles',
                'position' => 1,
            ],
        ];
        $result = $this->getTableLocator()->get('PolymorphicTagged')
            ->find('all', sort: ['id' => 'DESC'])
            ->enableHydration(false)
            ->toArray();
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests saving belongsToMany records can delete all links.
     */
    public function testSaveBelongsToManyDeleteAllLinks(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->Tags->setSaveStrategy('replace');

        $entity = $table->get(1, contain: 'Tags');
        $this->assertCount(2, $entity->tags, 'Fixture data did not change.');

        $entity->tags = [];
        $result = $table->save($entity);
        $this->assertSame($result, $entity);
        $this->assertSame([], $entity->tags, 'No tags on the entity.');

        $entity = $table->get(1, contain: 'Tags');
        $this->assertSame([], $entity->tags, 'No tags in the db either.');
    }

    /**
     * Tests saving belongsToMany records can delete some links.
     */
    public function testSaveBelongsToManyDeleteSomeLinks(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->Tags->setSaveStrategy('replace');

        $entity = $table->get(1, contain: 'Tags');
        $this->assertCount(2, $entity->tags, 'Fixture data did not change.');

        $tag = new Entity([
            'id' => 2,
        ]);
        $entity->tags = [$tag];
        $result = $table->save($entity);
        $this->assertSame($result, $entity);
        $this->assertCount(1, $entity->tags, 'Only one tag left.');
        $this->assertEquals($tag, $entity->tags[0]);

        $entity = $table->get(1, contain: 'Tags');
        $this->assertCount(1, $entity->tags, 'Only one tag in the db.');
        $this->assertEquals($tag->id, $entity->tags[0]->id);
    }

    /**
     * Test that belongsToMany ignores non-entity data.
     */
    public function testSaveBelongsToManyIgnoreNonEntityData(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $article = $articles->get(1, contain: ['Tags']);
        $article->tags = [
            '_ids' => [2, 1],
        ];
        $result = $articles->save($article);
        $this->assertSame($result, $article);
    }

    /**
     * Tests that saving a persisted and clean entity will is a no-op
     */
    public function testSaveCleanEntity(): void
    {
        $table = new class extends Table {
            // phpcs:ignore CakePHP.NamingConventions.ValidFunctionName.PublicWithUnderscore
            public function _processSave(EntityInterface $entity, $options): EntityInterface
            {
                throw new Exception('Should not be called');
            }
        };
        $entity = new Entity(
            ['id' => 'foo'],
            ['markNew' => false, 'markClean' => true]
        );
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Integration test to show how to append a new tag to an article
     */
    public function testBelongsToManyIntegration(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $article = $table->find('all')->where(['id' => 1])->contain(['Tags'])->first();
        $tags = $article->tags;
        $this->assertNotEmpty($tags);
        $tags[] = new Tag(['name' => 'Something New']);
        $article->tags = $tags;
        $this->assertSame($article, $table->save($article));
        $tags = $article->tags;
        $this->assertCount(3, $tags);
        $this->assertFalse($tags[2]->isNew());
        $this->assertSame(4, $tags[2]->id);
        $this->assertSame(1, $tags[2]->_joinData->article_id);
        $this->assertSame(4, $tags[2]->_joinData->tag_id);
    }

    /**
     * Tests that it is possible to do a deep save and control what associations get saved,
     * while having control of the options passed to each level of the save
     */
    public function testSaveDeepAssociationOptions(): void
    {
        $articles = $this->getMockBuilder(Table::class)
            ->onlyMethods(['_insert'])
            ->setConstructorArgs([['table' => 'articles', 'connection' => $this->connection]])
            ->getMock();
        $authors = $this->getMockBuilder(Table::class)
            ->onlyMethods(['_insert'])
            ->setConstructorArgs([['table' => 'authors', 'connection' => $this->connection]])
            ->getMock();
        $supervisors = $this->getMockBuilder(Table::class)
            ->onlyMethods(['_insert'])
            ->setConstructorArgs([[
                'table' => 'authors',
                'alias' => 'supervisors',
                'connection' => $this->connection,
            ]])
            ->getMock();
        $tags = $this->getMockBuilder(Table::class)
            ->onlyMethods(['_insert'])
            ->setConstructorArgs([['table' => 'tags', 'connection' => $this->connection]])
            ->getMock();

        $articles->belongsTo('authors', ['targetTable' => $authors]);
        $authors->hasOne('supervisors', ['targetTable' => $supervisors]);
        $supervisors->belongsToMany('tags', ['targetTable' => $tags]);

        $entity = new Entity([
            'title' => 'bar',
            'author' => new Entity([
                'name' => 'Juan',
                'supervisor' => new Entity(['name' => 'Marc']),
                'tags' => [
                    new Entity(['name' => 'foo']),
                ],
            ]),
        ]);
        $entity->setNew(true);
        $entity->author->setNew(true);
        $entity->author->supervisor->setNew(true);
        $entity->author->tags[0]->setNew(true);

        $articles->expects($this->once())
            ->method('_insert')
            ->with($entity, ['title' => 'bar'])
            ->willReturn($entity);

        $authors->expects($this->once())
            ->method('_insert')
            ->with($entity->author, ['name' => 'Juan'])
            ->willReturn($entity->author);

        $supervisors->expects($this->once())
            ->method('_insert')
            ->with($entity->author->supervisor, ['name' => 'Marc'])
            ->willReturn($entity->author->supervisor);

        $tags->expects($this->never())->method('_insert');

        $this->assertSame($entity, $articles->save($entity, [
            'associated' => [
                'authors' => [],
                'authors.supervisors' => [
                    'atomic' => false,
                    'associated' => false,
                ],
            ],
        ]));
    }

    public function testBelongsToFluentInterface(): void
    {
        $articles = new class (['table' => 'articles', 'connection' => $this->connection]) extends Table {
        };
        $authors = new class ([['table' => 'authors', 'connection' => $this->connection]]) extends Table {
        };

        try {
            $articles->belongsTo('Articles')
                ->setForeignKey('author_id')
                ->setTarget($authors)
                ->setBindingKey('id')
                ->setConditions([])
                ->setFinder('list')
                ->setProperty('authors')
                ->setJoinType('inner');
        } catch (BadMethodCallException) {
            $this->fail('Method chaining should be ok');
        }
        $this->assertSame('articles', $articles->getTable());
    }

    public function testHasOneFluentInterface(): void
    {
        $authors = new class (['table' => 'authors', 'connection' => $this->connection]) extends Table {
        };

        try {
            $authors->hasOne('Articles')
                ->setForeignKey('author_id')
                ->setDependent(true)
                ->setBindingKey('id')
                ->setConditions([])
                ->setCascadeCallbacks(true)
                ->setFinder('list')
                ->setStrategy('select')
                ->setProperty('authors')
                ->setJoinType('inner');
        } catch (BadMethodCallException) {
            $this->fail('Method chaining should be ok');
        }
        $this->assertSame('authors', $authors->getTable());
    }

    public function testHasManyFluentInterface(): void
    {
        $authors = new class (['table' => 'authors', 'connection' => $this->connection]) extends Table {
        };

        try {
            $authors->hasMany('Articles')
                ->setForeignKey('author_id')
                ->setDependent(true)
                ->setSort(['created' => 'DESC'])
                ->setBindingKey('id')
                ->setConditions([])
                ->setCascadeCallbacks(true)
                ->setFinder('list')
                ->setStrategy('select')
                ->setSaveStrategy('replace')
                ->setProperty('authors')
                ->setJoinType('inner');
        } catch (BadMethodCallException) {
            $this->fail('Method chaining should be ok');
        }
        $this->assertSame('authors', $authors->getTable());
    }

    public function testBelongsToManyFluentInterface(): void
    {
        $authors = new class (['table' => 'authors', 'connection' => $this->connection]) extends Table {
        };
        try {
            $authors->belongsToMany('Articles')
                ->setForeignKey('author_id')
                ->setDependent(true)
                ->setTargetForeignKey('article_id')
                ->setBindingKey('id')
                ->setConditions([])
                ->setFinder('list')
                ->setProperty('authors')
                ->setSource($authors)
                ->setStrategy('select')
                ->setSaveStrategy('append')
                ->setThrough('author_articles')
                ->setJoinType('inner');
        } catch (BadMethodCallException) {
            $this->fail('Method chaining should be ok');
        }
        $this->assertSame('authors', $authors->getTable());
    }

    /**
     * Integration test for linking entities with belongsToMany
     */
    public function testLinkBelongsToMany(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $tagsTable = $this->getTableLocator()->get('Tags');
        $source = ['source' => 'Tags'];
        $options = ['markNew' => false];

        $article = new Entity([
            'id' => 1,
        ], $options);

        $newTag = new Tag([
            'name' => 'Foo',
            'description' => 'Foo desc',
            'created' => null,
        ], $source);
        $tags[] = new Tag([
            'id' => 3,
        ], $options + $source);
        $tags[] = $newTag;

        $tagsTable->save($newTag);
        $table->getAssociation('Tags')->link($article, $tags);

        $this->assertEquals($article->tags, $tags);
        foreach ($tags as $tag) {
            $this->assertFalse($tag->isNew());
        }

        $article = $table->find('all')->where(['id' => 1])->contain(['Tags'])->first();
        $this->assertEquals($article->tags[2]->id, $tags[0]->id);
        $this->assertEqualsCanonicalizing($article->tags[3], $tags[1]);
    }

    /**
     * Integration test for linking entities with HasMany
     */
    public function testLinkHasMany(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $articles = $this->getTableLocator()->get('Articles');

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes',
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers',
                ],
            ]
        );

        $sizeArticles = count($newArticles);

        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $this->assertCount($sizeArticles, $authors->Articles->findAllByAuthorId($author->id));
        $this->assertCount($sizeArticles, $author->articles);
        $this->assertFalse($author->isDirty('articles'));
    }

    /**
     * Integration test for linking entities with HasMany combined with ReplaceSaveStrategy. It must append, not unlinking anything
     */
    public function testLinkHasManyReplaceSaveStrategy(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $articles = $this->getTableLocator()->get('Articles');

        $authors->Articles->setSaveStrategy('replace');

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes',
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers',
                ],
            ]
        );

        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $sizeArticles = count($newArticles);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'Nothing but the cake',
                    'body' => 'It is all that we need',
                ],
            ]
        );
        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $sizeArticles++;

        $this->assertCount($sizeArticles, $authors->Articles->findAllByAuthorId($author->id));
        $this->assertCount($sizeArticles, $author->articles);
        $this->assertFalse($author->isDirty('articles'));
    }

    /**
     * Integration test for linking entities with HasMany. The input contains already linked entities and they should not appeat duplicated
     */
    public function testLinkHasManyExisting(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $articles = $this->getTableLocator()->get('Articles');

        $authors->Articles->setSaveStrategy('replace');

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes',
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers',
                ],
            ]
        );

        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $sizeArticles = count($newArticles);

        $newArticles = array_merge(
            $author->articles,
            $articles->newEntities(
                [
                    [
                        'title' => 'Nothing but the cake',
                        'body' => 'It is all that we need',
                    ],
                ]
            )
        );
        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $sizeArticles++;

        $this->assertCount($sizeArticles, $authors->Articles->findAllByAuthorId($author->id));
        $this->assertCount($sizeArticles, $author->articles);
        $this->assertFalse($author->isDirty('articles'));
    }

    /**
     * Integration test for unlinking entities with HasMany. The association property must be cleaned
     */
    public function testUnlinkHasManyCleanProperty(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $articles = $this->getTableLocator()->get('Articles');

        $authors->Articles->setSaveStrategy('replace');

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes',
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers',
                ],
                [
                    'title' => 'Creamy cake recipe',
                    'body' => 'chocolate and cream',
                ],
            ]
        );

        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $sizeArticles = count($newArticles);

        $articlesToUnlink = [$author->articles[0], $author->articles[1]];

        $authors->Articles->unlink($author, $articlesToUnlink);

        $this->assertCount($sizeArticles - count($articlesToUnlink), $authors->Articles->findAllByAuthorId($author->id));
        $this->assertCount($sizeArticles - count($articlesToUnlink), $author->articles);
        $this->assertFalse($author->isDirty('articles'));
    }

    /**
     * Integration test for unlinking entities with HasMany. The association property must stay unchanged
     */
    public function testUnlinkHasManyNotCleanProperty(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $articles = $this->getTableLocator()->get('Articles');

        $authors->Articles->setSaveStrategy('replace');

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes',
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers',
                ],
                [
                    'title' => 'Creamy cake recipe',
                    'body' => 'chocolate and cream',
                ],
            ]
        );

        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $sizeArticles = count($newArticles);

        $articlesToUnlink = [$author->articles[0], $author->articles[1]];

        $authors->Articles->unlink($author, $articlesToUnlink, ['cleanProperty' => false]);

        $this->assertCount($sizeArticles - count($articlesToUnlink), $authors->Articles->findAllByAuthorId($author->id));
        $this->assertCount($sizeArticles, $author->articles);
        $this->assertFalse($author->isDirty('articles'));
    }

    /**
     * Integration test for unlinking entities with HasMany.
     * Checking that no error happens when the hasMany property is originally
     * null
     */
    public function testUnlinkHasManyEmpty(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $author = $authors->get(1);
        $article = $authors->Articles->get(1);

        $authors->Articles->unlink($author, [$article]);
        $this->assertNotEmpty($authors);
    }

    /**
     * Integration test for replacing entities which depend on their source entity with HasMany and failing transaction. False should be returned when
     * unlinking fails while replacing even when cascadeCallbacks is enabled
     */
    public function testReplaceHasManyOnErrorDependentCascadeCallbacks(): void
    {
        $articles = new class (['connection' => $this->connection, 'alias' => 'Articles', 'table' => 'articles']) extends Table {
            public function delete($entity, $options = []): bool
            {
                return false;
            }
        };

        $associations = new AssociationCollection();

        $hasManyArticles = new class ('articles', [
            'targetTable' => $articles,
            'foreignKey' => 'author_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]) extends HasMany {
        };

        $associations->add('Articles', $hasManyArticles);

        $authors = new Table([
            'connection' => $this->connection,
            'alias' => 'Authors',
            'table' => 'authors',
            'associations' => $associations,
        ]);
        $authors->Articles->setSource($authors);

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes',
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers',
                ],
            ]
        );

        $sizeArticles = count($newArticles);

        $this->assertTrue($authors->Articles->link($author, $newArticles));
        $this->assertEquals($authors->Articles->findAllByAuthorId($author->id)->count(), $sizeArticles);
        $this->assertCount($sizeArticles, $author->articles);

        $newArticles = array_merge(
            $author->articles,
            $articles->newEntities(
                [
                    [
                        'title' => 'Cheese cake recipe',
                        'body' => 'The secrets of mixing salt and sugar',
                    ],
                    [
                        'title' => 'Not another piece of cake',
                        'body' => 'This is the best',
                    ],
                ]
            )
        );
        unset($newArticles[0]);

        $this->assertFalse($authors->Articles->replace($author, $newArticles));
        $this->assertCount($sizeArticles, $authors->Articles->findAllByAuthorId($author->id));
    }

    /**
     * Integration test for replacing entities with HasMany and an empty target list. The transaction must be successful
     */
    public function testReplaceHasManyEmptyList(): void
    {
        $authors = new Table([
            'connection' => $this->connection,
            'alias' => 'Authors',
            'table' => 'authors',
        ]);
        $authors->hasMany('Articles');

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $authors->Articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes',
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers',
                ],
            ]
        );

        $sizeArticles = count($newArticles);

        $this->assertTrue($authors->Articles->link($author, $newArticles));
        $this->assertEquals($authors->Articles->findAllByAuthorId($author->id)->count(), $sizeArticles);
        $this->assertCount($sizeArticles, $author->articles);

        $newArticles = [];

        $this->assertTrue($authors->Articles->replace($author, $newArticles));
        $this->assertCount(0, $authors->Articles->findAllByAuthorId($author->id));
    }

    /**
     * Integration test for replacing entities with HasMany and no already persisted entities. The transaction must be successful.
     * Replace operation should prevent considering 0 changed records an error when they are not found in the table
     */
    public function testReplaceHasManyNoPersistedEntities(): void
    {
        $authors = new Table([
            'connection' => $this->connection,
            'alias' => 'Authors',
            'table' => 'authors',
        ]);
        $authors->hasMany('Articles');

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $authors->Articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes',
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers',
                ],
            ]
        );

        $authors->Articles->deleteAll(['1=1']);

        $sizeArticles = count($newArticles);

        $this->assertTrue($authors->Articles->link($author, $newArticles));
        $this->assertEquals($authors->Articles->findAllByAuthorId($author->id)->count(), $sizeArticles);
        $this->assertCount($sizeArticles, $author->articles);
        $this->assertTrue($authors->Articles->replace($author, $newArticles));
        $this->assertCount($sizeArticles, $authors->Articles->findAllByAuthorId($author->id));
    }

    /**
     * Integration test for replacing entities with HasMany.
     */
    public function testReplaceHasMany(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $articles = $this->getTableLocator()->get('Articles');

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes',
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers',
                ],
            ]
        );

        $sizeArticles = count($newArticles);

        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $this->assertEquals($authors->Articles->findAllByAuthorId($author->id)->count(), $sizeArticles);
        $this->assertCount($sizeArticles, $author->articles);

        $newArticles = array_merge(
            $author->articles,
            $articles->newEntities(
                [
                    [
                        'title' => 'Cheese cake recipe',
                        'body' => 'The secrets of mixing salt and sugar',
                    ],
                    [
                        'title' => 'Not another piece of cake',
                        'body' => 'This is the best',
                    ],
                ]
            )
        );
        unset($newArticles[0]);

        $this->assertTrue($authors->Articles->replace($author, $newArticles));
        $this->assertCount(count($newArticles), $author->articles);
        $this->assertEquals((new Collection($newArticles))->extract('title'), (new Collection($author->articles))->extract('title'));
    }

    /**
     * Integration test to show how to unlink a single record from a belongsToMany
     */
    public function testUnlinkBelongsToMany(): void
    {
        $table = $this->getTableLocator()->get('Articles');

        $article = $table->find('all')
            ->where(['id' => 1])
            ->contain(['Tags'])->first();

        $table->getAssociation('Tags')->unlink($article, [$article->tags[0]]);
        $this->assertCount(1, $article->tags);
        $this->assertSame(2, $article->tags[0]->get('id'));
        $this->assertFalse($article->isDirty('tags'));
    }

    /**
     * Integration test to show how to unlink multiple records from a belongsToMany
     */
    public function testUnlinkBelongsToManyMultiple(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $options = ['markNew' => false];

        $article = new Entity(['id' => 1], $options);
        $tags[] = new Tag(['id' => 1], $options);
        $tags[] = new Tag(['id' => 2], $options);

        $table->getAssociation('Tags')->unlink($article, $tags);
        $left = $table->find('all')->where(['id' => 1])->contain(['Tags'])->first();
        $this->assertEmpty($left->tags);
    }

    /**
     * Integration test to show how to unlink multiple records from a belongsToMany
     * providing some of the joint
     */
    public function testUnlinkBelongsToManyPassingJoint(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $options = ['markNew' => false];

        $article = new Entity(['id' => 1], $options);
        $tags[] = new Tag(['id' => 1], $options);
        $tags[] = new Tag(['id' => 2], $options);

        $tags[1]->_joinData = new Entity([
            'article_id' => 1,
            'tag_id' => 2,
        ], $options);

        $table->getAssociation('Tags')->unlink($article, $tags);
        $left = $table->find('all')->where(['id' => 1])->contain(['Tags'])->first();
        $this->assertEmpty($left->tags);
    }

    /**
     * Integration test to show how to replace records from a belongsToMany
     */
    public function testReplacelinksBelongsToMany(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $options = ['markNew' => false];

        $article = new Entity(['id' => 1], $options);
        $tags[] = new Tag(['id' => 2], $options);
        $tags[] = new Tag(['id' => 3], $options);
        $tags[] = new Tag(['name' => 'foo']);

        $table->getAssociation('Tags')->replaceLinks($article, $tags);
        $this->assertSame(2, $article->tags[0]->id);
        $this->assertSame(3, $article->tags[1]->id);
        $this->assertSame(4, $article->tags[2]->id);

        $article = $table->find('all')->where(['id' => 1])->contain(['Tags'])->first();
        $this->assertCount(3, $article->tags);
        $this->assertSame(2, $article->tags[0]->id);
        $this->assertSame(3, $article->tags[1]->id);
        $this->assertSame(4, $article->tags[2]->id);
        $this->assertSame('foo', $article->tags[2]->name);
    }

    /**
     * Integration test to show how remove all links from a belongsToMany
     */
    public function testReplacelinksBelongsToManyWithEmpty(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $options = ['markNew' => false];

        $article = new Entity(['id' => 1], $options);
        $tags = [];

        $table->getAssociation('Tags')->replaceLinks($article, $tags);
        $this->assertSame($tags, $article->tags);
        $article = $table->find('all')->where(['id' => 1])->contain(['Tags'])->first();
        $this->assertEmpty($article->tags);
    }

    /**
     * Integration test to show how to replace records from a belongsToMany
     * passing the joint property along in the target entity
     */
    public function testReplacelinksBelongsToManyWithJoint(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $options = ['markNew' => false];

        $article = new Entity(['id' => 1], $options);
        $tags[] = new Tag([
            'id' => 2,
            '_joinData' => new Entity([
                'article_id' => 1,
                'tag_id' => 2,
            ]),
        ], $options);
        $tags[] = new Tag(['id' => 3], $options);

        $table->getAssociation('Tags')->replaceLinks($article, $tags);
        $this->assertSame($tags, $article->tags);
        $article = $table->find('all')->where(['id' => 1])->contain(['Tags'])->first();
        $this->assertCount(2, $article->tags);
        $this->assertSame(2, $article->tags[0]->id);
        $this->assertSame(3, $article->tags[1]->id);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     */
    public function testOptionsBeingPassedToImplicitBelongsToManyDeletesUsingSaveReplace(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $tags = $articles->Tags;
        $tags->setSaveStrategy(BelongsToMany::SAVE_REPLACE)
            ->setDependent(true)
            ->setCascadeCallbacks(true);

        $actualOptions = null;
        $tags->junction()->getEventManager()->on(
            'Model.beforeDelete',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualOptions): void {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $article = $articles->get(1);
        $article->tags = [];
        $article->setDirty('tags', true);

        $result = $articles->save($article, ['foo' => 'bar']);
        $this->assertNotEmpty($result);

        $expected = [
            '_primary' => false,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'checkExisting' => true,
            '_cleanOnSuccess' => true,
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     */
    public function testOptionsBeingPassedToInternalSaveCallsUsingBelongsToManyLink(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $articles->Tags;

        $actualOptions = null;
        $tags->junction()->getEventManager()->on(
            'Model.beforeSave',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualOptions): void {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $article = $articles->get(1);

        $result = $tags->link($article, [$tags->getTarget()->get(2)], ['foo' => 'bar']);
        $this->assertTrue($result);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'checkExisting' => true,
            'associated' => [
                'Articles' => [],
                'Tags' => [],
            ],
            '_cleanOnSuccess' => true,
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     */
    public function testOptionsBeingPassedToInternalSaveCallsUsingBelongsToManyUnlink(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $articles->Tags;

        $actualOptions = null;
        $tags->junction()->getEventManager()->on(
            'Model.beforeDelete',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualOptions): void {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $article = $articles->get(1);

        $tags->unlink($article, [$tags->getTarget()->get(2)], ['foo' => 'bar']);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'cleanProperty' => true,
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     */
    public function testOptionsBeingPassedToInternalSaveAndDeleteCallsUsingBelongsToManyReplaceLinks(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $articles->Tags;

        $actualSaveOptions = null;
        $actualDeleteOptions = null;
        $tags->junction()->getEventManager()->on(
            'Model.beforeSave',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualSaveOptions): void {
                $actualSaveOptions = $options->getArrayCopy();
            }
        );
        $tags->junction()->getEventManager()->on(
            'Model.beforeDelete',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualDeleteOptions): void {
                $actualDeleteOptions = $options->getArrayCopy();
            }
        );

        $article = $articles->get(1);

        $result = $tags->replaceLinks(
            $article,
            [
                $tags->getTarget()->newEntity(['name' => 'new']),
                $tags->getTarget()->get(2),
            ],
            ['foo' => 'bar']
        );
        $this->assertTrue($result);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'checkExisting' => true,
            'associated' => [],
            '_cleanOnSuccess' => true,
        ];
        $this->assertEquals($expected, $actualSaveOptions);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
        ];
        $this->assertEquals($expected, $actualDeleteOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     */
    public function testOptionsBeingPassedToImplicitHasManyDeletesUsingSaveReplace(): void
    {
        $authors = $this->getTableLocator()->get('Authors');

        $articles = $authors->Articles;
        $articles->setSaveStrategy(HasMany::SAVE_REPLACE)
            ->setDependent(true)
            ->setCascadeCallbacks(true);

        $actualOptions = null;
        $articles->getTarget()->getEventManager()->on(
            'Model.beforeDelete',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualOptions): void {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $author = $authors->get(1);
        $author->articles = [];
        $author->setDirty('articles', true);

        $result = $authors->save($author, ['foo' => 'bar']);
        $this->assertNotEmpty($result);

        $expected = [
            '_primary' => false,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'checkExisting' => true,
            '_sourceTable' => $authors,
            '_cleanOnSuccess' => true,
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     */
    public function testOptionsBeingPassedToInternalSaveCallsUsingHasManyLink(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $articles = $authors->Articles;

        $actualOptions = null;
        $articles->getTarget()->getEventManager()->on(
            'Model.beforeSave',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualOptions): void {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $author = $authors->get(1);
        $author->articles = [];
        $author->setDirty('articles', true);

        $result = $articles->link($author, [$articles->getTarget()->get(2)], ['foo' => 'bar']);
        $this->assertTrue($result);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'checkExisting' => true,
            '_sourceTable' => $authors,
            'associated' => [
                'Authors' => [],
                'Tags' => [],
                'ArticlesTags' => [],
            ],
            '_cleanOnSuccess' => true,
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     */
    public function testOptionsBeingPassedToInternalSaveCallsUsingHasManyUnlink(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $articles = $authors->Articles;
        $articles->setDependent(true);
        $articles->setCascadeCallbacks(true);

        $actualOptions = null;
        $articles->getTarget()->getEventManager()->on(
            'Model.beforeDelete',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualOptions): void {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $author = $authors->get(1);
        $author->articles = [];
        $author->setDirty('articles', true);

        $articles->unlink($author, [$articles->getTarget()->get(1)], ['foo' => 'bar']);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'cleanProperty' => true,
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     */
    public function testOptionsBeingPassedToInternalSaveAndDeleteCallsUsingHasManyReplace(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $articles = $authors->Articles;
        $articles->setDependent(true);
        $articles->setCascadeCallbacks(true);

        $actualSaveOptions = null;
        $actualDeleteOptions = null;
        $articles->getTarget()->getEventManager()->on(
            'Model.beforeSave',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualSaveOptions): void {
                $actualSaveOptions = $options->getArrayCopy();
            }
        );
        $articles->getTarget()->getEventManager()->on(
            'Model.beforeDelete',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualDeleteOptions): void {
                $actualDeleteOptions = $options->getArrayCopy();
            }
        );

        $author = $authors->get(1);

        $result = $articles->replace(
            $author,
            [
                $articles->getTarget()->newEntity(['title' => 'new', 'body' => 'new']),
                $articles->getTarget()->get(1),
            ],
            ['foo' => 'bar']
        );
        $this->assertTrue($result);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'checkExisting' => true,
            '_sourceTable' => $authors,
            'associated' => [
                'Authors' => [],
                'Tags' => [],
                'ArticlesTags' => [],
            ],
            '_cleanOnSuccess' => true,
        ];
        $this->assertEquals($expected, $actualSaveOptions);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            '_sourceTable' => $authors,
        ];
        $this->assertEquals($expected, $actualDeleteOptions);
    }

    /**
     * Tests backwards compatibility of the the `$options` argument, formerly `$cleanProperty`.
     */
    public function testBackwardsCompatibilityForBelongsToManyUnlinkCleanPropertyOption(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $tags = $articles->Tags;

        $actualOptions = null;
        $tags->junction()->getEventManager()->on(
            'Model.beforeDelete',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualOptions): void {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $article = $articles->get(1);

        $tags->unlink($article, [$tags->getTarget()->get(1)], false);
        $this->assertArrayHasKey('cleanProperty', $actualOptions);
        $this->assertFalse($actualOptions['cleanProperty']);

        $actualOptions = null;
        $tags->unlink($article, [$tags->getTarget()->get(2)]);
        $this->assertArrayHasKey('cleanProperty', $actualOptions);
        $this->assertTrue($actualOptions['cleanProperty']);
    }

    /**
     * Tests backwards compatibility of the the `$options` argument, formerly `$cleanProperty`.
     */
    public function testBackwardsCompatibilityForHasManyUnlinkCleanPropertyOption(): void
    {
        $authors = $this->getTableLocator()->get('Authors');
        $articles = $authors->Articles;
        $articles->setDependent(true);
        $articles->setCascadeCallbacks(true);

        $actualOptions = null;
        $articles->getTarget()->getEventManager()->on(
            'Model.beforeDelete',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$actualOptions): void {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $author = $authors->get(1);
        $author->articles = [];
        $author->setDirty('articles', true);

        $articles->unlink($author, [$articles->getTarget()->get(1)], false);
        $this->assertArrayHasKey('cleanProperty', $actualOptions);
        $this->assertFalse($actualOptions['cleanProperty']);

        $actualOptions = null;
        $articles->unlink($author, [$articles->getTarget()->get(3)]);
        $this->assertArrayHasKey('cleanProperty', $actualOptions);
        $this->assertTrue($actualOptions['cleanProperty']);
    }

    /**
     * Tests that it is possible to call find with no arguments
     */
    public function testSimplifiedFind(): void
    {
        $table = new class (['alias' => 'Users', 'connection' => $this->connection, 'schema' => ['id' => ['type' => 'integer']]]) extends Table {
            public function findAll(SelectQuery $query): SelectQuery
            {
                return $query->where(['id' => 1]);
            }
        };

        $query = $table->find();
        /** @var QueryExpression $test */
        $exp = $query->clause('where');
        $exp->traverse(function ($expression) {
            $this->assertEquals(1, $expression->getValue());
            $this->assertEquals('id', $expression->getField());
        });
    }

    public static function providerForTestGet(): array
    {
        return [
            [['fields' => ['id']]],
            [['fields' => ['id'], 'cache' => null]],
        ];
    }

    /**
     * Test that get() will use the primary key for searching and return the first
     * entity found
     *
     * @param array $options
     */
    #[DataProvider('providerForTestGet')]
    public function testGet($options): void
    {
        $table = new class ([
            'alias' => 'Articles',
            'connection' => $this->connection,
            'schema' => [
                'id' => ['type' => 'integer'],
                'bar' => ['type' => 'integer'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['bar']]],
            ],
        ]) extends Table {
            public function selectQuery(): SelectQuery
            {
                return new class ($this) extends SelectQuery
                {
                    public function addDefaultTypes(Table $table)
                    {
                        return $this;
                    }

                    public function cache(false|Closure|string $key, CacheInterface|string $config = 'default')
                    {
                        throw new Exception('Should not be called');
                    }

                    public function firstOrFail(): mixed
                    {
                        return new Entity();
                    }
                };
            }
        };

        $entity = new Entity();
        $result = $table->get(10, ...$options);
        $this->assertEquals($entity, $result);
    }

    public static function providerForTestGetWithCache(): array
    {
        return [
            [
                ['fields' => ['id'], 'cache' => 'default'],
                'get-test-table_name-[10]', 'default', 10,
            ],
            [
                ['fields' => ['id'], 'cache' => 'default'],
                'get-test-table_name-["uuid"]', 'default', 'uuid',
            ],
            [
                ['fields' => ['id'], 'cache' => 'default'],
                'get-test-table_name-["2020-07-08T00:00:00+00:00"]', 'default', new DateTime('2020-07-08'),
            ],
            [
                ['fields' => ['id'], 'cache' => 'default', 'cacheKey' => 'custom_key'],
                'custom_key', 'default', 10,
            ],
        ];
    }

    /**
     * Test that get() will use the cache.
     *
     * @param array $options
     * @param string $cacheKey
     * @param string $cacheConfig
     * @param mixed $primaryKey
     */
    #[DataProvider('providerForTestGetWithCache')]
    public function testGetWithCache($options, $cacheKey, $cacheConfig, $primaryKey): void
    {
        $table = $this->getMockBuilder(Table::class)
            ->onlyMethods(['selectQuery'])
            ->setConstructorArgs([[
                'connection' => $this->connection,
                'schema' => [
                    'id' => ['type' => 'integer'],
                    'bar' => ['type' => 'integer'],
                    '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['bar']]],
                ],
            ]])
            ->getMock();
        $table->setTable('table_name');

        $query = $this->getMockBuilder(SelectQuery::class)
            ->onlyMethods(['addDefaultTypes', 'firstOrFail', 'where', 'cache', 'applyOptions'])
            ->setConstructorArgs([$table])
            ->getMock();

        $table->expects($this->once())->method('selectQuery')
            ->willReturn($query);

        $entity = new Entity();
        $query->expects($this->once())->method('applyOptions')
            ->with(['fields' => ['id']]);
        $query->expects($this->once())->method('where')
            ->with([$table->getAlias() . '.bar' => $primaryKey])
            ->willReturnSelf();
        $query->expects($this->once())->method('cache')
            ->with($cacheKey, $cacheConfig)
            ->willReturnSelf();
        $query->expects($this->once())->method('firstOrFail')
            ->willReturn($entity);

        $result = $table->get($primaryKey, ...$options);
        $this->assertSame($entity, $result);
    }

    /**
     * Test get() with options array.
     *
     * @return void
     */
    #[WithoutErrorHandler]
    public function testGetBackwardsCompatibility(): void
    {
        $this->deprecated(function (): void {
            $table = $this->getTableLocator()->get('Articles');
            $article = $table->get(1, ['contain' => 'Authors']);
            $this->assertNotEmpty($article->author);
        });
    }

    /**
     * Tests that get() will throw an exception if the record was not found
     */
    public function testGetNotFoundException(): void
    {
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionMessage('Record not found in table `articles`.');
        $table = new Table([
            'name' => 'Articles',
            'connection' => $this->connection,
            'table' => 'articles',
        ]);
        $table->get(10);
    }

    /**
     * Test that an exception is raised when there are not enough keys.
     */
    public function testGetExceptionOnNoData(): void
    {
        $this->expectException(InvalidPrimaryKeyException::class);
        $this->expectExceptionMessage('Record not found in table `articles` with primary key `[NULL]`.');
        $table = new Table([
            'name' => 'Articles',
            'connection' => $this->connection,
            'table' => 'articles',
        ]);
        $table->get(null);
    }

    /**
     * Test that an exception is raised when there are too many keys.
     */
    public function testGetExceptionOnTooMuchData(): void
    {
        $this->expectException(InvalidPrimaryKeyException::class);
        $this->expectExceptionMessage("Record not found in table `articles` with primary key `[1, 'two']`.");
        $table = new Table([
            'name' => 'Articles',
            'connection' => $this->connection,
            'table' => 'articles',
        ]);
        $table->get([1, 'two']);
    }

    /**
     * Tests that patchEntity delegates the task to the marshaller and passed
     * all associations
     */
    public function testPatchEntityMarshallerUsage(): void
    {
        $table = new class extends Table
        {
            public function marshaller(): Marshaller
            {
                return new class ($this) extends Marshaller
                {
                    public function merge(EntityInterface $entity, array $data, array $options = []): EntityInterface
                    {
                        if ($options['associated'] !== ['users', 'articles']) {
                            throw new Exception('Associated is not being passed correctly');
                        }

                        return $entity;
                    }
                };
            }
        };
        $table->belongsTo('users');
        $table->hasMany('articles');

        $entity = new Entity();
        $data = ['foo' => 'bar'];
        $this->assertInstanceOf(EntityInterface::class, $table->patchEntity($entity, $data));
    }

    /**
     * Tests patchEntity in a simple scenario. The tests for Marshaller cover
     * patch scenarios in more depth.
     */
    public function testPatchEntity(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $entity = new Entity(['title' => 'old title'], ['markNew' => false]);
        $data = ['title' => 'new title'];
        $entity = $table->patchEntity($entity, $data);

        $this->assertSame($data['title'], $entity->title);
        $this->assertFalse($entity->isNew(), 'entity should not be new.');
    }

    /**
     * Tests that patchEntities delegates the task to the marshaller and passed
     * all associations
     */
    public function testPatchEntitiesMarshallerUsage(): void
    {
        $table = new class extends Table
        {
            public function marshaller(): Marshaller
            {
                return new class ($this) extends Marshaller
                {
                    public function mergeMany(iterable $entities, array $data, array $options = []): array
                    {
                        if ($options['associated'] !== ['users', 'articles']) {
                            throw new Exception('Associated is not being passed correctly');
                        }

                        return $entities;
                    }
                };
            }
        };
        $table->belongsTo('users');
        $table->hasMany('articles');

        $entities = [new Entity()];
        $data = [['foo' => 'bar']];
        $this->assertNotEmpty($table->patchEntities($entities, $data));
    }

    /**
     * Tests patchEntities in a simple scenario. The tests for Marshaller cover
     * patch scenarios in more depth.
     */
    public function testPatchEntities(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $entities = $table->find()->limit(2)->toArray();

        $data = [
            ['id' => $entities[0]->id, 'title' => 'new title'],
            ['id' => $entities[1]->id, 'title' => 'new title2'],
        ];
        $entities = $table->patchEntities($entities, $data);
        foreach ($entities as $i => $entity) {
            $this->assertFalse($entity->isNew(), 'entities should not be new.');
            $this->assertSame($data[$i]['title'], $entity->title);
        }
    }

    /**
     * Tests __debugInfo
     */
    public function testDebugInfo(): void
    {
        $articles = $this->getTableLocator()->get('articles');
        $articles->addBehavior('Timestamp');
        $result = $articles->__debugInfo();
        $expected = [
            'registryAlias' => 'articles',
            'table' => 'articles',
            'alias' => 'articles',
            'entityClass' => Article::class,
            'associations' => ['Authors', 'Tags', 'ArticlesTags'],
            'behaviors' => ['Timestamp'],
            'defaultConnection' => 'default',
            'connectionName' => 'test',
        ];
        $this->assertEquals($expected, $result);

        $articles = $this->getTableLocator()->get('Foo.Articles');
        $result = $articles->__debugInfo();
        $expected = [
            'registryAlias' => 'Foo.Articles',
            'table' => 'articles',
            'alias' => 'Articles',
            'entityClass' => Entity::class,
            'associations' => [],
            'behaviors' => [],
            'defaultConnection' => 'default',
            'connectionName' => 'test',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that findOrCreate creates a new entity, and then finds that entity.
     */
    public function testFindOrCreateNewEntity(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $callbackExecuted = false;
        $firstArticle = $articles->findOrCreate(['title' => 'Not there'], function ($article) use (&$callbackExecuted): void {
            $this->assertInstanceOf(EntityInterface::class, $article);
            $article->body = 'New body';
            $callbackExecuted = true;
        });
        $this->assertTrue($callbackExecuted);
        $this->assertFalse($firstArticle->isNew());
        $this->assertNotNull($firstArticle->id);
        $this->assertSame('Not there', $firstArticle->title);
        $this->assertSame('New body', $firstArticle->body);

        $secondArticle = $articles->findOrCreate(['title' => 'Not there'], function ($article): void {
            $this->fail('Should not be called for existing entities.');
        });
        $this->assertFalse($secondArticle->isNew());
        $this->assertNotNull($secondArticle->id);
        $this->assertSame('Not there', $secondArticle->title);
        $this->assertEquals($firstArticle->id, $secondArticle->id);
    }

    /**
     * Test that findOrCreate finds fixture data.
     */
    public function testFindOrCreateExistingEntity(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $article = $articles->findOrCreate(['title' => 'First Article'], function ($article): void {
            $this->fail('Should not be called for existing entities.');
        });
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertSame('First Article', $article->title);
    }

    /**
     * Test that findOrCreate uses the search conditions as defaults for new entity.
     */
    public function testFindOrCreateDefaults(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $callbackExecuted = false;
        $article = $articles->findOrCreate(
            ['author_id' => 2, 'title' => 'First Article'],
            function ($article) use (&$callbackExecuted): void {
                $this->assertInstanceOf(EntityInterface::class, $article);
                $article->set(['published' => 'N', 'body' => 'New body']);
                $callbackExecuted = true;
            }
        );
        $this->assertTrue($callbackExecuted);
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertSame('First Article', $article->title);
        $this->assertSame('New body', $article->body);
        $this->assertSame('N', $article->published);
        $this->assertSame(2, $article->author_id);

        $query = $articles->find()->where(['author_id' => 2, 'title' => 'First Article']);
        $article = $articles->findOrCreate($query);
        $this->assertSame('First Article', $article->title);
        $this->assertSame(2, $article->author_id);
        $this->assertFalse($article->isNew());
    }

    /**
     * Test that findOrCreate adds new entity without using a callback.
     */
    public function testFindOrCreateNoCallable(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $article = $articles->findOrCreate(['title' => 'Just Something New']);
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertSame('Just Something New', $article->title);
    }

    /**
     * Test that findOrCreate executes search conditions as a callable.
     */
    public function testFindOrCreateSearchCallable(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $calledOne = false;
        $calledTwo = false;
        $article = $articles->findOrCreate(function ($query) use (&$calledOne): void {
            $this->assertInstanceOf(Query::class, $query);
            $query->where(['title' => 'Something Else']);
            $calledOne = true;
        }, function ($article) use (&$calledTwo): void {
            $this->assertInstanceOf(EntityInterface::class, $article);
            $article->title = 'Set Defaults Here';
            $calledTwo = true;
        });
        $this->assertTrue($calledOne);
        $this->assertTrue($calledTwo);
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertSame('Set Defaults Here', $article->title);
    }

    /**
     * Test that findOrCreate options disable defaults.
     */
    public function testFindOrCreateNoDefaults(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $article = $articles->findOrCreate(['title' => 'A New Article', 'published' => 'Y'], function ($article): void {
            $this->assertInstanceOf(EntityInterface::class, $article);
            $article->title = 'A Different Title';
        }, ['defaults' => false]);
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertSame('A Different Title', $article->title);
        $this->assertNull($article->published, 'Expected Null since defaults are disabled.');
    }

    /**
     * Test that findOrCreate executes callable inside transaction.
     */
    public function testFindOrCreateTransactions(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->getEventManager()->on('Model.afterSaveCommit', function (EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
            $entity->afterSaveCommit = true;
        });

        $article = $articles->findOrCreate(function ($query): void {
            $this->assertInstanceOf(Query::class, $query);
            $query->where(['title' => 'Find Something New']);
            $this->assertTrue($this->connection->inTransaction());
        }, function ($article): void {
            $this->assertInstanceOf(EntityInterface::class, $article);
            $article->title = 'Success';
            $this->assertTrue($this->connection->inTransaction());
        });
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertSame('Success', $article->title);
        $this->assertTrue($article->afterSaveCommit);
    }

    /**
     * Test that findOrCreate executes callable without transaction.
     */
    public function testFindOrCreateNoTransaction(): void
    {
        $articles = $this->getTableLocator()->get('Articles');

        $article = $articles->findOrCreate(function (SelectQuery $query): void {
            $this->assertInstanceOf(SelectQuery::class, $query);
            $query->where(['title' => 'Find Something New']);
            $this->assertFalse($this->connection->inTransaction());
        }, function ($article): void {
            $this->assertInstanceOf(EntityInterface::class, $article);
            $this->assertFalse($this->connection->inTransaction());
            $article->title = 'Success';
        }, ['atomic' => false]);
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertSame('Success', $article->title);
    }

    /**
     * Test that findOrCreate throws a PersistenceFailedException when it cannot save
     * an entity created from $search
     */
    public function testFindOrCreateWithInvalidEntity(): void
    {
        $this->expectException(PersistenceFailedException::class);
        $this->expectExceptionMessage(
            'Entity findOrCreate failure. ' .
            'Found the following errors (title._empty: "This field cannot be left empty").'
        );

        $articles = $this->getTableLocator()->get('Articles');
        $validator = new Validator();
        $validator->notEmptyString('title');
        $articles->setValidator('default', $validator);

        $articles->findOrCreate(['title' => '']);
    }

    /**
     * Test that findOrCreate allows patching of all $search keys
     */
    public function testFindOrCreateAccessibleFields(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->setEntityClass(ProtectedEntity::class);
        $validator = new Validator();
        $validator->notBlank('title');
        $articles->setValidator('default', $validator);

        $article = $articles->findOrCreate(['title' => 'test']);
        $this->assertInstanceOf(ProtectedEntity::class, $article);
        $this->assertSame('test', $article->title);
    }

    /**
     * Test that findOrCreate cannot accidentally bypass required validation.
     */
    public function testFindOrCreatePartialValidation(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->setEntityClass(ProtectedEntity::class);
        $validator = new Validator();
        $validator->notBlank('title')->requirePresence('title', 'create');
        $validator->notBlank('body')->requirePresence('body', 'create');
        $articles->setValidator('default', $validator);

        $this->expectException(PersistenceFailedException::class);
        $this->expectExceptionMessage(
            'Entity findOrCreate failure. ' .
            'Found the following errors (title._required: "This field is required").'
        );

        $articles->findOrCreate(['body' => 'test']);
    }

    /**
     * Test that creating a table fires the initialize event.
     */
    public function testInitializeEvent(): void
    {
        $count = 0;
        $cb = function (EventInterface $event) use (&$count): void {
            $count++;
        };
        EventManager::instance()->on('Model.initialize', $cb);
        $this->getTableLocator()->get('Articles');

        $this->assertSame(1, $count, 'Callback should be called');
        EventManager::instance()->off('Model.initialize', $cb);
    }

    /**
     * Tests the hasFinder method
     */
    public function testHasFinder(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $table->addBehavior('Sluggable');

        $this->assertTrue($table->hasFinder('list'));
        $this->assertTrue($table->hasFinder('noSlug'));
        $this->assertFalse($table->hasFinder('noFind'));
    }

    /**
     * Tests that calling validator() trigger the buildValidator event
     */
    public function testBuildValidatorEvent(): void
    {
        $count = 0;
        $cb = function (EventInterface $event) use (&$count): void {
            $count++;
        };
        EventManager::instance()->on('Model.buildValidator', $cb);
        $articles = $this->getTableLocator()->get('Articles');
        $articles->getValidator();
        $this->assertSame(1, $count, 'Callback should be called');

        $articles->getValidator();
        $this->assertSame(1, $count, 'Callback should be called only once');
    }

    /**
     * Tests the validateUnique method with different combinations
     */
    public function testValidateUnique(): void
    {
        $table = $this->getTableLocator()->get('Users');
        $validator = new Validator();
        $validator->add('username', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);
        $validator->setProvider('table', $table);

        $data = ['username' => ['larry', 'notthere']];
        $this->assertNotEmpty($validator->validate($data));

        $data = ['username' => 'larry'];
        $this->assertNotEmpty($validator->validate($data));

        $data = ['username' => 'jose'];
        $this->assertEmpty($validator->validate($data));

        $data = ['username' => 'larry', 'id' => 3];
        $this->assertEmpty($validator->validate($data, false));

        $data = ['username' => 'larry', 'id' => 3];
        $this->assertNotEmpty($validator->validate($data));

        $data = ['username' => 'larry'];
        $this->assertNotEmpty($validator->validate($data, false));

        $validator->add('username', 'unique', [
            'rule' => 'validateUnique', 'provider' => 'table',
        ]);
        $data = ['username' => 'larry'];
        $this->assertNotEmpty($validator->validate($data, false));
    }

    /**
     * Tests the validateUnique method with scope
     */
    public function testValidateUniqueScope(): void
    {
        $table = $this->getTableLocator()->get('Users');
        $validator = new Validator();
        $validator->add('username', 'unique', [
            'rule' => ['validateUnique', ['derp' => 'erp', 'scope' => 'id']],
            'provider' => 'table',
        ]);
        $validator->setProvider('table', $table);
        $data = ['username' => 'larry', 'id' => 3];
        $this->assertNotEmpty($validator->validate($data));

        $data = ['username' => 'larry', 'id' => 1];
        $this->assertEmpty($validator->validate($data));

        $data = ['username' => 'jose'];
        $this->assertEmpty($validator->validate($data));
    }

    /**
     * Tests the validateUnique method with options
     */
    public function testValidateUniqueMultipleNulls(): void
    {
        $entity = new Entity([
            'id' => 9,
            'site_id' => 1,
            'author_id' => null,
            'title' => 'Null title',
        ]);

        $table = $this->getTableLocator()->get('SiteArticles');
        $table->save($entity);

        $validator = new Validator();
        $validator->add('site_id', 'unique', [
            'rule' => [
                'validateUnique',
                [
                    'allowMultipleNulls' => false,
                    'scope' => ['author_id'],
                ],
            ],
            'provider' => 'table',
            'message' => 'Must be unique.',
        ]);
        $validator->setProvider('table', $table);

        $data = ['site_id' => 1, 'author_id' => null, 'title' => 'Null dupe'];
        $expected = ['site_id' => ['unique' => 'Must be unique.']];
        $this->assertEquals($expected, $validator->validate($data));
    }

    /**
     * Tests that the callbacks receive the expected types of arguments.
     */
    public function testCallbackArgumentTypes(): void
    {
        $table = $this->getTableLocator()->get('articles');
        $table->belongsTo('authors');

        $eventManager = $table->getEventManager();

        $associationBeforeFindCount = 0;
        $table->getAssociation('authors')->getTarget()->getEventManager()->on(
            'Model.beforeFind',
            function (EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary) use (&$associationBeforeFindCount): void {
                $this->assertIsBool($primary);
                $associationBeforeFindCount++;
            }
        );

        $beforeFindCount = 0;
        $eventManager->on(
            'Model.beforeFind',
            function (EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary) use (&$beforeFindCount): void {
                $this->assertIsBool($primary);
                $beforeFindCount++;
            }
        );
        $table->find()->contain('authors')->first();
        $this->assertSame(1, $associationBeforeFindCount);
        $this->assertSame(1, $beforeFindCount);

        $buildValidatorCount = 0;
        $eventManager->on(
            'Model.buildValidator',
            $callback = function (EventInterface $event, Validator $validator, $name) use (&$buildValidatorCount): void {
                $this->assertIsString($name);
                $buildValidatorCount++;
            }
        );
        $table->getValidator();
        $this->assertSame(1, $buildValidatorCount);
        $buildRulesCount = 0;
        $beforeRulesCount = 0;
        $afterRulesCount = 0;
        $beforeSaveCount = 0;
        $afterSaveCount = 0;
        $eventManager->on(
            'Model.buildRules',
            function (EventInterface $event, RulesChecker $rules) use (&$buildRulesCount): void {
                $buildRulesCount++;
            }
        );
        $eventManager->on(
            'Model.beforeRules',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options, $operation) use (&$beforeRulesCount): void {
                $this->assertIsString($operation);
                $beforeRulesCount++;
            }
        );
        $eventManager->on(
            'Model.afterRules',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options, $result, $operation) use (&$afterRulesCount): void {
                $this->assertIsBool($result);
                $this->assertIsString($operation);
                $afterRulesCount++;
            }
        );
        $eventManager->on(
            'Model.beforeSave',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$beforeSaveCount): void {
                $beforeSaveCount++;
            }
        );
        $eventManager->on(
            'Model.afterSave',
            $afterSaveCallback = function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$afterSaveCount): void {
                $afterSaveCount++;
            }
        );
        $entity = new Entity(['title' => 'Title']);
        $this->assertNotFalse($table->save($entity));
        $this->assertSame(1, $buildRulesCount);
        $this->assertSame(1, $beforeRulesCount);
        $this->assertSame(1, $afterRulesCount);
        $this->assertSame(1, $beforeSaveCount);
        $this->assertSame(1, $afterSaveCount);
        $beforeDeleteCount = 0;
        $afterDeleteCount = 0;
        $eventManager->on(
            'Model.beforeDelete',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$beforeDeleteCount): void {
                $beforeDeleteCount++;
            }
        );
        $eventManager->on(
            'Model.afterDelete',
            function (EventInterface $event, EntityInterface $entity, ArrayObject $options) use (&$afterDeleteCount): void {
                $afterDeleteCount++;
            }
        );
        $this->assertTrue($table->delete($entity, ['checkRules' => false]));
        $this->assertSame(1, $beforeDeleteCount);
        $this->assertSame(1, $afterDeleteCount);
    }

    /**
     * Tests that calling newEmptyEntity() on a table sets the right source alias.
     */
    public function testSetEntitySource(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $this->assertSame('Articles', $table->newEmptyEntity()->getSource());

        $this->loadPlugins(['TestPlugin']);
        $table = $this->getTableLocator()->get('TestPlugin.Comments');
        $this->assertSame('TestPlugin.Comments', $table->newEmptyEntity()->getSource());
    }

    /**
     * Tests that passing a coned entity that was marked as new to save() will
     * actually save it as a new entity
     */
    public function testSaveWithClonedEntity(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $article = $table->get(1);

        $cloned = clone $article;
        $cloned->unset('id');
        $cloned->setNew(true);
        $this->assertSame($cloned, $table->save($cloned));
        $this->assertEquals(
            $article->extract(['title', 'author_id']),
            $cloned->extract(['title', 'author_id'])
        );
        $this->assertSame(4, $cloned->id);
    }

    /**
     * Tests that the _ids notation can be used for HasMany
     */
    public function testSaveHasManyWithIds(): void
    {
        $data = [
            'username' => 'lux',
            'password' => 'passphrase',
            'comments' => [
                '_ids' => [1, 2],
            ],
        ];

        $userTable = $this->getTableLocator()->get('Users');
        $userTable->hasMany('Comments');
        $savedUser = $userTable->save($userTable->newEntity($data, ['associated' => ['Comments']]));
        $retrievedUser = $userTable->find('all')->where(['id' => $savedUser->id])->contain(['Comments'])->first();
        $this->assertEquals($savedUser->comments[0]->user_id, $retrievedUser->comments[0]->user_id);
        $this->assertEquals($savedUser->comments[1]->user_id, $retrievedUser->comments[1]->user_id);
    }

    /**
     * Tests that on second save, entities for the has many relation are not marked
     * as dirty unnecessarily. This helps avoid wasteful database statements and makes
     * for a cleaner transaction log
     */
    public function testSaveHasManyNoWasteSave(): void
    {
        $data = [
            'username' => 'lux',
            'password' => 'passphrase',
            'comments' => [
                '_ids' => [1, 2],
            ],
        ];

        $userTable = $this->getTableLocator()->get('Users');
        $userTable->hasMany('Comments');
        $savedUser = $userTable->save($userTable->newEntity($data, ['associated' => ['Comments']]));

        $counter = 0;
        $userTable->Comments
            ->getEventManager()
            ->on('Model.afterSave', function (EventInterface $event, $entity) use (&$counter): void {
                if ($entity->isDirty()) {
                    $counter++;
                }
            });

        $savedUser->comments[] = $userTable->Comments->get(5);
        $this->assertCount(3, $savedUser->comments);
        $savedUser->setDirty('comments', true);
        $userTable->save($savedUser);
        $this->assertSame(1, $counter);
    }

    /**
     * Tests that on second save, entities for the belongsToMany relation are not marked
     * as dirty unnecessarily. This helps avoid wasteful database statements and makes
     * for a cleaner transaction log
     */
    public function testSaveBelongsToManyNoWasteSave(): void
    {
        $data = [
            'title' => 'foo',
            'body' => 'bar',
            'tags' => [
                '_ids' => [1, 2],
            ],
        ];

        $table = $this->getTableLocator()->get('Articles');
        $article = $table->save($table->newEntity($data, ['associated' => ['Tags']]));

        $counter = 0;
        $table->Tags->junction()
            ->getEventManager()
            ->on('Model.afterSave', function (EventInterface $event, $entity) use (&$counter): void {
                if ($entity->isDirty()) {
                    $counter++;
                }
            });

        $article->tags[] = $table->Tags->get(3);
        $this->assertCount(3, $article->tags);
        $article->setDirty('tags', true);
        $table->save($article);
        $this->assertSame(1, $counter);
    }

    /**
     * Tests that after saving then entity contains the right primary
     * key casted to the right type
     */
    public function testSaveCorrectPrimaryKeyType(): void
    {
        $entity = new Entity([
            'username' => 'superuser',
            'created' => new DateTime('2013-10-10 00:00'),
            'updated' => new DateTime('2013-10-10 00:00'),
        ], ['markNew' => true]);

        $table = $this->getTableLocator()->get('Users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(self::$nextUserId, $entity->id);
    }

    /**
     * Tests entity clean()
     */
    public function testEntityClean(): void
    {
        $table = $this->getTableLocator()->get('Articles');
        $table->getValidator()->requirePresence('body');
        $entity = $table->newEntity(['title' => 'mark']);

        $entity->setDirty('title', true);
        $entity->setInvalidField('title', 'albert');

        $this->assertNotEmpty($entity->getErrors());
        $this->assertTrue($entity->isDirty());
        $this->assertEquals(['title' => 'albert'], $entity->getInvalid());

        $entity->title = 'alex';
        $this->assertSame($entity->getOriginal('title'), 'mark');

        $entity->clean();

        $this->assertEmpty($entity->getErrors());
        $this->assertFalse($entity->isDirty());
        $this->assertEquals([], $entity->getInvalid());
        $this->assertSame($entity->getOriginal('title'), 'alex');
    }

    /**
     * Tests the loadInto() method
     */
    public function testLoadIntoEntity(): void
    {
        $table = $this->getTableLocator()->get('Authors');
        $table->hasMany('SiteArticles');

        $entity = $table->get(1);
        $result = $table->loadInto($entity, ['SiteArticles', 'Articles.Tags']);
        $this->assertSame($entity, $result);

        $expected = $table->get(1, contain: ['SiteArticles', 'Articles.Tags']);
        $this->assertEquals($expected->site_articles, $result->site_articles);
        $this->assertEquals($expected->articles, $result->articles);
    }

    /**
     * Tests that it is possible to pass conditions and fields to loadInto()
     */
    public function testLoadIntoWithConditions(): void
    {
        $table = $this->getTableLocator()->get('Authors');
        $table->hasMany('SiteArticles');

        $entity = $table->get(1);
        $options = [
            'SiteArticles' => ['fields' => ['title', 'author_id']],
            'Articles.Tags' => function ($q) {
                return $q->where(['Tags.name' => 'tag2']);
            },
        ];
        $result = $table->loadInto($entity, $options);
        $this->assertSame($entity, $result);
        $expected = $table->get(1, contain: $options);
        $this->assertEquals($expected->site_articles, $result->site_articles);
        $this->assertEquals(['title', 'author_id'], $expected->site_articles[0]->getOriginalFields());
        $this->assertEquals($expected->articles, $result->articles);
        $this->assertSame('tag2', $expected->articles[0]->tags[0]->name);
    }

    /**
     * Tests loadInto() with a belongsTo association
     */
    public function testLoadBelongsTo(): void
    {
        $table = $this->getTableLocator()->get('Articles');

        $entity = $table->get(2);
        $result = $table->loadInto($entity, ['Authors']);
        $this->assertSame($entity, $result);

        $expected = $table->get(2, contain: ['Authors']);
        $this->assertEquals($expected, $entity);
    }

    /**
     * Tests that it is possible to post-load associations for many entities at
     * the same time
     */
    public function testLoadIntoMany(): void
    {
        $table = $this->getTableLocator()->get('Authors');
        $table->hasMany('SiteArticles');

        $entities = $table->find()->toArray();
        $contain = ['SiteArticles', 'Articles.Tags'];
        $result = $table->loadInto($entities, $contain);

        foreach ($entities as $k => $v) {
            $this->assertSame($v, $result[$k]);
        }

        $entities = $table->find()->contain($contain)->toArray();
        foreach ($entities as $k => $v) {
            $this->assertEquals($v->site_articles, $result[$k]->site_articles);
            $this->assertEquals($v->articles, $result[$k]->articles);
        }
    }

    /**
     * Tests that saveOrFail triggers an exception on not successful save
     */
    public function testSaveOrFail(): void
    {
        $this->expectException(PersistenceFailedException::class);
        $this->expectExceptionMessage('Entity save failure.');

        $entity = new Entity([
            'foo' => 'bar',
        ]);
        $table = $this->getTableLocator()->get('users');

        $table->saveOrFail($entity);
    }

    /**
     * Tests that saveOrFail displays useful messages on output, especially in tests for CLI.
     */
    public function testSaveOrFailErrorDisplay(): void
    {
        $this->expectException(PersistenceFailedException::class);
        $this->expectExceptionMessage('Entity save failure. Found the following errors (field.0: "Some message", multiple.one: "One", multiple.two: "Two")');

        $entity = new Entity([
            'foo' => 'bar',
        ]);
        $entity->setError('field', 'Some message');
        $entity->setError('multiple', ['one' => 'One', 'two' => 'Two']);
        $table = $this->getTableLocator()->get('users');

        $table->saveOrFail($entity);
    }

    /**
     * Tests that saveOrFail with nested errors
     */
    public function testSaveOrFailNestedError(): void
    {
        $this->expectException(PersistenceFailedException::class);
        $this->expectExceptionMessage('Entity save failure. Found the following errors (articles.0.title.0: "Bad value")');

        $entity = new Entity([
            'username' => 'bad',
            'articles' => [
                new Entity(['title' => 'not an entity']),
            ],
        ]);
        $entity->articles[0]->setError('title', 'Bad value');

        $table = $this->getTableLocator()->get('Users');
        $table->hasMany('Articles');

        $table->saveOrFail($entity);
    }

    /**
     * Tests that saveOrFail returns the right entity
     */
    public function testSaveOrFailGetEntity(): void
    {
        $entity = new Entity([
            'foo' => 'bar',
        ]);
        $table = $this->getTableLocator()->get('users');

        try {
            $table->saveOrFail($entity);
        } catch (PersistenceFailedException $e) {
            $this->assertSame($entity, $e->getEntity());
        }
    }

    /**
     * Tests that deleteOrFail triggers an exception on not successful delete
     */
    public function testDeleteOrFail(): void
    {
        $this->expectException(PersistenceFailedException::class);
        $this->expectExceptionMessage('Entity delete failure.');
        $entity = new Entity([
            'id' => 999,
        ]);
        $table = $this->getTableLocator()->get('users');

        $table->deleteOrFail($entity);
    }

    /**
     * Tests that deleteOrFail returns the right entity
     */
    public function testDeleteOrFailGetEntity(): void
    {
        $entity = new Entity([
            'id' => 999,
        ]);
        $table = $this->getTableLocator()->get('users');

        try {
            $table->deleteOrFail($entity);
        } catch (PersistenceFailedException $e) {
            $this->assertSame($entity, $e->getEntity());
        }
    }

    /**
     * Helper method to skip tests when connection is SQLServer.
     */
    public function skipIfSqlServer(): void
    {
        $this->skipIf(
            $this->connection->getDriver() instanceof Sqlserver,
            'SQLServer does not support the requirements of this test.'
        );
    }
}
