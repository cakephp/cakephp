<?php
/**
 * AclShell Test file
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('AclShell', 'Console/Command');
App::uses('ComponentCollection', 'Controller');

/**
 * AclShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class AclShellTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('core.aco', 'core.aro', 'core.aros_aco');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Acl.database', 'test');
		Configure::write('Acl.classname', 'DbAcl');

		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock(
			'AclShell',
			array('in', 'out', 'hr', 'createFile', 'error', 'err', 'clear', 'dispatchShell'),
			array($out, $out, $in)
		);
		$collection = new ComponentCollection();
		$this->Task->Acl = new AclComponent($collection);
		$this->Task->params['datasource'] = 'test';
	}

/**
 * test that model.foreign_key output works when looking at acl rows
 *
 * @return void
 */
	public function testViewWithModelForeignKeyOutput() {
		$this->Task->command = 'view';
		$this->Task->startup();
		$data = array(
			'parent_id' => null,
			'model' => 'MyModel',
			'foreign_key' => 2,
		);
		$this->Task->Acl->Aro->create($data);
		$this->Task->Acl->Aro->save();
		$this->Task->args[0] = 'aro';

		$this->Task->expects($this->at(0))->method('out')->with('Aro tree:');
		$this->Task->expects($this->at(2))->method('out')
			->with($this->stringContains('[1] ROOT'));

		$this->Task->expects($this->at(4))->method('out')
			->with($this->stringContains('[3] Gandalf'));

		$this->Task->expects($this->at(6))->method('out')
			->with($this->stringContains('[5] MyModel.2'));

		$this->Task->view();
	}

/**
 * test view with an argument
 *
 * @return void
 */
	public function testViewWithArgument() {
		$this->Task->args = array('aro', 'admins');

		$this->Task->expects($this->at(0))->method('out')->with('Aro tree:');
		$this->Task->expects($this->at(2))->method('out')->with('  [2] admins');
		$this->Task->expects($this->at(3))->method('out')->with('    [3] Gandalf');
		$this->Task->expects($this->at(4))->method('out')->with('    [4] Elrond');

		$this->Task->view();
	}

/**
 * test the method that splits model.foreign key. and that it returns an array.
 *
 * @return void
 */
	public function testParsingModelAndForeignKey() {
		$result = $this->Task->parseIdentifier('Model.foreignKey');
		$expected = array('model' => 'Model', 'foreign_key' => 'foreignKey');

		$result = $this->Task->parseIdentifier('mySuperUser');
		$this->assertEquals($result, 'mySuperUser');

		$result = $this->Task->parseIdentifier('111234');
		$this->assertEquals($result, '111234');
	}

/**
 * test creating aro/aco nodes
 *
 * @return void
 */
	public function testCreate() {
		$this->Task->args = array('aro', 'root', 'User.1');
		$this->Task->expects($this->at(0))->method('out')->with("<success>New Aro</success> 'User.1' created.", 2);
		$this->Task->expects($this->at(1))->method('out')->with("<success>New Aro</success> 'User.3' created.", 2);
		$this->Task->expects($this->at(2))->method('out')->with("<success>New Aro</success> 'somealias' created.", 2);

		$this->Task->create();

		$Aro = ClassRegistry::init('Aro');
		$Aro->cacheQueries = false;
		$result = $Aro->read();
		$this->assertEquals($result['Aro']['model'], 'User');
		$this->assertEquals($result['Aro']['foreign_key'], 1);
		$this->assertEquals($result['Aro']['parent_id'], null);
		$id = $result['Aro']['id'];

		$this->Task->args = array('aro', 'User.1', 'User.3');
		$this->Task->create();

		$Aro = ClassRegistry::init('Aro');
		$result = $Aro->read();
		$this->assertEquals($result['Aro']['model'], 'User');
		$this->assertEquals($result['Aro']['foreign_key'], 3);
		$this->assertEquals($result['Aro']['parent_id'], $id);

		$this->Task->args = array('aro', 'root', 'somealias');
		$this->Task->create();

		$Aro = ClassRegistry::init('Aro');
		$result = $Aro->read();
		$this->assertEquals($result['Aro']['alias'], 'somealias');
		$this->assertEquals($result['Aro']['model'], null);
		$this->assertEquals($result['Aro']['foreign_key'], null);
		$this->assertEquals($result['Aro']['parent_id'], null);
	}

/**
 * test the delete method with different node types.
 *
 * @return void
 */
	public function testDelete() {
		$this->Task->args = array('aro', 'AuthUser.1');
		$this->Task->expects($this->at(0))->method('out')
			->with("<success>Aro deleted.</success>", 2);
		$this->Task->delete();

		$Aro = ClassRegistry::init('Aro');
		$result = $Aro->findById(3);
		$this->assertFalse($result);
	}

/**
 * test setParent method.
 *
 * @return void
 */
	public function testSetParent() {
		$this->Task->args = array('aro', 'AuthUser.2', 'root');
		$this->Task->setParent();

		$Aro = ClassRegistry::init('Aro');
		$result = $Aro->read(null, 4);
		$this->assertEquals($result['Aro']['parent_id'], null);
	}

/**
 * test grant
 *
 * @return void
 */
	public function testGrant() {
		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'create');
		$this->Task->expects($this->at(0))->method('out')
			->with($this->matchesRegularExpression('/granted/'), true);
		$this->Task->grant();
		$node = $this->Task->Acl->Aro->node(array('model' => 'AuthUser', 'foreign_key' => 2));
		$node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);

		$this->assertFalse(empty($node['Aco'][0]));
		$this->assertEquals($node['Aco'][0]['Permission']['_create'], 1);
	}

/**
 * test deny
 *
 * @return void
 */
	public function testDeny() {
		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'create');
		$this->Task->expects($this->at(0))->method('out')
			->with($this->stringContains('Permission denied'), true);

		$this->Task->deny();

		$node = $this->Task->Acl->Aro->node(array('model' => 'AuthUser', 'foreign_key' => 2));
		$node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);
		$this->assertFalse(empty($node['Aco'][0]));
		$this->assertEquals($node['Aco'][0]['Permission']['_create'], -1);
	}

/**
 * test checking allowed and denied perms
 *
 * @return void
 */
	public function testCheck() {
		$this->Task->expects($this->at(0))->method('out')
			->with($this->matchesRegularExpression('/not allowed/'), true);
		$this->Task->expects($this->at(1))->method('out')
			->with($this->matchesRegularExpression('/granted/'), true);
		$this->Task->expects($this->at(2))->method('out')
			->with($this->matchesRegularExpression('/is.*allowed/'), true);
		$this->Task->expects($this->at(3))->method('out')
			->with($this->matchesRegularExpression('/not.*allowed/'), true);

		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', '*');
		$this->Task->check();

		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'create');
		$this->Task->grant();

		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'create');
		$this->Task->check();

		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', '*');
		$this->Task->check();
	}

/**
 * test inherit and that it 0's the permission fields.
 *
 * @return void
 */
	public function testInherit() {
		$this->Task->expects($this->at(0))->method('out')
			->with($this->matchesRegularExpression('/Permission .*granted/'), true);
		$this->Task->expects($this->at(1))->method('out')
			->with($this->matchesRegularExpression('/Permission .*inherited/'), true);

		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'create');
		$this->Task->grant();

		$this->Task->args = array('AuthUser.2', 'ROOT/Controller1', 'all');
		$this->Task->inherit();

		$node = $this->Task->Acl->Aro->node(array('model' => 'AuthUser', 'foreign_key' => 2));
		$node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);
		$this->assertFalse(empty($node['Aco'][0]));
		$this->assertEquals($node['Aco'][0]['Permission']['_create'], 0);
	}

/**
 * test getting the path for an aro/aco
 *
 * @return void
 */
	public function testGetPath() {
		$this->Task->args = array('aro', 'AuthUser.2');
		$node = $this->Task->Acl->Aro->node(array('model' => 'AuthUser', 'foreign_key' => 2));
		$first = $node[0]['Aro']['id'];
		$second = $node[1]['Aro']['id'];
		$last = $node[2]['Aro']['id'];
		$this->Task->expects($this->at(2))->method('out')->with('['.$last.'] ROOT');
		$this->Task->expects($this->at(3))->method('out')->with('  ['.$second.'] admins');
		$this->Task->expects($this->at(4))->method('out')->with('    ['.$first.'] Elrond');
		$this->Task->getPath();
	}

/**
 * test that initdb makes the correct call.
 *
 * @return void
 */
	public function testInitDb() {
		$this->Task->expects($this->once())->method('dispatchShell')
			->with('schema create DbAcl');

		$this->Task->initdb();
	}
}
