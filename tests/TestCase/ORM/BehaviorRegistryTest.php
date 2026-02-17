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
use Cake\Core\Exception\CakeException;
use Cake\ORM\Behavior\TranslateBehavior;
use Cake\ORM\BehaviorRegistry;
use Cake\ORM\Exception\MissingBehaviorException;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use LogicException;
use TestApp\Model\Behavior\SluggableBehavior;
use TestPlugin\Model\Behavior\PersisterOneBehavior;

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
    protected function setUp(): void
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
    protected function tearDown(): void
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

        $expected = TranslateBehavior::class;
        $result = BehaviorRegistry::className('Translate');
        $this->assertSame($expected, $result);

        $expected = PersisterOneBehavior::class;
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
        $this->assertInstanceOf(SluggableBehavior::class, $result);
        $this->assertEquals($config, $result->getConfig());

        $result = $this->Behaviors->load('TestPlugin.PersisterOne');
        $this->assertInstanceOf(PersisterOneBehavior::class, $result);

        $config = ['className' => 'TestPlugin.PersisterOne'];
        $this->assertSame($config, $result->getConfig());

        $this->Behaviors->unload('PersisterOne');
        $this->Behaviors->load('TestPlugin.PersisterOne', $config);
        $this->assertInstanceOf(PersisterOneBehavior::class, $this->Behaviors->PersisterOne);
    }

    /**
     * Test load() binding listeners.
     */
    public function testLoadBindEvents(): void
    {
        $result = $this->EventManager->listeners('Model.beforeFind');
        $this->assertCount(0, $result);

        $sluggable = $this->Behaviors->load('Sluggable');
        $result = $this->EventManager->listeners('Model.beforeFind');
        $this->assertEquals([['callable' => $sluggable->beforeFind(...)]], $result);
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

        $expected = PersisterOneBehavior::class;
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
     * Test load() duplicate finder error
     */
    public function testLoadDuplicateFinderError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('`TestApp\Model\Behavior\DuplicateBehavior` contains duplicate finder `children`'
        . ' which is already provided by `Tree`.');
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
     * test get finder
     */
    public function testGetFinder(): void
    {
        $this->Behaviors->load('Sluggable');

        $return = $this->Behaviors->getFinder('noSlug');
        $this->assertEquals($this->Behaviors->get('Sluggable')->findNoSlug(...), $return);
    }

    /**
     * Test errors on unknown methods.
     */
    public function testGetFinderError(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Finder `nope` not found on any behavior attached to `Cake\ORM\Table`');
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->getFinder('nope');
    }

    /**
     * Test errors on unloaded behavior finders.
     */
    public function testUnloadBehaviorThenGetFinder(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Finder `noslug` not found on any behavior attached to `Cake\ORM\Table`.');
        $this->Behaviors->load('Sluggable');
        $this->assertTrue($this->Behaviors->hasFinder('noSlug'));
        $this->Behaviors->unload('Sluggable');

        $this->assertFalse($this->Behaviors->hasFinder('noSlug'));
        $this->Behaviors->getFinder('noSlug');
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
        $this->assertTrue($this->Behaviors->hasFinder('noSlug'));

        $this->Behaviors->unload('Sluggable');

        $this->assertEmpty($this->Behaviors->loaded());
        $this->assertCount(0, $this->EventManager->listeners('Model.beforeFind'));
        $this->assertFalse($this->Behaviors->hasFinder('noSlug'));
        $this->assertFalse($this->Behaviors->hasFinder('noslug'));
    }

    /**
     * Test that unloading a none existing behavior triggers an error.
     */
    public function testUnloadUnknown(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Unknown object `Foo`');
        $this->Behaviors->unload('Foo');
    }

    /**
     * Test setTable() method.
     */
    public function testSetTable(): void
    {
        $table = new Table(['table' => 'users']);

        $this->Behaviors->setTable($table);
        $this->assertSame($table->getEventManager(), $this->Behaviors->getEventManager());
    }
}
