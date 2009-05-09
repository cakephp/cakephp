<?php
/* SVN FILE: $Id$ */
/**
 * TestTaskTest file
 *
 * Test Case for test generation shell task
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.0.7726
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'Shell');

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

if (!class_exists('ModelTask')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'model.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestModelTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);

Mock::generatePartial(
	'ModelTask', 'MockModelTask',
	array('in', 'out', 'err', 'createFile', '_stop')
);

Mock::generate(
	'Model', 'MockModelTaskModel'
);
/**
 * ModelTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class ModelTaskTest extends CakeTestCase {
/**
 * fixtures
 *
 * @var array
 **/
	var $fixtures = array('core.article', 'core.comment');

/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestModelTaskMockShellDispatcher();
		$this->Task =& new MockModelTask($this->Dispatcher);
		$this->Task->Dispatch = new $this->Dispatcher;
	}

/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function endTest() {
		unset($this->Task, $this->Dispatcher);
		ClassRegistry::flush();
	}

/**
 * Test that listAll scans the database connection and lists all the tables in it.s
 *
 * @return void
 **/
	function testListAll() {
		$this->Task->expectAt(1, 'out', array('1. Article'));
		$this->Task->expectAt(2, 'out', array('2. Comment'));
		$result = $this->Task->listAll('test_suite');
		$expected = array('articles', 'comments');
		$this->assertEqual($result, $expected);
		
		$this->Task->expectAt(4, 'out', array('1. Article'));
		$this->Task->expectAt(5, 'out', array('2. Comment'));
		$this->Task->connection = 'test_suite';
		$result = $this->Task->listAll();
		$expected = array('articles', 'comments');
		$this->assertEqual($result, $expected);
	}

/**
 * Test that listAll scans the database connection and lists all the tables in it.s
 *
 * @return void
 **/
	function testGetName() {
		$this->Task->setReturnValue('in', 1);

		$this->Task->setReturnValueAt(0, 'in', 'q');
		$this->Task->expectOnce('_stop');
		$this->Task->getName('test_suite');

		$this->Task->setReturnValueAt(1, 'in', 1);
		$result = $this->Task->getName('test_suite');
		$expected = 'Article';
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(2, 'in', 2);
		$result = $this->Task->getName('test_suite');
		$expected = 'Comment';
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(3, 'in', 10);
		$result = $this->Task->getName('test_suite');
		$this->Task->expectOnce('err');
	}

/**
 * Test table name interactions
 *
 * @return void
 **/
	function testGetTableName() {
		$this->Task->setReturnValueAt(0, 'in', 'y');
		$result = $this->Task->getTable('Article', 'test_suite');
		$expected = 'articles';
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(1, 'in', 'n');
		$this->Task->setReturnValueAt(2, 'in', 'my_table');
		$result = $this->Task->getTable('Article', 'test_suite');
		$expected = 'my_table';
		$this->assertEqual($result, $expected);
	}
/**
 * test that initializing the validations works.
 *
 * @return void
 **/
	function testInitValidations() {
		$result = $this->Task->initValidations();
		$this->assertTrue(in_array('notempty', $result));
	}

/**
 * test that individual field validation works, with interactive = false
 * tests the guessing features of validation
 *
 * @return void
 **/
	function testFieldValidationGuessing() {
		$this->Task->interactive = false;
		$this->Task->initValidations();

		$result = $this->Task->fieldValidation('text', array('type' => 'string', 'length' => 10, 'null' => false));
		$expected = array('notempty' => 'notempty');

		$result = $this->Task->fieldValidation('text', array('type' => 'date', 'length' => 10, 'null' => false));
		$expected = array('date' => 'date');

		$result = $this->Task->fieldValidation('text', array('type' => 'time', 'length' => 10, 'null' => false));
		$expected = array('time' => 'time');

		$result = $this->Task->fieldValidation('email', array('type' => 'string', 'length' => 10, 'null' => false));
		$expected = array('email' => 'email');
		
		$result = $this->Task->fieldValidation('test', array('type' => 'integer', 'length' => 10, 'null' => false));
		$expected = array('numeric' => 'numeric');

		$result = $this->Task->fieldValidation('test', array('type' => 'boolean', 'length' => 10, 'null' => false));
		$expected = array('numeric' => 'numeric');
	}

/**
 * test that interactive field validation works and returns multiple validators.
 *
 * @return void
 **/
	function testInteractiveFieldValidation() {
		$this->Task->initValidations();
		$this->Task->interactive = true;
		$this->Task->setReturnValueAt(0, 'in', '20');
		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->setReturnValueAt(2, 'in', '16');
		$this->Task->setReturnValueAt(3, 'in', 'n');

		$result = $this->Task->fieldValidation('text', array('type' => 'string', 'length' => 10, 'null' => false));
		$expected = array('notempty' => 'notempty', 'maxlength' => 'maxlength');
		$this->assertEqual($result, $expected);
	}

/**
 * test the validation Generation routine
 *
 * @return void
 **/
	function testNonInteractiveDoValidation() {
		$Model =& new MockModelTaskModel();
		$Model->primaryKey = 'id';
		$Model->setReturnValue('schema', array(
			'id' => array(
				'type' => 'integer',
				'length' => 11,
				'null' => false,
				'key' => 'primary',
			),
			'name' => array(
				'type' => 'string',
				'length' => 20,
				'null' => false,
			),
			'email' => array(
				'type' => 'string',
				'length' => 255,
				'null' => false,
			),
			'some_date' => array(
				'type' => 'date',
				'length' => '',
				'null' => false,
			),
			'some_time' => array(
				'type' => 'time',
				'length' => '',
				'null' => false,
			),
			'created' => array(
				'type' => 'datetime',
				'length' => '',
				'null' => false,
			)
		));
		$this->Task->interactive = false;

		$result = $this->Task->doValidation($Model);
		$expected = array(
			'name' => array(
				'notempty' => 'notempty'
			),
			'email' => array(
				'email' => 'email',
			),
			'some_date' => array(
				'date' => 'date'
			),
			'some_time' => array(
				'time' => 'time'
			),
		);
		$this->assertEqual($result, $expected);
	}
}
?>