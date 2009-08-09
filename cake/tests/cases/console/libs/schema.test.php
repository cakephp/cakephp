<?php
/**
 * SchemaShellTest Test file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2009, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2009, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Shell', 'Shell', false);

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

if (!class_exists('SchemaShell')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'schema.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestSchemaShellMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'SchemaShell', 'MockSchemaShell',
	array('in', 'out', 'hr', 'createFile', 'error', 'err', '_stop')
);

/**
 * SchemaShellTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class SchemaShellTest extends CakeTestCase {

	var $fixtures = array('core.article', 'core.user');
/**
 * startTest method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestSchemaShellMockShellDispatcher();
		$this->Task =& new MockSchemaShell($this->Dispatcher);
		$this->Task->Dispatch =& $this->Dispatcher;
	}

/**
 * endTest method
 *
 * @return void
 * @access public
 */
	function endTest() {
		ClassRegistry::flush();
	}

/**
 * test startup method
 *
 * @return void
 **/
	function testStartup() {
		$this->Task->startup();
		$this->assertTrue(isset($this->Task->Schema));
		$this->assertTrue(is_a($this->Task->Schema, 'CakeSchema'));
		$this->assertEqual($this->Task->Schema->name, 'App');
		$this->assertEqual($this->Task->Schema->file, 'schema.php');

		unset($this->Task->Schema);
		$this->Task->params = array(
			'name' => 'TestSchema'
		);
		$this->Task->startup();
		$this->assertEqual($this->Task->Schema->name, 'TestSchema');
		$this->assertEqual($this->Task->Schema->file, 'test_schema.php');
		$this->assertEqual($this->Task->Schema->connection, 'default');
		$this->assertEqual($this->Task->Schema->path, APP . 'config' . DS . 'schema');
		
		unset($this->Task->Schema);
		$this->Task->params = array(
			'file' => 'other_file.php',
			'connection' => 'test_suite',
			'path' => '/test/path'
		);
		$this->Task->startup();
		$this->assertEqual($this->Task->Schema->name, 'App');
		$this->assertEqual($this->Task->Schema->file, 'other_file.php');
		$this->assertEqual($this->Task->Schema->connection, 'test_suite');
		$this->assertEqual($this->Task->Schema->path, '/test/path');
	}

/**
 * Test View - and that it dumps the schema file to stdout
 *
 * @return void
 **/
	function testView() {
		$this->Task->startup();
		$this->Task->Schema->path = APP . 'config' . DS . 'schema';
		$this->Task->params['file'] = 'i18n.php';
		$this->Task->expectOnce('_stop');
		$this->Task->expectOnce('out');
		$this->Task->expectAt(0, 'out', array(new PatternExpectation('/class i18nSchema extends CakeSchema/')));
		$this->Task->view();
	}

/**
 * test dumping a schema file.
 *
 * @return void
 **/
	function testDump() {
		$this->Task->params = array('name' => 'i18n');
		$this->Task->startup();
		$this->Task->Schema->path = APP . 'config' . DS . 'schema';
		$this->Task->expectAt(0, 'out', array(new PatternExpectation('/create table `i18n`/i')));
		$this->Task->dump();
	}

/**
 * test dump() with sql file generation
 *
 * @return void
 **/
	function testDumpWithFileWriting() {
		//copy file.
		$file =& new File(APP . 'config' . DS . 'schema' . DS . 'i18n.php');
		$contents = $file->read();
		$file =& new File(TMP . 'tests' . DS . 'i18n.php');
		$file->write($contents);

		$this->Task->params = array('name' => 'i18n');
		$this->Task->args = array('write');
		$this->Task->startup();
		$this->Task->Schema->path = TMP . 'tests';
		$this->Task->dump();

		$sql =& new File(TMP . 'tests' . DS . 'i18n.sql');
		$contents = $sql->read();
		$this->assertPattern('/DROP TABLE/', $contents);
		$this->assertPattern('/CREATE TABLE `i18n`/', $contents);
		$this->assertPattern('/id/', $contents);
		$this->assertPattern('/model/', $contents);
		$this->assertPattern('/field/', $contents);
		$this->assertPattern('/locale/', $contents);
		$this->assertPattern('/foreign_key/', $contents);
		$this->assertPattern('/content/', $contents);

		$sql->delete();
		$file->delete();
	}
}
?>