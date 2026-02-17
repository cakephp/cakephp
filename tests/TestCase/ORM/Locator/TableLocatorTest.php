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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Locator;

use Cake\Core\Exception\CakeException;
use Cake\Database\Exception\DatabaseException;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Query\QueryFactory;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Mockery;
use ReflectionProperty;
use TestApp\Infrastructure\Table\AddressesTable;
use TestApp\Model\Entity\Article;
use TestApp\Model\Table\ArticlesTable;
use TestApp\Model\Table\AuthorsTable;
use TestApp\Model\Table\MyUsersTable;
use TestPlugin\Infrastructure\Table\AddressesTable as PluginAddressesTable;
use TestPlugin\Model\Entity\Comment;
use TestPlugin\Model\Table\CommentsTable;
use TestPlugin\Model\Table\TestPluginCommentsTable;
use TestPluginTwo\Model\Table\CommentsTable as PluginTwoCommentsTable;

/**
 * Test case for TableLocator
 */
class TableLocatorTest extends TestCase
{
    /**
     * TableLocator instance.
     *
     * @var \Cake\ORM\Locator\TableLocator
     */
    protected $locator;

    /**
     * setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();

        $this->locator = new TableLocator();
    }

    /**
     * tearDown
     */
    protected function tearDown(): void
    {
        $this->clearPlugins();
        parent::tearDown();
    }

    /**
     * Test getConfig() method.
     */
    public function testGetConfig(): void
    {
        $this->assertEquals([], $this->locator->getConfig('Tests'));

        $data = [
            'connection' => 'testing',
            'entityClass' => Article::class,
        ];
        $result = $this->locator->setConfig('Tests', $data);
        $this->assertSame($this->locator, $result, 'Returns locator');

        $result = $this->locator->getConfig();
        $expected = ['Tests' => $data];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getConfig() method with plugin syntax aliases
     */
    public function testConfigPlugin(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $data = [
            'connection' => 'testing',
            'entityClass' => Comment::class,
        ];

        $result = $this->locator->setConfig('TestPlugin.TestPluginComments', $data);
        $this->assertSame($this->locator, $result, 'Returns locator');
    }

    /**
     * Test calling getConfig() on existing instances throws an error.
     */
    public function testConfigOnDefinedInstance(): void
    {
        $users = $this->locator->get('Users');
        $this->assertNotEmpty($users);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('You cannot configure `Users`, it has already been constructed.');

        $this->locator->setConfig('Users', ['table' => 'my_users']);
    }

    /**
     * Test the exists() method.
     */
    public function testExists(): void
    {
        $this->assertFalse($this->locator->exists('Articles'));

        $this->locator->setConfig('Articles', ['table' => 'articles']);
        $this->assertFalse($this->locator->exists('Articles'));

        $this->locator->get('Articles', ['table' => 'articles']);
        $this->assertTrue($this->locator->exists('Articles'));
    }

    /**
     * Tests the casing and locator. Using table name directly is not
     * the same as using conventional aliases anymore.
     */
    public function testCasing(): void
    {
        $this->assertFalse($this->locator->exists('Articles'));

        $Article = $this->locator->get('Articles', ['table' => 'articles']);
        $this->assertTrue($this->locator->exists('Articles'));

        $this->assertFalse($this->locator->exists('articles'));

        $article = $this->locator->get('articles');
        $this->assertTrue($this->locator->exists('articles'));

        $this->assertNotSame($Article, $article);
    }

    /**
     * Test the exists() method with plugin-prefixed models.
     */
    public function testExistsPlugin(): void
    {
        $this->assertFalse($this->locator->exists('Comments'));
        $this->assertFalse($this->locator->exists('TestPlugin.Comments'));

        $this->locator->setConfig('TestPlugin.Comments', ['table' => 'comments']);
        $this->assertFalse($this->locator->exists('Comments'), 'The Comments key should not be populated');
        $this->assertFalse($this->locator->exists('TestPlugin.Comments'), 'The plugin.alias key should not be populated');

        $this->locator->get('TestPlugin.Comments', ['table' => 'comments']);
        $this->assertFalse($this->locator->exists('Comments'), 'The Comments key should not be populated');
        $this->assertTrue($this->locator->exists('TestPlugin.Comments'), 'The plugin.alias key should now be populated');
    }

    /**
     * Test getting instances from the registry.
     */
    public function testGet(): void
    {
        $result = $this->locator->get('Articles', [
            'table' => 'my_articles',
        ]);
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('my_articles', $result->getTable());

        $result2 = $this->locator->get('Articles');
        $this->assertSame($result, $result2);
        $this->assertSame('my_articles', $result->getTable());

        $this->assertSame($this->locator, $result->associations()->getTableLocator());

        $result = $this->locator->get(ArticlesTable::class);
        $this->assertSame('Articles', $result->getAlias());
        $this->assertSame(ArticlesTable::class, $result->getRegistryAlias());

        $result2 = $this->locator->get($result->getRegistryAlias());
        $this->assertSame($result, $result2);
    }

    /**
     * Are auto-models instantiated correctly? How about when they have an alias?
     */
    public function testGetFallbacks(): void
    {
        $result = $this->locator->get('Droids');
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('droids', $result->getTable());
        $this->assertSame('Droids', $result->getAlias());

        $result = $this->locator->get('R2D2', ['className' => 'Droids']);
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('droids', $result->getTable(), 'The table should be derived from the className');
        $this->assertSame('R2D2', $result->getAlias());

        $result = $this->locator->get('C3P0', ['className' => 'Droids', 'table' => 'rebels']);
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('rebels', $result->getTable(), 'The table should be taken from options');
        $this->assertSame('C3P0', $result->getAlias());

        $result = $this->locator->get('Funky.Chipmunks');
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('chipmunks', $result->getTable(), 'The table should be derived from the alias');
        $this->assertSame('Chipmunks', $result->getAlias());

        $result = $this->locator->get('Awesome', ['className' => 'Funky.Monkies']);
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('monkies', $result->getTable(), 'The table should be derived from the classname');
        $this->assertSame('Awesome', $result->getAlias());

        $result = $this->locator->get('Stuff', ['className' => Table::class]);
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('stuff', $result->getTable(), 'The table should be derived from the alias');
        $this->assertSame('Stuff', $result->getAlias());
    }

    public function testExceptionForAliasWhenFallbackTurnedOff(): void
    {
        $this->expectException(MissingTableClassException::class);
        $this->expectExceptionMessage('Table class for alias `Droids` could not be found.');

        $this->locator->get('Droids', ['allowFallbackClass' => false]);
    }

    public function testExceptionForFQCNWhenFallbackTurnedOff(): void
    {
        $this->expectException(MissingTableClassException::class);
        $this->expectExceptionMessage('Table class `App\Model\DroidsTable` could not be found.');

        $this->locator->get('App\Model\DroidsTable', ['allowFallbackClass' => false]);
    }

    /**
     * Test that get() uses config data set with getConfig()
     */
    public function testGetWithGetConfig(): void
    {
        $this->locator->setConfig('Articles', [
            'table' => 'my_articles',
        ]);
        $result = $this->locator->get('Articles');
        $this->assertSame('my_articles', $result->getTable(), 'Should use getConfig() data.');
    }

    /**
     * Test that get() uses config data set with getConfig()
     */
    public function testGetWithConnectionName(): void
    {
        ConnectionManager::alias('test', 'testing');
        $result = $this->locator->get('Articles', [
            'connectionName' => 'testing',
        ]);
        $this->assertSame('articles', $result->getTable());
        $this->assertSame('test', $result->getConnection()->configName());
    }

    /**
     * Test that get() uses config data `className` set with getConfig()
     */
    public function testGetWithConfigClassName(): void
    {
        $this->locator->setConfig('MyUsersTableAlias', [
            'className' => MyUsersTable::class,
        ]);
        $result = $this->locator->get('MyUsersTableAlias');
        $this->assertInstanceOf(MyUsersTable::class, $result, 'Should use getConfig() data className option.');
    }

    /**
     * Test get with config throws an exception if the alias exists already.
     */
    public function testGetExistingWithConfigData(): void
    {
        $users = $this->locator->get('Users');
        $this->assertNotEmpty($users);

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('You cannot configure `Users`, it already exists in the registry.');

        $this->locator->get('Users', ['table' => 'my_users']);
    }

    /**
     * Test get() can be called several times with the same option without
     * throwing an exception.
     */
    public function testGetWithSameOption(): void
    {
        $result = $this->locator->get('Users', ['className' => MyUsersTable::class]);
        $result2 = $this->locator->get('Users', ['className' => MyUsersTable::class]);
        $this->assertEquals($result, $result2);
    }

    /**
     * Tests that tables can be instantiated based on conventions
     * and using plugin notation
     */
    public function testGetWithConventions(): void
    {
        $table = $this->locator->get('Articles');
        $this->assertInstanceOf(ArticlesTable::class, $table);

        $table = $this->locator->get('Authors');
        $this->assertInstanceOf(AuthorsTable::class, $table);
    }

    /**
     * Test get() with plugin syntax aliases
     */
    public function testGetPlugin(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $table = $this->locator->get('TestPlugin.TestPluginComments');

        $this->assertInstanceOf(TestPluginCommentsTable::class, $table);
        $this->assertFalse(
            $this->locator->exists('TestPluginComments'),
            'Short form should NOT exist',
        );
        $this->assertTrue(
            $this->locator->exists('TestPlugin.TestPluginComments'),
            'Long form should exist',
        );

        $second = $this->locator->get('TestPlugin.TestPluginComments');
        $this->assertSame($table, $second, 'Can fetch long form');
    }

    /**
     * Test get() with same-alias models in different plugins
     *
     * There should be no internal cache-confusion
     */
    public function testGetMultiplePlugins(): void
    {
        $this->loadPlugins(['TestPlugin', 'TestPluginTwo']);

        $app = $this->locator->get('Comments');
        $plugin1 = $this->locator->get('TestPlugin.Comments');
        $plugin2 = $this->locator->get('TestPluginTwo.Comments');

        $this->assertInstanceOf(Table::class, $app, 'Should be an app table instance');
        $this->assertInstanceOf(CommentsTable::class, $plugin1, 'Should be a plugin 1 table instance');
        $this->assertInstanceOf(PluginTwoCommentsTable::class, $plugin2, 'Should be a plugin 2 table instance');

        $plugin2 = $this->locator->get('TestPluginTwo.Comments');
        $plugin1 = $this->locator->get('TestPlugin.Comments');
        $app = $this->locator->get('Comments');

        $this->assertInstanceOf(Table::class, $app, 'Should still be an app table instance');
        $this->assertInstanceOf(CommentsTable::class, $plugin1, 'Should still be a plugin 1 table instance');
        $this->assertInstanceOf(PluginTwoCommentsTable::class, $plugin2, 'Should still be a plugin 2 table instance');
    }

    /**
     * Test get() with plugin aliases + className option.
     */
    public function testGetPluginWithClassNameOption(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $table = $this->locator->get('Comments', [
            'className' => 'TestPlugin.TestPluginComments',
        ]);
        $class = TestPluginCommentsTable::class;
        $this->assertInstanceOf($class, $table);
        $this->assertFalse($this->locator->exists('TestPluginComments'), 'Class name should not exist');
        $this->assertFalse($this->locator->exists('TestPlugin.TestPluginComments'), 'Full class alias should not exist');
        $this->assertTrue($this->locator->exists('Comments'), 'Class name should exist');

        $second = $this->locator->get('Comments');
        $this->assertSame($table, $second);
    }

    /**
     * Test get() with full namespaced classname
     */
    public function testGetPluginWithFullNamespaceName(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $class = TestPluginCommentsTable::class;
        $table = $this->locator->get('Comments', [
            'className' => $class,
        ]);
        $this->assertInstanceOf($class, $table);
        $this->assertFalse($this->locator->exists('TestPluginComments'), 'Class name should not exist');
        $this->assertFalse($this->locator->exists('TestPlugin.TestPluginComments'), 'Full class alias should not exist');
        $this->assertTrue($this->locator->exists('Comments'), 'Class name should exist');
    }

    /**
     * Tests that table options can be pre-configured for the factory method
     */
    public function testConfigAndBuild(): void
    {
        $this->locator->clear();
        $map = $this->locator->getConfig();
        $this->assertEquals([], $map);

        $connection = ConnectionManager::get('test', false);
        $options = ['connection' => $connection];
        $this->locator->setConfig('users', $options);
        $map = $this->locator->getConfig();
        $this->assertEquals(['users' => $options], $map);
        $this->assertEquals($options, $this->locator->getConfig('users'));

        $schema = ['id' => ['type' => 'rubbish']];
        $options += ['schema' => $schema];
        $this->locator->setConfig('users', $options);

        $table = $this->locator->get('users', ['table' => 'users']);
        $this->assertInstanceOf(Table::class, $table);
        $this->assertSame('users', $table->getTable());
        $this->assertSame('users', $table->getAlias());
        $this->assertSame($connection, $table->getConnection());
        $this->assertEquals(array_keys($schema), $table->getSchema()->columns());
        $this->assertSame($schema['id']['type'], $table->getSchema()->getColumnType('id'));

        $this->locator->clear();
        $this->assertEmpty($this->locator->getConfig());

        $this->locator->setConfig('users', $options);
        $table = $this->locator->get('users', ['className' => MyUsersTable::class]);
        $this->assertInstanceOf(MyUsersTable::class, $table);
        $this->assertSame('users', $table->getTable());
        $this->assertSame('users', $table->getAlias());
        $this->assertSame($connection, $table->getConnection());
        $this->assertEquals(array_keys($schema), $table->getSchema()->columns());
        $this->assertSame($schema['id']['type'], $table->getSchema()->getColumnType('id'));
    }

    /**
     * Tests that table options can be pre-configured with a single validator
     */
    public function testConfigWithSingleValidator(): void
    {
        $validator = new Validator();

        $this->locator->setConfig('users', ['validator' => $validator]);
        $table = $this->locator->get('users');

        $this->assertSame($table->getValidator('default'), $validator);
    }

    /**
     * Tests that table options can be pre-configured with multiple validators
     */
    public function testConfigWithMultipleValidators(): void
    {
        $validator1 = new Validator();
        $validator2 = new Validator();
        $validator3 = new Validator();

        $this->locator->setConfig('users', [
            'validator' => [
                'default' => $validator1,
                'secondary' => $validator2,
                'tertiary' => $validator3,
            ],
        ]);
        $table = $this->locator->get('users');

        $this->assertSame($table->getValidator('default'), $validator1);
        $this->assertSame($table->getValidator('secondary'), $validator2);
        $this->assertSame($table->getValidator('tertiary'), $validator3);
    }

    /**
     * Test setting an instance.
     */
    public function testSet(): void
    {
        $mock = Mockery::mock(Table::class);
        $this->assertSame($mock, $this->locator->set('Articles', $mock));
        $this->assertSame($mock, $this->locator->get('Articles'));
    }

    /**
     * Test setting an instance with plugin syntax aliases
     */
    public function testSetPlugin(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $mock = Mockery::mock(CommentsTable::class);

        $this->assertSame($mock, $this->locator->set('TestPlugin.Comments', $mock));
        $this->assertSame($mock, $this->locator->get('TestPlugin.Comments'));
    }

    /**
     * Tests genericInstances
     */
    public function testGenericInstances(): void
    {
        $foos = $this->locator->get('Foos');
        $bars = $this->locator->get('Bars');
        $this->locator->get('Articles');
        $expected = ['Foos' => $foos, 'Bars' => $bars];
        $this->assertEquals($expected, $this->locator->genericInstances());
    }

    /**
     * Tests remove an instance
     */
    public function testRemove(): void
    {
        $first = $this->locator->get('Comments');

        $this->assertTrue($this->locator->exists('Comments'));

        $this->locator->remove('Comments');
        $this->assertFalse($this->locator->exists('Comments'));

        $second = $this->locator->get('Comments');

        $this->assertNotSame($first, $second, 'Should be different objects, as the reference to the first was destroyed');
        $this->assertTrue($this->locator->exists('Comments'));
    }

    /**
     * testRemovePlugin
     *
     * Removing a plugin-prefixed model should not affect any other
     * plugin-prefixed model, or app model.
     * Removing an app model should not affect any other
     * plugin-prefixed model.
     */
    public function testRemovePlugin(): void
    {
        $this->loadPlugins(['TestPlugin', 'TestPluginTwo']);

        $app = $this->locator->get('Comments');
        $this->locator->get('TestPlugin.Comments');
        $plugin = $this->locator->get('TestPluginTwo.Comments');

        $this->assertTrue($this->locator->exists('Comments'));
        $this->assertTrue($this->locator->exists('TestPlugin.Comments'));
        $this->assertTrue($this->locator->exists('TestPluginTwo.Comments'));

        $this->locator->remove('TestPlugin.Comments');

        $this->assertTrue($this->locator->exists('Comments'));
        $this->assertFalse($this->locator->exists('TestPlugin.Comments'));
        $this->assertTrue($this->locator->exists('TestPluginTwo.Comments'));

        $app2 = $this->locator->get('Comments');
        $plugin2 = $this->locator->get('TestPluginTwo.Comments');

        $this->assertSame($app, $app2, 'Should be the same Comments object');
        $this->assertSame($plugin, $plugin2, 'Should be the same TestPluginTwo.Comments object');

        $this->locator->remove('Comments');

        $this->assertFalse($this->locator->exists('Comments'));
        $this->assertFalse($this->locator->exists('TestPlugin.Comments'));
        $this->assertTrue($this->locator->exists('TestPluginTwo.Comments'));

        $plugin3 = $this->locator->get('TestPluginTwo.Comments');

        $this->assertSame($plugin, $plugin3, 'Should be the same TestPluginTwo.Comments object');
    }

    /**
     * testCustomLocation
     *
     * Tests that the correct table is returned when non-standard namespace is defined.
     */
    public function testCustomLocation(): void
    {
        $locator = new TableLocator(['Infrastructure/Table']);

        $table = $locator->get('Addresses');
        $this->assertInstanceOf(AddressesTable::class, $table);
    }

    /**
     * testCustomLocationPlugin
     *
     * Tests that the correct plugin table is returned when non-standard namespace is defined.
     */
    public function testCustomLocationPlugin(): void
    {
        $locator = new TableLocator(['Infrastructure/Table']);

        $table = $locator->get('TestPlugin.Addresses');
        $this->assertInstanceOf(PluginAddressesTable::class, $table);
    }

    /**
     * testCustomLocationDefaultWhenNone
     *
     * Tests that the default table is returned when no namespace is defined.
     */
    public function testCustomLocationDefaultWhenNone(): void
    {
        $locator = new TableLocator([]);

        $table = $locator->get('Addresses');
        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * testCustomLocationDefaultWhenMissing
     *
     * Tests that the default table is returned when the class cannot be found in a non-standard namespace.
     */
    public function testCustomLocationDefaultWhenMissing(): void
    {
        $locator = new TableLocator(['Infrastructure/Table']);

        $table = $locator->get('Articles');
        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * testCustomLocationMultiple
     *
     * Tests that the correct table is returned when multiple namespaces are defined.
     */
    public function testCustomLocationMultiple(): void
    {
        $locator = new TableLocator([
            'Infrastructure/Table',
            'Model/Table',
        ]);

        $table = $locator->get('Articles');
        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * testAddLocation
     *
     * Tests that adding a namespace takes effect.
     */
    public function testAddLocation(): void
    {
        $locator = new TableLocator([]);

        $table = $locator->get('Addresses');
        $this->assertInstanceOf(Table::class, $table);

        $locator->clear();
        $locator->addLocation('Infrastructure/Table');

        $table = $locator->get('Addresses');
        $this->assertInstanceOf(AddressesTable::class, $table);
    }

    public function testSetFallbackClassName(): void
    {
        $this->locator->setFallbackClassName(ArticlesTable::class);

        $table = $this->locator->get('FooBar');
        $this->assertInstanceOf(ArticlesTable::class, $table);
    }

    /**
     * testInstanceSetButNotOptions
     *
     * Tests that mock model will not throw an exception if model fetched with options.
     */
    public function testInstanceSetButNotOptions(): void
    {
        $this->setTableLocator($this->locator);
        $mock = $this->getMockForModel('Articles', ['findPublished']);
        $table = $this->locator->get('Articles', ['className' => ArticlesTable::class]);

        $this->assertSame($table, $mock);
    }

    public function testQueryFactoryInstance(): void
    {
        $articles = $this->locator->get(ArticlesTable::class);
        $prop1 = new ReflectionProperty($articles, 'queryFactory');

        $users = $this->locator->get(MyUsersTable::class);
        $prop2 = new ReflectionProperty($users, 'queryFactory');

        $this->assertInstanceOf(QueryFactory::class, $prop1->getValue($articles));
        $this->assertSame($prop1->getValue($articles), $prop2->getValue($users));

        $addresses = $this->locator->get(AddressesTable::class, ['queryFactory' => new QueryFactory()]);
        $prop3 = new ReflectionProperty($addresses, 'queryFactory');
        $this->assertNotSame($prop1->getValue($articles), $prop3->getValue($addresses));
    }
}
