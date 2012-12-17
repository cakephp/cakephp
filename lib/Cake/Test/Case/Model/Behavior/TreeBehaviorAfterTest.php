<?php
/**
 * TreeBehaviorAfterTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.5330
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
require_once dirname(dirname(__FILE__)) . DS . 'models.php';


/**
 * TreeBehaviorAfterTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class TreeBehaviorAfterTest extends CakeTestCase {

/**
 * Whether backup global state for each test method or not
 *
 * @var bool false
 */
	public $backupGlobals = false;

/**
 * settings property
 *
 * @var array
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
 */
	public $fixtures = array('core.after_tree');

/**
 * Tests the afterSave callback in the model
 *
 * @return void
 */
	public function testAftersaveCallback() {
		$this->Tree = new AfterTree();

		$expected = array('AfterTree' => array('name' => 'Six and One Half Changed in AfterTree::afterSave() but not in database', 'parent_id' => 6, 'lft' => 11, 'rght' => 12));
		$result = $this->Tree->save(array('AfterTree' => array('name' => 'Six and One Half', 'parent_id' => 6)));
		$expected['AfterTree']['id'] = $this->Tree->id;
		$this->assertEquals($expected, $result);

		$expected = array('AfterTree' => array('name' => 'Six and One Half', 'parent_id' => 6, 'lft' => 11, 'rght' => 12, 'id' => 8));
		$result = $this->Tree->find('all');
		$this->assertEquals($expected, $result[7]);
	}
}
