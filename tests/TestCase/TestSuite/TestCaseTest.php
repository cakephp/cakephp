<?php
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

use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\Fixture\FixtureManager;
use Cake\TestSuite\TestCase;
use Cake\Test\Fixture\FixturizedTestCase;

/**
 * Testing stub.
 */
class SecondaryPostsTable extends Table
{

    /**
     * @return string
     */
    public static function defaultConnectionName()
    {
        return 'secondary';
    }
}

/**
 * TestCaseTest
 */
class TestCaseTest extends TestCase
{

    /**
     * tests trying to assertEventFired without configuring an event list
     *
     */
    public function testEventFiredMisconfiguredEventList()
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $manager = EventManager::instance();
        $this->assertEventFired('my.event', $manager);
    }

    /**
     * tests trying to assertEventFired without configuring an event list
     *
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
            'some' => 'data'
        ]);
        $manager->dispatch($event);
        $this->assertEventFiredWith('my.event', 'some', 'data');

        $manager = new EventManager();
        $manager->setEventList(new EventList());
        $manager->trackEvents(true);

        $event = new Event('my.event', $this, [
            'other' => 'data'
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

        $this->assertEquals(0, $result->errorCount());
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

        $this->assertEquals(0, $result->errorCount());
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
        $this->assertEquals(1, $result->skippedCount());

        $test = new FixturizedTestCase('testSkipIfFalse');
        $result = $test->run();
        $this->assertEquals(0, $result->skippedCount());
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
     * @expectedException \PHPUnit\Framework\AssertionFailedError
     * @return void
     */
    public function testWithErrorReportingWithException()
    {
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
     * testDeprecated
     *
     * @return void
     */
    public function testDeprecated()
    {
        $value = 'custom';
        $setter = 'setLayout';
        $getter = 'getLayout';
        $property = 'layout';
        $controller = new \Cake\Controller\Controller();
        $controller->viewBuilder()->{$setter}($value);
        $this->deprecated(function () use ($value, $getter, $controller, $property) {
              $this->assertSame($value, $controller->$property);
              $this->assertSame($value, $controller->viewBuilder()->{$getter}());
        });
    }

    /**
     * testDeprecated
     *
     * @expectedException \PHPUnit\Framework\AssertionFailedError
     * @return void
     */
    public function testDeprecatedWithException()
    {
        $value = 'custom';
        $setter = 'setLayout';
        $getter = 'getLayout';
        $property = 'layout';
        $controller = new \Cake\Controller\Controller();
        $controller->viewBuilder()->{$setter}($value);
        $this->deprecated(function () use ($value, $getter, $controller, $property) {
              $this->assertSame($value, $controller->$property);
              $this->assertSame('Derp', $controller->viewBuilder()->{$getter}());
        });
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
        $stringClean = "some\nstring\nwith\ndifferent\nline endings!";

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
        $stringClean = "some\nstring\nwith\ndifferent\nline endings!";

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
        $stringClean = "some\nstring\nwith\ndifferent\nline endings!";

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
        $stringClean = "some\nstring\nwith\ndifferent\nline endings!";

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
        $stringClean = "some\nstring\nwith\ndifferent\nline endings!";

        $this->assertContains('different', $stringDirty);
        $this->assertNotContains("different\rline", $stringDirty);

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
        $stringClean = "some\nstring\nwith\ndifferent\nline endings!";

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
        $Posts = $this->getMockForModel('Posts');
        $entity = new Entity([]);

        $this->assertInstanceOf('TestApp\Model\Table\PostsTable', $Posts);
        $this->assertNull($Posts->save($entity));
        $this->assertNull($Posts->getTable());

        $Posts = $this->getMockForModel('Posts', ['save']);
        $Posts->expects($this->at(0))
            ->method('save')
            ->will($this->returnValue('mocked'));
        $this->assertEquals('mocked', $Posts->save($entity));
        $this->assertEquals('Cake\ORM\Entity', $Posts->getEntityClass());

        $Posts = $this->getMockForModel('Posts', ['doSomething']);
        $this->assertInstanceOf('Cake\Database\Connection', $Posts->getConnection());
        $this->assertEquals('test', $Posts->getConnection()->configName());

        $Tags = $this->getMockForModel('Tags', ['doSomething']);
        $this->assertEquals('TestApp\Model\Entity\Tag', $Tags->getEntityClass());
    }

    /**
     * Test getMockForModel on secondary datasources.
     *
     * @return void
     */
    public function testGetMockForModelSecondaryDatasource()
    {
        ConnectionManager::alias('test', 'secondary');

        $post = $this->getMockForModel(__NAMESPACE__ . '\SecondaryPostsTable', ['save']);
        $this->assertEquals('test', $post->getConnection()->configName());
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
        $this->assertEquals('Cake\ORM\Entity', $TestPluginComment->getEntityClass());
        $TestPluginComment->expects($this->at(0))
            ->method('save')
            ->will($this->returnValue(true));
        $TestPluginComment->expects($this->at(1))
            ->method('save')
            ->will($this->returnValue(false));

        $entity = new Entity([]);
        $this->assertTrue($TestPluginComment->save($entity));
        $this->assertFalse($TestPluginComment->save($entity));

        $TestPluginAuthors = $this->getMockForModel('TestPlugin.Authors', ['doSomething']);
        $this->assertInstanceOf('TestPlugin\Model\Table\AuthorsTable', $TestPluginAuthors);
        $this->assertEquals('TestPlugin\Model\Entity\Author', $TestPluginAuthors->getEntityClass());
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
            ['alias' => 'Comments', 'className' => '\Cake\ORM\Table']
        );

        $result = $this->getTableLocator()->get('Comments');
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('Comments', $Mock->getAlias());

        $Mock->expects($this->at(0))
            ->method('save')
            ->will($this->returnValue(true));
        $Mock->expects($this->at(1))
            ->method('save')
            ->will($this->returnValue(false));

        $entity = new Entity([]);
        $this->assertTrue($Mock->save($entity));
        $this->assertFalse($Mock->save($entity));

        $allMethodsStubs = $this->getMockForModel(
            'Table',
            [],
            ['alias' => 'Comments', 'className' => '\Cake\ORM\Table']
        );
        $result = $this->getTableLocator()->get('Comments');
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEmpty([], $allMethodsStubs->getAlias());

        $allMethodsMocks = $this->getMockForModel(
            'Table',
            null,
            ['alias' => 'Comments', 'className' => '\Cake\ORM\Table']
        );
        $result = $this->getTableLocator()->get('Comments');
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('Comments', $allMethodsMocks->getAlias());

        $this->assertNotEquals($allMethodsStubs, $allMethodsMocks);
    }

    /**
     * Test getting a table mock that doesn't have a preset table name sets the proper name
     *
     * @return void
     */
    public function testGetMockForModelSetTable()
    {
        static::setAppNamespace();

        $I18n = $this->getMockForModel('I18n', ['doSomething']);
        $this->assertEquals('custom_i18n_table', $I18n->getTable());

        $Tags = $this->getMockForModel('Tags', ['doSomething']);
        $this->assertEquals('tags', $Tags->getTable());
    }
}
