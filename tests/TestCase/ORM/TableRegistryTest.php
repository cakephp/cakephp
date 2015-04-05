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

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;

/**
 * Used to test correct class is instantiated when using TableRegistry::get();
 */
class MyUsersTable extends Table
{

    /**
     * Overrides default table name
     *
     * @var string
     */
    protected $_table = 'users';
}


/**
 * Test case for TableRegistry
 */
class TableRegistryTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    /**
     * Test config() method.
     *
     * @return void
     */
    public function testConfig()
    {
        $this->assertEquals([], TableRegistry::config('Tests'));

        $data = [
            'connection' => 'testing',
            'entityClass' => 'TestApp\Model\Entity\Article',
        ];
        $result = TableRegistry::config('Tests', $data);
        $this->assertEquals($data, $result, 'Returns config data.');

        $result = TableRegistry::config();
        $expected = ['Tests' => $data];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test config() method with plugin syntax aliases
     *
     * @return void
     */
    public function testConfigPlugin()
    {
        Plugin::load('TestPlugin');

        $data = [
            'connection' => 'testing',
            'entityClass' => 'TestPlugin\Model\Entity\Comment',
        ];

        $result = TableRegistry::config('TestPlugin.TestPluginComments', $data);
        $this->assertEquals($data, $result, 'Returns config data.');
    }

    /**
     * Test calling config() on existing instances throws an error.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage You cannot configure "Users", it has already been constructed.
     * @return void
     */
    public function testConfigOnDefinedInstance()
    {
        $users = TableRegistry::get('Users');
        TableRegistry::config('Users', ['table' => 'my_users']);
    }

    /**
     * Test the exists() method.
     *
     * @return void
     */
    public function testExists()
    {
        $this->assertFalse(TableRegistry::exists('Articles'));

        TableRegistry::config('Articles', ['table' => 'articles']);
        $this->assertFalse(TableRegistry::exists('Articles'));

        TableRegistry::get('Articles', ['table' => 'articles']);
        $this->assertTrue(TableRegistry::exists('Articles'));
    }

    /**
     * Test the exists() method with plugin-prefixed models.
     *
     * @return void
     */
    public function testExistsPlugin()
    {
        $this->assertFalse(TableRegistry::exists('Comments'));
        $this->assertFalse(TableRegistry::exists('TestPlugin.Comments'));

        TableRegistry::config('TestPlugin.Comments', ['table' => 'comments']);
        $this->assertFalse(TableRegistry::exists('Comments'), 'The Comments key should not be populated');
        $this->assertFalse(TableRegistry::exists('TestPlugin.Comments'), 'The plugin.alias key should not be populated');

        TableRegistry::get('TestPlugin.Comments', ['table' => 'comments']);
        $this->assertFalse(TableRegistry::exists('Comments'), 'The Comments key should not be populated');
        $this->assertTrue(TableRegistry::exists('TestPlugin.Comments'), 'The plugin.alias key should now be populated');
    }

    /**
     * Test getting instances from the registry.
     *
     * @return void
     */
    public function testGet()
    {
        $result = TableRegistry::get('Articles', [
            'table' => 'my_articles',
        ]);
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('my_articles', $result->table());

        $result2 = TableRegistry::get('Articles');
        $this->assertSame($result, $result2);
        $this->assertEquals('my_articles', $result->table());
    }

    /**
     * Are auto-models instanciated correctly? How about when they have an alias?
     *
     * @return void
     */
    public function testGetFallbacks()
    {
        $result = TableRegistry::get('Droids');
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('droids', $result->table());
        $this->assertEquals('Droids', $result->alias());

        $result = TableRegistry::get('R2D2', ['className' => 'Droids']);
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('droids', $result->table(), 'The table should be derived from the className');
        $this->assertEquals('R2D2', $result->alias());

        $result = TableRegistry::get('C3P0', ['className' => 'Droids', 'table' => 'rebels']);
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('rebels', $result->table(), 'The table should be taken from options');
        $this->assertEquals('C3P0', $result->alias());

        $result = TableRegistry::get('Funky.Chipmunks');
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('chipmunks', $result->table(), 'The table should be derived from the alias');
        $this->assertEquals('Chipmunks', $result->alias());

        $result = TableRegistry::get('Awesome', ['className' => 'Funky.Monkies']);
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('monkies', $result->table(), 'The table should be derived from the classname');
        $this->assertEquals('Awesome', $result->alias());

        $result = TableRegistry::get('Stuff', ['className' => 'Cake\ORM\Table']);
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('stuff', $result->table(), 'The table should be derived from the alias');
        $this->assertEquals('Stuff', $result->alias());
    }

    /**
     * Test that get() uses config data set with config()
     *
     * @return void
     */
    public function testGetWithConfig()
    {
        TableRegistry::config('Articles', [
            'table' => 'my_articles',
        ]);
        $result = TableRegistry::get('Articles');
        $this->assertEquals('my_articles', $result->table(), 'Should use config() data.');
    }

    /**
     * Test get with config throws an exception if the alias exists already.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage You cannot configure "Users", it already exists in the registry.
     * @return void
     */
    public function testGetExistingWithConfigData()
    {
        $users = TableRegistry::get('Users');
        TableRegistry::get('Users', ['table' => 'my_users']);
    }

    /**
     * Test get() can be called several times with the same option without
     * throwing an exception.
     *
     * @return void
     */
    public function testGetWithSameOption()
    {
        $result = TableRegistry::get('Users', ['className' => 'Cake\Test\TestCase\ORM\MyUsersTable']);
        $result2 = TableRegistry::get('Users', ['className' => 'Cake\Test\TestCase\ORM\MyUsersTable']);
        $this->assertEquals($result, $result2);
    }

    /**
     * Tests that tables can be instantiated based on conventions
     * and using plugin notation
     *
     * @return void
     */
    public function testGetWithConventions()
    {
        $table = TableRegistry::get('articles');
        $this->assertInstanceOf('TestApp\Model\Table\ArticlesTable', $table);
        $table = TableRegistry::get('Articles');
        $this->assertInstanceOf('TestApp\Model\Table\ArticlesTable', $table);

        $table = TableRegistry::get('authors');
        $this->assertInstanceOf('TestApp\Model\Table\AuthorsTable', $table);
        $table = TableRegistry::get('Authors');
        $this->assertInstanceOf('TestApp\Model\Table\AuthorsTable', $table);
    }

    /**
     * Test get() with plugin syntax aliases
     *
     * @return void
     */
    public function testGetPlugin()
    {
        Plugin::load('TestPlugin');
        $table = TableRegistry::get('TestPlugin.TestPluginComments');

        $this->assertInstanceOf('TestPlugin\Model\Table\TestPluginCommentsTable', $table);
        $this->assertFalse(
            TableRegistry::exists('TestPluginComments'),
            'Short form should NOT exist'
        );
        $this->assertTrue(
            TableRegistry::exists('TestPlugin.TestPluginComments'),
            'Long form should exist'
        );

        $second = TableRegistry::get('TestPlugin.TestPluginComments');
        $this->assertSame($table, $second, 'Can fetch long form');
    }

    /**
     * Test get() with same-alias models in different plugins
     *
     * There should be no internal cache-confusion
     *
     * @return void
     */
    public function testGetMultiplePlugins()
    {
        Plugin::load('TestPlugin');
        Plugin::load('TestPluginTwo');

        $app = TableRegistry::get('Comments');
        $plugin1 = TableRegistry::get('TestPlugin.Comments');
        $plugin2 = TableRegistry::get('TestPluginTwo.Comments');

        $this->assertInstanceOf('Cake\ORM\Table', $app, 'Should be an app table instance');
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $plugin1, 'Should be a plugin 1 table instance');
        $this->assertInstanceOf('TestPluginTwo\Model\Table\CommentsTable', $plugin2, 'Should be a plugin 2 table instance');

        $plugin2 = TableRegistry::get('TestPluginTwo.Comments');
        $plugin1 = TableRegistry::get('TestPlugin.Comments');
        $app = TableRegistry::get('Comments');

        $this->assertInstanceOf('Cake\ORM\Table', $app, 'Should still be an app table instance');
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $plugin1, 'Should still be a plugin 1 table instance');
        $this->assertInstanceOf('TestPluginTwo\Model\Table\CommentsTable', $plugin2, 'Should still be a plugin 2 table instance');
    }

    /**
     * Test get() with plugin aliases + className option.
     *
     * @return void
     */
    public function testGetPluginWithClassNameOption()
    {
        Plugin::load('TestPlugin');
        $table = TableRegistry::get('Comments', [
            'className' => 'TestPlugin.TestPluginComments',
        ]);
        $class = 'TestPlugin\Model\Table\TestPluginCommentsTable';
        $this->assertInstanceOf($class, $table);
        $this->assertFalse(TableRegistry::exists('TestPluginComments'), 'Class name should not exist');
        $this->assertFalse(TableRegistry::exists('TestPlugin.TestPluginComments'), 'Full class alias should not exist');
        $this->assertTrue(TableRegistry::exists('Comments'), 'Class name should exist');

        $second = TableRegistry::get('Comments');
        $this->assertSame($table, $second);
    }

    /**
     * Test get() with full namespaced classname
     *
     * @return void
     */
    public function testGetPluginWithFullNamespaceName()
    {
        Plugin::load('TestPlugin');
        $class = 'TestPlugin\Model\Table\TestPluginCommentsTable';
        $table = TableRegistry::get('Comments', [
            'className' => $class,
        ]);
        $this->assertInstanceOf($class, $table);
        $this->assertFalse(TableRegistry::exists('TestPluginComments'), 'Class name should not exist');
        $this->assertFalse(TableRegistry::exists('TestPlugin.TestPluginComments'), 'Full class alias should not exist');
        $this->assertTrue(TableRegistry::exists('Comments'), 'Class name should exist');
    }

    /**
     * Tests that table options can be pre-configured for the factory method
     *
     * @return void
     */
    public function testConfigAndBuild()
    {
        TableRegistry::clear();
        $map = TableRegistry::config();
        $this->assertEquals([], $map);

        $connection = ConnectionManager::get('test', false);
        $options = ['connection' => $connection];
        TableRegistry::config('users', $options);
        $map = TableRegistry::config();
        $this->assertEquals(['users' => $options], $map);
        $this->assertEquals($options, TableRegistry::config('users'));

        $schema = ['id' => ['type' => 'rubbish']];
        $options += ['schema' => $schema];
        TableRegistry::config('users', $options);

        $table = TableRegistry::get('users', ['table' => 'users']);
        $this->assertInstanceOf('Cake\ORM\Table', $table);
        $this->assertEquals('users', $table->table());
        $this->assertEquals('users', $table->alias());
        $this->assertSame($connection, $table->connection());
        $this->assertEquals(array_keys($schema), $table->schema()->columns());
        $this->assertEquals($schema['id']['type'], $table->schema()->column('id')['type']);

        TableRegistry::clear();
        $this->assertEmpty(TableRegistry::config());

        TableRegistry::config('users', $options);
        $table = TableRegistry::get('users', ['className' => __NAMESPACE__ . '\MyUsersTable']);
        $this->assertInstanceOf(__NAMESPACE__ . '\MyUsersTable', $table);
        $this->assertEquals('users', $table->table());
        $this->assertEquals('users', $table->alias());
        $this->assertSame($connection, $table->connection());
        $this->assertEquals(array_keys($schema), $table->schema()->columns());
        $this->assertEquals($schema['id']['type'], $table->schema()->column('id')['type']);
    }

    /**
     * Tests that table options can be pre-configured with a single validator
     *
     * @return void
     */
    public function testConfigWithSingleValidator()
    {
        $validator = new Validator();

        TableRegistry::config('users', ['validator' => $validator]);
        $table = TableRegistry::get('users');

        $this->assertSame($table->validator('default'), $validator);
    }

    /**
     * Tests that table options can be pre-configured with multiple validators
     *
     * @return void
     */
    public function testConfigWithMultipleValidators()
    {
        $validator1 = new Validator();
        $validator2 = new Validator();
        $validator3 = new Validator();

        TableRegistry::config('users', [
            'validator' => [
                'default' => $validator1,
                'secondary' => $validator2,
                'tertiary' => $validator3,
            ]
        ]);
        $table = TableRegistry::get('users');

        $this->assertSame($table->validator('default'), $validator1);
        $this->assertSame($table->validator('secondary'), $validator2);
        $this->assertSame($table->validator('tertiary'), $validator3);
    }

    /**
     * Test setting an instance.
     *
     * @return void
     */
    public function testSet()
    {
        $mock = $this->getMock('Cake\ORM\Table');
        $this->assertSame($mock, TableRegistry::set('Articles', $mock));
        $this->assertSame($mock, TableRegistry::get('Articles'));
    }

    /**
     * Test setting an instance with plugin syntax aliases
     *
     * @return void
     */
    public function testSetPlugin()
    {
        Plugin::load('TestPlugin');

        $mock = $this->getMock('TestPlugin\Model\Table\CommentsTable');

        $this->assertSame($mock, TableRegistry::set('TestPlugin.Comments', $mock));
        $this->assertSame($mock, TableRegistry::get('TestPlugin.Comments'));
    }

    /**
     * Tests genericInstances
     *
     * @return void
     */
    public function testGenericInstances()
    {
        $foos = TableRegistry::get('Foos');
        $bars = TableRegistry::get('Bars');
        TableRegistry::get('Articles');
        $expected = ['Foos' => $foos, 'Bars' => $bars];
        $this->assertEquals($expected, TableRegistry::genericInstances());
    }

    /**
     * Tests remove an instance
     *
     * @return void
     */
    public function testRemove()
    {
        $first = TableRegistry::get('Comments');

        $this->assertTrue(TableRegistry::exists('Comments'));

        TableRegistry::remove('Comments');
        $this->assertFalse(TableRegistry::exists('Comments'));

        $second = TableRegistry::get('Comments');

        $this->assertNotSame($first, $second, 'Should be different objects, as the reference to the first was destroyed');
        $this->assertTrue(TableRegistry::exists('Comments'));
    }

    /**
     * testRemovePlugin
     *
     * Removing a plugin-prefixed model should not affect any other
     * plugin-prefixed model, or app model.
     * Removing an app model should not affect any other
     * plugin-prefixed model.
     *
     * @return void
     */
    public function testRemovePlugin()
    {
        Plugin::load('TestPlugin');
        Plugin::load('TestPluginTwo');

        $app = TableRegistry::get('Comments');
        TableRegistry::get('TestPlugin.Comments');
        $plugin = TableRegistry::get('TestPluginTwo.Comments');

        $this->assertTrue(TableRegistry::exists('Comments'));
        $this->assertTrue(TableRegistry::exists('TestPlugin.Comments'));
        $this->assertTrue(TableRegistry::exists('TestPluginTwo.Comments'));

        TableRegistry::remove('TestPlugin.Comments');

        $this->assertTrue(TableRegistry::exists('Comments'));
        $this->assertFalse(TableRegistry::exists('TestPlugin.Comments'));
        $this->assertTrue(TableRegistry::exists('TestPluginTwo.Comments'));

        $app2 = TableRegistry::get('Comments');
        $plugin2 = TableRegistry::get('TestPluginTwo.Comments');

        $this->assertSame($app, $app2, 'Should be the same Comments object');
        $this->assertSame($plugin, $plugin2, 'Should be the same TestPluginTwo.Comments object');

        TableRegistry::remove('Comments');

        $this->assertFalse(TableRegistry::exists('Comments'));
        $this->assertFalse(TableRegistry::exists('TestPlugin.Comments'));
        $this->assertTrue(TableRegistry::exists('TestPluginTwo.Comments'));

        $plugin3 = TableRegistry::get('TestPluginTwo.Comments');

        $this->assertSame($plugin, $plugin3, 'Should be the same TestPluginTwo.Comments object');
    }
}
