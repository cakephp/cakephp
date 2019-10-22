<?php
/**
 * Acl Extras Shell.
 *
 * Enhances the existing Acl Shell with a few handy functions
 *
 * Copyright 2008, Mark Story.
 * 694B The Queensway
 * toronto, ontario M8Y 1K9
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2008-2009, Mark Story.
 * @link http://mark-story.com
 * @author Mark Story <mark@mark-story.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::uses('Shell', 'Console');
App::uses('Aco', 'Model');
App::uses('AclComponent', 'Controller/Component');
App::uses('Controller', 'Controller');
App::uses('AclExtras', 'AclExtras.Lib');


//Mock::generate('Aco', 'MockAco', array('children', 'verify', 'recover'));

//import test controller class names.
include dirname(dirname(dirname(__FILE__))) . DS . 'test_controllers.php';

/**
 * AclExtras Shell Test case
 *
 * @package acl_extras.tests.cases
 */
class AclExtrasShellTestCase extends CakeTestCase {

	public $fixtures = array('core.aco', 'core.aro', 'core.aros_aco');

/**
 * startTest
 *
 * @return void
 * @access public
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Acl.classname', 'DbAcl');
		Configure::write('Acl.database', 'test');

		$this->Task = $this->getMock(
			'AclExtras',
			array('in', 'out', 'hr', 'createFile', 'error', 'err', 'clear', 'getControllerList')
		);
	}

/**
 * end the test
 *
 * @return void
 **/
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
	}

/**
 * test recover
 *
 * @return void
 **/
	public function testRecover() {
		$this->Task->startup();
		$this->Task->args = array('Aco');
		$this->Task->Acl->Aco = $this->getMock('Aco', array('recover'));
		$this->Task->Acl->Aco->expects($this->once())
			->method('recover')
			->will($this->returnValue(true));

		$this->Task->expects($this->once())
			->method('out')
			->with($this->matchesRegularExpression('/recovered/'));

		$this->Task->recover();
	}

/**
 * test verify
 *
 * @return void
 **/
	public function testVerify() {
		$this->Task->startup();
		$this->Task->args = array('Aco');
		$this->Task->Acl->Aco = $this->getMock('Aco', array('verify'));
		$this->Task->Acl->Aco->expects($this->once())
			->method('verify')
			->will($this->returnValue(true));

		$this->Task->expects($this->once())
			->method('out')
			->with($this->matchesRegularExpression('/valid/'));

		$this->Task->verify();
	}

/**
 * test startup
 *
 * @return void
 **/
	public function testStartup() {
		$this->assertEqual($this->Task->Acl, null);
		$this->Task->startup();
		$this->assertInstanceOf('AclComponent', $this->Task->Acl);
	}

/**
 * clean fixtures and setup mock
 *
 * @return void
 **/
	protected function _cleanAndSetup() {
		$tableName = $this->db->fullTableName('acos');
		$this->db->execute('DELETE FROM ' . $tableName);
		$this->Task->expects($this->any())
			->method('getControllerList')
			->will($this->returnValue(array('CommentsController', 'PostsController', 'BigLongNamesController')));

		$this->Task->startup();
	}
/**
 * Test aco_update method.
 *
 * @return void
 **/
	public function testAcoUpdate() {
		$this->_cleanAndSetup();
		$this->Task->aco_update();

		$Aco = $this->Task->Acl->Aco;

		$result = $Aco->node('controllers/Comments');
		$this->assertEqual($result[0]['Aco']['alias'], 'Comments');

		$result = $Aco->children($result[0]['Aco']['id']);
		$this->assertEqual(count($result), 3);
		$this->assertEqual($result[0]['Aco']['alias'], 'add');
		$this->assertEqual($result[1]['Aco']['alias'], 'index');
		$this->assertEqual($result[2]['Aco']['alias'], 'delete');

		$result = $Aco->node('controllers/Posts');
		$this->assertEqual($result[0]['Aco']['alias'], 'Posts');
		$result = $Aco->children($result[0]['Aco']['id']);
		$this->assertEqual(count($result), 3);

		$result = $Aco->node('controllers/BigLongNames');
		$this->assertEqual($result[0]['Aco']['alias'], 'BigLongNames');
		$result = $Aco->children($result[0]['Aco']['id']);
		$this->assertEqual(count($result), 4);
	}

/**
 * test syncing of Aco records
 *
 * @return void
 **/
	public function testAcoSyncRemoveMethods() {
		$this->_cleanAndSetup();
		$this->Task->aco_update();

		$Aco = $this->Task->Acl->Aco;
		$Aco->cacheQueries = false;

		$result = $Aco->node('controllers/Comments');
		$new = array(
			'parent_id' => $result[0]['Aco']['id'],
			'alias' => 'some_method'
		);
		$Aco->create($new);
		$Aco->save();
		$children = $Aco->children($result[0]['Aco']['id']);
		$this->assertEqual(count($children), 4);

		$this->Task->aco_sync();
		$children = $Aco->children($result[0]['Aco']['id']);
		$this->assertEqual(count($children), 3);

		$method = $Aco->node('controllers/Commments/some_method');
		$this->assertFalse($method);
	}

/**
 * test adding methods with aco_update
 *
 * @return void
 **/
	public function testAcoUpdateAddingMethods() {
		$this->_cleanAndSetup();
		$this->Task->aco_update();

		$Aco = $this->Task->Acl->Aco;
		$Aco->cacheQueries = false;

		$result = $Aco->node('controllers/Comments');
		$children = $Aco->children($result[0]['Aco']['id']);
		$this->assertEqual(count($children), 3);

		$Aco->delete($children[0]['Aco']['id']);
		$Aco->delete($children[1]['Aco']['id']);
		$this->Task->aco_update();

		$children = $Aco->children($result[0]['Aco']['id']);
		$this->assertEqual(count($children), 3);
	}

/**
 * test adding controllers on sync
 *
 * @return void
 **/
	public function testAddingControllers() {
		$this->_cleanAndSetup();
		$this->Task->aco_update();

		$Aco = $this->Task->Acl->Aco;
		$Aco->cacheQueries = false;

		$result = $Aco->node('controllers/Comments');
		$Aco->delete($result[0]['Aco']['id']);

		$this->Task->aco_update();
		$newResult = $Aco->node('controllers/Comments');
		$this->assertNotEqual($newResult[0]['Aco']['id'], $result[0]['Aco']['id']);
	}
}
