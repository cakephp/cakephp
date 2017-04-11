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
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Locator;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;

/**
 * Used to test correct class is instantiated when using $this->_locator->get();
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
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');

        $this->_locator = new TableLocator;
    }

    /**
     * Test config() method.
     *
     * @return void
     */
    public function testConfig()
    {
        $this->assertEquals([], $this->_locator->config('Tests'));

        $data = [
            'connection' => 'testing',
            'entityClass' => 'TestApp\Model\Entity\Article',
        ];
        $result = $this->_locator->config('Tests', $data);
        $this->assertEquals($data, $result, 'Returns config data.');

        $result = $this->_locator->config();
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

        $result = $this->_locator->config('TestPlugin.TestPluginComments', $data);
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
        $users = $this->_locator->get('Users');
        $this->_locator->config('Users', ['table' => 'my_users']);
    }

    /**
     * Test the exists() method.
     *
     * @return void
     */
    public function testExists()
    {
        $this->assertFalse($this->_locator->exists('Articles'));

        $this->_locator->config('Articles', ['table' => 'articles']);
        $this->assertFalse($this->_locator->exists('Articles'));

        $this->_locator->get('Articles', ['table' => 'articles']);
        $this->assertTrue($this->_locator->exists('Articles'));
    }

    /**
     * Test the exists() method with plugin-prefixed models.
     *
     * @return void
     */
    public function testExistsPlugin()
    {
        $this->assertFalse($this->_locator->exists('Comments'));
        $this->assertFalse($this->_locator->exists('TestPlugin.Comments'));

        $this->_locator->config('TestPlugin.Comments', ['table' => 'comments']);
        $this->assertFalse($this->_locator->exists('Comments'), 'The Comments key should not be populated');
        $this->assertFalse($this->_locator->exists('TestPlugin.Comments'), 'The plugin.alias key should not be populated');

        $this->_locator->get('TestPlugin.Comments', ['table' => 'comments']);
        $this->assertFalse($this->_locator->exists('Comments'), 'The Comments key should not be populated');
        $this->assertTrue($this->_locator->exists('TestPlugin.Comments'), 'The plugin.alias key should now be populated');
    }

    /**
     * Test getting instances from the registry.
     *
     * @return void
     */
    public function testGet()
    {
        $result = $this->_locator->get('Articles', [
            'table' => 'my_articles',
        ]);
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('my_articles', $result->table());

        $result2 = $this->_locator->get('Articles');
        $this->assertSame($result, $result2);
        $this->assertEquals('my_articles', $result->table());
    }

    /**
     * Are auto-models instantiated correctly? How about when they have an alias?
     *
     * @return void
     */
    public function testGetFallbacks()
    {
        $result = $this->_locator->get('Droids');
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('droids', $result->table());
        $this->assertEquals('Droids', $result->alias());

        $result = $this->_locator->get('R2D2', ['className' => 'Droids']);
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('droids', $result->table(), 'The table should be derived from the className');
        $this->assertEquals('R2D2', $result->alias());

        $result = $this->_locator->get('C3P0', ['className' => 'Droids', 'table' => 'rebels']);
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('rebels', $result->table(), 'The table should be taken from options');
        $this->assertEquals('C3P0', $result->alias());

        $result = $this->_locator->get('Funky.Chipmunks');
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('chipmunks', $result->table(), 'The table should be derived from the alias');
        $this->assertEquals('Chipmunks', $result->alias());

        $result = $this->_locator->get('Awesome', ['className' => 'Funky.Monkies']);
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('monkies', $result->table(), 'The table should be derived from the classname');
        $this->assertEquals('Awesome', $result->alias());

        $result = $this->_locator->get('Stuff', ['className' => 'Cake\ORM\Table']);
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
        $this->_locator->config('Articles', [
            'table' => 'my_articles',
        ]);
        $result = $this->_locator->get('Articles');
        $this->assertEquals('my_articles', $result->table(), 'Should use config() data.');
    }

    /**
     * Test that get() uses config data set with config()
     *
     * @return void
     */
    public function testGetWithConnectionName()
    {
        ConnectionManager::alias('test', 'testing');
        $result = $this->_locator->get('Articles', [
            'connectionName' => 'testing'
        ]);
        $this->assertEquals('articles', $result->table());
        $this->assertEquals('test', $result->connection()->configName());
    }

    /**
     * Test that get() uses config data `className` set with config()
     *
     * @return void
     */
    public function testGetWithConfigClassName()
    {
        $this->_locator->config('MyUsersTableAlias', [
            'className' => '\Cake\Test\TestCase\ORM\Locator\MyUsersTable',
        ]);
        $result = $this->_locator->get('MyUsersTableAlias');
        $this->assertInstanceOf('\Cake\Test\TestCase\ORM\Locator\MyUsersTable', $result, 'Should use config() data className option.');
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
        $users = $this->_locator->get('Users');
        $this->_locator->get('Users', ['table' => 'my_users']);
    }

    /**
     * Test get() can be called several times with the same option without
     * throwing an exception.
     *
     * @return void
     */
    public function testGetWithSameOption()
    {
        $result = $this->_locator->get('Users', ['className' => 'Cake\Test\TestCase\ORM\Locator\MyUsersTable']);
        $result2 = $this->_locator->get('Users', ['className' => 'Cake\Test\TestCase\ORM\Locator\MyUsersTable']);
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
     *
     * @return void
     */
    public function testGetPlugin()
    {
        Plugin::load('TestPlugin');
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
     *
     * @return void
     */
    public function testGetMultiplePlugins()
    {
        Plugin::load('TestPlugin');
        Plugin::load('TestPluginTwo');

        $app = $this->_locator->get('Comments');
        $plugin1 = $this->_locator->get('TestPlugin.Comments');
        $plugin2 = $this->_locator->get('TestPluginTwo.Comments');

        $this->assertInstanceOf('Cake\ORM\Table', $app, 'Should be an app table instance');
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $plugin1, 'Should be a plugin 1 table instance');
        $this->assertInstanceOf('TestPluginTwo\Model\Table\CommentsTable', $plugin2, 'Should be a plugin 2 table instance');

        $plugin2 = $this->_locator->get('TestPluginTwo.Comments');
        $plugin1 = $this->_locator->get('TestPlugin.Comments');
        $app = $this->_locator->get('Comments');

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
     *
     * @return void
     */
    public function testGetPluginWithFullNamespaceName()
    {
        Plugin::load('TestPlugin');
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
     *
     * @return void
     */
    public function testConfigAndBuild()
    {
        $this->_locator->clear();
        $map = $this->_locator->config();
        $this->assertEquals([], $map);

        $connection = ConnectionManager::get('test', false);
        $options = ['connection' => $connection];
        $this->_locator->config('users', $options);
        $map = $this->_locator->config();
        $this->assertEquals(['users' => $options], $map);
        $this->assertEquals($options, $this->_locator->config('users'));

        $schema = ['id' => ['type' => 'rubbish']];
        $options += ['schema' => $schema];
        $this->_locator->config('users', $options);

        $table = $this->_locator->get('users', ['table' => 'users']);
        $this->assertInstanceOf('Cake\ORM\Table', $table);
        $this->assertEquals('users', $table->table());
        $this->assertEquals('users', $table->alias());
        $this->assertSame($connection, $table->connection());
        $this->assertEquals(array_keys($schema), $table->schema()->columns());
        $this->assertEquals($schema['id']['type'], $table->schema()->column('id')['type']);

        $this->_locator->clear();
        $this->assertEmpty($this->_locator->config());

        $this->_locator->config('users', $options);
        $table = $this->_locator->get('users', ['className' => __NAMESPACE__ . '\MyUsersTable']);
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

        $this->_locator->config('users', ['validator' => $validator]);
        $table = $this->_locator->get('users');

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

        $this->_locator->config('users', [
            'validator' => [
                'default' => $validator1,
                'secondary' => $validator2,
                'tertiary' => $validator3,
            ]
        ]);
        $table = $this->_locator->get('users');

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
        $mock = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $this->assertSame($mock, $this->_locator->set('Articles', $mock));
        $this->assertSame($mock, $this->_locator->get('Articles'));
    }

    /**
     * Test setting an instance with plugin syntax aliases
     *
     * @return void
     */
    public function testSetPlugin()
    {
        Plugin::load('TestPlugin');

        $mock = $this->getMockBuilder('TestPlugin\Model\Table\CommentsTable')->getMock();

        $this->assertSame($mock, $this->_locator->set('TestPlugin.Comments', $mock));
        $this->assertSame($mock, $this->_locator->get('TestPlugin.Comments'));
    }

    /**
     * Tests genericInstances
     *
     * @return void
     */
    public function testGenericInstances()
    {
        $foos = $this->_locator->get('Foos');
        $bars = $this->_locator->get('Bars');
        $this->_locator->get('Articles');
        $expected = ['Foos' => $foos, 'Bars' => $bars];
        $this->assertEquals($expected, $this->_locator->genericInstances());
    }

    /**
     * Tests remove an instance
     *
     * @return void
     */
    public function testRemove()
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
     *
     * @return void
     */
    public function testRemovePlugin()
    {
        Plugin::load('TestPlugin');
        Plugin::load('TestPluginTwo');

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
}
