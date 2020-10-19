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
namespace Cake\Test\TestCase\TestSuite;

use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\TestSuite\Fixture\FixtureManager;
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;
use PDOException;
use RuntimeException;

/**
 * Fixture manager test case.
 */
class FixtureManagerTest extends TestCase
{
    /**
     * @var \Cake\TestSuite\Fixture\FixtureManager
     */
    protected $manager;

    /**
     * Setup method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->manager = new FixtureManager();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->manager->shutDown();
        Log::reset();
        $this->clearPlugins();
    }

    /**
     * Test loading core fixtures.
     *
     * @return void
     */
    public function testFixturizeCore()
    {
        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(['core.Articles']);

        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('Articles', $fixtures);
        $this->assertInstanceOf('Cake\Test\Fixture\ArticlesFixture', $fixtures['Articles']);
    }

    /**
     * Test logging depends on fixture manager debug.
     *
     * @return void
     */
    public function testLogSchemaWithDebug()
    {
        $db = ConnectionManager::get('test');
        $restore = $db->isQueryLoggingEnabled();
        $db->enableQueryLogging(true);

        $buffer = new ConsoleOutput();
        Log::setConfig('testQueryLogger', [
            'className' => 'Console',
            'stream' => $buffer,
        ]);

        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(['core.Articles']);

        $this->manager->fixturize($test);
        $this->manager->setDebug(true);
        $this->manager->load($test);

        $db->enableQueryLogging($restore);
        $this->assertStringContainsString('CREATE TABLE', implode('', $buffer->messages()));
    }

    /**
     * Tests that tables are dropped on load if they exist.
     *
     * @return void
     */
    public function testDropTablesOnLoad()
    {
        $db = ConnectionManager::get('test');
        $restore = $db->isQueryLoggingEnabled();
        $db->enableQueryLogging(true);

        $buffer = new ConsoleOutput();
        Log::setConfig('testQueryLogger', [
            'className' => 'Console',
            'stream' => $buffer,
        ]);

        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(['core.Articles']);

        $this->manager->fixturize($test);
        $this->manager->load($test);
        $this->manager->setDebug(true);
        $this->manager->load($test);
        $this->manager->setDebug(false);
        $this->manager->shutDown();

        $db->enableQueryLogging($restore);
        $this->assertStringContainsString('DROP TABLE', implode('', $buffer->messages()));
    }

    /**
     * Test loading fixtures with constraints.
     *
     * @return void
     */
    public function testFixturizeCoreConstraint()
    {
        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(['core.Articles', 'core.ArticlesTags', 'core.Tags']);

        $this->manager->fixturize($test);
        $this->manager->load($test);

        $table = $this->getTableLocator()->get('ArticlesTags');
        $schema = $table->getSchema();
        $expectedConstraint = [
            'type' => 'foreign',
            'columns' => [
                'tag_id',
            ],
            'references' => [
                'tags',
                'id',
            ],
            'update' => 'cascade',
            'delete' => 'cascade',
            'length' => [],
        ];
        $this->assertSame($expectedConstraint, $schema->getConstraint('tag_id_fk'));
    }

    /**
     * Test loading plugin fixtures.
     *
     * @return void
     */
    public function testFixturizePlugin()
    {
        $this->loadPlugins(['TestPlugin']);

        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(['plugin.TestPlugin.Articles']);

        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('Articles', $fixtures);
        $this->assertInstanceOf(
            'TestPlugin\Test\Fixture\ArticlesFixture',
            $fixtures['Articles']
        );
    }

    /**
     * Test loading plugin fixtures.
     *
     * @return void
     */
    public function testFixturizePluginSubdirectory()
    {
        $this->loadPlugins(['TestPlugin']);

        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(['plugin.TestPlugin.Blog/Comments']);

        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('Blog\Comments', $fixtures);
        $this->assertInstanceOf(
            'TestPlugin\Test\Fixture\Blog\CommentsFixture',
            $fixtures['Blog\Comments']
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
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(['plugin.Company/TestPluginThree.Articles']);

        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('Articles', $fixtures);
        $this->assertInstanceOf(
            'Company\TestPluginThree\Test\Fixture\ArticlesFixture',
            $fixtures['Articles']
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
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(['Company\TestPluginThree\Test\Fixture\ArticlesFixture']);

        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('Articles', $fixtures);
        $this->assertInstanceOf(
            'Company\TestPluginThree\Test\Fixture\ArticlesFixture',
            $fixtures['Articles']
        );
    }

    /**
     * Test that unknown types are handled gracefully.
     */
    public function testFixturizeInvalidType()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches(
            '/Could not init fixture `Derp.Derp` in test case `Mock_TestCase_\w+`. ' .
            'Class `Test\\\Fixture\\\Derp.DerpFixture` not found./'
        );
        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(['Derp.Derp']);
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
            ->onlyMethods(['execute'])
            ->setConstructorArgs([['driver' => $connection->getDriver()]])
            ->getMock();
        $other->expects($this->never())
            ->method('execute')
            ->will($this->returnValue($statement));

        // This connection should be used instead of
        // the 'other' connection as the alias should not be ignored.
        $testOther = $this->getMockBuilder('Cake\Database\Connection')
            ->onlyMethods(['execute'])
            ->setConstructorArgs([[
                'database' => $connection->config()['database'],
                'driver' => $connection->getDriver(),
            ]])
            ->getMock();
        $testOther->expects($this->atLeastOnce())
            ->method('execute')
            ->will($this->returnValue($statement));

        ConnectionManager::setConfig('other', $other);
        ConnectionManager::setConfig('test_other', $testOther);

        // Connect the alias making test_other an alias of other.
        ConnectionManager::alias('test_other', 'other');

        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(['core.OtherArticles']);

        $this->manager->fixturize($test);
        $this->manager->load($test);
        $this->manager->shutDown();

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
        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')
            ->onlyMethods(['getFixtures'])
            ->getMock();
        $test->autoFixtures = false;
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(['core.Articles', 'core.Tags']);
        $this->manager->fixturize($test);
        $this->manager->loadSingle('Articles');
        $this->manager->loadSingle('Tags');

        $table = $this->getTableLocator()->get('Articles');
        $results = $table->find('all')->toArray();
        $schema = $table->getSchema();
        $expectedConstraint = [
            'type' => 'primary',
            'columns' => [
                'id',
            ],
            'length' => [],
        ];
        $this->assertSame($expectedConstraint, $schema->getConstraint('primary'));
        $this->assertCount(3, $results);
    }

    /**
     * Test warning creating new fixtures.
     *
     * @dataProvider createFixtureErrorProvider
     * @return void
     */
    public function testWarningCreatingNewFixtures($method, $expectedMessage)
    {
        $fixture = $this->getMockBuilder('Cake\Test\Fixture\ProductsFixture')
            ->onlyMethods([$method])
            ->getMock();
        $fixture->expects($this->once())
            ->method($method)
            ->will($this->throwException(new PDOException('error')));

        $fixtures = [
            'core.Products' => $fixture,
        ];

        $test = $this->getMockBuilder('Cake\TestSuite\TestCase')->getMock();
        $test->expects($this->any())
            ->method('getFixtures')
            ->willReturn(array_keys($fixtures));

        /** @var \Cake\TestSuite\Fixture\FixtureManager|\PHPUnit\Framework\MockObject\MockObject $manager */
        $manager = $this->getMockBuilder(FixtureManager::class)
            ->onlyMethods(['groupByConnection'])
            ->getMock();
        $manager->expects($this->any())
            ->method('groupByConnection')
            ->will($this->returnValue([
                'test' => $fixtures,
            ]));

        $manager->fixturize($test);

        $this->expectWarning();
        $this->expectWarningMessageMatches($expectedMessage);

        try {
            $manager->load($test);
        } finally {
            $manager->shutDown();
        }
    }

    /**
     * Data provider for testWarningCreatingNewFixtures
     *
     * @return array
     */
    public function createFixtureErrorProvider()
    {
        return [
            [
                'create',
                '/^Unable to create fixture `Mock_ProductsFixture_\w+` in test case `Mock_TestCase_\w+`/',
            ],
            [
                'createConstraints',
                '/^Unable to create constraints for fixture `Mock_ProductsFixture_\w+` in test case `Mock_TestCase_\w+`/',
            ],
            [
                'insert',
                '/^Unable to insert records for fixture `Mock_ProductsFixture_\w+` in test case `Mock_TestCase_\w+`/',
            ],
        ];
    }
}
