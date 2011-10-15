<?php
/**
 * SchemaShellTest Test file
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('CakeSchema', 'Model');
App::uses('SchemaShell', 'Console/Command');

/**
 * Test for Schema database management
 *
 * @package       Cake.Test.Case.Console.Command
 */
class SchemaShellTestSchema extends CakeSchema {

/**
 * name property
 *
 * @var string 'MyApp'
 */
	public $name = 'SchemaShellTest';

/**
 * connection property
 *
 * @var string 'test'
 */
	public $connection = 'test';

/**
 * comments property
 *
 * @var array
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
 * @package       Cake.Test.Case.Console.Command
 */
class SchemaShellTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('core.article', 'core.user', 'core.post', 'core.auth_user', 'core.author',
		'core.comment', 'core.test_plugin_comment'
	);

/**
 * setup method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Shell = $this->getMock(
			'SchemaShell',
			array('in', 'out', 'hr', 'createFile', 'error', 'err', '_stop'),
			array($out, $out, $in)
		);
	}

/**
 * endTest method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		if (!empty($this->file) && $this->file instanceof File) {
			$this->file->delete();
			unset($this->file);
		}
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

		$this->Shell->Schema = null;
		$this->Shell->params = array(
			'name' => 'TestSchema'
		);
		$this->Shell->startup();
		$this->assertEqual($this->Shell->Schema->name, 'TestSchema');
		$this->assertEqual($this->Shell->Schema->file, 'test_schema.php');
		$this->assertEqual($this->Shell->Schema->connection, 'default');
		$this->assertEqual($this->Shell->Schema->path, APP . 'Config' . DS . 'Schema');

		$this->Shell->Schema = null;
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
		$this->Shell->Schema->path = APP . 'Config' . DS . 'Schema';
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
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load('TestPlugin');
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
		CakePlugin::unload();
	}

/**
 * test dump() with sql file generation
 *
 * @return void
 */
	public function testDumpWithFileWriting() {
		$this->Shell->params = array(
			'name' => 'i18n',
			'connection' => 'test',
			'write' => TMP . 'tests' . DS . 'i18n.sql'
		);
		$this->Shell->expects($this->once())->method('_stop');
		$this->Shell->startup();
		$this->Shell->dump();

		$this->file = new File(TMP . 'tests' . DS . 'i18n.sql');
		$contents = $this->file->read();
		$this->assertPattern('/DROP TABLE/', $contents);
		$this->assertPattern('/CREATE TABLE.*?i18n/', $contents);
		$this->assertPattern('/id/', $contents);
		$this->assertPattern('/model/', $contents);
		$this->assertPattern('/field/', $contents);
		$this->assertPattern('/locale/', $contents);
		$this->assertPattern('/foreign_key/', $contents);
		$this->assertPattern('/content/', $contents);
	}

/**
 * test that dump() can find and work with plugin schema files.
 *
 * @return void
 */
	public function testDumpFileWritingWithPlugins() {
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load('TestPlugin');
		$this->Shell->args = array('TestPlugin.TestPluginApp');
		$this->Shell->params = array(
			'connection' => 'test',
			'write' => TMP . 'tests' . DS . 'dump_test.sql'
		);
		$this->Shell->startup();
		$this->Shell->expects($this->once())->method('_stop');
		$this->Shell->dump();

		$this->file = new File(TMP . 'tests' . DS . 'dump_test.sql');
		$contents = $this->file->read();

		$this->assertPattern('/CREATE TABLE.*?test_plugin_acos/', $contents);
		$this->assertPattern('/id/', $contents);
		$this->assertPattern('/model/', $contents);

		$this->file->delete();
		App::build();
		CakePlugin::unload();
	}

/**
 * test generate with snapshot generation
 *
 * @return void
 */
	public function testGenerateSnapshot() {
		$this->Shell->path = TMP;
		$this->Shell->params['file'] = 'schema.php';
		$this->Shell->params['force'] = false;
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
		$this->Shell->params['force'] = false;
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
		$this->Shell->params['force'] = false;
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
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), true);
		CakePlugin::load('TestPlugin');

		$this->db->cacheSources = false;
		$this->Shell->params = array(
			'plugin' => 'TestPlugin',
			'connection' => 'test',
			'force' => false
		);
		$this->Shell->startup();
		$this->Shell->Schema->path = TMP . 'tests' . DS;

		$this->Shell->generate();
		$this->file = new File(TMP . 'tests' . DS . 'schema.php');
		$contents = $this->file->read();

		$this->assertPattern('/class TestPluginSchema/', $contents);
		$this->assertPattern('/var \$posts/', $contents);
		$this->assertPattern('/var \$auth_users/', $contents);
		$this->assertPattern('/var \$authors/', $contents);
		$this->assertPattern('/var \$test_plugin_comments/', $contents);
		$this->assertNoPattern('/var \$users/', $contents);
		$this->assertNoPattern('/var \$articles/', $contents);
		CakePlugin::unload();
	}

/**
 * Test schema run create with no table args.
 *
 * @return void
 */
	public function testCreateNoArgs() {
		$this->Shell->params = array(
			'connection' => 'test'
		);
		$this->Shell->args = array('i18n');
		$this->Shell->startup();
		$this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
		$this->Shell->create();

		$db = ConnectionManager::getDataSource('test');

		$db->cacheSources = false;
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
		$db = ConnectionManager::getDataSource('test');
		$sources = $db->listSources();
		if (in_array('acos', $sources)) {
			$this->markTestSkipped('acos table already exists, cannot try to create it again.');
		}
		$this->Shell->params = array(
			'connection' => 'test',
			'name' => 'DbAcl',
			'path' => APP . 'Config' . DS . 'Schema'
		);
		$this->Shell->args = array('DbAcl', 'acos');
		$this->Shell->startup();
		$this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
		$this->Shell->create();

		$db = ConnectionManager::getDataSource('test');
		$db->cacheSources = false;
		$sources = $db->listSources();
		$this->assertTrue(in_array($db->config['prefix'] . 'acos', $sources), 'acos should be present.');
		$this->assertFalse(in_array($db->config['prefix'] . 'aros', $sources), 'aros should not be found.');
		$this->assertFalse(in_array('aros_acos', $sources), 'aros_acos should not be found.');

		$schema = new DbAclSchema();
		$db->execute($db->dropSchema($schema, 'acos'));
	}

/**
 * test run update with a table arg.
 *
 * @return void
 */
	public function testUpdateWithTable() {
		$this->Shell = $this->getMock(
			'SchemaShell',
			array('in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'),
			array(&$this->Dispatcher)
		);

		$this->Shell->params = array(
			'connection' => 'test',
			'force' => true
		);
		$this->Shell->args = array('SchemaShellTest', 'articles');
		$this->Shell->startup();
		$this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
		$this->Shell->expects($this->once())->method('_run')
			->with($this->arrayHasKey('articles'), 'update', $this->isInstanceOf('CakeSchema'));

		$this->Shell->update();
	}

/**
 * test that the plugin param creates the correct path in the schema object.
 *
 * @return void
 */
	public function testPluginParam() {
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load('TestPlugin');
		$this->Shell->params = array(
			'plugin' => 'TestPlugin',
			'connection' => 'test'
		);
		$this->Shell->startup();
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'Config' . DS . 'Schema';
		$this->assertEqual($this->Shell->Schema->path, $expected);
		CakePlugin::unload();
	}

/**
 * test that using Plugin.name with write.
 *
 * @return void
 */
	public function testPluginDotSyntaxWithCreate() {
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load('TestPlugin');
		$this->Shell->params = array(
			'connection' => 'test'
		);
		$this->Shell->args = array('TestPlugin.TestPluginApp');
		$this->Shell->startup();
		$this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
		$this->Shell->create();

		$db = ConnectionManager::getDataSource('test');
		$sources = $db->listSources();
		$this->assertTrue(in_array($db->config['prefix'] . 'test_plugin_acos', $sources));

		$schema = new TestPluginAppSchema();
		$db->execute($db->dropSchema($schema, 'test_plugin_acos'));
		CakePlugin::unload();
	}
}
