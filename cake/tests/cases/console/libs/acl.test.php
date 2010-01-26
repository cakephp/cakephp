<?php
/* SVN FILE: $Id$ */
/**
 * AclShell Test file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakefoundation.org/projects/info/cakephp CakePHP Project
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

if (!class_exists('AclShell')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'acl.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestAclShellMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'AclShell', 'MockAclShell',
	array('in', 'out', 'hr', 'createFile')
);
/**
 * AclShellTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class AclShellTest extends CakeTestCase {
	var $fixtures = array('core.aco', 'core.aro', 'core.aros_aco');
/**
 * configure Configure for testcase
 *
 * @return void
 **/
	function startCase() {
		$this->_aclDb = Configure::read('Acl.database');
		$this->_aclClass = Configure::read('Acl.classname');

		Configure::write('Acl.database', 'test_suite');
		Configure::write('Acl.classname', 'DbAcl');
	}

/**
 * restore Environment settings
 *
 * @return void
 **/
	function endCase() {
		Configure::write('Acl.database', $this->_aclDb);
		Configure::write('Acl.classname', $this->_aclClass);
	}
/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestAclShellMockShellDispatcher();
		$this->Task =& new MockAclShell($this->Dispatcher);
		$this->Task->Dispatch =& $this->Dispatcher;
		$this->Task->params['datasource'] = 'test_suite';
	}

/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function endTest() {
		ClassRegistry::flush();
	}

/**
 * test that model.foreign_key output works when looking at acl rows
 *
 * @return void
 **/
	function testViewWithModelForeignKeyOutput() {
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

		$this->Task->expectAt(0, 'out', array('Aro tree:'));
		$this->Task->expectAt(1, 'out', array(new PatternExpectation('/\[1\]ROOT/')));
		$this->Task->expectAt(3, 'out', array(new PatternExpectation('/\[3\]Gandalf/')));
		$this->Task->expectAt(5, 'out', array(new PatternExpectation('/\[5\]MyModel.2/')));

		$this->Task->view();
	}
}
?>