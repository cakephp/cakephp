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
 * @subpackage    cake.tests.cases.console.libs.Shells
 * @since         CakePHP v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Shell', 'Shell', false);
App::import('Model', 'CakeSchema', false);

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

Mock::generate('CakeSchema', 'MockSchemaCakeSchema');

/**
 * SchemaShellTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.Shells
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
		$this->Shell =& new MockSchemaShell($this->Dispatcher);
		$this->Shell->Dispatch =& $this->Dispatcher;
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
		$this->Shell->startup();
		$this->assertTrue(isset($this->Shell->Schema));
		$this->assertTrue(is_a($this->Shell->Schema, 'CakeSchema'));
		$this->assertEqual($this->Shell->Schema->name, 'App');
		$this->assertEqual($this->Shell->Schema->file, 'schema.php');

		unset($this->Shell->Schema);
		$this->Shell->params = array(
			'name' => 'TestSchema'
		);
		$this->Shell->startup();
		$this->assertEqual($this->Shell->Schema->name, 'TestSchema');
		$this->assertEqual($this->Shell->Schema->file, 'test_schema.php');
		$this->assertEqual($this->Shell->Schema->connection, 'default');
		$this->assertEqual($this->Shell->Schema->path, APP . 'config' . DS . 'schema');
		
		unset($this->Shell->Schema);
		$this->Shell->params = array(
			'file' => 'other_file.php',
			'connection' => 'test_suite',
			'path' => '/test/path'
		);
		$this->Shell->startup();
		$this->assertEqual($this->Shell->Schema->name, 'App');
		$this->assertEqual($this->Shell->Schema->file, 'other_file.php');
		$this->assertEqual($this->Shell->Schema->connection, 'test_suite');
		$this->assertEqual($this->Shell->Schema->path, '/test/path');
	}

/**
 * Test View - and that it dumps the schema file to stdout
 *
 * @return void
 **/
	function testView() {
		$this->Shell->startup();
		$this->Shell->Schema->path = APP . 'config' . DS . 'schema';
		$this->Shell->params['file'] = 'i18n.php';
		$this->Shell->expectOnce('_stop');
		$this->Shell->expectOnce('out');
		$this->Shell->expectAt(0, 'out', array(new PatternExpectation('/class i18nSchema extends CakeSchema/')));
		$this->Shell->view();
	}

/**
 * test dumping a schema file.
 *
 * @return void
 **/
	function testDump() {
		$this->Shell->params = array('name' => 'i18n');
		$this->Shell->startup();
		$this->Shell->Schema->path = APP . 'config' . DS . 'schema';
		$this->Shell->expectAt(0, 'out', array(new PatternExpectation('/create table `i18n`/i')));
		$this->Shell->dump();
	}

/**
 * test dump() with sql file generation
 *
 * @return void
 **/
	function testDumpWithFileWriting() {
		$file =& new File(APP . 'config' . DS . 'schema' . DS . 'i18n.php');
		$contents = $file->read();
		$file =& new File(TMP . 'tests' . DS . 'i18n.php');
		$file->write($contents);

		$this->Shell->params = array('name' => 'i18n');
		$this->Shell->args = array('write');
		$this->Shell->startup();
		$this->Shell->Schema->path = TMP . 'tests';
		$this->Shell->dump();

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

/**
 * test generate with snapshot generation
 *
 * @return void
 */
	function testGenerateSnaphot() {
		$this->Shell->path = TMP;
		$this->Shell->params['file'] = 'schema.php';
		$this->Shell->args = array('snapshot');
		$this->Shell->Schema =& new MockSchemaCakeSchema();
		$this->Shell->Schema->setReturnValue('read', array('schema data'));
		$this->Shell->Schema->setReturnValue('write', true);

		$this->Shell->Schema->expectOnce('read');
		$this->Shell->Schema->expectOnce('write', array(array('schema data', 'file' => 'schema_1.php')));

		$this->Shell->generate();
	}

/**
 * test generate without a snapshot.
 *
 * @return void
 **/
	function testGenerateNoOverwrite() {
		touch(TMP . 'schema.php');
		$this->Shell->params['file'] = 'schema.php';
		$this->Shell->args = array();

		$this->Shell->setReturnValue('in', 'q');
		$this->Shell->Schema =& new MockSchemaCakeSchema();
		$this->Shell->Schema->path = TMP;
		$this->Shell->Schema->expectNever('read');

		$result = $this->Shell->generate();
		unlink(TMP . 'schema.php');
	}

/**
 * test generate with overwriting of the schema files.
 *
 * @return void
 **/
	function testGenerateOverwrite() {
		touch(TMP . 'schema.php');
		$this->Shell->params['file'] = 'schema.php';
		$this->Shell->args = array();

		$this->Shell->setReturnValue('in', 'o');
		$this->Shell->expectAt(1, 'out', array(new PatternExpectation('/Schema file:\s[a-z\.]+\sgenerated/')));
		$this->Shell->Schema =& new MockSchemaCakeSchema();
		$this->Shell->Schema->path = TMP;
		$this->Shell->Schema->setReturnValue('read', array('schema data'));
		$this->Shell->Schema->setReturnValue('write', true);

		$this->Shell->Schema->expectOnce('read');
		$this->Shell->Schema->expectOnce('write', array(array('schema data', 'file' => 'schema.php')));

		$this->Shell->generate();
		unlink(TMP . 'schema.php');
	}

/**
 * Test schema run create with no table args.
 *
 * @return void
 **/
	function testRunCreateNoArgs() {
		$this->Shell->params = array(
			'connection' => 'test_suite',
			'name' => 'i18n',
			'path' => APP . 'config' . DS . 'schema'
		);
		$this->Shell->args = array('create');
		$this->Shell->startup();
		$this->Shell->setReturnValue('in', 'y');
		$this->Shell->run();
		
		$db =& ConnectionManager::getDataSource('test_suite');
		$sources = $db->listSources();
		$this->assertTrue(in_array('i18n', $sources));
	}

/**
 * Test schema run create with no table args.
 *
 * @return void
 **/
	function testRunCreateWithTableArgs() {
		$this->Shell->params = array(
			'connection' => 'test_suite',
			'name' => 'DbAcl',
			'path' => APP . 'config' . DS . 'schema'
		);
		$this->Shell->args = array('create', 'acos');
		$this->Shell->startup();
		$this->Shell->setReturnValue('in', 'y');
		$this->Shell->run();

		$db =& ConnectionManager::getDataSource('test_suite');
		$sources = $db->listSources();
		$this->assertTrue(in_array('acos', $sources));
		$this->assertFalse(in_array('aros', $sources));
		$this->assertFalse(in_array('aros_acos', $sources));
	}

}
?>