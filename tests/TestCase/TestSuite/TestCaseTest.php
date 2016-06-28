<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Test\Fixture\AssertHtmlTestCase;
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
 *
 */
class TestCaseTest extends TestCase
{

    /**
     * tests trying to assertEventFired without configuring an event list
     *
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testEventFiredMisconfiguredEventList()
    {
        $manager = EventManager::instance();
        $this->assertEventFired('my.event', $manager);
    }

    /**
     * tests trying to assertEventFired without configuring an event list
     *
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testEventFiredWithMisconfiguredEventList()
    {
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
     * testAssertHtml
     *
     * @return void
     */
    public function testAssertHtmlBasic()
    {
        $test = new AssertHtmlTestCase('testAssertHtmlQuotes');
        $result = $test->run();
        ob_start();
        $this->assertEquals(0, $result->errorCount());
        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(0, $result->failureCount());
    }

    /**
     * test assertHtml works with single and double quotes
     *
     * @return void
     */
    public function testAssertHtmlQuoting()
    {
        $input = '<a href="/test.html" class="active">My link</a>';
        $pattern = [
            'a' => ['href' => '/test.html', 'class' => 'active'],
            'My link',
            '/a'
        ];
        $this->assertHtml($pattern, $input);

        $input = "<a href='/test.html' class='active'>My link</a>";
        $pattern = [
            'a' => ['href' => '/test.html', 'class' => 'active'],
            'My link',
            '/a'
        ];
        $this->assertHtml($pattern, $input);

        $input = "<a href='/test.html' class='active'>My link</a>";
        $pattern = [
            'a' => ['href' => 'preg:/.*\.html/', 'class' => 'active'],
            'My link',
            '/a'
        ];
        $this->assertHtml($pattern, $input);

        $input = "<span><strong>Text</strong></span>";
        $pattern = [
            '<span',
            '<strong',
            'Text',
            '/strong',
            '/span'
        ];
        $this->assertHtml($pattern, $input);

        $input = "<span class='active'><strong>Text</strong></span>";
        $pattern = [
            'span' => ['class'],
            '<strong',
            'Text',
            '/strong',
            '/span'
        ];
        $this->assertHtml($pattern, $input);
    }

    /**
     * Test that assertHtml runs quickly.
     *
     * @return void
     */
    public function testAssertHtmlRuntimeComplexity()
    {
        $pattern = [
            'div' => [
                'attr1' => 'val1',
                'attr2' => 'val2',
                'attr3' => 'val3',
                'attr4' => 'val4',
                'attr5' => 'val5',
                'attr6' => 'val6',
                'attr7' => 'val7',
                'attr8' => 'val8',
            ],
            'My div',
            '/div'
        ];
        $input = '<div attr8="val8" attr6="val6" attr4="val4" attr2="val2"' .
            ' attr1="val1" attr3="val3" attr5="val5" attr7="val7" />' .
            'My div' .
            '</div>';
        $this->assertHtml($pattern, $input);
    }

    /**
     * testNumericValuesInExpectationForAssertHtml
     *
     * @return void
     */
    public function testNumericValuesInExpectationForAssertHtml()
    {
        $test = new AssertHtmlTestCase('testNumericValuesInExpectationForAssertHtml');
        $result = $test->run();
        ob_start();
        $this->assertEquals(0, $result->errorCount());
        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(0, $result->failureCount());
    }

    /**
     * testBadAssertHtml
     *
     * @return void
     */
    public function testBadAssertHtml()
    {
        $test = new AssertHtmlTestCase('testBadAssertHtml');
        $result = $test->run();
        ob_start();
        $this->assertEquals(0, $result->errorCount());
        $this->assertFalse($result->wasSuccessful());
        $this->assertEquals(1, $result->failureCount());

        $test = new AssertHtmlTestCase('testBadAssertHtml2');
        $result = $test->run();
        ob_start();
        $this->assertEquals(0, $result->errorCount());
        $this->assertFalse($result->wasSuccessful());
        $this->assertEquals(1, $result->failureCount());
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
        ob_start();

        $this->assertEquals(0, $result->errorCount());
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
        ob_start();
        $this->assertEquals(1, $result->skippedCount());

        $test = new FixturizedTestCase('testSkipIfFalse');
        $result = $test->run();
        ob_start();
        $this->assertEquals(0, $result->skippedCount());
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

        $this->assertContains("different", $stringDirty);
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
        Configure::write('App.namespace', 'TestApp');
        $Posts = $this->getMockForModel('Posts');
        $entity = new \Cake\ORM\Entity([]);

        $this->assertInstanceOf('TestApp\Model\Table\PostsTable', $Posts);
        $this->assertNull($Posts->save($entity));
        $this->assertNull($Posts->table());

        $Posts = $this->getMockForModel('Posts', ['save']);
        $Posts->expects($this->at(0))
            ->method('save')
            ->will($this->returnValue('mocked'));
        $this->assertEquals('mocked', $Posts->save($entity));
        $this->assertEquals('\Cake\ORM\Entity', $Posts->entityClass());

        $Posts = $this->getMockForModel('Posts', ['doSomething']);
        $this->assertInstanceOf('Cake\Database\Connection', $Posts->connection());
        $this->assertEquals('test', $Posts->connection()->configName());

        $Tags = $this->getMockForModel('Tags', ['doSomething']);
        $this->assertEquals('TestApp\Model\Entity\Tag', $Tags->entityClass());
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
        $this->assertEquals('test', $post->connection()->configName());
    }

    /**
     * test getMockForModel() with plugin models
     *
     * @return void
     */
    public function testGetMockForModelWithPlugin()
    {
        Configure::write('App.namespace', 'TestApp');
        Plugin::load('TestPlugin');
        $TestPluginComment = $this->getMockForModel('TestPlugin.TestPluginComments');

        $result = TableRegistry::get('TestPlugin.TestPluginComments');
        $this->assertInstanceOf('TestPlugin\Model\Table\TestPluginCommentsTable', $result);

        $TestPluginComment = $this->getMockForModel('TestPlugin.TestPluginComments', ['save']);

        $this->assertInstanceOf('TestPlugin\Model\Table\TestPluginCommentsTable', $TestPluginComment);
        $this->assertEquals('\Cake\ORM\Entity', $TestPluginComment->entityClass());
        $TestPluginComment->expects($this->at(0))
            ->method('save')
            ->will($this->returnValue(true));
        $TestPluginComment->expects($this->at(1))
            ->method('save')
            ->will($this->returnValue(false));

        $entity = new \Cake\ORM\Entity([]);
        $this->assertTrue($TestPluginComment->save($entity));
        $this->assertFalse($TestPluginComment->save($entity));

        $TestPluginAuthors = $this->getMockForModel('TestPlugin.Authors', ['doSomething']);
        $this->assertInstanceOf('TestPlugin\Model\Table\AuthorsTable', $TestPluginAuthors);
        $this->assertEquals('TestPlugin\Model\Entity\Author', $TestPluginAuthors->entityClass());
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

        $result = TableRegistry::get('Comments');
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertEquals('Comments', $Mock->alias());

        $Mock->expects($this->at(0))
            ->method('save')
            ->will($this->returnValue(true));
        $Mock->expects($this->at(1))
            ->method('save')
            ->will($this->returnValue(false));

        $entity = new \Cake\ORM\Entity([]);
        $this->assertTrue($Mock->save($entity));
        $this->assertFalse($Mock->save($entity));
    }
}
