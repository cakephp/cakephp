<?php
/**
 * DebugKit TimedBehavior Test Case
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DebugKitDebugger', 'DebugKit.Lib');

/**
 * Class TimedBehaviorTestCase
 *
 * @since         DebugKit 1.3
 */
class TimedBehaviorTestCase extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('core.article');

/**
 * Start Test callback
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Article = ClassRegistry::init('Article');
		$this->Article->Behaviors->attach('DebugKit.Timed');
	}

/**
 * End a test
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Article);
		ClassRegistry::flush();
		DebugKitDebugger::clearTimers();
	}

/**
 * Test find timers
 *
 * @return void
 */
	public function testFindTimers() {
		$timers = DebugKitDebugger::getTimers(false);
		$this->assertEquals(count($timers), 1);

		$this->Article->find('all');
		$result = DebugKitDebugger::getTimers(false);
		$this->assertEquals(count($result), 2);

		$this->Article->find('all');
		$result = DebugKitDebugger::getTimers(false);
		$this->assertEquals(count($result), 3);
	}

/**
 * Test save timers
 *
 * @return void
 */
	public function testSaveTimers() {
		$timers = DebugKitDebugger::getTimers(false);
		$this->assertEquals(count($timers), 1);

		$this->Article->save(array('user_id' => 1, 'title' => 'test', 'body' => 'test'));
		$result = DebugKitDebugger::getTimers(false);
		$this->assertEquals(count($result), 2);
	}
}
