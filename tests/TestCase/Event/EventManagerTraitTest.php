<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Event;

use Cake\Event\EventManager;
use Cake\Event\EventManagerTrait;
use Cake\TestSuite\TestCase;

/**
 * EventManagerTrait test case
 *
 */
class EventManagerTraitTest extends TestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->subject = $this->getObjectForTrait('Cake\Event\EventManagerTrait');
	}

/**
 * testIsInitiallyEmpty
 *
 * @return void
 */
	public function testIsInitiallyEmpty() {
		$this->assertAttributeEmpty('_eventManager', $this->subject);
	}

/**
 * testSettingEventManager
 *
 * @covers EventManagerTrait::eventManager
 * @return void
 */
	public function testSettingEventManager() {
		$eventManager = new EventManager();

		$this->subject->eventManager($eventManager);

		$this->assertSame($eventManager, $this->subject->eventManager());
	}
}
