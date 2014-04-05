<?php
/**
 * SqlLogPanelTest
 *
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('SqlLogPanel', 'DebugKit.Lib/Panel');
App::uses('Model', 'Model');
App::uses('Controller', 'Controller');

/**
 * Class SqlLogPanelTest
 *
 * @since         DebugKit 2.1
 */
class SqlLogPanelTest extends CakeTestCase {

/**
 * fixtures.
 *
 * @var array
 */
	public $fixtures = array('core.article');

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->panel = new SqlLogPanel();
	}

/**
 * test the parsing of source list.
 *
 * @return void
 */
	public function testBeforeRender() {
		$Article = ClassRegistry::init('Article');
		$Article->find('first', array('conditions' => array('Article.id' => 1)));

		$controller = new Controller();
		$result = $this->panel->beforeRender($controller);

		$this->assertTrue(isset($result['connections'][$Article->useDbConfig]));
		$this->assertTrue(isset($result['threshold']));
	}
}
