<?php
/**
 * SchemaShellTest Test file
 *
 * PHP versions 4 and 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.Shells
 * @since         CakePHP v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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

/**
 * Test for Schema database management
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class SchemaShellTestSchema extends CakeSchema {

/**
 * name property
 *
 * @var string 'MyApp'
 * @access public
 */
	public $name = 'SchemaShellTest';

/**
 * connection property
 *
 * @var string 'test'
 * @access public
 */
	public $connection = 'test';

/**
 * comments property
 *
 * @var array
 * @access public
 */
	public $comments = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'),
		'post_id' => array('type' => 'integer', 'null' => false, 'default' => 0),
		'user_id' => array('type' => 'integer', 'null' => false),
		'title' => array('type' => 'string', 'null' => false, 'length' => 100),
		'comment' => array('type' => 'text', 'null' => false, 'default' => null),
		'published' => array('type' => 'string', 'null' => true, 'default' => 'N', 'length' => 1),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'updated' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => true)),
	);

/**
 * posts property
 *
 * @var array
 * @access public
 */
	public $articles = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'),
		'user_id' => array('type' => 'integer', 'null' => true, 'default' => ''),
		'title' => array('type' => 'string', 'null' => false, 'default' => 'Title'),
		'body' => array('type' => 'text', 'null' => true, 'default' => null),
		'summary' => array('type' => 'text', 'null' => true),
		'published' => array('type' => 'string', 'null' => true, 'default' => 'Y', 'length' => 1),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'updated' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => true)),
	);
}

/**
 * SchemaShellTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.Shells
 */
class SchemaShellTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.article', 'core.user', 'core.post', 'core.auth_user', 'core.author', 
		'core.comment', 'core.test_plugin_comment'
	);

/**
 * startTest method
 *
 * @return void
 */
	public function startTest() {
		$this->Dispatcher = $this->getMock(
			'ShellDispatcher', 
			array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment', 'clear')
		);
		$this->Shell = $this->getMock(
			'SchemaShell',
			array('in', 'out', 'hr', 'createFile', 'error', 'err', '_stop'),
			array(&$this->Dispatcher)
		);
	}

/**
 * endTest method
 *
 * @return void
 */
	public function endTest() {
		ClassRegistry::flush();
	}

/**
 * test startup method
 *
 * @return void
 */
	public function testStartup() {
		$this->Shell->startup();
		$this->assertTrue(isset($this->Shell->Schema));
		$this->assertTrue(is_a($this->Shell->Schema, 'CakeSchema'));
		$this->assertEqual(strtolower($this->Shell->Schema->name), strtolower(APP_DIR));
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
			'connection' => 'test',
			'path' => '/test/path'
		);
		$this->Shell->startup();
		$this->assertEqual(strtolower($this->Shell->Schema->name), strtolower(APP_DIR));
		$this->assertEqual($this->Shell->Schema->file, 'other_file.php');
		$this->assertEqual($this->Shell->Schema->connection, 'test');
		$this->assertEqual($this->Shell->Schema->path, '/test/path');
	}

/**
 * Test View - and that it dumps the schema file to stdout
 *
 * @return void
 */
	public function testView() {
		$this->Shell->startup();
		$this->Shell->Schema->path = APP . 'config' . DS . 'schema';
		$this->Shell->params['file'] = 'i18n.php';
		$this->Shell->expects($this->once())->method('_stop');
		$this->Shell->expects($this->once())->method('out');
		$this->Shell->view();
	}

/**
 * test that view() can find plugin schema files.
 *
 * @return void
 */
	public function testViewWithPlugins() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));
		$this->Shell->args = array('TestPlugin.schema');
		$this->Shell->startup();
		$this->Shell->expects($this->exactly(2))->method('_stop');
		$this->Shell->expects($this->exactly(2))->method('out');
		$this->Shell->view();

		$this->Shell->args = array();
		$this->Shell->params = array('plugin' => 'TestPlugin');
		$this->Shell->startup();
		$this->Shell->view();

		App::build();
	}

/**
 * test dump() with sql file generation
 *
 * @return void
 */
	public function testDumpWithFileWriting() {
		$this->Shell->params = array(
			'name' => 'i18n',
			'write' => TMP . 'tests' . DS . 'i18n.sql'
		);
		$this->Shell->expects($this->once())->method('_stop');
		$this->Shell->startup();
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
	}

/**
 * test that dump() can find and work with plugin schema files.
 *
 * @return void
 */
	public function testDumpFileWritingWithPlugins() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));
		$this->Shell->args = array('TestPlugin.TestPluginApp');
		$this->Shell->params = array(
			'connection' => 'test',
			'write' => TMP . 'tests' . DS . 'dump_test.sql'
		);
		$this->Shell->startup();
		$this->Shell->expects($this->once())->method('_stop');
		$this->Shell->dump();

		$file =& new File(TMP . 'tests' . DS . 'dump_test.sql');
		$contents = $file->read();

		$this->assertPattern('/CREATE TABLE `acos`/', $contents);
		$this->assertPattern('/id/', $contents);
		$this->assertPattern('/model/', $contents);

		$file->delete();
		App::build();
	}

/**
 * test generate with snapshot generation
 *
 * @return void
 */
	public function testGenerateSnapshot() {
		$this->Shell->path = TMP;
		$this->Shell->params['file'] = 'schema.php';
		$this->Shell->args = array('snapshot');
		$this->Shell->Schema = $this->getMock('CakeSchema');
		$this->Shell->Schema->expects($this->at(0))->method('read')->will($this->returnValue(array('schema data')));
		$this->Shell->Schema->expects($this->at(0))->method('write')->will($this->returnValue(true));

		$this->Shell->Schema->expects($this->at(1))->method('read');
		$this->Shell->Schema->expects($this->at(1))->method('write')->with(array('schema data', 'file' => 'schema_0.php'));

		$this->Shell->generate();
	}

/**
 * test generate without a snapshot.
 *
 * @return void
 */
	public function testGenerateNoOverwrite() {
		touch(TMP . 'schema.php');
		$this->Shell->params['file'] = 'schema.php';
		$this->Shell->args = array();

		$this->Shell->expects($this->once())->method('in')->will($this->returnValue('q'));
		$this->Shell->Schema = $this->getMock('CakeSchema');
		$this->Shell->Schema->path = TMP;
		$this->Shell->Schema->expects($this->never())->method('read');

		$result = $this->Shell->generate();
		unlink(TMP . 'schema.php');
	}

/**
 * test generate with overwriting of the schema files.
 *
 * @return void
 */
	public function testGenerateOverwrite() {
		touch(TMP . 'schema.php');
		$this->Shell->params['file'] = 'schema.php';
		$this->Shell->args = array();

		$this->Shell->expects($this->once())->method('in')->will($this->returnValue('o'));

		$this->Shell->expects($this->at(2))->method('out')
			->with(new PHPUnit_Framework_Constraint_PCREMatch('/Schema file:\s[a-z\.]+\sgenerated/'));
	
		$this->Shell->Schema = $this->getMock('CakeSchema');
		$this->Shell->Schema->path = TMP;
		$this->Shell->Schema->expects($this->once())->method('read')->will($this->returnValue(array('schema data')));
		$this->Shell->Schema->expects($this->once())->method('write')->will($this->returnValue(true));

		$this->Shell->Schema->expects($this->once())->method('read');
		$this->Shell->Schema->expects($this->once())->method('write')
			->with(array('schema data', 'file' => 'schema.php'));

		$this->Shell->generate();
		unlink(TMP . 'schema.php');
	}

/**
 * test that generate() can read plugin dirs and generate schema files for the models
 * in a plugin.
 *
 * @return void
 */
	public function testGenerateWithPlugins() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));
		$this->Shell->params = array(
			'plugin' => 'TestPlugin',
			'connection' => 'test'
		);
		$this->Shell->startup();
		$this->Shell->Schema->path = TMP . 'tests' . DS;

		$this->Shell->generate();
		$file = new File(TMP . 'tests' . DS . 'schema.php');
		$contents = $file->read();

		$this->assertPattern('/class TestPluginSchema/', $contents);
		$this->assertPattern('/var \$posts/', $contents);
		$this->assertPattern('/var \$auth_users/', $contents);
		$this->assertPattern('/var \$authors/', $contents);
		$this->assertPattern('/var \$test_plugin_comments/', $contents);
		$this->assertNoPattern('/var \$users/', $contents);
		$this->assertNoPattern('/var \$articles/', $contents);

		$file->delete();
		App::build();
	}

/**
 * Test schema run create with no table args.
 *
 * @return void
 */
	public function testCreateNoArgs() {
		$this->Shell->params = array(
			'connection' => 'test',
			'path' => APP . 'config' . DS . 'sql'
		);
		$this->Shell->args = array('i18n');
		$this->Shell->startup();
		$this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
		$this->Shell->create();

		$db = ConnectionManager::getDataSource('test');
		$sources = $db->listSources();
		$this->assertTrue(in_array($db->config['prefix'] . 'i18n', $sources));

		$schema = new i18nSchema();
		$db->execute($db->dropSchema($schema));
	}

/**
 * Test schema run create with no table args.
 *
 * @return void
 */
	public function testCreateWithTableArgs() {
		$this->Shell->params = array(
			'connection' => 'test',
			'name' => 'DbAcl',
			'path' => APP . 'config' . DS . 'schema'
		);
		$this->Shell->args = array('DbAcl', 'acos');
		$this->Shell->startup();
		$this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
		$this->Shell->create();

		$db =& ConnectionManager::getDataSource('test');
		$sources = $db->listSources();
		$this->assertTrue(in_array($db->config['prefix'] . 'acos', $sources));
		$this->assertFalse(in_array($db->config['prefix'] . 'aros', $sources));
		$this->assertFalse(in_array('aros_acos', $sources));

		$db->execute('DROP TABLE ' . $db->config['prefix'] . 'acos');
	}

/**
 * test run update with a table arg.
 *
 * @return void
 */
	public function testUpdateWithTable() {
		$this->Shell->params = array(
			'connection' => 'test',
			'f' => true
		);
		$this->Shell->args = array('SchemaShellTest', 'articles');
		$this->Shell->startup();
		$this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
		$this->Shell->update();

		$article =& new Model(array('name' => 'Article', 'ds' => 'test'));
		$fields = $article->schema();
		$this->assertTrue(isset($fields['summary']));

	}

/**
 * test that the plugin param creates the correct path in the schema object.
 *
 * @return void
 */
	public function testPluginParam() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));
		$this->Shell->params = array(
			'plugin' => 'TestPlugin',
			'connection' => 'test'
		);
		$this->Shell->startup();
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin' . DS . 'config' . DS . 'schema';
		$this->assertEqual($this->Shell->Schema->path, $expected);
		
		App::build();
	}

/**
 * test that using Plugin.name with write.
 *
 * @return void
 */
	public function testPluginDotSyntaxWithCreate() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));
		$this->Shell->params = array(
			'connection' => 'test'
		);
		$this->Shell->args = array('TestPlugin.TestPluginApp');
		$this->Shell->startup();
		$this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
		$this->Shell->create();

		$db =& ConnectionManager::getDataSource('test');
		$sources = $db->listSources();
		$this->assertTrue(in_array($db->config['prefix'] . 'acos', $sources));

		$db->execute('DROP TABLE ' . $db->config['prefix'] . 'acos');
		App::build();
	}
}
