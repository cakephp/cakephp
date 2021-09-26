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
namespace Cake\Test\TestCase\ORM;

use BadMethodCallException;
use Cake\ORM\BehaviorRegistry;
use Cake\ORM\Exception\MissingBehaviorException;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use LogicException;

/**
 * Test case for BehaviorRegistry.
 */
class BehaviorRegistryTest extends TestCase
{
    /**
     * @var \Cake\ORM\BehaviorRegistry
     */
    protected $Behaviors;

    /**
     * @var \Cake\ORM\Table
     */
    protected $Table;

    /**
     * @var \Cake\Event\EventManagerInterface
     */
    protected $EventManager;

    /**
     * setup method.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Table = new Table(['table' => 'articles']);
        $this->EventManager = $this->Table->getEventManager();
        $this->Behaviors = new BehaviorRegistry($this->Table);
        static::setAppNamespace();
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        $this->clearPlugins();
        unset($this->Table, $this->EventManager, $this->Behaviors);
        parent::tearDown();
    }

    /**
     * Test classname resolution.
     */
    public function testClassName(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $expected = 'Cake\ORM\Behavior\TranslateBehavior';
        $result = BehaviorRegistry::className('Translate');
        $this->assertSame($expected, $result);

        $expected = 'TestPlugin\Model\Behavior\PersisterOneBehavior';
        $result = BehaviorRegistry::className('TestPlugin.PersisterOne');
        $this->assertSame($expected, $result);

        $this->assertNull(BehaviorRegistry::className('NonExistent'));
    }

    /**
     * Test loading behaviors.
     */
    public function testLoad(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $config = ['alias' => 'Sluggable', 'replacement' => '-'];
        $result = $this->Behaviors->load('Sluggable', $config);
        $this->assertInstanceOf('TestApp\Model\Behavior\SluggableBehavior', $result);
        $this->assertEquals($config, $result->getConfig());

        $result = $this->Behaviors->load('TestPlugin.PersisterOne');
        $this->assertInstanceOf('TestPlugin\Model\Behavior\PersisterOneBehavior', $result);
    }

    /**
     * Test load() binding listeners.
     */
    public function testLoadBindEvents(): void
    {
        $result = $this->EventManager->listeners('Model.beforeFind');
        $this->assertCount(0, $result);

        $this->Behaviors->load('Sluggable');
        $result = $this->EventManager->listeners('Model.beforeFind');
        $this->assertCount(1, $result);
        $this->assertInstanceOf('TestApp\Model\Behavior\SluggableBehavior', $result[0]['callable'][0]);
        $this->assertSame('beforeFind', $result[0]['callable'][1], 'Method name should match.');
    }

    /**
     * Test load() with enabled = false
     */
    public function testLoadEnabledFalse(): void
    {
        $result = $this->EventManager->listeners('Model.beforeFind');
        $this->assertCount(0, $result);

        $this->Behaviors->load('Sluggable', ['enabled' => false]);
        $result = $this->EventManager->listeners('Model.beforeFind');
        $this->assertCount(0, $result);
    }

    /**
     * Test loading plugin behaviors
     */
    public function testLoadPlugin(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $result = $this->Behaviors->load('TestPlugin.PersisterOne');

        $expected = 'TestPlugin\Model\Behavior\PersisterOneBehavior';
        $this->assertInstanceOf($expected, $result);
        $this->assertInstanceOf($expected, $this->Behaviors->PersisterOne);

        $this->Behaviors->unload('PersisterOne');

        $result = $this->Behaviors->load('TestPlugin.PersisterOne', ['foo' => 'bar']);
        $this->assertInstanceOf($expected, $result);
        $this->assertInstanceOf($expected, $this->Behaviors->PersisterOne);
    }

    /**
     * Test load() on undefined class
     */
    public function testLoadMissingClass(): void
    {
        $this->expectException(MissingBehaviorException::class);
        $this->Behaviors->load('DoesNotExist');
    }

    /**
     * Test load() duplicate method error
     */
    public function testLoadDuplicateMethodError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('TestApp\Model\Behavior\DuplicateBehavior contains duplicate method "slugify"');
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->load('Duplicate');
    }

    /**
     * Test load() duplicate method aliasing
     */
    public function testLoadDuplicateMethodAliasing(): void
    {
        $this->Behaviors->load('Tree');
        $this->Behaviors->load('Duplicate', [
            'implementedFinders' => [
                'renamed' => 'findChildren',
            ],
            'implementedMethods' => [
                'renamed' => 'slugify',
            ],
        ]);
        $this->assertTrue($this->Behaviors->hasMethod('renamed'));
    }

    /**
     * Test load() duplicate finder error
     */
    public function testLoadDuplicateFinderError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('TestApp\Model\Behavior\DuplicateBehavior contains duplicate finder "children"');
        $this->Behaviors->load('Tree');
        $this->Behaviors->load('Duplicate');
    }

    /**
     * Test load() duplicate finder aliasing
     */
    public function testLoadDuplicateFinderAliasing(): void
    {
        $this->Behaviors->load('Tree');
        $this->Behaviors->load('Duplicate', [
            'implementedFinders' => [
                'renamed' => 'findChildren',
            ],
        ]);
        $this->assertTrue($this->Behaviors->hasFinder('renamed'));
    }

    /**
     * test hasMethod()
     */
    public function testHasMethod(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $this->Behaviors->load('TestPlugin.PersisterOne');
        $this->Behaviors->load('Sluggable');

        $this->assertTrue($this->Behaviors->hasMethod('slugify'));
        $this->assertTrue($this->Behaviors->hasMethod('SLUGIFY'));

        $this->assertTrue($this->Behaviors->hasMethod('persist'));
        $this->assertTrue($this->Behaviors->hasMethod('PERSIST'));

        $this->assertFalse($this->Behaviors->hasMethod('__construct'));
        $this->assertFalse($this->Behaviors->hasMethod('config'));
        $this->assertFalse($this->Behaviors->hasMethod('implementedEvents'));

        $this->assertFalse($this->Behaviors->hasMethod('nope'));
        $this->assertFalse($this->Behaviors->hasMethod('beforeFind'));
        $this->assertFalse($this->Behaviors->hasMethod('noSlug'));
    }

    /**
     * Test hasFinder() method.
     */
    public function testHasFinder(): void
    {
        $this->Behaviors->load('Sluggable');

        $this->assertTrue($this->Behaviors->hasFinder('noSlug'));
        $this->assertTrue($this->Behaviors->hasFinder('noslug'));
        $this->assertTrue($this->Behaviors->hasFinder('NOSLUG'));

        $this->assertFalse($this->Behaviors->hasFinder('slugify'));
        $this->assertFalse($this->Behaviors->hasFinder('beforeFind'));
        $this->assertFalse($this->Behaviors->hasFinder('nope'));
    }

    /**
     * test call
     *
     * Setup a behavior, then replace it with a mock to verify methods are called.
     * use dummy return values to verify the return value makes it back
     */
    public function testCall(): void
    {
        $this->Behaviors->load('Sluggable');
        $mockedBehavior = $this->getMockBuilder('Cake\ORM\Behavior')
            ->addMethods(['slugify'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->Behaviors->set('Sluggable', $mockedBehavior);

        $mockedBehavior
            ->expects($this->once())
            ->method('slugify')
            ->with(['some value'])
            ->will($this->returnValue('some-thing'));
        $return = $this->Behaviors->call('slugify', [['some value']]);
        $this->assertSame('some-thing', $return);
    }

    /**
     * Test errors on unknown methods.
     */
    public function testCallError(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot call "nope"');
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->call('nope');
    }

    /**
     * test call finder
     *
     * Setup a behavior, then replace it with a mock to verify methods are called.
     * use dummy return values to verify the return value makes it back
     */
    public function testCallFinder(): void
    {
        $this->Behaviors->load('Sluggable');
        $mockedBehavior = $this->getMockBuilder('Cake\ORM\Behavior')
            ->addMethods(['findNoSlug'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->Behaviors->set('Sluggable', $mockedBehavior);

        $query = new Query($this->Table->getConnection(), $this->Table);
        $mockedBehavior
            ->expects($this->once())
            ->method('findNoSlug')
            ->with($query, [])
            ->will($this->returnValue($query));
        $return = $this->Behaviors->callFinder('noSlug', [$query, []]);
        $this->assertSame($query, $return);
    }

    /**
     * Test errors on unknown methods.
     */
    public function testCallFinderError(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot call finder "nope"');
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->callFinder('nope');
    }

    /**
     * Test errors on unloaded behavior methods.
     */
    public function testUnloadBehaviorThenCall(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot call "slugify" it does not belong to any attached behavior.');
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->unload('Sluggable');

        $this->Behaviors->call('slugify');
    }

    /**
     * Test errors on unloaded behavior finders.
     */
    public function testUnloadBehaviorThenCallFinder(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot call finder "noslug" it does not belong to any attached behavior.');
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->unload('Sluggable');

        $this->Behaviors->callFinder('noSlug');
    }

    /**
     * Test that unloading then reloading a behavior does not throw any errors.
     */
    public function testUnloadBehaviorThenReload(): void
    {
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->unload('Sluggable');

        $this->assertEmpty($this->Behaviors->loaded());

        $this->Behaviors->load('Sluggable');

        $this->assertEquals(['Sluggable'], $this->Behaviors->loaded());
    }

    /**
     * Test that unloading a none existing behavior triggers an error.
     */
    public function testUnload(): void
    {
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->unload('Sluggable');

        $this->assertEmpty($this->Behaviors->loaded());
        $this->assertCount(0, $this->EventManager->listeners('Model.beforeFind'));
    }

    /**
     * Test that unloading a none existing behavior triggers an error.
     */
    public function testUnloadUnknown(): void
    {
        $this->expectException(MissingBehaviorException::class);
        $this->expectExceptionMessage('Behavior class FooBehavior could not be found.');
        $this->Behaviors->unload('Foo');
    }

    /**
     * Test setTable() method.
     */
    public function testSetTable(): void
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $table->expects($this->once())->method('getEventManager');

        $this->Behaviors->setTable($table);
    }
}
