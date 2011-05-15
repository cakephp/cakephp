<?php
/**
 * TreeBehaviorAfterTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.model.behaviors
 * @since         CakePHP(tm) v 1.2.0.5330
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
require_once(dirname(dirname(__FILE__)) . DS . 'models.php');


/**
 * TreeBehaviorAfterTest class
 *
 * @package       cake.tests.cases.libs.model.behaviors
 */
class TreeBehaviorAfterTest extends CakeTestCase {

/**
 * Whether backup global state for each test method or not
 *
 * @var bool false
 * @access public
 */
	public $backupGlobals = false;

/**
 * settings property
 *
 * @var array
 * @access public
 */
	public $settings = array(
		'modelClass' => 'AfterTree',
		'leftField' => 'lft',
		'rightField' => 'rght',
		'parentField' => 'parent_id'
	);

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.after_tree');

/**
 * Tests the afterSave callback in the model
 *
 * @access public
 * @return void
 */
	function testAftersaveCallback() {
		$this->Tree = new AfterTree();

		$expected = array('AfterTree' => array('name' => 'Six and One Half Changed in AfterTree::afterSave() but not in database', 'parent_id' => 6, 'lft' => 11, 'rght' => 12));
		$result = $this->Tree->save(array('AfterTree' => array('name' => 'Six and One Half', 'parent_id' => 6)));
		$this->assertEqual($result, $expected);

		$expected = array('AfterTree' => array('name' => 'Six and One Half', 'parent_id' => 6, 'lft' => 11, 'rght' => 12, 'id' => 8));
		$result = $this->Tree->find('all');
		$this->assertEqual($result[7], $expected);
	}
}


