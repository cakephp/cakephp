<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use ArrayObject;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Database\Exception;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\TypeMap;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\I18n\Time;
use Cake\ORM\AssociationCollection;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\SaveOptionsBuilder;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;

/**
 * Used to test correct class is instantiated when using TableRegistry::get();
 */
class UsersTable extends Table
{

}

/**
 * Tests Table class
 */
class TableTest extends TestCase
{

    public $fixtures = [
        'core.articles',
        'core.tags',
        'core.articles_tags',
        'core.authors',
        'core.categories',
        'core.comments',
        'core.groups',
        'core.groups_members',
        'core.members',
        'core.polymorphic_tagged',
        'core.site_articles',
        'core.users'
    ];

    /**
     * Handy variable containing the next primary key that will be inserted in the
     * users table
     *
     * @var int
     */
    public static $nextUserId = 5;

    public function setUp()
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        Configure::write('App.namespace', 'TestApp');

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
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    /**
     * Tests the table method
     *
     * @return void
     */
    public function testTableMethod()
    {
        $table = new Table(['table' => 'users']);
        $this->assertEquals('users', $table->table());

        $table = new UsersTable;
        $this->assertEquals('users', $table->table());

        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['find'])
            ->setMockClassName('SpecialThingsTable')
            ->getMock();
        $this->assertEquals('special_things', $table->table());

        $table = new Table(['alias' => 'LoveBoats']);
        $this->assertEquals('love_boats', $table->table());

        $table->table('other');
        $this->assertEquals('other', $table->table());

        $table->table('database.other');
        $this->assertEquals('database.other', $table->table());
    }

    /**
     * Tests the alias method
     *
     * @return void
     */
    public function testAliasMethod()
    {
        $table = new Table(['alias' => 'users']);
        $this->assertEquals('users', $table->alias());

        $table = new Table(['table' => 'stuffs']);
        $this->assertEquals('stuffs', $table->alias());

        $table = new UsersTable;
        $this->assertEquals('Users', $table->alias());

        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['find'])
            ->setMockClassName('SpecialThingTable')
            ->getMock();
        $this->assertEquals('SpecialThing', $table->alias());

        $table->alias('AnotherOne');
        $this->assertEquals('AnotherOne', $table->alias());
    }

    /**
     * Test that aliasField() works.
     *
     * @return void
     */
    public function testAliasField()
    {
        $table = new Table(['alias' => 'Users']);
        $this->assertEquals('Users.id', $table->aliasField('id'));

        $this->assertEquals('Users.id', $table->aliasField('Users.id'));
    }

    /**
     * Tests connection method
     *
     * @return void
     */
    public function testConnection()
    {
        $table = new Table(['table' => 'users']);
        $this->assertNull($table->connection());
        $table->connection($this->connection);
        $this->assertSame($this->connection, $table->connection());
    }

    /**
     * Tests primaryKey method
     *
     * @return void
     */
    public function testPrimaryKey()
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'id' => ['type' => 'integer'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
            ]
        ]);
        $this->assertEquals('id', $table->primaryKey());
        $table->primaryKey('thingID');
        $this->assertEquals('thingID', $table->primaryKey());

        $table->primaryKey(['thingID', 'user_id']);
        $this->assertEquals(['thingID', 'user_id'], $table->primaryKey());
    }

    /**
     * Tests that name will be selected as a displayField
     *
     * @return void
     */
    public function testDisplayFieldName()
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'foo' => ['type' => 'string'],
                'name' => ['type' => 'string']
            ]
        ]);
        $this->assertEquals('name', $table->displayField());
    }

    /**
     * Tests that title will be selected as a displayField
     *
     * @return void
     */
    public function testDisplayFieldTitle()
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'foo' => ['type' => 'string'],
                'title' => ['type' => 'string']
            ]
        ]);
        $this->assertEquals('title', $table->displayField());
    }

    /**
     * Tests that no displayField will fallback to primary key
     *
     * @return void
     */
    public function testDisplayFallback()
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'id' => ['type' => 'string'],
                'foo' => ['type' => 'string'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
            ]
        ]);
        $this->assertEquals('id', $table->displayField());
    }

    /**
     * Tests that displayField can be changed
     *
     * @return void
     */
    public function testDisplaySet()
    {
        $table = new Table([
            'table' => 'users',
            'schema' => [
                'id' => ['type' => 'string'],
                'foo' => ['type' => 'string'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
            ]
        ]);
        $this->assertEquals('id', $table->displayField());
        $table->displayField('foo');
        $this->assertEquals('foo', $table->displayField());
    }

    /**
     * Tests schema method
     *
     * @return void
     */
    public function testSchema()
    {
        $schema = $this->connection->schemaCollection()->describe('users');
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $this->assertEquals($schema, $table->schema());

        $table = new Table(['table' => 'stuff']);
        $table->schema($schema);
        $this->assertSame($schema, $table->schema());

        $table = new Table(['table' => 'another']);
        $schema = ['id' => ['type' => 'integer']];
        $table->schema($schema);
        $this->assertEquals(
            new \Cake\Database\Schema\Table('another', $schema),
            $table->schema()
        );
    }

    /**
     * Tests that _initializeSchema can be used to alter the database schema
     *
     * @return void
     */
    public function testSchemaInitialize()
    {
        $schema = $this->connection->schemaCollection()->describe('users');
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['_initializeSchema'])
            ->setConstructorArgs([['table' => 'users', 'connection' => $this->connection]])
            ->getMock();
        $table->expects($this->once())
            ->method('_initializeSchema')
            ->with($schema)
            ->will($this->returnCallback(function ($schema) {
                $schema->columnType('username', 'integer');

                return $schema;
            }));
        $result = $table->schema();
        $schema->columnType('username', 'integer');
        $this->assertEquals($schema, $result);
        $this->assertEquals($schema, $table->schema(), '_initializeSchema should be called once');
    }

    /**
     * Tests that all fields for a table are added by default in a find when no
     * other fields are specified
     *
     * @return void
     */
    public function testFindAllNoFieldsAndNoHydration()
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $results = $table
            ->find('all')
            ->where(['id IN' => [1, 2]])
            ->order('id')
            ->hydrate(false)
            ->toArray();
        $expected = [
            [
                'id' => 1,
                'username' => 'mariano',
                'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
                'created' => new Time('2007-03-17 01:16:23'),
                'updated' => new Time('2007-03-17 01:18:31'),
            ],
            [
                'id' => 2,
                'username' => 'nate',
                'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
                'created' => new Time('2008-03-17 01:18:23'),
                'updated' => new Time('2008-03-17 01:20:31'),
            ],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that it is possible to select only a few fields when finding over a table
     *
     * @return void
     */
    public function testFindAllSomeFieldsNoHydration()
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $results = $table->find('all')
            ->select(['username', 'password'])
            ->hydrate(false)
            ->order('username')->toArray();
        $expected = [
            ['username' => 'garrett', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
            ['username' => 'larry', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
            ['username' => 'mariano', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
            ['username' => 'nate', 'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO'],
        ];
        $this->assertSame($expected, $results);

        $results = $table->find('all')
            ->select(['foo' => 'username', 'password'])
            ->order('username')
            ->hydrate(false)
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
     *
     * @return void
     */
    public function testFindAllConditionAutoTypes()
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $query = $table->find('all')
            ->select(['id', 'username'])
            ->where(['created >=' => new Time('2010-01-22 00:00')])
            ->hydrate(false)
            ->order('id');
        $expected = [
            ['id' => 3, 'username' => 'larry'],
            ['id' => 4, 'username' => 'garrett']
        ];
        $this->assertSame($expected, $query->toArray());

        $query->orWhere(['users.created' => new Time('2008-03-17 01:18:23')]);
        $expected = [
            ['id' => 2, 'username' => 'nate'],
            ['id' => 3, 'username' => 'larry'],
            ['id' => 4, 'username' => 'garrett']
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * Test that beforeFind events can mutate the query.
     *
     * @return void
     */
    public function testFindBeforeFindEventMutateQuery()
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $table->eventManager()->on(
            'Model.beforeFind',
            function ($event, $query, $options) {
                $query->limit(1);
            }
        );

        $result = $table->find('all')->all();
        $this->assertCount(1, $result, 'Should only have 1 record, limit 1 applied.');
    }

    /**
     * Test that beforeFind events are fired and can stop the find and
     * return custom results.
     *
     * @return void
     */
    public function testFindBeforeFindEventOverrideReturn()
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $expected = ['One', 'Two', 'Three'];
        $table->eventManager()->on(
            'Model.beforeFind',
            function ($event, $query, $options) use ($expected) {
                $query->setResult($expected);
                $event->stopPropagation();
            }
        );

        $query = $table->find('all');
        $query->limit(1);
        $this->assertEquals($expected, $query->all()->toArray());
    }

    /**
     * Tests that belongsTo() creates and configures correctly the association
     *
     * @return void
     */
    public function testBelongsTo()
    {
        $options = ['foreignKey' => 'fake_id', 'conditions' => ['a' => 'b']];
        $table = new Table(['table' => 'dates']);
        $belongsTo = $table->belongsTo('user', $options);
        $this->assertInstanceOf('Cake\ORM\Association\BelongsTo', $belongsTo);
        $this->assertSame($belongsTo, $table->association('user'));
        $this->assertEquals('user', $belongsTo->name());
        $this->assertEquals('fake_id', $belongsTo->foreignKey());
        $this->assertEquals(['a' => 'b'], $belongsTo->conditions());
        $this->assertSame($table, $belongsTo->source());
    }

    /**
     * Tests that hasOne() creates and configures correctly the association
     *
     * @return void
     */
    public function testHasOne()
    {
        $options = ['foreignKey' => 'user_id', 'conditions' => ['b' => 'c']];
        $table = new Table(['table' => 'users']);
        $hasOne = $table->hasOne('profile', $options);
        $this->assertInstanceOf('Cake\ORM\Association\HasOne', $hasOne);
        $this->assertSame($hasOne, $table->association('profile'));
        $this->assertEquals('profile', $hasOne->name());
        $this->assertEquals('user_id', $hasOne->foreignKey());
        $this->assertEquals(['b' => 'c'], $hasOne->conditions());
        $this->assertSame($table, $hasOne->source());
    }

    /**
     * Test has one with a plugin model
     *
     * @return void
     */
    public function testHasOnePlugin()
    {
        $options = ['className' => 'TestPlugin.Comments'];
        $table = new Table(['table' => 'users']);

        $hasOne = $table->hasOne('Comments', $options);
        $this->assertInstanceOf('Cake\ORM\Association\HasOne', $hasOne);
        $this->assertSame('Comments', $hasOne->name());

        $hasOneTable = $hasOne->target();
        $this->assertSame('Comments', $hasOne->alias());
        $this->assertSame('TestPlugin.Comments', $hasOne->registryAlias());

        $options = ['className' => 'TestPlugin.Comments'];
        $table = new Table(['table' => 'users']);

        $hasOne = $table->hasOne('TestPlugin.Comments', $options);
        $this->assertInstanceOf('Cake\ORM\Association\HasOne', $hasOne);
        $this->assertSame('Comments', $hasOne->name());

        $hasOneTable = $hasOne->target();
        $this->assertSame('Comments', $hasOne->alias());
        $this->assertSame('TestPlugin.Comments', $hasOne->registryAlias());
    }

    /**
     * testNoneUniqueAssociationsSameClass
     *
     * @return void
     */
    public function testNoneUniqueAssociationsSameClass()
    {
        $Users = new Table(['table' => 'users']);
        $options = ['className' => 'Comments'];
        $Users->hasMany('Comments', $options);

        $Articles = new Table(['table' => 'articles']);
        $options = ['className' => 'Comments'];
        $Articles->hasMany('Comments', $options);

        $Categories = new Table(['table' => 'categories']);
        $options = ['className' => 'TestPlugin.Comments'];
        $Categories->hasMany('Comments', $options);

        $this->assertInstanceOf('Cake\ORM\Table', $Users->Comments->target());
        $this->assertInstanceOf('Cake\ORM\Table', $Articles->Comments->target());
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $Categories->Comments->target());
    }

    /**
     * Test associations which refer to the same table multiple times
     *
     * @return void
     */
    public function testSelfJoinAssociations()
    {
        $Categories = TableRegistry::get('Categories');
        $options = ['className' => 'Categories'];
        $Categories->hasMany('Children', ['foreignKey' => 'parent_id'] + $options);
        $Categories->belongsTo('Parent', $options);

        $this->assertSame('categories', $Categories->Children->target()->table());
        $this->assertSame('categories', $Categories->Parent->target()->table());

        $this->assertSame('Children', $Categories->Children->alias());
        $this->assertSame('Children', $Categories->Children->target()->alias());

        $this->assertSame('Parent', $Categories->Parent->alias());
        $this->assertSame('Parent', $Categories->Parent->target()->alias());

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
                ]
            ]
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
     *
     * @return void
     */
    public function testHasMany()
    {
        $options = [
            'foreignKey' => 'author_id',
            'conditions' => ['b' => 'c'],
            'sort' => ['foo' => 'asc']
        ];
        $table = new Table(['table' => 'authors']);
        $hasMany = $table->hasMany('article', $options);
        $this->assertInstanceOf('Cake\ORM\Association\HasMany', $hasMany);
        $this->assertSame($hasMany, $table->association('article'));
        $this->assertEquals('article', $hasMany->name());
        $this->assertEquals('author_id', $hasMany->foreignKey());
        $this->assertEquals(['b' => 'c'], $hasMany->conditions());
        $this->assertEquals(['foo' => 'asc'], $hasMany->sort());
        $this->assertSame($table, $hasMany->source());
    }

    /**
     * testHasManyWithClassName
     *
     * @return void
     */
    public function testHasManyWithClassName()
    {
        $table = TableRegistry::get('Articles');
        $table->hasMany('Comments', [
            'className' => 'Comments',
            'conditions' => ['published' => 'Y'],
        ]);

        $table->hasMany('UnapprovedComments', [
            'className' => 'Comments',
            'conditions' => ['published' => 'N'],
            'propertyName' => 'unaproved_comments'
        ]);

        $expected = [
            'id' => 1,
            'title' => 'First Article',
            'unaproved_comments' => [
                [
                    'id' => 4,
                    'article_id' => 1,
                    'comment' => 'Fourth Comment for First Article'
                ]
            ],
            'comments' => [
                [
                    'id' => 1,
                    'article_id' => 1,
                    'comment' => 'First Comment for First Article'
                ],
                [
                    'id' => 2,
                    'article_id' => 1,
                    'comment' => 'Second Comment for First Article'
                ],
                [
                    'id' => 3,
                    'article_id' => 1,
                    'comment' => 'Third Comment for First Article'
                ]
            ]
        ];
        $result = $table->find()
            ->select(['id', 'title'])
            ->contain([
                'Comments' => ['fields' => ['id', 'article_id', 'comment']],
                'UnapprovedComments' => ['fields' => ['id', 'article_id', 'comment']]
            ])
            ->where(['id' => 1])
            ->first();

        $this->assertSame($expected, $result->toArray());
    }

    /**
     * Ensure associations use the plugin-prefixed model
     *
     * @return void
     */
    public function testHasManyPluginOverlap()
    {
        TableRegistry::get('Comments');
        Plugin::load('TestPlugin');

        $table = new Table(['table' => 'authors']);

        $table->hasMany('TestPlugin.Comments');
        $comments = $table->Comments->target();
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $comments);
    }

    /**
     * Ensure associations use the plugin-prefixed model
     * even if specified with config
     *
     * @return void
     */
    public function testHasManyPluginOverlapConfig()
    {
        TableRegistry::get('Comments');
        Plugin::load('TestPlugin');

        $table = new Table(['table' => 'authors']);

        $table->hasMany('Comments', ['className' => 'TestPlugin.Comments']);
        $comments = $table->Comments->target();
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $comments);
    }

    /**
     * Tests that BelongsToMany() creates and configures correctly the association
     *
     * @return void
     */
    public function testBelongsToMany()
    {
        $options = [
            'foreignKey' => 'thing_id',
            'joinTable' => 'things_tags',
            'conditions' => ['b' => 'c'],
            'sort' => ['foo' => 'asc']
        ];
        $table = new Table(['table' => 'authors', 'connection' => $this->connection]);
        $belongsToMany = $table->belongsToMany('tag', $options);
        $this->assertInstanceOf('Cake\ORM\Association\BelongsToMany', $belongsToMany);
        $this->assertSame($belongsToMany, $table->association('tag'));
        $this->assertEquals('tag', $belongsToMany->name());
        $this->assertEquals('thing_id', $belongsToMany->foreignKey());
        $this->assertEquals(['b' => 'c'], $belongsToMany->conditions());
        $this->assertEquals(['foo' => 'asc'], $belongsToMany->sort());
        $this->assertSame($table, $belongsToMany->source());
        $this->assertSame('things_tags', $belongsToMany->junction()->table());
    }

    /**
     * Test addAssociations()
     *
     * @return void
     */
    public function testAddAssociations()
    {
        $params = [
            'belongsTo' => [
                'users' => ['foreignKey' => 'fake_id', 'conditions' => ['a' => 'b']]
            ],
            'hasOne' => ['profiles'],
            'hasMany' => ['authors'],
            'belongsToMany' => [
                'tags' => [
                    'joinTable' => 'things_tags',
                    'conditions' => [
                        'Tags.starred' => true
                    ]
                ]
            ]
        ];

        $table = new Table(['table' => 'dates']);
        $table->addAssociations($params);

        $associations = $table->associations();

        $belongsTo = $associations->get('users');
        $this->assertInstanceOf('Cake\ORM\Association\BelongsTo', $belongsTo);
        $this->assertEquals('users', $belongsTo->name());
        $this->assertEquals('fake_id', $belongsTo->foreignKey());
        $this->assertEquals(['a' => 'b'], $belongsTo->conditions());
        $this->assertSame($table, $belongsTo->source());

        $hasOne = $associations->get('profiles');
        $this->assertInstanceOf('Cake\ORM\Association\HasOne', $hasOne);
        $this->assertEquals('profiles', $hasOne->name());

        $hasMany = $associations->get('authors');
        $this->assertInstanceOf('Cake\ORM\Association\hasMany', $hasMany);
        $this->assertEquals('authors', $hasMany->name());

        $belongsToMany = $associations->get('tags');
        $this->assertInstanceOf('Cake\ORM\Association\BelongsToMany', $belongsToMany);
        $this->assertEquals('tags', $belongsToMany->name());
        $this->assertSame('things_tags', $belongsToMany->junction()->table());
        $this->assertSame(['Tags.starred' => true], $belongsToMany->conditions());
    }

    /**
     * Test basic multi row updates.
     *
     * @return void
     */
    public function testUpdateAll()
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
            ->order(['id' => 'asc'])
            ->hydrate(false)
            ->toArray();
        $expected = array_fill(0, 3, $fields);
        $expected[] = ['username' => 'garrett'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that exceptions from the Query bubble up.
     *
     * @expectedException \Cake\Database\Exception
     */
    public function testUpdateAllFailure()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['query'])
            ->setConstructorArgs([['table' => 'users', 'connection' => $this->connection]])
            ->getMock();
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->setMethods(['execute'])
            ->setConstructorArgs([$this->connection, $table])
            ->getMock();
        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('execute')
            ->will($this->throwException(new Exception('Not good')));

        $table->updateAll(['username' => 'mark'], []);
    }

    /**
     * Test deleting many records.
     *
     * @return void
     */
    public function testDeleteAll()
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $result = $table->deleteAll(['id <' => 4]);
        $this->assertSame(3, $result);

        $result = $table->find('all')->toArray();
        $this->assertCount(1, $result, 'Only one record should remain');
        $this->assertEquals(4, $result[0]['id']);
    }

    /**
     * Test deleting many records with conditions using the alias
     *
     * @return void
     */
    public function testDeleteAllAliasedConditions()
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
        $this->assertEquals(4, $result[0]['id']);
    }

    /**
     * Test that exceptions from the Query bubble up.
     *
     * @expectedException \Cake\Database\Exception
     */
    public function testDeleteAllFailure()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['query'])
            ->setConstructorArgs([['table' => 'users', 'connection' => $this->connection]])
            ->getMock();
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->setMethods(['execute'])
            ->setConstructorArgs([$this->connection, $table])
            ->getMock();
        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('execute')
            ->will($this->throwException(new Exception('Not good')));

        $table->deleteAll(['id >' => 4]);
    }

    /**
     * Tests that array options are passed to the query object using applyOptions
     *
     * @return void
     */
    public function testFindApplyOptions()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['query', 'findAll'])
            ->setConstructorArgs([['table' => 'users', 'connection' => $this->connection]])
            ->getMock();
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->setConstructorArgs([$this->connection, $table])
            ->getMock();
        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $options = ['fields' => ['a', 'b'], 'connections' => ['a >' => 1]];
        $query->expects($this->any())
            ->method('select')
            ->will($this->returnSelf());

        $query->expects($this->once())->method('getOptions')
            ->will($this->returnValue(['connections' => ['a >' => 1]]));
        $query->expects($this->once())
            ->method('applyOptions')
            ->with($options);

        $table->expects($this->once())->method('findAll')
            ->with($query, ['connections' => ['a >' => 1]]);
        $table->find('all', $options);
    }

    /**
     * Tests find('list')
     *
     * @return void
     */
    public function testFindListNoHydration()
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $table->displayField('username');
        $query = $table->find('list')
            ->hydrate(false)
            ->order('id');
        $expected = [
            1 => 'mariano',
            2 => 'nate',
            3 => 'larry',
            4 => 'garrett'
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $table->find('list', ['fields' => ['id', 'username']])
            ->hydrate(false)
            ->order('id');
        $expected = [
            1 => 'mariano',
            2 => 'nate',
            3 => 'larry',
            4 => 'garrett'
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $table->find('list', ['groupField' => 'odd'])
            ->select(['id', 'username', 'odd' => new QueryExpression('id % 2')])
            ->hydrate(false)
            ->order('id');
        $expected = [
            1 => [
                1 => 'mariano',
                3 => 'larry'
            ],
            0 => [
                2 => 'nate',
                4 => 'garrett'
            ]
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * Tests find('threaded')
     *
     * @return void
     */
    public function testFindThreadedNoHydration()
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
                                'children' => []
                            ],
                            [
                                'id' => 8,
                                'parent_id' => '2',
                                'name' => 'Category 1.1.2',
                                'children' => []
                            ]
                        ],
                    ],
                    [
                        'id' => 3,
                        'parent_id' => '1',
                        'name' => 'Category 1.2',
                        'children' => []
                    ],
                ]
            ],
            [
                'id' => 4,
                'parent_id' => 0,
                'name' => 'Category 2',
                'children' => []
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
                        'children' => []
                    ]
                ]
            ]
        ];
        $results = $table->find('all')
            ->select(['id', 'parent_id', 'name'])
            ->hydrate(false)
            ->find('threaded')
            ->toArray();

        $this->assertEquals($expected, $results);
    }

    /**
     * Tests that finders can be stacked
     *
     * @return void
     */
    public function testStackingFinders()
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['find', 'findList'])
            ->disableOriginalConstructor()
            ->getMock();
        $params = [$this->connection, $table];
        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['addDefaultTypes'])
            ->setConstructorArgs($params)
            ->getMock();

        $table->expects($this->once())
            ->method('find')
            ->with('threaded', ['order' => ['name' => 'ASC']])
            ->will($this->returnValue($query));

        $table->expects($this->once())
            ->method('findList')
            ->with($query, ['keyPath' => 'id'])
            ->will($this->returnValue($query));

        $result = $table
            ->find('threaded', ['order' => ['name' => 'ASC']])
            ->find('list', ['keyPath' => 'id']);
        $this->assertSame($query, $result);
    }

    /**
     * Tests find('threaded') with hydrated results
     *
     * @return void
     */
    public function testFindThreadedHydrated()
    {
        $table = new Table([
            'table' => 'categories',
            'connection' => $this->connection,
        ]);
        $results = $table->find('all')
            ->find('threaded')
            ->select(['id', 'parent_id', 'name'])
            ->toArray();

        $this->assertEquals(1, $results[0]->id);
        $expected = [
            'id' => 8,
            'parent_id' => 2,
            'name' => 'Category 1.1.2',
            'children' => []
        ];
        $this->assertEquals($expected, $results[0]->children[0]->children[1]->toArray());
    }

    /**
     * Tests find('list') with hydrated records
     *
     * @return void
     */
    public function testFindListHydrated()
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $table->displayField('username');
        $query = $table
            ->find('list', ['fields' => ['id', 'username']])
            ->order('id');
        $expected = [
            1 => 'mariano',
            2 => 'nate',
            3 => 'larry',
            4 => 'garrett'
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $table->find('list', ['groupField' => 'odd'])
            ->select(['id', 'username', 'odd' => new QueryExpression('id % 2')])
            ->hydrate(true)
            ->order('id');
        $expected = [
            1 => [
                1 => 'mariano',
                3 => 'larry'
            ],
            0 => [
                2 => 'nate',
                4 => 'garrett'
            ]
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * Test that find('list') only selects required fields.
     *
     * @return void
     */
    public function testFindListSelectedFields()
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
        ]);
        $table->displayField('username');

        $query = $table->find('list');
        $expected = ['id', 'username'];
        $this->assertSame($expected, $query->clause('select'));

        $query = $table->find('list', ['valueField' => function ($row) {
            return $row->username;
        }]);
        $this->assertEmpty($query->clause('select'));

        $expected = ['odd' => new QueryExpression('id % 2'), 'id', 'username'];
        $query = $table->find('list', [
            'fields' => $expected,
            'groupField' => 'odd',
        ]);
        $this->assertSame($expected, $query->clause('select'));

        $articles = new Table([
            'table' => 'articles',
            'connection' => $this->connection,
        ]);

        $query = $articles->find('list', ['groupField' => 'author_id']);
        $expected = ['id', 'title', 'author_id'];
        $this->assertSame($expected, $query->clause('select'));

        $query = $articles->find('list', ['valueField' => ['author_id', 'title']])
            ->order('id');
        $expected = ['id', 'author_id', 'title'];
        $this->assertSame($expected, $query->clause('select'));

        $expected = [
            1 => '1;First Article',
            2 => '3;Second Article',
            3 => '1;Third Article',
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * test that find('list') does not auto add fields to select if using virtual properties
     *
     * @return void
     */
    public function testFindListWithVirtualField()
    {
        $table = new Table([
            'table' => 'users',
            'connection' => $this->connection,
            'entityClass' => '\TestApp\Model\Entity\VirtualUser'
        ]);
        $table->displayField('bonus');

        $query = $table
            ->find('list')
            ->order('id');
        $this->assertEmpty($query->clause('select'));

        $expected = [
            1 => 'bonus',
            2 => 'bonus',
            3 => 'bonus',
            4 => 'bonus'
        ];
        $this->assertSame($expected, $query->toArray());

        $query = $table->find('list', ['groupField' => 'odd']);
        $this->assertEmpty($query->clause('select'));
    }

    /**
     * Test find('list') with value field from associated table
     *
     * @return void
     */
    public function testFindListWithAssociatedTable()
    {
        $articles = new Table([
            'table' => 'articles',
            'connection' => $this->connection,
        ]);

        $articles->belongsTo('Authors');
        $query = $articles->find('list', ['valueField' => 'author.name'])
            ->contain(['Authors'])
            ->order('articles.id');
        $this->assertEmpty($query->clause('select'));

        $expected = [
            1 => 'mariano',
            2 => 'larry',
            3 => 'mariano',
        ];
        $this->assertSame($expected, $query->toArray());
    }

    /**
     * Test the default entityClass.
     *
     * @return void
     */
    public function testEntityClassDefault()
    {
        $table = new Table();
        $this->assertEquals('\Cake\ORM\Entity', $table->entityClass());
    }

    /**
     * Tests that using a simple string for entityClass will try to
     * load the class from the App namespace
     *
     * @return void
     */
    public function testTableClassInApp()
    {
        $class = $this->getMockClass('\Cake\ORM\Entity');

        if (!class_exists('TestApp\Model\Entity\TestUser')) {
            class_alias($class, 'TestApp\Model\Entity\TestUser');
        }

        $table = new Table();
        $this->assertEquals('TestApp\Model\Entity\TestUser', $table->entityClass('TestUser'));
    }

    /**
     * Tests that using a simple string for entityClass will try to
     * load the class from the Plugin namespace when using plugin notation
     *
     * @return void
     */
    public function testTableClassInPlugin()
    {
        $class = $this->getMockClass('\Cake\ORM\Entity');

        if (!class_exists('MyPlugin\Model\Entity\SuperUser')) {
            class_alias($class, 'MyPlugin\Model\Entity\SuperUser');
        }

        $table = new Table();
        $this->assertEquals(
            'MyPlugin\Model\Entity\SuperUser',
            $table->entityClass('MyPlugin.SuperUser')
        );
    }

    /**
     * Tests that using a simple string for entityClass will throw an exception
     * when the class does not exist in the namespace
     *
     * @expectedException \Cake\ORM\Exception\MissingEntityException
     * @expectedExceptionMessage Entity class FooUser could not be found.
     * @return void
     */
    public function testTableClassNonExisting()
    {
        $table = new Table;
        $this->assertFalse($table->entityClass('FooUser'));
    }

    /**
     * Tests getting the entityClass based on conventions for the entity
     * namespace
     *
     * @return void
     */
    public function testTableClassConventionForAPP()
    {
        $table = new \TestApp\Model\Table\ArticlesTable;
        $this->assertEquals('TestApp\Model\Entity\Article', $table->entityClass());
    }

    /**
     * Tests setting a entity class object using the setter method
     *
     * @return void
     */
    public function testSetEntityClass()
    {
        $table = new Table;
        $class = '\\' . $this->getMockClass('\Cake\ORM\Entity');
        $table->entityClass($class);
        $this->assertEquals($class, $table->entityClass());
    }

    /**
     * Proves that associations, even though they are lazy loaded, will fetch
     * records using the correct table class and hydrate with the correct entity
     *
     * @return void
     */
    public function testReciprocalBelongsToLoading()
    {
        $table = new \TestApp\Model\Table\ArticlesTable([
            'connection' => $this->connection,
        ]);
        $result = $table->find('all')->contain(['authors'])->first();
        $this->assertInstanceOf('TestApp\Model\Entity\Author', $result->author);
    }

    /**
     * Proves that associations, even though they are lazy loaded, will fetch
     * records using the correct table class and hydrate with the correct entity
     *
     * @return void
     */
    public function testReciprocalHasManyLoading()
    {
        $table = new \TestApp\Model\Table\ArticlesTable([
            'connection' => $this->connection,
        ]);
        $result = $table->find('all')->contain(['authors' => ['articles']])->first();
        $this->assertCount(2, $result->author->articles);
        foreach ($result->author->articles as $article) {
            $this->assertInstanceOf('TestApp\Model\Entity\Article', $article);
        }
    }

    /**
     * Tests that the correct table and entity are loaded for the join association in
     * a belongsToMany setup
     *
     * @return void
     */
    public function testReciprocalBelongsToMany()
    {
        $table = new \TestApp\Model\Table\ArticlesTable([
            'connection' => $this->connection,
        ]);
        $result = $table->find('all')->contain(['tags'])->first();
        $this->assertInstanceOf('TestApp\Model\Entity\Tag', $result->tags[0]);
        $this->assertInstanceOf(
            'TestApp\Model\Entity\ArticlesTag',
            $result->tags[0]->_joinData
        );
    }

    /**
     * Tests that recently fetched entities are always clean
     *
     * @return void
     */
    public function testFindCleanEntities()
    {
        $table = new \TestApp\Model\Table\ArticlesTable([
            'connection' => $this->connection,
        ]);
        $results = $table->find('all')->contain(['tags', 'authors'])->toArray();
        $this->assertCount(3, $results);
        foreach ($results as $article) {
            $this->assertFalse($article->dirty('id'));
            $this->assertFalse($article->dirty('title'));
            $this->assertFalse($article->dirty('author_id'));
            $this->assertFalse($article->dirty('body'));
            $this->assertFalse($article->dirty('published'));
            $this->assertFalse($article->dirty('author'));
            $this->assertFalse($article->author->dirty('id'));
            $this->assertFalse($article->author->dirty('name'));
            $this->assertFalse($article->dirty('tag'));
            if ($article->tag) {
                $this->assertFalse($article->tag[0]->_joinData->dirty('tag_id'));
            }
        }
    }

    /**
     * Tests that recently fetched entities are marked as not new
     *
     * @return void
     */
    public function testFindPersistedEntities()
    {
        $table = new \TestApp\Model\Table\ArticlesTable([
            'connection' => $this->connection,
        ]);
        $results = $table->find('all')->contain(['tags', 'authors'])->toArray();
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
     *
     * @return void
     */
    public function testExists()
    {
        $table = TableRegistry::get('users');
        $this->assertTrue($table->exists(['id' => 1]));
        $this->assertFalse($table->exists(['id' => 501]));
        $this->assertTrue($table->exists(['id' => 3, 'username' => 'larry']));
    }

    /**
     * Test adding a behavior to a table.
     *
     * @return void
     */
    public function testAddBehavior()
    {
        $mock = $this->getMockBuilder('Cake\ORM\BehaviorRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->once())
            ->method('load')
            ->with('Sluggable');

        $table = new Table([
            'table' => 'articles',
            'behaviors' => $mock
        ]);
        $table->addBehavior('Sluggable');
    }

    /**
     * Test adding a behavior that is a duplicate.
     *
     * @return void
     */
    public function testAddBehaviorDuplicate()
    {
        $table = new Table(['table' => 'articles']);
        $this->assertNull($table->addBehavior('Sluggable', ['test' => 'value']));
        $this->assertNull($table->addBehavior('Sluggable', ['test' => 'value']));
        try {
            $table->addBehavior('Sluggable', ['thing' => 'thing']);
            $this->fail('No exception raised');
        } catch (\RuntimeException $e) {
            $this->assertContains('The "Sluggable" alias has already been loaded', $e->getMessage());
        }
    }

    /**
     * Test removing a behavior from a table.
     *
     * @return void
     */
    public function testRemoveBehavior()
    {
        $mock = $this->getMockBuilder('Cake\ORM\BehaviorRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->once())
            ->method('unload')
            ->with('Sluggable');

        $table = new Table([
            'table' => 'articles',
            'behaviors' => $mock
        ]);
        $table->removeBehavior('Sluggable');
    }

    /**
     * Test getting a behavior instance from a table.
     *
     * @return void
     */
    public function testBehaviors()
    {
        $table = TableRegistry::get('article');
        $result = $table->behaviors();
        $this->assertInstanceOf('Cake\ORM\BehaviorRegistry', $result);
    }

    /**
     * Ensure exceptions are raised on missing behaviors.
     *
     * @expectedException \Cake\ORM\Exception\MissingBehaviorException
     */
    public function testAddBehaviorMissing()
    {
        $table = TableRegistry::get('article');
        $this->assertNull($table->addBehavior('NopeNotThere'));
    }

    /**
     * Test mixin methods from behaviors.
     *
     * @return void
     */
    public function testCallBehaviorMethod()
    {
        $table = TableRegistry::get('article');
        $table->addBehavior('Sluggable');
        $this->assertEquals('some-value', $table->slugify('some value'));
    }

    /**
     * Test you can alias a behavior method
     *
     * @return void
     */
    public function testCallBehaviorAliasedMethod()
    {
        $table = TableRegistry::get('article');
        $table->addBehavior('Sluggable', ['implementedMethods' => ['wednesday' => 'slugify']]);
        $this->assertEquals('some-value', $table->wednesday('some value'));
    }

    /**
     * Test finder methods from behaviors.
     *
     * @return void
     */
    public function testCallBehaviorFinder()
    {
        $table = TableRegistry::get('articles');
        $table->addBehavior('Sluggable');

        $query = $table->find('noSlug');
        $this->assertInstanceOf('Cake\ORM\Query', $query);
        $this->assertNotEmpty($query->clause('where'));
    }

    /**
     * testCallBehaviorAliasedFinder
     *
     * @return void
     */
    public function testCallBehaviorAliasedFinder()
    {
        $table = TableRegistry::get('articles');
        $table->addBehavior('Sluggable', ['implementedFinders' => ['special' => 'findNoSlug']]);

        $query = $table->find('special');
        $this->assertInstanceOf('Cake\ORM\Query', $query);
        $this->assertNotEmpty($query->clause('where'));
    }

    /**
     * Test implementedEvents
     *
     * @return void
     */
    public function testImplementedEvents()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods([
                'buildValidator',
                'beforeMarshal',
                'beforeFind',
                'beforeSave',
                'afterSave',
                'beforeDelete',
                'afterDelete',
                'afterRules'
            ])
            ->getMock();
        $result = $table->implementedEvents();
        $expected = [
            'Model.beforeMarshal' => 'beforeMarshal',
            'Model.buildValidator' => 'buildValidator',
            'Model.beforeFind' => 'beforeFind',
            'Model.beforeSave' => 'beforeSave',
            'Model.afterSave' => 'afterSave',
            'Model.beforeDelete' => 'beforeDelete',
            'Model.afterDelete' => 'afterDelete',
            'Model.afterRules' => 'afterRules',
        ];
        $this->assertEquals($expected, $result, 'Events do not match.');
    }

    /**
     * Tests that it is possible to insert a new row using the save method
     *
     * @group save
     * @return void
     */
    public function testSaveNewEntity()
    {
        $entity = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'password' => 'root',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);
        $table = TableRegistry::get('users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals($entity->id, self::$nextUserId);

        $row = $table->find('all')->where(['id' => self::$nextUserId])->first();
        $this->assertEquals($entity->toArray(), $row->toArray());
    }

    /**
     * Test that saving a new empty entity does nothing.
     *
     * @group save
     * @return void
     */
    public function testSaveNewEmptyEntity()
    {
        $entity = new \Cake\ORM\Entity();
        $table = TableRegistry::get('users');
        $this->assertFalse($table->save($entity));
    }

    /**
     * Test that saving a new empty entity does not call exists.
     *
     * @group save
     * @return void
     */
    public function testSaveNewEntityNoExists()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['exists'])
            ->setConstructorArgs([[
                'connection' => $this->connection,
                'alias' => 'Users',
                'table' => 'users',
            ]])
            ->getMock();
        $entity = $table->newEntity(['username' => 'mark']);
        $this->assertTrue($entity->isNew());

        $table->expects($this->never())
            ->method('exists');
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Test that saving a new entity with a Primary Key set does call exists.
     *
     * @group save
     * @return void
     */
    public function testSavePrimaryKeyEntityExists()
    {
        $this->skipIfSqlServer();
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['exists'])
            ->setConstructorArgs([[
                'connection' => $this->connection,
                'alias' => 'Users',
                'table' => 'users',
            ]])
            ->getMock();
        $entity = $table->newEntity(['id' => 20, 'username' => 'mark']);
        $this->assertTrue($entity->isNew());

        $table->expects($this->once())->method('exists');
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Test that save works with replace saveStrategy and are not deleted once they are not null
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategy()
    {
        $authors = new Table(
            [
                'table' => 'authors',
                'alias' => 'Authors',
                'connection' => $this->connection,
                'entityClass' => 'Cake\ORM\Entity',
            ]
        );

        $authors->hasMany('Articles', ['saveStrategy' => 'replace']);

        $entity = $authors->newEntity([
            'name' => 'mylux',
            'articles' => [
                ['title' => 'One Random Post', 'body' => 'The cake is not a lie'],
                ['title' => 'Another Random Post', 'body' => 'The cake is nice'],
                ['title' => 'One more random post', 'body' => 'The cake is forever']
            ]
        ], ['associated' => ['Articles']]);

        $entity = $authors->save($entity, ['associated' => ['Articles']]);
        $sizeArticles = count($entity->articles);
        $this->assertEquals($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        $articleId = $entity->articles[0]->id;
        unset($entity->articles[0]);
        $entity->dirty('articles', true);

        $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertEquals($sizeArticles - 1, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());
        $this->assertTrue($authors->Articles->exists(['id' => $articleId]));
    }


    /**
     * Test that save works with replace saveStrategy, replacing the already persisted entities even if no new entities are passed
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategyNotAdding()
    {
        $authors = new Table(
            [
                'table' => 'authors',
                'alias' => 'Authors',
                'connection' => $this->connection,
                'entityClass' => 'Cake\ORM\Entity',
            ]
        );

        $authors->hasMany('Articles', ['saveStrategy' => 'replace']);

        $entity = $authors->newEntity([
            'name' => 'mylux',
            'articles' => [
                ['title' => 'One Random Post', 'body' => 'The cake is not a lie'],
                ['title' => 'Another Random Post', 'body' => 'The cake is nice'],
                ['title' => 'One more random post', 'body' => 'The cake is forever']
            ]
        ], ['associated' => ['Articles']]);

        $entity = $authors->save($entity, ['associated' => ['Articles']]);
        $sizeArticles = count($entity->articles);
        $this->assertCount($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']]));

        $entity->set('articles', []);

        $entity = $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertCount(0, $authors->Articles->find('all')->where(['author_id' => $entity['id']]));
    }

    /**
     * Test that save works with append saveStrategy not deleting or setting null anything
     *
     * @return void
     */
    public function testSaveAppendSaveStrategy()
    {
        $authors = new Table(
            [
                'table' => 'authors',
                'alias' => 'Authors',
                'connection' => $this->connection,
                'entityClass' => 'Cake\ORM\Entity',
            ]
        );

        $authors->hasMany('Articles', ['saveStrategy' => 'append']);

        $entity = $authors->newEntity([
            'name' => 'mylux',
            'articles' => [
                ['title' => 'One Random Post', 'body' => 'The cake is not a lie'],
                ['title' => 'Another Random Post', 'body' => 'The cake is nice'],
                ['title' => 'One more random post', 'body' => 'The cake is forever']
            ]
        ], ['associated' => ['Articles']]);

        $entity = $authors->save($entity, ['associated' => ['Articles']]);
        $sizeArticles = count($entity->articles);

        $this->assertEquals($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        $articleId = $entity->articles[0]->id;
        unset($entity->articles[0]);
        $entity->dirty('articles', true);

        $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertEquals($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());
        $this->assertTrue($authors->Articles->exists(['id' => $articleId]));
    }

    /**
     * Test that save has append as the default save strategy
     *
     * @return void
     */
    public function testSaveDefaultSaveStrategy()
    {
        $authors = new Table(
            [
                'table' => 'authors',
                'alias' => 'Authors',
                'connection' => $this->connection,
                'entityClass' => 'Cake\ORM\Entity',
            ]
        );
        $authors->hasMany('Articles', ['saveStrategy' => 'append']);
        $this->assertEquals('append', $authors->association('articles')->saveStrategy());
    }

    /**
     * Test that the associated entities are unlinked and deleted when they are dependent
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategyDependent()
    {
        $authors = new Table(
            [
                'table' => 'authors',
                'alias' => 'Authors',
                'connection' => $this->connection,
                'entityClass' => 'Cake\ORM\Entity',
            ]
        );

        $authors->hasMany('Articles', ['saveStrategy' => 'replace', 'dependent' => true]);

        $entity = $authors->newEntity([
            'name' => 'mylux',
            'articles' => [
                ['title' => 'One Random Post', 'body' => 'The cake is not a lie'],
                ['title' => 'Another Random Post', 'body' => 'The cake is nice'],
                ['title' => 'One more random post', 'body' => 'The cake is forever']
            ]
        ], ['associated' => ['Articles']]);

        $entity = $authors->save($entity, ['associated' => ['Articles']]);
        $sizeArticles = count($entity->articles);
        $this->assertEquals($sizeArticles, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());

        $articleId = $entity->articles[0]->id;
        unset($entity->articles[0]);
        $entity->dirty('articles', true);

        $authors->save($entity, ['associated' => ['Articles']]);

        $this->assertEquals($sizeArticles - 1, $authors->Articles->find('all')->where(['author_id' => $entity['id']])->count());
        $this->assertFalse($authors->Articles->exists(['id' => $articleId]));
    }

    /**
     * Test that the associated entities are unlinked and deleted when they have a not nullable foreign key
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategyNotNullable()
    {
        $articles = new Table(
            [
                'table' => 'articles',
                'alias' => 'Articles',
                'connection' => $this->connection,
                'entityClass' => 'Cake\ORM\Entity',
            ]
        );

        $articles->hasMany('Comments', ['saveStrategy' => 'replace']);

        $article = $articles->newEntity([
            'title' => 'Bakeries are sky rocketing',
            'body' => 'All because of cake',
            'comments' => [
                [
                    'user_id' => 1,
                    'comment' => 'That is true!'
                ],
                [
                    'user_id' => 2,
                    'comment' => 'Of course'
                ]
            ]
        ], ['associated' => ['Comments']]);

        $article = $articles->save($article, ['associated' => ['Comments']]);
        $commentId = $article->comments[0]->id;
        $sizeComments = count($article->comments);

        $this->assertEquals($sizeComments, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertTrue($articles->Comments->exists(['id' => $commentId]));

        unset($article->comments[0]);
        $article->dirty('comments', true);
        $article = $articles->save($article, ['associated' => ['Comments']]);

        $this->assertEquals($sizeComments - 1, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertFalse($articles->Comments->exists(['id' => $commentId]));
    }

    /**
     * Test that the associated entities are unlinked and deleted when they have a not nullable foreign key
     *
     * @return void
     */
    public function testSaveReplaceSaveStrategyAdding()
    {
        $articles = new Table(
            [
                'table' => 'articles',
                'alias' => 'Articles',
                'connection' => $this->connection,
                'entityClass' => 'Cake\ORM\Entity',
            ]
        );

        $articles->hasMany('Comments', ['saveStrategy' => 'replace']);

        $article = $articles->newEntity([
            'title' => 'Bakeries are sky rocketing',
            'body' => 'All because of cake',
            'comments' => [
                [
                    'user_id' => 1,
                    'comment' => 'That is true!'
                ],
                [
                    'user_id' => 2,
                    'comment' => 'Of course'
                ]
            ]
        ], ['associated' => ['Comments']]);

        $article = $articles->save($article, ['associated' => ['Comments']]);
        $commentId = $article->comments[0]->id;
        $sizeComments = count($article->comments);
        $articleId = $article->id;

        $this->assertEquals($sizeComments, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertTrue($articles->Comments->exists(['id' => $commentId]));

        unset($article->comments[0]);
        $article->comments[] = $articles->Comments->newEntity([
            'user_id' => 1,
            'comment' => 'new comment'
        ]);

        $article->dirty('comments', true);
        $article = $articles->save($article, ['associated' => ['Comments']]);

        $this->assertEquals($sizeComments, $articles->Comments->find('all')->where(['article_id' => $article->id])->count());
        $this->assertFalse($articles->Comments->exists(['id' => $commentId]));
        $this->assertTrue($articles->Comments->exists(['comment' => 'new comment', 'article_id' => $articleId]));
    }

    /**
     * Tests that dependent, non-cascading deletes are using the association
     * conditions for deleting associated records.
     *
     * @return void
     */
    public function testHasManyNonCascadingUnlinkDeleteUsesAssociationConditions()
    {
        $Articles = TableRegistry::get('Articles');
        $Comments = $Articles->hasMany('Comments', [
            'dependent' => true,
            'cascadeCallbacks' => false,
            'saveStrategy' => HasMany::SAVE_REPLACE,
            'conditions' => [
                'Comments.published' => 'Y'
            ]
        ]);

        $article = $Articles->newEntity([
            'title' => 'Title',
            'body' => 'Body',
            'comments' => [
                [
                    'user_id' => 1,
                    'comment' => 'First comment',
                    'published' => 'Y'
                ],
                [
                    'user_id' => 1,
                    'comment' => 'Second comment',
                    'published' => 'Y'
                ]
            ]
        ]);
        $article = $Articles->save($article);
        $this->assertNotEmpty($article);

        $comment3 = $Comments->target()->newEntity([
            'article_id' => $article->get('id'),
            'user_id' => 1,
            'comment' => 'Third comment',
            'published' => 'N'
        ]);
        $comment3 = $Comments->target()->save($comment3);
        $this->assertNotEmpty($comment3);

        $this->assertEquals(3, $Comments->target()->find()->where(['Comments.article_id' => $article->get('id')])->count());

        unset($article->comments[1]);
        $article->dirty('comments', true);

        $article = $Articles->save($article);
        $this->assertNotEmpty($article);

        // Given the association condition of `'Comments.published' => 'Y'`,
        // it is expected that only one the three linked comments are actually
        // being deleted, as only one of them matches the association
        // conditions.
        $this->assertEquals(2, $Comments->target()->find()->where(['Comments.article_id' => $article->get('id')])->count());
    }

    /**
     * Tests that non-dependent, non-cascading deletes are using the association
     * conditions for updating associated records.
     *
     * @return void
     */
    public function testHasManyNonDependentNonCascadingUnlinkUpdateUsesAssociationConditions()
    {
        $Authors = TableRegistry::get('Authors');
        $Authors->associations()->removeAll();
        $Articles = $Authors->hasMany('Articles', [
            'dependent' => false,
            'cascadeCallbacks' => false,
            'saveStrategy' => HasMany::SAVE_REPLACE,
            'conditions' => [
                'Articles.published' => 'Y'
            ]
        ]);

        $author = $Authors->newEntity([
            'name' => 'Name',
            'articles' => [
                [
                    'title' => 'First article',
                    'body' => 'First article',
                    'published' => 'Y'
                ],
                [
                    'title' => 'Second article',
                    'body' => 'Second article',
                    'published' => 'Y'
                ]
            ]
        ]);
        $author = $Authors->save($author);
        $this->assertNotEmpty($author);

        $article3 = $Articles->target()->newEntity([
            'author_id' => $author->get('id'),
            'title' => 'Third article',
            'body' => 'Third article',
            'published' => 'N'
        ]);
        $article3 = $Articles->target()->save($article3);
        $this->assertNotEmpty($article3);

        $this->assertEquals(3, $Articles->target()->find()->where(['Articles.author_id' => $author->get('id')])->count());

        $article2 = $author->articles[1];
        unset($author->articles[1]);
        $author->dirty('articles', true);

        $author = $Authors->save($author);
        $this->assertNotEmpty($author);

        // Given the association condition of `'Articles.published' => 'Y'`,
        // it is expected that only one the three linked articles are actually
        // being unlinked (nulled), as only one of them matches the association
        // conditions.
        $this->assertEquals(2, $Articles->target()->find()->where(['Articles.author_id' => $author->get('id')])->count());
        $this->assertNull($Articles->get($article2->get('id'))->get('author_id'));
        $this->assertEquals($author->get('id'), $Articles->get($article3->get('id'))->get('author_id'));
    }

    /**
     * Test that saving a new entity with a Primary Key set does not call exists when checkExisting is false.
     *
     * @group save
     * @return void
     */
    public function testSavePrimaryKeyEntityNoExists()
    {
        $this->skipIfSqlServer();
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['exists'])
            ->setConstructorArgs([[
                'connection' => $this->connection,
                'alias' => 'Users',
                'table' => 'users',
            ]])
            ->getMock();
        $entity = $table->newEntity(['id' => 20, 'username' => 'mark']);
        $this->assertTrue($entity->isNew());

        $table->expects($this->never())->method('exists');
        $this->assertSame($entity, $table->save($entity, ['checkExisting' => false]));
    }

    /**
     * Tests that saving an entity will filter out properties that
     * are not present in the table schema when saving
     *
     * @group save
     * @return void
     */
    public function testSaveEntityOnlySchemaFields()
    {
        $entity = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'password' => 'root',
            'crazyness' => 'super crazy value',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00'),
        ]);
        $table = TableRegistry::get('users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals($entity->id, self::$nextUserId);

        $row = $table->find('all')->where(['id' => self::$nextUserId])->first();
        $entity->unsetProperty('crazyness');
        $this->assertEquals($entity->toArray(), $row->toArray());
    }

    /**
     * Tests that it is possible to modify data from the beforeSave callback
     *
     * @group save
     * @return void
     */
    public function testBeforeSaveModifyData()
    {
        $table = TableRegistry::get('users');
        $data = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);
        $listener = function ($e, $entity, $options) use ($data) {
            $this->assertSame($data, $entity);
            $entity->set('password', 'foo');
        };
        $table->eventManager()->on('Model.beforeSave', $listener);
        $this->assertSame($data, $table->save($data));
        $this->assertEquals($data->id, self::$nextUserId);
        $row = $table->find('all')->where(['id' => self::$nextUserId])->first();
        $this->assertEquals('foo', $row->get('password'));
    }

    /**
     * Tests that it is possible to modify the options array in beforeSave
     *
     * @group save
     * @return void
     */
    public function testBeforeSaveModifyOptions()
    {
        $table = TableRegistry::get('users');
        $data = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'password' => 'foo',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);
        $listener1 = function ($e, $entity, $options) {
            $options['crazy'] = true;
        };
        $listener2 = function ($e, $entity, $options) {
            $this->assertTrue($options['crazy']);
        };
        $table->eventManager()->on('Model.beforeSave', $listener1);
        $table->eventManager()->on('Model.beforeSave', $listener2);
        $this->assertSame($data, $table->save($data));
        $this->assertEquals($data->id, self::$nextUserId);

        $row = $table->find('all')->where(['id' => self::$nextUserId])->first();
        $this->assertEquals($data->toArray(), $row->toArray());
    }

    /**
     * Tests that it is possible to stop the saving altogether, without implying
     * the save operation failed
     *
     * @group save
     * @return void
     */
    public function testBeforeSaveStopEvent()
    {
        $table = TableRegistry::get('users');
        $data = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);
        $listener = function ($e, $entity) {
            $e->stopPropagation();

            return $entity;
        };
        $table->eventManager()->on('Model.beforeSave', $listener);
        $this->assertSame($data, $table->save($data));
        $this->assertNull($data->id);
        $row = $table->find('all')->where(['id' => self::$nextUserId])->first();
        $this->assertNull($row);
    }

    /**
     * Asserts that afterSave callback is called on successful save
     *
     * @group save
     * @return void
     */
    public function testAfterSave()
    {
        $table = TableRegistry::get('users');
        $data = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);

        $called = false;
        $listener = function ($e, $entity, $options) use ($data, &$called) {
            $this->assertSame($data, $entity);
            $this->assertTrue($entity->dirty());
            $called = true;
        };
        $table->eventManager()->on('Model.afterSave', $listener);

        $calledAfterCommit = false;
        $listenerAfterCommit = function ($e, $entity, $options) use ($data, &$calledAfterCommit) {
            $this->assertSame($data, $entity);
            $this->assertFalse($entity->dirty());
            $calledAfterCommit = true;
        };
        $table->eventManager()->on('Model.afterSaveCommit', $listenerAfterCommit);

        $this->assertSame($data, $table->save($data));
        $this->assertEquals($data->id, self::$nextUserId);
        $this->assertTrue($called);
        $this->assertTrue($calledAfterCommit);
    }

    /**
     * Asserts that afterSaveCommit is also triggered for non-atomic saves
     *
     * @return void
     */
    public function testAfterSaveCommitForNonAtomic()
    {
        $table = TableRegistry::get('users');
        $data = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);

        $called = false;
        $listener = function ($e, $entity, $options) use ($data, &$called) {
            $this->assertSame($data, $entity);
            $called = true;
        };
        $table->eventManager()->on('Model.afterSave', $listener);

        $calledAfterCommit = false;
        $listenerAfterCommit = function ($e, $entity, $options) use ($data, &$calledAfterCommit) {
            $calledAfterCommit = true;
        };
        $table->eventManager()->on('Model.afterSaveCommit', $listenerAfterCommit);

        $this->assertSame($data, $table->save($data, ['atomic' => false]));
        $this->assertEquals($data->id, self::$nextUserId);
        $this->assertTrue($called);
        $this->assertTrue($calledAfterCommit);
    }

    /**
     * Asserts the afterSaveCommit is not triggered if transaction is running.
     *
     * @return void
     */
    public function testAfterSaveCommitWithTransactionRunning()
    {
        $table = TableRegistry::get('users');
        $data = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);

        $called = false;
        $listener = function ($e, $entity, $options) use (&$called) {
            $called = true;
        };
        $table->eventManager()->on('Model.afterSaveCommit', $listener);

        $this->connection->begin();
        $this->assertSame($data, $table->save($data));
        $this->assertFalse($called);
        $this->connection->commit();
    }

    /**
     * Asserts the afterSaveCommit is not triggered if transaction is running.
     *
     * @return void
     */
    public function testAfterSaveCommitWithNonAtomicAndTransactionRunning()
    {
        $table = TableRegistry::get('users');
        $data = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);

        $called = false;
        $listener = function ($e, $entity, $options) use (&$called) {
            $called = true;
        };
        $table->eventManager()->on('Model.afterSaveCommit', $listener);

        $this->connection->begin();
        $this->assertSame($data, $table->save($data, ['atomic' => false]));
        $this->assertFalse($called);
        $this->connection->commit();
    }

    /**
     * Asserts that afterSave callback not is called on unsuccessful save
     *
     * @group save
     * @return void
     */
    public function testAfterSaveNotCalled()
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['query'])
            ->setConstructorArgs([['table' => 'users', 'connection' => $this->connection]])
            ->getMock();
        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['execute', 'addDefaultTypes'])
            ->setConstructorArgs([null, $table])
            ->getMock();
        $statement = $this->getMockBuilder('\Cake\Database\Statement\StatementDecorator')->getMock();
        $data = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);

        $table->expects($this->once())->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('execute')
            ->will($this->returnValue($statement));

        $statement->expects($this->once())->method('rowCount')
            ->will($this->returnValue(0));

        $called = false;
        $listener = function ($e, $entity, $options) use ($data, &$called) {
            $called = true;
        };
        $table->eventManager()->on('Model.afterSave', $listener);

        $calledAfterCommit = false;
        $listenerAfterCommit = function ($e, $entity, $options) use ($data, &$calledAfterCommit) {
            $calledAfterCommit = true;
        };
        $table->eventManager()->on('Model.afterSaveCommit', $listenerAfterCommit);

        $this->assertFalse($table->save($data));
        $this->assertFalse($called);
        $this->assertFalse($calledAfterCommit);
    }

    /**
     * Asserts that afterSaveCommit callback is triggered only for primary table
     *
     * @group save
     * @return void
     */
    public function testAfterSaveCommitTriggeredOnlyForPrimaryTable()
    {
        $entity = new \Cake\ORM\Entity([
            'title' => 'A Title',
            'body' => 'A body'
        ]);
        $entity->author = new \Cake\ORM\Entity([
            'name' => 'Jose'
        ]);

        $table = TableRegistry::get('articles');
        $table->belongsTo('authors');

        $calledForArticle = false;
        $listenerForArticle = function ($e, $entity, $options) use (&$calledForArticle) {
            $calledForArticle = true;
        };
        $table->eventManager()->on('Model.afterSaveCommit', $listenerForArticle);

        $calledForAuthor = false;
        $listenerForAuthor = function ($e, $entity, $options) use (&$calledForAuthor) {
            $calledForAuthor = true;
        };
        $table->authors->eventManager()->on('Model.afterSaveCommit', $listenerForAuthor);

        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->author->isNew());
        $this->assertTrue($calledForArticle);
        $this->assertFalse($calledForAuthor);
    }

    /**
     * Test that you cannot save rows without a primary key.
     *
     * @group save
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot insert row in "users" table, it has no primary key
     * @return void
     */
    public function testSaveNewErrorOnNoPrimaryKey()
    {
        $entity = new \Cake\ORM\Entity(['username' => 'superuser']);
        $table = TableRegistry::get('users', [
            'schema' => [
                'id' => ['type' => 'integer'],
                'username' => ['type' => 'string'],
            ]
        ]);
        $table->save($entity);
    }

    /**
     * Tests that save is wrapped around a transaction
     *
     * @group save
     * @return void
     */
    public function testAtomicSave()
    {
        $config = ConnectionManager::config('test');

        $connection = $this->getMockBuilder('\Cake\Database\Connection')
            ->setMethods(['begin', 'commit', 'inTransaction'])
            ->setConstructorArgs([$config])
            ->getMock();
        $connection->driver($this->connection->driver());

        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['connection'])
            ->setConstructorArgs([['table' => 'users']])
            ->getMock();
        $table->expects($this->any())->method('connection')
            ->will($this->returnValue($connection));

        $connection->expects($this->once())->method('begin');
        $connection->expects($this->once())->method('commit');
        $connection->expects($this->any())->method('inTransaction')->will($this->returnValue(true));
        $data = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);
        $this->assertSame($data, $table->save($data));
    }

    /**
     * Tests that save will rollback the transaction in the case of an exception
     *
     * @group save
     * @expectedException \PDOException
     * @return void
     */
    public function testAtomicSaveRollback()
    {
        $connection = $this->getMockBuilder('\Cake\Database\Connection')
            ->setMethods(['begin', 'rollback'])
            ->setConstructorArgs([ConnectionManager::config('test')])
            ->getMock();
        $connection->driver(ConnectionManager::get('test')->driver());
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['query', 'connection'])
            ->setConstructorArgs([['table' => 'users']])
            ->getMock();
        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['execute', 'addDefaultTypes'])
            ->setConstructorArgs([null, $table])
            ->getMock();
        $table->expects($this->any())->method('connection')
            ->will($this->returnValue($connection));

        $table->expects($this->once())->method('query')
            ->will($this->returnValue($query));

        $connection->expects($this->once())->method('begin');
        $connection->expects($this->once())->method('rollback');
        $query->expects($this->once())->method('execute')
            ->will($this->throwException(new \PDOException));

        $data = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);
        $table->save($data);
    }

    /**
     * Tests that save will rollback the transaction in the case of an exception
     *
     * @group save
     * @return void
     */
    public function testAtomicSaveRollbackOnFailure()
    {
        $connection = $this->getMockBuilder('\Cake\Database\Connection')
            ->setMethods(['begin', 'rollback'])
            ->setConstructorArgs([ConnectionManager::config('test')])
            ->getMock();
        $connection->driver(ConnectionManager::get('test')->driver());
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['query', 'connection', 'exists'])
            ->setConstructorArgs([['table' => 'users']])
            ->getMock();
        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['execute', 'addDefaultTypes'])
            ->setConstructorArgs([null, $table])
            ->getMock();

        $table->expects($this->any())->method('connection')
            ->will($this->returnValue($connection));

        $table->expects($this->once())->method('query')
            ->will($this->returnValue($query));

        $statement = $this->getMockBuilder('\Cake\Database\Statement\StatementDecorator')->getMock();
        $statement->expects($this->once())
            ->method('rowCount')
            ->will($this->returnValue(0));
        $connection->expects($this->once())->method('begin');
        $connection->expects($this->once())->method('rollback');
        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($statement));

        $data = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);
        $table->save($data);
    }


    /**
     * Tests that only the properties marked as dirty are actually saved
     * to the database
     *
     * @group save
     * @return void
     */
    public function testSaveOnlyDirtyProperties()
    {
        $entity = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'password' => 'root',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);
        $entity->clean();
        $entity->dirty('username', true);
        $entity->dirty('created', true);
        $entity->dirty('updated', true);

        $table = TableRegistry::get('users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertEquals($entity->id, self::$nextUserId);

        $row = $table->find('all')->where(['id' => self::$nextUserId])->first();
        $entity->set('password', null);
        $this->assertEquals($entity->toArray(), $row->toArray());
    }

    /**
     * Tests that a recently saved entity is marked as clean
     *
     * @group save
     * @return void
     */
    public function testASavedEntityIsClean()
    {
        $entity = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'password' => 'root',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);
        $table = TableRegistry::get('users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->dirty('usermane'));
        $this->assertFalse($entity->dirty('password'));
        $this->assertFalse($entity->dirty('created'));
        $this->assertFalse($entity->dirty('updated'));
    }

    /**
     * Tests that a recently saved entity is marked as not new
     *
     * @group save
     * @return void
     */
    public function testASavedEntityIsNotNew()
    {
        $entity = new \Cake\ORM\Entity([
            'username' => 'superuser',
            'password' => 'root',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ]);
        $table = TableRegistry::get('users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
    }

    /**
     * Tests that save can detect automatically if it needs to insert
     * or update a row
     *
     * @group save
     * @return void
     */
    public function testSaveUpdateAuto()
    {
        $entity = new \Cake\ORM\Entity([
            'id' => 2,
            'username' => 'baggins'
        ]);
        $table = TableRegistry::get('users');
        $original = $table->find('all')->where(['id' => 2])->first();
        $this->assertSame($entity, $table->save($entity));

        $row = $table->find('all')->where(['id' => 2])->first();
        $this->assertEquals('baggins', $row->username);
        $this->assertEquals($original->password, $row->password);
        $this->assertEquals($original->created, $row->created);
        $this->assertEquals($original->updated, $row->updated);
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->dirty('id'));
        $this->assertFalse($entity->dirty('username'));
    }

    /**
     * Tests that beforeFind gets the correct isNew() state for the entity
     *
     * @return void
     */
    public function testBeforeSaveGetsCorrectPersistance()
    {
        $entity = new \Cake\ORM\Entity([
            'id' => 2,
            'username' => 'baggins'
        ]);
        $table = TableRegistry::get('users');
        $called = false;
        $listener = function ($event, $entity) use (&$called) {
            $this->assertFalse($entity->isNew());
            $called = true;
        };
        $table->eventManager()->on('Model.beforeSave', $listener);
        $this->assertSame($entity, $table->save($entity));
        $this->assertTrue($called);
    }

    /**
     * Tests that marking an entity as already persisted will prevent the save
     * method from trying to infer the entity's actual status.
     *
     * @group save
     * @return void
     */
    public function testSaveUpdateWithHint()
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['exists'])
            ->setConstructorArgs([['table' => 'users', 'connection' => ConnectionManager::get('test')]])
            ->getMock();
        $entity = new \Cake\ORM\Entity([
            'id' => 2,
            'username' => 'baggins'
        ], ['markNew' => false]);
        $this->assertFalse($entity->isNew());
        $table->expects($this->never())->method('exists');
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests that when updating the primary key is not passed to the list of
     * attributes to change
     *
     * @group save
     * @return void
     */
    public function testSaveUpdatePrimaryKeyNotModified()
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['query'])
            ->setConstructorArgs([['table' => 'users', 'connection' => $this->connection]])
            ->getMock();

        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['execute', 'addDefaultTypes', 'set'])
            ->setConstructorArgs([null, $table])
            ->getMock();

        $table->expects($this->once())->method('query')
            ->will($this->returnValue($query));

        $statement = $this->getMockBuilder('\Cake\Database\Statement\StatementDecorator')->getMock();
        $statement->expects($this->once())
            ->method('errorCode')
            ->will($this->returnValue('00000'));

        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($statement));

        $query->expects($this->once())->method('set')
            ->with(['username' => 'baggins'])
            ->will($this->returnValue($query));

        $entity = new \Cake\ORM\Entity([
            'id' => 2,
            'username' => 'baggins'
        ], ['markNew' => false]);
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests that passing only the primary key to save will not execute any queries
     * but still return success
     *
     * @group save
     * @return void
     */
    public function testUpdateNoChange()
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['query'])
            ->setConstructorArgs([['table' => 'users', 'connection' => $this->connection]])
            ->getMock();
        $table->expects($this->never())->method('query');
        $entity = new \Cake\ORM\Entity([
            'id' => 2,
        ], ['markNew' => false]);
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests that passing only the primary key to save will not execute any queries
     * but still return success
     *
     * @group save
     * @group integration
     * @return void
     */
    public function testUpdateDirtyNoActualChanges()
    {
        $table = TableRegistry::get('Articles');
        $entity = $table->get(1);

        $entity->accessible('*', true);
        $entity->set($entity->toArray());
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Tests that failing to pass a primary key to save will result in exception
     *
     * @group save
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testUpdateNoPrimaryButOtherKeys()
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['query'])
            ->setConstructorArgs([['table' => 'users', 'connection' => $this->connection]])
            ->getMock();
        $table->expects($this->never())->method('query');
        $entity = new \Cake\ORM\Entity([
            'username' => 'mariano',
        ], ['markNew' => false]);
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Test saveMany() with entities array
     *
     * @return void
     */
    public function testSaveManyArray()
    {
        $entities = [
            new Entity(['name' => 'admad']),
            new Entity(['name' => 'dakota'])
        ];

        $table = TableRegistry::get('authors');
        $result = $table->saveMany($entities);

        $this->assertSame($entities, $result);
        $this->assertTrue(isset($result[0]->id));
        foreach ($entities as $entity) {
            $this->assertFalse($entity->isNew());
        }
    }

    /**
     * Test saveMany() with ResultSet instance
     *
     * @return void
     */
    public function testSaveManyResultSet()
    {
        $table = TableRegistry::get('authors');

        $entities = $table->find()
            ->order(['id' => 'ASC'])
            ->all();
        $entities->first()->name = 'admad';

        $result = $table->saveMany($entities);
        $this->assertSame($entities, $result);

        $first = $table->find()
            ->order(['id' => 'ASC'])
            ->first();
        $this->assertSame('admad', $first->name);
    }

    /**
     * Test saveMany() with failed save
     *
     * @return void
     */
    public function testSaveManyFailed()
    {
        $table = TableRegistry::get('authors');
        $entities = [
            new Entity(['name' => 'mark']),
            new Entity(['name' => 'jose'])
        ];
        $entities[1]->errors(['name' => ['message']]);
        $result = $table->saveMany($entities);

        $this->assertFalse($result);
        foreach ($entities as $entity) {
            $this->assertTrue($entity->isNew());
        }
    }

    /**
     * Test simple delete.
     *
     * @return void
     */
    public function testDelete()
    {
        $table = TableRegistry::get('users');
        $conditions = [
            'limit' => 1,
            'conditions' => [
                'username' => 'nate'
            ]
        ];
        $query = $table->find('all', $conditions);
        $entity = $query->first();
        $result = $table->delete($entity);
        $this->assertTrue($result);

        $query = $table->find('all', $conditions);
        $results = $query->execute();
        $this->assertCount(0, $results, 'Find should fail.');
    }

    /**
     * Test delete with dependent records
     *
     * @return void
     */
    public function testDeleteDependent()
    {
        $table = TableRegistry::get('authors');
        $table->hasOne('articles', [
            'foreignKey' => 'author_id',
            'dependent' => true,
        ]);

        $entity = $table->get(1);
        $result = $table->delete($entity);

        $articles = $table->association('articles')->target();
        $query = $articles->find('all', [
            'conditions' => [
                'author_id' => $entity->id
            ]
        ]);
        $this->assertNull($query->all()->first(), 'Should not find any rows.');
    }

    /**
     * Test delete with dependent records
     *
     * @return void
     */
    public function testDeleteDependentHasMany()
    {
        $table = TableRegistry::get('authors');
        $table->hasMany('articles', [
            'foreignKey' => 'author_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);

        $entity = $table->get(1);
        $result = $table->delete($entity);
        $this->assertTrue($result);
    }

    /**
     * Test delete with dependent = false does not cascade.
     *
     * @return void
     */
    public function testDeleteNoDependentNoCascade()
    {
        $table = TableRegistry::get('authors');
        $table->hasMany('article', [
            'foreignKey' => 'author_id',
            'dependent' => false,
        ]);

        $query = $table->find('all')->where(['id' => 1]);
        $entity = $query->first();
        $result = $table->delete($entity);

        $articles = $table->association('articles')->target();
        $query = $articles->find('all')->where(['author_id' => $entity->id]);
        $this->assertCount(2, $query->execute(), 'Should find rows.');
    }

    /**
     * Test delete with BelongsToMany
     *
     * @return void
     */
    public function testDeleteBelongsToMany()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tag', [
            'foreignKey' => 'article_id',
            'joinTable' => 'articles_tags'
        ]);
        $query = $table->find('all')->where(['id' => 1]);
        $entity = $query->first();
        $table->delete($entity);

        $junction = $table->association('tags')->junction();
        $query = $junction->find('all')->where(['article_id' => 1]);
        $this->assertNull($query->all()->first(), 'Should not find any rows.');
    }

    /**
     * Test delete with dependent records belonging to an aliased
     * belongsToMany association.
     *
     * @return void
     */
    public function testDeleteDependentAliased()
    {
        $Authors = TableRegistry::get('authors');
        $Authors->associations()->removeAll();
        $Articles = TableRegistry::get('articles');
        $Articles->associations()->removeAll();

        $Authors->hasMany('AliasedArticles', [
            'className' => 'articles',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $Articles->belongsToMany('Tags');

        $author = $Authors->get(1);
        $result = $Authors->delete($author);

        $this->assertTrue($result);
    }

    /**
     * Test that cascading associations are deleted first.
     *
     * @return void
     */
    public function testDeleteAssociationsCascadingCallbacksOrder()
    {
        $groups = TableRegistry::get('Groups');
        $members = TableRegistry::get('Members');
        $groupsMembers = TableRegistry::get('GroupsMembers');

        $groups->belongsToMany('Members');
        $groups->hasMany('GroupsMembers', [
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $groupsMembers->belongsTo('Members');
        $groupsMembers->addBehavior('CounterCache', [
            'Members' => ['group_count']
        ]);

        $member = $members->get(1);
        $this->assertEquals(2, $member->group_count);

        $group = $groups->get(1);
        $groups->delete($group);

        $member = $members->get(1);
        $this->assertEquals(1, $member->group_count);
    }

    /**
     * Test delete callbacks
     *
     * @return void
     */
    public function testDeleteCallbacks()
    {
        $entity = new \Cake\ORM\Entity(['id' => 1, 'name' => 'mark']);
        $options = new \ArrayObject(['atomic' => true, 'checkRules' => false, '_primary' => true]);

        $mock = $this->getMockBuilder('Cake\Event\EventManager')->getMock();

        $mock->expects($this->at(0))
            ->method('on');

        $mock->expects($this->at(1))
            ->method('dispatch');

        $mock->expects($this->at(2))
            ->method('dispatch')
            ->with($this->logicalAnd(
                $this->attributeEqualTo('_name', 'Model.beforeDelete'),
                $this->attributeEqualTo(
                    'data',
                    ['entity' => $entity, 'options' => $options]
                )
            ));

        $mock->expects($this->at(3))
            ->method('dispatch')
            ->with($this->logicalAnd(
                $this->attributeEqualTo('_name', 'Model.afterDelete'),
                $this->attributeEqualTo(
                    'data',
                    ['entity' => $entity, 'options' => $options]
                )
            ));

        $mock->expects($this->at(4))
            ->method('dispatch')
            ->with($this->logicalAnd(
                $this->attributeEqualTo('_name', 'Model.afterDeleteCommit'),
                $this->attributeEqualTo(
                    'data',
                    ['entity' => $entity, 'options' => $options]
                )
            ));

        $table = TableRegistry::get('users', ['eventManager' => $mock]);
        $entity->isNew(false);
        $table->delete($entity, ['checkRules' => false]);
    }

    /**
     * Test afterDeleteCommit is also called for non-atomic delete
     *
     * @return void
     */
    public function testDeleteCallbacksNonAtomic()
    {
        $table = TableRegistry::get('users');

        $data = $table->get(1);
        $options = new \ArrayObject(['atomic' => false, 'checkRules' => false]);

        $called = false;
        $listener = function ($e, $entity, $options) use ($data, &$called) {
            $this->assertSame($data, $entity);
            $called = true;
        };
        $table->eventManager()->on('Model.afterDelete', $listener);

        $calledAfterCommit = false;
        $listenerAfterCommit = function ($e, $entity, $options) use ($data, &$calledAfterCommit) {
            $calledAfterCommit = true;
        };
        $table->eventManager()->on('Model.afterDeleteCommit', $listenerAfterCommit);

        $table->delete($data, ['atomic' => false]);
        $this->assertTrue($called);
        $this->assertTrue($calledAfterCommit);
    }

    /**
     * Test that afterDeleteCommit is only triggered for primary table
     *
     * @return void
     */
    public function testAfterDeleteCommitTriggeredOnlyForPrimaryTable()
    {
        $table = TableRegistry::get('authors');
        $table->hasOne('articles', [
            'foreignKey' => 'author_id',
            'dependent' => true,
        ]);

        $called = false;
        $listener = function ($e, $entity, $options) use (&$called) {
            $called = true;
        };
        $table->eventManager()->on('Model.afterDeleteCommit', $listener);

        $called2 = false;
        $listener = function ($e, $entity, $options) use (&$called2) {
            $called2 = true;
        };
        $table->articles->eventManager()->on('Model.afterDeleteCommit', $listener);

        $entity = $table->get(1);
        $this->assertTrue($table->delete($entity));

        $this->assertTrue($called);
        $this->assertFalse($called2);
    }

    /**
     * Test delete beforeDelete can abort the delete.
     *
     * @return void
     */
    public function testDeleteBeforeDeleteAbort()
    {
        $entity = new \Cake\ORM\Entity(['id' => 1, 'name' => 'mark']);
        $options = new \ArrayObject(['atomic' => true, 'cascade' => true]);

        $mock = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $mock->expects($this->at(2))
            ->method('dispatch')
            ->will($this->returnCallback(function ($event) {
                $event->stopPropagation();
            }));

        $table = TableRegistry::get('users', ['eventManager' => $mock]);
        $entity->isNew(false);
        $result = $table->delete($entity, ['checkRules' => false]);
        $this->assertNull($result);
    }

    /**
     * Test delete beforeDelete return result
     *
     * @return void
     */
    public function testDeleteBeforeDeleteReturnResult()
    {
        $entity = new \Cake\ORM\Entity(['id' => 1, 'name' => 'mark']);
        $options = new \ArrayObject(['atomic' => true, 'cascade' => true]);

        $mock = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $mock->expects($this->at(2))
            ->method('dispatch')
            ->will($this->returnCallback(function ($event) {
                $event->stopPropagation();
                $event->result = 'got stopped';
            }));

        $table = TableRegistry::get('users', ['eventManager' => $mock]);
        $entity->isNew(false);
        $result = $table->delete($entity, ['checkRules' => false]);
        $this->assertEquals('got stopped', $result);
    }

    /**
     * Test deleting new entities does nothing.
     *
     * @return void
     */
    public function testDeleteIsNew()
    {
        $entity = new \Cake\ORM\Entity(['id' => 1, 'name' => 'mark']);

        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['query'])
            ->setConstructorArgs([['connection' => $this->connection]])
            ->getMock();
        $table->expects($this->never())
            ->method('query');

        $entity->isNew(true);
        $result = $table->delete($entity);
        $this->assertFalse($result);
    }

    /**
     * test hasField()
     *
     * @return void
     */
    public function testHasField()
    {
        $table = TableRegistry::get('articles');
        $this->assertFalse($table->hasField('nope'), 'Should not be there.');
        $this->assertTrue($table->hasField('title'), 'Should be there.');
        $this->assertTrue($table->hasField('body'), 'Should be there.');
    }

    /**
     * Tests that there exists a default validator
     *
     * @return void
     */
    public function testValidatorDefault()
    {
        $table = new Table();
        $validator = $table->validator();
        $this->assertSame($table, $validator->provider('table'));
        $this->assertInstanceOf('Cake\Validation\Validator', $validator);
        $default = $table->validator('default');
        $this->assertSame($validator, $default);
    }

    /**
     * Tests that it is possible to define custom validator methods
     *
     * @return void
     */
    public function testValidationWithDefiner()
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['validationForOtherStuff'])
            ->getMock();
        $table->expects($this->once())->method('validationForOtherStuff')
            ->will($this->returnArgument(0));
        $other = $table->validator('forOtherStuff');
        $this->assertInstanceOf('Cake\Validation\Validator', $other);
        $this->assertNotSame($other, $table->validator());
        $this->assertSame($table, $other->provider('table'));
    }

    /**
     * Tests that a RuntimeException is thrown if the custom validator does not return an Validator instance
     *
     * @return void
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The Cake\ORM\Table::validationBad() validation method must return an instance of Cake\Validation\Validator.
     */
    public function testValidationWithBadDefiner()
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['validationBad'])
            ->getMock();
        $table->expects($this->once())
            ->method('validationBad');
        $table->validator('bad');
    }

    /**
     * Tests that it is possible to set a custom validator under a name
     *
     * @return void
     */
    public function testValidatorSetter()
    {
        $table = new Table;
        $validator = new \Cake\Validation\Validator;
        $table->validator('other', $validator);
        $this->assertSame($validator, $table->validator('other'));
        $this->assertSame($table, $validator->provider('table'));
    }

    /**
     * Tests that the source of an existing Entity is the same as a new one
     *
     * @return void
     */
    public function testEntitySourceExistingAndNew()
    {
        Plugin::load('TestPlugin');
        $table = TableRegistry::get('TestPlugin.Authors');

        $existingAuthor = $table->find()->first();
        $newAuthor = $table->newEntity();

        $this->assertEquals('TestPlugin.Authors', $existingAuthor->source());
        $this->assertEquals('TestPlugin.Authors', $newAuthor->source());
    }

    /**
     * Tests that calling an entity with an empty array will run validation
     * whereas calling it with no parameters will not run any validation.
     *
     * @return void
     */
    public function testNewEntityAndValidation()
    {
        $table = TableRegistry::get('Articles');
        $validator = $table->validator()->requirePresence('title');
        $entity = $table->newEntity([]);
        $errors = $entity->errors();
        $this->assertNotEmpty($errors['title']);

        $entity = $table->newEntity();
        $this->assertEmpty($entity->errors());
    }

    /**
     * Test magic findByXX method.
     *
     * @return void
     */
    public function testMagicFindDefaultToAll()
    {
        $table = TableRegistry::get('Users');

        $result = $table->findByUsername('garrett');
        $this->assertInstanceOf('Cake\ORM\Query', $result);

        $expected = new QueryExpression(['Users.username' => 'garrett'], $this->usersTypeMap);
        $this->assertEquals($expected, $result->clause('where'));
    }

    /**
     * Test magic findByXX errors on missing arguments.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not enough arguments for magic finder. Got 0 required 1
     * @return void
     */
    public function testMagicFindError()
    {
        $table = TableRegistry::get('Users');

        $table->findByUsername();
    }

    /**
     * Test magic findByXX errors on missing arguments.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not enough arguments for magic finder. Got 1 required 2
     * @return void
     */
    public function testMagicFindErrorMissingField()
    {
        $table = TableRegistry::get('Users');

        $table->findByUsernameAndId('garrett');
    }

    /**
     * Test magic findByXX errors when there is a mix of or & and.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Cannot mix "and" & "or" in a magic finder. Use find() instead.
     * @return void
     */
    public function testMagicFindErrorMixOfOperators()
    {
        $table = TableRegistry::get('Users');

        $table->findByUsernameAndIdOrPassword('garrett', 1, 'sekret');
    }

    /**
     * Test magic findByXX method.
     *
     * @return void
     */
    public function testMagicFindFirstAnd()
    {
        $table = TableRegistry::get('Users');

        $result = $table->findByUsernameAndId('garrett', 4);
        $this->assertInstanceOf('Cake\ORM\Query', $result);

        $expected = new QueryExpression(['Users.username' => 'garrett', 'Users.id' => 4], $this->usersTypeMap);
        $this->assertEquals($expected, $result->clause('where'));
    }

    /**
     * Test magic findByXX method.
     *
     * @return void
     */
    public function testMagicFindFirstOr()
    {
        $table = TableRegistry::get('Users');

        $result = $table->findByUsernameOrId('garrett', 4);
        $this->assertInstanceOf('Cake\ORM\Query', $result);

        $expected = new QueryExpression([], $this->usersTypeMap);
        $expected->add(
            [
            'OR' => [
                'Users.username' => 'garrett',
                'Users.id' => 4
            ]]
        );
        $this->assertEquals($expected, $result->clause('where'));
    }

    /**
     * Test magic findAllByXX method.
     *
     * @return void
     */
    public function testMagicFindAll()
    {
        $table = TableRegistry::get('Articles');

        $result = $table->findAllByAuthorId(1);
        $this->assertInstanceOf('Cake\ORM\Query', $result);
        $this->assertNull($result->clause('limit'));

        $expected = new QueryExpression(['Articles.author_id' => 1], $this->articlesTypeMap);
        $this->assertEquals($expected, $result->clause('where'));
    }

    /**
     * Test magic findAllByXX method.
     *
     * @return void
     */
    public function testMagicFindAllAnd()
    {
        $table = TableRegistry::get('Users');

        $result = $table->findAllByAuthorIdAndPublished(1, 'Y');
        $this->assertInstanceOf('Cake\ORM\Query', $result);
        $this->assertNull($result->clause('limit'));
        $expected = new QueryExpression(
            ['Users.author_id' => 1, 'Users.published' => 'Y'],
            $this->usersTypeMap
        );
        $this->assertEquals($expected, $result->clause('where'));
    }

    /**
     * Test magic findAllByXX method.
     *
     * @return void
     */
    public function testMagicFindAllOr()
    {
        $table = TableRegistry::get('Users');

        $result = $table->findAllByAuthorIdOrPublished(1, 'Y');
        $this->assertInstanceOf('Cake\ORM\Query', $result);
        $this->assertNull($result->clause('limit'));
        $expected = new QueryExpression();
        $expected->typeMap()->defaults($this->usersTypeMap->toArray());
        $expected->add(
            ['or' => ['Users.author_id' => 1, 'Users.published' => 'Y']]
        );
        $this->assertEquals($expected, $result->clause('where'));
        $this->assertNull($result->clause('order'));
    }

    /**
     * Test the behavior method.
     *
     * @return void
     */
    public function testBehaviorIntrospection()
    {
        $table = TableRegistry::get('users');

        $table->addBehavior('Timestamp');
        $this->assertTrue($table->hasBehavior('Timestamp'), 'should be true on loaded behavior');
        $this->assertFalse($table->hasBehavior('Tree'), 'should be false on unloaded behavior');
    }

    /**
     * Tests saving belongsTo association
     *
     * @group save
     * @return void
     */
    public function testSaveBelongsTo()
    {
        $entity = new \Cake\ORM\Entity([
            'title' => 'A Title',
            'body' => 'A body'
        ]);
        $entity->author = new \Cake\ORM\Entity([
            'name' => 'Jose'
        ]);

        $table = TableRegistry::get('articles');
        $table->belongsTo('authors');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->author->isNew());
        $this->assertEquals(5, $entity->author->id);
        $this->assertEquals(5, $entity->get('author_id'));
    }

    /**
     * Tests saving hasOne association
     *
     * @group save
     * @return void
     */
    public function testSaveHasOne()
    {
        $entity = new \Cake\ORM\Entity([
            'name' => 'Jose'
        ]);
        $entity->article = new \Cake\ORM\Entity([
            'title' => 'A Title',
            'body' => 'A body'
        ]);

        $table = TableRegistry::get('authors');
        $table->hasOne('articles');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->article->isNew());
        $this->assertEquals(4, $entity->article->id);
        $this->assertEquals(5, $entity->article->get('author_id'));
        $this->assertFalse($entity->article->dirty('author_id'));
    }

    /**
     * Tests saving associations only saves associations
     * if they are entities.
     *
     * @group save
     * @return void
     */
    public function testSaveOnlySaveAssociatedEntities()
    {
        $entity = new \Cake\ORM\Entity([
            'name' => 'Jose'
        ]);

        // Not an entity.
        $entity->article = [
            'title' => 'A Title',
            'body' => 'A body'
        ];

        $table = TableRegistry::get('authors');
        $table->hasOne('articles');

        $table->save($entity);
        $this->assertFalse($entity->isNew());
        $this->assertInternalType('array', $entity->article);
    }

    /**
     * Tests saving multiple entities in a hasMany association
     *
     * @return void
     */
    public function testSaveHasMany()
    {
        $entity = new \Cake\ORM\Entity([
            'name' => 'Jose'
        ]);
        $entity->articles = [
            new \Cake\ORM\Entity([
                'title' => 'A Title',
                'body' => 'A body'
            ]),
            new \Cake\ORM\Entity([
                'title' => 'Another Title',
                'body' => 'Another body'
            ])
        ];

        $table = TableRegistry::get('authors');
        $table->hasMany('articles');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->articles[0]->isNew());
        $this->assertFalse($entity->articles[1]->isNew());
        $this->assertEquals(4, $entity->articles[0]->id);
        $this->assertEquals(5, $entity->articles[1]->id);
        $this->assertEquals(5, $entity->articles[0]->author_id);
        $this->assertEquals(5, $entity->articles[1]->author_id);
    }

    /**
     * Tests overwriting hasMany associations in an integration scenario.
     *
     * @return void
     */
    public function testSaveHasManyOverwrite()
    {
        $table = TableRegistry::get('authors');
        $table->hasMany('articles');

        $entity = $table->get(3, ['contain' => ['articles']]);
        $data = [
            'name' => 'big jose',
            'articles' => [
                [
                    'id' => 2,
                    'title' => 'New title'
                ]
            ]
        ];
        $entity = $table->patchEntity($entity, $data, ['associated' => 'articles']);
        $this->assertSame($entity, $table->save($entity));

        $entity = $table->get(3, ['contain' => ['articles']]);
        $this->assertEquals('big jose', $entity->name, 'Author did not persist');
        $this->assertEquals('New title', $entity->articles[0]->title, 'Article did not persist');
    }

    /**
     * Tests saving belongsToMany records
     *
     * @group save
     * @return void
     */
    public function testSaveBelongsToMany()
    {
        $entity = new \Cake\ORM\Entity([
            'title' => 'A Title',
            'body' => 'A body'
        ]);
        $entity->tags = [
            new \Cake\ORM\Entity([
                'name' => 'Something New'
            ]),
            new \Cake\ORM\Entity([
                'name' => 'Another Something'
            ])
        ];
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');
        $this->assertSame($entity, $table->save($entity));
        $this->assertFalse($entity->isNew());
        $this->assertFalse($entity->tags[0]->isNew());
        $this->assertFalse($entity->tags[1]->isNew());
        $this->assertEquals(4, $entity->tags[0]->id);
        $this->assertEquals(5, $entity->tags[1]->id);
        $this->assertEquals(4, $entity->tags[0]->_joinData->article_id);
        $this->assertEquals(4, $entity->tags[1]->_joinData->article_id);
        $this->assertEquals(4, $entity->tags[0]->_joinData->tag_id);
        $this->assertEquals(5, $entity->tags[1]->_joinData->tag_id);
    }

    /**
     * Tests saving belongsToMany records when record exists.
     *
     * @group save
     * @return void
     */
    public function testSaveBelongsToManyJoinDataOnExistingRecord()
    {
        $tags = TableRegistry::get('Tags');
        $table = TableRegistry::get('Articles');
        $table->belongsToMany('Tags');

        $entity = $table->find()->contain('Tags')->first();
        // not associated to the article already.
        $entity->tags[] = $tags->get(3);
        $entity->dirty('tags', true);

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
     *
     * @return void
     */
    public function testSaveBelongsToManyJoinData()
    {
        $articles = TableRegistry::get('Articles');
        $article = $articles->get(1, ['contain' => ['tags']]);
        $data = [
            'tags' => [
                ['id' => 1, '_joinData' => ['highlighted' => 1]],
                ['id' => 3]
            ]
        ];
        $article = $articles->patchEntity($article, $data);
        $result = $articles->save($article);
        $this->assertSame($result, $article);
    }

    /**
     * Test to check that association condition are used when fetching existing
     * records to decide which records to unlink.
     *
     * @return void
     */
    public function testPolymorphicBelongsToManySave()
    {
        $articles = TableRegistry::get('Articles');
        $articles->belongsToMany('Tags', [
            'through' => 'PolymorphicTagged',
            'foreignKey' => 'foreign_key',
            'conditions' => [
                'PolymorphicTagged.foreign_model' => 'Articles'
            ],
            'sort' => ['PolymorphicTagged.position' => 'ASC']
        ]);

        $articles->Tags->junction()->belongsTo('Tags');

        $entity = $articles->get(1, ['contain' => ['Tags']]);
        $data = [
            'id' => 1,
            'tags' => [
                [
                    'id' => 1,
                    '_joinData' => [
                        'id' => 2,
                        'foreign_model' => 'Articles',
                        'position' => 2
                    ]
                ],
                [
                    'id' => 2,
                    '_joinData' => [
                        'foreign_model' => 'Articles',
                        'position' => 1
                    ]
                ]
            ]
        ];
        $entity = $articles->patchEntity($entity, $data, ['associated' => ['Tags._joinData']]);
        $entity = $articles->save($entity);

        $expected = [
            [
                'id' => 1,
                'tag_id' => 1,
                'foreign_key' => 1,
                'foreign_model' => 'Posts',
                'position' => 1
            ],
            [
                'id' => 2,
                'tag_id' => 1,
                'foreign_key' => 1,
                'foreign_model' => 'Articles',
                'position' => 2
            ],
            [
                'id' => 3,
                'tag_id' => 2,
                'foreign_key' => 1,
                'foreign_model' => 'Articles',
                'position' => 1
            ]
        ];
        $result = TableRegistry::get('PolymorphicTagged')
            ->find('all', ['sort' => ['id' => 'DESC']])
            ->hydrate(false)
            ->toArray();
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests saving belongsToMany records can delete all links.
     *
     * @group save
     * @return void
     */
    public function testSaveBelongsToManyDeleteAllLinks()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags', [
            'saveStrategy' => 'replace',
        ]);

        $entity = $table->get(1, ['contain' => 'tags']);
        $this->assertCount(2, $entity->tags, 'Fixture data did not change.');

        $entity->tags = [];
        $result = $table->save($entity);
        $this->assertSame($result, $entity);
        $this->assertSame([], $entity->tags, 'No tags on the entity.');

        $entity = $table->get(1, ['contain' => 'tags']);
        $this->assertSame([], $entity->tags, 'No tags in the db either.');
    }

    /**
     * Tests saving belongsToMany records can delete some links.
     *
     * @group save
     * @return void
     */
    public function testSaveBelongsToManyDeleteSomeLinks()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags', [
            'saveStrategy' => 'replace',
        ]);

        $entity = $table->get(1, ['contain' => 'tags']);
        $this->assertCount(2, $entity->tags, 'Fixture data did not change.');

        $tag = new \Cake\ORM\Entity([
            'id' => 2,
        ]);
        $entity->tags = [$tag];
        $result = $table->save($entity);
        $this->assertSame($result, $entity);
        $this->assertCount(1, $entity->tags, 'Only one tag left.');
        $this->assertEquals($tag, $entity->tags[0]);

        $entity = $table->get(1, ['contain' => 'tags']);
        $this->assertCount(1, $entity->tags, 'Only one tag in the db.');
        $this->assertEquals($tag->id, $entity->tags[0]->id);
    }

    /**
     * Test that belongsToMany ignores non-entity data.
     *
     * @return void
     */
    public function testSaveBelongsToManyIgnoreNonEntityData()
    {
        $articles = TableRegistry::get('articles');
        $article = $articles->get(1, ['contain' => ['tags']]);
        $article->tags = [
            '_ids' => [2, 1]
        ];
        $result = $articles->save($article);
        $this->assertSame($result, $article);
    }

    /**
     * Test that a save call takes a SaveOptionBuilder object as well.
     *
     * @group save
     * @return void
     */
    public function testSaveWithOptionBuilder()
    {
        $articles = new Table([
            'table' => 'articles',
            'connection' => $this->connection,
        ]);
        $articles->belongsTo('Authors');

        $optionBuilder = new SaveOptionsBuilder($articles, [
            'associated' => [
                'Authors'
            ]
        ]);

        $entity = $articles->newEntity([
            'title' => 'test save options',
            'author' => [
                'name' => 'author name'
            ]
        ]);

        $articles->save($entity, $optionBuilder);
        $this->assertFalse($entity->isNew());
        $this->assertEquals('test save options', $entity->title);
        $this->assertNotEmpty($entity->id);
        $this->assertNotEmpty($entity->author->id);
        $this->assertEquals('author name', $entity->author->name);

        $entity = $articles->newEntity([
            'title' => 'test save options 2',
            'author' => [
                'name' => 'author name'
            ]
        ]);

        $optionBuilder = new SaveOptionsBuilder($articles, [
            'associated' => []
        ]);

        $articles->save($entity, $optionBuilder);
        $this->assertFalse($entity->isNew());
        $this->assertEquals('test save options 2', $entity->title);
        $this->assertNotEmpty($entity->id);
        $this->assertEmpty($entity->author->id);
        $this->assertTrue($entity->author->isNew());
    }

    /**
     * Tests that saving a persisted and clean entity will is a no-op
     *
     * @group save
     * @return void
     */
    public function testSaveCleanEntity()
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['_processSave'])
            ->getMock();
        $entity = new \Cake\ORM\Entity(
            ['id' => 'foo'],
            ['markNew' => false, 'markClean' => true]
        );
        $table->expects($this->never())->method('_processSave');
        $this->assertSame($entity, $table->save($entity));
    }

    /**
     * Integration test to show how to append a new tag to an article
     *
     * @group save
     * @return void
     */
    public function testBelongsToManyIntegration()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');
        $article = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
        $tags = $article->tags;
        $this->assertNotEmpty($tags);
        $tags[] = new \TestApp\Model\Entity\Tag(['name' => 'Something New']);
        $article->tags = $tags;
        $this->assertSame($article, $table->save($article));
        $tags = $article->tags;
        $this->assertCount(3, $tags);
        $this->assertFalse($tags[2]->isNew());
        $this->assertEquals(4, $tags[2]->id);
        $this->assertEquals(1, $tags[2]->_joinData->article_id);
        $this->assertEquals(4, $tags[2]->_joinData->tag_id);
    }

    /**
     * Tests that it is possible to do a deep save and control what associations get saved,
     * while having control of the options passed to each level of the save
     *
     * @group save
     * @return void
     */
    public function testSaveDeepAssociationOptions()
    {
        $articles = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['_insert'])
            ->setConstructorArgs([['table' => 'articles', 'connection' => $this->connection]])
            ->getMock();
        $authors = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['_insert'])
            ->setConstructorArgs([['table' => 'authors', 'connection' => $this->connection]])
            ->getMock();
        $supervisors = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['_insert', 'validate'])
            ->setConstructorArgs([[
                'table' => 'authors',
                'alias' => 'supervisors',
                'connection' => $this->connection
            ]])
            ->getMock();
        $tags = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['_insert'])
            ->setConstructorArgs([['table' => 'tags', 'connection' => $this->connection]])
            ->getMock();

        $articles->belongsTo('authors', ['targetTable' => $authors]);
        $authors->hasOne('supervisors', ['targetTable' => $supervisors]);
        $supervisors->belongsToMany('tags', ['targetTable' => $tags]);

        $entity = new \Cake\ORM\Entity([
            'title' => 'bar',
            'author' => new \Cake\ORM\Entity([
                'name' => 'Juan',
                'supervisor' => new \Cake\ORM\Entity(['name' => 'Marc']),
                'tags' => [
                    new \Cake\ORM\Entity(['name' => 'foo'])
                ]
            ]),
        ]);
        $entity->isNew(true);
        $entity->author->isNew(true);
        $entity->author->supervisor->isNew(true);
        $entity->author->tags[0]->isNew(true);

        $articles->expects($this->once())
            ->method('_insert')
            ->with($entity, ['title' => 'bar'])
            ->will($this->returnValue($entity));

        $authors->expects($this->once())
            ->method('_insert')
            ->with($entity->author, ['name' => 'Juan'])
            ->will($this->returnValue($entity->author));

        $supervisors->expects($this->once())
            ->method('_insert')
            ->with($entity->author->supervisor, ['name' => 'Marc'])
            ->will($this->returnValue($entity->author->supervisor));

        $tags->expects($this->never())->method('_insert');

        $this->assertSame($entity, $articles->save($entity, [
            'associated' => [
                'authors' => [],
                'authors.supervisors' => [
                    'atomic' => false,
                    'associated' => false
                ]
            ]
        ]));
    }

    /**
     * Integration test for linking entities with belongsToMany
     *
     * @return void
     */
    public function testLinkBelongsToMany()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');
        $tagsTable = TableRegistry::get('tags');
        $source = ['source' => 'tags'];
        $options = ['markNew' => false];

        $article = new \Cake\ORM\Entity([
            'id' => 1,
        ], $options);

        $newTag = new \TestApp\Model\Entity\Tag([
            'name' => 'Foo',
            'description' => 'Foo desc',
            'created' => null,
        ], $source);
        $tags[] = new \TestApp\Model\Entity\Tag([
            'id' => 3
        ], $options + $source);
        $tags[] = $newTag;

        $tagsTable->save($newTag);
        $table->association('tags')->link($article, $tags);

        $this->assertEquals($article->tags, $tags);
        foreach ($tags as $tag) {
            $this->assertFalse($tag->isNew());
        }

        $article = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
        $this->assertEquals($article->tags[2]->id, $tags[0]->id);
        $this->assertEquals($article->tags[3], $tags[1]);
    }

    /**
     * Integration test for linking entities with HasMany
     *
     * @return void
     */
    public function testLinkHasMany()
    {
        $authors = TableRegistry::get('Authors');
        $articles = TableRegistry::get('Articles');

        $authors->hasMany('Articles', [
            'foreignKey' => 'author_id'
        ]);

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes'
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers'
                ]
            ]
        );

        $sizeArticles = count($newArticles);

        $this->assertTrue($authors->Articles->link($author, $newArticles));


        $this->assertCount($sizeArticles, $authors->Articles->findAllByAuthorId($author->id));
        $this->assertCount($sizeArticles, $author->articles);
        $this->assertFalse($author->dirty('articles'));
    }

    /**
     * Integration test for linking entities with HasMany combined with ReplaceSaveStrategy. It must append, not unlinking anything
     *
     * @return void
     */
    public function testLinkHasManyReplaceSaveStrategy()
    {
        $authors = TableRegistry::get('Authors');
        $articles = TableRegistry::get('Articles');

        $authors->hasMany('Articles', [
            'foreignKey' => 'author_id',
            'saveStrategy' => 'replace'
        ]);

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes'
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers'
                ]
            ]
        );

        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $sizeArticles = count($newArticles);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'Nothing but the cake',
                    'body' => 'It is all that we need'
                ]
            ]
        );
        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $sizeArticles++;

        $this->assertCount($sizeArticles, $authors->Articles->findAllByAuthorId($author->id));
        $this->assertCount($sizeArticles, $author->articles);
        $this->assertFalse($author->dirty('articles'));
    }

    /**
     * Integration test for linking entities with HasMany. The input contains already linked entities and they should not appeat duplicated
     *
     * @return void
     */
    public function testLinkHasManyExisting()
    {
        $authors = TableRegistry::get('Authors');
        $articles = TableRegistry::get('Articles');

        $authors->hasMany('Articles', [
            'foreignKey' => 'author_id',
            'saveStrategy' => 'replace'
        ]);

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes'
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers'
                ]
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
                        'body' => 'It is all that we need'
                    ]
                ]
            )
        );
        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $sizeArticles++;

        $this->assertCount($sizeArticles, $authors->Articles->findAllByAuthorId($author->id));
        $this->assertCount($sizeArticles, $author->articles);
        $this->assertFalse($author->dirty('articles'));
    }

    /**
     * Integration test for unlinking entities with HasMany. The association property must be cleaned
     *
     * @return void
     */
    public function testUnlinkHasManyCleanProperty()
    {
        $authors = TableRegistry::get('Authors');
        $articles = TableRegistry::get('Articles');

        $authors->hasMany('Articles', [
            'foreignKey' => 'author_id',
            'saveStrategy' => 'replace'
        ]);

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes'
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers'
                ],
                [
                    'title' => 'Creamy cake recipe',
                    'body' => 'chocolate and cream'
                ],
            ]
        );

        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $sizeArticles = count($newArticles);

        $articlesToUnlink = [ $author->articles[0], $author->articles[1] ];

        $authors->Articles->unlink($author, $articlesToUnlink);

        $this->assertCount($sizeArticles - count($articlesToUnlink), $authors->Articles->findAllByAuthorId($author->id));
        $this->assertCount($sizeArticles - count($articlesToUnlink), $author->articles);
        $this->assertFalse($author->dirty('articles'));
    }

    /**
     * Integration test for unlinking entities with HasMany. The association property must stay unchanged
     *
     * @return void
     */
    public function testUnlinkHasManyNotCleanProperty()
    {
        $authors = TableRegistry::get('Authors');
        $articles = TableRegistry::get('Articles');

        $authors->hasMany('Articles', [
            'foreignKey' => 'author_id',
            'saveStrategy' => 'replace'
        ]);

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes'
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers'
                ],
                [
                    'title' => 'Creamy cake recipe',
                    'body' => 'chocolate and cream'
                ],
            ]
        );

        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $sizeArticles = count($newArticles);

        $articlesToUnlink = [ $author->articles[0], $author->articles[1] ];

        $authors->Articles->unlink($author, $articlesToUnlink, ['cleanProperty' => false]);

        $this->assertCount($sizeArticles - count($articlesToUnlink), $authors->Articles->findAllByAuthorId($author->id));
        $this->assertCount($sizeArticles, $author->articles);
        $this->assertFalse($author->dirty('articles'));
    }

    /**
     * Integration test for unlinking entities with HasMany.
     * Checking that no error happens when the hasMany property is originally
     * null
     *
     * @return void
     */
    public function testUnlinkHasManyEmpty()
    {
        $authors = TableRegistry::get('Authors');
        $articles = TableRegistry::get('Articles');
        $authors->hasMany('Articles');
        $author = $authors->get(1);
        $article = $authors->Articles->get(1);

        $authors->Articles->unlink($author, [$article]);
    }


    /**
     * Integration test for replacing entities which depend on their source entity with HasMany and failing transaction. False should be returned when
     * unlinking fails while replacing even when cascadeCallbacks is enabled
     *
     * @return void
     */
    public function testReplaceHasManyOnErrorDependentCascadeCallbacks()
    {
        $articles = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['delete'])
            ->setConstructorArgs([[
                'connection' => $this->connection,
                'alias' => 'Articles',
                'table' => 'articles',
            ]])
            ->getMock();

        $articles->method('delete')->willReturn(false);

        $associations = new AssociationCollection();

        $hasManyArticles = $this->getMockBuilder('Cake\ORM\Association\HasMany')
            ->setMethods(['target'])
            ->setConstructorArgs([
                'articles',
                [
                    'target' => $articles,
                    'foreignKey' => 'author_id',
                    'dependent' => true,
                    'cascadeCallbacks' => true
                ]
            ])
            ->getMock();
        $hasManyArticles->method('target')->willReturn($articles);

        $associations->add('articles', $hasManyArticles);

        $authors = new Table([
            'connection' => $this->connection,
            'alias' => 'Authors',
            'table' => 'authors',
            'associations' => $associations
        ]);
        $authors->Articles->source($authors);

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes'
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers'
                ]
            ]
        );

        $sizeArticles = count($newArticles);

        $this->assertTrue($authors->Articles->link($author, $newArticles));
        $this->assertEquals($authors->Articles->findAllByAuthorId($author->id)->count(), $sizeArticles);
        $this->assertEquals(count($author->articles), $sizeArticles);

        $newArticles = array_merge(
            $author->articles,
            $articles->newEntities(
                [
                    [
                        'title' => 'Cheese cake recipe',
                        'body' => 'The secrets of mixing salt and sugar'
                    ],
                    [
                        'title' => 'Not another piece of cake',
                        'body' => 'This is the best'
                    ]
                ]
            )
        );
        unset($newArticles[0]);

        $this->assertFalse($authors->Articles->replace($author, $newArticles));
        $this->assertCount($sizeArticles, $authors->Articles->findAllByAuthorId($author->id));
    }


    /**
     * Integration test for replacing entities with HasMany and an empty target list. The transaction must be successfull
     *
     * @return void
     */
    public function testReplaceHasManyEmptyList()
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
                    'body' => 'They sell tastefull cakes'
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers'
                ]
            ]
        );

        $sizeArticles = count($newArticles);

        $this->assertTrue($authors->Articles->link($author, $newArticles));
        $this->assertEquals($authors->Articles->findAllByAuthorId($author->id)->count(), $sizeArticles);
        $this->assertEquals(count($author->articles), $sizeArticles);

        $newArticles = [];

        $this->assertTrue($authors->Articles->replace($author, $newArticles));
        $this->assertCount(0, $authors->Articles->findAllByAuthorId($author->id));
    }

    /**
     * Integration test for replacing entities with HasMany and no already persisted entities. The transaction must be successfull.
     * Replace operation should prevent considering 0 changed records an error when they are not found in the table
     *
     * @return void
     */
    public function testReplaceHasManyNoPersistedEntities()
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
                    'body' => 'They sell tastefull cakes'
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers'
                ]
            ]
        );

        $authors->Articles->deleteAll(['1=1']);

        $sizeArticles = count($newArticles);

        $this->assertTrue($authors->Articles->link($author, $newArticles));
        $this->assertEquals($authors->Articles->findAllByAuthorId($author->id)->count(), $sizeArticles);
        $this->assertEquals(count($author->articles), $sizeArticles);
        $this->assertTrue($authors->Articles->replace($author, $newArticles));
        $this->assertCount($sizeArticles, $authors->Articles->findAllByAuthorId($author->id));
    }

    /**
     * Integration test for replacing entities with HasMany.
     *
     * @return void
     */
    public function testReplaceHasMany()
    {
        $authors = TableRegistry::get('Authors');
        $articles = TableRegistry::get('Articles');

        $authors->hasMany('Articles', [
            'foreignKey' => 'author_id'
        ]);

        $author = $authors->newEntity(['name' => 'mylux']);
        $author = $authors->save($author);

        $newArticles = $articles->newEntities(
            [
                [
                    'title' => 'New bakery next corner',
                    'body' => 'They sell tastefull cakes'
                ],
                [
                    'title' => 'Spicy cake recipe',
                    'body' => 'chocolate and peppers'
                ]
            ]
        );

        $sizeArticles = count($newArticles);

        $this->assertTrue($authors->Articles->link($author, $newArticles));

        $this->assertEquals($authors->Articles->findAllByAuthorId($author->id)->count(), $sizeArticles);
        $this->assertEquals(count($author->articles), $sizeArticles);

        $newArticles = array_merge(
            $author->articles,
            $articles->newEntities(
                [
                    [
                        'title' => 'Cheese cake recipe',
                        'body' => 'The secrets of mixing salt and sugar'
                    ],
                    [
                        'title' => 'Not another piece of cake',
                        'body' => 'This is the best'
                    ]
                ]
            )
        );
        unset($newArticles[0]);

        $this->assertTrue($authors->Articles->replace($author, $newArticles));
        $this->assertEquals(count($newArticles), count($author->articles));
        $this->assertEquals((new Collection($newArticles))->extract('title'), (new Collection($author->articles))->extract('title'));
    }


    /**
     * Integration test to show how to unlink a single record from a belongsToMany
     *
     * @return void
     */
    public function testUnlinkBelongsToMany()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');
        $tagsTable = TableRegistry::get('tags');
        $options = ['markNew' => false];

        $article = $table->find('all')
            ->where(['id' => 1])
            ->contain(['tags'])->first();

        $table->association('tags')->unlink($article, [$article->tags[0]]);
        $this->assertCount(1, $article->tags);
        $this->assertEquals(2, $article->tags[0]->get('id'));
        $this->assertFalse($article->dirty('tags'));
    }

    /**
     * Integration test to show how to unlink multiple records from a belongsToMany
     *
     * @return void
     */
    public function testUnlinkBelongsToManyMultiple()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');
        $tagsTable = TableRegistry::get('tags');
        $options = ['markNew' => false];

        $article = new \Cake\ORM\Entity(['id' => 1], $options);
        $tags[] = new \TestApp\Model\Entity\Tag(['id' => 1], $options);
        $tags[] = new \TestApp\Model\Entity\Tag(['id' => 2], $options);

        $table->association('tags')->unlink($article, $tags);
        $left = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
        $this->assertEmpty($left->tags);
    }

    /**
     * Integration test to show how to unlink multiple records from a belongsToMany
     * providing some of the joint
     *
     * @return void
     */
    public function testUnlinkBelongsToManyPassingJoint()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');
        $tagsTable = TableRegistry::get('tags');
        $options = ['markNew' => false];

        $article = new \Cake\ORM\Entity(['id' => 1], $options);
        $tags[] = new \TestApp\Model\Entity\Tag(['id' => 1], $options);
        $tags[] = new \TestApp\Model\Entity\Tag(['id' => 2], $options);

        $tags[1]->_joinData = new \Cake\ORM\Entity([
            'article_id' => 1,
            'tag_id' => 2
        ], $options);

        $table->association('tags')->unlink($article, $tags);
        $left = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
        $this->assertEmpty($left->tags);
    }

    /**
     * Integration test to show how to replace records from a belongsToMany
     *
     * @return void
     */
    public function testReplacelinksBelongsToMany()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');
        $tagsTable = TableRegistry::get('tags');
        $options = ['markNew' => false];

        $article = new \Cake\ORM\Entity(['id' => 1], $options);
        $tags[] = new \TestApp\Model\Entity\Tag(['id' => 2], $options);
        $tags[] = new \TestApp\Model\Entity\Tag(['id' => 3], $options);
        $tags[] = new \TestApp\Model\Entity\Tag(['name' => 'foo']);

        $table->association('tags')->replaceLinks($article, $tags);
        $this->assertEquals(2, $article->tags[0]->id);
        $this->assertEquals(3, $article->tags[1]->id);
        $this->assertEquals(4, $article->tags[2]->id);

        $article = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
        $this->assertCount(3, $article->tags);
        $this->assertEquals(2, $article->tags[0]->id);
        $this->assertEquals(3, $article->tags[1]->id);
        $this->assertEquals(4, $article->tags[2]->id);
        $this->assertEquals('foo', $article->tags[2]->name);
    }

    /**
     * Integration test to show how remove all links from a belongsToMany
     *
     * @return void
     */
    public function testReplacelinksBelongsToManyWithEmpty()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');
        $tagsTable = TableRegistry::get('tags');
        $options = ['markNew' => false];

        $article = new \Cake\ORM\Entity(['id' => 1], $options);
        $tags = [];

        $table->association('tags')->replaceLinks($article, $tags);
        $this->assertSame($tags, $article->tags);
        $article = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
        $this->assertEmpty($article->tags);
    }

    /**
     * Integration test to show how to replace records from a belongsToMany
     * passing the joint property along in the target entity
     *
     * @return void
     */
    public function testReplacelinksBelongsToManyWithJoint()
    {
        $table = TableRegistry::get('articles');
        $table->belongsToMany('tags');
        $tagsTable = TableRegistry::get('tags');
        $options = ['markNew' => false];

        $article = new \Cake\ORM\Entity(['id' => 1], $options);
        $tags[] = new \TestApp\Model\Entity\Tag([
            'id' => 2,
            '_joinData' => new \Cake\ORM\Entity([
                'article_id' => 1,
                'tag_id' => 2,
            ])
        ], $options);
        $tags[] = new \TestApp\Model\Entity\Tag(['id' => 3], $options);

        $table->association('tags')->replaceLinks($article, $tags);
        $this->assertSame($tags, $article->tags);
        $article = $table->find('all')->where(['id' => 1])->contain(['tags'])->first();
        $this->assertCount(2, $article->tags);
        $this->assertEquals(2, $article->tags[0]->id);
        $this->assertEquals(3, $article->tags[1]->id);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     *
     * @return void
     */
    public function testOptionsBeingPassedToImplicitBelongsToManyDeletesUsingSaveReplace()
    {
        $articles = TableRegistry::get('Articles');

        $tags = $articles->belongsToMany('Tags');
        $tags->saveStrategy(BelongsToMany::SAVE_REPLACE);
        $tags->dependent(true);
        $tags->cascadeCallbacks(true);

        $actualOptions = null;
        $tags->junction()->eventManager()->on(
            'Model.beforeDelete',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualOptions) {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $article = $articles->get(1);
        $article->tags = [];
        $article->dirty('tags', true);

        $result = $articles->save($article, ['foo' => 'bar']);
        $this->assertNotEmpty($result);

        $expected = [
            '_primary' => false,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'checkExisting' => true
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     *
     * @return void
     */
    public function testOptionsBeingPassedToInternalSaveCallsUsingBelongsToManyLink()
    {
        $articles = TableRegistry::get('Articles');
        $tags = $articles->belongsToMany('Tags');

        $actualOptions = null;
        $tags->junction()->eventManager()->on(
            'Model.beforeSave',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualOptions) {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $article = $articles->get(1);

        $result = $tags->link($article, [$tags->target()->get(2)], ['foo' => 'bar']);
        $this->assertTrue($result);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'checkExisting' => true,
            'associated' => [
                'articles' => [],
                'tags' => []
            ]
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     *
     * @return void
     */
    public function testOptionsBeingPassedToInternalSaveCallsUsingBelongsToManyUnlink()
    {
        $articles = TableRegistry::get('Articles');
        $tags = $articles->belongsToMany('Tags');

        $actualOptions = null;
        $tags->junction()->eventManager()->on(
            'Model.beforeDelete',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualOptions) {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $article = $articles->get(1);

        $tags->unlink($article, [$tags->target()->get(2)], ['foo' => 'bar']);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'cleanProperty' => true
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     *
     * @return void
     */
    public function testOptionsBeingPassedToInternalSaveAndDeleteCallsUsingBelongsToManyReplaceLinks()
    {
        $articles = TableRegistry::get('Articles');
        $tags = $articles->belongsToMany('Tags');

        $actualSaveOptions = null;
        $actualDeleteOptions = null;
        $tags->junction()->eventManager()->on(
            'Model.beforeSave',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualSaveOptions) {
                $actualSaveOptions = $options->getArrayCopy();
            }
        );
        $tags->junction()->eventManager()->on(
            'Model.beforeDelete',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualDeleteOptions) {
                $actualDeleteOptions = $options->getArrayCopy();
            }
        );

        $article = $articles->get(1);

        $result = $tags->replaceLinks(
            $article,
            [
                $tags->target()->newEntity(['name' => 'new']),
                $tags->target()->get(2)
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
            'associated' => []
        ];
        $this->assertEquals($expected, $actualSaveOptions);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true
        ];
        $this->assertEquals($expected, $actualDeleteOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     *
     * @return void
     */
    public function testOptionsBeingPassedToImplicitHasManyDeletesUsingSaveReplace()
    {
        $authors = TableRegistry::get('Authors');

        $articles = $authors->hasMany('Articles');
        $articles->saveStrategy(HasMany::SAVE_REPLACE);
        $articles->dependent(true);
        $articles->cascadeCallbacks(true);

        $actualOptions = null;
        $articles->target()->eventManager()->on(
            'Model.beforeDelete',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualOptions) {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $author = $authors->get(1);
        $author->articles = [];
        $author->dirty('articles', true);

        $result = $authors->save($author, ['foo' => 'bar']);
        $this->assertNotEmpty($result);

        $expected = [
            '_primary' => false,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'checkExisting' => true,
            '_sourceTable' => $authors
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     *
     * @return void
     */
    public function testOptionsBeingPassedToInternalSaveCallsUsingHasManyLink()
    {
        $authors = TableRegistry::get('Authors');
        $articles = $authors->hasMany('Articles');

        $actualOptions = null;
        $articles->target()->eventManager()->on(
            'Model.beforeSave',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualOptions) {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $author = $authors->get(1);
        $author->articles = [];
        $author->dirty('articles', true);

        $result = $articles->link($author, [$articles->target()->get(2)], ['foo' => 'bar']);
        $this->assertTrue($result);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'checkExisting' => true,
            '_sourceTable' => $authors,
            'associated' => [
                'authors' => [],
                'tags' => [],
                'articlestags' => []
            ]
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     *
     * @return void
     */
    public function testOptionsBeingPassedToInternalSaveCallsUsingHasManyUnlink()
    {
        $authors = TableRegistry::get('Authors');
        $articles = $authors->hasMany('Articles');
        $articles->dependent(true);
        $articles->cascadeCallbacks(true);

        $actualOptions = null;
        $articles->target()->eventManager()->on(
            'Model.beforeDelete',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualOptions) {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $author = $authors->get(1);
        $author->articles = [];
        $author->dirty('articles', true);

        $articles->unlink($author, [$articles->target()->get(1)], ['foo' => 'bar']);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            'cleanProperty' => true
        ];
        $this->assertEquals($expected, $actualOptions);
    }

    /**
     * Tests that options are being passed through to the internal table method calls.
     *
     * @return void
     */
    public function testOptionsBeingPassedToInternalSaveAndDeleteCallsUsingHasManyReplace()
    {
        $authors = TableRegistry::get('Authors');
        $articles = $authors->hasMany('Articles');
        $articles->dependent(true);
        $articles->cascadeCallbacks(true);

        $actualSaveOptions = null;
        $actualDeleteOptions = null;
        $articles->target()->eventManager()->on(
            'Model.beforeSave',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualSaveOptions) {
                $actualSaveOptions = $options->getArrayCopy();
            }
        );
        $articles->target()->eventManager()->on(
            'Model.beforeDelete',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualDeleteOptions) {
                $actualDeleteOptions = $options->getArrayCopy();
            }
        );

        $author = $authors->get(1);

        $result = $articles->replace(
            $author,
            [
                $articles->target()->newEntity(['title' => 'new', 'body' => 'new']),
                $articles->target()->get(1)
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
                'authors' => [],
                'tags' => [],
                'articlestags' => []
            ]
        ];
        $this->assertEquals($expected, $actualSaveOptions);

        $expected = [
            '_primary' => true,
            'foo' => 'bar',
            'atomic' => true,
            'checkRules' => true,
            '_sourceTable' => $authors
        ];
        $this->assertEquals($expected, $actualDeleteOptions);
    }

    /**
     * Tests backwards compatibility of the the `$options` argument, formerly `$cleanProperty`.
     *
     * @return void
     */
    public function testBackwardsCompatibilityForBelongsToManyUnlinkCleanPropertyOption()
    {
        $articles = TableRegistry::get('Articles');
        $tags = $articles->belongsToMany('Tags');

        $actualOptions = null;
        $tags->junction()->eventManager()->on(
            'Model.beforeDelete',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualOptions) {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $article = $articles->get(1);

        $tags->unlink($article, [$tags->target()->get(1)], false);
        $this->assertArrayHasKey('cleanProperty', $actualOptions);
        $this->assertFalse($actualOptions['cleanProperty']);

        $actualOptions = null;
        $tags->unlink($article, [$tags->target()->get(2)]);
        $this->assertArrayHasKey('cleanProperty', $actualOptions);
        $this->assertTrue($actualOptions['cleanProperty']);
    }

    /**
     * Tests backwards compatibility of the the `$options` argument, formerly `$cleanProperty`.
     *
     * @return void
     */
    public function testBackwardsCompatibilityForHasManyUnlinkCleanPropertyOption()
    {
        $authors = TableRegistry::get('Authors');
        $articles = $authors->hasMany('Articles');
        $articles->dependent(true);
        $articles->cascadeCallbacks(true);

        $actualOptions = null;
        $articles->target()->eventManager()->on(
            'Model.beforeDelete',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$actualOptions) {
                $actualOptions = $options->getArrayCopy();
            }
        );

        $author = $authors->get(1);
        $author->articles = [];
        $author->dirty('articles', true);

        $articles->unlink($author, [$articles->target()->get(1)], false);
        $this->assertArrayHasKey('cleanProperty', $actualOptions);
        $this->assertFalse($actualOptions['cleanProperty']);

        $actualOptions = null;
        $articles->unlink($author, [$articles->target()->get(3)]);
        $this->assertArrayHasKey('cleanProperty', $actualOptions);
        $this->assertTrue($actualOptions['cleanProperty']);
    }

    /**
     * Tests that it is possible to call find with no arguments
     *
     * @return void
     */
    public function testSimplifiedFind()
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['callFinder'])
            ->setConstructorArgs([[
                'connection' => $this->connection,
                'schema' => ['id' => ['type' => 'integer']]
            ]])
            ->getMock();

        $query = (new \Cake\ORM\Query($this->connection, $table))->select();
        $table->expects($this->once())->method('callFinder')
            ->with('all', $query, []);
        $table->find();
    }

    public function providerForTestGet()
    {
        return [
            [ ['fields' => ['id']] ],
            [ ['fields' => ['id'], 'cache' => false] ]
        ];
    }

    /**
     * Test that get() will use the primary key for searching and return the first
     * entity found
     *
     * @dataProvider providerForTestGet
     * @param array $options
     * @return void
     */
    public function testGet($options)
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['callFinder', 'query'])
            ->setConstructorArgs([[
                'connection' => $this->connection,
                'schema' => [
                    'id' => ['type' => 'integer'],
                    'bar' => ['type' => 'integer'],
                    '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['bar']]]
                ]
            ]])
            ->getMock();

        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['addDefaultTypes', 'firstOrFail', 'where', 'cache'])
            ->setConstructorArgs([$this->connection, $table])
            ->getMock();

        $entity = new \Cake\ORM\Entity;
        $table->expects($this->once())->method('query')
            ->will($this->returnValue($query));
        $table->expects($this->once())->method('callFinder')
            ->with('all', $query, ['fields' => ['id']])
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('where')
            ->with([$table->alias() . '.bar' => 10])
            ->will($this->returnSelf());
        $query->expects($this->never())->method('cache');
        $query->expects($this->once())->method('firstOrFail')
            ->will($this->returnValue($entity));
        $result = $table->get(10, $options);
        $this->assertSame($entity, $result);
    }

    public function providerForTestGetWithCustomFinder()
    {
        return [
            [ ['fields' => ['id'], 'finder' => 'custom'] ]
        ];
    }

    /**
     * Test that get() will call a custom finder.
     *
     * @dataProvider providerForTestGetWithCustomFinder
     * @param array $options
     * @return void
     */
    public function testGetWithCustomFinder($options)
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['callFinder', 'query'])
            ->setConstructorArgs([[
                'connection' => $this->connection,
                'schema' => [
                    'id' => ['type' => 'integer'],
                    'bar' => ['type' => 'integer'],
                    '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['bar']]]
                ]
            ]])
            ->getMock();

        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['addDefaultTypes', 'firstOrFail', 'where', 'cache'])
            ->setConstructorArgs([$this->connection, $table])
            ->getMock();

        $entity = new \Cake\ORM\Entity;
        $table->expects($this->once())->method('query')
            ->will($this->returnValue($query));
        $table->expects($this->once())->method('callFinder')
            ->with('custom', $query, ['fields' => ['id']])
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('where')
            ->with([$table->alias() . '.bar' => 10])
            ->will($this->returnSelf());
        $query->expects($this->never())->method('cache');
        $query->expects($this->once())->method('firstOrFail')
            ->will($this->returnValue($entity));
        $result = $table->get(10, $options);
        $this->assertSame($entity, $result);
    }

    public function providerForTestGetWithCache()
    {
        return [
            [
                ['fields' => ['id'], 'cache' => 'default'],
                'get:test.table_name[10]', 'default'
            ],
            [
                ['fields' => ['id'], 'cache' => 'default', 'key' => 'custom_key'],
                'custom_key', 'default'
            ]
        ];
    }

    /**
     * Test that get() will use the cache.
     *
     * @dataProvider providerForTestGetWithCache
     * @param array $options
     * @param string $cacheKey
     * @param string $cacheConfig
     * @return void
     */
    public function testGetWithCache($options, $cacheKey, $cacheConfig)
    {
        $table = $this->getMockBuilder('\Cake\ORM\Table')
            ->setMethods(['callFinder', 'query'])
            ->setConstructorArgs([[
                'connection' => $this->connection,
                'schema' => [
                    'id' => ['type' => 'integer'],
                    'bar' => ['type' => 'integer'],
                    '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['bar']]]
                ]
            ]])
            ->getMock();
        $table->table('table_name');

        $query = $this->getMockBuilder('\Cake\ORM\Query')
            ->setMethods(['addDefaultTypes', 'firstOrFail', 'where', 'cache'])
            ->setConstructorArgs([$this->connection, $table])
            ->getMock();

        $entity = new \Cake\ORM\Entity;
        $table->expects($this->once())->method('query')
            ->will($this->returnValue($query));
        $table->expects($this->once())->method('callFinder')
            ->with('all', $query, ['fields' => ['id']])
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('where')
            ->with([$table->alias() . '.bar' => 10])
            ->will($this->returnSelf());
        $query->expects($this->once())->method('cache')
            ->with($cacheKey, $cacheConfig)
            ->will($this->returnSelf());
        $query->expects($this->once())->method('firstOrFail')
            ->will($this->returnValue($entity));
        $result = $table->get(10, $options);
        $this->assertSame($entity, $result);
    }

    /**
     * Tests that get() will throw an exception if the record was not found
     *
     * @expectedException Cake\Datasource\Exception\RecordNotFoundException
     * @expectedExceptionMessage Record not found in table "articles"
     * @return void
     */
    public function testGetNotFoundException()
    {
        $table = new Table([
            'name' => 'Articles',
            'connection' => $this->connection,
            'table' => 'articles',
        ]);
        $table->get(10);
    }

    /**
     * Test that an exception is raised when there are not enough keys.
     *
     * @expectedException Cake\Datasource\Exception\InvalidPrimaryKeyException
     * @expectedExceptionMessage Record not found in table "articles" with primary key [NULL]
     * @return void
     */
    public function testGetExceptionOnNoData()
    {
        $table = new Table([
            'name' => 'Articles',
            'connection' => $this->connection,
            'table' => 'articles',
        ]);
        $table->get(null);
    }

    /**
     * Test that an exception is raised when there are too many keys.
     *
     * @expectedException Cake\Datasource\Exception\InvalidPrimaryKeyException
     * @expectedExceptionMessage Record not found in table "articles" with primary key [1, 'two']
     * @return void
     */
    public function testGetExceptionOnTooMuchData()
    {
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
     *
     * @return void
     */
    public function testPatchEntity()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['marshaller'])
            ->getMock();
        $marshaller = $this->getMockBuilder('Cake\ORM\Marshaller')
            ->setConstructorArgs([$table])
            ->getMock();
        $table->belongsTo('users');
        $table->hasMany('articles');
        $table->expects($this->once())->method('marshaller')
            ->will($this->returnValue($marshaller));

        $entity = new \Cake\ORM\Entity;
        $data = ['foo' => 'bar'];
        $marshaller->expects($this->once())
            ->method('merge')
            ->with($entity, $data, ['associated' => ['users', 'articles']])
            ->will($this->returnValue($entity));
        $table->patchEntity($entity, $data);
    }

    /**
     * Tests that patchEntities delegates the task to the marshaller and passed
     * all associations
     *
     * @return void
     */
    public function testPatchEntities()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')
            ->setMethods(['marshaller'])
            ->getMock();
        $marshaller = $this->getMockBuilder('Cake\ORM\Marshaller')
            ->setConstructorArgs([$table])
            ->getMock();
        $table->belongsTo('users');
        $table->hasMany('articles');
        $table->expects($this->once())->method('marshaller')
            ->will($this->returnValue($marshaller));

        $entities = [new \Cake\ORM\Entity];
        $data = [['foo' => 'bar']];
        $marshaller->expects($this->once())
            ->method('mergeMany')
            ->with($entities, $data, ['associated' => ['users', 'articles']])
            ->will($this->returnValue($entities));
        $table->patchEntities($entities, $data);
    }

    /**
     * Tests __debugInfo
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $articles = TableRegistry::get('articles');
        $articles->addBehavior('Timestamp');
        $result = $articles->__debugInfo();
        $expected = [
            'registryAlias' => 'articles',
            'table' => 'articles',
            'alias' => 'articles',
            'entityClass' => 'TestApp\Model\Entity\Article',
            'associations' => ['authors', 'tags', 'articlestags'],
            'behaviors' => ['Timestamp'],
            'defaultConnection' => 'default',
            'connectionName' => 'test'
        ];
        $this->assertEquals($expected, $result);

        $articles = TableRegistry::get('Foo.Articles');
        $result = $articles->__debugInfo();
        $expected = [
            'registryAlias' => 'Foo.Articles',
            'table' => 'articles',
            'alias' => 'Articles',
            'entityClass' => '\Cake\ORM\Entity',
            'associations' => [],
            'behaviors' => [],
            'defaultConnection' => 'default',
            'connectionName' => 'test'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that findOrCreate creates a new entity, and then finds that entity.
     *
     * @return void
     */
    public function testFindOrCreateNewEntity()
    {
        $articles = TableRegistry::get('Articles');

        $callbackExecuted = false;
        $firstArticle = $articles->findOrCreate(['title' => 'Not there'], function ($article) use (&$callbackExecuted) {
            $this->assertTrue($article instanceof EntityInterface);
            $article->body = 'New body';
            $callbackExecuted = true;
        });
        $this->assertTrue($callbackExecuted);
        $this->assertFalse($firstArticle->isNew());
        $this->assertNotNull($firstArticle->id);
        $this->assertEquals('Not there', $firstArticle->title);
        $this->assertEquals('New body', $firstArticle->body);

        $secondArticle = $articles->findOrCreate(['title' => 'Not there'], function ($article) {
            $this->fail('Should not be called for existing entities.');
        });
        $this->assertFalse($secondArticle->isNew());
        $this->assertNotNull($secondArticle->id);
        $this->assertEquals('Not there', $secondArticle->title);
        $this->assertEquals($firstArticle->id, $secondArticle->id);
    }

    /**
     * Test that findOrCreate finds fixture data.
     *
     * @return void
     */
    public function testFindOrCreateExistingEntity()
    {
        $articles = TableRegistry::get('Articles');

        $article = $articles->findOrCreate(['title' => 'First Article'], function ($article) {
            $this->fail('Should not be called for existing entities.');
        });
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertEquals('First Article', $article->title);
    }

    /**
     * Test that findOrCreate uses the search conditions as defaults for new entity.
     *
     * @return void
     */
    public function testFindOrCreateDefaults()
    {
        $articles = TableRegistry::get('Articles');

        $callbackExecuted = false;
        $article = $articles->findOrCreate(
            ['author_id' => 2, 'title' => 'First Article'],
            function ($article) use (&$callbackExecuted) {
                $this->assertInstanceOf('Cake\Datasource\EntityInterface', $article);
                $article->set(['published' => 'N', 'body' => 'New body']);
                $callbackExecuted = true;
            }
        );
        $this->assertTrue($callbackExecuted);
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertEquals('First Article', $article->title);
        $this->assertEquals('New body', $article->body);
        $this->assertEquals('N', $article->published);
        $this->assertEquals(2, $article->author_id);

        $query = $articles->find()->where(['author_id' => 2, 'title' => 'First Article']);
        $article = $articles->findOrCreate($query);
        $this->assertEquals('First Article', $article->title);
        $this->assertEquals(2, $article->author_id);
        $this->assertFalse($article->isNew());
    }

    /**
     * Test that findOrCreate adds new entity without using a callback.
     *
     * @return void
     */
    public function testFindOrCreateNoCallable()
    {
        $articles = TableRegistry::get('Articles');

        $article = $articles->findOrCreate(['title' => 'Just Something New']);
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertEquals('Just Something New', $article->title);
    }

    /**
     * Test that findOrCreate executes search conditions as a callable.
     *
     * @return void
     */
    public function testFindOrCreateSearchCallable()
    {
        $articles = TableRegistry::get('Articles');

        $calledOne = false;
        $calledTwo = false;
        $article = $articles->findOrCreate(function ($query) use (&$calledOne) {
            $this->assertInstanceOf('Cake\ORM\Query', $query);
            $query->where(['title' => 'Something Else']);
            $calledOne = true;
        }, function ($article) use (&$calledTwo) {
            $this->assertInstanceOf('Cake\Datasource\EntityInterface', $article);
            $article->title = 'Set Defaults Here';
            $calledTwo = true;
        });
        $this->assertTrue($calledOne);
        $this->assertTrue($calledTwo);
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertEquals('Set Defaults Here', $article->title);
    }

    /**
     * Test that findOrCreate options disable defaults.
     *
     * @return void
     */
    public function testFindOrCreateNoDefaults()
    {
        $articles = TableRegistry::get('Articles');

        $article = $articles->findOrCreate(['title' => 'A New Article', 'published' => 'Y'], function ($article) {
            $this->assertInstanceOf('Cake\Datasource\EntityInterface', $article);
            $article->title = 'A Different Title';
        }, ['defaults' => false]);
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertEquals('A Different Title', $article->title);
        $this->assertNull($article->published, 'Expected Null since defaults are disabled.');
    }

    /**
     * Test that findOrCreate executes callable inside transaction.
     *
     * @return void
     */
    public function testFindOrCreateTransactions()
    {
        $articles = TableRegistry::get('Articles');

        $article = $articles->findOrCreate(function ($query) {
            $this->assertInstanceOf('Cake\ORM\Query', $query);
            $query->where(['title' => 'Find Something New']);
            $this->assertTrue($this->connection->inTransaction());
        }, function ($article) {
            $this->assertInstanceOf('Cake\Datasource\EntityInterface', $article);
            $this->assertTrue($this->connection->inTransaction());
            $article->title = 'Success';
        });
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertEquals('Success', $article->title);
    }

    /**
     * Test that findOrCreate executes callable without transaction.
     *
     * @return void
     */
    public function testFindOrCreateNoTransaction()
    {
        $articles = TableRegistry::get('Articles');

        $article = $articles->findOrCreate(function ($query) {
            $this->assertInstanceOf('Cake\ORM\Query', $query);
            $query->where(['title' => 'Find Something New']);
            $this->assertFalse($this->connection->inTransaction());
        }, function ($article) {
            $this->assertInstanceOf('Cake\Datasource\EntityInterface', $article);
            $this->assertFalse($this->connection->inTransaction());
            $article->title = 'Success';
        }, ['atomic' => false]);
        $this->assertFalse($article->isNew());
        $this->assertNotNull($article->id);
        $this->assertEquals('Success', $article->title);
    }

    /**
     * Test that creating a table fires the initialize event.
     *
     * @return void
     */
    public function testInitializeEvent()
    {
        $count = 0;
        $cb = function ($event) use (&$count) {
            $count++;
        };
        EventManager::instance()->on('Model.initialize', $cb);
        $articles = TableRegistry::get('Articles');

        $this->assertEquals(1, $count, 'Callback should be called');
        EventManager::instance()->detach($cb, 'Model.initialize');
    }

    /**
     * Tests the hasFinder method
     *
     * @return void
     */
    public function testHasFinder()
    {
        $table = TableRegistry::get('articles');
        $table->addBehavior('Sluggable');

        $this->assertTrue($table->hasFinder('list'));
        $this->assertTrue($table->hasFinder('noSlug'));
        $this->assertFalse($table->hasFinder('noFind'));
    }

    /**
     * Tests that calling validator() trigger the buildValidator event
     *
     * @return void
     */
    public function testBuildValidatorEvent()
    {
        $count = 0;
        $cb = function ($event) use (&$count) {
            $count++;
        };
        EventManager::instance()->on('Model.buildValidator', $cb);
        $articles = TableRegistry::get('Articles');
        $articles->validator();
        $this->assertEquals(1, $count, 'Callback should be called');

        $articles->validator();
        $this->assertEquals(1, $count, 'Callback should be called only once');
    }

    /**
     * Tests the validateUnique method with different combinations
     *
     * @return void
     */
    public function testValidateUnique()
    {
        $table = TableRegistry::get('Users');
        $validator = new Validator;
        $validator->add('username', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);
        $validator->provider('table', $table);

        $data = ['username' => ['larry', 'notthere']];
        $this->assertNotEmpty($validator->errors($data));

        $data = ['username' => 'larry'];
        $this->assertNotEmpty($validator->errors($data));

        $data = ['username' => 'jose'];
        $this->assertEmpty($validator->errors($data));

        $data = ['username' => 'larry', 'id' => 3];
        $this->assertEmpty($validator->errors($data, false));

        $data = ['username' => 'larry', 'id' => 3];
        $this->assertNotEmpty($validator->errors($data));

        $data = ['username' => 'larry'];
        $this->assertNotEmpty($validator->errors($data, false));

        $validator->add('username', 'unique', [
            'rule' => 'validateUnique', 'provider' => 'table'
        ]);
        $data = ['username' => 'larry'];
        $this->assertNotEmpty($validator->errors($data, false));
    }

    /**
     * Tests the validateUnique method with scope
     *
     * @return void
     */
    public function testValidateUniqueScope()
    {
        $table = TableRegistry::get('Users');
        $validator = new Validator;
        $validator->add('username', 'unique', [
            'rule' => ['validateUnique', ['derp' => 'erp', 'scope' => 'id']],
            'provider' => 'table'
        ]);
        $validator->provider('table', $table);
        $data = ['username' => 'larry', 'id' => 3];
        $this->assertNotEmpty($validator->errors($data));

        $data = ['username' => 'larry', 'id' => 1];
        $this->assertEmpty($validator->errors($data));

        $data = ['username' => 'jose'];
        $this->assertEmpty($validator->errors($data));
    }

    /**
     * Tests that the callbacks receive the expected types of arguments.
     *
     * @return void
     */
    public function testCallbackArgumentTypes()
    {
        $table = TableRegistry::get('articles');
        $table->belongsTo('authors');

        $eventManager = $table->eventManager();

        $associationBeforeFindCount = 0;
        $table->association('authors')->target()->eventManager()->on(
            'Model.beforeFind',
            function (Event $event, Query $query, ArrayObject $options, $primary) use (&$associationBeforeFindCount) {
                $this->assertTrue(is_bool($primary));
                $associationBeforeFindCount ++;
            }
        );

        $beforeFindCount = 0;
        $eventManager->on(
            'Model.beforeFind',
            function (Event $event, Query $query, ArrayObject $options, $primary) use (&$beforeFindCount) {
                $this->assertTrue(is_bool($primary));
                $beforeFindCount ++;
            }
        );
        $table->find()->contain('authors')->first();
        $this->assertEquals(1, $associationBeforeFindCount);
        $this->assertEquals(1, $beforeFindCount);

        $buildValidatorCount = 0;
        $eventManager->on(
            'Model.buildValidator',
            $callback = function (Event $event, Validator $validator, $name) use (&$buildValidatorCount) {
                $this->assertTrue(is_string($name));
                $buildValidatorCount ++;
            }
        );
        $table->validator();
        $this->assertEquals(1, $buildValidatorCount);

        $buildRulesCount =
        $beforeRulesCount =
        $afterRulesCount =
        $beforeSaveCount =
        $afterSaveCount = 0;
        $eventManager->on(
            'Model.buildRules',
            function (Event $event, RulesChecker $rules) use (&$buildRulesCount) {
                $buildRulesCount ++;
            }
        );
        $eventManager->on(
            'Model.beforeRules',
            function (Event $event, Entity $entity, ArrayObject $options, $operation) use (&$beforeRulesCount) {
                $this->assertTrue(is_string($operation));
                $beforeRulesCount ++;
            }
        );
        $eventManager->on(
            'Model.afterRules',
            function (Event $event, Entity $entity, ArrayObject $options, $result, $operation) use (&$afterRulesCount) {
                $this->assertTrue(is_bool($result));
                $this->assertTrue(is_string($operation));
                $afterRulesCount ++;
            }
        );
        $eventManager->on(
            'Model.beforeSave',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$beforeSaveCount) {
                $beforeSaveCount ++;
            }
        );
        $eventManager->on(
            'Model.afterSave',
            $afterSaveCallback = function (Event $event, Entity $entity, ArrayObject $options) use (&$afterSaveCount) {
                $afterSaveCount ++;
            }
        );
        $entity = new Entity(['title' => 'Title']);
        $this->assertNotFalse($table->save($entity));
        $this->assertEquals(1, $buildRulesCount);
        $this->assertEquals(1, $beforeRulesCount);
        $this->assertEquals(1, $afterRulesCount);
        $this->assertEquals(1, $beforeSaveCount);
        $this->assertEquals(1, $afterSaveCount);

        $beforeDeleteCount =
        $afterDeleteCount = 0;
        $eventManager->on(
            'Model.beforeDelete',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$beforeDeleteCount) {
                $beforeDeleteCount ++;
            }
        );
        $eventManager->on(
            'Model.afterDelete',
            function (Event $event, Entity $entity, ArrayObject $options) use (&$afterDeleteCount) {
                $afterDeleteCount ++;
            }
        );
        $this->assertTrue($table->delete($entity, ['checkRules' => false]));
        $this->assertEquals(1, $beforeDeleteCount);
        $this->assertEquals(1, $afterDeleteCount);
    }

    /**
     * Tests that calling newEntity() on a table sets the right source alias
     *
     * @return void
     */
    public function testEntitySource()
    {
        $table = TableRegistry::get('Articles');
        $this->assertEquals('Articles', $table->newEntity()->source());

        Plugin::load('TestPlugin');
        $table = TableRegistry::get('TestPlugin.Comments');
        $this->assertEquals('TestPlugin.Comments', $table->newEntity()->source());
    }

    /**
     * Tests that passing a coned entity that was marked as new to save() will
     * actaully save it as a new entity
     *
     * @group save
     * @return void
     */
    public function testSaveWithClonedEntity()
    {
        $table = TableRegistry::get('Articles');
        $article = $table->get(1);

        $cloned = clone $article;
        $cloned->unsetProperty('id');
        $cloned->isNew(true);
        $this->assertSame($cloned, $table->save($cloned));
        $this->assertEquals(
            $article->extract(['title', 'author_id']),
            $cloned->extract(['title', 'author_id'])
        );
        $this->assertEquals(4, $cloned->id);
    }

    /**
     * Tests that the _ids notation can be used for HasMany
     *
     * @return void
     */
    public function testSaveHasManyWithIds()
    {
        $data = [
            'username' => 'lux',
            'password' => 'passphrase',
            'comments' => [
                '_ids' => [1, 2]
            ]
        ];

        $userTable = TableRegistry::get('Users');
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
     *
     * @return void
     */
    public function testSaveHasManyNoWasteSave()
    {
        $data = [
            'username' => 'lux',
            'password' => 'passphrase',
            'comments' => [
                '_ids' => [1, 2]
            ]
        ];

        $userTable = TableRegistry::get('Users');
        $userTable->hasMany('Comments');
        $savedUser = $userTable->save($userTable->newEntity($data, ['associated' => ['Comments']]));

        $counter = 0;
        $userTable->Comments
            ->eventManager()
            ->on('Model.afterSave', function ($event, $entity) use (&$counter) {
                if ($entity->dirty()) {
                    $counter++;
                }
            });

        $savedUser->comments[] = $userTable->Comments->get(5);
        $this->assertCount(3, $savedUser->comments);
        $savedUser->dirty('comments', true);
        $userTable->save($savedUser);
        $this->assertEquals(1, $counter);
    }

    /**
     * Tests that on second save, entities for the belongsToMany relation are not marked
     * as dirty unnecessarily. This helps avoid wasteful database statements and makes
     * for a cleaner transaction log
     *
     * @return void
     */
    public function testSaveBelongsToManyNoWasteSave()
    {
        $data = [
            'title' => 'foo',
            'body' => 'bar',
            'tags' => [
                '_ids' => [1, 2]
            ]
        ];

        $table = TableRegistry::get('Articles');
        $table->belongsToMany('Tags');
        $article = $table->save($table->newEntity($data, ['associated' => ['Tags']]));

        $counter = 0;
        $table->Tags->junction()
            ->eventManager()
            ->on('Model.afterSave', function ($event, $entity) use (&$counter) {
                if ($entity->dirty()) {
                    $counter++;
                }
            });

        $article->tags[] = $table->Tags->get(3);
        $this->assertCount(3, $article->tags);
        $article->dirty('tags', true);
        $table->save($article);
        $this->assertEquals(1, $counter);
    }

    /**
     * Tests that after saving then entity contains the right primary
     * key casted to the right type
     *
     * @group save
     * @return void
     */
    public function testSaveCorrectPrimaryKeyType()
    {
        $entity = new Entity([
            'username' => 'superuser',
            'created' => new Time('2013-10-10 00:00'),
            'updated' => new Time('2013-10-10 00:00')
        ], ['markNew' => true]);

        $table = TableRegistry::get('Users');
        $this->assertSame($entity, $table->save($entity));
        $this->assertSame(self::$nextUserId, $entity->id);
    }

    /**
     * Tests entity clean()
     *
     * @return void
     */
    public function testEntityClean()
    {
        $table = TableRegistry::get('Articles');
        $validator = $table->validator()->requirePresence('body');
        $entity = $table->newEntity(['title' => 'mark']);

        $entity->dirty('title', true);
        $entity->invalid('title', 'albert');

        $this->assertNotEmpty($entity->errors());
        $this->assertTrue($entity->dirty());
        $this->assertEquals(['title' => 'albert'], $entity->invalid());

        $entity->title = 'alex';
        $this->assertSame($entity->getOriginal('title'), 'mark');

        $entity->clean();

        $this->assertEmpty($entity->errors());
        $this->assertFalse($entity->dirty());
        $this->assertEquals([], $entity->invalid());
        $this->assertSame($entity->getOriginal('title'), 'alex');
    }

    /**
     * Tests the loadInto() method
     *
     * @return void
     */
    public function testLoadIntoEntity()
    {
        $table = TableRegistry::get('Authors');
        $table->hasMany('SiteArticles');
        $articles = $table->hasMany('Articles');
        $articles->belongsToMany('Tags');

        $entity = $table->get(1);
        $result = $table->loadInto($entity, ['SiteArticles', 'Articles.Tags']);
        $this->assertSame($entity, $result);

        $expected = $table->get(1, ['contain' => ['SiteArticles', 'Articles.Tags']]);
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that it is possible to pass conditions and fields to loadInto()
     *
     * @return void
     */
    public function testLoadIntoWithConditions()
    {
        $table = TableRegistry::get('Authors');
        $table->hasMany('SiteArticles');
        $articles = $table->hasMany('Articles');
        $articles->belongsToMany('Tags');

        $entity = $table->get(1);
        $options = [
            'SiteArticles' => ['fields' => ['title', 'author_id']],
            'Articles.Tags' => function ($q) {
                return $q->where(['Tags.name' => 'tag2']);
            }
        ];
        $result = $table->loadInto($entity, $options);
        $this->assertSame($entity, $result);
        $expected = $table->get(1, ['contain' => $options]);
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests loadInto() with a belongsTo association
     *
     * @return void
     */
    public function testLoadBelognsTo()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsTo('Authors');

        $entity = $table->get(2);
        $result = $table->loadInto($entity, ['Authors']);
        $this->assertSame($entity, $result);

        $expected = $table->get(2, ['contain' => ['Authors']]);
        $this->assertEquals($expected, $entity);
    }

    /**
     * Tests that it is possible to post-load associations for many entities at
     * the same time
     *
     * @return void
     */
    public function testLoadIntoMany()
    {
        $table = TableRegistry::get('Authors');
        $table->hasMany('SiteArticles');
        $articles = $table->hasMany('Articles');
        $articles->belongsToMany('Tags');

        $entities = $table->find()->compile();
        $contain = ['SiteArticles', 'Articles.Tags'];
        $result = $table->loadInto($entities, $contain);

        foreach ($entities as $k => $v) {
            $this->assertSame($v, $result[$k]);
        }

        $expected = $table->find()->contain($contain)->toList();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getting the save options builder.
     *
     * @return void
     */
    public function getSaveOptionsBuilder()
    {
        $table = TableRegistry::get('Authors');
        $result = $table->getSaveOptionsBuilder();
        $this->assertInstanceOf('Cake\ORM\SaveOptionsBuilder', $result);
    }

    /**
     * Helper method to skip tests when connection is SQLServer.
     *
     * @return void
     */
    public function skipIfSqlServer()
    {
        $this->skipIf(
            $this->connection->driver() instanceof \Cake\Database\Driver\Sqlserver,
            'SQLServer does not support the requirements of this test.'
        );
    }
}
