<?php
/**
 * DboMysqliTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
App::import('Core', array('Model', 'DataSource', 'DboSource', 'DboMysqli'));

/**
 * DboMysqliTestDb class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class DboMysqliTestDb extends DboMysqli {

/**
 * simulated property
 *
 * @var array
 * @access public
 */
	public $simulated = array();

/**
 * testing property
 *
 * @var bool true
 * @access public
 */
	public $testing = true;

/**
 * execute method
 *
 * @param mixed $sql
 * @access protected
 * @return void
 */
	function _execute($sql) {
		if ($this->testing) {
			$this->simulated[] = $sql;
			return null;
		}
		return parent::_execute($sql);
	}

/**
 * getLastQuery method
 *
 * @access public
 * @return void
 */
	function getLastQuery() {
		return $this->simulated[count($this->simulated) - 1];
	}
}

/**
 * MysqliTestModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class MysqliTestModel extends Model {

/**
 * name property
 *
 * @var string 'MysqlTestModel'
 * @access public
 */
	public $name = 'MysqliTestModel';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	public $useTable = false;

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		return array(
			'id'		=> array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'client_id'	=> array('type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'),
			'name'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'login'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'passwd'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'addr_1'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'addr_2'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'),
			'zip_code'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'city'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'country'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'phone'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'fax'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'url'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'email'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'comments'	=> array('type' => 'text', 'null' => '1', 'default' => '', 'length' => ''),
			'last_login'=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
			'created'	=> array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		);
	}
}

/**
 * DboMysqliTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources.dbo
 */
class DboMysqliTest extends CakeTestCase {
	public $fixtures = array('core.datatype');
/**
 * The Dbo instance to be tested
 *
 * @var DboSource
 * @access public
 */
	public $Dbo = null;

/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function setUp() {
		$this->Dbo = ConnectionManager::getDataSource('test');
		if ($this->Dbo->config['driver'] !== 'mysqli') {
			$this->markTestSkipped('The MySQLi extension is not available.');
		}
		$this->model = new MysqliTestModel();
	}

/**
 * Sets up a Dbo class instance for testing
 *
 */
	public function tearDown() {
		unset($this->model);
		ClassRegistry::flush();
	}

/**
 * testIndexDetection method
 *
 * @return void
 */
	public function testIndexDetection() {
		$this->Dbo->cacheSources = false;

		$name = $this->Dbo->fullTableName('simple');
		$this->Dbo->query('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id));');
		$expected = array('PRIMARY' => array('column' => 'id', 'unique' => 1));
		$result = $this->Dbo->index($name, false);
		$this->assertEqual($expected, $result);
		$this->Dbo->query('DROP TABLE ' . $name);

		$name = $this->Dbo->fullTableName('with_a_key');
		$this->Dbo->query('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ));');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
		);
		$result = $this->Dbo->index($name, false);
		$this->assertEqual($expected, $result);
		$this->Dbo->query('DROP TABLE ' . $name);

		$name = $this->Dbo->fullTableName('with_two_keys');
		$this->Dbo->query('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ), KEY `pointless_small_int` ( `small_int` ));');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
			'pointless_small_int' => array('column' => 'small_int', 'unique' => 0),
		);
		$result = $this->Dbo->index($name, false);
		$this->assertEqual($expected, $result);
		$this->Dbo->query('DROP TABLE ' . $name);

		$name = $this->Dbo->fullTableName('with_compound_keys');
		$this->Dbo->query('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ), KEY `pointless_small_int` ( `small_int` ), KEY `one_way` ( `bool`, `small_int` ));');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
			'pointless_small_int' => array('column' => 'small_int', 'unique' => 0),
			'one_way' => array('column' => array('bool', 'small_int'), 'unique' => 0),
		);
		$result = $this->Dbo->index($name, false);
		$this->assertEqual($expected, $result);
		$this->Dbo->query('DROP TABLE ' . $name);

		$name = $this->Dbo->fullTableName('with_multiple_compound_keys');
		$this->Dbo->query('CREATE TABLE ' . $name . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id), KEY `pointless_bool` ( `bool` ), KEY `pointless_small_int` ( `small_int` ), KEY `one_way` ( `bool`, `small_int` ), KEY `other_way` ( `small_int`, `bool` ));');
		$expected = array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'pointless_bool' => array('column' => 'bool', 'unique' => 0),
			'pointless_small_int' => array('column' => 'small_int', 'unique' => 0),
			'one_way' => array('column' => array('bool', 'small_int'), 'unique' => 0),
			'other_way' => array('column' => array('small_int', 'bool'), 'unique' => 0),
		);
		$result = $this->Dbo->index($name, false);
		$this->assertEqual($expected, $result);
		$this->Dbo->query('DROP TABLE ' . $name);
	}

/**
 * testColumn method
 *
 * @return void
 */
	public function testColumn() {
		$result = $this->Dbo->column('varchar(50)');
		$expected = 'string';
		$this->assertEqual($result, $expected);

		$result = $this->Dbo->column('text');
		$expected = 'text';
		$this->assertEqual($result, $expected);

		$result = $this->Dbo->column('int(11)');
		$expected = 'integer';
		$this->assertEqual($result, $expected);

		$result = $this->Dbo->column('int(11) unsigned');
		$expected = 'integer';
		$this->assertEqual($result, $expected);

		$result = $this->Dbo->column('tinyint(1)');
		$expected = 'boolean';
		$this->assertEqual($result, $expected);

		$result = $this->Dbo->column('boolean');
		$expected = 'boolean';
		$this->assertEqual($result, $expected);

		$result = $this->Dbo->column('float');
		$expected = 'float';
		$this->assertEqual($result, $expected);

		$result = $this->Dbo->column('float unsigned');
		$expected = 'float';
		$this->assertEqual($result, $expected);

		$result = $this->Dbo->column('double unsigned');
		$expected = 'float';
		$this->assertEqual($result, $expected);

		$result = $this->Dbo->column('decimal(14,7) unsigned');
		$expected = 'float';
		$this->assertEqual($result, $expected);
	}

/**
 * test transaction commands.
 *
 * @return void
 */
	public function testTransactions() {
		$this->Dbo->testing = false;
		$result = $this->Dbo->begin($this->model);
		$this->assertTrue($result);

		$log = $this->Dbo->getLog();
		$beginSqlCalls = Set::extract('/.[query=START TRANSACTION]', $log['log']);
		$this->assertEqual(1, count($beginSqlCalls));

		$result = $this->Dbo->commit($this->model);
		$this->assertTrue($result);
	}
/**
 * test that float values are correctly identified
 *
 * @return void
 */
	function testFloatParsing() {
		$model =& new Model(array('ds' => 'test', 'table' => 'datatypes', 'name' => 'Datatype'));
		$result = $this->Dbo->describe($model);
		$this->assertEqual((string)$result['float_field']['length'], '5,2');
	}

/**
 * test that tableParameters like collation, charset and engine are functioning.
 *
 * @access public
 * @return void
 */
	function testReadTableParameters() {
		$table = 'tinyint' . uniqid();
		$this->Dbo->cacheSources = $this->Dbo->testing = false;
		$this->Dbo->query('CREATE TABLE ' . $this->Dbo->fullTableName($table) . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');
		$result = $this->Dbo->readTableParameters($table);
		$expected = array(
			'charset' => 'utf8',
			'collate' => 'utf8_unicode_ci',
			'engine' => 'InnoDB');
		$this->assertEqual($result, $expected);

		$this->Dbo->query('DROP TABLE ' . $this->Dbo->fullTableName($table));
		$this->Dbo->query('CREATE TABLE ' . $this->Dbo->fullTableName($table) . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=cp1250 COLLATE=cp1250_general_ci;');
		$result = $this->Dbo->readTableParameters($table);
		$expected = array(
			'charset' => 'cp1250',
			'collate' => 'cp1250_general_ci',
			'engine' => 'MyISAM');
		$this->assertEqual($result, $expected);
		$this->Dbo->query('DROP TABLE ' . $this->Dbo->fullTableName($table));
	}
}
