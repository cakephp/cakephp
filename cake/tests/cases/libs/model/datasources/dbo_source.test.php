<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.model.datasources
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
	if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
		define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
	}
	require_once LIBS.'model'.DS.'model.php';
	require_once LIBS.'model'.DS.'datasources'.DS.'datasource.php';
	require_once LIBS.'model'.DS.'datasources'.DS.'dbo_source.php';
	require_once LIBS.'model'.DS.'datasources'.DS.'dbo'.DS.'dbo_mysql.php';
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class TestModel extends Model {

	var $name = 'TestModel';
	var $useTable = false;

	function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}

	function loadInfo() {
		return new Set(array(
			array('name' => 'id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			array('name' => 'client_id', 'type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'),
			array('name' => 'name', 'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			array('name' => 'login', 'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			array('name' => 'passwd', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			array('name' => 'addr_1', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			array('name' => 'addr_2', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'),
			array('name' => 'zip_code', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			array('name' => 'city', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			array('name' => 'country', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			array('name' => 'phone', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			array('name' => 'fax', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			array('name' => 'url', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			array('name' => 'email', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			array('name' => 'comments', 'type' => 'text', 'null' => '1', 'default' => '', 'length' => ''),
			array('name' => 'last_login', 'type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
			array('name' => 'created', 'type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			array('name' => 'updated', 'type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		));
	}
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class TestModel2 extends Model {

	var $name = 'TestModel2';
	var $useTable = false;
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class TestModel3 extends Model {

	var $name = 'TestModel3';
	var $useTable = false;
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class TestModel4 extends Model {

	var $name = 'TestModel4';
	var $table = 'test_model4';
	var $useTable = false;
	
	var $belongsTo = array(
		'TestModel4Parent' => array(
			'className' => 'TestModel4',
			'foreignKey' => 'parent_id'
		)
	);
	
	var $hasOne = array(
		'TestModel5' => array(
			'className' => 'TestModel5',
			'foreignKey' => 'test_model4_id'
		)
	);
	
	var $hasAndBelongsToMany = array('TestModel7' => array(
		'className' => 'TestModel7',
		'joinTable' => 'test_model4_test_model7',
		'foreignKey' => 'test_model4_id',
		'associationForeignKey' => 'test_model7_id'
	));
	
	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				array('name' => 'name', 'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				array('name' => 'created', 'type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				array('name' => 'updated', 'type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			));
		}
		
		return $this->_tableInfo;
	}
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class TestModel5 extends Model {

	var $name = 'TestModel5';
	var $table = 'test_model5';
	var $useTable = false;
	
	var $belongsTo = array('TestModel4' => array(
		'className' => 'TestModel4',
		'foreignKey' => 'test_model4_id'
	));
	var $hasMany = array('TestModel6' => array(
		'className' => 'TestModel6',
		'foreignKey' => 'test_model5_id'
	));
	
	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				array('name' => 'test_model4_id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				array('name' => 'name', 'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				array('name' => 'created', 'type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				array('name' => 'updated', 'type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			));
		}
		
		return $this->_tableInfo;
	}
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class TestModel6 extends Model {

	var $name = 'TestModel6';
	var $table = 'test_model6';
	var $useTable = false;
	
	var $belongsTo = array('TestModel5' => array(
		'className' => 'TestModel5',
		'foreignKey' => 'test_model5_id'
	));
	
	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				array('name' => 'test_model5_id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				array('name' => 'name', 'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				array('name' => 'created', 'type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				array('name' => 'updated', 'type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			));
		}
		
		return $this->_tableInfo;
	}
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class TestModel7 extends Model {

	var $name = 'TestModel7';
	var $table = 'test_model7';
	var $useTable = false;
	
	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				array('name' => 'name', 'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				array('name' => 'created', 'type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				array('name' => 'updated', 'type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			));
		}
		
		return $this->_tableInfo;
	}
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class DboTest extends DboMysql {

	var $simulated = array();

	function _execute($sql) {
		$this->simulated[] = $sql;
		return null;
	}

	function getLastQuery() {
		return $this->simulated[count($this->simulated) - 1];
	}
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class DboSourceTest extends UnitTestCase {

	function setUp() {
		require_once r('//', '/', APP) . 'config/database.php';
		$config = new DATABASE_CONFIG();
		$this->db =& new DboTest($config->default);
		$this->model = new TestModel();
	}
	
	function tearDown() {
		unset($this->model);
		unset($this->db);
	}
	
	function testGenerateAssociationQuerySelfJoin() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);
		
		$binding = array('type'=>'belongsTo', 'model'=>'TestModel4Parent');
		$queryData = array();
		$resultSet = null;
		$null = null;
		
		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);
		
		$result = $this->db->generateSelfAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		
		$this->assertTrue($result);
		
		$expected = 'SELECT ';
		$expected .= '`TestModel4`.`id`, `TestModel4`.`name`, `TestModel4`.`created`, `TestModel4`.`updated`, `TestModel4Parent`.`id`, `TestModel4Parent`.`name`, `TestModel4Parent`.`created`, `TestModel4Parent`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model4` AS `TestModel4`';
		$expected .= ' LEFT JOIN ';
		$expected .= '`test_model4` AS `TestModel4Parent`';
		$expected .= ' ON ';
		$expected .= '`TestModel4`.`parent_id` = `TestModel4Parent`.`id`';
		
		$this->assertEqual($queryData['selfJoin'][0], $expected);
		
		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		
		$expected = 'SELECT ';
		$expected .= '`TestModel4`.`id`, `TestModel4`.`name`, `TestModel4`.`created`, `TestModel4`.`updated`, `TestModel4Parent`.`id`, `TestModel4Parent`.`name`, `TestModel4Parent`.`created`, `TestModel4Parent`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model4` AS `TestModel4`';
		$expected .= ' LEFT JOIN ';
		$expected .= '`test_model4` AS `TestModel4Parent`';
		$expected .= ' ON ';
		$expected .= '`TestModel4`.`parent_id` = `TestModel4Parent`.`id`';
		$expected .= '  WHERE  ';
		$expected .= '1 = 1';
		$expected .= '   ';
		
		$this->assertEqual($result, $expected);
	}
		
	function testGenerateAssociationQuerySelfJoinWithConditions() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);
		
		$binding = array('type'=>'belongsTo', 'model'=>'TestModel4Parent');
		$queryData = array('conditions' => array('TestModel4Parent.name' => '!= mariano'));
		$resultSet = null;
		$null = null;
		
		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);
		
		$result = $this->db->generateSelfAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		
		$this->assertTrue($result);
		
		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		
		$expected = 'SELECT ';
		$expected .= '`TestModel4`.`id`, `TestModel4`.`name`, `TestModel4`.`created`, `TestModel4`.`updated`, `TestModel4Parent`.`id`, `TestModel4Parent`.`name`, `TestModel4Parent`.`created`, `TestModel4Parent`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model4` AS `TestModel4`';
		$expected .= ' LEFT JOIN ';
		$expected .= '`test_model4` AS `TestModel4Parent`';
		$expected .= ' ON ';
		$expected .= '`TestModel4`.`parent_id` = `TestModel4Parent`.`id`';
		$expected .= '  WHERE  ';
		$expected .= '(`TestModel4Parent`.`name` !=  \'mariano\')';
		$expected .= '  ';
		
		$this->assertEqual($result, $expected);
	}
	
	function testGenerateAssociationQueryHasOne() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);
		
		$binding = array('type'=>'hasOne', 'model'=>'TestModel5');
		
		$queryData = array();
		$resultSet = null;
		$null = null;
		
		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);
		
		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		
		$this->assertTrue($result);
		
		$expected = ' LEFT JOIN `test_model5` AS `TestModel5` ON `TestModel5`.`test_model4_id` = `TestModel4`.`id`';
		
		$this->assertEqual($queryData['joins'][0], $expected);
		
		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel4`.`id`, `TestModel4`.`name`, `TestModel4`.`created`, `TestModel4`.`updated`';
		$expected .= ', ';
		$expected .= '`TestModel5`.`id`, `TestModel5`.`test_model4_id`, `TestModel5`.`name`, `TestModel5`.`created`, `TestModel5`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model4` AS `TestModel4`';
		$expected .= '  LEFT JOIN ';
		$expected .= '`test_model5` AS `TestModel5`';
		$expected .= ' ON ';
		$expected .= '`TestModel5`.`test_model4_id` = `TestModel4`.`id`';
		$expected .= '  WHERE  ';
		$expected .= '(  1 = 1 ) AND (  1 = 1 )';
		$expected .= '  ';
		
		$this->assertEqual($result, $expected);
	}
	
	function testGenerateAssociationQueryHasOneWithConditions() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);
		
		$binding = array('type'=>'hasOne', 'model'=>'TestModel5');
		
		$queryData = array('conditions' => array('TestModel5.name' => '!= mariano'));
		$resultSet = null;
		$null = null;
		
		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);
		
		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		
		$this->assertTrue($result);
		
		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel4`.`id`, `TestModel4`.`name`, `TestModel4`.`created`, `TestModel4`.`updated`';
		$expected .= ', ';
		$expected .= '`TestModel5`.`id`, `TestModel5`.`test_model4_id`, `TestModel5`.`name`, `TestModel5`.`created`, `TestModel5`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model4` AS `TestModel4`';
		$expected .= '  LEFT JOIN ';
		$expected .= '`test_model5` AS `TestModel5`';
		$expected .= ' ON ';
		$expected .= '`TestModel5`.`test_model4_id` = `TestModel4`.`id`';
		$expected .= '  WHERE  ';
		$expected .= '(  1 = 1 )';
		$expected .= ' AND ';
		$expected .= '(`TestModel5`.`name` !=  \'mariano\')';
		$expected .= '  ';
		
		$this->assertEqual($result, $expected);
	}
	
	function testGenerateAssociationQueryBelongsTo() {
		$this->model = new TestModel5();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);
		
		$binding = array('type'=>'belongsTo', 'model'=>'TestModel4');
		$queryData = array();
		$resultSet = null;
		$null = null;
		
		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);
		
		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		
		$this->assertTrue($result);
		
		$expected = ' LEFT JOIN `test_model4` AS `TestModel4` ON `TestModel5`.`test_model4_id` = `TestModel4`.`id`';
		
		$this->assertEqual($queryData['joins'][0], $expected);
		
		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel5`.`id`, `TestModel5`.`test_model4_id`, `TestModel5`.`name`, `TestModel5`.`created`, `TestModel5`.`updated`';
		$expected .= ', ';
		$expected .= '`TestModel4`.`id`, `TestModel4`.`name`, `TestModel4`.`created`, `TestModel4`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model5` AS `TestModel5`';
		$expected .= '  LEFT JOIN ';
		$expected .= '`test_model4` AS `TestModel4`';
		$expected .= ' ON ';
		$expected .= '`TestModel5`.`test_model4_id` = `TestModel4`.`id`';
		$expected .= '  WHERE  ';
		$expected .= '(  1 = 1 ) AND (  1 = 1 )';
		$expected .= '  ';
		
		$this->assertEqual($result, $expected);
	}
	
	function testGenerateAssociationQueryBelongsToWithConditions() {
		$this->model = new TestModel5();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);
		
		$binding = array('type'=>'belongsTo', 'model'=>'TestModel4');
		$queryData = array('conditions' => array('TestModel5.name' => '!= mariano'));
		$resultSet = null;
		$null = null;
		
		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);
		
		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		
		$this->assertTrue($result);
		
		$expected = ' LEFT JOIN `test_model4` AS `TestModel4` ON `TestModel5`.`test_model4_id` = `TestModel4`.`id`';
		
		$this->assertEqual($queryData['joins'][0], $expected);
		
		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel5`.`id`, `TestModel5`.`test_model4_id`, `TestModel5`.`name`, `TestModel5`.`created`, `TestModel5`.`updated`';
		$expected .= ', ';
		$expected .= '`TestModel4`.`id`, `TestModel4`.`name`, `TestModel4`.`created`, `TestModel4`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model5` AS `TestModel5`';
		$expected .= '  LEFT JOIN ';
		$expected .= '`test_model4` AS `TestModel4`';
		$expected .= ' ON ';
		$expected .= '`TestModel5`.`test_model4_id` = `TestModel4`.`id`';
		$expected .= '  WHERE  ';
		$expected .= '(  1 = 1 ) AND (`TestModel5`.`name` !=  \'mariano\')';
		$expected .= '  ';
		
		$this->assertEqual($result, $expected);
	}
	
	function testGenerateAssociationQueryHasMany() {
		$this->model = new TestModel5();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);
		
		$binding = array('type'=>'hasMany', 'model'=>'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;
		
		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);
		
		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel6`.`id`, `TestModel6`.`test_model5_id`, `TestModel6`.`name`, `TestModel6`.`created`, `TestModel6`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model6` AS `TestModel6`';
		$expected .= ' WHERE  ';
		$expected .= '1 = 1';
		$expected .= '  AND ';
		$expected .= '`TestModel6`.`test_model5_id` = {$__cakeID__$}';
		
		$this->assertEqual($result, $expected);
		
		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel5`.`id`, `TestModel5`.`test_model4_id`, `TestModel5`.`name`, `TestModel5`.`created`, `TestModel5`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model5` AS `TestModel5`';
		$expected .= '   WHERE  ';
		$expected .= '1 = 1';
		$expected .= '   ';
		
		$this->assertEqual($result, $expected);
	}
	
	function testGenerateAssociationQueryHasManyWithConditions() {
		$this->model = new TestModel5();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);
		
		$binding = array('type'=>'hasMany', 'model'=>'TestModel6');
		$queryData = array('conditions' => array('TestModel5.name' => '!= mariano'));
		$resultSet = null;
		$null = null;
		
		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);
		
		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel6`.`id`, `TestModel6`.`test_model5_id`, `TestModel6`.`name`, `TestModel6`.`created`, `TestModel6`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model6` AS `TestModel6`';
		$expected .= ' WHERE  ';
		$expected .= '1 = 1';
		$expected .= '  AND ';
		$expected .= '`TestModel6`.`test_model5_id` = {$__cakeID__$}';
		
		$this->assertEqual($result, $expected);
		
		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel5`.`id`, `TestModel5`.`test_model4_id`, `TestModel5`.`name`, `TestModel5`.`created`, `TestModel5`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model5` AS `TestModel5`';
		$expected .= '   WHERE  ';
		$expected .= '(`TestModel5`.`name` !=  \'mariano\')';
		$expected .= '  ';
		
		$this->assertEqual($result, $expected);
	}
	
	function testGenerateAssociationQueryHasAndBelongsToMany() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);
		
		$binding = array('type'=>'hasAndBelongsToMany', 'model'=>'TestModel7');
		$queryData = array();
		$resultSet = null;
		$null = null;
		
		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);
		
		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel7`.`id`, `TestModel7`.`name`, `TestModel7`.`created`, `TestModel7`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model7` AS `TestModel7`';
		$expected .= ' JOIN ';
		$expected .= '`test_model4_test_model7`';
		$expected .= ' ON ';
		$expected .= '`test_model4_test_model7`.`test_model4_id` = {$__cakeID__$}';
		$expected .= ' AND ';
		$expected .= '`test_model4_test_model7`.`test_model7_id` = `TestModel7`.`id`';
		$expected .= ' WHERE  ';
		$expected .= '1 = 1 ';
		
		$this->assertEqual($result, $expected);
		
		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel4`.`id`, `TestModel4`.`name`, `TestModel4`.`created`, `TestModel4`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model4` AS `TestModel4`';
		$expected .= '   WHERE  ';
		$expected .= '1 = 1';
		$expected .= '   ';
		
		$this->assertEqual($result, $expected);
	}
	
	function testGenerateAssociationQueryHasAndBelongsToManyWithConditions() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);
		
		$binding = array('type'=>'hasAndBelongsToMany', 'model'=>'TestModel7');
		$queryData = array('conditions' => array('TestModel4.name' => '!= mariano'));
		$resultSet = null;
		$null = null;
		
		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);
		
		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel7`.`id`, `TestModel7`.`name`, `TestModel7`.`created`, `TestModel7`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model7` AS `TestModel7`';
		$expected .= ' JOIN ';
		$expected .= '`test_model4_test_model7`';
		$expected .= ' ON ';
		$expected .= '`test_model4_test_model7`.`test_model4_id` = {$__cakeID__$}';
		$expected .= ' AND ';
		$expected .= '`test_model4_test_model7`.`test_model7_id` = `TestModel7`.`id`';
		$expected .= ' WHERE  ';
		$expected .= '1 = 1 ';
		
		$this->assertEqual($result, $expected);
		
		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		
		$expected = 'SELECT  ';
		$expected .= '`TestModel4`.`id`, `TestModel4`.`name`, `TestModel4`.`created`, `TestModel4`.`updated`';
		$expected .= ' FROM ';
		$expected .= '`test_model4` AS `TestModel4`';
		$expected .= '   WHERE  ';
		$expected .= '(`TestModel4`.`name` !=  \'mariano\')';
		$expected .= '  ';
		
		$this->assertEqual($result, $expected);
	}
	
	function _buildRelatedModels(&$model) {
		foreach($model->__associations as $type) {
			foreach($model->{$type} as $assoc => $assocData) {
				if (is_string($assocData)) {
					$className = $assocData;
				} else if (isset($assocData['className'])) {
					$className = $assocData['className'];
				}
				$model->$className = new $className();
				$model->$className->loadInfo();
			}
		}
	}
	
	function &_prepareAssociationQuery(&$model, &$queryData, $binding) {
		$type = $binding['type'];
		$assoc = $binding['model'];
		$assocData = $model->{$type}[$assoc];
		$className = $assocData['className'];
		
		$linkModel =& $model->{$className};
		$external = isset($assocData['external']);
		
		$this->db->__scrubQueryData($queryData);
		
		$result = array(
			'linkModel'=> &$linkModel,
			'type'=> $type,
			'assoc'=> $assoc,
			'assocData'=> $assocData,
			'external'=> $external
		);
		
		return $result;
	}

	function testStringConditionsParsing() {
		$result = $this->db->conditions("Candy.name LIKE 'a' AND HardCandy.name LIKE 'c'");
		$expected = " WHERE  `Candy`.`name` LIKE 'a' AND `HardCandy`.`name` LIKE 'c'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("HardCandy.name LIKE 'a' AND Candy.name LIKE 'c'");
		$expected = " WHERE  `HardCandy`.`name` LIKE 'a' AND `Candy`.`name` LIKE 'c'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("Post.title = '1.1'");
		$expected = " WHERE  `Post`.`title` = '1.1'";
		$this->assertEqual($result, $expected);

		/*
		$result = $this->db->conditions("SUM(Post.comments_count) > 500");
		$expected = " WHERE SUM( `Post`.`comments_count`) > 500";
		$this->assertEqual($result, $expected);
		*/

		/*
		$result = $this->db->conditions("(Post.created < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(Post.created), MONTH(Post.created)");
		$expected = " WHERE ( `Post`.`created` < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR( `Post`.`created`), MONTH( `Post`.`created`)";
		$this->assertEqual($result, $expected);
		*/

		$result = $this->db->conditions("score BETWEEN 90.1 AND 95.7");
		$expected = " WHERE score BETWEEN 90.1 AND 95.7";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("Aro.rght = Aro.lft + 1.1");
		$expected = " WHERE  `Aro`.`rght` = `Aro`.`lft` + 1.1";
		$this->assertEqual($result, $expected);

		/*
		$result = $this->db->conditions("(Post.created < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(Post.created), MONTH(Post.created)");
		$expected = " WHERE ( `Post`.`created` < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR( `Post`.`created`), MONTH( `Post`.`created`)";
		$this->assertEqual($result, $expected);
		*/

		$result = $this->db->conditions('Sportstaette.sportstaette LIKE "%ru%" AND Sportstaette.sportstaettenart_id = 2');
		$expected = ' WHERE  `Sportstaette`.`sportstaette` LIKE "%ru%" AND `Sportstaette`.`sportstaettenart_id` = 2';
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('Sportstaette.sportstaettenart_id = 2 AND Sportstaette.sportstaette LIKE "%ru%"');
		$expected = ' WHERE  `Sportstaette`.`sportstaettenart_id` = 2 AND `Sportstaette`.`sportstaette` LIKE "%ru%"';
		$this->assertEqual($result, $expected);

		/*
		$result = $this->db->conditions('SUM(Post.comments_count) > 500 AND NOT Post.title IS NULL AND NOT Post.extended_title IS NULL');
		$expected = ' WHERE SUM( `Post`.`comments_count`) > 500 AND NOT `Post`.`title` IS NULL AND NOT `Post`.`extended_title` IS NULL';
		$this->assertEqual($result, $expected);
		*/

		/*
		$result = $this->db->conditions('NOT Post.title IS NULL AND NOT Post.extended_title IS NULL AND SUM(Post.comments_count) > 500');
		$expected = ' WHERE NOT `Post`.`title` IS NULL AND NOT `Post`.`extended_title` IS NULL AND SUM( `Post`.`comments_count`) > 500';
		$this->assertEqual($result, $expected);
		*/

		/*
		$result = $this->db->conditions('NOT Post.extended_title IS NULL AND NOT Post.title IS NULL AND Post.title != "" AND SPOON(SUM(Post.comments_count) + 1.1) > 500');
		$expected = ' WHERE NOT `Post`.`extended_title` IS NULL AND NOT `Post`.`title` IS NULL AND `Post`.`title` != "" AND SPOON(SUM( `Post`.`comments_count`) + 1.1) > 500';
		$this->assertEqual($result, $expected);
		*/

		$result = $this->db->conditions('NOT Post.title_extended IS NULL AND NOT Post.title IS NULL AND Post.title_extended != Post.title');
		$expected = ' WHERE NOT `Post`.`title_extended` IS NULL AND NOT `Post`.`title` IS NULL AND `Post`.`title_extended` != `Post`.`title`';
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("Comment.id = 'a'");
		$expected = " WHERE  `Comment`.`id` = 'a'";
		$this->assertEqual($result, $expected);
	}

	function testArrayConditionsParsing() {
		$result = $this->db->conditions(array('Candy.name' => 'LIKE a', 'HardCandy.name' => 'LIKE c'));
		$expected = " WHERE  (`Candy`.`name` LIKE  'a') AND (`HardCandy`.`name` LIKE  'c')";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('HardCandy.name' => 'LIKE a', 'Candy.name' => 'LIKE c'));
		$expected = " WHERE  (`HardCandy`.`name` LIKE  'a') AND (`Candy`.`name` LIKE  'c')";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('score' => 'BETWEEN 90.1 AND 95.7'));
		$expected = " WHERE  (`score` BETWEEN  '90.1' AND '95.7')";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('Post.title' => 1.1));
		$expected = " WHERE  (`Post`.`title`  =  1.1)";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('SUM(Post.comments_count)' => '> 500'));
		$expected = " WHERE  (SUM(`Post`.`comments_count`) >  500)";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('title' => 'LIKE %hello'));
		$expected = " WHERE  (`title` LIKE  '%hello')";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('Post.name' => 'mad(g)ik'));
		$expected = " WHERE  (`Post`.`name` mad(g) 'ik')";
		$this->assertEqual($result, $expected);
	}

	function testFieldParsing() {
		/*
		$result = $this->db->fields($this->model, 'Post', "CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name, Node.created");
		$expected = array("CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name", "`Node`.`created`");
		$this->assertEqual($result, $expected);
		*/

		/*
		$result = $this->db->fields($this->model, 'Post', "Node.created, CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name");
		$expected = array("`Node`.`created`", "CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name");
		$this->assertEqual($result, $expected);
		*/

		/*
		$result = $this->db->fields($this->model, 'Post', "2.2,COUNT(*), SUM(Something.else) as sum, Node.created, CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name,Post.title,Post.1,1.1");
		$expected = array(
			'2.2', 'COUNT(*)', 'SUM(`Something`.`else`) as sum', '`Node`.`created`',
			"CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name", '`Post`.`title`', '`Post`.`1`', '1.1'
		);
		$this->assertEqual($result, $expected);
		*/
	}

	function testMagicMethodQuerying() {
		$result = $this->db->query('findByFieldName', array('value'), $this->model);
		$expected = array('TestModel.field_name' => '= value');
		$this->assertEqual($result, $expected);

		/*
		$result = $this->db->query('findAllById', array('a'), $this->model);
		$expected = array('TestModel.id' => '= value');
		$this->assertEqual($result, $expected);
		*/

		$result = $this->db->query('findByFieldName', array(array('value1', 'value2', 'value3')), $this->model);
		$expected = array('TestModel.field_name' => array('value1', 'value2', 'value3'));
		$this->assertEqual($result, $expected);

		$result = $this->db->query('findByFieldName', array(null), $this->model);
		$expected = array('TestModel.field_name' => null);
		$this->assertEqual($result, $expected);

		$result = $this->db->query('findByFieldName', array('= a'), $this->model);
		$expected = array('TestModel.field_name' => '= = a');
		$this->assertEqual($result, $expected);
	}

	function testOrderParsing() {
		$result = $this->db->order("ADDTIME(Event.time_begin, '-06:00:00') ASC");
		$expected = " ORDER BY ADDTIME(`Event`.`time_begin`, '-06:00:00') ASC";
		$this->assertEqual($result, $expected);
	}

	function testSomething() {
		$this->model->Test2 = new TestModel2();
		$this->model->hasAndBelongsToMany = array('Test2' => array(
		//	'with'					=> 'Testship',
			'className'				=> 'TestModel2',
			'joinTable'				=> 'tests',
			'foreignKey'			=> 'contact_id',
			'associationForeignKey'	=> 'project_id',
			'conditions'			=> null,
			'fields'				=> null,
			'order'					=> null,
			'limit'					=> null,
			'offset'				=> null,
			'unique'				=> null,
			'finderQuery'			=> null,
			'deleteQuery'			=> null,
			'insertQuery'			=> null
		));

		//generateAssociationQuery($this->model, $this->model->Test2, $type, $association = null, $assocData = array(), &$queryData, $external = false, &$resultSet)
	}
}

?>