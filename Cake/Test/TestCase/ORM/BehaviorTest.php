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

use Cake\ORM\Behavior;
use Cake\TestSuite\TestCase;

/**
 * Test Stub.
 */
class TestBehavior extends Behavior {

/**
 * Test for event bindings.
 */
	public function beforeFind() {
	}

}

/**
 * Behavior test case
 */
class BehaviorTest extends TestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
	}

/**
 * Test the side effects of the constructor.
 *
 * @return void
 */
	public function testConstructor() {
		$table = $this->getMock('Cake\ORM\Table');
		$settings = ['key' => 'value'];
		$behavior = new TestBehavior($table, $settings);
		$this->assertEquals($settings, $behavior->settings());
	}

/**
 * Test the default behavior of implementedEvents
 *
 * @return void
 */
	public function testImplementedEvents() {
		$table = $this->getMock('Cake\ORM\Table');
		$behavior = new TestBehavior($table);
		$expected = [
			'Model.beforeFind' => 'beforeFind'
		];
		$this->assertEquals($expected, $behavior->implementedEvents());
	}

/**
 * Test that implementedEvents uses the priority setting.
 *
 * @return void
 */
	public function testImplementedEventsWithPriority() {
		$table = $this->getMock('Cake\ORM\Table');
		$behavior = new TestBehavior($table, ['priority' => 10]);
		$expected = [
			'Model.beforeFind' => [
				'priority' => 10,
				'callable' => 'beforeFind'
			]
		];
		$this->assertEquals($expected, $behavior->implementedEvents());
	}

}
