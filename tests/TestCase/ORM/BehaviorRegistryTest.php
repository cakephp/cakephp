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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
class BehaviorRegistryTest extends TestCase {

/**
 * setup method.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Table = new Table(['table' => 'articles']);
		$this->EventManager = $this->Table->getEventManager();
		$this->Behaviors = new BehaviorRegistry($this->Table);
		Configure::write('App.namespace', 'TestApp');
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		Plugin::unload();
		unset($this->Table, $this->EventManager, $this->Behaviors);
		parent::tearDown();
	}

/**
 * Test loading behaviors.
 *
 * @return void
 */
	public function testLoad() {
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
	public function testLoadBindEvents() {
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
	public function testLoadEnabledFalse() {
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
	public function testLoadPlugin() {
		Plugin::load('TestPlugin');
		$result = $this->Behaviors->load('TestPlugin.PersisterOne');
		$this->assertInstanceOf('TestPlugin\Model\Behavior\PersisterOneBehavior', $result);
	}

/**
 * Test load() on undefined class
 *
 * @expectedException \Cake\Error\MissingBehaviorException
 * @return void
 */
	public function testLoadMissingClass() {
		$this->Behaviors->load('DoesNotExist');
	}

/**
 * Test load() duplicate method error
 *
 * @expectedException \Cake\Error\Exception
 * @expectedExceptionMessage TestApp\Model\Behavior\DuplicateBehavior contains duplicate method "slugify"
 * @return void
 */
	public function testLoadDuplicateMethodError() {
		$this->Behaviors->load('Sluggable');
		$this->Behaviors->load('Duplicate');
	}

/**
 * test hasMethod()
 *
 * @return void
 */
	public function testHasMethod() {
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
	public function testHasFinder() {
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
	public function testCall() {
		$this->Behaviors->load('Sluggable');
		$mockedBehavior = $this->getMock('Behavior', ['slugify']);
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
 * @expectedException \Cake\Error\Exception
 * @expectedExceptionMessage Cannot call "nope"
 */
	public function testCallError() {
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
	public function testCallFinder() {
		$this->Behaviors->load('Sluggable');
		$mockedBehavior = $this->getMock('Behavior', ['findNoSlug']);
		$this->Behaviors->set('Sluggable', $mockedBehavior);

		$query = $this->getMock('Cake\ORM\Query', [], [null, null]);
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
 * @expectedException \Cake\Error\Exception
 * @expectedExceptionMessage Cannot call finder "nope"
 */
	public function testCallFinderError() {
		$this->Behaviors->load('Sluggable');
		$this->Behaviors->callFinder('nope');
	}

}
