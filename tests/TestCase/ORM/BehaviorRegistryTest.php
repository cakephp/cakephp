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
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\ORM\BehaviorRegistry;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * Test case for BehaviorRegistry.
 */
class BehaviorRegistryTest extends TestCase
{

    /**
     * setup method.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Table = new Table(['table' => 'articles']);
        $this->EventManager = $this->Table->eventManager();
        $this->Behaviors = new BehaviorRegistry($this->Table);
        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        Plugin::unload();
        unset($this->Table, $this->EventManager, $this->Behaviors);
        parent::tearDown();
    }

    /**
     * Test loading behaviors.
     *
     * @return void
     */
    public function testLoad()
    {
        Plugin::load('TestPlugin');
        $config = ['alias' => 'Sluggable', 'replacement' => '-'];
        $result = $this->Behaviors->load('Sluggable', $config);
        $this->assertInstanceOf('TestApp\Model\Behavior\SluggableBehavior', $result);
        $this->assertEquals($config, $result->config());

        $result = $this->Behaviors->load('TestPlugin.PersisterOne');
        $this->assertInstanceOf('TestPlugin\Model\Behavior\PersisterOneBehavior', $result);
    }

    /**
     * Test load() binding listeners.
     *
     * @return void
     */
    public function testLoadBindEvents()
    {
        $result = $this->EventManager->listeners('Model.beforeFind');
        $this->assertCount(0, $result);

        $this->Behaviors->load('Sluggable');
        $result = $this->EventManager->listeners('Model.beforeFind');
        $this->assertCount(1, $result);
        $this->assertInstanceOf('TestApp\Model\Behavior\SluggableBehavior', $result[0]['callable'][0]);
        $this->assertEquals('beforeFind', $result[0]['callable'][1], 'Method name should match.');
    }

    /**
     * Test load() with enabled = false
     *
     * @return void
     */
    public function testLoadEnabledFalse()
    {
        $result = $this->EventManager->listeners('Model.beforeFind');
        $this->assertCount(0, $result);

        $this->Behaviors->load('Sluggable', ['enabled' => false]);
        $result = $this->EventManager->listeners('Model.beforeFind');
        $this->assertCount(0, $result);
    }

    /**
     * Test loading plugin behaviors
     *
     * @return void
     */
    public function testLoadPlugin()
    {
        Plugin::load('TestPlugin');
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
     *
     * @expectedException \Cake\ORM\Exception\MissingBehaviorException
     * @return void
     */
    public function testLoadMissingClass()
    {
        $this->Behaviors->load('DoesNotExist');
    }

    /**
     * Test load() duplicate method error
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage TestApp\Model\Behavior\DuplicateBehavior contains duplicate method "slugify"
     * @return void
     */
    public function testLoadDuplicateMethodError()
    {
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->load('Duplicate');
    }

    /**
     * Test load() duplicate method aliasing
     *
     * @return void
     */
    public function testLoadDuplicateMethodAliasing()
    {
        $this->Behaviors->load('Tree');
        $this->Behaviors->load('Duplicate', [
            'implementedFinders' => [
                'renamed' => 'findChildren',
            ],
            'implementedMethods' => [
                'renamed' => 'slugify',
            ]
        ]);
        $this->assertTrue($this->Behaviors->hasMethod('renamed'));
    }

    /**
     * Test load() duplicate finder error
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage TestApp\Model\Behavior\DuplicateBehavior contains duplicate finder "children"
     * @return void
     */
    public function testLoadDuplicateFinderError()
    {
        $this->Behaviors->load('Tree');
        $this->Behaviors->load('Duplicate');
    }

    /**
     * Test load() duplicate finder aliasing
     *
     * @return void
     */
    public function testLoadDuplicateFinderAliasing()
    {
        $this->Behaviors->load('Tree');
        $this->Behaviors->load('Duplicate', [
            'implementedFinders' => [
                'renamed' => 'findChildren',
            ]
        ]);
        $this->assertTrue($this->Behaviors->hasFinder('renamed'));
    }

    /**
     * test hasMethod()
     *
     * @return void
     */
    public function testHasMethod()
    {
        Plugin::load('TestPlugin');
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
     *
     * @return void
     */
    public function testHasFinder()
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
     *
     * @return void
     */
    public function testCall()
    {
        $this->Behaviors->load('Sluggable');
        $mockedBehavior = $this->getMockBuilder('Cake\ORM\Behavior')
            ->setMethods(['slugify'])
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
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Cannot call "nope"
     */
    public function testCallError()
    {
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->call('nope');
    }

    /**
     * test call finder
     *
     * Setup a behavior, then replace it with a mock to verify methods are called.
     * use dummy return values to verify the return value makes it back
     *
     * @return void
     */
    public function testCallFinder()
    {
        $this->Behaviors->load('Sluggable');
        $mockedBehavior = $this->getMockBuilder('Cake\ORM\Behavior')
            ->setMethods(['findNoSlug'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->Behaviors->set('Sluggable', $mockedBehavior);

        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->setConstructorArgs([null, null])
            ->getMock();
        $mockedBehavior
            ->expects($this->once())
            ->method('findNoSlug')
            ->with($query, [])
            ->will($this->returnValue('example'));
        $return = $this->Behaviors->callFinder('noSlug', [$query, []]);
        $this->assertSame('example', $return);
    }

    /**
     * Test errors on unknown methods.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Cannot call finder "nope"
     */
    public function testCallFinderError()
    {
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->callFinder('nope');
    }

    /**
     * Test errors on unloaded behavior methods.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Cannot call "slugify" it does not belong to any attached behavior.
     */
    public function testUnloadBehaviorThenCall()
    {
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->unload('Sluggable');

        $this->Behaviors->call('slugify');
    }

    /**
     * Test errors on unloaded behavior finders.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Cannot call finder "noslug" it does not belong to any attached behavior.
     */
    public function testUnloadBehaviorThenCallFinder()
    {
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->unload('Sluggable');

        $this->Behaviors->callFinder('noSlug');
    }

    /**
     * Test that unloading then reloading a behavior does not throw any errors.
     *
     * @return void
     */
    public function testUnloadBehaviorThenReload()
    {
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->unload('Sluggable');

        $this->assertEmpty($this->Behaviors->loaded());

        $this->Behaviors->load('Sluggable');

        $this->assertEquals(['Sluggable'], $this->Behaviors->loaded());
    }

    /**
     * Test that unloading a none existing behavior triggers an error.
     *
     * @return void
     */
    public function testUnload()
    {
        $this->Behaviors->load('Sluggable');
        $this->Behaviors->unload('Sluggable');

        $this->assertEmpty($this->Behaviors->loaded());
        $this->assertCount(0, $this->EventManager->listeners('Model.beforeFind'));
    }

    /**
     * Test that unloading a none existing behavior triggers an error.
     *
     * @expectedException \Cake\ORM\Exception\MissingBehaviorException
     * @expectedExceptionMessage Behavior class FooBehavior could not be found.
     * @return void
     */
    public function testUnloadUnknown()
    {
        $this->Behaviors->unload('Foo');
    }

    /**
     * Test setTable() method.
     *
     * @return void
     */
    public function testSetTable()
    {
        $table = $this->getMockBuilder('Cake\ORM\Table')->getMock();
        $table->expects($this->once())->method('eventManager');

        $this->Behaviors->setTable($table);
    }
}
