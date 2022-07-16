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

use Cake\Datasource\ConnectionManager;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use RuntimeException;
use TestApp\Infrastructure\Table\AddressesTable;
use TestApp\Model\Table\ArticlesTable;
use TestApp\Model\Table\MyUsersTable;
use TestPlugin\Infrastructure\Table\AddressesTable as PluginAddressesTable;

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
    protected $_locator;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();

        $this->_locator = new TableLocator();
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        $this->clearPlugins();
        parent::tearDown();
    }

    /**
     * Test getConfig() method.
     */
    public function testGetConfig(): void
    {
        $this->assertEquals([], $this->_locator->getConfig('Tests'));

        $data = [
            'connection' => 'testing',
            'entityClass' => 'TestApp\Model\Entity\Article',
        ];
        $result = $this->_locator->setConfig('Tests', $data);
        $this->assertSame($this->_locator, $result, 'Returns locator');

        $result = $this->_locator->getConfig();
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
            'entityClass' => 'TestPlugin\Model\Entity\Comment',
        ];

        $result = $this->_locator->setConfig('TestPlugin.TestPluginComments', $data);
        $this->assertSame($this->_locator, $result, 'Returns locator');
    }

    /**
     * Test calling getConfig() on existing instances throws an error.
     */
    public function testConfigOnDefinedInstance(): void
    {
        $users = $this->_locator->get('Users');
        $this->assertNotEmpty($users);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You cannot configure "Users", it has already been constructed.');

        $this->_locator->setConfig('Users', ['table' => 'my_users']);
    }

    /**
     * Test the exists() method.
     */
    public function testExists(): void
    {
        $this->assertFalse($this->_locator->exists('Articles'));

        $this->_locator->setConfig('Articles', ['table' => 'articles']);
        $this->assertFalse($this->_locator->exists('Articles'));

        $this->_locator->get('Articles', ['table' => 'articles']);
        $this->assertTrue($this->_locator->exists('Articles'));
    }

    /**
     * Tests the casing and locator. Using table name directly is not
     * the same as using conventional aliases anymore.
     */
    public function testCasing(): void
    {
        $this->assertFalse($this->_locator->exists('Articles'));

        $Article = $this->_locator->get('Articles', ['table' => 'articles']);
        $this->assertTrue($this->_locator->exists('Articles'));

        $this->assertFalse($this->_locator->exists('articles'));

        $article = $this->_locator->get('articles');
        $this->assertTrue($this->_locator->exists('articles'));

        $this->assertNotSame($Article, $article);
    }

    /**
     * Test the exists() method with plugin-prefixed models.
     */
    public function testExistsPlugin(): void
    {
        $this->assertFalse($this->_locator->exists('Comments'));
        $this->assertFalse($this->_locator->exists('TestPlugin.Comments'));

        $this->_locator->setConfig('TestPlugin.Comments', ['table' => 'comments']);
        $this->assertFalse($this->_locator->exists('Comments'), 'The Comments key should not be populated');
        $this->assertFalse($this->_locator->exists('TestPlugin.Comments'), 'The plugin.alias key should not be populated');

        $this->_locator->get('TestPlugin.Comments', ['table' => 'comments']);
        $this->assertFalse($this->_locator->exists('Comments'), 'The Comments key should not be populated');
        $this->assertTrue($this->_locator->exists('TestPlugin.Comments'), 'The plugin.alias key should now be populated');
    }

    /**
     * Test getting instances from the registry.
     */
    public function testGet(): void
    {
        $result = $this->_locator->get('Articles', [
            'table' => 'my_articles',
        ]);
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('my_articles', $result->getTable());

        $result2 = $this->_locator->get('Articles');
        $this->assertSame($result, $result2);
        $this->assertSame('my_articles', $result->getTable());

        $this->assertSame($this->_locator, $result->associations()->getTableLocator());

        $result = $this->_locator->get(ArticlesTable::class);
        $this->assertSame('Articles', $result->getAlias());
        $this->assertSame(ArticlesTable::class, $result->getRegistryAlias());

        $result2 = $this->_locator->get($result->getRegistryAlias());
        $this->assertSame($result, $result2);
    }

    /**
     * Are auto-models instantiated correctly? How about when they have an alias?
     */
    public function testGetFallbacks(): void
    {
        $result = $this->_locator->get('Droids');
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('droids', $result->getTable());
        $this->assertSame('Droids', $result->getAlias());

        $result = $this->_locator->get('R2D2', ['className' => 'Droids']);
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('droids', $result->getTable(), 'The table should be derived from the className');
        $this->assertSame('R2D2', $result->getAlias());

        $result = $this->_locator->get('C3P0', ['className' => 'Droids', 'table' => 'rebels']);
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('rebels', $result->getTable(), 'The table should be taken from options');
        $this->assertSame('C3P0', $result->getAlias());

        $result = $this->_locator->get('Funky.Chipmunks');
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('chipmunks', $result->getTable(), 'The table should be derived from the alias');
        $this->assertSame('Chipmunks', $result->getAlias());

        $result = $this->_locator->get('Awesome', ['className' => 'Funky.Monkies']);
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('monkies', $result->getTable(), 'The table should be derived from the classname');
        $this->assertSame('Awesome', $result->getAlias());

        $result = $this->_locator->get('Stuff', ['className' => Table::class]);
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('stuff', $result->getTable(), 'The table should be derived from the alias');
        $this->assertSame('Stuff', $result->getAlias());
    }

    public function testExceptionForAliasWhenFallbackTurnedOff(): void
    {
        $this->expectException(MissingTableClassException::class);
        $this->expectExceptionMessage('Table class for alias `Droids` could not be found.');

        $this->_locator->get('Droids', ['allowFallbackClass' => false]);
    }

    public function testExceptionForFQCNWhenFallbackTurnedOff(): void
    {
        $this->expectException(MissingTableClassException::class);
        $this->expectExceptionMessage('Table class `App\Model\DroidsTable` could not be found.');

        $this->_locator->get('App\Model\DroidsTable', ['allowFallbackClass' => false]);
    }

    /**
     * Test that get() uses config data set with getConfig()
     */
    public function testGetWithgetConfig(): void
    {
        $this->_locator->setConfig('Articles', [
            'table' => 'my_articles',
        ]);
        $result = $this->_locator->get('Articles');
        $this->assertSame('my_articles', $result->getTable(), 'Should use getConfig() data.');
    }

    /**
     * Test that get() uses config data set with getConfig()
     */
    public function testGetWithConnectionName(): void
    {
        ConnectionManager::alias('test', 'testing');
        $result = $this->_locator->get('Articles', [
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
        $this->_locator->setConfig('MyUsersTableAlias', [
            'className' => MyUsersTable::class,
        ]);
        $result = $this->_locator->get('MyUsersTableAlias');
        $this->assertInstanceOf(MyUsersTable::class, $result, 'Should use getConfig() data className option.');
    }

    /**
     * Test get with config throws an exception if the alias exists already.
     */
    public function testGetExistingWithConfigData(): void
    {
        $users = $this->_locator->get('Users');
        $this->assertNotEmpty($users);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You cannot configure "Users", it already exists in the registry.');

        $this->_locator->get('Users', ['table' => 'my_users']);
    }

    /**
     * Test get() can be called several times with the same option without
     * throwing an exception.
     */
    public function testGetWithSameOption(): void
    {
        $result = $this->_locator->get('Users', ['className' => MyUsersTable::class]);
        $result2 = $this->_locator->get('Users', ['className' => MyUsersTable::class]);
        $this->assertEquals($result, $result2);
    }

    /**
     * Tests that tables can be instantiated based on conventions
     * and using plugin notation
     */
    public function testGetWithConventions(): void
    {
        $table = $this->_locator->get('articles');
        $this->assertInstanceOf('TestApp\Model\Table\ArticlesTable', $table);
        $table = $this->_locator->get('Articles');
        $this->assertInstanceOf('TestApp\Model\Table\ArticlesTable', $table);

        $table = $this->_locator->get('authors');
        $this->assertInstanceOf('TestApp\Model\Table\AuthorsTable', $table);
        $table = $this->_locator->get('Authors');
        $this->assertInstanceOf('TestApp\Model\Table\AuthorsTable', $table);
    }

    /**
     * Test get() with plugin syntax aliases
     */
    public function testGetPlugin(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $table = $this->_locator->get('TestPlugin.TestPluginComments');

        $this->assertInstanceOf('TestPlugin\Model\Table\TestPluginCommentsTable', $table);
        $this->assertFalse(
            $this->_locator->exists('TestPluginComments'),
            'Short form should NOT exist'
        );
        $this->assertTrue(
            $this->_locator->exists('TestPlugin.TestPluginComments'),
            'Long form should exist'
        );

        $second = $this->_locator->get('TestPlugin.TestPluginComments');
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

        $app = $this->_locator->get('Comments');
        $plugin1 = $this->_locator->get('TestPlugin.Comments');
        $plugin2 = $this->_locator->get('TestPluginTwo.Comments');

        $this->assertInstanceOf(Table::class, $app, 'Should be an app table instance');
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $plugin1, 'Should be a plugin 1 table instance');
        $this->assertInstanceOf('TestPluginTwo\Model\Table\CommentsTable', $plugin2, 'Should be a plugin 2 table instance');

        $plugin2 = $this->_locator->get('TestPluginTwo.Comments');
        $plugin1 = $this->_locator->get('TestPlugin.Comments');
        $app = $this->_locator->get('Comments');

        $this->assertInstanceOf(Table::class, $app, 'Should still be an app table instance');
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $plugin1, 'Should still be a plugin 1 table instance');
        $this->assertInstanceOf('TestPluginTwo\Model\Table\CommentsTable', $plugin2, 'Should still be a plugin 2 table instance');
    }

    /**
     * Test get() with plugin aliases + className option.
     */
    public function testGetPluginWithClassNameOption(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $table = $this->_locator->get('Comments', [
            'className' => 'TestPlugin.TestPluginComments',
        ]);
        $class = 'TestPlugin\Model\Table\TestPluginCommentsTable';
        $this->assertInstanceOf($class, $table);
        $this->assertFalse($this->_locator->exists('TestPluginComments'), 'Class name should not exist');
        $this->assertFalse($this->_locator->exists('TestPlugin.TestPluginComments'), 'Full class alias should not exist');
        $this->assertTrue($this->_locator->exists('Comments'), 'Class name should exist');

        $second = $this->_locator->get('Comments');
        $this->assertSame($table, $second);
    }

    /**
     * Test get() with full namespaced classname
     */
    public function testGetPluginWithFullNamespaceName(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $class = 'TestPlugin\Model\Table\TestPluginCommentsTable';
        $table = $this->_locator->get('Comments', [
            'className' => $class,
        ]);
        $this->assertInstanceOf($class, $table);
        $this->assertFalse($this->_locator->exists('TestPluginComments'), 'Class name should not exist');
        $this->assertFalse($this->_locator->exists('TestPlugin.TestPluginComments'), 'Full class alias should not exist');
        $this->assertTrue($this->_locator->exists('Comments'), 'Class name should exist');
    }

    /**
     * Tests that table options can be pre-configured for the factory method
     */
    public function testConfigAndBuild(): void
    {
        $this->_locator->clear();
        $map = $this->_locator->getConfig();
        $this->assertEquals([], $map);

        $connection = ConnectionManager::get('test', false);
        $options = ['connection' => $connection];
        $this->_locator->setConfig('users', $options);
        $map = $this->_locator->getConfig();
        $this->assertEquals(['users' => $options], $map);
        $this->assertEquals($options, $this->_locator->getConfig('users'));

        $schema = ['id' => ['type' => 'rubbish']];
        $options += ['schema' => $schema];
        $this->_locator->setConfig('users', $options);

        $table = $this->_locator->get('users', ['table' => 'users']);
        $this->assertInstanceOf(Table::class, $table);
        $this->assertSame('users', $table->getTable());
        $this->assertSame('users', $table->getAlias());
        $this->assertSame($connection, $table->getConnection());
        $this->assertEquals(array_keys($schema), $table->getSchema()->columns());
        $this->assertSame($schema['id']['type'], $table->getSchema()->getColumnType('id'));

        $this->_locator->clear();
        $this->assertEmpty($this->_locator->getConfig());

        $this->_locator->setConfig('users', $options);
        $table = $this->_locator->get('users', ['className' => MyUsersTable::class]);
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

        $this->_locator->setConfig('users', ['validator' => $validator]);
        $table = $this->_locator->get('users');

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

        $this->_locator->setConfig('users', [
            'validator' => [
                'default' => $validator1,
                'secondary' => $validator2,
                'tertiary' => $validator3,
            ],
        ]);
        $table = $this->_locator->get('users');

        $this->assertSame($table->getValidator('default'), $validator1);
        $this->assertSame($table->getValidator('secondary'), $validator2);
        $this->assertSame($table->getValidator('tertiary'), $validator3);
    }

    /**
     * Test setting an instance.
     */
    public function testSet(): void
    {
        $mock = $this->getMockBuilder(Table::class)->getMock();
        $this->assertSame($mock, $this->_locator->set('Articles', $mock));
        $this->assertSame($mock, $this->_locator->get('Articles'));
    }

    /**
     * Test setting an instance with plugin syntax aliases
     */
    public function testSetPlugin(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $mock = $this->getMockBuilder('TestPlugin\Model\Table\CommentsTable')->getMock();

        $this->assertSame($mock, $this->_locator->set('TestPlugin.Comments', $mock));
        $this->assertSame($mock, $this->_locator->get('TestPlugin.Comments'));
    }

    /**
     * Tests genericInstances
     */
    public function testGenericInstances(): void
    {
        $foos = $this->_locator->get('Foos');
        $bars = $this->_locator->get('Bars');
        $this->_locator->get('Articles');
        $expected = ['Foos' => $foos, 'Bars' => $bars];
        $this->assertEquals($expected, $this->_locator->genericInstances());
    }

    /**
     * Tests remove an instance
     */
    public function testRemove(): void
    {
        $first = $this->_locator->get('Comments');

        $this->assertTrue($this->_locator->exists('Comments'));

        $this->_locator->remove('Comments');
        $this->assertFalse($this->_locator->exists('Comments'));

        $second = $this->_locator->get('Comments');

        $this->assertNotSame($first, $second, 'Should be different objects, as the reference to the first was destroyed');
        $this->assertTrue($this->_locator->exists('Comments'));
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

        $app = $this->_locator->get('Comments');
        $this->_locator->get('TestPlugin.Comments');
        $plugin = $this->_locator->get('TestPluginTwo.Comments');

        $this->assertTrue($this->_locator->exists('Comments'));
        $this->assertTrue($this->_locator->exists('TestPlugin.Comments'));
        $this->assertTrue($this->_locator->exists('TestPluginTwo.Comments'));

        $this->_locator->remove('TestPlugin.Comments');

        $this->assertTrue($this->_locator->exists('Comments'));
        $this->assertFalse($this->_locator->exists('TestPlugin.Comments'));
        $this->assertTrue($this->_locator->exists('TestPluginTwo.Comments'));

        $app2 = $this->_locator->get('Comments');
        $plugin2 = $this->_locator->get('TestPluginTwo.Comments');

        $this->assertSame($app, $app2, 'Should be the same Comments object');
        $this->assertSame($plugin, $plugin2, 'Should be the same TestPluginTwo.Comments object');

        $this->_locator->remove('Comments');

        $this->assertFalse($this->_locator->exists('Comments'));
        $this->assertFalse($this->_locator->exists('TestPlugin.Comments'));
        $this->assertTrue($this->_locator->exists('TestPluginTwo.Comments'));

        $plugin3 = $this->_locator->get('TestPluginTwo.Comments');

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
        $this->_locator->setFallbackClassName(ArticlesTable::class);

        $table = $this->_locator->get('FooBar');
        $this->assertInstanceOf(ArticlesTable::class, $table);
    }

    /**
     * testInstanceSetButNotOptions
     *
     * Tests that mock model will not throw an exception if model fetched with options.
     */
    public function testInstanceSetButNotOptions(): void
    {
        $this->setTableLocator($this->_locator);
        $mock = $this->getMockForModel('Articles', ['findPublished']);
        $table = $this->_locator->get('Articles', ['className' => ArticlesTable::class]);

        $this->assertSame($table, $mock);
    }
}
