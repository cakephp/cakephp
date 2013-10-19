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
 * Test loading app & core behaviors.
 *
 * @return void
 */
	public function testLoad() {
		$this->markTestIncomplete('not done');
	}

/**
 * Test load() binding listeners.
 *
 * @return void
 */
	public function testLoadBindEvents() {
		$this->markTestIncomplete('not done');
	}

/**
 * Test load() with enabled = false
 *
 * @return void
 */
	public function testLoadEnabledFalse() {
		$this->markTestIncomplete('not done');
	}

/**
 * Test loading plugin behaviors
 *
 * @return void
 */
	public function testLoadPlugin() {
		$this->markTestIncomplete('not done');
	}

/**
 * Test load() on undefined class
 *
 * @expectedException Cake\Error\MissingBehaviorException
 * @return void
 */
	public function testLoadMissingClass() {
		$this->markTestIncomplete('not done');
	}

/**
 * Test load() duplicate method error
 *
 * @expectedException Cake\Error\Exception
 * @expectedExceptionMessage TestApp\Model\Behavior\DuplicateBehavior contains duplicate method "dupe"
 * @return void
 */
	public function testLoadDuplicateMethodError() {
		$this->markTestIncomplete('not done');
	}

/**
 * test hasMethod()
 *
 * @return void
 */
	public function testHasMethod() {
		$this->markTestIncomplete('not done');
	}

}
