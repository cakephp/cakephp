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
    public function testEventFiredMisconfiguredEventList(): void
    {
        $this->expectException(AssertionFailedError::class);
        $manager = EventManager::instance();
        $this->assertEventFired('my.event', $manager);
    }

    /**
     * tests trying to assertEventFired without configuring an event list
     */
    public function testEventFiredWithMisconfiguredEventList(): void
    {
        $this->expectException(AssertionFailedError::class);
        $manager = EventManager::instance();
        $this->assertEventFiredWith('my.event', 'some', 'data', $manager);
    }

    /**
     * tests assertEventFiredWith
     */
    public function testEventFiredWith(): void
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
     */
    public function testEventFired(): void
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
     * testSkipIf
     */
    public function testSkipIf(): void
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
     */
    public function testWithErrorReporting(): void
    {
        $errorLevel = error_reporting();
        $this->withErrorReporting(E_USER_WARNING, function (): void {
              $this->assertSame(E_USER_WARNING, error_reporting());
        });
        $this->assertSame($errorLevel, error_reporting());
    }

    /**
     * test withErrorReporting with exceptions
     */
    public function testWithErrorReportingWithException(): void
    {
        $this->expectException(AssertionFailedError::class);

        $errorLevel = error_reporting();
        try {
            $this->withErrorReporting(E_USER_WARNING, function (): void {
                $this->assertSame(1, 2);
            });
        } finally {
            $this->assertSame($errorLevel, error_reporting());
        }
    }

    /**
     * test deprecated
     */
    public function testDeprecated(): void
    {
        $this->deprecated(function () {
            trigger_error('deprecation message', E_USER_DEPRECATED);
        });
    }

    /**
     * test deprecated with assert after trigger warning
     */
    public function testDeprecatedWithAssertAfterTriggerWarning(): void
    {
        try {
            $this->deprecated(function () {
                trigger_error('deprecation message', E_USER_DEPRECATED);
                $this->assertTrue(false, 'A random message');
            });

            $this->fail();
        } catch (\Exception $e) {
            $this->assertStringContainsString('A random message', $e->getMessage());
        }
    }

    /**
     * test deprecated
     */
    public function testDeprecatedWithNoDeprecation(): void
    {
        try {
            $this->deprecated(function () {
            });

            $this->fail();
        } catch (\Exception $e) {
            $this->assertStringStartsWith('Should have at least one deprecation warning', $e->getMessage());
        }
    }

    /**
     * test deprecated() with duplicate deprecation with same messsage and line
     */
    public function testDeprecatedWithDuplicatedDeprecation(): void
    {
        /**
         * setting stackframe = 0 and having same method
         * to have same deprecation message and same line for all cases
         */
        $fun = function () {
            deprecationWarning('Test same deprecation message', 0);
        };
        $this->deprecated(function () use ($fun) {
            $fun();
        });
        $this->deprecated(function () use ($fun) {
            $fun();
        });
    }

    /**
     * Test that TestCase::setUp() backs up values.
     */
    public function testSetupBackUpValues(): void
    {
        $this->assertArrayHasKey('debug', $this->_configure);
    }

    /**
     * test assertTextNotEquals()
     */
    public function testAssertTextNotEquals(): void
    {
        $one = "\r\nOne\rTwooo";
        $two = "\nOne\nTwo";
        $this->assertTextNotEquals($one, $two);
    }

    /**
     * test assertTextEquals()
     */
    public function testAssertTextEquals(): void
    {
        $one = "\r\nOne\rTwo";
        $two = "\nOne\nTwo";
        $this->assertTextEquals($one, $two);
    }

    /**
     * test assertTextStartsWith()
     */
    public function testAssertTextStartsWith(): void
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
     */
    public function testAssertTextStartsNotWith(): void
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertTextStartsNotWith("some\nstring\nwithout", $stringDirty);
    }

    /**
     * test assertTextEndsWith()
     */
    public function testAssertTextEndsWith(): void
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertTextEndsWith("string\nwith\r\ndifferent\rline endings!", $stringDirty);
        $this->assertTextEndsWith("string\r\nwith\ndifferent\nline endings!", $stringDirty);
    }

    /**
     * test assertTextEndsNotWith()
     */
    public function testAssertTextEndsNotWith(): void
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertStringEndsNotWith("different\nline endings", $stringDirty);
        $this->assertTextEndsNotWith("different\rline endings", $stringDirty);
    }

    /**
     * test assertTextContains()
     */
    public function testAssertTextContains(): void
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertStringContainsString('different', $stringDirty);
        $this->assertStringNotContainsString("different\rline", $stringDirty);

        $this->assertTextContains("different\rline", $stringDirty);
    }

    /**
     * test assertTextNotContains()
     */
    public function testAssertTextNotContains(): void
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertTextNotContains("different\rlines", $stringDirty);
    }

    /**
     * test testAssertWithinRange()
     */
    public function testAssertWithinRange(): void
    {
        $this->assertWithinRange(21, 22, 1, 'Not within range');
        $this->assertWithinRange(21.3, 22.2, 1.0, 'Not within range');
    }

    /**
     * test testAssertNotWithinRange()
     */
    public function testAssertNotWithinRange(): void
    {
        $this->assertNotWithinRange(21, 23, 1, 'Within range');
        $this->assertNotWithinRange(21.3, 22.2, 0.7, 'Within range');
    }

    /**
     * test getMockForModel()
     */
    public function testGetMockForModel(): void
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
     */
    public function testGetMockForModelSecondaryDatasource(): void
    {
        ConnectionManager::alias('test', 'secondary');

        $post = $this->getMockForModel(SecondaryPostsTable::class, ['save']);
        $this->assertSame('test', $post->getConnection()->configName());
    }

    /**
     * test getMockForModel() with plugin models
     */
    public function testGetMockForModelWithPlugin(): void
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
     */
    public function testGetMockForModelTable(): void
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
     */
    public function testGetMockForModelSetTable(): void
    {
        static::setAppNamespace();
        ConnectionManager::alias('test', 'custom_i18n_datasource');

        $I18n = $this->getMockForModel('CustomI18n', ['save']);
        $this->assertSame('custom_i18n_table', $I18n->getTable());

        $Tags = $this->getMockForModel('Tags', ['save']);
        $this->assertSame('tags', $Tags->getTable());
        ConnectionManager::dropAlias('custom_i18n_datasource');
    }

    /**
     * Test loadRoutes() helper
     */
    public function testLoadRoutes(): void
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
