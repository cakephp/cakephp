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
     * Test loading app fixtures.
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
     * Test loading app fixtures.
     *
     * @return void
     */
    public function testFixturizeCustom()
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
     * Test that unknown types are handled gracefully.
     *
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Referenced fixture class "Test\Fixture\Derp.derpFixture" not found. Fixture "derp.derp" was referenced
     */
    public function testFixturizeInvalidType()
    {
        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->fixtures = ['derp.derp'];
        $this->manager->fixturize($test);
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
