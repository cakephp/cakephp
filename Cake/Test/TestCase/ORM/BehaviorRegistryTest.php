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
use Cake\ORM\Table;
use Cake\ORM\BehaviorRegistry;
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
		$settings = ['alias' => 'Sluggable', 'replacement' => '-'];
		$result = $this->Behaviors->load('Sluggable', $settings);
		$this->assertInstanceOf('TestApp\Model\Behavior\SluggableBehavior', $result);
		$this->assertEquals($settings, $result->settings);
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
 * @expectedException Cake\Error\MissingBehaviorException
 * @return void
 */
	public function testLoadMissingClass() {
		$this->Behaviors->load('DoesNotExist');
	}

/**
 * Test load() duplicate method error
 *
 * @expectedException Cake\Error\Exception
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
		$this->Behaviors->load('Sluggable');
		$this->assertTrue($this->Behaviors->hasMethod('slugify'));
		$this->assertFalse($this->Behaviors->hasMethod('nope'));
	}

/**
 * test call
 *
 * @return void
 */
	public function testCall() {
		$this->Behaviors->load('Sluggable');
		$result = $this->Behaviors->call('nope');
		$this->assertFalse($result);

		$result = $this->Behaviors->call('slugify', ['some value']);
		$this->assertEquals('some_value', $result);
	}

}
