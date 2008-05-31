<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.model.behaviors
 * @since			CakePHP(tm) v 1.2.0.5330
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * Short description for file
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.tests.cases.libs.model.behaviors
 */
class NumberTree extends CakeTestModel {

	var $name = 'NumberTree';
	var $actsAs = array('Tree');

	function initialize($levelLimit = 3, $childLimit = 3, $currentLevel = null, $parent_id = null, $prefix = '1', $hierachial = true) {
		if (!$parent_id) {
			$this->deleteAll(true);
			$this->save(array($this->name => array('name' => '1. Root')));
			$this->initialize($levelLimit, $childLimit, 1, $this->id, '1', $hierachial);
			$this->create(array());
		}

		if (!$currentLevel || $currentLevel > $levelLimit) {
			return;
		}

		for ($i = 1; $i <= $childLimit; $i++) {
			$name = $prefix . '.' . $i;
			$data = array($this->name => array('name' => $name));
			$this->create($data);

			if ($hierachial) {
				$data[$this->name]['parent_id'] = $parent_id;
			}
			$this->save($data);
			$this->initialize($levelLimit, $childLimit, $currentLevel + 1, $this->id, $name, $hierachial);
		}
	}
}

class FlagTree extends NumberTree {
	var $name = 'FlagTree';
}

class Campaign extends CakeTestModel {
	var $name = 'Campaign';
	var $hasMany = array('Ad' => array('fields' => array('id','campaign_id','name') ));
}
class Ad extends CakeTestModel {
	var $name = 'Ad';
	var $actsAs = array('Tree');
	var $belongsTo = array('Campaign' );
}

class NumberTreeCase extends CakeTestCase {

	var $fixtures = array('core.number_tree', 'core.flag_tree', 'core.campaign','core.ad');

	function testInitialize() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);
	
		$result = $this->NumberTree->find('count');
		$this->assertEqual($result, 7);
	
		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testStringScope() {
		$this->FlagTree =& new FlagTree();
		$this->FlagTree->initialize(2, 3);

		$this->FlagTree->id = 1;
		$this->FlagTree->saveField('flag', 1);
		$this->FlagTree->id = 2;
		$this->FlagTree->saveField('flag', 1);

		$result = $this->FlagTree->children();
		$expected = array(
			array('FlagTree' => array('id' => '3', 'name' => '1.1.1', 'parent_id' => '2', 'lft' => '3', 'rght' => '4', 'flag' => '0')),
			array('FlagTree' => array('id' => '4', 'name' => '1.1.2', 'parent_id' => '2', 'lft' => '5', 'rght' => '6', 'flag' => '0')),
			array('FlagTree' => array('id' => '5', 'name' => '1.1.3', 'parent_id' => '2', 'lft' => '7', 'rght' => '8', 'flag' => '0'))
		);
		$this->assertEqual($result, $expected);

		$this->FlagTree->Behaviors->attach('Tree', array('scope' => 'FlagTree.flag = 1'));
		$this->assertEqual($this->FlagTree->children(), array());

		$this->FlagTree->id = 1;
		$this->FlagTree->Behaviors->attach('Tree', array('scope' => 'FlagTree.flag = 1'));

		$result = $this->FlagTree->children();
		$expected = array(array('FlagTree' => array('id' => '2', 'name' => '1.1', 'parent_id' => '1', 'lft' => '2', 'rght' => '9', 'flag' => '1')));
		$this->assertEqual($result, $expected);

		$this->assertTrue($this->FlagTree->delete());
		$this->assertEqual($this->FlagTree->find('count'), 11);
	}

	function testArrayScope() {
		$this->FlagTree =& new FlagTree();
		$this->FlagTree->initialize(2, 3);

		$this->FlagTree->id = 1;
		$this->FlagTree->saveField('flag', 1);
		$this->FlagTree->id = 2;
		$this->FlagTree->saveField('flag', 1);

		$result = $this->FlagTree->children();
		$expected = array(
			array('FlagTree' => array('id' => '3', 'name' => '1.1.1', 'parent_id' => '2', 'lft' => '3', 'rght' => '4', 'flag' => '0')),
			array('FlagTree' => array('id' => '4', 'name' => '1.1.2', 'parent_id' => '2', 'lft' => '5', 'rght' => '6', 'flag' => '0')),
			array('FlagTree' => array('id' => '5', 'name' => '1.1.3', 'parent_id' => '2', 'lft' => '7', 'rght' => '8', 'flag' => '0'))
		);
		$this->assertEqual($result, $expected);

		$this->FlagTree->Behaviors->attach('Tree', array('scope' => array('FlagTree.flag' => 1)));
		$this->assertEqual($this->FlagTree->children(), array());

		$this->FlagTree->id = 1;
		$this->FlagTree->Behaviors->attach('Tree', array('scope' => array('FlagTree.flag' => 1)));

		$result = $this->FlagTree->children();
		$expected = array(array('FlagTree' => array('id' => '2', 'name' => '1.1', 'parent_id' => '1', 'lft' => '2', 'rght' => '9', 'flag' => '1')));
		$this->assertEqual($result, $expected);

		$this->assertTrue($this->FlagTree->delete());
		$this->assertEqual($this->FlagTree->find('count'), 11);
	}

	function testDetectInvalidLeft() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$result = $this->NumberTree->findByName('1.1');

		$save['NumberTree']['id'] = $result['NumberTree']['id'];
		$save['NumberTree']['lft'] = 0;

		$this->NumberTree->save($save);
		$result = $this->NumberTree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->NumberTree->recover();
		$this->assertIdentical($result, true);

		$result = $this->NumberTree->verify();
		$this->assertIdentical($result, true);
	}

	function testDetectInvalidRight() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$result = $this->NumberTree->findByName('1.1');

		$save['NumberTree']['id'] = $result['NumberTree']['id'];
		$save['NumberTree']['rght'] = 0;

		$this->NumberTree->save($save);
		$result = $this->NumberTree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->NumberTree->recover();
		$this->assertIdentical($result, true);

		$result = $this->NumberTree->verify();
		$this->assertIdentical($result, true);
	}

	function testDetectInvalidParent() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$result = $this->NumberTree->findByName('1.1');

		// Bypass behavior and any other logic
		$this->NumberTree->updateAll(array('parent_id' => null), array('id' => $result['NumberTree']['id']));

		$result = $this->NumberTree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->NumberTree->recover();
		$this->assertIdentical($result, true);

		$result = $this->NumberTree->verify();
		$this->assertIdentical($result, true);
	}

	function testDetectNoneExistantParent() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$result = $this->NumberTree->findByName('1.1');
		$this->NumberTree->updateAll(array('parent_id' => 999999), array('id' => $result['NumberTree']['id']));

		$result = $this->NumberTree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->NumberTree->recover('MPTT');
		$this->assertIdentical($result, true);

		$result = $this->NumberTree->verify();
		$this->assertIdentical($result, true);
	}

	function testRecoverFromMissingParent() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$result = $this->NumberTree->findByName('1.1');
		$this->NumberTree->updateAll(array('parent_id' => 999999), array('id' => $result['NumberTree']['id']));

		$result = $this->NumberTree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->NumberTree->recover();
		$this->assertIdentical($result, true);

		$result = $this->NumberTree->verify();
		$this->assertIdentical($result, true);
	}

	function testDetectInvalidParents() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$this->NumberTree->updateAll(array('parent_id' => null));

		$result = $this->NumberTree->verify();
		$this->assertNotIdentical($result, true);

		$result = $this->NumberTree->recover();
		$this->assertIdentical($result, true);

		$result = $this->NumberTree->verify();
		$this->assertIdentical($result, true);
	}

	function testDetectInvalidLftsRghts() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$this->NumberTree->updateAll(array('lft' => 0, 'rght' => 0));

		$result = $this->NumberTree->verify();
		$this->assertNotIdentical($result, true);

		$this->NumberTree->recover();

		$result = $this->NumberTree->verify();
		$this->assertIdentical($result, true);
	}

	function testAddOrphan() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$this->NumberTree->save(array('NumberTree' => array('name' => 'testAddOrphan', 'parent_id' => null)));
		$result = $this->NumberTree->find(null, array('name', 'parent_id'), 'NumberTree.lft desc');
		$expected = array('NumberTree' => array('name' => 'testAddOrphan', 'parent_id' => null));
		$this->assertEqual($result, $expected);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testAddMiddle() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$data= $this->NumberTree->find(array('NumberTree.name' => '1.1'), array('id'));
		$initialCount = $this->NumberTree->find('count');

		$this->NumberTree->create();
		$result = $this->NumberTree->save(array('NumberTree' => array('name' => 'testAddMiddle', 'parent_id' => $data['NumberTree']['id'])));
		$expected = array_merge(array('NumberTree' => array('name' => 'testAddMiddle', 'parent_id' => '2')), $result);
		$this->assertIdentical($result, $expected);

		$laterCount = $this->NumberTree->find('count');

		$laterCount = $this->NumberTree->find('count');
		$this->assertEqual($initialCount + 1, $laterCount);

		$children = $this->NumberTree->children($data['NumberTree']['id'], true, array('name'));
		$expects = array(array('NumberTree' => array('name' => '1.1.1')),
							array('NumberTree' => array('name' => '1.1.2')),
							array('NumberTree' => array('name' => 'testAddMiddle')));
		$this->assertIdentical($children, $expects);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testAddInvalid() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);
		$this->NumberTree->id = null;

		$initialCount = $this->NumberTree->find('count');
		//$this->expectError('Trying to save a node under a none-existant node in TreeBehavior::beforeSave');

		$saveSuccess = $this->NumberTree->save(array('NumberTree' => array('name' => 'testAddInvalid', 'parent_id' => 99999)));
		$this->assertIdentical($saveSuccess, false);

		$laterCount = $this->NumberTree->find('count');
		$this->assertIdentical($initialCount, $laterCount);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testMovePromote() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);
		$this->NumberTree->id = null;

		$parent = $this->NumberTree->find(array('NumberTree.name' => '1. Root'));
		$parent_id = $parent['NumberTree']['id'];

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.1.1'), array('id'));
		$this->NumberTree->id= $data['NumberTree']['id'];
		$this->NumberTree->saveField('parent_id', $parent_id);

		$direct = $this->NumberTree->children($parent_id, true, array('id', 'name', 'parent_id', 'lft', 'rght'));
		$expects = array(array('NumberTree' => array('id' => 2, 'name' => '1.1', 'parent_id' => 1, 'lft' => 2, 'rght' => 5)),
						array('NumberTree' => array('id' => 5, 'name' => '1.2', 'parent_id' => 1, 'lft' => 6, 'rght' => 11)),
						array('NumberTree' => array('id' => 3, 'name' => '1.1.1', 'parent_id' => 1, 'lft' => 12, 'rght' => 13)));
		$this->assertEqual($direct, $expects);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testMoveWithWhitelist() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);
		$this->NumberTree->id = null;

		$parent = $this->NumberTree->find(array('NumberTree.name' => '1. Root'));
		$parent_id = $parent['NumberTree']['id'];

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.1.1'), array('id'));
		$this->NumberTree->id = $data['NumberTree']['id'];
		$this->NumberTree->whitelist = array('parent_id', 'name', 'description');
		$this->NumberTree->saveField('parent_id', $parent_id);

		$result = $this->NumberTree->children($parent_id, true, array('id', 'name', 'parent_id', 'lft', 'rght'));
		$expected = array(array('NumberTree' => array('id' => 2, 'name' => '1.1', 'parent_id' => 1, 'lft' => 2, 'rght' => 5)),
						array('NumberTree' => array('id' => 5, 'name' => '1.2', 'parent_id' => 1, 'lft' => 6, 'rght' => 11)),
						array('NumberTree' => array('id' => 3, 'name' => '1.1.1', 'parent_id' => 1, 'lft' => 12, 'rght' => 13)));
		$this->assertEqual($result, $expected);
		$this->assertTrue($this->NumberTree->verify());
	}

	function testInsertWithWhitelist() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$this->NumberTree->whitelist = array('name', 'parent_id');
		$this->NumberTree->save(array('NumberTree' => array('name' => 'testAddOrphan', 'parent_id' => null)));
		$result = $this->NumberTree->findByName('testAddOrphan', array('name', 'parent_id', 'lft', 'rght'));
		$expected = array('name' => 'testAddOrphan', 'parent_id' => null, 'lft' => '15', 'rght' => 16);
		$this->assertEqual($result['NumberTree'], $expected);
		$this->assertIdentical($this->NumberTree->verify(), true);
	}

	function testMoveBefore() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);
		$this->NumberTree->id = null;

		$parent = $this->NumberTree->find(array('NumberTree.name' => '1.1'));
		$parent_id = $parent['NumberTree']['id'];

		$data= $this->NumberTree->find(array('NumberTree.name' => '1.2'), array('id'));
		$this->NumberTree->id = $data['NumberTree']['id'];
		$this->NumberTree->saveField('parent_id', $parent_id);
		//$this->NumberTree->setparent($parent_id);

		$result = $this->NumberTree->children($parent_id, true, array('name'));
		$expects = array(array('NumberTree' => array('name' => '1.1.1')),
						array('NumberTree' => array('name' => '1.1.2')),
						array('NumberTree' => array('name' => '1.2')));
		$this->assertEqual($result, $expects);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testMoveAfter() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);
		$this->NumberTree->id = null;

		$parent = $this->NumberTree->find(array('NumberTree.name' => '1.2'));
		$parent_id = $parent['NumberTree']['id'];

		$data= $this->NumberTree->find(array('NumberTree.name' => '1.1'), array('id'));
		$this->NumberTree->id = $data['NumberTree']['id'];
		$this->NumberTree->saveField('parent_id', $parent_id);
		//$this->NumberTree->setparent($parent_id);

		$result = $this->NumberTree->children($parent_id, true, array('name'));
		$expects = array(array('NumberTree' => array('name' => '1.2.1')),
						array('NumberTree' => array('name' => '1.2.2')),
						array('NumberTree' => array('name' => '1.1')));
		$this->assertEqual($result, $expects);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testMoveDemoteInvalid() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);
		$this->NumberTree->id = null;

		$parent = $this->NumberTree->find(array('NumberTree.name' => '1. Root'));
		$parent_id = $parent['NumberTree']['id'];

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.1.1'), array('id'));

		$expects = $this->NumberTree->find('all');
		$before = $this->NumberTree->read(null, $data['NumberTree']['id']);

		$this->NumberTree->id = $parent_id;
		//$this->expectError('Trying to save a node under itself in TreeBehavior::beforeSave');
		$this->NumberTree->saveField('parent_id', $data['NumberTree']['id']);
		//$this->NumberTree->setparent($data['NumberTree']['id']);

		$results = $this->NumberTree->find('all');
		$after = $this->NumberTree->read(null, $data['NumberTree']['id']);

		$this->assertEqual($results, $expects);
		$this->assertEqual($before, $after);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testMoveInvalid() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);
		$this->NumberTree->id = null;

		$initialCount = $this->NumberTree->find('count');
		$data= $this->NumberTree->findByName('1.1');

		//$this->expectError('Trying to save a node under a none-existant node in TreeBehavior::beforeSave');
		$this->NumberTree->id = $data['NumberTree']['id'];
		$this->NumberTree->saveField('parent_id', 999999);
		//$saveSuccess = $this->NumberTree->setparent(999999);

		//$this->assertIdentical($saveSuccess, false);
		$laterCount = $this->NumberTree->find('count');
		$this->assertIdentical($initialCount, $laterCount);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testMoveSelfInvalid() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);
		$this->NumberTree->id = null;

		$initialCount = $this->NumberTree->find('count');
		$data= $this->NumberTree->findByName('1.1');

		//$this->expectError('Trying to set a node to be the parent of itself in TreeBehavior::beforeSave');
		$this->NumberTree->id = $data['NumberTree']['id'];
		$saveSuccess = $this->NumberTree->saveField('parent_id', $this->NumberTree->id);
		//$saveSuccess= $this->NumberTree->setparent($this->NumberTree->id);

		$this->assertIdentical($saveSuccess, false);
		$laterCount = $this->NumberTree->find('count');
		$this->assertIdentical($initialCount, $laterCount);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testMoveUpSuccess() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.2'), array('id'));
		$this->NumberTree->moveUp($data['NumberTree']['id']);

		$parent = $this->NumberTree->findByName('1. Root', array('id'));
		$this->NumberTree->id = $parent['NumberTree']['id'];
		$result = $this->NumberTree->children(null, true, array('name'));
		$expected = array(array('NumberTree' => array('name' => '1.2',)),
						array('NumberTree' => array('name' => '1.1',)));
		$this->assertIdentical($result, $expected);
	}

	function testMoveUpFail() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.1'));

		$this->NumberTree->moveUp($data['NumberTree']['id']);

		$parent = $this->NumberTree->findByName('1. Root', array('id'));
		$this->NumberTree->id = $parent['NumberTree']['id'];
		$result = $this->NumberTree->children(null, true, array('name'));
		$expected = array(array('NumberTree' => array('name' => '1.1',)),
						array('NumberTree' => array('name' => '1.2',)));
		$this->assertIdentical($result, $expected);
	}

	function testMoveUp2() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(1, 10);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.5'), array('id'));
		$this->NumberTree->moveUp($data['NumberTree']['id'], 2);

		$parent = $this->NumberTree->findByName('1. Root', array('id'));
		$this->NumberTree->id = $parent['NumberTree']['id'];
		$result = $this->NumberTree->children(null, true, array('name'));
		$expected = array(
				array('NumberTree' => array('name' => '1.1',)),
				array('NumberTree' => array('name' => '1.2',)),
				array('NumberTree' => array('name' => '1.5',)),
				array('NumberTree' => array('name' => '1.3',)),
				array('NumberTree' => array('name' => '1.4',)),
				array('NumberTree' => array('name' => '1.6',)),
				array('NumberTree' => array('name' => '1.7',)),
				array('NumberTree' => array('name' => '1.8',)),
				array('NumberTree' => array('name' => '1.9',)),
				array('NumberTree' => array('name' => '1.10',)));
		$this->assertIdentical($result, $expected);
	}

	function testMoveUpFirst() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(1, 10);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.5'), array('id'));
		$this->NumberTree->moveUp($data['NumberTree']['id'], true);

		$parent = $this->NumberTree->findByName('1. Root', array('id'));
		$this->NumberTree->id = $parent['NumberTree']['id'];
		$result = $this->NumberTree->children(null, true, array('name'));
		$expected = array(
				array('NumberTree' => array('name' => '1.5',)),
				array('NumberTree' => array('name' => '1.1',)),
				array('NumberTree' => array('name' => '1.2',)),
				array('NumberTree' => array('name' => '1.3',)),
				array('NumberTree' => array('name' => '1.4',)),
				array('NumberTree' => array('name' => '1.6',)),
				array('NumberTree' => array('name' => '1.7',)),
				array('NumberTree' => array('name' => '1.8',)),
				array('NumberTree' => array('name' => '1.9',)),
				array('NumberTree' => array('name' => '1.10',)));
		$this->assertIdentical($result, $expected);
	}

	function testMoveDownSuccess() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.1'), array('id'));
		$this->NumberTree->moveDown($data['NumberTree']['id']);

		$parent = $this->NumberTree->findByName('1. Root', array('id'));
		$this->NumberTree->id = $parent['NumberTree']['id'];
		$result = $this->NumberTree->children(null, true, array('name'));
		$expected = array(array('NumberTree' => array('name' => '1.2',)),
						array('NumberTree' => array('name' => '1.1',)));
		$this->assertIdentical($result, $expected);
	}

	function testMoveDownFail() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.2'));
		$this->NumberTree->moveDown($data['NumberTree']['id']);

		$parent = $this->NumberTree->findByName('1. Root', array('id'));
		$this->NumberTree->id = $parent['NumberTree']['id'];
		$result = $this->NumberTree->children(null, true, array('name'));
		$expected = array(array('NumberTree' => array('name' => '1.1',)),
					array('NumberTree' => array('name' => '1.2',)));
		$this->assertIdentical($result, $expected);
	}

	function testMoveDownLast() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(1, 10);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.5'), array('id'));
		$this->NumberTree->moveDown($data['NumberTree']['id'], true);

		$parent = $this->NumberTree->findByName('1. Root', array('id'));
		$this->NumberTree->id = $parent['NumberTree']['id'];
		$result = $this->NumberTree->children(null, true, array('name'));
		$expected = array(
				array('NumberTree' => array('name' => '1.1',)),
				array('NumberTree' => array('name' => '1.2',)),
				array('NumberTree' => array('name' => '1.3',)),
				array('NumberTree' => array('name' => '1.4',)),
				array('NumberTree' => array('name' => '1.6',)),
				array('NumberTree' => array('name' => '1.7',)),
				array('NumberTree' => array('name' => '1.8',)),
				array('NumberTree' => array('name' => '1.9',)),
				array('NumberTree' => array('name' => '1.10',)),
				array('NumberTree' => array('name' => '1.5',)));
		$this->assertIdentical($result, $expected);
	}

	function testMoveDown2() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(1, 10);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.5'), array('id'));
		$this->NumberTree->moveDown($data['NumberTree']['id'], 2);

		$parent = $this->NumberTree->findByName('1. Root', array('id'));
		$this->NumberTree->id = $parent['NumberTree']['id'];
		$result = $this->NumberTree->children(null, true, array('name'));
		$expected = array(
				array('NumberTree' => array('name' => '1.1',)),
				array('NumberTree' => array('name' => '1.2',)),
				array('NumberTree' => array('name' => '1.3',)),
				array('NumberTree' => array('name' => '1.4',)),
				array('NumberTree' => array('name' => '1.6',)),
				array('NumberTree' => array('name' => '1.7',)),
				array('NumberTree' => array('name' => '1.5',)),
				array('NumberTree' => array('name' => '1.8',)),
				array('NumberTree' => array('name' => '1.9',)),
				array('NumberTree' => array('name' => '1.10',)));
		$this->assertIdentical($result, $expected);
	}

	function testSaveNoMove() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(1, 10);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.5'), array('id'));
		$this->NumberTree->id = $data['NumberTree']['id'];
		$this->NumberTree->saveField('name', 'renamed');
		$parent = $this->NumberTree->findByName('1. Root', array('id'));
		$this->NumberTree->id = $parent['NumberTree']['id'];
		$result = $this->NumberTree->children(null, true, array('name'));
		$expected = array(
				array('NumberTree' => array('name' => '1.1',)),
				array('NumberTree' => array('name' => '1.2',)),
				array('NumberTree' => array('name' => '1.3',)),
				array('NumberTree' => array('name' => '1.4',)),
				array('NumberTree' => array('name' => 'renamed',)),
				array('NumberTree' => array('name' => '1.6',)),
				array('NumberTree' => array('name' => '1.7',)),
				array('NumberTree' => array('name' => '1.8',)),
				array('NumberTree' => array('name' => '1.9',)),
				array('NumberTree' => array('name' => '1.10',)));
		$this->assertIdentical($result, $expected);
	}

	function testMoveToRootAndMoveUp(){
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(1, 1);
		$data = $this->NumberTree->find(array('NumberTree.name' => '1.1'), array('id'));
		$this->NumberTree->id = $data['NumberTree']['id'];
		$this->NumberTree->save(array('parent_id' => null));

		$result = $this->NumberTree->verify();
		$this->assertIdentical($result, true);

		$this->NumberTree->moveup();

		$result = $this->NumberTree->find('all', array('fields' => 'name', 'order' => 'NumberTree.lft ASC'));
		$expected = array(array('NumberTree' => array('name' => '1.1')),
						array('NumberTree' => array('name' => '1. Root')));
		$this->assertIdentical($result, $expected);
	}

	function testDelete() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$initialCount = $this->NumberTree->find('count');
		$result = $this->NumberTree->findByName('1.1.1');

		$return = $this->NumberTree->delete($result['NumberTree']['id']);
		$this->assertEqual($return, true);

		$laterCount = $this->NumberTree->find('count');
		$this->assertEqual($initialCount - 1, $laterCount);

		$validTree= $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);

		$initialCount = $this->NumberTree->find('count');
		$result= $this->NumberTree->findByName('1.1');

		$return = $this->NumberTree->delete($result['NumberTree']['id']);
		$this->assertEqual($return, true);

		$laterCount = $this->NumberTree->find('count');
		$this->assertEqual($initialCount - 2, $laterCount);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testRemove() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);
		$initialCount = $this->NumberTree->find('count');
		$result = $this->NumberTree->findByName('1.1');

		$this->NumberTree->removeFromTree($result['NumberTree']['id']);

		$laterCount = $this->NumberTree->find('count');
		$this->assertEqual($initialCount, $laterCount);

		$children = $this->NumberTree->children($result['NumberTree']['parent_id'], true, array('name'));
		$expects = array(array('NumberTree' => array('name' => '1.1.1')),
							array('NumberTree' => array('name' => '1.1.2')),
							array('NumberTree' => array('name' => '1.2')));
		$this->assertEqual($children, $expects);

		$topNodes = $this->NumberTree->children(false,true,array('name'));
		$expects = array(array('NumberTree' => array('name' => '1. Root')),
						array('NumberTree' => array('name' => '1.1')));
		$this->assertEqual($topNodes, $expects);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testRemoveLastTopParent() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$initialCount = $this->NumberTree->find('count');
		$initialTopNodes = $this->NumberTree->childCount(false);

		$result = $this->NumberTree->findByName('1. Root');
		$this->NumberTree->removeFromTree($result['NumberTree']['id']);

		$laterCount = $this->NumberTree->find('count');
		$laterTopNodes = $this->NumberTree->childCount(false);

		$this->assertEqual($initialCount, $laterCount);
		$this->assertEqual($initialTopNodes, $laterTopNodes);

		$topNodes = $this->NumberTree->children(false,true,array('name'));
		$expects = array(array('NumberTree' => array('name' => '1.1')),
						array('NumberTree' => array('name' => '1.2')),
						array('NumberTree' => array('name' => '1. Root')));

		$this->assertEqual($topNodes, $expects);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testRemoveAndDelete() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$initialCount = $this->NumberTree->find('count');
		$result = $this->NumberTree->findByName('1.1');

		$this->NumberTree->removeFromTree($result['NumberTree']['id'],true);

		$laterCount = $this->NumberTree->find('count');
		$this->assertEqual($initialCount-1, $laterCount);

		$children = $this->NumberTree->children($result['NumberTree']['parent_id'], true, array('name'), 'lft asc');
		$expects= array(array('NumberTree' => array('name' => '1.1.1')),
						array('NumberTree' => array('name' => '1.1.2')),
						array('NumberTree' => array('name' => '1.2')));
		$this->assertEqual($children, $expects);

		$topNodes = $this->NumberTree->children(false,true,array('name'));
		$expects = array(array('NumberTree' => array('name' => '1. Root')));
		$this->assertEqual($topNodes, $expects);

		$validTree = $this->NumberTree->verify();
		$this->assertIdentical($validTree, true);
	}

	function testChildren() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1. Root'));
		$this->NumberTree->id= $data['NumberTree']['id'];

		$direct = $this->NumberTree->children(null, true, array('id', 'name', 'parent_id', 'lft', 'rght'));
		$expects = array(array('NumberTree' => array('id' => 2, 'name' => '1.1', 'parent_id' => 1, 'lft' => 2, 'rght' => 7)),
					array('NumberTree' => array('id' => 5, 'name' => '1.2', 'parent_id' => 1, 'lft' => 8, 'rght' => 13)));
		$this->assertEqual($direct, $expects);

		$total = $this->NumberTree->children(null, null, array('id', 'name', 'parent_id', 'lft', 'rght'));
		$expects = array(array('NumberTree' => array('id' => 2, 'name' => '1.1', 'parent_id' => 1, 'lft' => 2, 'rght' => 7)),
						array('NumberTree' => array('id' => 3, 'name' => '1.1.1', 'parent_id' => 2, 'lft' => 3, 'rght' => 4)),
						array('NumberTree' => array('id' => 4, 'name' => '1.1.2', 'parent_id' => 2, 'lft' => 5, 'rght' => 6)),
						array('NumberTree' => array('id' => 5, 'name' => '1.2', 'parent_id' => 1, 'lft' => 8, 'rght' => 13)),
						array('NumberTree' => array( 'id' => 6, 'name' => '1.2.1', 'parent_id' => 5, 'lft' => 9, 'rght' => 10)),
						array('NumberTree' => array('id' => 7, 'name' => '1.2.2', 'parent_id' => 5, 'lft' => 11, 'rght' => 12)));
		$this->assertEqual($total, $expects);
	}

	function testCountChildren() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1. Root'));
		$this->NumberTree->id = $data['NumberTree']['id'];

		$direct = $this->NumberTree->childCount(null, true);
		$this->assertEqual($direct, 2);

		$expects = $this->NumberTree->find('count') - 1;
		$total = $this->NumberTree->childCount();
		$this->assertEqual($total, 6);
	}

	function testGetParentNode() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.2.2'));
		$this->NumberTree->id= $data['NumberTree']['id'];

		$result = $this->NumberTree->getparentNode(null, array('name'));
		$expects = array('NumberTree' => array('name' => '1.2'));
		$this->assertIdentical($result, $expects);
	}

	function testGetPath() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(2, 2);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.2.2'));
		$this->NumberTree->id= $data['NumberTree']['id'];

		$result = $this->NumberTree->getPath(null, array('name'));
		$expects = array(array('NumberTree' => array('name' => '1. Root')),
					array('NumberTree' => array('name' => '1.2')),
					array('NumberTree' => array('name' => '1.2.2')));
		$this->assertIdentical($result, $expects);
	}

	function testNoAmbiguousColumn() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->bindModel(array('belongsTo' => array('Dummy' =>
					array('className' => 'NumberTree', 'foreignKey' => 'parent_id', 'conditions' => array('Dummy.id' => null)))), false);
		$this->NumberTree->initialize(2, 2);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1. Root'));
		$this->NumberTree->id= $data['NumberTree']['id'];

		$direct = $this->NumberTree->children(null, true, array('id', 'name', 'parent_id', 'lft', 'rght'), null, null, null, 1);
		$expects = array(array('NumberTree' => array('id' => 2, 'name' => '1.1', 'parent_id' => 1, 'lft' => 2, 'rght' => 7)),
					array('NumberTree' => array('id' => 5, 'name' => '1.2', 'parent_id' => 1, 'lft' => 8, 'rght' => 13)));
		$this->assertEqual($direct, $expects);

		$total = $this->NumberTree->children(null, null, array('id', 'name', 'parent_id', 'lft', 'rght'), null, null, null, 1);
		$expects = array(
			array('NumberTree' => array('id' => 2, 'name' => '1.1', 'parent_id' => 1, 'lft' => 2, 'rght' => 7)),
			array('NumberTree' => array('id' => 3, 'name' => '1.1.1', 'parent_id' => 2, 'lft' => 3, 'rght' => 4)),
			array('NumberTree' => array('id' => 4, 'name' => '1.1.2', 'parent_id' => 2, 'lft' => 5, 'rght' => 6)),
			array('NumberTree' => array('id' => 5, 'name' => '1.2', 'parent_id' => 1, 'lft' => 8, 'rght' => 13)),
			array('NumberTree' => array( 'id' => 6, 'name' => '1.2.1', 'parent_id' => 5, 'lft' => 9, 'rght' => 10)),
			array('NumberTree' => array('id' => 7, 'name' => '1.2.2', 'parent_id' => 5, 'lft' => 11, 'rght' => 12))
		);
		$this->assertEqual($total, $expects);
	}

	function testReorderTree() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(3, 3);
		$nodes = $this->NumberTree->find('list', array('order' => 'lft'));
		
		$data = $this->NumberTree->find(array('NumberTree.name' => '1.1'), array('id'));
		$this->NumberTree->moveDown($data['NumberTree']['id']);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.2.1'), array('id'));
		$this->NumberTree->moveDown($data['NumberTree']['id']);

		$data = $this->NumberTree->find(array('NumberTree.name' => '1.3.2.2'), array('id'));
		$this->NumberTree->moveDown($data['NumberTree']['id']);

		$unsortedNodes = $this->NumberTree->find('list', array('order' => 'lft'));
		$this->assertNotIdentical($nodes, $unsortedNodes);

		$this->NumberTree->reorder();
		$sortedNodes = $this->NumberTree->find('list', array('order' => 'lft'));
		$this->assertIdentical($nodes, $sortedNodes);
	}

	function testGenerateTreeListWithSelfJoin() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->bindModel(array('belongsTo' => array('Dummy' =>
					array('className' => 'NumberTree', 'foreignKey' => 'parent_id', 'conditions' => array('Dummy.id' => null)))), false);
		$this->NumberTree->initialize(2, 2);

		$result = $this->NumberTree->generateTreeList();
		$expected = array(1 => '1. Root', 2 => '_1.1', 3 => '__1.1.1', 4 => '__1.1.2', 5 => '_1.2', 6 => '__1.2.1', 7 => '__1.2.2');
		$this->assertIdentical($result, $expected);
	}

	function testMoveUpWithScope() {
		$this->Ad =& new Ad();
		$this->Ad->Behaviors->attach('Tree', array('scope'=>'Campaign'));
		$this->Ad->moveUp(6);
		
		$this->Ad->id = 4;
		$result = $this->Ad->children();
		$this->assertEqual(Set::extract('/Ad/id', $result), array(6, 5));
		$this->assertEqual(Set::extract('/Campaign/id', $result), array(2, 2));
	}

	function testMoveDownWithScope() {
		$this->Ad =& new Ad();
		$this->Ad->Behaviors->attach('Tree', array('scope'=>'Campaign'));
		$this->Ad->moveDown(6);

		$this->Ad->id = 4;
		$result = $this->Ad->children();
		$this->assertEqual(Set::extract('/Ad/id', $result), array(5, 6));
		$this->assertEqual(Set::extract('/Campaign/id', $result), array(2, 2));
	}

	function testArraySyntax() {
		$this->NumberTree =& new NumberTree();
		$this->NumberTree->initialize(3, 3);
		$this->assertIdentical($this->NumberTree->childcount(2), $this->NumberTree->childcount(array('id' => 2)));
		$this->assertIdentical($this->NumberTree->getparentnode(2), $this->NumberTree->getparentnode(array('id' => 2)));
		$this->assertIdentical($this->NumberTree->getpath(4), $this->NumberTree->getpath(array('id' => 4)));
	}
}
?>
