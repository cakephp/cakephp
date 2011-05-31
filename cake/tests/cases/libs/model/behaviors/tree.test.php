<?php
/**
 * TreeBehaviorTest file
 *
 * Holds several Test Cases
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.behaviors
 * @since         CakePHP(tm) v 1.2.0.5330
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('AppModel', 'Model'));
require_once(dirname(dirname(__FILE__)) . DS . 'models.php');

/**
 * NumberTreeTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.behaviors
 */
class NumberTreeTest extends CakeTestCase {

/**
 * settings property
 *
 * @var array
 * @access public
 */
	var $settings = array(
		'modelClass' => 'NumberTree',
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
	var $fixtures = array('core.number_tree');

/**
 * testInitialize method
 *
 * @access public
 * @return void
 */
	function testInitialize() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->find('count');
		$this->assertEqual($result, 7);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testDetectInvalidLeft method
 *
 * @access public
 * @return void
 */
	function testDetectInvalidLeft() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->findByName('1.1');

		$save[$modelClass]['id'] = $result[$modelClass]['id'];
		$save[$modelClass][$leftField] = 0;

		$this->Tree->save($save);
		$result = $this->Tree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->Tree->recover();
		$this->assertIdentical($result, true);

		$result = $this->Tree->verify();
		$this->assertIdentical($result, true);
	}

/**
 * testDetectInvalidRight method
 *
 * @access public
 * @return void
 */
	function testDetectInvalidRight() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->findByName('1.1');

		$save[$modelClass]['id'] = $result[$modelClass]['id'];
		$save[$modelClass][$rightField] = 0;

		$this->Tree->save($save);
		$result = $this->Tree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->Tree->recover();
		$this->assertIdentical($result, true);

		$result = $this->Tree->verify();
		$this->assertIdentical($result, true);
	}

/**
 * testDetectInvalidParent method
 *
 * @access public
 * @return void
 */
	function testDetectInvalidParent() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->findByName('1.1');

		// Bypass behavior and any other logic
		$this->Tree->updateAll(array($parentField => null), array('id' => $result[$modelClass]['id']));

		$result = $this->Tree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->Tree->recover();
		$this->assertIdentical($result, true);

		$result = $this->Tree->verify();
		$this->assertIdentical($result, true);
	}

/**
 * testDetectNoneExistantParent method
 *
 * @access public
 * @return void
 */
	function testDetectNoneExistantParent() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->findByName('1.1');
		$this->Tree->updateAll(array($parentField => 999999), array('id' => $result[$modelClass]['id']));

		$result = $this->Tree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->Tree->recover('MPTT');
		$this->assertIdentical($result, true);

		$result = $this->Tree->verify();
		$this->assertIdentical($result, true);
	}

/**
 * testRecoverFromMissingParent method
 *
 * @access public
 * @return void
 */
	function testRecoverFromMissingParent() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->findByName('1.1');
		$this->Tree->updateAll(array($parentField => 999999), array('id' => $result[$modelClass]['id']));

		$result = $this->Tree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->Tree->recover();
		$this->assertIdentical($result, true);

		$result = $this->Tree->verify();
		$this->assertIdentical($result, true);
	}

/**
 * testDetectInvalidParents method
 *
 * @access public
 * @return void
 */
	function testDetectInvalidParents() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$this->Tree->updateAll(array($parentField => null));

		$result = $this->Tree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->Tree->recover();
		$this->assertIdentical($result, true);

		$result = $this->Tree->verify();
		$this->assertIdentical($result, true);
	}

/**
 * testDetectInvalidLftsRghts method
 *
 * @access public
 * @return void
 */
	function testDetectInvalidLftsRghts() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$this->Tree->updateAll(array($leftField => 0, $rightField => 0));

		$result = $this->Tree->verify();
		$this->assertNotIdentical($result, true);

		$this->Tree->recover();

		$result = $this->Tree->verify();
		$this->assertIdentical($result, true);
	}

/**
 * Reproduces a situation where a single node has lft= rght, and all other lft and rght fields follow sequentially
 *
 * @access public
 * @return void
 */
	function testDetectEqualLftsRghts() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(1, 3);

		$result = $this->Tree->findByName('1.1');
		$this->Tree->updateAll(array($rightField => $result[$modelClass][$leftField]), array('id' => $result[$modelClass]['id']));
		$this->Tree->updateAll(array($leftField => $this->Tree->escapeField($leftField) . ' -1'),
			array($leftField . ' >' => $result[$modelClass][$leftField]));
		$this->Tree->updateAll(array($rightField => $this->Tree->escapeField($rightField) . ' -1'),
			array($rightField . ' >' => $result[$modelClass][$leftField]));

		$result = $this->Tree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * testAddOrphan method
 *
 * @access public
 * @return void
 */
	function testAddOrphan() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$this->Tree->save(array($modelClass => array('name' => 'testAddOrphan', $parentField => null)));
		$result = $this->Tree->find(null, array('name', $parentField), $modelClass . '.' . $leftField . ' desc');
		$expected = array($modelClass => array('name' => 'testAddOrphan', $parentField => null));
		$this->assertEqual($result, $expected);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testAddMiddle method
 *
 * @access public
 * @return void
 */
	function testAddMiddle() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$data= $this->Tree->find(array($modelClass . '.name' => '1.1'), array('id'));
		$initialCount = $this->Tree->find('count');

		$this->Tree->create();
		$result = $this->Tree->save(array($modelClass => array('name' => 'testAddMiddle', $parentField => $data[$modelClass]['id'])));
		$expected = array_merge(array($modelClass => array('name' => 'testAddMiddle', $parentField => '2')), $result);
		$this->assertIdentical($result, $expected);

		$laterCount = $this->Tree->find('count');

		$laterCount = $this->Tree->find('count');
		$this->assertEqual($initialCount + 1, $laterCount);

		$children = $this->Tree->children($data[$modelClass]['id'], true, array('name'));
		$expects = array(array($modelClass => array('name' => '1.1.1')),
			array($modelClass => array('name' => '1.1.2')),
			array($modelClass => array('name' => 'testAddMiddle')));
		$this->assertIdentical($children, $expects);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testAddInvalid method
 *
 * @access public
 * @return void
 */
	function testAddInvalid() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$initialCount = $this->Tree->find('count');
		//$this->expectError('Trying to save a node under a none-existant node in TreeBehavior::beforeSave');

		$saveSuccess = $this->Tree->save(array($modelClass => array('name' => 'testAddInvalid', $parentField => 99999)));
		$this->assertIdentical($saveSuccess, false);

		$laterCount = $this->Tree->find('count');
		$this->assertIdentical($initialCount, $laterCount);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testAddNotIndexedByModel method
 *
 * @access public
 * @return void
 */
	function testAddNotIndexedByModel() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$this->Tree->save(array('name' => 'testAddNotIndexed', $parentField => null));
		$result = $this->Tree->find(null, array('name', $parentField), $modelClass . '.' . $leftField . ' desc');
		$expected = array($modelClass => array('name' => 'testAddNotIndexed', $parentField => null));
		$this->assertEqual($result, $expected);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
}

/**
 * testMovePromote method
 *
 * @access public
 * @return void
 */
	function testMovePromote() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find(array($modelClass . '.name' => '1. Root'));
		$parent_id = $parent[$modelClass]['id'];

		$data = $this->Tree->find(array($modelClass . '.name' => '1.1.1'), array('id'));
		$this->Tree->id= $data[$modelClass]['id'];
		$this->Tree->saveField($parentField, $parent_id);
		$direct = $this->Tree->children($parent_id, true, array('id', 'name', $parentField, $leftField, $rightField));
		$expects = array(array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 5)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 6, $rightField => 11)),
			array($modelClass => array('id' => 3, 'name' => '1.1.1', $parentField => 1, $leftField => 12, $rightField => 13)));
		$this->assertEqual($direct, $expects);
		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testMoveWithWhitelist method
 *
 * @access public
 * @return void
 */
	function testMoveWithWhitelist() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find(array($modelClass . '.name' => '1. Root'));
		$parent_id = $parent[$modelClass]['id'];

		$data = $this->Tree->find(array($modelClass . '.name' => '1.1.1'), array('id'));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->whitelist = array($parentField, 'name', 'description');
		$this->Tree->saveField($parentField, $parent_id);

		$result = $this->Tree->children($parent_id, true, array('id', 'name', $parentField, $leftField, $rightField));
		$expected = array(array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 5)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 6, $rightField => 11)),
			array($modelClass => array('id' => 3, 'name' => '1.1.1', $parentField => 1, $leftField => 12, $rightField => 13)));
		$this->assertEqual($result, $expected);
		$this->assertTrue($this->Tree->verify());
	}

/**
 * testInsertWithWhitelist method
 *
 * @access public
 * @return void
 */
	function testInsertWithWhitelist() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$this->Tree->whitelist = array('name', $parentField);
		$this->Tree->save(array($modelClass => array('name' => 'testAddOrphan', $parentField => null)));
		$result = $this->Tree->findByName('testAddOrphan', array('name', $parentField, $leftField, $rightField));
		$expected = array('name' => 'testAddOrphan', $parentField => null, $leftField => '15', $rightField => 16);
		$this->assertEqual($result[$modelClass], $expected);
		$this->assertIdentical($this->Tree->verify(), true);
	}

/**
 * testMoveBefore method
 *
 * @access public
 * @return void
 */
	function testMoveBefore() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find(array($modelClass . '.name' => '1.1'));
		$parent_id = $parent[$modelClass]['id'];

		$data= $this->Tree->find(array($modelClass . '.name' => '1.2'), array('id'));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->saveField($parentField, $parent_id);

		$result = $this->Tree->children($parent_id, true, array('name'));
		$expects = array(array($modelClass => array('name' => '1.1.1')),
			array($modelClass => array('name' => '1.1.2')),
			array($modelClass => array('name' => '1.2')));
		$this->assertEqual($result, $expects);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testMoveAfter method
 *
 * @access public
 * @return void
 */
	function testMoveAfter() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find(array($modelClass . '.name' => '1.2'));
		$parent_id = $parent[$modelClass]['id'];

		$data= $this->Tree->find(array($modelClass . '.name' => '1.1'), array('id'));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->saveField($parentField, $parent_id);

		$result = $this->Tree->children($parent_id, true, array('name'));
		$expects = array(array($modelClass => array('name' => '1.2.1')),
			array($modelClass => array('name' => '1.2.2')),
			array($modelClass => array('name' => '1.1')));
		$this->assertEqual($result, $expects);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testMoveDemoteInvalid method
 *
 * @access public
 * @return void
 */
	function testMoveDemoteInvalid() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find(array($modelClass . '.name' => '1. Root'));
		$parent_id = $parent[$modelClass]['id'];

		$data = $this->Tree->find(array($modelClass . '.name' => '1.1.1'), array('id'));

		$expects = $this->Tree->find('all');
		$before = $this->Tree->read(null, $data[$modelClass]['id']);

		$this->Tree->id = $parent_id;
		//$this->expectError('Trying to save a node under itself in TreeBehavior::beforeSave');
		$this->Tree->saveField($parentField, $data[$modelClass]['id']);

		$results = $this->Tree->find('all');
		$after = $this->Tree->read(null, $data[$modelClass]['id']);

		$this->assertEqual($results, $expects);
		$this->assertEqual($before, $after);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testMoveInvalid method
 *
 * @access public
 * @return void
 */
	function testMoveInvalid() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$initialCount = $this->Tree->find('count');
		$data= $this->Tree->findByName('1.1');

		//$this->expectError('Trying to save a node under a none-existant node in TreeBehavior::beforeSave');
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->saveField($parentField, 999999);

		//$this->assertIdentical($saveSuccess, false);
		$laterCount = $this->Tree->find('count');
		$this->assertIdentical($initialCount, $laterCount);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testMoveSelfInvalid method
 *
 * @access public
 * @return void
 */
	function testMoveSelfInvalid() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$initialCount = $this->Tree->find('count');
		$data= $this->Tree->findByName('1.1');

		//$this->expectError('Trying to set a node to be the parent of itself in TreeBehavior::beforeSave');
		$this->Tree->id = $data[$modelClass]['id'];
		$saveSuccess = $this->Tree->saveField($parentField, $this->Tree->id);

		$this->assertIdentical($saveSuccess, false);
		$laterCount = $this->Tree->find('count');
		$this->assertIdentical($initialCount, $laterCount);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testMoveUpSuccess method
 *
 * @access public
 * @return void
 */
	function testMoveUpSuccess() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.2'), array('id'));
		$this->Tree->moveUp($data[$modelClass]['id']);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(array($modelClass => array('name' => '1.2', )),
			array($modelClass => array('name' => '1.1', )));
		$this->assertIdentical($result, $expected);
	}

/**
 * testMoveUpFail method
 *
 * @access public
 * @return void
 */
	function testMoveUpFail() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.1'));

		$this->Tree->moveUp($data[$modelClass]['id']);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(array($modelClass => array('name' => '1.1', )),
			array($modelClass => array('name' => '1.2', )));
		$this->assertIdentical($result, $expected);
	}

/**
 * testMoveUp2 method
 *
 * @access public
 * @return void
 */
	function testMoveUp2() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(1, 10);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.5'), array('id'));
		$this->Tree->moveUp($data[$modelClass]['id'], 2);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(
			array($modelClass => array('name' => '1.1', )),
			array($modelClass => array('name' => '1.2', )),
			array($modelClass => array('name' => '1.5', )),
			array($modelClass => array('name' => '1.3', )),
			array($modelClass => array('name' => '1.4', )),
			array($modelClass => array('name' => '1.6', )),
			array($modelClass => array('name' => '1.7', )),
			array($modelClass => array('name' => '1.8', )),
			array($modelClass => array('name' => '1.9', )),
			array($modelClass => array('name' => '1.10', )));
		$this->assertIdentical($result, $expected);
	}

/**
 * testMoveUpFirst method
 *
 * @access public
 * @return void
 */
	function testMoveUpFirst() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(1, 10);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.5'), array('id'));
		$this->Tree->moveUp($data[$modelClass]['id'], true);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(
			array($modelClass => array('name' => '1.5', )),
			array($modelClass => array('name' => '1.1', )),
			array($modelClass => array('name' => '1.2', )),
			array($modelClass => array('name' => '1.3', )),
			array($modelClass => array('name' => '1.4', )),
			array($modelClass => array('name' => '1.6', )),
			array($modelClass => array('name' => '1.7', )),
			array($modelClass => array('name' => '1.8', )),
			array($modelClass => array('name' => '1.9', )),
			array($modelClass => array('name' => '1.10', )));
		$this->assertIdentical($result, $expected);
	}

/**
 * testMoveDownSuccess method
 *
 * @access public
 * @return void
 */
	function testMoveDownSuccess() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.1'), array('id'));
		$this->Tree->moveDown($data[$modelClass]['id']);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(array($modelClass => array('name' => '1.2', )),
			array($modelClass => array('name' => '1.1', )));
		$this->assertIdentical($result, $expected);
	}

/**
 * testMoveDownFail method
 *
 * @access public
 * @return void
 */
	function testMoveDownFail() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.2'));
		$this->Tree->moveDown($data[$modelClass]['id']);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(array($modelClass => array('name' => '1.1', )),
			array($modelClass => array('name' => '1.2', )));
		$this->assertIdentical($result, $expected);
	}

/**
 * testMoveDownLast method
 *
 * @access public
 * @return void
 */
	function testMoveDownLast() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(1, 10);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.5'), array('id'));
		$this->Tree->moveDown($data[$modelClass]['id'], true);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(
			array($modelClass => array('name' => '1.1', )),
			array($modelClass => array('name' => '1.2', )),
			array($modelClass => array('name' => '1.3', )),
			array($modelClass => array('name' => '1.4', )),
			array($modelClass => array('name' => '1.6', )),
			array($modelClass => array('name' => '1.7', )),
			array($modelClass => array('name' => '1.8', )),
			array($modelClass => array('name' => '1.9', )),
			array($modelClass => array('name' => '1.10', )),
			array($modelClass => array('name' => '1.5', )));
		$this->assertIdentical($result, $expected);
	}

/**
 * testMoveDown2 method
 *
 * @access public
 * @return void
 */
	function testMoveDown2() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(1, 10);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.5'), array('id'));
		$this->Tree->moveDown($data[$modelClass]['id'], 2);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(
			array($modelClass => array('name' => '1.1', )),
			array($modelClass => array('name' => '1.2', )),
			array($modelClass => array('name' => '1.3', )),
			array($modelClass => array('name' => '1.4', )),
			array($modelClass => array('name' => '1.6', )),
			array($modelClass => array('name' => '1.7', )),
			array($modelClass => array('name' => '1.5', )),
			array($modelClass => array('name' => '1.8', )),
			array($modelClass => array('name' => '1.9', )),
			array($modelClass => array('name' => '1.10', )));
		$this->assertIdentical($result, $expected);
	}

/**
 * testSaveNoMove method
 *
 * @access public
 * @return void
 */
	function testSaveNoMove() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(1, 10);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.5'), array('id'));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->saveField('name', 'renamed');
		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(
			array($modelClass => array('name' => '1.1', )),
			array($modelClass => array('name' => '1.2', )),
			array($modelClass => array('name' => '1.3', )),
			array($modelClass => array('name' => '1.4', )),
			array($modelClass => array('name' => 'renamed', )),
			array($modelClass => array('name' => '1.6', )),
			array($modelClass => array('name' => '1.7', )),
			array($modelClass => array('name' => '1.8', )),
			array($modelClass => array('name' => '1.9', )),
			array($modelClass => array('name' => '1.10', )));
		$this->assertIdentical($result, $expected);
	}

/**
 * testMoveToRootAndMoveUp method
 *
 * @access public
 * @return void
 */
	function testMoveToRootAndMoveUp() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(1, 1);
		$data = $this->Tree->find(array($modelClass . '.name' => '1.1'), array('id'));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->save(array($parentField => null));

		$result = $this->Tree->verify();
		$this->assertIdentical($result, true);

		$this->Tree->moveup();

		$result = $this->Tree->find('all', array('fields' => 'name', 'order' => $modelClass . '.' . $leftField . ' ASC'));
		$expected = array(array($modelClass => array('name' => '1.1')),
			array($modelClass => array('name' => '1. Root')));
		$this->assertIdentical($result, $expected);
	}

/**
 * testDelete method
 *
 * @access public
 * @return void
 */
	function testDelete() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$initialCount = $this->Tree->find('count');
		$result = $this->Tree->findByName('1.1.1');

		$return = $this->Tree->delete($result[$modelClass]['id']);
		$this->assertEqual($return, true);

		$laterCount = $this->Tree->find('count');
		$this->assertEqual($initialCount - 1, $laterCount);

		$validTree= $this->Tree->verify();
		$this->assertIdentical($validTree, true);

		$initialCount = $this->Tree->find('count');
		$result= $this->Tree->findByName('1.1');

		$return = $this->Tree->delete($result[$modelClass]['id']);
		$this->assertEqual($return, true);

		$laterCount = $this->Tree->find('count');
		$this->assertEqual($initialCount - 2, $laterCount);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testRemove method
 *
 * @access public
 * @return void
 */
	function testRemove() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$initialCount = $this->Tree->find('count');
		$result = $this->Tree->findByName('1.1');

		$this->Tree->removeFromTree($result[$modelClass]['id']);

		$laterCount = $this->Tree->find('count');
		$this->assertEqual($initialCount, $laterCount);

		$children = $this->Tree->children($result[$modelClass][$parentField], true, array('name'));
		$expects = array(array($modelClass => array('name' => '1.1.1')),
			array($modelClass => array('name' => '1.1.2')),
			array($modelClass => array('name' => '1.2')));
		$this->assertEqual($children, $expects);

		$topNodes = $this->Tree->children(false, true,array('name'));
		$expects = array(array($modelClass => array('name' => '1. Root')),
			array($modelClass => array('name' => '1.1')));
		$this->assertEqual($topNodes, $expects);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testRemoveLastTopParent method
 *
 * @access public
 * @return void
 */
	function testRemoveLastTopParent() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$initialCount = $this->Tree->find('count');
		$initialTopNodes = $this->Tree->childCount(false);

		$result = $this->Tree->findByName('1. Root');
		$this->Tree->removeFromTree($result[$modelClass]['id']);

		$laterCount = $this->Tree->find('count');
		$laterTopNodes = $this->Tree->childCount(false);

		$this->assertEqual($initialCount, $laterCount);
		$this->assertEqual($initialTopNodes, $laterTopNodes);

		$topNodes = $this->Tree->children(false, true,array('name'));
		$expects = array(array($modelClass => array('name' => '1.1')),
			array($modelClass => array('name' => '1.2')),
			array($modelClass => array('name' => '1. Root')));

		$this->assertEqual($topNodes, $expects);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testRemoveNoChildren method
 *
 * @return void
 * @access public
 */
	function testRemoveNoChildren() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$initialCount = $this->Tree->find('count');

		$result = $this->Tree->findByName('1.1.1');
		$this->Tree->removeFromTree($result[$modelClass]['id']);

		$laterCount = $this->Tree->find('count');
		$this->assertEqual($initialCount, $laterCount);

		$nodes = $this->Tree->find('list', array('order' => $leftField));
		$expects = array(
			1 => '1. Root',
			2 => '1.1',
			4 => '1.1.2',
			5 => '1.2',
			6 => '1.2.1',
			7 => '1.2.2',
			3 => '1.1.1',
		);

		$this->assertEqual($nodes, $expects);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testRemoveAndDelete method
 *
 * @access public
 * @return void
 */
	function testRemoveAndDelete() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$initialCount = $this->Tree->find('count');
		$result = $this->Tree->findByName('1.1');

		$this->Tree->removeFromTree($result[$modelClass]['id'], true);

		$laterCount = $this->Tree->find('count');
		$this->assertEqual($initialCount-1, $laterCount);

		$children = $this->Tree->children($result[$modelClass][$parentField], true, array('name'), $leftField . ' asc');
		$expects= array(array($modelClass => array('name' => '1.1.1')),
			array($modelClass => array('name' => '1.1.2')),
			array($modelClass => array('name' => '1.2')));
		$this->assertEqual($children, $expects);

		$topNodes = $this->Tree->children(false, true,array('name'));
		$expects = array(array($modelClass => array('name' => '1. Root')));
		$this->assertEqual($topNodes, $expects);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testRemoveAndDeleteNoChildren method
 *
 * @return void
 * @access public
 */
	function testRemoveAndDeleteNoChildren() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$initialCount = $this->Tree->find('count');

		$result = $this->Tree->findByName('1.1.1');
		$this->Tree->removeFromTree($result[$modelClass]['id'], true);

		$laterCount = $this->Tree->find('count');
		$this->assertEqual($initialCount - 1, $laterCount);

		$nodes = $this->Tree->find('list', array('order' => $leftField));
		$expects = array(
			1 => '1. Root',
			2 => '1.1',
			4 => '1.1.2',
			5 => '1.2',
			6 => '1.2.1',
			7 => '1.2.2',
		);
		$this->assertEqual($nodes, $expects);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testChildren method
 *
 * @access public
 * @return void
 */
	function testChildren() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find(array($modelClass . '.name' => '1. Root'));
		$this->Tree->id= $data[$modelClass]['id'];

		$direct = $this->Tree->children(null, true, array('id', 'name', $parentField, $leftField, $rightField));
		$expects = array(array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 7)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 8, $rightField => 13)));
		$this->assertEqual($direct, $expects);

		$total = $this->Tree->children(null, null, array('id', 'name', $parentField, $leftField, $rightField));
		$expects = array(array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 7)),
			array($modelClass => array('id' => 3, 'name' => '1.1.1', $parentField => 2, $leftField => 3, $rightField => 4)),
			array($modelClass => array('id' => 4, 'name' => '1.1.2', $parentField => 2, $leftField => 5, $rightField => 6)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 8, $rightField => 13)),
			array($modelClass => array( 'id' => 6, 'name' => '1.2.1', $parentField => 5, $leftField => 9, $rightField => 10)),
			array($modelClass => array('id' => 7, 'name' => '1.2.2', $parentField => 5, $leftField => 11, $rightField => 12)));
		$this->assertEqual($total, $expects);

		$this->assertEqual(array(), $this->Tree->children(10000));
	}

/**
 * testCountChildren method
 *
 * @access public
 * @return void
 */
	function testCountChildren() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find(array($modelClass . '.name' => '1. Root'));
		$this->Tree->id = $data[$modelClass]['id'];

		$direct = $this->Tree->childCount(null, true);
		$this->assertEqual($direct, 2);

		$total = $this->Tree->childCount();
		$this->assertEqual($total, 6);
	}

/**
 * testGetParentNode method
 *
 * @access public
 * @return void
 */
	function testGetParentNode() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.2.2'));
		$this->Tree->id= $data[$modelClass]['id'];

		$result = $this->Tree->getparentNode(null, array('name'));
		$expects = array($modelClass => array('name' => '1.2'));
		$this->assertIdentical($result, $expects);
	}

/**
 * testGetPath method
 *
 * @access public
 * @return void
 */
	function testGetPath() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.2.2'));
		$this->Tree->id= $data[$modelClass]['id'];

		$result = $this->Tree->getPath(null, array('name'));
		$expects = array(array($modelClass => array('name' => '1. Root')),
			array($modelClass => array('name' => '1.2')),
			array($modelClass => array('name' => '1.2.2')));
		$this->assertIdentical($result, $expects);
	}

/**
 * testNoAmbiguousColumn method
 *
 * @access public
 * @return void
 */
	function testNoAmbiguousColumn() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->bindModel(array('belongsTo' => array('Dummy' =>
			array('className' => $modelClass, 'foreignKey' => $parentField, 'conditions' => array('Dummy.id' => null)))), false);
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find(array($modelClass . '.name' => '1. Root'));
		$this->Tree->id= $data[$modelClass]['id'];

		$direct = $this->Tree->children(null, true, array('id', 'name', $parentField, $leftField, $rightField));
		$expects = array(array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 7)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 8, $rightField => 13)));
		$this->assertEqual($direct, $expects);

		$total = $this->Tree->children(null, null, array('id', 'name', $parentField, $leftField, $rightField));
		$expects = array(
			array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 7)),
			array($modelClass => array('id' => 3, 'name' => '1.1.1', $parentField => 2, $leftField => 3, $rightField => 4)),
			array($modelClass => array('id' => 4, 'name' => '1.1.2', $parentField => 2, $leftField => 5, $rightField => 6)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 8, $rightField => 13)),
			array($modelClass => array( 'id' => 6, 'name' => '1.2.1', $parentField => 5, $leftField => 9, $rightField => 10)),
			array($modelClass => array('id' => 7, 'name' => '1.2.2', $parentField => 5, $leftField => 11, $rightField => 12))
		);
		$this->assertEqual($total, $expects);
	}

/**
 * testReorderTree method
 *
 * @access public
 * @return void
 */
	function testReorderTree() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(3, 3);
		$nodes = $this->Tree->find('list', array('order' => $leftField));

		$data = $this->Tree->find(array($modelClass . '.name' => '1.1'), array('id'));
		$this->Tree->moveDown($data[$modelClass]['id']);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.2.1'), array('id'));
		$this->Tree->moveDown($data[$modelClass]['id']);

		$data = $this->Tree->find(array($modelClass . '.name' => '1.3.2.2'), array('id'));
		$this->Tree->moveDown($data[$modelClass]['id']);

		$unsortedNodes = $this->Tree->find('list', array('order' => $leftField));
		$this->assertNotIdentical($nodes, $unsortedNodes);

		$this->Tree->reorder();
		$sortedNodes = $this->Tree->find('list', array('order' => $leftField));
		$this->assertIdentical($nodes, $sortedNodes);
	}

/**
 * test reordering large-ish trees with cacheQueries = true.
 * This caused infinite loops when moving down elements as stale data is returned
 * from the memory cache
 *
 * @access public
 * @return void
 */
	function testReorderBigTreeWithQueryCaching() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 10);

		$original = $this->Tree->cacheQueries;
		$this->Tree->cacheQueries = true;
		$this->Tree->reorder(array('field' => 'name', 'direction' => 'DESC'));
		$this->assertTrue($this->Tree->cacheQueries, 'cacheQueries was not restored after reorder(). %s');
		$this->Tree->cacheQueries = $original;
	}
/**
 * testGenerateTreeListWithSelfJoin method
 *
 * @access public
 * @return void
 */
	function testGenerateTreeListWithSelfJoin() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->bindModel(array('belongsTo' => array('Dummy' =>
			array('className' => $modelClass, 'foreignKey' => $parentField, 'conditions' => array('Dummy.id' => null)))), false);
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->generateTreeList();
		$expected = array(1 => '1. Root', 2 => '_1.1', 3 => '__1.1.1', 4 => '__1.1.2', 5 => '_1.2', 6 => '__1.2.1', 7 => '__1.2.2');
		$this->assertIdentical($result, $expected);
	}

/**
 * testArraySyntax method
 *
 * @access public
 * @return void
 */
	function testArraySyntax() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(3, 3);
		$this->assertIdentical($this->Tree->childCount(2), $this->Tree->childCount(array('id' => 2)));
		$this->assertIdentical($this->Tree->getParentNode(2), $this->Tree->getParentNode(array('id' => 2)));
		$this->assertIdentical($this->Tree->getPath(4), $this->Tree->getPath(array('id' => 4)));
	}
}

/**
 * ScopedTreeTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.behaviors
 */
class ScopedTreeTest extends NumberTreeTest {

/**
 * settings property
 *
 * @var array
 * @access public
 */
	var $settings = array(
		'modelClass' => 'FlagTree',
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
	var $fixtures = array('core.flag_tree', 'core.ad', 'core.campaign', 'core.translate', 'core.number_tree_two');

/**
 * testStringScope method
 *
 * @access public
 * @return void
 */
	function testStringScope() {
		$this->Tree =& new FlagTree();
		$this->Tree->initialize(2, 3);

		$this->Tree->id = 1;
		$this->Tree->saveField('flag', 1);
		$this->Tree->id = 2;
		$this->Tree->saveField('flag', 1);

		$result = $this->Tree->children();
		$expected = array(
			array('FlagTree' => array('id' => '3', 'name' => '1.1.1', 'parent_id' => '2', 'lft' => '3', 'rght' => '4', 'flag' => '0')),
			array('FlagTree' => array('id' => '4', 'name' => '1.1.2', 'parent_id' => '2', 'lft' => '5', 'rght' => '6', 'flag' => '0')),
			array('FlagTree' => array('id' => '5', 'name' => '1.1.3', 'parent_id' => '2', 'lft' => '7', 'rght' => '8', 'flag' => '0'))
		);
		$this->assertEqual($result, $expected);

		$this->Tree->Behaviors->attach('Tree', array('scope' => 'FlagTree.flag = 1'));
		$this->assertEqual($this->Tree->children(), array());

		$this->Tree->id = 1;
		$this->Tree->Behaviors->attach('Tree', array('scope' => 'FlagTree.flag = 1'));

		$result = $this->Tree->children();
		$expected = array(array('FlagTree' => array('id' => '2', 'name' => '1.1', 'parent_id' => '1', 'lft' => '2', 'rght' => '9', 'flag' => '1')));
		$this->assertEqual($result, $expected);

		$this->assertTrue($this->Tree->delete());
		$this->assertEqual($this->Tree->find('count'), 11);
	}

/**
 * testArrayScope method
 *
 * @access public
 * @return void
 */
	function testArrayScope() {
		$this->Tree =& new FlagTree();
		$this->Tree->initialize(2, 3);

		$this->Tree->id = 1;
		$this->Tree->saveField('flag', 1);
		$this->Tree->id = 2;
		$this->Tree->saveField('flag', 1);

		$result = $this->Tree->children();
		$expected = array(
			array('FlagTree' => array('id' => '3', 'name' => '1.1.1', 'parent_id' => '2', 'lft' => '3', 'rght' => '4', 'flag' => '0')),
			array('FlagTree' => array('id' => '4', 'name' => '1.1.2', 'parent_id' => '2', 'lft' => '5', 'rght' => '6', 'flag' => '0')),
			array('FlagTree' => array('id' => '5', 'name' => '1.1.3', 'parent_id' => '2', 'lft' => '7', 'rght' => '8', 'flag' => '0'))
		);
		$this->assertEqual($result, $expected);

		$this->Tree->Behaviors->attach('Tree', array('scope' => array('FlagTree.flag' => 1)));
		$this->assertEqual($this->Tree->children(), array());

		$this->Tree->id = 1;
		$this->Tree->Behaviors->attach('Tree', array('scope' => array('FlagTree.flag' => 1)));

		$result = $this->Tree->children();
		$expected = array(array('FlagTree' => array('id' => '2', 'name' => '1.1', 'parent_id' => '1', 'lft' => '2', 'rght' => '9', 'flag' => '1')));
		$this->assertEqual($result, $expected);

		$this->assertTrue($this->Tree->delete());
		$this->assertEqual($this->Tree->find('count'), 11);
	}

/**
 * testMoveUpWithScope method
 *
 * @access public
 * @return void
 */
	function testMoveUpWithScope() {
		$this->Ad =& new Ad();
		$this->Ad->Behaviors->attach('Tree', array('scope'=>'Campaign'));
		$this->Ad->moveUp(6);

		$this->Ad->id = 4;
		$result = $this->Ad->children();
		$this->assertEqual(Set::extract('/Ad/id', $result), array(6, 5));
		$this->assertEqual(Set::extract('/Campaign/id', $result), array(2, 2));
	}

/**
 * testMoveDownWithScope method
 *
 * @access public
 * @return void
 */
	function testMoveDownWithScope() {
		$this->Ad =& new Ad();
		$this->Ad->Behaviors->attach('Tree', array('scope' => 'Campaign'));
		$this->Ad->moveDown(6);

		$this->Ad->id = 4;
		$result = $this->Ad->children();
		$this->assertEqual(Set::extract('/Ad/id', $result), array(5, 6));
		$this->assertEqual(Set::extract('/Campaign/id', $result), array(2, 2));
	}

/**
 * Tests the interaction (non-interference) between TreeBehavior and other behaviors with respect
 * to callback hooks
 *
 * @access public
 * @return void
 */
	function testTranslatingTree() {
		$this->Tree =& new FlagTree();
		$this->Tree->cacheQueries = false;
		$this->Tree->translateModel = 'TranslateTreeTestModel';
		$this->Tree->Behaviors->attach('Translate', array('name'));

		//Save
		$this->Tree->locale = 'eng';
		$data = array('FlagTree' => array(
			'name' => 'name #1',
			'locale' => 'eng',
			'parent_id' => null,
		));
		$this->Tree->save($data);
		$result = $this->Tree->find('all');
		$expected = array(array('FlagTree' => array(
			'id' => 1,
			'name' => 'name #1',
			'parent_id' => null,
			'lft' => 1,
			'rght' => 2,
			'flag' => 0,
			'locale' => 'eng',
		)));
		$this->assertEqual($result, $expected);

		//update existing record, same locale
		$this->Tree->create();
		$data['FlagTree']['name'] = 'Named 2';
		$this->Tree->id = 1;
		$this->Tree->save($data);
		$result = $this->Tree->find('all');
		$expected = array(array('FlagTree' => array(
			'id' => 1,
			'name' => 'Named 2',
			'parent_id' => null,
			'lft' => 1,
			'rght' => 2,
			'flag' => 0,
			'locale' => 'eng',
		)));
		$this->assertEqual($result, $expected);

		//update different locale, same record
		$this->Tree->create();
		$this->Tree->locale = 'deu';
		$this->Tree->id = 1;
		$data = array('FlagTree' => array(
			'id' => 1,
			'parent_id' => null,
			'name' => 'namen #1',
			'locale' => 'deu',
		));
		$this->Tree->save($data);

		$this->Tree->locale = 'deu';
		$result = $this->Tree->find('all');
		$expected = array(array('FlagTree' => array(
			'id' => 1,
			'name' => 'namen #1',
			'parent_id' => null,
			'lft' => 1,
			'rght' => 2,
			'flag' => 0,
			'locale' => 'deu',
		)));
		$this->assertEqual($result, $expected);

		//Save with bindTranslation
		$this->Tree->locale = 'eng';
		$data = array(
			'name' => array('eng' => 'New title', 'spa' => 'Nuevo leyenda'),
			'parent_id' => null
		);
		$this->Tree->create($data);
		$this->Tree->save();

		$this->Tree->unbindTranslation();
		$translations = array('name' => 'Name');
		$this->Tree->bindTranslation($translations, false);
		$this->Tree->locale = array('eng', 'spa');

		$result = $this->Tree->read();
		$expected = array(
			'FlagTree' => array('id' => 2, 'parent_id' => null, 'locale' => 'eng', 'name' => 'New title', 'flag' => 0, 'lft' => 3, 'rght' => 4),
			'Name' => array(
			array('id' => 21, 'locale' => 'eng', 'model' => 'FlagTree', 'foreign_key' => 2, 'field' => 'name', 'content' => 'New title'),
			array('id' => 22, 'locale' => 'spa', 'model' => 'FlagTree', 'foreign_key' => 2, 'field' => 'name', 'content' => 'Nuevo leyenda')
			),
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testGenerateTreeListWithSelfJoin method
 *
 * @return void
 * @access public
 */
	function testAliasesWithScopeInTwoTreeAssociations() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$this->TreeTwo =& new NumberTreeTwo();

		$record = $this->Tree->find('first');

		$this->Tree->bindModel(array(
			'hasMany' => array(
				'SecondTree' => array(
					'className' => 'NumberTreeTwo',
					'foreignKey' => 'number_tree_id'
				)
			)
		));
		$this->TreeTwo->bindModel(array(
			'belongsTo' => array(
				'FirstTree' => array(
					'className' => $modelClass,
					'foreignKey' => 'number_tree_id'
				)
			)
		));
		$this->TreeTwo->Behaviors->attach('Tree', array(
			'scope' => 'FirstTree'
		));

		$data = array(
			'NumberTreeTwo' => array(
				'name' => 'First',
				'number_tree_id' => $record['FlagTree']['id']
			)
		);
		$this->TreeTwo->create();
		$this->assertTrue($this->TreeTwo->save($data));

		$result = $this->TreeTwo->find('first');
		$expected = array('NumberTreeTwo' => array(
			'id' => 1,
			'name' => 'First',
			'number_tree_id' => $record['FlagTree']['id'],
			'parent_id' => null,
			'lft' => 1,
			'rght' => 2
		));
		$this->assertEqual($result, $expected);
	}
}

/**
 * AfterTreeTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.behaviors
 */
class AfterTreeTest extends NumberTreeTest {

/**
 * settings property
 *
 * @var array
 * @access public
 */
	var $settings = array(
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
	var $fixtures = array('core.after_tree');

/**
 * Tests the afterSave callback in the model
 *
 * @access public
 * @return void
 */
	function testAftersaveCallback() {
		$this->Tree =& new AfterTree();

		$expected = array('AfterTree' => array('name' => 'Six and One Half Changed in AfterTree::afterSave() but not in database', 'parent_id' => 6, 'lft' => 11, 'rght' => 12));
		$result = $this->Tree->save(array('AfterTree' => array('name' => 'Six and One Half', 'parent_id' => 6)));
		$this->assertEqual($result, $expected);

		$expected = array('AfterTree' => array('name' => 'Six and One Half', 'parent_id' => 6, 'lft' => 11, 'rght' => 12, 'id' => 8));
		$result = $this->Tree->find('all');
		$this->assertEqual($result[7], $expected);
	}
}

/**
 * UnconventionalTreeTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.behaviors
 */
class UnconventionalTreeTest extends NumberTreeTest {

/**
 * settings property
 *
 * @var array
 * @access public
 */
	var $settings = array(
		'modelClass' => 'UnconventionalTree',
		'leftField' => 'left',
		'rightField' => 'right',
		'parentField' => 'join'
	);

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array('core.unconventional_tree');
}

/**
 * UuidTreeTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.behaviors
 */
class UuidTreeTest extends NumberTreeTest {

/**
 * settings property
 *
 * @var array
 * @access public
 */
	var $settings = array(
		'modelClass' => 'UuidTree',
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
	var $fixtures = array('core.uuid_tree');

/**
 * testMovePromote method
 *
 * @return void
 * @access public
 */
	function testMovePromote() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find(array($modelClass . '.name' => '1. Root'));
		$parent_id = $parent[$modelClass]['id'];

		$data = $this->Tree->find(array($modelClass . '.name' => '1.1.1'), array('id'));
		$this->Tree->id= $data[$modelClass]['id'];
		$this->Tree->saveField($parentField, $parent_id);
		$direct = $this->Tree->children($parent_id, true, array('name', $leftField, $rightField));
		$expects = array(array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 5)),
			array($modelClass => array('name' => '1.2', $leftField => 6, $rightField => 11)),
			array($modelClass => array('name' => '1.1.1', $leftField => 12, $rightField => 13)));
		$this->assertEqual($direct, $expects);
		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testMoveWithWhitelist method
 *
 * @return void
 * @access public
 */
	function testMoveWithWhitelist() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find(array($modelClass . '.name' => '1. Root'));
		$parent_id = $parent[$modelClass]['id'];

		$data = $this->Tree->find(array($modelClass . '.name' => '1.1.1'), array('id'));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->whitelist = array($parentField, 'name', 'description');
		$this->Tree->saveField($parentField, $parent_id);

		$result = $this->Tree->children($parent_id, true, array('name', $leftField, $rightField));
		$expected = array(array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 5)),
			array($modelClass => array('name' => '1.2', $leftField => 6, $rightField => 11)),
			array($modelClass => array('name' => '1.1.1', $leftField => 12, $rightField => 13)));
		$this->assertEqual($result, $expected);
		$this->assertTrue($this->Tree->verify());
	}

/**
 * testRemoveNoChildren method
 *
 * @return void
 * @access public
 */
	function testRemoveNoChildren() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$initialCount = $this->Tree->find('count');

		$result = $this->Tree->findByName('1.1.1');
		$this->Tree->removeFromTree($result[$modelClass]['id']);

		$laterCount = $this->Tree->find('count');
		$this->assertEqual($initialCount, $laterCount);

		$nodes = $this->Tree->find('list', array('order' => $leftField));
		$expects = array(
			'1. Root',
			'1.1',
			'1.1.2',
			'1.2',
			'1.2.1',
			'1.2.2',
			'1.1.1',
		);

		$this->assertEqual(array_values($nodes), $expects);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testRemoveAndDeleteNoChildren method
 *
 * @return void
 * @access public
 */
	function testRemoveAndDeleteNoChildren() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);
		$initialCount = $this->Tree->find('count');

		$result = $this->Tree->findByName('1.1.1');
		$this->Tree->removeFromTree($result[$modelClass]['id'], true);

		$laterCount = $this->Tree->find('count');
		$this->assertEqual($initialCount - 1, $laterCount);

		$nodes = $this->Tree->find('list', array('order' => $leftField));
		$expects = array(
			'1. Root',
			'1.1',
			'1.1.2',
			'1.2',
			'1.2.1',
			'1.2.2',
		);
		$this->assertEqual(array_values($nodes), $expects);

		$validTree = $this->Tree->verify();
		$this->assertIdentical($validTree, true);
	}

/**
 * testChildren method
 *
 * @return void
 * @access public
 */
	function testChildren() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find(array($modelClass . '.name' => '1. Root'));
		$this->Tree->id = $data[$modelClass]['id'];

		$direct = $this->Tree->children(null, true, array('name', $leftField, $rightField));
		$expects = array(array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 7)),
			array($modelClass => array('name' => '1.2', $leftField => 8, $rightField => 13)));
		$this->assertEqual($direct, $expects);

		$total = $this->Tree->children(null, null, array('name', $leftField, $rightField));
		$expects = array(array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 7)),
			array($modelClass => array('name' => '1.1.1', $leftField => 3, $rightField => 4)),
			array($modelClass => array('name' => '1.1.2', $leftField => 5, $rightField => 6)),
			array($modelClass => array('name' => '1.2', $leftField => 8, $rightField => 13)),
			array($modelClass => array('name' => '1.2.1', $leftField => 9, $rightField => 10)),
			array($modelClass => array('name' => '1.2.2', $leftField => 11, $rightField => 12)));
		$this->assertEqual($total, $expects);
	}

/**
 * testNoAmbiguousColumn method
 *
 * @return void
 * @access public
 */
	function testNoAmbiguousColumn() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->initialize(2, 2);

		$this->Tree->bindModel(array('belongsTo' => array('Dummy' =>
			array('className' => $modelClass, 'foreignKey' => $parentField, 'conditions' => array('Dummy.id' => null)))), false);

		$data = $this->Tree->find(array($modelClass . '.name' => '1. Root'));
		$this->Tree->id = $data[$modelClass]['id'];

		$direct = $this->Tree->children(null, true, array('name', $leftField, $rightField));
		$expects = array(array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 7)),
			array($modelClass => array('name' => '1.2', $leftField => 8, $rightField => 13)));
		$this->assertEqual($direct, $expects);

		$total = $this->Tree->children(null, null, array('name', $leftField, $rightField));
		$expects = array(
			array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 7)),
			array($modelClass => array('name' => '1.1.1', $leftField => 3, $rightField => 4)),
			array($modelClass => array('name' => '1.1.2', $leftField => 5, $rightField => 6)),
			array($modelClass => array('name' => '1.2', $leftField => 8, $rightField => 13)),
			array($modelClass => array('name' => '1.2.1', $leftField => 9, $rightField => 10)),
			array($modelClass => array('name' => '1.2.2', $leftField => 11, $rightField => 12))
		);
		$this->assertEqual($total, $expects);
	}

/**
 * testGenerateTreeListWithSelfJoin method
 *
 * @return void
 * @access public
 */
	function testGenerateTreeListWithSelfJoin() {
		extract($this->settings);
		$this->Tree =& new $modelClass();
		$this->Tree->bindModel(array('belongsTo' => array('Dummy' =>
			array('className' => $modelClass, 'foreignKey' => $parentField, 'conditions' => array('Dummy.id' => null)))), false);
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->generateTreeList();
		$expected = array('1. Root', '_1.1', '__1.1.1', '__1.1.2', '_1.2', '__1.2.1', '__1.2.2');
		$this->assertIdentical(array_values($result), $expected);
	}
}
