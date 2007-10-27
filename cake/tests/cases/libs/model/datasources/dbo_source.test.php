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

uses('model'.DS.'model', 'model'.DS.'datasources'.DS.'datasource',
	'model'.DS.'datasources'.DS.'dbo_source', 'model'.DS.'datasources'.DS.'dbo'.DS.'dbo_mysql');
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class TestModel extends CakeTestModel {

	var $name = 'TestModel';
	var $useTable = false;

	function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}

	function findAll($conditions = null, $fields = null, $order = null, $recursive = null) {
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
class TestModel2 extends CakeTestModel {

	var $name = 'TestModel2';
	var $useTable = false;
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class TestModel3 extends CakeTestModel {

	var $name = 'TestModel3';
	var $useTable = false;
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class TestModel4 extends CakeTestModel {

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
class TestModel5 extends CakeTestModel {

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
class TestModel6 extends CakeTestModel {

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
class TestModel7 extends CakeTestModel {

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
class TestModel8 extends CakeTestModel {

	var $name = 'TestModel8';
	var $table = 'test_model8';
	var $useTable = false;

	var $hasOne = array(
		'TestModel9' => array(
			'className' => 'TestModel9',
			'foreignKey' => 'test_model9_id',
			'conditions' => 'TestModel9.name != \'mariano\''
		)
	);

	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				array('name' => 'test_model9_id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
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
class TestModel9 extends CakeTestModel {

	var $name = 'TestModel9';
	var $table = 'test_model9';
	var $useTable = false;

	var $belongsTo = array('TestModel8' => array(
		'className' => 'TestModel8',
		'foreignKey' => 'test_model8_id',
		'conditions' => 'TestModel8.name != \'larry\''
	));

	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				array('name' => 'test_model8_id', 'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				array('name' => 'name', 'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				array('name' => 'created', 'type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				array('name' => 'updated', 'type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			));
		}

		return $this->_tableInfo;
	}
}

class Level extends CakeTestModel {

	var $name = 'Level';
	var $table = 'level';
	var $useTable = false;

	var $hasMany = array(
		'Group'=> array(
			'className' => 'Group'
		),
		'User2' => array(
			'className' => 'User2'
		)
	);

	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				array('name' => 'name', 'type' => 'string', 'null' => true, 'default' => null, 'length' => '20')
			));
		}
		return $this->_tableInfo;
	}
}

class Group extends CakeTestModel {

	var $name = 'Group';
	var $table = 'group';
	var $useTable = false;

	var $belongsTo = array('Level');

	var $hasMany = array('Category2', 'User2');

	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				array('name' => 'level_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'name', 'type' => 'string', 'null' => true, 'default' => null, 'length' => '20')
			));
		}
		return $this->_tableInfo;
	}
}

class User2 extends CakeTestModel {

	var $name = 'User2';
	var $table = 'user';
	var $useTable = false;

	var $belongsTo = array(
		'Group' => array(
			'className' => 'Group'
		),
		'Level' => array(
			'className' => 'Level'
		)
	);

	var $hasMany = array(
		'Article2' => array(
			'className' => 'Article2'
		),
	);

	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				array('name' => 'group_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'level_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'name', 'type' => 'string', 'null' => true, 'default' => null, 'length' => '20')
			));
		}
		return $this->_tableInfo;
	}
}

class Category2 extends CakeTestModel {
	var $name = 'Category2';
	var $table = 'category';
	var $useTable = false;

  var $belongsTo = array(
		'Group' => array(
			'className' => 'Group',
			'foreignKey' => 'group_id'
		),
		'ParentCat' => array(
			'className' => 'Category2',
			'foreignKey' => 'parent_id'
		)
	);

	var $hasMany = array(
		'ChildCat' => array(
			'className' => 'Category2',
			'foreignKey' => 'parent_id'
		),
		'Article2' => array(
			'className' => 'Article2',
			'order'=>'Article2.published_date DESC',
			'foreignKey' => 'category_id',
			'limit'=>'3')
	);

	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				array('name' => 'group_id', 'type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				array('name' => 'parent_id', 'type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				array('name' => 'name', 'type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				array('name' => 'icon', 'type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				array('name' => 'description', 'text' => 'string', 'null' => false, 'default' => '', 'length' => null)
			));
		}
		return $this->_tableInfo;
	}
}

class Article2 extends CakeTestModel {
	var $name = 'Article2';
	var $table = 'article';
	var $useTable = false;

	var $belongsTo = array(
		'Category2' => array(
  			'className' => 'Category2'
		),
		'User2' => array(
			'className' => 'User2'
 		)
 	);

	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				array('name' => 'category_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'user_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'rate_count', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'rate_sum', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'viewed', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'version', 'type' => 'string', 'null' => true, 'default' => '', 'length' => '45'),
				array('name' => 'title', 'type' => 'string', 'null' => false, 'default' => '', 'length' => '200'),
				array('name' => 'intro', 'text' => 'string', 'null' => true, 'default' => '', 'length' => null),
				array('name' => 'comments', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '4'),
				array('name' => 'body', 'text' => 'string', 'null' => true, 'default' => '', 'length' => null),
				array('name' => 'isdraft', 'type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				array('name' => 'allow_comments', 'type' => 'boolean', 'null' => false, 'default' => '1', 'length' => '1'),
				array('name' => 'moderate_comments', 'type' => 'boolean', 'null' => false, 'default' => '1', 'length' => '1'),
				array('name' => 'published', 'type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				array('name' => 'multipage', 'type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				array('name' => 'published_date', 'type' => 'datetime', 'null' => true, 'default' => '', 'length' => null),
				array('name' => 'created', 'type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null),
				array('name' => 'modified', 'type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null)
			));
		}
		return $this->_tableInfo;
	}
}

class CategoryFeatured2 extends CakeTestModel {
	var $name = 'CategoryFeatured2';
	var $table = 'category_featured';
	var $useTable = false;

	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				array('name' => 'parent_id', 'type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				array('name' => 'name', 'type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				array('name' => 'icon', 'type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				array('name' => 'description', 'text' => 'string', 'null' => false, 'default' => '', 'length' => null)
			));
		}
		return $this->_tableInfo;
	}
}

class Featured2 extends CakeTestModel {

	var $name = 'Featured2';
	var $table = 'featured2';
	var $useTable = false;

	var $belongsTo = array(
		'CategoryFeatured2' => array(
			'className' => 'CategoryFeatured2'
		)
	);

	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				array('name' => 'article_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'category_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'name', 'type' => 'string', 'null' => true, 'default' => null, 'length' => '20')
			));
		}
		return $this->_tableInfo;
	}
}

class Comment2 extends CakeTestModel {
	var $name = 'Comment2';
	var $table = 'comment';
	var $belongsTo = array('ArticleFeatured2', 'User2');
	var $useTable = false;

	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				array('name' => 'article_featured_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'user_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'title', 'type' => 'string', 'null' => true, 'default' => null, 'length' => '20')
			));
		}
		return $this->_tableInfo;
	}
}

class ArticleFeatured2 extends CakeTestModel {
	var $name = 'ArticleFeatured2';
	var $table = 'article_featured';
	var $useTable = false;

	var $belongsTo = array(
		'CategoryFeatured2' => array(
  			'className' => 'CategoryFeatured2'
		),
		'User2' => array(
			'className' => 'User2'
 		)
 	);
 	var $hasOne = array(
 		'Featured2' => array('className' => 'Featured2')
 	);
	var $hasMany = array(
		'Comment2' => array('className'=>'Comment2', 'dependent' => true)
	);

	function loadInfo() {
		if (!isset($this->_tableInfo)) {
			$this->_tableInfo = new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				array('name' => 'category_featured_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'user_id', 'type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				array('name' => 'title', 'type' => 'string', 'null' => false, 'default' => '', 'length' => '200'),
				array('name' => 'body', 'text' => 'string', 'null' => true, 'default' => '', 'length' => null),
				array('name' => 'published', 'type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				array('name' => 'published_date', 'type' => 'datetime', 'null' => true, 'default' => '', 'length' => null),
				array('name' => 'created', 'type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null),
				array('name' => 'modified', 'type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null)
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
		config('database');
		$config = new DATABASE_CONFIG();
		if (isset($config->test)) {
			$config = $config->test;
		} else {
			$config = $config->default;
		}

		$this->db =& new DboTest($config);
		$this->db->fullDebug = false;
		$this->model = new TestModel();
		$db =& ConnectionManager::getDataSource($this->model->useDbConfig);
		$db->fullDebug = false;
	}

	function tearDown() {
		unset($this->model);
		unset($this->db);
	}

	function testGenerateAssociationQuerySelfJoin() {
		$this->model = new Article2();
		$this->_buildRelatedModels($this->model);
		$this->_buildRelatedModels($this->model->Category2);
		$this->model->Category2->ChildCat = new Category2();
		$this->model->Category2->ParentCat = new Category2();

		$queryData = array();

		foreach ($this->model->Category2->__associations as $type) {
			foreach ($this->model->Category2->{$type} as $assoc => $assocData) {
				$linkModel =& $this->model->Category2->{$assoc};
				$external = isset($assocData['external']);

				if ($this->model->Category2->alias == $linkModel->alias && $type != 'hasAndBelongsToMany' && $type != 'hasMany') {
					$result = $this->db->generateSelfAssociationQuery($this->model->Category2, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null);
					$this->assertTrue($result);
				} else {
					if ($this->model->Category2->useDbConfig == $linkModel->useDbConfig) {
						$result = $this->db->generateAssociationQuery($this->model->Category2, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null);
						$this->assertTrue($result);
					}
				}
			}
		}

		$query = $this->db->generateAssociationQuery($this->model->Category2, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+(.+)FROM(.+)`Category2`\.`group_id`\s+=\s+`Group`\.`id`\)\s+WHERE/', $query);

		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel4Parent');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateSelfAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$expected = array (array (
			'fields' => array (
				'`TestModel4`.`id`',
				'`TestModel4`.`name`',
				'`TestModel4`.`created`',
				'`TestModel4`.`updated`',
				'`TestModel4Parent`.`id`',
				'`TestModel4Parent`.`name`',
				'`TestModel4Parent`.`created`',
				'`TestModel4Parent`.`updated`'
			),
			'joins' => array (
				array (
					'table' => '`test_model4`',
					'alias' => 'TestModel4Parent',
					'type' => 'LEFT',
					'conditions' => array (
						'`TestModel4`.`parent_id`' => '{$__cakeIdentifier[TestModel4Parent.id]__$}'
					)
				)
			),
			'table' => '`test_model4`',
			'alias' => 'TestModel4',
			'limit' => array ( ),
			'offset' => array ( ),
			'conditions' => array ( ),
			'order' => array ( )
		));

		$this->assertEqual($queryData['selfJoin'], $expected);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel4Parent`\.`id`, `TestModel4Parent`\.`name`, `TestModel4Parent`\.`created`, `TestModel4Parent`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+`test_model4` AS `TestModel4Parent`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4`.`parent_id` = `TestModel4Parent`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+1 = 1\s+$/', $result);
	}

	function testGenerateAssociationQuerySelfJoinWithConditionsInHasOneBinding() {
		$this->model = new TestModel8();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$binding = array('type' => 'hasOne', 'model' => 'TestModel9');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateSelfAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);

		$this->assertTrue($result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);

		$this->assertPattern('/^SELECT\s+`TestModel8`\.`id`, `TestModel8`\.`test_model9_id`, `TestModel8`\.`name`, `TestModel8`\.`created`, `TestModel8`\.`updated`, `TestModel9`\.`id`, `TestModel9`\.`test_model8_id`, `TestModel9`\.`name`, `TestModel9`\.`created`, `TestModel9`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model8` AS `TestModel8`\s+LEFT JOIN\s+`test_model9` AS `TestModel9`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel8`.`test_model9_id` = `TestModel9`.`id`\s+AND\s+`TestModel9`\.`name` != \'mariano\'\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);
	}

	function testGenerateAssociationQuerySelfJoinWithConditionsInBelongsToBinding() {
		$this->model = new TestModel9();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel8');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateSelfAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);

		$this->assertTrue($result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);

		$this->assertPattern('/^SELECT\s+`TestModel9`\.`id`, `TestModel9`\.`test_model8_id`, `TestModel9`\.`name`, `TestModel9`\.`created`, `TestModel9`\.`updated`, `TestModel8`\.`id`, `TestModel8`\.`test_model9_id`, `TestModel8`\.`name`, `TestModel8`\.`created`, `TestModel8`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model9` AS `TestModel9`\s+LEFT JOIN\s+`test_model8` AS `TestModel8`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel9`.`test_model8_id` = `TestModel8`.`id`\s+AND\s+`TestModel8`\.`name` != \'larry\'\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);
	}

	function testGenerateAssociationQuerySelfJoinWithConditions() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel4Parent');
		$queryData = array('conditions' => array('TestModel4Parent.name' => '!= mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateSelfAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);

		$this->assertTrue($result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel4Parent`\.`id`, `TestModel4Parent`\.`name`, `TestModel4Parent`\.`created`, `TestModel4Parent`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+`test_model4` AS `TestModel4Parent`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4`.`parent_id` = `TestModel4Parent`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?`TestModel4Parent`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);

		$this->Featured2 = new Featured2();
		$this->Featured2->loadInfo();

		$this->Featured2->bindModel(array(
			'belongsTo' => array(
				'ArticleFeatured2' => array(
					'conditions' => 'ArticleFeatured2.published = \'Y\'',
					'fields' => 'id, title, user_id, published'
				)
			)
		));

		$this->_buildRelatedModels($this->Featured2);

		$binding = array('type' => 'belongsTo', 'model' => 'ArticleFeatured2');
		$queryData = array('conditions' => array());
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Featured2, $queryData, $binding);

		$result = $this->db->generateSelfAssociationQuery($this->Featured2, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);

		$this->assertTrue($result);

		$result = $this->db->generateAssociationQuery($this->Featured2, $null, null, null, null, $queryData, false, $null);

		$this->assertPattern(
			'/^SELECT\s+`Featured2`\.`id`, `Featured2`\.`article_id`, `Featured2`\.`category_id`, `Featured2`\.`name`,\s+'.
			'`ArticleFeatured2`\.`id`, `ArticleFeatured2`\.`title`, `ArticleFeatured2`\.`user_id`, `ArticleFeatured2`\.`published`\s+' .
			'FROM\s+`featured2` AS `Featured2`\s+LEFT JOIN\s+`article_featured` AS `ArticleFeatured2`' .
			'\s+ON\s+\(`Featured2`\.`article_featured2_id` = `ArticleFeatured2`\.`id`\s+AND\s+`ArticleFeatured2`.`published` = \'Y\'\)' .
			'\s+WHERE\s+1\s+=\s+1\s*$/',
			$result);
	}

	function testGenerateAssociationQueryHasOne() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$binding = array('type' => 'hasOne', 'model' => 'TestModel5');

		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->db->buildJoinStatement($queryData['joins'][0]);
		$expected = ' LEFT JOIN `test_model5` AS `TestModel5` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
		$this->assertEqual(trim($result), trim($expected));

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+/', $result);
		$this->assertPattern('/`test_model5` AS `TestModel5`\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
	}

	function testGenerateAssociationQueryHasOneWithConditions() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$binding = array('type' => 'hasOne', 'model' => 'TestModel5');

		$queryData = array('conditions' => array('TestModel5.name' => '!= mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);

		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+`test_model5` AS `TestModel5`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel5`.`test_model4_id`\s+=\s+`TestModel4`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*`TestModel5`.`name`\s+!=\s+\'mariano\'\s*(?:\))?\s*$/', $result);
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

		$result = $this->db->buildJoinStatement($queryData['joins'][0]);
		$expected = ' LEFT JOIN `test_model4` AS `TestModel4` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
		$this->assertEqual(trim($result), trim($expected));

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`, `TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+LEFT JOIN\s+`test_model4` AS `TestModel4`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
	}

	function testGenerateAssociationQueryBelongsToWithConditions() {
		$this->model = new TestModel5();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel4');
		$queryData = array('conditions' => array('TestModel5.name' => '!= mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->db->buildJoinStatement($queryData['joins'][0]);
		$expected = ' LEFT JOIN `test_model4` AS `TestModel4` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
		$this->assertEqual(trim($result), trim($expected));

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`, `TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+LEFT JOIN\s+`test_model4` AS `TestModel4`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+`TestModel5`.`name` !=  \'mariano\'\s*$/', $result);
	}

	function testGenerateAssociationQueryHasMany() {
		$this->model = new TestModel5();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+`TestModel6`.`test_model5_id`\s+IN\s+\({\$__cakeID__\$}\)/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
	}

	function testGenerateAssociationQueryHasManyWithLimit() {
		$this->model = new TestModel5();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$this->model->hasMany['TestModel6']['limit'] = 2;

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+' .
												 '`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+'.
												 'FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+' .
												 '`TestModel6`.`test_model5_id`\s+IN\s+\({\$__cakeID__\$}\)\s*'.
												 'LIMIT \d*'.
												 '\s*$/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+'.
												 '`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+'.
												 'FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+'.
												 '(?:\()?\s*1 = 1\s*(?:\))?'.
												 '\s*$/', $result);
	}

	function testGenerateAssociationQueryHasManyWithConditions() {
		$this->model = new TestModel5();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('conditions' => array('TestModel5.name' => '!= mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+IN\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?`TestModel5`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);
	}

	function testGenerateAssociationQueryHasManyWithOffsetAndLimit() {
		$this->model = new TestModel5();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$__backup = $this->model->hasMany['TestModel6'];

		$this->model->hasMany['TestModel6']['offset'] = 2;
		$this->model->hasMany['TestModel6']['limit'] = 5;

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+IN\s+\({\$__cakeID__\$}\)(?:\))?/', $result);
		$this->assertPattern('/\s+LIMIT 2,\s*5\s*$/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->model->hasMany['TestModel6'] = $__backup;
	}

	function testGenerateAssociationQueryHasManyWithPageAndLimit() {
		$this->model = new TestModel5();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$__backup = $this->model->hasMany['TestModel6'];

		$this->model->hasMany['TestModel6']['page'] = 2;
		$this->model->hasMany['TestModel6']['limit'] = 5;

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+IN\s+\({\$__cakeID__\$}\)(?:\))?/', $result);
		$this->assertPattern('/\s+LIMIT 5,\s*5\s*$/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->model->hasMany['TestModel6'] = $__backup;
	}

	function testGenerateAssociationQueryHasManyWithFields() {
		$this->model = new TestModel5();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+IN\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`name`, `TestModel5`\.`id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`, `TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+IN\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`name`', '`TestModel5`.`created`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+IN\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->model->hasMany['TestModel6']['fields'] = array('name');

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`', '`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`name`, `TestModel6`\.`test_model5_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+IN\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		unset($this->model->hasMany['TestModel6']['fields']);

		$this->model->hasMany['TestModel6']['fields'] = array('id', 'name');

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`', '`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`name`, `TestModel6`\.`test_model5_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+IN\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		unset($this->model->hasMany['TestModel6']['fields']);

		$this->model->hasMany['TestModel6']['fields'] = array('test_model5_id', 'name');

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`', '`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`test_model5_id`, `TestModel6`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+IN\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		unset($this->model->hasMany['TestModel6']['fields']);
	}

	function testGenerateAssociationQueryHasAndBelongsToMany() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$binding = array('type' => 'hasAndBelongsToMany', 'model' => 'TestModel7');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7` AS `TestModel7`\s+JOIN\s+`test_model4_test_model7`/', $result);
		$this->assertPattern('/\s+ON\s+(?:\()?`test_model4_test_model7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}(?:\))?/', $result);
		$this->assertPattern('/\s+AND\s+(?:\()?`test_model4_test_model7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`(?:\))?/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?1 = 1(?:\))?\s*$/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1 = 1(?:\))?\s*$/', $result);
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
		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7` AS `TestModel7`\s+JOIN\s+`test_model4_test_model7`+/', $result);
		$this->assertPattern('/\s+ON\s+(?:\()?`test_model4_test_model7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}(?:\))?/', $result);
		$this->assertPattern('/\s+AND\s+(?:\()?`test_model4_test_model7`\.`test_model7_id`\s+=\s+`TestModel7`.`id`(?:\))?\s+WHERE\s+/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?`TestModel4`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);
	}

	function testGenerateAssociationQueryHasAndBelongsToManyWithOffsetAndLimit() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$__backup = $this->model->hasAndBelongsToMany['TestModel7'];

		$this->model->hasAndBelongsToMany['TestModel7']['offset'] = 2;
		$this->model->hasAndBelongsToMany['TestModel7']['limit'] = 5;

		$binding = array('type'=>'hasAndBelongsToMany', 'model'=>'TestModel7');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);

		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7` AS `TestModel7`\s+JOIN\s+`test_model4_test_model7`+/', $result);
		$this->assertPattern('/\s+ON\s+(?:\()?`test_model4_test_model7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}(?:\))?/', $result);
		$this->assertPattern('/\s+AND\s+(?:\()?`test_model4_test_model7`\.`test_model7_id`\s+=\s+`TestModel7`.`id`(?:\))?\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+(?:\()?1\s+=\s+1(?:\))?\s*\s+LIMIT 2,\s*5\s*$/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->model->hasAndBelongsToMany['TestModel7'] = $__backup;
	}

	function testGenerateAssociationQueryHasAndBelongsToManyWithPageAndLimit() {
		$this->model = new TestModel4();
		$this->model->loadInfo();
		$this->_buildRelatedModels($this->model);

		$__backup = $this->model->hasAndBelongsToMany['TestModel7'];

		$this->model->hasAndBelongsToMany['TestModel7']['page'] = 2;
		$this->model->hasAndBelongsToMany['TestModel7']['limit'] = 5;

		$binding = array('type'=>'hasAndBelongsToMany', 'model'=>'TestModel7');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->model, $queryData, $binding);

		$result = $this->db->generateAssociationQuery($this->model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);

		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7` AS `TestModel7`\s+JOIN\s+`test_model4_test_model7`+/', $result);
		$this->assertPattern('/\s+ON\s+(?:\()?`test_model4_test_model7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}(?:\))?/', $result);
		$this->assertPattern('/\s+AND\s+(?:\()?`test_model4_test_model7`\.`test_model7_id`\s+=\s+`TestModel7`.`id`(?:\))?\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+(?:\()?1\s+=\s+1(?:\))?\s*\s+LIMIT 5,\s*5\s*$/', $result);

		$result = $this->db->generateAssociationQuery($this->model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->model->hasAndBelongsToMany['TestModel7'] = $__backup;
	}

	function _buildRelatedModels(&$model) {
		foreach ($model->__associations as $type) {
			foreach ($model->{$type} as $assoc => $assocData) {
				if (is_string($assocData)) {
					$className = $assocData;
				} elseif (isset($assocData['className'])) {
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
		$result = $this->db->conditions("ProjectBid.project_id = Project.id");
		$expected = " WHERE `ProjectBid`.`project_id` = `Project`.`id`";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("Candy.name LIKE 'a' AND HardCandy.name LIKE 'c'");
		$expected = " WHERE `Candy`.`name` LIKE 'a' AND `HardCandy`.`name` LIKE 'c'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("HardCandy.name LIKE 'a' AND Candy.name LIKE 'c'");
		$expected = " WHERE `HardCandy`.`name` LIKE 'a' AND `Candy`.`name` LIKE 'c'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("Post.title = '1.1'");
		$expected = " WHERE `Post`.`title` = '1.1'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("User.id != 0 AND User.user LIKE '%arr%'");
		$expected = " WHERE `User`.`id` != 0 AND `User`.`user` LIKE '%arr%'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("SUM(Post.comments_count) > 500");
		$expected = " WHERE SUM(`Post`.`comments_count`) > 500";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("(Post.created < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(Post.created), MONTH(Post.created)");
		$expected = " WHERE (`Post`.`created` < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(`Post`.`created`), MONTH(`Post`.`created`)";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("score BETWEEN 90.1 AND 95.7");
		$expected = " WHERE score BETWEEN 90.1 AND 95.7";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("Aro.rght = Aro.lft + 1.1");
		$expected = " WHERE `Aro`.`rght` = `Aro`.`lft` + 1.1";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("(Post.created < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(Post.created), MONTH(Post.created)");
		$expected = " WHERE (`Post`.`created` < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(`Post`.`created`), MONTH(`Post`.`created`)";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('Sportstaette.sportstaette LIKE "%ru%" AND Sportstaette.sportstaettenart_id = 2');
		$expected = ' WHERE `Sportstaette`.`sportstaette` LIKE "%ru%" AND `Sportstaette`.`sportstaettenart_id` = 2';
		$this->assertPattern('/\s*WHERE\s+`Sportstaette`\.`sportstaette`\s+LIKE\s+"%ru%"\s+AND\s+`Sports/', $result);
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('Sportstaette.sportstaettenart_id = 2 AND Sportstaette.sportstaette LIKE "%ru%"');
		$expected = ' WHERE `Sportstaette`.`sportstaettenart_id` = 2 AND `Sportstaette`.`sportstaette` LIKE "%ru%"';
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('SUM(Post.comments_count) > 500 AND NOT Post.title IS NULL AND NOT Post.extended_title IS NULL');
		$expected = ' WHERE SUM(`Post`.`comments_count`) > 500 AND NOT `Post`.`title` IS NULL AND NOT `Post`.`extended_title` IS NULL';
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('NOT Post.title IS NULL AND NOT Post.extended_title IS NULL AND SUM(Post.comments_count) > 500');
		$expected = ' WHERE NOT `Post`.`title` IS NULL AND NOT `Post`.`extended_title` IS NULL AND SUM(`Post`.`comments_count`) > 500';
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('NOT Post.extended_title IS NULL AND NOT Post.title IS NULL AND Post.title != "" AND SPOON(SUM(Post.comments_count) + 1.1) > 500');
		$expected = ' WHERE NOT `Post`.`extended_title` IS NULL AND NOT `Post`.`title` IS NULL AND `Post`.`title` != "" AND SPOON(SUM(`Post`.`comments_count`) + 1.1) > 500';
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('NOT Post.title_extended IS NULL AND NOT Post.title IS NULL AND Post.title_extended != Post.title');
		$expected = ' WHERE NOT `Post`.`title_extended` IS NULL AND NOT `Post`.`title` IS NULL AND `Post`.`title_extended` != `Post`.`title`';
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("Comment.id = 'a'");
		$expected = " WHERE `Comment`.`id` = 'a'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("lower(Article.title) LIKE 'a%'");
		$expected = " WHERE lower(`Article`.`title`) LIKE 'a%'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('((MATCH(Video.title) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 2) + (MATCH(Video.description) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 0.4) + (MATCH(Video.tags) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 1.5))');
		$expected = ' WHERE ((MATCH(`Video`.`title`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 2) + (MATCH(`Video`.`description`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 0.4) + (MATCH(`Video`.`tags`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 1.5))';
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('DATEDIFF(NOW(),Article.published) < 1 && Article.live=1');
		$expected = " WHERE DATEDIFF(NOW(),`Article`.`published`) < 1 && `Article`.`live`=1";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('file = "index.html"');
		$expected = ' WHERE file = "index.html"';
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions("file = 'index.html'");
		$expected = " WHERE file = 'index.html'";
		$this->assertEqual($result, $expected);

		$letter = $letter = 'd.a';
		$conditions = array('Company.name' => 'like '.$letter.'%');
		$result = $this->db->conditions($conditions);
		$expected = " WHERE `Company`.`name` like  'd.a%'";
		$this->assertEqual($result, $expected);
	}

	function testQuotesInStringConditions() {
		$result = $this->db->conditions('Member.email = \'mariano@cricava.com\'');
		$expected = ' WHERE `Member`.`email` = \'mariano@cricava.com\'';
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('Member.email = "mariano@cricava.com"');
		$expected = ' WHERE `Member`.`email` = "mariano@cricava.com"';
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions('Member.email = \'mariano@cricava.com\' AND Member.user LIKE \'mariano.iglesias%\'');
		$expected = ' WHERE `Member`.`email` = \'mariano@cricava.com\' AND `Member`.`user` LIKE \'mariano.iglesias%\'';
		$this->assertEqual($result, $expected);


		$result = $this->db->conditions('Member.email = "mariano@cricava.com" AND Member.user LIKE "mariano.iglesias%"');
		$expected = ' WHERE `Member`.`email` = "mariano@cricava.com" AND `Member`.`user` LIKE "mariano.iglesias%"';
		$this->assertEqual($result, $expected);
	}

	function testParenthesisInStringConditions() {
		$result = $this->db->conditions('Member.name = \'(lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(lu\'$/', $result);

		$result = $this->db->conditions('Member.name = \')lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\)lu\'$/', $result);

		$result = $this->db->conditions('Member.name = \'va(lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\'$/', $result);

		$result = $this->db->conditions('Member.name = \'va)lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\)lu\'$/', $result);

		$result = $this->db->conditions('Member.name = \'va(lu)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)\'$/', $result);

		$result = $this->db->conditions('Member.name = \'va(lu)e\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)e\'$/', $result);

		$result = $this->db->conditions('Member.name = \'(mariano)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)\'$/', $result);

		$result = $this->db->conditions('Member.name = \'(mariano)iglesias\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)iglesias\'$/', $result);

		$result = $this->db->conditions('Member.name = \'(mariano) iglesias\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\) iglesias\'$/', $result);

		$result = $this->db->conditions('Member.name = \'(mariano word) iglesias\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano word\) iglesias\'$/', $result);

		$result = $this->db->conditions('Member.name = \'(mariano.iglesias)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\)\'$/', $result);

		$result = $this->db->conditions('Member.name = \'Mariano Iglesias (mariano.iglesias)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\)\'$/', $result);

		$result = $this->db->conditions('Member.name = \'Mariano Iglesias (mariano.iglesias) CakePHP\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\) CakePHP\'$/', $result);

		$result = $this->db->conditions('Member.name = \'(mariano.iglesias) CakePHP\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\) CakePHP\'$/', $result);
	}

	function testParenthesisInArrayConditions() {
		$result = $this->db->conditions(array('Member.name' => '(lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(lu\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => ')lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\)lu\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => 'va(lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => 'va)lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\)lu\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => 'va(lu)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => 'va(lu)e'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)e\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => '(mariano)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => '(mariano)iglesias'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)iglesias\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => '(mariano) iglesias'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\) iglesias\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => '(mariano word) iglesias'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano word\) iglesias\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => '(mariano.iglesias)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\)\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => 'Mariano Iglesias (mariano.iglesias)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\)\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => 'Mariano Iglesias (mariano.iglesias) CakePHP'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\) CakePHP\'$/', $result);

		$result = $this->db->conditions(array('Member.name' => '(mariano.iglesias) CakePHP'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\) CakePHP\'$/', $result);
	}

	function testArrayConditionsParsing() {
		$result = $this->db->conditions(array('Candy.name' => 'LIKE a', 'HardCandy.name' => 'LIKE c'));
		$this->assertPattern("/^\s+WHERE\s+`Candy`.`name` LIKE\s+'a'\s+AND\s+`HardCandy`.`name`\s+LIKE\s+'c'/", $result);

		$result = $this->db->conditions(array('HardCandy.name' => 'LIKE a', 'Candy.name' => 'LIKE c'));
		$expected = " WHERE `HardCandy`.`name` LIKE  'a' AND `Candy`.`name` LIKE  'c'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('HardCandy.name' => 'LIKE a%', 'Candy.name' => 'LIKE %c%'));
		$expected = " WHERE `HardCandy`.`name` LIKE  'a%' AND `Candy`.`name` LIKE  '%c%'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('HardCandy.name' => 'LIKE to be or%', 'Candy.name' => 'LIKE %not to be%'));
		$expected = " WHERE `HardCandy`.`name` LIKE  'to be or%' AND `Candy`.`name` LIKE  '%not to be%'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('score' => 'BETWEEN 90.1 AND 95.7'));
		$expected = " WHERE `score` BETWEEN  '90.1' AND '95.7'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('Post.title' => 1.1));
		$expected = " WHERE `Post`.`title`  =  1.1";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('SUM(Post.comments_count)' => '> 500'));
		$expected = " WHERE SUM(`Post`.`comments_count`) >  500";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('MAX(Post.rating)' => '> 50'));
		$expected = " WHERE MAX(`Post`.`rating`) >  50";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('title' => 'LIKE %hello'));
		$expected = " WHERE `title` LIKE  '%hello'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('Post.name' => '= mad(g)ik'));
		$expected = " WHERE `Post`.`name` =  'mad(g)ik'";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('score' => array(1, 2, 10)));
		$expected = " WHERE `score` IN (1, 2, 10) ";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('score' => '!= 20'));
		$expected = " WHERE `score` !=  20";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('score' => '> 20'));
		$expected = " WHERE `score` >  20";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('or' => array( 'score' => 'BETWEEN 4 AND 5', 'rating' => '> 20') ));
		$expected = " WHERE ((`score` BETWEEN  '4' AND '5') OR (`rating` >  20))";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('or' => array('score' => 'BETWEEN 4 AND 5', array('score' => '> 20')) ));
		$expected = " WHERE ((`score` BETWEEN  '4' AND '5') OR (`score` >  20))";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('and' => array( 'score' => 'BETWEEN 4 AND 5', array('score' => '> 20')) ));
		$expected = " WHERE ((`score` BETWEEN  '4' AND '5') AND (`score` >  20))";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('published' => 1, 'or' => array('score' => '< 2', array('score' => '> 20')) ));
		$expected = " WHERE `published`  =  1 AND ((`score` <  2) OR (`score` >  20))";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array(array('Project.removed' => false)));
		$this->assertPattern('/^\s*WHERE\s+`Project`.`removed`\s+=\s+0\s*$/', $result);

		$result = $this->db->conditions(array(array('Project.removed' => true)));
		$this->assertPattern('/^\s*WHERE\s+`Project`.`removed`\s+=\s+1\s*$/', $result);

		$result = $this->db->conditions(array(array('Project.removed' => null)));
		$this->assertPattern('/^\s*WHERE\s+`Project`.`removed`\s+IS\s+NULL\s*$/', $result);

		$result = $this->db->conditions(array('(Usergroup.permissions) & 4' => 4));
		$this->assertPattern('/^\s*WHERE\s+\(`Usergroup`\.`permissions`\)\s+& 4\s+=\s+4\s*$/', $result);

		$result = $this->db->conditions(array('((Usergroup.permissions) & 4)' => 4));
		$this->assertPattern('/^\s*WHERE\s+\(\(`Usergroup`\.`permissions`\)\s+& 4\)\s+=\s+4\s*$/', $result);

		$result = $this->db->conditions(array('Post.modified' => '>= DATE_SUB(NOW(), INTERVAL 7 DAY)'));
		$expected = " WHERE `Post`.`modified` >=  DATE_SUB(NOW(), INTERVAL 7 DAY)";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array(
			'NOT' => array('Course.id' => null, 'Course.vet' => 'N', 'level_of_education_id' => array(912,999)),
			'Enrollment.yearcompleted' => '> 0')
		);
		$this->assertPattern('/^\s*WHERE\s+NOT\s+\(\(`Course`\.`id` IS NULL\)\s+AND NOT\s+\(`Course`\.`vet`\s+=\s+\'N\'\)\s+AND NOT\s+\(`level_of_education_id` IN \(912, 999\)\s*\)\)\s+AND\s+`Enrollment`\.`yearcompleted`\s+>\s+0\s*$/', $result);

		$result = $this->db->conditions(array('id' => '<> 8'));
		$this->assertPattern('/^\s*WHERE\s+`id`\s+<>\s+8\s*$/', $result);

		$result = $this->db->conditions(array('TestModel.field' => '= gribe$@()lu'));
		$expected = " WHERE `TestModel`.`field` =  'gribe$@()lu'";
		$this->assertEqual($result, $expected);

		$conditions['NOT'] = array('Listing.expiration' => "BETWEEN 1 AND 100");
		$conditions[0]['OR'] = array(
			"Listing.title" => "LIKE %term%",
			"Listing.description" => "LIKE %term%"
		);
		$conditions[1]['OR'] = array(
			"Listing.title" => "LIKE %term_2%",
			"Listing.description" => "LIKE %term_2%"
		);
		$result = $this->db->conditions($conditions);
		$expected = " WHERE NOT ((`Listing`.`expiration` BETWEEN  '1' AND '100')) AND ((`Listing`.`title` LIKE  '%term%') OR (`Listing`.`description` LIKE  '%term%')) AND ((`Listing`.`title` LIKE  '%term_2%') OR (`Listing`.`description` LIKE  '%term_2%'))";
		$this->assertEqual($result, $expected);

		$result = $this->db->conditions(array('MD5(CONCAT(Reg.email,Reg.id))' => 'blah'));
		$expected = " WHERE MD5(CONCAT(`Reg`.`email`,`Reg`.`id`))  =  'blah'";
		$this->assertEqual($result, $expected);

		$conditions = array('id' => array(2, 5, 6, 9, 12, 45, 78, 43, 76));
		$result = $this->db->conditions($conditions);
		$expected = " WHERE `id` IN (2, 5, 6, 9, 12, 45, 78, 43, 76) ";
		$this->assertEqual($result, $expected);
	}

	function testMixedConditionsParsing() {
		$conditions[] = 'User.first_name = \'Firstname\'';
		$conditions[] = array('User.last_name' => 'Lastname');
		$result = $this->db->conditions($conditions);
		$expected = " WHERE `User`.`first_name` = 'Firstname' AND `User`.`last_name`  =  'Lastname'";
		$this->assertEqual($result, $expected);

		$conditions = array(
			'Thread.project_id' => 5,
			'Thread.buyer_id' => 14,
			'1=1 GROUP BY Thread.project_id'
		);
		$result = $this->db->conditions($conditions);
		$this->assertPattern('/^\s*WHERE\s+`Thread`.`project_id`\s*=\s*5\s+AND\s+`Thread`.`buyer_id`\s*=\s*14\s+AND\s+1\s*=\s*1\s+GROUP BY `Thread`.`project_id`$/', $result);
	}

	function testFieldParsing() {
		$result = $this->db->fields($this->model, 'Vendor', "Vendor.id, COUNT(Model.vendor_id) AS `Vendor`.`count`");
		$expected = array('`Vendor`.`id`', 'COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`');
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, 'Vendor', "`Vendor`.`id`, COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`");
		$expected = array('`Vendor`.`id`', 'COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`');
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, 'Post', "CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name, Node.created");
		$expected = array("CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name", "`Node`.`created`");
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, null, 'round( (3.55441 * fooField), 3 ) AS test');
		$this->assertEqual($result, array('round( (3.55441 * fooField), 3 ) AS test'));

		$result = $this->db->fields($this->model, null, 'ROUND(`Rating`.`rate_total` / `Rating`.`rate_count`,2) AS rating');
		$this->assertEqual($result, array('ROUND(`Rating`.`rate_total` / `Rating`.`rate_count`,2) AS rating'));

		$result = $this->db->fields($this->model, null, 'ROUND(Rating.rate_total / Rating.rate_count,2) AS rating');
		$this->assertEqual($result, array('ROUND(Rating.rate_total / Rating.rate_count,2) AS rating'));

		$result = $this->db->fields($this->model, 'Post', "Node.created, CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name");
		$expected = array("`Node`.`created`", "CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name");
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, 'Post', "2.2,COUNT(*), SUM(Something.else) as sum, Node.created, CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name,Post.title,Post.1,1.1");
		$expected = array(
			'2.2', 'COUNT(*)', 'SUM(`Something`.`else`) as sum', '`Node`.`created`',
			"CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name", '`Post`.`title`', '`Post`.`1`', '1.1'
		);
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, 'Post');
		$expected = array(
			'`Post`.`id`', '`Post`.`client_id`', '`Post`.`name`', '`Post`.`login`',
			'`Post`.`passwd`', '`Post`.`addr_1`', '`Post`.`addr_2`', '`Post`.`zip_code`',
			'`Post`.`city`', '`Post`.`country`', '`Post`.`phone`', '`Post`.`fax`',
			'`Post`.`url`', '`Post`.`email`', '`Post`.`comments`', '`Post`.`last_login`',
			'`Post`.`created`', '`Post`.`updated`'
		);
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, 'Other');
		$expected = array(
			'`Other`.`id`', '`Other`.`client_id`', '`Other`.`name`', '`Other`.`login`',
			'`Other`.`passwd`', '`Other`.`addr_1`', '`Other`.`addr_2`', '`Other`.`zip_code`',
			'`Other`.`city`', '`Other`.`country`', '`Other`.`phone`', '`Other`.`fax`',
			'`Other`.`url`', '`Other`.`email`', '`Other`.`comments`', '`Other`.`last_login`',
			'`Other`.`created`', '`Other`.`updated`'
		);
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, null, array(), false);
		$expected = array('id', 'client_id', 'name', 'login', 'passwd', 'addr_1', 'addr_2', 'zip_code', 'city', 'country', 'phone', 'fax', 'url', 'email', 'comments', 'last_login', 'created', 'updated');
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, null, 'COUNT(*)');
		$expected = array('COUNT(*)');
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, null, 'SUM(Thread.unread_buyer) AS ' . $this->db->name('sum_unread_buyer'));
		$expected = array('SUM(`Thread`.`unread_buyer`) AS `sum_unread_buyer`');
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, null, 'name, count(*)');
		$expected = array('`TestModel`.`name`', 'count(*)');
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, null, 'count(*), name');
		$expected = array('count(*)', '`TestModel`.`name`');
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, null, 'field1, field2, field3, count(*), name');
		$expected = array('`TestModel`.`field1`', '`TestModel`.`field2`', '`TestModel`.`field3`', 'count(*)', '`TestModel`.`name`');
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, null, array('dayofyear(now())'));
		$expected = array('dayofyear(now())');
		$this->assertEqual($result, $expected);
	}

 	function testMergeAssociations() {
 		$data = array(
 			'Article2' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			)
 		);
 		$merge = array(
 			'Topic' => array (
 				array(
 					'id' => '1', 'topic' => 'Topic', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				)
 			)
 		);
 		$expected = array(
 			'Article2' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			),
 			'Topic' => array(
 				'id' => '1', 'topic' => 'Topic', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 			)
 		);
 		$this->db->__mergeAssociation($data, $merge, 'Topic', 'hasOne');
 		$this->assertEqual($data, $expected);

 		$data = array(
 			'Article2' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			)
 		);
 		$merge = array(
 			'User2' => array (
 				array(
 					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				)
 			)
 		);
 		$expected = array(
 			'Article2' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			),
 			'User2' => array(
 				'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 			)
 		);
 		$this->db->__mergeAssociation($data, $merge, 'User2', 'belongsTo');
 		$this->assertEqual($data, $expected);

 		$data = array(
 			'Article2' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			)
 		);
 		$merge = array(
 			array (
 				'Comment' => false
 			)
 		);
 		$expected = array(
 			'Article2' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			),
 			'Comment' => array()
 		);
 		$this->db->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
 		$this->assertEqual($data, $expected);

 		$data = array(
 			'Article' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			)
 		);
 		$merge = array(
 			array (
 				'Comment' => array(
 					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				)
 			),
 			array(
 				'Comment' => array(
 					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				)
 			)
 		);
 		$expected = array(
 			'Article' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			),
 			'Comment' => array(
 				array(
 					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				),
 				array(
 					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				)
 			)
 		);
 		$this->db->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
 		$this->assertEqual($data, $expected);

 		$data = array(
 			'Article' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			)
 		);
 		$merge = array(
 			array (
 				'Comment' => array(
 					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				),
 				'User2' => array(
 					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				)
 			),
 			array(
 				'Comment' => array(
 					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				),
 				'User2' => array(
 					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				)
 			)
 		);
 		$expected = array(
 			'Article' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			),
 			'Comment' => array(
 				array(
 					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
 					'User2' => array(
 						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 					)
 				),
 				array(
 					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
 					'User2' => array(
 						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 					)
 				)
 			)
 		);
 		$this->db->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
 		$this->assertEqual($data, $expected);

 		$data = array(
 			'Article' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			)
 		);
 		$merge = array(
 			array (
 				'Comment' => array(
 					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				),
 				'User2' => array(
 					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				),
 				'Tag' => array(
 					array('id' => 1, 'tag' => 'Tag 1'),
 					array('id' => 2, 'tag' => 'Tag 2')
 				)
 			),
 			array(
 				'Comment' => array(
 					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				),
 				'User2' => array(
 					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				),
 				'Tag' => array()
 			)
 		);
 		$expected = array(
 			'Article' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			),
 			'Comment' => array(
 				array(
 					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
 					'User2' => array(
 						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 					),
 					'Tag' => array(
 						array('id' => 1, 'tag' => 'Tag 1'),
 						array('id' => 2, 'tag' => 'Tag 2')
 					)
 				),
 				array(
 					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
 					'User2' => array(
 						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 					),
 					'Tag' => array()
 				)
 			)
 		);
 		$this->db->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
 		$this->assertEqual($data, $expected);

 		$data = array(
 			'Article' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			)
 		);
 		$merge = array(
 			array (
 				'Tag' => array(
 					'id' => '1', 'tag' => 'Tag 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				)
 			),
 			array(
 				'Tag' => array(
 					'id' => '2', 'tag' => 'Tag 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				)
 			),
 			array(
 				'Tag' => array(
 					'id' => '3', 'tag' => 'Tag 3', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				)
 			)
 		);
 		$expected = array(
 			'Article' => array(
 				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
 			),
 			'Tag' => array(
 				array(
 					'id' => '1', 'tag' => 'Tag 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				),
 				array(
 					'id' => '2', 'tag' => 'Tag 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				),
 				array(
 					'id' => '3', 'tag' => 'Tag 3', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
 				)
 			)
 		);
 		$this->db->__mergeAssociation($data, $merge, 'Tag', 'hasAndBelongsToMany');
 		$this->assertEqual($data, $expected);
 	}

	function testMagicMethodQuerying() {
		$result = $this->db->query('findByFieldName', array('value'), $this->model);
		$expected = array('TestModel.field_name' => '= value');
		$this->assertEqual($result, $expected);

		$result = $this->db->query('findAllByFieldName', array('value'), $this->model);
		$expected = array('TestModel.field_name' => '= value');
		$this->assertEqual($result, $expected);

		$result = $this->db->query('findAllById', array('a'), $this->model);
		$expected = array('TestModel.id' => '= a');
		$this->assertEqual($result, $expected);

		$result = $this->db->query('findByFieldName', array(array('value1', 'value2', 'value3')), $this->model);
		$expected = array('TestModel.field_name' => array('value1', 'value2', 'value3'));
		$this->assertEqual($result, $expected);

		$result = $this->db->query('findByFieldName', array(null), $this->model);
		$expected = array('TestModel.field_name' => null);
		$this->assertEqual($result, $expected);

		$result = $this->db->query('findByFieldName', array('= a'), $this->model);
		$expected = array('TestModel.field_name' => '= = a');
		$this->assertEqual($result, $expected);

		$result = $this->db->query('findByFieldName', array(), $this->model);
		$expected = false;
		$this->assertEqual($result, $expected);

	}

	function testOrderParsing() {
		$result = $this->db->order("ADDTIME(Event.time_begin, '-06:00:00') ASC");
		$expected = " ORDER BY ADDTIME(`Event`.`time_begin`, '-06:00:00') ASC";
		$this->assertEqual($result, $expected);

		$result = $this->db->order("title, id");
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->db->order("title desc, id desc");
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+desc,\s+`id`\s+desc\s*$/', $result);

		$result = $this->db->order(array("title desc, id desc"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+desc,\s+`id`\s+desc\s*$/', $result);

		$result = $this->db->order(array("title", "id"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->db->order(array(array('title'), array('id')));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->db->order(array("Post.title" => 'asc', "Post.id" => 'desc'));
		$this->assertPattern('/^\s*ORDER BY\s+`Post`.`title`\s+asc,\s+`Post`.`id`\s+desc\s*$/', $result);

		$result = $this->db->order(array(array("Post.title" => 'asc', "Post.id" => 'desc')));
		$this->assertPattern('/^\s*ORDER BY\s+`Post`.`title`\s+asc,\s+`Post`.`id`\s+desc\s*$/', $result);

		$result = $this->db->order(array("title"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC\s*$/', $result);

		$result = $this->db->order(array(array("title")));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC\s*$/', $result);

		$result = $this->db->order("Dealer.id = 7 desc, Dealer.id = 3 desc, Dealer.title asc");
		$expected = " ORDER BY Dealer`.`id` = 7 desc,  Dealer`.`id` = 3 desc,  `Dealer`.`title` asc";
		$this->assertEqual($result, $expected);

		$result = $this->db->order(array("Page.name"=>"='test' DESC"));
		$this->assertPattern("/^\s*ORDER BY\s+`Page`\.`name`\s*='test'\s+DESC\s*$/", $result);

		$result = $this->db->order("Page.name = 'view' DESC");
		$this->assertPattern("/^\s*ORDER BY\s+`Page`\.`name`\s*=\s*'view'\s+DESC\s*$/", $result);
	}
}
?>