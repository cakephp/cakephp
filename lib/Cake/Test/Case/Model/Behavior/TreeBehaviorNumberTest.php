<?php
/**
 * TreeBehaviorNumberTest file
 *
 * This is the basic Tree behavior test
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.5330
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
require_once dirname(dirname(__FILE__)) . DS . 'models.php';

/**
 * TreeBehaviorNumberTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class TreeBehaviorNumberTest extends CakeTestCase {

/**
 * Whether backup global state for each test method or not
 *
 * @var boolean
 */
	public $backupGlobals = false;

/**
 * settings property
 *
 * @var array
 */
	public $settings = array(
		'modelClass' => 'NumberTree',
		'leftField' => 'lft',
		'rightField' => 'rght',
		'parentField' => 'parent_id'
	);

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.number_tree', 'core.person');

/**
 * testInitialize method
 *
 * @return void
 */
	public function testInitialize() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->find('count');
		$this->assertEquals(7, $result);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testDetectInvalidLeft method
 *
 * @return void
 */
	public function testDetectInvalidLeft() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->findByName('1.1');

		$save[$modelClass]['id'] = $result[$modelClass]['id'];
		$save[$modelClass][$leftField] = 0;

		$this->Tree->create();
		$this->Tree->save($save);
		$result = $this->Tree->verify();
		$this->assertNotSame($result, true);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * testDetectInvalidRight method
 *
 * @return void
 */
	public function testDetectInvalidRight() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->findByName('1.1');

		$save[$modelClass]['id'] = $result[$modelClass]['id'];
		$save[$modelClass][$rightField] = 0;

		$this->Tree->create();
		$this->Tree->save($save);
		$result = $this->Tree->verify();
		$this->assertNotSame($result, true);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * testDetectInvalidParent method
 *
 * @return void
 */
	public function testDetectInvalidParent() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->findByName('1.1');

		// Bypass behavior and any other logic
		$this->Tree->updateAll(array($parentField => null), array('id' => $result[$modelClass]['id']));

		$result = $this->Tree->verify();
		$this->assertNotSame($result, true);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * testDetectNoneExistentParent method
 *
 * @return void
 */
	public function testDetectNoneExistentParent() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->findByName('1.1');
		$this->Tree->updateAll(array($parentField => 999999), array('id' => $result[$modelClass]['id']));

		$result = $this->Tree->verify();
		$this->assertNotSame($result, true);

		$result = $this->Tree->recover('MPTT');
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * testRecoverUsingParentMode method
 *
 * @return void
 */
	public function testRecoverUsingParentMode() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->Behaviors->disable('Tree');

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Main', $parentField => null, $leftField => 0, $rightField => 0));
		$node1 = $this->Tree->id;

		$this->Tree->create();
		$this->Tree->save(array('name' => 'About Us', $parentField => $node1, $leftField => 0, $rightField => 0));
		$node11 = $this->Tree->id;

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Programs', $parentField => $node1, $leftField => 0, $rightField => 0));
		$node12 = $this->Tree->id;

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Mission and History', $parentField => $node11, $leftField => 0, $rightField => 0));

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Overview', $parentField => $node12, $leftField => 0, $rightField => 0));

		$this->Tree->Behaviors->enable('Tree');

		$result = $this->Tree->verify();
		$this->assertNotSame($result, true);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);

		$result = $this->Tree->find('first', array(
			'fields' => array('name', $parentField, $leftField, $rightField),
			'conditions' => array('name' => 'Main'),
			'recursive' => -1
		));
		$expected = array(
			$modelClass => array(
				'name' => 'Main',
				$parentField => null,
				$leftField => 1,
				$rightField => 10
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testRecoverUsingParentModeAndDelete method
 *
 * @return void
 */
	public function testRecoverUsingParentModeAndDelete() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->Behaviors->disable('Tree');

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Main', $parentField => null, $leftField => 0, $rightField => 0));
		$node1 = $this->Tree->id;

		$this->Tree->create();
		$this->Tree->save(array('name' => 'About Us', $parentField => $node1, $leftField => 0, $rightField => 0));
		$node11 = $this->Tree->id;

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Programs', $parentField => $node1, $leftField => 0, $rightField => 0));
		$node12 = $this->Tree->id;

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Mission and History', $parentField => $node11, $leftField => 0, $rightField => 0));

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Overview', $parentField => $node12, $leftField => 0, $rightField => 0));

		$this->Tree->create();
		$this->Tree->save(array('name' => 'Lost', $parentField => 9, $leftField => 0, $rightField => 0));

		$this->Tree->Behaviors->enable('Tree');

		$this->Tree->bindModel(array('belongsTo' => array('Parent' => array(
			'className' => $this->Tree->name,
			'foreignKey' => $parentField
		))));
		$this->Tree->bindModel(array('hasMany' => array('Child' => array(
			'className' => $this->Tree->name,
			'foreignKey' => $parentField
		))));

		$result = $this->Tree->verify();
		$this->assertNotSame($result, true);

		$count = $this->Tree->find('count');
		$this->assertEquals(6, $count);

		$result = $this->Tree->recover('parent', 'delete');
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);

		$count = $this->Tree->find('count');
		$this->assertEquals(5, $count);

		$result = $this->Tree->find('first', array(
			'fields' => array('name', $parentField, $leftField, $rightField),
			'conditions' => array('name' => 'Main'),
			'recursive' => -1
		));
		$expected = array(
			$modelClass => array(
				'name' => 'Main',
				$parentField => null,
				$leftField => 1,
				$rightField => 10
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testRecoverFromMissingParent method
 *
 * @return void
 */
	public function testRecoverFromMissingParent() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->findByName('1.1');
		$this->Tree->updateAll(array($parentField => 999999), array('id' => $result[$modelClass]['id']));

		$result = $this->Tree->verify();
		$this->assertNotSame($result, true);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * testDetectInvalidParents method
 *
 * @return void
 */
	public function testDetectInvalidParents() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$this->Tree->updateAll(array($parentField => null));

		$result = $this->Tree->verify();
		$this->assertNotSame($result, true);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * testDetectInvalidLftsRghts method
 *
 * @return void
 */
	public function testDetectInvalidLftsRghts() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$this->Tree->updateAll(array($leftField => 0, $rightField => 0));

		$result = $this->Tree->verify();
		$this->assertNotSame($result, true);

		$this->Tree->recover();

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * Reproduces a situation where a single node has lft= rght, and all other lft and rght fields follow sequentially
 *
 * @return void
 */
	public function testDetectEqualLftsRghts() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(1, 3);

		$result = $this->Tree->findByName('1.1');
		$this->Tree->updateAll(array($rightField => $result[$modelClass][$leftField]), array('id' => $result[$modelClass]['id']));
		$this->Tree->updateAll(array($leftField => $this->Tree->escapeField($leftField) . ' -1'),
			array($leftField . ' >' => $result[$modelClass][$leftField]));
		$this->Tree->updateAll(array($rightField => $this->Tree->escapeField($rightField) . ' -1'),
			array($rightField . ' >' => $result[$modelClass][$leftField]));

		$result = $this->Tree->verify();
		$this->assertNotSame($result, true);

		$result = $this->Tree->recover();
		$this->assertTrue($result);

		$result = $this->Tree->verify();
		$this->assertTrue($result);
	}

/**
 * testAddOrphan method
 *
 * @return void
 */
	public function testAddOrphan() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$this->Tree->create();
		$this->Tree->save(array($modelClass => array('name' => 'testAddOrphan', $parentField => null)));
		$result = $this->Tree->find('first', array('fields' => array('name', $parentField), 'order' => $modelClass . '.' . $leftField . ' desc'));
		$expected = array($modelClass => array('name' => 'testAddOrphan', $parentField => null));
		$this->assertEquals($expected, $result);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testAddMiddle method
 *
 * @return void
 */
	public function testAddMiddle() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.1')));
		$initialCount = $this->Tree->find('count');

		$this->Tree->create();
		$result = $this->Tree->save(array($modelClass => array('name' => 'testAddMiddle', $parentField => $data[$modelClass]['id'])));
		$expected = array_merge(array($modelClass => array('name' => 'testAddMiddle', $parentField => '2')), $result);
		$this->assertSame($expected, $result);

		$laterCount = $this->Tree->find('count');
		$this->assertEquals($initialCount + 1, $laterCount);

		$children = $this->Tree->children($data[$modelClass]['id'], true, array('name'));
		$expects = array(array($modelClass => array('name' => '1.1.1')),
			array($modelClass => array('name' => '1.1.2')),
			array($modelClass => array('name' => 'testAddMiddle')));
		$this->assertSame($children, $expects);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testAddWithPreSpecifiedId method
 *
 * @return void
 */
	public function testAddWithPreSpecifiedId() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array(
			'fields' => array('id'),
			'conditions' => array($modelClass . '.name' => '1.1')
		));

		$this->Tree->create();
		$result = $this->Tree->save(array($modelClass => array(
			'id' => 100,
			'name' => 'testAddMiddle',
			$parentField => $data[$modelClass]['id'])
		));
		$expected = array_merge(
			array($modelClass => array('id' => 100, 'name' => 'testAddMiddle', $parentField => '2')),
			$result
		);
		$this->assertSame($expected, $result);

		$this->assertTrue($this->Tree->verify());
	}

/**
 * testAddInvalid method
 *
 * @return void
 */
	public function testAddInvalid() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$initialCount = $this->Tree->find('count');
		//$this->expectError('Trying to save a node under a none-existant node in TreeBehavior::beforeSave');

		$this->Tree->create();
		$saveSuccess = $this->Tree->save(array($modelClass => array('name' => 'testAddInvalid', $parentField => 99999)));
		$this->assertFalse($saveSuccess);

		$laterCount = $this->Tree->find('count');
		$this->assertSame($initialCount, $laterCount);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testAddNotIndexedByModel method
 *
 * @return void
 */
	public function testAddNotIndexedByModel() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$this->Tree->create();
		$this->Tree->save(array('name' => 'testAddNotIndexed', $parentField => null));
		$result = $this->Tree->find('first', array('fields' => array('name', $parentField), 'order' => $modelClass . '.' . $leftField . ' desc'));
		$expected = array($modelClass => array('name' => 'testAddNotIndexed', $parentField => null));
		$this->assertEquals($expected, $result);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testMovePromote method
 *
 * @return void
 */
	public function testMovePromote() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1. Root')));
		$parentId = $parent[$modelClass]['id'];

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.1.1')));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->saveField($parentField, $parentId);
		$direct = $this->Tree->children($parentId, true, array('id', 'name', $parentField, $leftField, $rightField));
		$expects = array(array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 5)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 6, $rightField => 11)),
			array($modelClass => array('id' => 3, 'name' => '1.1.1', $parentField => 1, $leftField => 12, $rightField => 13)));
		$this->assertEquals($direct, $expects);
		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testMoveWithWhitelist method
 *
 * @return void
 */
	public function testMoveWithWhitelist() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1. Root')));
		$parentId = $parent[$modelClass]['id'];

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.1.1')));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->whitelist = array($parentField, 'name', 'description');
		$this->Tree->saveField($parentField, $parentId);

		$result = $this->Tree->children($parentId, true, array('id', 'name', $parentField, $leftField, $rightField));
		$expected = array(array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 5)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 6, $rightField => 11)),
			array($modelClass => array('id' => 3, 'name' => '1.1.1', $parentField => 1, $leftField => 12, $rightField => 13)));
		$this->assertEquals($expected, $result);
		$this->assertTrue($this->Tree->verify());
	}

/**
 * testInsertWithWhitelist method
 *
 * @return void
 */
	public function testInsertWithWhitelist() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$this->Tree->whitelist = array('name', $parentField);
		$this->Tree->create();
		$this->Tree->save(array($modelClass => array('name' => 'testAddOrphan', $parentField => null)));
		$result = $this->Tree->findByName('testAddOrphan', array('name', $parentField, $leftField, $rightField));
		$expected = array('name' => 'testAddOrphan', $parentField => null, $leftField => '15', $rightField => 16);
		$this->assertEquals($expected, $result[$modelClass]);
		$this->assertTrue($this->Tree->verify());
	}

/**
 * testMoveBefore method
 *
 * @return void
 */
	public function testMoveBefore() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1.1')));
		$parentId = $parent[$modelClass]['id'];

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.2')));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->saveField($parentField, $parentId);

		$result = $this->Tree->children($parentId, true, array('name'));
		$expects = array(array($modelClass => array('name' => '1.1.1')),
			array($modelClass => array('name' => '1.1.2')),
			array($modelClass => array('name' => '1.2')));
		$this->assertEquals($expects, $result);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testMoveAfter method
 *
 * @return void
 */
	public function testMoveAfter() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1.2')));
		$parentId = $parent[$modelClass]['id'];

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.1')));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->saveField($parentField, $parentId);

		$result = $this->Tree->children($parentId, true, array('name'));
		$expects = array(array($modelClass => array('name' => '1.2.1')),
			array($modelClass => array('name' => '1.2.2')),
			array($modelClass => array('name' => '1.1')));
		$this->assertEquals($expects, $result);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testMoveDemoteInvalid method
 *
 * @return void
 */
	public function testMoveDemoteInvalid() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1. Root')));
		$parentId = $parent[$modelClass]['id'];

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.1.1')));

		$expects = $this->Tree->find('all');
		$before = $this->Tree->read(null, $data[$modelClass]['id']);

		$this->Tree->id = $parentId;
		$this->Tree->saveField($parentField, $data[$modelClass]['id']);

		$results = $this->Tree->find('all');
		$after = $this->Tree->read(null, $data[$modelClass]['id']);

		$this->assertEquals($expects, $results);
		$this->assertEquals($before, $after);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testMoveInvalid method
 *
 * @return void
 */
	public function testMoveInvalid() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$initialCount = $this->Tree->find('count');
		$data = $this->Tree->findByName('1.1');

		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->saveField($parentField, 999999);

		$laterCount = $this->Tree->find('count');
		$this->assertSame($initialCount, $laterCount);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testMoveSelfInvalid method
 *
 * @return void
 */
	public function testMoveSelfInvalid() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$initialCount = $this->Tree->find('count');
		$data = $this->Tree->findByName('1.1');

		$this->Tree->id = $data[$modelClass]['id'];
		$saveSuccess = $this->Tree->saveField($parentField, $this->Tree->id);

		$this->assertFalse($saveSuccess);
		$laterCount = $this->Tree->find('count');
		$this->assertSame($initialCount, $laterCount);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testMoveUpSuccess method
 *
 * @return void
 */
	public function testMoveUpSuccess() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.2')));
		$this->Tree->moveUp($data[$modelClass]['id']);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(array($modelClass => array('name' => '1.2')),
			array($modelClass => array('name' => '1.1')));
		$this->assertSame($expected, $result);
	}

/**
 * testMoveUpFail method
 *
 * @return void
 */
	public function testMoveUpFail() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1.1')));

		$this->Tree->moveUp($data[$modelClass]['id']);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(array($modelClass => array('name' => '1.1')),
			array($modelClass => array('name' => '1.2')));
		$this->assertSame($expected, $result);
	}

/**
 * testMoveUp2 method
 *
 * @return void
 */
	public function testMoveUp2() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(1, 10);

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.5')));
		$this->Tree->moveUp($data[$modelClass]['id'], 2);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(
			array($modelClass => array('name' => '1.1')),
			array($modelClass => array('name' => '1.2')),
			array($modelClass => array('name' => '1.5')),
			array($modelClass => array('name' => '1.3')),
			array($modelClass => array('name' => '1.4')),
			array($modelClass => array('name' => '1.6')),
			array($modelClass => array('name' => '1.7')),
			array($modelClass => array('name' => '1.8')),
			array($modelClass => array('name' => '1.9')),
			array($modelClass => array('name' => '1.10')));
		$this->assertSame($expected, $result);
	}

/**
 * testMoveUpFirst method
 *
 * @return void
 */
	public function testMoveUpFirst() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(1, 10);

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.5')));
		$this->Tree->moveUp($data[$modelClass]['id'], true);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(
			array($modelClass => array('name' => '1.5')),
			array($modelClass => array('name' => '1.1')),
			array($modelClass => array('name' => '1.2')),
			array($modelClass => array('name' => '1.3')),
			array($modelClass => array('name' => '1.4')),
			array($modelClass => array('name' => '1.6')),
			array($modelClass => array('name' => '1.7')),
			array($modelClass => array('name' => '1.8')),
			array($modelClass => array('name' => '1.9')),
			array($modelClass => array('name' => '1.10')));
		$this->assertSame($expected, $result);
	}

/**
 * testMoveDownSuccess method
 *
 * @return void
 */
	public function testMoveDownSuccess() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.1')));
		$this->Tree->moveDown($data[$modelClass]['id']);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(array($modelClass => array('name' => '1.2')),
			array($modelClass => array('name' => '1.1')));
		$this->assertSame($expected, $result);
	}

/**
 * testMoveDownFail method
 *
 * @return void
 */
	public function testMoveDownFail() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1.2')));
		$this->Tree->moveDown($data[$modelClass]['id']);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(array($modelClass => array('name' => '1.1')),
			array($modelClass => array('name' => '1.2')));
		$this->assertSame($expected, $result);
	}

/**
 * testMoveDownLast method
 *
 * @return void
 */
	public function testMoveDownLast() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(1, 10);

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.5')));
		$this->Tree->moveDown($data[$modelClass]['id'], true);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(
			array($modelClass => array('name' => '1.1')),
			array($modelClass => array('name' => '1.2')),
			array($modelClass => array('name' => '1.3')),
			array($modelClass => array('name' => '1.4')),
			array($modelClass => array('name' => '1.6')),
			array($modelClass => array('name' => '1.7')),
			array($modelClass => array('name' => '1.8')),
			array($modelClass => array('name' => '1.9')),
			array($modelClass => array('name' => '1.10')),
			array($modelClass => array('name' => '1.5')));
		$this->assertSame($expected, $result);
	}

/**
 * testMoveDown2 method
 *
 * @return void
 */
	public function testMoveDown2() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(1, 10);

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.5')));
		$this->Tree->moveDown($data[$modelClass]['id'], 2);

		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(
			array($modelClass => array('name' => '1.1')),
			array($modelClass => array('name' => '1.2')),
			array($modelClass => array('name' => '1.3')),
			array($modelClass => array('name' => '1.4')),
			array($modelClass => array('name' => '1.6')),
			array($modelClass => array('name' => '1.7')),
			array($modelClass => array('name' => '1.5')),
			array($modelClass => array('name' => '1.8')),
			array($modelClass => array('name' => '1.9')),
			array($modelClass => array('name' => '1.10')));
		$this->assertSame($expected, $result);
	}

/**
 * testSaveNoMove method
 *
 * @return void
 */
	public function testSaveNoMove() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(1, 10);

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.5')));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->saveField('name', 'renamed');
		$parent = $this->Tree->findByName('1. Root', array('id'));
		$this->Tree->id = $parent[$modelClass]['id'];
		$result = $this->Tree->children(null, true, array('name'));
		$expected = array(
			array($modelClass => array('name' => '1.1')),
			array($modelClass => array('name' => '1.2')),
			array($modelClass => array('name' => '1.3')),
			array($modelClass => array('name' => '1.4')),
			array($modelClass => array('name' => 'renamed')),
			array($modelClass => array('name' => '1.6')),
			array($modelClass => array('name' => '1.7')),
			array($modelClass => array('name' => '1.8')),
			array($modelClass => array('name' => '1.9')),
			array($modelClass => array('name' => '1.10')));
		$this->assertSame($expected, $result);
	}

/**
 * testMoveToRootAndMoveUp method
 *
 * @return void
 */
	public function testMoveToRootAndMoveUp() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(1, 1);
		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.1')));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->save(array($parentField => null));

		$result = $this->Tree->verify();
		$this->assertTrue($result);

		$this->Tree->moveUp();

		$result = $this->Tree->find('all', array('fields' => 'name', 'order' => $modelClass . '.' . $leftField . ' ASC'));
		$expected = array(array($modelClass => array('name' => '1.1')),
			array($modelClass => array('name' => '1. Root')));
		$this->assertSame($expected, $result);
	}

/**
 * testDelete method
 *
 * @return void
 */
	public function testDelete() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$initialCount = $this->Tree->find('count');
		$result = $this->Tree->findByName('1.1.1');

		$return = $this->Tree->delete($result[$modelClass]['id']);
		$this->assertEquals(true, $return);

		$laterCount = $this->Tree->find('count');
		$this->assertEquals($initialCount - 1, $laterCount);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);

		$initialCount = $this->Tree->find('count');
		$result = $this->Tree->findByName('1.1');

		$return = $this->Tree->delete($result[$modelClass]['id']);
		$this->assertEquals(true, $return);

		$laterCount = $this->Tree->find('count');
		$this->assertEquals($initialCount - 2, $laterCount);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * Test deleting a record that doesn't exist.
 *
 * @return void
 */
	public function testDeleteDoesNotExist() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$this->Tree->delete(99999);
	}

/**
 * testRemove method
 *
 * @return void
 */
	public function testRemove() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$initialCount = $this->Tree->find('count');
		$result = $this->Tree->findByName('1.1');

		$this->Tree->removeFromTree($result[$modelClass]['id']);

		$laterCount = $this->Tree->find('count');
		$this->assertEquals($initialCount, $laterCount);

		$children = $this->Tree->children($result[$modelClass][$parentField], true, array('name'));
		$expects = array(array($modelClass => array('name' => '1.1.1')),
			array($modelClass => array('name' => '1.1.2')),
			array($modelClass => array('name' => '1.2')));
		$this->assertEquals($children, $expects);

		$topNodes = $this->Tree->children(false, true, array('name'));
		$expects = array(array($modelClass => array('name' => '1. Root')),
			array($modelClass => array('name' => '1.1')));
		$this->assertEquals($topNodes, $expects);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testRemoveLastTopParent method
 *
 * @return void
 */
	public function testRemoveLastTopParent() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$initialCount = $this->Tree->find('count');
		$initialTopNodes = $this->Tree->childCount(false);

		$result = $this->Tree->findByName('1. Root');
		$this->Tree->removeFromTree($result[$modelClass]['id']);

		$laterCount = $this->Tree->find('count');
		$laterTopNodes = $this->Tree->childCount(false);

		$this->assertEquals($initialCount, $laterCount);
		$this->assertEquals($initialTopNodes, $laterTopNodes);

		$topNodes = $this->Tree->children(false, true, array('name'));
		$expects = array(array($modelClass => array('name' => '1.1')),
			array($modelClass => array('name' => '1.2')),
			array($modelClass => array('name' => '1. Root')));

		$this->assertEquals($topNodes, $expects);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testRemoveNoChildren method
 *
 * @return void
 */
	public function testRemoveNoChildren() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$initialCount = $this->Tree->find('count');

		$result = $this->Tree->findByName('1.1.1');
		$this->Tree->removeFromTree($result[$modelClass]['id']);

		$laterCount = $this->Tree->find('count');
		$this->assertEquals($initialCount, $laterCount);

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

		$this->assertEquals($nodes, $expects);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testRemoveAndDelete method
 *
 * @return void
 */
	public function testRemoveAndDelete() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$initialCount = $this->Tree->find('count');
		$result = $this->Tree->findByName('1.1');

		$this->Tree->removeFromTree($result[$modelClass]['id'], true);

		$laterCount = $this->Tree->find('count');
		$this->assertEquals($initialCount - 1, $laterCount);

		$children = $this->Tree->children($result[$modelClass][$parentField], true, array('name'), $leftField . ' asc');
		$expects = array(
			array($modelClass => array('name' => '1.1.1')),
			array($modelClass => array('name' => '1.1.2')),
			array($modelClass => array('name' => '1.2'))
		);
		$this->assertEquals($children, $expects);

		$topNodes = $this->Tree->children(false, true, array('name'));
		$expects = array(array($modelClass => array('name' => '1. Root')));
		$this->assertEquals($topNodes, $expects);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testRemoveAndDeleteNoChildren method
 *
 * @return void
 */
	public function testRemoveAndDeleteNoChildren() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);
		$initialCount = $this->Tree->find('count');

		$result = $this->Tree->findByName('1.1.1');
		$this->Tree->removeFromTree($result[$modelClass]['id'], true);

		$laterCount = $this->Tree->find('count');
		$this->assertEquals($initialCount - 1, $laterCount);

		$nodes = $this->Tree->find('list', array('order' => $leftField));
		$expects = array(
			1 => '1. Root',
			2 => '1.1',
			4 => '1.1.2',
			5 => '1.2',
			6 => '1.2.1',
			7 => '1.2.2',
		);
		$this->assertEquals($nodes, $expects);

		$validTree = $this->Tree->verify();
		$this->assertTrue($validTree);
	}

/**
 * testChildren method
 *
 * @return void
 */
	public function testChildren() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1. Root')));
		$this->Tree->id = $data[$modelClass]['id'];

		$direct = $this->Tree->children(null, true, array('id', 'name', $parentField, $leftField, $rightField));
		$expects = array(array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 7)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 8, $rightField => 13)));
		$this->assertEquals($direct, $expects);

		$total = $this->Tree->children(null, null, array('id', 'name', $parentField, $leftField, $rightField));
		$expects = array(array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 7)),
			array($modelClass => array('id' => 3, 'name' => '1.1.1', $parentField => 2, $leftField => 3, $rightField => 4)),
			array($modelClass => array('id' => 4, 'name' => '1.1.2', $parentField => 2, $leftField => 5, $rightField => 6)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 8, $rightField => 13)),
			array($modelClass => array('id' => 6, 'name' => '1.2.1', $parentField => 5, $leftField => 9, $rightField => 10)),
			array($modelClass => array('id' => 7, 'name' => '1.2.2', $parentField => 5, $leftField => 11, $rightField => 12)));
		$this->assertEquals($total, $expects);

		$this->assertEquals(array(), $this->Tree->children(10000));
	}

/**
 * testCountChildren method
 *
 * @return void
 */
	public function testCountChildren() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1. Root')));
		$this->Tree->id = $data[$modelClass]['id'];

		$direct = $this->Tree->childCount(null, true);
		$this->assertEquals(2, $direct);

		$total = $this->Tree->childCount();
		$this->assertEquals(6, $total);

		$this->Tree->read(null, $data[$modelClass]['id']);
		$id = $this->Tree->field('id', array($modelClass . '.name' => '1.2'));
		$total = $this->Tree->childCount($id);
		$this->assertEquals(2, $total);
	}

/**
 * testGetParentNode method
 *
 * @return void
 */
	public function testGetParentNode() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1.2.2')));
		$this->Tree->id = $data[$modelClass]['id'];

		$result = $this->Tree->getParentNode(null, array('name'));
		$expects = array($modelClass => array('name' => '1.2'));
		$this->assertSame($expects, $result);
	}

/**
 * testGetPath method
 *
 * @return void
 */
	public function testGetPath() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1.2.2')));
		$this->Tree->id = $data[$modelClass]['id'];

		$result = $this->Tree->getPath(null, array('name'));
		$expects = array(array($modelClass => array('name' => '1. Root')),
			array($modelClass => array('name' => '1.2')),
			array($modelClass => array('name' => '1.2.2')));
		$this->assertSame($expects, $result);
	}

/**
 * testNoAmbiguousColumn method
 *
 * @return void
 */
	public function testNoAmbiguousColumn() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->bindModel(array('belongsTo' => array('Dummy' =>
			array('className' => $modelClass, 'foreignKey' => $parentField, 'conditions' => array('Dummy.id' => null)))), false);
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1. Root')));
		$this->Tree->id = $data[$modelClass]['id'];

		$direct = $this->Tree->children(null, true, array('id', 'name', $parentField, $leftField, $rightField));
		$expects = array(array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 7)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 8, $rightField => 13)));
		$this->assertEquals($direct, $expects);

		$total = $this->Tree->children(null, null, array('id', 'name', $parentField, $leftField, $rightField));
		$expects = array(
			array($modelClass => array('id' => 2, 'name' => '1.1', $parentField => 1, $leftField => 2, $rightField => 7)),
			array($modelClass => array('id' => 3, 'name' => '1.1.1', $parentField => 2, $leftField => 3, $rightField => 4)),
			array($modelClass => array('id' => 4, 'name' => '1.1.2', $parentField => 2, $leftField => 5, $rightField => 6)),
			array($modelClass => array('id' => 5, 'name' => '1.2', $parentField => 1, $leftField => 8, $rightField => 13)),
			array($modelClass => array('id' => 6, 'name' => '1.2.1', $parentField => 5, $leftField => 9, $rightField => 10)),
			array($modelClass => array('id' => 7, 'name' => '1.2.2', $parentField => 5, $leftField => 11, $rightField => 12))
		);
		$this->assertEquals($total, $expects);
	}

/**
 * testReorderTree method
 *
 * @return void
 */
	public function testReorderTree() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(3, 3);
		$nodes = $this->Tree->find('list', array('order' => $leftField));

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.1')));
		$this->Tree->moveDown($data[$modelClass]['id']);

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.2.1')));
		$this->Tree->moveDown($data[$modelClass]['id']);

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.3.2.2')));
		$this->Tree->moveDown($data[$modelClass]['id']);

		$unsortedNodes = $this->Tree->find('list', array('order' => $leftField));
		$this->assertEquals($nodes, $unsortedNodes);
		$this->assertNotEquals(array_keys($nodes), array_keys($unsortedNodes));

		$this->Tree->reorder();
		$sortedNodes = $this->Tree->find('list', array('order' => $leftField));
		$this->assertSame($nodes, $sortedNodes);
	}

/**
 * test reordering large-ish trees with cacheQueries = true.
 * This caused infinite loops when moving down elements as stale data is returned
 * from the memory cache
 *
 * @return void
 */
	public function testReorderBigTreeWithQueryCaching() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
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
 * @return void
 */
	public function testGenerateTreeListWithSelfJoin() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->bindModel(array('belongsTo' => array('Dummy' =>
			array('className' => $modelClass, 'foreignKey' => $parentField, 'conditions' => array('Dummy.id' => null)))), false);
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->generateTreeList();
		$expected = array(1 => '1. Root', 2 => '_1.1', 3 => '__1.1.1', 4 => '__1.1.2', 5 => '_1.2', 6 => '__1.2.1', 7 => '__1.2.2');
		$this->assertSame($expected, $result);
	}

/**
 * Test the formatting options of generateTreeList()
 *
 * @return void
 */
	public function testGenerateTreeListFormatting() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->generateTreeList(
			null,
			"{n}.$modelClass.id",
			array('%s - %s', "{n}.$modelClass.id", "{n}.$modelClass.name")
		);
		$this->assertEquals('1 - 1. Root', $result[1]);
		$this->assertEquals('_2 - 1.1', $result[2]);
		$this->assertEquals('__3 - 1.1.1', $result[3]);
	}

/**
 * testArraySyntax method
 *
 * @return void
 */
	public function testArraySyntax() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->order = null;
		$this->Tree->initialize(3, 3);
		$this->assertSame($this->Tree->childCount(2), $this->Tree->childCount(array('id' => 2)));
		$this->assertSame($this->Tree->getParentNode(2), $this->Tree->getParentNode(array('id' => 2)));
		$this->assertSame($this->Tree->getPath(4), $this->Tree->getPath(array('id' => 4)));
	}

/**
 * testFindThreaded method
 *
 * @return void
 */
	public function testFindThreaded() {
		$Model = new Person();
		$Model->recursive = -1;
		$Model->Behaviors->load('Tree', array('parent' => 'mother_id'));

		$result = $Model->find('threaded');
		$expected = array(
			array(
				'Person' => array(
					'id' => '4',
					'name' => 'mother - grand mother',
					'mother_id' => '0',
					'father_id' => '0'
				),
				'children' => array(
					array(
						'Person' => array(
							'id' => '2',
							'name' => 'mother',
							'mother_id' => '4',
							'father_id' => '5'
						),
						'children' => array(
							array(
								'Person' => array(
									'id' => '1',
									'name' => 'person',
									'mother_id' => '2',
									'father_id' => '3'
								),
								'children' => array()
							)
						)
					)
				)
			),
			array(
				'Person' => array(
					'id' => '5',
					'name' => 'mother - grand father',
					'mother_id' => '0',
					'father_id' => '0'
				),
				'children' => array()
			),
			array(
				'Person' => array(
					'id' => '6',
					'name' => 'father - grand mother',
					'mother_id' => '0',
					'father_id' => '0'
				),
				'children' => array(
					array(
						'Person' => array(
							'id' => '3',
							'name' => 'father',
							'mother_id' => '6',
							'father_id' => '7'
						),
						'children' => array()
					)
				)
			),
			array(
				'Person' => array(
					'id' => '7',
					'name' => 'father - grand father',
					'mother_id' => '0',
					'father_id' => '0'
				),
				'children' => array()
			)
		);
		$this->assertEquals($expected, $result);
	}
}
