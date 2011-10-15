<?php
/**
 * TreeBehaviorScopedTest file
 *
 * A tree test using scope
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
 * TreeBehaviorScopedTest class
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class TreeBehaviorScopedTest extends CakeTestCase {

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
		'modelClass' => 'FlagTree',
		'leftField' => 'lft',
		'rightField' => 'rght',
		'parentField' => 'parent_id'
	);

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.flag_tree', 'core.ad', 'core.campaign', 'core.translate', 'core.number_tree_two');

/**
 * testStringScope method
 *
 * @return void
 */
	public function testStringScope() {
		$this->Tree = new FlagTree();
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
		$this->assertEqual($expected, $result);

		$this->Tree->Behaviors->attach('Tree', array('scope' => 'FlagTree.flag = 1'));
		$this->assertEqual($this->Tree->children(), array());

		$this->Tree->id = 1;
		$this->Tree->Behaviors->attach('Tree', array('scope' => 'FlagTree.flag = 1'));

		$result = $this->Tree->children();
		$expected = array(array('FlagTree' => array('id' => '2', 'name' => '1.1', 'parent_id' => '1', 'lft' => '2', 'rght' => '9', 'flag' => '1')));
		$this->assertEqual($expected, $result);

		$this->assertTrue($this->Tree->delete());
		$this->assertEqual($this->Tree->find('count'), 11);
	}

/**
 * testArrayScope method
 *
 * @return void
 */
	public function testArrayScope() {
		$this->Tree = new FlagTree();
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
		$this->assertEqual($expected, $result);

		$this->Tree->Behaviors->attach('Tree', array('scope' => array('FlagTree.flag' => 1)));
		$this->assertEqual($this->Tree->children(), array());

		$this->Tree->id = 1;
		$this->Tree->Behaviors->attach('Tree', array('scope' => array('FlagTree.flag' => 1)));

		$result = $this->Tree->children();
		$expected = array(array('FlagTree' => array('id' => '2', 'name' => '1.1', 'parent_id' => '1', 'lft' => '2', 'rght' => '9', 'flag' => '1')));
		$this->assertEqual($expected, $result);

		$this->assertTrue($this->Tree->delete());
		$this->assertEqual($this->Tree->find('count'), 11);
	}

/**
 * testMoveUpWithScope method
 *
 * @return void
 */
	public function testMoveUpWithScope() {
		$this->Ad = new Ad();
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
 * @return void
 */
	public function testMoveDownWithScope() {
		$this->Ad = new Ad();
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
 * @return void
 */
	public function testTranslatingTree() {
		$this->Tree = new FlagTree();
		$this->Tree->cacheQueries = false;
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
		$this->assertEqual($expected, $result);

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
		$this->assertEqual($expected, $result);

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
		$this->assertEqual($expected, $result);

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
		$this->assertEqual($expected, $result);
	}

/**
 * testGenerateTreeListWithSelfJoin method
 *
 * @return void
 */
	public function testAliasesWithScopeInTwoTreeAssociations() {
		extract($this->settings);
		$this->Tree = new $modelClass();
		$this->Tree->initialize(2, 2);

		$this->TreeTwo = new NumberTreeTwo();

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
		$result = $this->TreeTwo->save($data);
		$this->assertFalse(empty($result));

		$result = $this->TreeTwo->find('first');
		$expected = array('NumberTreeTwo' => array(
			'id' => 1,
			'name' => 'First',
			'number_tree_id' => $record['FlagTree']['id'],
			'parent_id' => null,
			'lft' => 1,
			'rght' => 2
		));
		$this->assertEqual($expected, $result);
	}
}
