<?php
/**
 * TreeBehaviorUuidTest file
 *
 * Tree test using UUIDs
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.5330
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
require_once(dirname(dirname(__FILE__)) . DS . 'models.php');

/**
 * TreeBehaviorUuidTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class TreeBehaviorUuidTest extends CakeTestCase {

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
		'modelClass' => 'UuidTree',
		'leftField' => 'lft',
		'rightField' => 'rght',
		'parentField' => 'parent_id'
	);

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.uuid_tree');

/**
 * testMovePromote method
 *
 * @return void
 */
	public function testMovePromote() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1. Root')));
		$parent_id = $parent[$modelClass]['id'];

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.1.1')));
		$this->Tree->id= $data[$modelClass]['id'];
		$this->Tree->saveField($parentField, $parent_id);
		$direct = $this->Tree->children($parent_id, true, array('name', $leftField, $rightField));
		$expects = array(array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 5)),
			array($modelClass => array('name' => '1.2', $leftField => 6, $rightField => 11)),
			array($modelClass => array('name' => '1.1.1', $leftField => 12, $rightField => 13)));
		$this->assertEquals($direct, $expects);
		$validTree = $this->Tree->verify();
		$this->assertSame($validTree, true);
	}

/**
 * testMoveWithWhitelist method
 *
 * @return void
 */
	public function testMoveWithWhitelist() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->initialize(2, 2);
		$this->Tree->id = null;

		$parent = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1. Root')));
		$parent_id = $parent[$modelClass]['id'];

		$data = $this->Tree->find('first', array('fields' => array('id'), 'conditions' => array($modelClass . '.name' => '1.1.1')));
		$this->Tree->id = $data[$modelClass]['id'];
		$this->Tree->whitelist = array($parentField, 'name', 'description');
		$this->Tree->saveField($parentField, $parent_id);

		$result = $this->Tree->children($parent_id, true, array('name', $leftField, $rightField));
		$expected = array(array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 5)),
			array($modelClass => array('name' => '1.2', $leftField => 6, $rightField => 11)),
			array($modelClass => array('name' => '1.1.1', $leftField => 12, $rightField => 13)));
		$this->assertEquals($expected, $result);
		$this->assertTrue($this->Tree->verify());
	}

/**
 * testRemoveNoChildren method
 *
 * @return void
 */
	public function testRemoveNoChildren() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->initialize(2, 2);
		$initialCount = $this->Tree->find('count');

		$result = $this->Tree->findByName('1.1.1');
		$this->Tree->removeFromTree($result[$modelClass]['id']);

		$laterCount = $this->Tree->find('count');
		$this->assertEquals($initialCount, $laterCount);

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

		$this->assertEquals(array_values($nodes), $expects);

		$validTree = $this->Tree->verify();
		$this->assertSame($validTree, true);
	}

/**
 * testRemoveAndDeleteNoChildren method
 *
 * @return void
 */
	public function testRemoveAndDeleteNoChildren() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->initialize(2, 2);
		$initialCount = $this->Tree->find('count');

		$result = $this->Tree->findByName('1.1.1');
		$this->Tree->removeFromTree($result[$modelClass]['id'], true);

		$laterCount = $this->Tree->find('count');
		$this->assertEquals($initialCount - 1, $laterCount);

		$nodes = $this->Tree->find('list', array('order' => $leftField));
		$expects = array(
			'1. Root',
			'1.1',
			'1.1.2',
			'1.2',
			'1.2.1',
			'1.2.2',
		);
		$this->assertEquals(array_values($nodes), $expects);

		$validTree = $this->Tree->verify();
		$this->assertSame($validTree, true);
	}

/**
 * testChildren method
 *
 * @return void
 */
	public function testChildren() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->initialize(2, 2);

		$data = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1. Root')));
		$this->Tree->id = $data[$modelClass]['id'];

		$direct = $this->Tree->children(null, true, array('name', $leftField, $rightField));
		$expects = array(array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 7)),
			array($modelClass => array('name' => '1.2', $leftField => 8, $rightField => 13)));
		$this->assertEquals($direct, $expects);

		$total = $this->Tree->children(null, null, array('name', $leftField, $rightField));
		$expects = array(array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 7)),
			array($modelClass => array('name' => '1.1.1', $leftField => 3, $rightField => 4)),
			array($modelClass => array('name' => '1.1.2', $leftField => 5, $rightField => 6)),
			array($modelClass => array('name' => '1.2', $leftField => 8, $rightField => 13)),
			array($modelClass => array('name' => '1.2.1', $leftField => 9, $rightField => 10)),
			array($modelClass => array('name' => '1.2.2', $leftField => 11, $rightField => 12)));
		$this->assertEquals($total, $expects);
	}

/**
 * testNoAmbiguousColumn method
 *
 * @return void
 */
	public function testNoAmbiguousColumn() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->initialize(2, 2);

		$this->Tree->bindModel(array('belongsTo' => array('Dummy' =>
			array('className' => $modelClass, 'foreignKey' => $parentField, 'conditions' => array('Dummy.id' => null)))), false);

		$data = $this->Tree->find('first', array('conditions' => array($modelClass . '.name' => '1. Root')));
		$this->Tree->id = $data[$modelClass]['id'];

		$direct = $this->Tree->children(null, true, array('name', $leftField, $rightField));
		$expects = array(array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 7)),
			array($modelClass => array('name' => '1.2', $leftField => 8, $rightField => 13)));
		$this->assertEquals($direct, $expects);

		$total = $this->Tree->children(null, null, array('name', $leftField, $rightField));
		$expects = array(
			array($modelClass => array('name' => '1.1', $leftField => 2, $rightField => 7)),
			array($modelClass => array('name' => '1.1.1', $leftField => 3, $rightField => 4)),
			array($modelClass => array('name' => '1.1.2', $leftField => 5, $rightField => 6)),
			array($modelClass => array('name' => '1.2', $leftField => 8, $rightField => 13)),
			array($modelClass => array('name' => '1.2.1', $leftField => 9, $rightField => 10)),
			array($modelClass => array('name' => '1.2.2', $leftField => 11, $rightField => 12))
		);
		$this->assertEquals($total, $expects);
	}

/**
 * testGenerateTreeListWithSelfJoin method
 *
 * @return void
 */
	public function testGenerateTreeListWithSelfJoin() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->bindModel(array('belongsTo' => array('Dummy' =>
			array('className' => $modelClass, 'foreignKey' => $parentField, 'conditions' => array('Dummy.id' => null)))), false);
		$this->Tree->initialize(2, 2);

		$result = $this->Tree->generateTreeList();
		$expected = array('1. Root', '_1.1', '__1.1.1', '__1.1.2', '_1.2', '__1.2.1', '__1.2.2');
		$this->assertSame(array_values($result), $expected);
	}
}
