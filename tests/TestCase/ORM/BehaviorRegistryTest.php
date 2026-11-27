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
use Closure;
use LogicException;
use ReflectionFunction;
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
        $this->assertSame([0], array_keys($result));
        $this->assertSame(['callable'], array_keys($result[0]));
        $this->assertInstanceOf(Closure::class, $result[0]['callable']);
        $callback = new ReflectionFunction($result[0]['callable']);
        $this->assertSame($sluggable, $callback->getClosureThis());
        $this->assertSame('beforeFind', $callback->getName());
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
     * Test load() duplicate method error
     */
    public function testLoadDuplicateMethodError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            '`TestApp\Model\Behavior\DuplicateBehavior` contains duplicate method `slugify`'
                . ' which is already provided by `Sluggable`.',
        );
        $this->Behaviors->load('Sluggable', [
            'implementedMethods' => ['slugify' => 'slugify'],
        ]);
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
     * Test set()
     */
    public function testSet(): void
    {
        $this->Behaviors->set('Sluggable', new SluggableBehavior($this->Table, [
            'replacement' => '_',
            'implementedMethods' => ['slugify' => 'slugify'],
        ]));

        $config = $this->Behaviors->get('Sluggable')->getConfig();
        $this->assertSame('_', $config['replacement']);
        $this->assertTrue($this->Behaviors->hasMethod('slugify'));
    }

    /**
     * test hasMethod()
     */
    public function testHasMethod(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $this->Behaviors->load('TestPlugin.PersisterOne', ['implementedMethods' => true]);
        $this->Behaviors->load('Sluggable', ['implementedMethods' => true]);

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
     * Methods are not proxied unless the `implementedMethods` config is set.
     */
    public function testHasMethodNotEnabledByDefault(): void
    {
        $this->Behaviors->load('Sluggable');

        $this->assertFalse($this->Behaviors->hasMethod('slugify'));
    }

    /**
     * Test hasMethod() and call() with a numerically indexed list of method names.
     */
    public function testHasMethodWithNumericList(): void
    {
        $this->Behaviors->load('Sluggable', ['implementedMethods' => ['slugify']]);

        $this->assertTrue($this->Behaviors->hasMethod('slugify'));
        $this->assertSame('some-value', $this->Behaviors->call('slugify', ['some value']));
    }

    /**
     * test call()
     */
    public function testCall(): void
    {
        $this->Behaviors->load('Sluggable', ['implementedMethods' => true]);
        $return = $this->Behaviors->call('slugify', ['some value']);
        $this->assertSame('some-value', $return);
    }

    /**
     * Test errors on unknown methods.
     */
    public function testCallError(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot call `nope`, it does not belong to any attached behavior.');
        $this->Behaviors->load('Sluggable', ['implementedMethods' => true]);
        $this->Behaviors->call('nope');
    }

    /**
     * Test load() duplicate finder error
     */
    public function testLoadDuplicateFinderError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            '`TestApp\Model\Behavior\DuplicateBehavior` contains duplicate finder `children`'
                . ' which is already provided by `Tree`.',
        );
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
        $sluggable = $this->Behaviors->load('Sluggable');

        $return = $this->Behaviors->getFinder('noSlug');
        $this->assertInstanceOf(Closure::class, $return);
        $callback = new ReflectionFunction($return);
        $this->assertSame($sluggable, $callback->getClosureThis());
        $this->assertSame('findNoSlug', $callback->getName());
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
     * Test errors on unloaded behavior methods.
     */
    public function testUnloadBehaviorThenCall(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot call `slugify`, it does not belong to any attached behavior.');
        $this->Behaviors->load('Sluggable', ['implementedMethods' => true]);

        $this->assertTrue($this->Behaviors->hasMethod('slugify'));
        $this->Behaviors->unload('Sluggable');

        $this->assertFalse($this->Behaviors->hasMethod('slugify'), 'should not have method anymore');
        $this->Behaviors->call('slugify');
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
        $this->Behaviors->load('Sluggable', ['implementedMethods' => true]);
        $this->assertTrue($this->Behaviors->hasFinder('noSlug'));
        $this->assertTrue($this->Behaviors->hasMethod('slugify'));

        $this->Behaviors->load('Validation', ['implementedMethods' => true]);
        $this->assertTrue($this->Behaviors->hasMethod('customValidationRule'));

        $this->Behaviors->unload('Validation');
        $this->Behaviors->unload('Sluggable');

        $this->assertEmpty($this->Behaviors->loaded());
        $this->assertCount(0, $this->EventManager->listeners('Model.beforeFind'));
        $this->assertFalse($this->Behaviors->hasFinder('noSlug'));
        $this->assertFalse($this->Behaviors->hasFinder('noslug'));
        $this->assertFalse($this->Behaviors->hasMethod('customValidationRule'));
        $this->assertFalse($this->Behaviors->hasMethod('customvalidationrule'));
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
