<?php
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
namespace Cake\Test\TestSuite;

use Cake\Core\Plugin;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\Fixture\FixtureManager;
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;

/**
 * Fixture manager test case.
 */
class FixtureManagerTest extends TestCase
{

    /**
     * Setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->manager = new FixtureManager();
    }

    public function tearDown()
    {
        parent::tearDown();
        Log::reset();
    }

    /**
     * Test loading core fixtures.
     *
     * @return void
     */
    public function testFixturizeCore()
    {
        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->fixtures = ['core.articles'];
        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('core.articles', $fixtures);
        $this->assertInstanceOf('Cake\Test\Fixture\ArticlesFixture', $fixtures['core.articles']);
    }

    /**
     * Test logging depends on fixture manager debug.
     *
     * @return void
     */
    public function testLogSchemaWithDebug()
    {
        $db = ConnectionManager::get('test');
        $restore = $db->logQueries();
        $db->logQueries(true);

        $this->manager->setDebug(true);
        $buffer = new ConsoleOutput();
        Log::config('testQueryLogger', [
            'className' => 'Console',
            'stream' => $buffer
        ]);

        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->fixtures = ['core.articles'];
        $this->manager->fixturize($test);
        // Need to load/shutdown twice to ensure fixture is created.
        $this->manager->load($test);
        $this->manager->shutdown();
        $this->manager->load($test);
        $this->manager->shutdown();

        $db->logQueries($restore);
        $this->assertContains('CREATE TABLE', implode('', $buffer->messages()));
    }

    /**
     * Test that if a table already exists in the test database, it will dropped
     * before being recreated
     *
     * @return void
     */
    public function testResetDbIfTableExists()
    {
        $db = ConnectionManager::get('test');
        $restore = $db->logQueries();
        $db->logQueries(true);

        $this->manager->setDebug(true);
        $buffer = new ConsoleOutput();
        Log::config('testQueryLogger', [
            'className' => 'Console',
            'stream' => $buffer
        ]);

        $table = new Table('articles', [
            'id' => ['type' => 'integer', 'unsigned' => true],
            'title' => ['type' => 'string', 'length' => 255],
        ]);
        $table->addConstraint('primary', ['type' => 'primary', 'columns' => ['id']]);
        $sql = $table->createSql($db);
        foreach ($sql as $stmt) {
            $db->execute($stmt);
        }

        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->fixtures = ['core.articles'];
        $this->manager->fixturize($test);
        $this->manager->load($test);

        $db->logQueries($restore);
        $this->assertContains('DROP TABLE', implode('', $buffer->messages()));
    }

    /**
     * Test loading fixtures with constraints.
     *
     * @return void
     */
    public function testFixturizeCoreConstraint()
    {
        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->fixtures = ['core.articles', 'core.articles_tags', 'core.tags'];
        $this->manager->fixturize($test);
        $this->manager->load($test);

        $table = TableRegistry::get('ArticlesTags');
        $schema = $table->schema();
        $expectedConstraint = [
            'type' => 'foreign',
            'columns' => [
                'tag_id'
            ],
            'references' => [
                'tags',
                'id'
            ],
            'update' => 'cascade',
            'delete' => 'cascade',
            'length' => []
        ];
        $this->assertEquals($expectedConstraint, $schema->constraint('tag_id_fk'));
        $this->manager->unload($test);

        $this->manager->load($test);
        $table = TableRegistry::get('ArticlesTags');
        $schema = $table->schema();
        $expectedConstraint = [
            'type' => 'foreign',
            'columns' => [
                'tag_id'
            ],
            'references' => [
                'tags',
                'id'
            ],
            'update' => 'cascade',
            'delete' => 'cascade',
            'length' => []
        ];
        $this->assertEquals($expectedConstraint, $schema->constraint('tag_id_fk'));

        $this->manager->unload($test);
    }

    /**
     * Test loading plugin fixtures.
     *
     * @return void
     */
    public function testFixturizePlugin()
    {
        Plugin::load('TestPlugin');

        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->fixtures = ['plugin.test_plugin.articles'];
        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('plugin.test_plugin.articles', $fixtures);
        $this->assertInstanceOf(
            'TestPlugin\Test\Fixture\ArticlesFixture',
            $fixtures['plugin.test_plugin.articles']
        );
    }

    /**
     * Test loading plugin fixtures.
     *
     * @return void
     */
    public function testFixturizePluginSubdirectory()
    {
        Plugin::load('TestPlugin');

        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->fixtures = ['plugin.test_plugin.blog/comments'];
        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('plugin.test_plugin.blog/comments', $fixtures);
        $this->assertInstanceOf(
            'TestPlugin\Test\Fixture\Blog\CommentsFixture',
            $fixtures['plugin.test_plugin.blog/comments']
        );
    }

    /**
     * Test loading plugin fixtures from a vendor namespaced plugin
     *
     * @return void
     */
    public function testFixturizeVendorPlugin()
    {
        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->fixtures = ['plugin.Company/TestPluginThree.articles'];
        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('plugin.Company/TestPluginThree.articles', $fixtures);
        $this->assertInstanceOf(
            'Company\TestPluginThree\Test\Fixture\ArticlesFixture',
            $fixtures['plugin.Company/TestPluginThree.articles']
        );
    }

    /**
     * Test loading fixtures with fully-qualified namespaces.
     *
     * @return void
     */
    public function testFixturizeClassName()
    {
        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->fixtures = ['Company\TestPluginThree\Test\Fixture\ArticlesFixture'];
        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('Company\TestPluginThree\Test\Fixture\ArticlesFixture', $fixtures);
        $this->assertInstanceOf(
            'Company\TestPluginThree\Test\Fixture\ArticlesFixture',
            $fixtures['Company\TestPluginThree\Test\Fixture\ArticlesFixture']
        );
    }

    /**
     * Test that unknown types are handled gracefully.
     *
     */
    public function testFixturizeInvalidType()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Referenced fixture class "Test\Fixture\Derp.derpFixture" not found. Fixture "derp.derp" was referenced');
        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->fixtures = ['derp.derp'];
        $this->manager->fixturize($test);
    }

    /**
     * Test load uses aliased connections via a mock.
     *
     * Ensure that FixtureManager uses connection aliases
     * protecting 'live' tables from being wiped by mistakes in
     * fixture connection names.
     *
     * @return void
     */
    public function testLoadConnectionAliasUsage()
    {
        $connection = ConnectionManager::get('test');
        $statement = $this->getMockBuilder('Cake\Database\StatementInterface')
            ->getMock();

        // This connection should _not_ be used.
        $other = $this->getMockBuilder('Cake\Database\Connection')
            ->setMethods(['execute'])
            ->setConstructorArgs([['driver' => $connection->getDriver()]])
            ->getMock();
        $other->expects($this->never())
            ->method('execute')
            ->will($this->returnValue($statement));

        // This connection should be used instead of
        // the 'other' connection as the alias should not be ignored.
        $testOther = $this->getMockBuilder('Cake\Database\Connection')
            ->setMethods(['execute'])
            ->setConstructorArgs([[
                'database' => $connection->config()['database'],
                'driver' => $connection->getDriver()
            ]])
            ->getMock();
        $testOther->expects($this->atLeastOnce())
            ->method('execute')
            ->will($this->returnValue($statement));

        ConnectionManager::config('other', $other);
        ConnectionManager::config('test_other', $testOther);

        // Connect the alias making test_other an alias of other.
        ConnectionManager::alias('test_other', 'other');

        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->fixtures = ['core.other_articles'];
        $this->manager->fixturize($test);
        $this->manager->load($test);

        ConnectionManager::drop('other');
        ConnectionManager::drop('test_other');
    }

    /**
     * Test loading fixtures using loadSingle()
     *
     * @return void
     */
    public function testLoadSingle()
    {
        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->autoFixtures = false;
        $test->fixtures = ['core.articles', 'core.articles_tags', 'core.tags'];
        $this->manager->fixturize($test);
        $this->manager->loadSingle('Articles');
        $this->manager->loadSingle('Tags');
        $this->manager->loadSingle('ArticlesTags');

        $table = TableRegistry::get('ArticlesTags');
        $results = $table->find('all')->toArray();
        $schema = $table->schema();
        $expectedConstraint = [
            'type' => 'foreign',
            'columns' => [
                'tag_id'
            ],
            'references' => [
                'tags',
                'id'
            ],
            'update' => 'cascade',
            'delete' => 'cascade',
            'length' => []
        ];
        $this->assertEquals($expectedConstraint, $schema->constraint('tag_id_fk'));
        $this->assertCount(4, $results);

        $this->manager->unload($test);

        $this->manager->loadSingle('Articles');
        $this->manager->loadSingle('Tags');
        $this->manager->loadSingle('ArticlesTags');

        $table = TableRegistry::get('ArticlesTags');
        $results = $table->find('all')->toArray();
        $schema = $table->schema();
        $expectedConstraint = [
            'type' => 'foreign',
            'columns' => [
                'tag_id'
            ],
            'references' => [
                'tags',
                'id'
            ],
            'update' => 'cascade',
            'delete' => 'cascade',
            'length' => []
        ];
        $this->assertEquals($expectedConstraint, $schema->constraint('tag_id_fk'));
        $this->assertCount(4, $results);
        $this->manager->unload($test);
    }
}
