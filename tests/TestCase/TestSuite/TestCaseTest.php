<?php
declare(strict_types=1);

/**
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Router;
use Cake\Test\Fixture\FixturizedTestCase;
use Cake\TestSuite\Fixture\FixtureManager;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\AssertionFailedError;
use TestApp\Model\Table\SecondaryPostsTable;

/**
 * TestCaseTest
 */
class TestCaseTest extends TestCase
{
    /**
     * tests trying to assertEventFired without configuring an event list
     */
    public function testEventFiredMisconfiguredEventList()
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $manager = EventManager::instance();
        $this->assertEventFired('my.event', $manager);
    }

    /**
     * tests trying to assertEventFired without configuring an event list
     */
    public function testEventFiredWithMisconfiguredEventList()
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $manager = EventManager::instance();
        $this->assertEventFiredWith('my.event', 'some', 'data', $manager);
    }

    /**
     * tests assertEventFiredWith
     *
     * @return void
     */
    public function testEventFiredWith()
    {
        $manager = EventManager::instance();
        $manager->setEventList(new EventList());
        $manager->trackEvents(true);

        $event = new Event('my.event', $this, [
            'some' => 'data',
        ]);
        $manager->dispatch($event);
        $this->assertEventFiredWith('my.event', 'some', 'data');

        $manager = new EventManager();
        $manager->setEventList(new EventList());
        $manager->trackEvents(true);

        $event = new Event('my.event', $this, [
            'other' => 'data',
        ]);
        $manager->dispatch($event);
        $this->assertEventFiredWith('my.event', 'other', 'data', $manager);
    }

    /**
     * tests assertEventFired
     *
     * @return void
     */
    public function testEventFired()
    {
        $manager = EventManager::instance();
        $manager->setEventList(new EventList());
        $manager->trackEvents(true);

        $event = new Event('my.event');
        $manager->dispatch($event);
        $this->assertEventFired('my.event');

        $manager = new EventManager();
        $manager->setEventList(new EventList());
        $manager->trackEvents(true);

        $event = new Event('my.event');
        $manager->dispatch($event);
        $this->assertEventFired('my.event', $manager);
    }

    /**
     * testLoadFixturesOnDemand
     *
     * @return void
     */
    public function testLoadFixturesOnDemand()
    {
        $test = new FixturizedTestCase('testFixtureLoadOnDemand');
        $test->autoFixtures = false;
        $manager = $this->getMockBuilder('Cake\TestSuite\Fixture\FixtureManager')->getMock();
        $manager->fixturize($test);
        $test->fixtureManager = $manager;
        $manager->expects($this->once())->method('loadSingle');
        $result = $test->run();

        $this->assertSame(0, $result->errorCount());
    }

    /**
     * tests loadFixtures loads all fixtures on the test
     *
     * @return void
     */
    public function testLoadAllFixtures()
    {
        $test = new FixturizedTestCase('testLoadAllFixtures');
        $test->autoFixtures = false;
        $manager = new FixtureManager();
        $manager->fixturize($test);
        $test->fixtureManager = $manager;

        $result = $test->run();

        $this->assertSame(0, $result->errorCount());
        $this->assertCount(1, $result->passed());
        $this->assertFalse($test->autoFixtures);
    }

    /**
     * testSkipIf
     *
     * @return void
     */
    public function testSkipIf()
    {
        $test = new FixturizedTestCase('testSkipIfTrue');
        $result = $test->run();
        $this->assertSame(1, $result->skippedCount());

        $test = new FixturizedTestCase('testSkipIfFalse');
        $result = $test->run();
        $this->assertSame(0, $result->skippedCount());
    }

    /**
     * test withErrorReporting
     *
     * @return void
     */
    public function testWithErrorReporting()
    {
        $errorLevel = error_reporting();
        $this->withErrorReporting(E_USER_WARNING, function () {
              $this->assertSame(E_USER_WARNING, error_reporting());
        });
        $this->assertSame($errorLevel, error_reporting());
    }

    /**
     * test withErrorReporting with exceptions
     *
     * @return void
     */
    public function testWithErrorReportingWithException()
    {
        $this->expectException(AssertionFailedError::class);

        $errorLevel = error_reporting();
        try {
            $this->withErrorReporting(E_USER_WARNING, function () {
                $this->assertSame(1, 2);
            });
        } finally {
            $this->assertSame($errorLevel, error_reporting());
        }
    }

    /**
     * Test that TestCase::setUp() backs up values.
     *
     * @return void
     */
    public function testSetupBackUpValues()
    {
        $this->assertArrayHasKey('debug', $this->_configure);
    }

    /**
     * test assertTextNotEquals()
     *
     * @return void
     */
    public function testAssertTextNotEquals()
    {
        $one = "\r\nOne\rTwooo";
        $two = "\nOne\nTwo";
        $this->assertTextNotEquals($one, $two);
    }

    /**
     * test assertTextEquals()
     *
     * @return void
     */
    public function testAssertTextEquals()
    {
        $one = "\r\nOne\rTwo";
        $two = "\nOne\nTwo";
        $this->assertTextEquals($one, $two);
    }

    /**
     * test assertTextStartsWith()
     *
     * @return void
     */
    public function testAssertTextStartsWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertStringStartsWith("some\nstring", $stringDirty);
        $this->assertStringStartsNotWith("some\r\nstring\r\nwith", $stringDirty);
        $this->assertStringStartsNotWith("some\nstring\nwith", $stringDirty);

        $this->assertTextStartsWith("some\nstring\nwith", $stringDirty);
        $this->assertTextStartsWith("some\r\nstring\r\nwith", $stringDirty);
    }

    /**
     * test assertTextStartsNotWith()
     *
     * @return void
     */
    public function testAssertTextStartsNotWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertTextStartsNotWith("some\nstring\nwithout", $stringDirty);
    }

    /**
     * test assertTextEndsWith()
     *
     * @return void
     */
    public function testAssertTextEndsWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertTextEndsWith("string\nwith\r\ndifferent\rline endings!", $stringDirty);
        $this->assertTextEndsWith("string\r\nwith\ndifferent\nline endings!", $stringDirty);
    }

    /**
     * test assertTextEndsNotWith()
     *
     * @return void
     */
    public function testAssertTextEndsNotWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertStringEndsNotWith("different\nline endings", $stringDirty);
        $this->assertTextEndsNotWith("different\rline endings", $stringDirty);
    }

    /**
     * test assertTextContains()
     *
     * @return void
     */
    public function testAssertTextContains()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertStringContainsString('different', $stringDirty);
        $this->assertStringNotContainsString("different\rline", $stringDirty);

        $this->assertTextContains("different\rline", $stringDirty);
    }

    /**
     * test assertTextNotContains()
     *
     * @return void
     */
    public function testAssertTextNotContains()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertTextNotContains("different\rlines", $stringDirty);
    }

    /**
     * test testAssertWithinRange()
     *
     * @return void
     */
    public function testAssertWithinRange()
    {
        $this->assertWithinRange(21, 22, 1, 'Not within range');
        $this->assertWithinRange(21.3, 22.2, 1.0, 'Not within range');
    }

    /**
     * test testAssertNotWithinRange()
     *
     * @return void
     */
    public function testAssertNotWithinRange()
    {
        $this->assertNotWithinRange(21, 23, 1, 'Within range');
        $this->assertNotWithinRange(21.3, 22.2, 0.7, 'Within range');
    }

    /**
     * test getMockForModel()
     *
     * @return void
     */
    public function testGetMockForModel()
    {
        static::setAppNamespace();
        // No methods will be mocked if $methods argument of getMockForModel() is empty.
        $Posts = $this->getMockForModel('Posts');
        $entity = new Entity([]);

        $this->assertInstanceOf('TestApp\Model\Table\PostsTable', $Posts);
        $this->assertSame('posts', $Posts->getTable());

        $Posts = $this->getMockForModel('Posts', ['save']);
        $Posts->expects($this->once())
            ->method('save')
            ->will($this->returnValue('mocked'));
        $this->assertSame('mocked', $Posts->save($entity));
        $this->assertSame('Cake\ORM\Entity', $Posts->getEntityClass());
        $this->assertInstanceOf('Cake\Database\Connection', $Posts->getConnection());
        $this->assertSame('test', $Posts->getConnection()->configName());

        $Tags = $this->getMockForModel('Tags', ['save']);
        $this->assertSame('TestApp\Model\Entity\Tag', $Tags->getEntityClass());

        $SluggedPosts = $this->getMockForModel('SluggedPosts', ['slugify']);
        $SluggedPosts->expects($this->once())
            ->method('slugify')
            ->with('some value')
            ->will($this->returnValue('mocked'));
        $this->assertSame('mocked', $SluggedPosts->slugify('some value'));

        $SluggedPosts = $this->getMockForModel('SluggedPosts', ['save', 'slugify']);
        $SluggedPosts->expects($this->once())
            ->method('slugify')
            ->with('some value two')
            ->will($this->returnValue('mocked'));
        $this->assertSame('mocked', $SluggedPosts->slugify('some value two'));
    }

    /**
     * Test getMockForModel on secondary datasources.
     *
     * @return void
     */
    public function testGetMockForModelSecondaryDatasource()
    {
        ConnectionManager::alias('test', 'secondary');

        $post = $this->getMockForModel(SecondaryPostsTable::class, ['save']);
        $this->assertSame('test', $post->getConnection()->configName());
    }

    /**
     * test getMockForModel() with plugin models
     *
     * @return void
     */
    public function testGetMockForModelWithPlugin()
    {
        static::setAppNamespace();
        $this->loadPlugins(['TestPlugin']);
        $TestPluginComment = $this->getMockForModel('TestPlugin.TestPluginComments');

        $result = $this->getTableLocator()->get('TestPlugin.TestPluginComments');
        $this->assertInstanceOf('TestPlugin\Model\Table\TestPluginCommentsTable', $result);
        $this->assertSame($TestPluginComment, $result);

        $TestPluginComment = $this->getMockForModel('TestPlugin.TestPluginComments', ['save']);

        $this->assertInstanceOf('TestPlugin\Model\Table\TestPluginCommentsTable', $TestPluginComment);
        $this->assertSame('Cake\ORM\Entity', $TestPluginComment->getEntityClass());
        $TestPluginComment->expects($this->exactly(2))
            ->method('save')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(true),
                $this->returnValue(false)
            ));

        $entity = new Entity([]);
        $this->assertTrue($TestPluginComment->save($entity));
        $this->assertFalse($TestPluginComment->save($entity));

        $TestPluginAuthors = $this->getMockForModel('TestPlugin.Authors', ['save']);
        $this->assertInstanceOf('TestPlugin\Model\Table\AuthorsTable', $TestPluginAuthors);
        $this->assertSame('TestPlugin\Model\Entity\Author', $TestPluginAuthors->getEntityClass());
        $this->clearPlugins();
    }

    /**
     * testGetMockForModelTable
     *
     * @return void
     */
    public function testGetMockForModelTable()
    {
        $Mock = $this->getMockForModel(
            'Table',
            ['save'],
            ['alias' => 'Comments', 'className' => Table::class]
        );

        $result = $this->getTableLocator()->get('Comments');
        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame('Comments', $Mock->getAlias());

        $Mock->expects($this->exactly(2))
            ->method('save')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(true),
                $this->returnValue(false)
            ));

        $entity = new Entity([]);
        $this->assertTrue($Mock->save($entity));
        $this->assertFalse($Mock->save($entity));

        $allMethodsStubs = $this->getMockForModel(
            'Table',
            [],
            ['alias' => 'Comments', 'className' => Table::class]
        );
        $result = $this->getTableLocator()->get('Comments');
        $this->assertInstanceOf(Table::class, $result);
        $this->assertEmpty([], $allMethodsStubs->getAlias());
    }

    /**
     * Test getting a table mock that doesn't have a preset table name sets the proper name
     *
     * @return void
     */
    public function testGetMockForModelSetTable()
    {
        static::setAppNamespace();

        $I18n = $this->getMockForModel('CustomI18n', ['save']);
        $this->assertSame('custom_i18n_table', $I18n->getTable());

        $Tags = $this->getMockForModel('Tags', ['save']);
        $this->assertSame('tags', $Tags->getTable());
    }

    /**
     * Test loadRoutes() helper
     *
     * @return void
     */
    public function testLoadRoutes()
    {
        $url = ['controller' => 'Articles', 'action' => 'index'];
        try {
            Router::url($url);
            $this->fail('Missing URL should throw an exception');
        } catch (MissingRouteException $e) {
        }
        Configure::write('App.namespace', 'TestApp');
        $this->loadRoutes();

        $result = Router::url($url);
        $this->assertSame('/app/articles', $result);
    }
}
