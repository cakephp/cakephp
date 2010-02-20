<?php
/**
 * DboSourceTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *	Licensed under The Open Group Test Suite License
 *	Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
App::import('Model', array('Model', 'DataSource', 'DboSource', 'DboMysql', 'App'));
require_once dirname(dirname(__FILE__)) . DS . 'models.php';

/**
 * TestModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class TestModel extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel'
 * @access public
 */
	var $name = 'TestModel';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * schema property
 *
 * @var array
 * @access protected
 */
	var $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'client_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '11'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'login' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'passwd' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'addr_1' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'addr_2' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'),
		'zip_code' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'city' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'country' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'phone' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'fax' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'url' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
		'comments' => array('type' => 'text', 'null' => '1', 'default' => '', 'length' => '155'),
		'last_login' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

/**
 * find method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @access public
 * @return void
 */
	function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return array($conditions, $fields);
	}

/**
 * findAll method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @access public
 * @return void
 */
	function findAll($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}
}

/**
 * TestModel2 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class TestModel2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel2'
 * @access public
 */
	var $name = 'TestModel2';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
}

/**
 * TestModel4 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class TestModel3 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel3'
 * @access public
 */
	var $name = 'TestModel3';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
}

/**
 * TestModel4 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class TestModel4 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel4'
 * @access public
 */
	var $name = 'TestModel4';

/**
 * table property
 *
 * @var string 'test_model4'
 * @access public
 */
	var $table = 'test_model4';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array(
		'TestModel4Parent' => array(
			'className' => 'TestModel4',
			'foreignKey' => 'parent_id'
		)
	);

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array(
		'TestModel5' => array(
			'className' => 'TestModel5',
			'foreignKey' => 'test_model4_id'
		)
	);

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('TestModel7' => array(
		'className' => 'TestModel7',
		'joinTable' => 'test_model4_test_model7',
		'foreignKey' => 'test_model4_id',
		'associationForeignKey' => 'test_model7_id',
		'with' => 'TestModel4TestModel7'
	));

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel4TestModel7 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class TestModel4TestModel7 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel4TestModel7'
 * @access public
 */
	var $name = 'TestModel4TestModel7';

/**
 * table property
 *
 * @var string 'test_model4_test_model7'
 * @access public
 */
	var $table = 'test_model4_test_model7';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'test_model4_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'test_model7_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8')
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel5 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class TestModel5 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel5'
 * @access public
 */
	var $name = 'TestModel5';

/**
 * table property
 *
 * @var string 'test_model5'
 * @access public
 */
	var $table = 'test_model5';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('TestModel4' => array(
		'className' => 'TestModel4',
		'foreignKey' => 'test_model4_id'
	));

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('TestModel6' => array(
		'className' => 'TestModel6',
		'foreignKey' => 'test_model5_id'
	));

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'test_model4_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel6 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class TestModel6 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel6'
 * @access public
 */
	var $name = 'TestModel6';

/**
 * table property
 *
 * @var string 'test_model6'
 * @access public
 */
	var $table = 'test_model6';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('TestModel5' => array(
		'className' => 'TestModel5',
		'foreignKey' => 'test_model5_id'
	));

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'test_model5_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel7 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class TestModel7 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel7'
 * @access public
 */
	var $name = 'TestModel7';

/**
 * table property
 *
 * @var string 'test_model7'
 * @access public
 */
	var $table = 'test_model7';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel8 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class TestModel8 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel8'
 * @access public
 */
	var $name = 'TestModel8';

/**
 * table property
 *
 * @var string 'test_model8'
 * @access public
 */
	var $table = 'test_model8';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array(
		'TestModel9' => array(
			'className' => 'TestModel9',
			'foreignKey' => 'test_model8_id',
			'conditions' => 'TestModel9.name != \'mariano\''
		)
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'test_model9_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * TestModel9 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class TestModel9 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'TestModel9'
 * @access public
 */
	var $name = 'TestModel9';

/**
 * table property
 *
 * @var string 'test_model9'
 * @access public
 */
	var $table = 'test_model9';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('TestModel8' => array(
		'className' => 'TestModel8',
		'foreignKey' => 'test_model8_id',
		'conditions' => 'TestModel8.name != \'larry\''
	));

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
				'test_model8_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '11'),
				'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * Level class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class Level extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Level'
 * @access public
 */
	var $name = 'Level';

/**
 * table property
 *
 * @var string 'level'
 * @access public
 */
	var $table = 'level';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array(
		'Group'=> array(
			'className' => 'Group'
		),
		'User2' => array(
			'className' => 'User2'
		)
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'name' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20'),
			);
		}
		return $this->_schema;
	}
}

/**
 * Group class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class Group extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Group'
 * @access public
 */
	var $name = 'Group';

/**
 * table property
 *
 * @var string 'group'
 * @access public
 */
	var $table = 'group';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('Level');

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('Category2', 'User2');

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'level_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'name' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20'),
			);
		}
		return $this->_schema;
	}

}

/**
 * User2 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class User2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'User2'
 * @access public
 */
	var $name = 'User2';

/**
 * table property
 *
 * @var string 'user'
 * @access public
 */
	var $table = 'user';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array(
		'Group' => array(
			'className' => 'Group'
		),
		'Level' => array(
			'className' => 'Level'
		)
	);

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array(
		'Article2' => array(
			'className' => 'Article2'
		),
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'group_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'level_id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'name' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20'),
			);
		}
		return $this->_schema;
	}
}

/**
 * Category2 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class Category2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Category2'
 * @access public
 */
	var $name = 'Category2';

/**
 * table property
 *
 * @var string 'category'
 * @access public
 */
	var $table = 'category';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
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

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
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

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'group_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'parent_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'name' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				'icon' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				'description' => array('type' => 'text', 'null' => false, 'default' => '', 'length' => null),

			);
		}
		return $this->_schema;
	}
}

/**
 * Article2 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class Article2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Article2'
 * @access public
 */
	var $name = 'Article2';

/**
 * table property
 *
 * @var string 'article'
 * @access public
 */
	var $table = 'article';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array(
		'Category2' => array('className' => 'Category2'),
		'User2' => array('className' => 'User2')
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'category_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'user_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'rate_count' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'rate_sum' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'viewed' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'version' => array('type' => 'string', 'null' => true, 'default' => '', 'length' => '45'),
				'title' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '200'),
				'intro' => array('text' => 'string', 'null' => true, 'default' => '', 'length' => null),
				'comments' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '4'),
				'body' => array('text' => 'string', 'null' => true, 'default' => '', 'length' => null),
				'isdraft' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				'allow_comments' => array('type' => 'boolean', 'null' => false, 'default' => '1', 'length' => '1'),
				'moderate_comments' => array('type' => 'boolean', 'null' => false, 'default' => '1', 'length' => '1'),
				'published' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				'multipage' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				'published_date' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null),
				'created' => array('type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null),
				'modified' => array('type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * CategoryFeatured2 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class CategoryFeatured2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'CategoryFeatured2'
 * @access public
 */
	var $name = 'CategoryFeatured2';

/**
 * table property
 *
 * @var string 'category_featured'
 * @access public
 */
	var $table = 'category_featured';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'parent_id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '10'),
				'name' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				'icon' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
				'description' => array('text' => 'string', 'null' => false, 'default' => '', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * Featured2 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class Featured2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Featured2'
 * @access public
 */
	var $name = 'Featured2';

/**
 * table property
 *
 * @var string 'featured2'
 * @access public
 */
	var $table = 'featured2';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array(
		'CategoryFeatured2' => array(
			'className' => 'CategoryFeatured2'
		)
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'article_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'category_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'name' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20')
			);
		}
		return $this->_schema;
	}
}

/**
 * Comment2 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class Comment2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Comment2'
 * @access public
 */
	var $name = 'Comment2';

/**
 * table property
 *
 * @var string 'comment'
 * @access public
 */
	var $table = 'comment';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('ArticleFeatured2', 'User2');

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'article_featured_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'user_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'name' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20')
			);
		}
		return $this->_schema;
	}
}

/**
 * ArticleFeatured2 class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class ArticleFeatured2 extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ArticleFeatured2'
 * @access public
 */
	var $name = 'ArticleFeatured2';

/**
 * table property
 *
 * @var string 'article_featured'
 * @access public
 */
	var $table = 'article_featured';

/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array(
		'CategoryFeatured2' => array('className' => 'CategoryFeatured2'),
		'User2' => array('className' => 'User2')
	);

/**
 * hasOne property
 *
 * @var array
 * @access public
 */
	var $hasOne = array(
		'Featured2' => array('className' => 'Featured2')
	);

/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array(
		'Comment2' => array('className'=>'Comment2', 'dependent' => true)
	);

/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		if (!isset($this->_schema)) {
			$this->_schema = array(
				'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => '10'),
				'category_featured_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'user_id' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => '10'),
				'title' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => '20'),
				'body' => array('text' => 'string', 'null' => true, 'default' => '', 'length' => null),
				'published' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'length' => '1'),
				'published_date' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null),
				'created' => array('type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null),
				'modified' => array('type' => 'datetime', 'null' => false, 'default' => '0000-00-00 00:00:00', 'length' => null)
			);
		}
		return $this->_schema;
	}
}

/**
 * DboSourceTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class DboSourceTest extends CakeTestCase {

/**
 * debug property
 *
 * @var mixed null
 * @access public
 */
	var $debug = null;

/**
 * autoFixtures property
 *
 * @var bool false
 * @access public
 */
	var $autoFixtures = false;

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array(
		'core.apple', 'core.article', 'core.articles_tag', 'core.attachment', 'core.comment',
		'core.sample', 'core.tag', 'core.user', 'core.post', 'core.author'
	);

/**
 * startTest method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->__config = $this->db->config;

		if (!class_exists('DboTest')) {
			$db = ConnectionManager::getDataSource('test_suite');
			$class = get_class($db);
			eval("class DboTest extends $class {
				var \$simulated = array();

/**
 * execute method
 *
 * @param \$sql
 * @access protected
 * @return void
 */
				function _execute(\$sql) {
					\$this->simulated[] = \$sql;
					return null;
				}

/**
 * getLastQuery method
 *
 * @access public
 * @return void
 */
				function getLastQuery() {
					return \$this->simulated[count(\$this->simulated) - 1];
				}
			}");
		}

		$this->testDb =& new DboTest($this->__config);
		$this->testDb->cacheSources = false;
		$this->testDb->startQuote = '`';
		$this->testDb->endQuote = '`';
		Configure::write('debug', 1);
		$this->debug = Configure::read('debug');
		$this->Model =& new TestModel();
	}

/**
 * endTest method
 *
 * @access public
 * @return void
 */
	function endTest() {
		unset($this->Model);
		Configure::write('debug', $this->debug);
		unset($this->debug);
	}

/**
 * testFieldDoubleEscaping method
 *
 * @access public
 * @return void
 */
	function testFieldDoubleEscaping() {
		$config = array_merge($this->__config, array('driver' => 'test'));
		$test =& ConnectionManager::create('quoteTest', $config);
		$test->simulated = array();

		$this->Model =& new Article2(array('alias' => 'Article', 'ds' => 'quoteTest'));
		$this->Model->setDataSource('quoteTest');

		$this->assertEqual($this->Model->escapeField(), '`Article`.`id`');
		$result = $test->fields($this->Model, null, $this->Model->escapeField());
		$this->assertEqual($result, array('`Article`.`id`'));

		$result = $test->read($this->Model, array(
			'fields' => $this->Model->escapeField(),
			'conditions' => null,
			'recursive' => -1
		));
		$this->assertEqual(trim($test->simulated[0]), 'SELECT `Article`.`id` FROM `' . $this->testDb->fullTableName('article', false) . '` AS `Article`   WHERE 1 = 1');

		$test->startQuote = '[';
		$test->endQuote = ']';
		$this->assertEqual($this->Model->escapeField(), '[Article].[id]');

		$result = $test->fields($this->Model, null, $this->Model->escapeField());
		$this->assertEqual($result, array('[Article].[id]'));

		$result = $test->read($this->Model, array(
			'fields' => $this->Model->escapeField(),
			'conditions' => null,
			'recursive' => -1
		));
		$this->assertEqual(trim($test->simulated[1]), 'SELECT [Article].[id] FROM [' . $this->testDb->fullTableName('article', false) . '] AS [Article]   WHERE 1 = 1');

		ClassRegistry::removeObject('Article');
	}

/**
 * testGenerateAssociationQuerySelfJoin method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQuerySelfJoin() {
		$this->startTime = microtime(true);
		$this->Model =& new Article2();
		$this->_buildRelatedModels($this->Model);
		$this->_buildRelatedModels($this->Model->Category2);
		$this->Model->Category2->ChildCat =& new Category2();
		$this->Model->Category2->ParentCat =& new Category2();

		$queryData = array();

		foreach ($this->Model->Category2->__associations as $type) {
			foreach ($this->Model->Category2->{$type} as $assoc => $assocData) {
				$linkModel =& $this->Model->Category2->{$assoc};
				$external = isset($assocData['external']);

				if ($this->Model->Category2->alias == $linkModel->alias && $type != 'hasAndBelongsToMany' && $type != 'hasMany') {
					$result = $this->testDb->generateAssociationQuery($this->Model->Category2, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null);
					$this->assertTrue($result);
				} else {
					if ($this->Model->Category2->useDbConfig == $linkModel->useDbConfig) {
						$result = $this->testDb->generateAssociationQuery($this->Model->Category2, $linkModel, $type, $assoc, $assocData, $queryData, $external, $null);
						$this->assertTrue($result);
					}
				}
			}
		}

		$query = $this->testDb->generateAssociationQuery($this->Model->Category2, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+(.+)FROM(.+)`Category2`\.`group_id`\s+=\s+`Group`\.`id`\)\s+LEFT JOIN(.+)WHERE\s+1 = 1\s*$/', $query);

		$this->Model =& new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel4Parent');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$_queryData = $queryData;
		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$expected = array(
			'fields' => array(
				'`TestModel4`.`id`',
				'`TestModel4`.`name`',
				'`TestModel4`.`created`',
				'`TestModel4`.`updated`',
				'`TestModel4Parent`.`id`',
				'`TestModel4Parent`.`name`',
				'`TestModel4Parent`.`created`',
				'`TestModel4Parent`.`updated`'
			),
			'joins' => array(
				array(
					'table' => '`test_model4`',
					'alias' => 'TestModel4Parent',
					'type' => 'LEFT',
					'conditions' => '`TestModel4`.`parent_id` = `TestModel4Parent`.`id`'
				)
			),
			'limit' => array(),
			'offset' => array(),
			'conditions' => array(),
			'order' => array(),
			'group' => null
		);
		$this->assertEqual($queryData, $expected);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel4Parent`\.`id`, `TestModel4Parent`\.`name`, `TestModel4Parent`\.`created`, `TestModel4Parent`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+`test_model4` AS `TestModel4Parent`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4`.`parent_id` = `TestModel4Parent`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+1 = 1\s+$/', $result);

		$params['assocData']['type'] = 'INNER';
		$this->Model->belongsTo['TestModel4Parent']['type'] = 'INNER';
		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $_queryData, $params['external'], $resultSet);
		$this->assertTrue($result);
		$this->assertEqual($_queryData['joins'][0]['type'], 'INNER');
	}

/**
 * testGenerateInnerJoinAssociationQuery method
 *
 * @access public
 * @return void
 */
	function testGenerateInnerJoinAssociationQuery() {
		$this->Model =& new TestModel9();
		$test =& ConnectionManager::create('test2', $this->__config);
		$this->Model->setDataSource('test2');
		$this->Model->TestModel8 =& new TestModel8();
		$this->Model->TestModel8->setDataSource('test2');

		$this->testDb->read($this->Model, array('recursive' => 1));
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/`TestModel9` LEFT JOIN `' . $this->testDb->fullTableName('test_model8', false) . '`/', $result);

		$this->Model->belongsTo['TestModel8']['type'] = 'INNER';
		$this->testDb->read($this->Model, array('recursive' => 1));
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/`TestModel9` INNER JOIN `' . $this->testDb->fullTableName('test_model8', false) . '`/', $result);

	}

/**
 * testGenerateAssociationQuerySelfJoinWithConditionsInHasOneBinding method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQuerySelfJoinWithConditionsInHasOneBinding() {
		$this->Model =& new TestModel8();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasOne', 'model' => 'TestModel9');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);
		$_queryData = $queryData;
		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel8`\.`id`, `TestModel8`\.`test_model9_id`, `TestModel8`\.`name`, `TestModel8`\.`created`, `TestModel8`\.`updated`, `TestModel9`\.`id`, `TestModel9`\.`test_model8_id`, `TestModel9`\.`name`, `TestModel9`\.`created`, `TestModel9`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model8` AS `TestModel8`\s+LEFT JOIN\s+`test_model9` AS `TestModel9`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel9`\.`name` != \'mariano\'\s+AND\s+`TestModel9`.`test_model8_id` = `TestModel8`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQuerySelfJoinWithConditionsInBelongsToBinding method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQuerySelfJoinWithConditionsInBelongsToBinding() {
		$this->Model =& new TestModel9();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel8');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);
		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel9`\.`id`, `TestModel9`\.`test_model8_id`, `TestModel9`\.`name`, `TestModel9`\.`created`, `TestModel9`\.`updated`, `TestModel8`\.`id`, `TestModel8`\.`test_model9_id`, `TestModel8`\.`name`, `TestModel8`\.`created`, `TestModel8`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model9` AS `TestModel9`\s+LEFT JOIN\s+`test_model8` AS `TestModel8`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel8`\.`name` != \'larry\'\s+AND\s+`TestModel9`.`test_model8_id` = `TestModel8`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQuerySelfJoinWithConditions method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQuerySelfJoinWithConditions() {
		$this->Model =& new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel4Parent');
		$queryData = array('conditions' => array('TestModel4Parent.name !=' => 'mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel4Parent`\.`id`, `TestModel4Parent`\.`name`, `TestModel4Parent`\.`created`, `TestModel4Parent`\.`updated`\s+/', $result);
		$this->assertPattern('/FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+`test_model4` AS `TestModel4Parent`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4`.`parent_id` = `TestModel4Parent`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?`TestModel4Parent`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);

		$this->Featured2 =& new Featured2();
		$this->Featured2->schema();

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

		$result = $this->testDb->generateAssociationQuery($this->Featured2, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);
		$result = $this->testDb->generateAssociationQuery($this->Featured2, $null, null, null, null, $queryData, false, $null);

		$this->assertPattern(
			'/^SELECT\s+`Featured2`\.`id`, `Featured2`\.`article_id`, `Featured2`\.`category_id`, `Featured2`\.`name`,\s+'.
			'`ArticleFeatured2`\.`id`, `ArticleFeatured2`\.`title`, `ArticleFeatured2`\.`user_id`, `ArticleFeatured2`\.`published`\s+' .
			'FROM\s+`featured2` AS `Featured2`\s+LEFT JOIN\s+`article_featured` AS `ArticleFeatured2`' .
			'\s+ON\s+\(`ArticleFeatured2`.`published` = \'Y\'\s+AND\s+`Featured2`\.`article_featured2_id` = `ArticleFeatured2`\.`id`\)' .
			'\s+WHERE\s+1\s+=\s+1\s*$/',
			$result
		);
	}

/**
 * testGenerateAssociationQueryHasOne method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasOne() {
		$this->Model =& new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasOne', 'model' => 'TestModel5');

		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->testDb->buildJoinStatement($queryData['joins'][0]);
		$expected = ' LEFT JOIN `test_model5` AS `TestModel5` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
		$this->assertEqual(trim($result), trim($expected));

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+/', $result);
		$this->assertPattern('/`test_model5` AS `TestModel5`\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasOneWithConditions method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasOneWithConditions() {
		$this->Model =& new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasOne', 'model' => 'TestModel5');

		$queryData = array('conditions' => array('TestModel5.name !=' => 'mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);

		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`, `TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+LEFT JOIN\s+`test_model5` AS `TestModel5`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel5`.`test_model4_id`\s+=\s+`TestModel4`.`id`\)\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*`TestModel5`.`name`\s+!=\s+\'mariano\'\s*(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryBelongsTo method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryBelongsTo() {
		$this->Model =& new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type'=>'belongsTo', 'model'=>'TestModel4');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->testDb->buildJoinStatement($queryData['joins'][0]);
		$expected = ' LEFT JOIN `test_model4` AS `TestModel4` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
		$this->assertEqual(trim($result), trim($expected));

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`, `TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+LEFT JOIN\s+`test_model4` AS `TestModel4`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryBelongsToWithConditions method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryBelongsToWithConditions() {
		$this->Model =& new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'belongsTo', 'model' => 'TestModel4');
		$queryData = array('conditions' => array('TestModel5.name !=' => 'mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertTrue($result);

		$result = $this->testDb->buildJoinStatement($queryData['joins'][0]);
		$expected = ' LEFT JOIN `test_model4` AS `TestModel4` ON (`TestModel5`.`test_model4_id` = `TestModel4`.`id`)';
		$this->assertEqual(trim($result), trim($expected));

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`, `TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+LEFT JOIN\s+`test_model4` AS `TestModel4`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel5`.`test_model4_id` = `TestModel4`.`id`\)\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+`TestModel5`.`name` != \'mariano\'\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasMany method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasMany() {
		$this->Model =& new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);

		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+`TestModel6`.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?\s*1 = 1\s*(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasManyWithLimit method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasManyWithLimit() {
		$this->Model =& new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$this->Model->hasMany['TestModel6']['limit'] = 2;

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern(
			'/^SELECT\s+' .
			'`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+'.
			'FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+' .
			'`TestModel6`.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)\s*'.
			'LIMIT \d*'.
			'\s*$/', $result
		);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern(
			'/^SELECT\s+'.
			'`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+'.
			'FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+'.
			'(?:\()?\s*1 = 1\s*(?:\))?'.
			'\s*$/', $result
		);
	}

/**
 * testGenerateAssociationQueryHasManyWithConditions method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasManyWithConditions() {
		$this->Model =& new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('conditions' => array('TestModel5.name !=' => 'mariano'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?`TestModel5`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasManyWithOffsetAndLimit method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasManyWithOffsetAndLimit() {
		$this->Model =& new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$__backup = $this->Model->hasMany['TestModel6'];

		$this->Model->hasMany['TestModel6']['offset'] = 2;
		$this->Model->hasMany['TestModel6']['limit'] = 5;

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);

		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);
		$this->assertPattern('/\s+LIMIT 2,\s*5\s*$/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->Model->hasMany['TestModel6'] = $__backup;
	}

/**
 * testGenerateAssociationQueryHasManyWithPageAndLimit method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasManyWithPageAndLimit() {
		$this->Model =& new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$__backup = $this->Model->hasMany['TestModel6'];

		$this->Model->hasMany['TestModel6']['page'] = 2;
		$this->Model->hasMany['TestModel6']['limit'] = 5;

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);
		$this->assertPattern('/\s+LIMIT 5,\s*5\s*$/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`test_model4_id`, `TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->Model->hasMany['TestModel6'] = $__backup;
	}

/**
 * testGenerateAssociationQueryHasManyWithFields method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasManyWithFields() {
		$this->Model =& new TestModel5();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`name`, `TestModel5`\.`id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`, `TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`name`', '`TestModel5`.`created`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`test_model5_id`, `TestModel6`\.`name`, `TestModel6`\.`created`, `TestModel6`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`name`, `TestModel5`\.`created`, `TestModel5`\.`id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->Model->hasMany['TestModel6']['fields'] = array('name');

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`', '`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`name`, `TestModel6`\.`test_model5_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		unset($this->Model->hasMany['TestModel6']['fields']);

		$this->Model->hasMany['TestModel6']['fields'] = array('id', 'name');

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`', '`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`id`, `TestModel6`\.`name`, `TestModel6`\.`test_model5_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		unset($this->Model->hasMany['TestModel6']['fields']);

		$this->Model->hasMany['TestModel6']['fields'] = array('test_model5_id', 'name');

		$binding = array('type' => 'hasMany', 'model' => 'TestModel6');
		$queryData = array('fields' => array('`TestModel5`.`id`', '`TestModel5`.`name`'));
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel6`\.`test_model5_id`, `TestModel6`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model6` AS `TestModel6`\s+WHERE\s+/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?`TestModel6`\.`test_model5_id`\s+=\s+\({\$__cakeID__\$}\)(?:\))?/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel5`\.`id`, `TestModel5`\.`name`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model5` AS `TestModel5`\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		unset($this->Model->hasMany['TestModel6']['fields']);
	}

/**
 * testGenerateAssociationQueryHasAndBelongsToMany method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasAndBelongsToMany() {
		$this->Model =& new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type' => 'hasAndBelongsToMany', 'model' => 'TestModel7');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params =& $this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7` AS `TestModel7`\s+JOIN\s+`' . $this->testDb->fullTableName('test_model4_test_model7', false) . '`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}\s+AND/', $result);
		$this->assertPattern('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)/', $result);
		$this->assertPattern('/WHERE\s+(?:\()?1 = 1(?:\))?\s*$/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE/', $result);
		$this->assertPattern('/\s+WHERE\s+(?:\()?1 = 1(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasAndBelongsToManyWithConditions method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasAndBelongsToManyWithConditions() {
		$this->Model =& new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$binding = array('type'=>'hasAndBelongsToMany', 'model'=>'TestModel7');
		$queryData = array('conditions' => array('TestModel4.name !=' => 'mariano'));
		$resultSet = null;
		$null = null;

		$params =& $this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7`\s+AS\s+`TestModel7`\s+JOIN\s+`test_model4_test_model7`\s+AS\s+`TestModel4TestModel7`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}/', $result);
		$this->assertPattern('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)\s+WHERE\s+/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?`TestModel4`.`name`\s+!=\s+\'mariano\'(?:\))?\s*$/', $result);
	}

/**
 * testGenerateAssociationQueryHasAndBelongsToManyWithOffsetAndLimit method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasAndBelongsToManyWithOffsetAndLimit() {
		$this->Model =& new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$__backup = $this->Model->hasAndBelongsToMany['TestModel7'];

		$this->Model->hasAndBelongsToMany['TestModel7']['offset'] = 2;
		$this->Model->hasAndBelongsToMany['TestModel7']['limit'] = 5;

		$binding = array('type'=>'hasAndBelongsToMany', 'model'=>'TestModel7');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7`\s+AS\s+`TestModel7`\s+JOIN\s+`test_model4_test_model7`\s+AS\s+`TestModel4TestModel7`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}\s+/', $result);
		$this->assertPattern('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+(?:\()?1\s+=\s+1(?:\))?\s*\s+LIMIT 2,\s*5\s*$/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->Model->hasAndBelongsToMany['TestModel7'] = $__backup;
	}

/**
 * testGenerateAssociationQueryHasAndBelongsToManyWithPageAndLimit method
 *
 * @access public
 * @return void
 */
	function testGenerateAssociationQueryHasAndBelongsToManyWithPageAndLimit() {
		$this->Model =& new TestModel4();
		$this->Model->schema();
		$this->_buildRelatedModels($this->Model);

		$__backup = $this->Model->hasAndBelongsToMany['TestModel7'];

		$this->Model->hasAndBelongsToMany['TestModel7']['page'] = 2;
		$this->Model->hasAndBelongsToMany['TestModel7']['limit'] = 5;

		$binding = array('type'=>'hasAndBelongsToMany', 'model'=>'TestModel7');
		$queryData = array();
		$resultSet = null;
		$null = null;

		$params = &$this->_prepareAssociationQuery($this->Model, $queryData, $binding);

		$result = $this->testDb->generateAssociationQuery($this->Model, $params['linkModel'], $params['type'], $params['assoc'], $params['assocData'], $queryData, $params['external'], $resultSet);
		$this->assertPattern('/^SELECT\s+`TestModel7`\.`id`, `TestModel7`\.`name`, `TestModel7`\.`created`, `TestModel7`\.`updated`, `TestModel4TestModel7`\.`test_model4_id`, `TestModel4TestModel7`\.`test_model7_id`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model7`\s+AS\s+`TestModel7`\s+JOIN\s+`test_model4_test_model7`\s+AS\s+`TestModel4TestModel7`/', $result);
		$this->assertPattern('/\s+ON\s+\(`TestModel4TestModel7`\.`test_model4_id`\s+=\s+{\$__cakeID__\$}/', $result);
		$this->assertPattern('/\s+AND\s+`TestModel4TestModel7`\.`test_model7_id`\s+=\s+`TestModel7`\.`id`\)\s+WHERE\s+/', $result);
		$this->assertPattern('/\s+(?:\()?1\s+=\s+1(?:\))?\s*\s+LIMIT 5,\s*5\s*$/', $result);

		$result = $this->testDb->generateAssociationQuery($this->Model, $null, null, null, null, $queryData, false, $null);
		$this->assertPattern('/^SELECT\s+`TestModel4`\.`id`, `TestModel4`\.`name`, `TestModel4`\.`created`, `TestModel4`\.`updated`\s+/', $result);
		$this->assertPattern('/\s+FROM\s+`test_model4` AS `TestModel4`\s+WHERE\s+(?:\()?1\s+=\s+1(?:\))?\s*$/', $result);

		$this->Model->hasAndBelongsToMany['TestModel7'] = $__backup;
	}

/**
 * buildRelatedModels method
 *
 * @param mixed $model
 * @access protected
 * @return void
 */
	function _buildRelatedModels(&$model) {
		foreach ($model->__associations as $type) {
			foreach ($model->{$type} as $assoc => $assocData) {
				if (is_string($assocData)) {
					$className = $assocData;
				} elseif (isset($assocData['className'])) {
					$className = $assocData['className'];
				}
				$model->$className =& new $className();
				$model->$className->schema();
			}
		}
	}

/**
 * &_prepareAssociationQuery method
 *
 * @param mixed $model
 * @param mixed $queryData
 * @param mixed $binding
 * @access public
 * @return void
 */
	function &_prepareAssociationQuery(&$model, &$queryData, $binding) {
		$type = $binding['type'];
		$assoc = $binding['model'];
		$assocData = $model->{$type}[$assoc];
		$className = $assocData['className'];

		$linkModel =& $model->{$className};
		$external = isset($assocData['external']);
		$queryData = $this->testDb->__scrubQueryData($queryData);

		$result = array_merge(array('linkModel' => &$linkModel), compact('type', 'assoc', 'assocData', 'external'));
		return $result;
	}

/**
 * testSelectDistict method
 *
 * @access public
 * @return void
 */
	function testSelectDistict() {
		$result = $this->testDb->fields($this->Model, 'Vendor', "DISTINCT Vendor.id, Vendor.name");
		$expected = array('DISTINCT `Vendor`.`id`', '`Vendor`.`name`');
		$this->assertEqual($result, $expected);
	}

/**
 * test that booleans and null make logical condition strings.
 *
 * @return void
 */
	function testBooleanNullConditionsParsing() {
		$result = $this->testDb->conditions(true);
		$this->assertEqual($result, ' WHERE 1 = 1', 'true conditions failed %s');

		$result = $this->testDb->conditions(false);
		$this->assertEqual($result, ' WHERE 0 = 1', 'false conditions failed %s');

		$result = $this->testDb->conditions(null);
		$this->assertEqual($result, ' WHERE 1 = 1', 'null conditions failed %s');

		$result = $this->testDb->conditions(array());
		$this->assertEqual($result, ' WHERE 1 = 1', 'array() conditions failed %s');

		$result = $this->testDb->conditions('');
		$this->assertEqual($result, ' WHERE 1 = 1', '"" conditions failed %s');

		$result = $this->testDb->conditions(' ', '"  " conditions failed %s');
		$this->assertEqual($result, ' WHERE 1 = 1');
	}
/**
 * testStringConditionsParsing method
 *
 * @access public
 * @return void
 */
	function testStringConditionsParsing() {
		$result = $this->testDb->conditions("ProjectBid.project_id = Project.id");
		$expected = " WHERE `ProjectBid`.`project_id` = `Project`.`id`";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("Candy.name LIKE 'a' AND HardCandy.name LIKE 'c'");
		$expected = " WHERE `Candy`.`name` LIKE 'a' AND `HardCandy`.`name` LIKE 'c'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("HardCandy.name LIKE 'a' AND Candy.name LIKE 'c'");
		$expected = " WHERE `HardCandy`.`name` LIKE 'a' AND `Candy`.`name` LIKE 'c'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("Post.title = '1.1'");
		$expected = " WHERE `Post`.`title` = '1.1'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("User.id != 0 AND User.user LIKE '%arr%'");
		$expected = " WHERE `User`.`id` != 0 AND `User`.`user` LIKE '%arr%'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("SUM(Post.comments_count) > 500");
		$expected = " WHERE SUM(`Post`.`comments_count`) > 500";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("(Post.created < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(Post.created), MONTH(Post.created)");
		$expected = " WHERE (`Post`.`created` < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(`Post`.`created`), MONTH(`Post`.`created`)";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("score BETWEEN 90.1 AND 95.7");
		$expected = " WHERE score BETWEEN 90.1 AND 95.7";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('score' => array(2=>1, 2, 10)));
		$expected = " WHERE score IN (1, 2, 10)";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("Aro.rght = Aro.lft + 1.1");
		$expected = " WHERE `Aro`.`rght` = `Aro`.`lft` + 1.1";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("(Post.created < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(Post.created), MONTH(Post.created)");
		$expected = " WHERE (`Post`.`created` < '" . date('Y-m-d H:i:s') . "') GROUP BY YEAR(`Post`.`created`), MONTH(`Post`.`created`)";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions('Sportstaette.sportstaette LIKE "%ru%" AND Sportstaette.sportstaettenart_id = 2');
		$expected = ' WHERE `Sportstaette`.`sportstaette` LIKE "%ru%" AND `Sportstaette`.`sportstaettenart_id` = 2';
		$this->assertPattern('/\s*WHERE\s+`Sportstaette`\.`sportstaette`\s+LIKE\s+"%ru%"\s+AND\s+`Sports/', $result);
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions('Sportstaette.sportstaettenart_id = 2 AND Sportstaette.sportstaette LIKE "%ru%"');
		$expected = ' WHERE `Sportstaette`.`sportstaettenart_id` = 2 AND `Sportstaette`.`sportstaette` LIKE "%ru%"';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions('SUM(Post.comments_count) > 500 AND NOT Post.title IS NULL AND NOT Post.extended_title IS NULL');
		$expected = ' WHERE SUM(`Post`.`comments_count`) > 500 AND NOT `Post`.`title` IS NULL AND NOT `Post`.`extended_title` IS NULL';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions('NOT Post.title IS NULL AND NOT Post.extended_title IS NULL AND SUM(Post.comments_count) > 500');
		$expected = ' WHERE NOT `Post`.`title` IS NULL AND NOT `Post`.`extended_title` IS NULL AND SUM(`Post`.`comments_count`) > 500';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions('NOT Post.extended_title IS NULL AND NOT Post.title IS NULL AND Post.title != "" AND SPOON(SUM(Post.comments_count) + 1.1) > 500');
		$expected = ' WHERE NOT `Post`.`extended_title` IS NULL AND NOT `Post`.`title` IS NULL AND `Post`.`title` != "" AND SPOON(SUM(`Post`.`comments_count`) + 1.1) > 500';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions('NOT Post.title_extended IS NULL AND NOT Post.title IS NULL AND Post.title_extended != Post.title');
		$expected = ' WHERE NOT `Post`.`title_extended` IS NULL AND NOT `Post`.`title` IS NULL AND `Post`.`title_extended` != `Post`.`title`';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("Comment.id = 'a'");
		$expected = " WHERE `Comment`.`id` = 'a'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("lower(Article.title) LIKE 'a%'");
		$expected = " WHERE lower(`Article`.`title`) LIKE 'a%'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions('((MATCH(Video.title) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 2) + (MATCH(Video.description) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 0.4) + (MATCH(Video.tags) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 1.5))');
		$expected = ' WHERE ((MATCH(`Video`.`title`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 2) + (MATCH(`Video`.`description`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 0.4) + (MATCH(`Video`.`tags`) AGAINST(\'My Search*\' IN BOOLEAN MODE) * 1.5))';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions('DATEDIFF(NOW(),Article.published) < 1 && Article.live=1');
		$expected = " WHERE DATEDIFF(NOW(),`Article`.`published`) < 1 && `Article`.`live`=1";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions('file = "index.html"');
		$expected = ' WHERE file = "index.html"';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions("file = 'index.html'");
		$expected = " WHERE file = 'index.html'";
		$this->assertEqual($result, $expected);

		$letter = $letter = 'd.a';
		$conditions = array('Company.name like ' => $letter . '%');
		$result = $this->testDb->conditions($conditions);
		$expected = " WHERE `Company`.`name` like 'd.a%'";
		$this->assertEqual($result, $expected);

		$conditions = array('Artist.name' => 'JUDY and MARY');
		$result = $this->testDb->conditions($conditions);
		$expected = " WHERE `Artist`.`name` = 'JUDY and MARY'";
		$this->assertEqual($result, $expected);

		$conditions = array('Artist.name' => 'JUDY AND MARY');
		$result = $this->testDb->conditions($conditions);
		$expected = " WHERE `Artist`.`name` = 'JUDY AND MARY'";
		$this->assertEqual($result, $expected);
	}

/**
 * testQuotesInStringConditions method
 *
 * @access public
 * @return void
 */
	function testQuotesInStringConditions() {
		$result = $this->testDb->conditions('Member.email = \'mariano@cricava.com\'');
		$expected = ' WHERE `Member`.`email` = \'mariano@cricava.com\'';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions('Member.email = "mariano@cricava.com"');
		$expected = ' WHERE `Member`.`email` = "mariano@cricava.com"';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions('Member.email = \'mariano@cricava.com\' AND Member.user LIKE \'mariano.iglesias%\'');
		$expected = ' WHERE `Member`.`email` = \'mariano@cricava.com\' AND `Member`.`user` LIKE \'mariano.iglesias%\'';
		$this->assertEqual($result, $expected);


		$result = $this->testDb->conditions('Member.email = "mariano@cricava.com" AND Member.user LIKE "mariano.iglesias%"');
		$expected = ' WHERE `Member`.`email` = "mariano@cricava.com" AND `Member`.`user` LIKE "mariano.iglesias%"';
		$this->assertEqual($result, $expected);
	}

/**
 * testParenthesisInStringConditions method
 *
 * @access public
 * @return void
 */
	function testParenthesisInStringConditions() {
		$result = $this->testDb->conditions('Member.name = \'(lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(lu\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \')lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\)lu\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'va(lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'va)lu\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\)lu\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'va(lu)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'va(lu)e\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)e\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'(mariano)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'(mariano)iglesias\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)iglesias\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'(mariano) iglesias\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\) iglesias\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'(mariano word) iglesias\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano word\) iglesias\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'(mariano.iglesias)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\)\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'Mariano Iglesias (mariano.iglesias)\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\)\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'Mariano Iglesias (mariano.iglesias) CakePHP\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\) CakePHP\'$/', $result);

		$result = $this->testDb->conditions('Member.name = \'(mariano.iglesias) CakePHP\'');
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\) CakePHP\'$/', $result);
	}

/**
 * testParenthesisInArrayConditions method
 *
 * @access public
 * @return void
 */
	function testParenthesisInArrayConditions() {
		$result = $this->testDb->conditions(array('Member.name' => '(lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(lu\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => ')lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\)lu\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => 'va(lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => 'va)lu'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\)lu\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => 'va(lu)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => 'va(lu)e'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'va\(lu\)e\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => '(mariano)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => '(mariano)iglesias'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\)iglesias\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => '(mariano) iglesias'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano\) iglesias\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => '(mariano word) iglesias'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano word\) iglesias\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => '(mariano.iglesias)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\)\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => 'Mariano Iglesias (mariano.iglesias)'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\)\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => 'Mariano Iglesias (mariano.iglesias) CakePHP'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'Mariano Iglesias \(mariano.iglesias\) CakePHP\'$/', $result);

		$result = $this->testDb->conditions(array('Member.name' => '(mariano.iglesias) CakePHP'));
		$this->assertPattern('/^\s+WHERE\s+`Member`.`name`\s+=\s+\'\(mariano.iglesias\) CakePHP\'$/', $result);
	}

/**
 * testArrayConditionsParsing method
 *
 * @access public
 * @return void
 */
	function testArrayConditionsParsing() {
		$result = $this->testDb->conditions(array('Stereo.type' => 'in dash speakers'));
		$this->assertPattern("/^\s+WHERE\s+`Stereo`.`type`\s+=\s+'in dash speakers'/", $result);

		$result = $this->testDb->conditions(array('Candy.name LIKE' => 'a', 'HardCandy.name LIKE' => 'c'));
		$this->assertPattern("/^\s+WHERE\s+`Candy`.`name` LIKE\s+'a'\s+AND\s+`HardCandy`.`name`\s+LIKE\s+'c'/", $result);

		$result = $this->testDb->conditions(array('HardCandy.name LIKE' => 'a', 'Candy.name LIKE' => 'c'));
		$expected = " WHERE `HardCandy`.`name` LIKE 'a' AND `Candy`.`name` LIKE 'c'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('HardCandy.name LIKE' => 'a%', 'Candy.name LIKE' => '%c%'));
		$expected = " WHERE `HardCandy`.`name` LIKE 'a%' AND `Candy`.`name` LIKE '%c%'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('HardCandy.name LIKE' => 'to be or%', 'Candy.name LIKE' => '%not to be%'));
		$expected = " WHERE `HardCandy`.`name` LIKE 'to be or%' AND `Candy`.`name` LIKE '%not to be%'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('score BETWEEN ? AND ?' => array(90.1, 95.7)));
		$expected = " WHERE `score` BETWEEN 90.1 AND 95.7";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('Post.title' => 1.1));
		$expected = " WHERE `Post`.`title` = 1.1";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('Post.title' => 1.1), true, true, new Post());
		$expected = " WHERE `Post`.`title` = '1.1'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('SUM(Post.comments_count) >' => '500'));
		$expected = " WHERE SUM(`Post`.`comments_count`) > '500'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('MAX(Post.rating) >' => '50'));
		$expected = " WHERE MAX(`Post`.`rating`) > '50'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('title LIKE' => '%hello'));
		$expected = " WHERE `title` LIKE '%hello'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('Post.name' => 'mad(g)ik'));
		$expected = " WHERE `Post`.`name` = 'mad(g)ik'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('score' => array(1, 2, 10)));
		$expected = " WHERE score IN (1, 2, 10)";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('score' => array()));
		$expected = " WHERE `score` IS NULL";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('score !=' => array()));
		$expected = " WHERE `score` IS NOT NULL";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('score !=' => '20'));
		$expected = " WHERE `score` != '20'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('score >' => '20'));
		$expected = " WHERE `score` > '20'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('client_id >' => '20'), true, true, new TestModel());
		$expected = " WHERE `client_id` > 20";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('OR' => array(
			array('User.user' => 'mariano'),
			array('User.user' => 'nate')
		)));

		$expected = " WHERE ((`User`.`user` = 'mariano') OR (`User`.`user` = 'nate'))";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('or' => array(
			'score BETWEEN ? AND ?' => array('4', '5'), 'rating >' => '20'
		)));
		$expected = " WHERE ((`score` BETWEEN '4' AND '5') OR (`rating` > '20'))";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('or' => array(
			'score BETWEEN ? AND ?' => array('4', '5'), array('score >' => '20')
		)));
		$expected = " WHERE ((`score` BETWEEN '4' AND '5') OR (`score` > '20'))";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('and' => array(
			'score BETWEEN ? AND ?' => array('4', '5'), array('score >' => '20')
		)));
		$expected = " WHERE ((`score` BETWEEN '4' AND '5') AND (`score` > '20'))";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array(
			'published' => 1, 'or' => array('score >' => '2', array('score >' => '20'))
		));
		$expected = " WHERE `published` = 1 AND ((`score` > '2') OR (`score` > '20'))";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array(array('Project.removed' => false)));
		$expected = " WHERE `Project`.`removed` = 0";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array(array('Project.removed' => true)));
		$expected = " WHERE `Project`.`removed` = 1";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array(array('Project.removed' => null)));
		$expected = " WHERE `Project`.`removed` IS NULL";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array(array('Project.removed !=' => null)));
		$expected = " WHERE `Project`.`removed` IS NOT NULL";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('(Usergroup.permissions) & 4' => 4));
		$expected = " WHERE (`Usergroup`.`permissions`) & 4 = 4";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('((Usergroup.permissions) & 4)' => 4));
		$expected = " WHERE ((`Usergroup`.`permissions`) & 4) = 4";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('Post.modified >=' => 'DATE_SUB(NOW(), INTERVAL 7 DAY)'));
		$expected = " WHERE `Post`.`modified` >= 'DATE_SUB(NOW(), INTERVAL 7 DAY)'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('Post.modified >= DATE_SUB(NOW(), INTERVAL 7 DAY)'));
		$expected = " WHERE `Post`.`modified` >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array(
			'NOT' => array('Course.id' => null, 'Course.vet' => 'N', 'level_of_education_id' => array(912,999)),
			'Enrollment.yearcompleted >' => '0')
		);
		$this->assertPattern('/^\s*WHERE\s+\(NOT\s+\(`Course`\.`id` IS NULL\)\s+AND NOT\s+\(`Course`\.`vet`\s+=\s+\'N\'\)\s+AND NOT\s+\(level_of_education_id IN \(912, 999\)\)\)\s+AND\s+`Enrollment`\.`yearcompleted`\s+>\s+\'0\'\s*$/', $result);

		$result = $this->testDb->conditions(array('id <>' => '8'));
		$this->assertPattern('/^\s*WHERE\s+`id`\s+<>\s+\'8\'\s*$/', $result);

		$result = $this->testDb->conditions(array('TestModel.field =' => 'gribe$@()lu'));
		$expected = " WHERE `TestModel`.`field` = 'gribe$@()lu'";
		$this->assertEqual($result, $expected);

		$conditions['NOT'] = array('Listing.expiration BETWEEN ? AND ?' => array("1", "100"));
		$conditions[0]['OR'] = array(
			"Listing.title LIKE" => "%term%",
			"Listing.description LIKE" => "%term%"
		);
		$conditions[1]['OR'] = array(
			"Listing.title LIKE" => "%term_2%",
			"Listing.description LIKE" => "%term_2%"
		);
		$result = $this->testDb->conditions($conditions);
		$expected = " WHERE NOT (`Listing`.`expiration` BETWEEN '1' AND '100') AND" .
		" ((`Listing`.`title` LIKE '%term%') OR (`Listing`.`description` LIKE '%term%')) AND" .
		" ((`Listing`.`title` LIKE '%term_2%') OR (`Listing`.`description` LIKE '%term_2%'))";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('MD5(CONCAT(Reg.email,Reg.id))' => 'blah'));
		$expected = " WHERE MD5(CONCAT(`Reg`.`email`,`Reg`.`id`)) = 'blah'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array(
			'MD5(CONCAT(Reg.email,Reg.id))' => array('blah', 'blahblah')
		));
		$expected = " WHERE MD5(CONCAT(`Reg`.`email`,`Reg`.`id`)) IN ('blah', 'blahblah')";
		$this->assertEqual($result, $expected);

		$conditions = array('id' => array(2, 5, 6, 9, 12, 45, 78, 43, 76));
		$result = $this->testDb->conditions($conditions);
		$expected = " WHERE id IN (2, 5, 6, 9, 12, 45, 78, 43, 76)";
		$this->assertEqual($result, $expected);

		$conditions = array('title' => 'user(s)');
		$result = $this->testDb->conditions($conditions);
		$expected = " WHERE `title` = 'user(s)'";
		$this->assertEqual($result, $expected);

		$conditions = array('title' => 'user(s) data');
		$result = $this->testDb->conditions($conditions);
		$expected = " WHERE `title` = 'user(s) data'";
		$this->assertEqual($result, $expected);

		$conditions = array('title' => 'user(s,arg) data');
		$result = $this->testDb->conditions($conditions);
		$expected = " WHERE `title` = 'user(s,arg) data'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array("Book.book_name" => 'Java(TM)'));
		$expected = " WHERE `Book`.`book_name` = 'Java(TM)'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array("Book.book_name" => 'Java(TM) '));
		$expected = " WHERE `Book`.`book_name` = 'Java(TM) '";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array("Book.id" => 0));
		$expected = " WHERE `Book`.`id` = 0";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array("Book.id" => NULL));
		$expected = " WHERE `Book`.`id` IS NULL";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('Listing.beds >=' => 0));
		$expected = " WHERE `Listing`.`beds` >= 0";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array(
			'ASCII(SUBSTRING(keyword, 1, 1)) BETWEEN ? AND ?' => array(65, 90)
		));
		$expected = ' WHERE ASCII(SUBSTRING(keyword, 1, 1)) BETWEEN 65 AND 90';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('or' => array(
			'? BETWEEN Model.field1 AND Model.field2' => '2009-03-04'
		)));
		$expected = " WHERE '2009-03-04' BETWEEN Model.field1 AND Model.field2";
		$this->assertEqual($result, $expected);
	}

/**
 * testArrayConditionsParsingComplexKeys method
 *
 * @access public
 * @return void
 */
	function testArrayConditionsParsingComplexKeys() {
		$result = $this->testDb->conditions(array(
			'CAST(Book.created AS DATE)' => '2008-08-02'
		));
		$expected = " WHERE CAST(`Book`.`created` AS DATE) = '2008-08-02'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array(
			'CAST(Book.created AS DATE) <=' => '2008-08-02'
		));
		$expected = " WHERE CAST(`Book`.`created` AS DATE) <= '2008-08-02'";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array(
			'(Stats.clicks * 100) / Stats.views >' => 50
		));
		$expected = " WHERE (`Stats`.`clicks` * 100) / `Stats`.`views` > 50";
		$this->assertEqual($result, $expected);
	}

/**
 * testMixedConditionsParsing method
 *
 * @access public
 * @return void
 */
	function testMixedConditionsParsing() {
		$conditions[] = 'User.first_name = \'Firstname\'';
		$conditions[] = array('User.last_name' => 'Lastname');
		$result = $this->testDb->conditions($conditions);
		$expected = " WHERE `User`.`first_name` = 'Firstname' AND `User`.`last_name` = 'Lastname'";
		$this->assertEqual($result, $expected);

		$conditions = array(
			'Thread.project_id' => 5,
			'Thread.buyer_id' => 14,
			'1=1 GROUP BY Thread.project_id'
		);
		$result = $this->testDb->conditions($conditions);
		$this->assertPattern('/^\s*WHERE\s+`Thread`.`project_id`\s*=\s*5\s+AND\s+`Thread`.`buyer_id`\s*=\s*14\s+AND\s+1\s*=\s*1\s+GROUP BY `Thread`.`project_id`$/', $result);
	}

/**
 * testConditionsOptionalArguments method
 *
 * @access public
 * @return void
 */
	function testConditionsOptionalArguments() {
		$result = $this->testDb->conditions( array('Member.name' => 'Mariano'), true, false);
		$this->assertPattern('/^\s*`Member`.`name`\s*=\s*\'Mariano\'\s*$/', $result);

		$result = $this->testDb->conditions( array(), true, false);
		$this->assertPattern('/^\s*1\s*=\s*1\s*$/', $result);
	}

/**
 * testConditionsWithModel
 *
 * @access public
 * @return void
 */
	function testConditionsWithModel() {
		$this->Model =& new Article2();

		$result = $this->testDb->conditions(array('Article2.viewed >=' => 0), true, true, $this->Model);
		$expected = " WHERE `Article2`.`viewed` >= 0";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('Article2.viewed >=' => '0'), true, true, $this->Model);
		$expected = " WHERE `Article2`.`viewed` >= 0";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('Article2.viewed >=' => '1'), true, true, $this->Model);
		$expected = " WHERE `Article2`.`viewed` >= 1";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('Article2.rate_sum BETWEEN ? AND ?' => array(0, 10)), true, true, $this->Model);
		$expected = " WHERE `Article2`.`rate_sum` BETWEEN 0 AND 10";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('Article2.rate_sum BETWEEN ? AND ?' => array('0', '10')), true, true, $this->Model);
		$expected = " WHERE `Article2`.`rate_sum` BETWEEN 0 AND 10";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->conditions(array('Article2.rate_sum BETWEEN ? AND ?' => array('1', '10')), true, true, $this->Model);
		$expected = " WHERE `Article2`.`rate_sum` BETWEEN 1 AND 10";
		$this->assertEqual($result, $expected);
	}

/**
 * testFieldParsing method
 *
 * @access public
 * @return void
 */
	function testFieldParsing() {
		$result = $this->testDb->fields($this->Model, 'Vendor', "Vendor.id, COUNT(Model.vendor_id) AS `Vendor`.`count`");
		$expected = array('`Vendor`.`id`', 'COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, 'Vendor', "`Vendor`.`id`, COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`");
		$expected = array('`Vendor`.`id`', 'COUNT(`Model`.`vendor_id`) AS `Vendor`.`count`');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, 'Post', "CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name, Node.created");
		$expected = array("CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name", "`Node`.`created`");
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, 'round( (3.55441 * fooField), 3 ) AS test');
		$this->assertEqual($result, array('round( (3.55441 * fooField), 3 ) AS test'));

		$result = $this->testDb->fields($this->Model, null, 'ROUND(`Rating`.`rate_total` / `Rating`.`rate_count`,2) AS rating');
		$this->assertEqual($result, array('ROUND(`Rating`.`rate_total` / `Rating`.`rate_count`,2) AS rating'));

		$result = $this->testDb->fields($this->Model, null, 'ROUND(Rating.rate_total / Rating.rate_count,2) AS rating');
		$this->assertEqual($result, array('ROUND(Rating.rate_total / Rating.rate_count,2) AS rating'));

		$result = $this->testDb->fields($this->Model, 'Post', "Node.created, CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name");
		$expected = array("`Node`.`created`", "CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name");
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, 'Post', "2.2,COUNT(*), SUM(Something.else) as sum, Node.created, CONCAT(REPEAT(' ', COUNT(Parent.name) - 1), Node.name) AS name,Post.title,Post.1,1.1");
		$expected = array(
			'2.2', 'COUNT(*)', 'SUM(`Something`.`else`) as sum', '`Node`.`created`',
			"CONCAT(REPEAT(' ', COUNT(`Parent`.`name`) - 1), Node.name) AS name", '`Post`.`title`', '`Post`.`1`', '1.1'
		);
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, "(`Provider`.`star_total` / `Provider`.`total_ratings`) as `rating`");
		$expected = array("(`Provider`.`star_total` / `Provider`.`total_ratings`) as `rating`");
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, 'Post');
		$expected = array(
			'`Post`.`id`', '`Post`.`client_id`', '`Post`.`name`', '`Post`.`login`',
			'`Post`.`passwd`', '`Post`.`addr_1`', '`Post`.`addr_2`', '`Post`.`zip_code`',
			'`Post`.`city`', '`Post`.`country`', '`Post`.`phone`', '`Post`.`fax`',
			'`Post`.`url`', '`Post`.`email`', '`Post`.`comments`', '`Post`.`last_login`',
			'`Post`.`created`', '`Post`.`updated`'
		);
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, 'Other');
		$expected = array(
			'`Other`.`id`', '`Other`.`client_id`', '`Other`.`name`', '`Other`.`login`',
			'`Other`.`passwd`', '`Other`.`addr_1`', '`Other`.`addr_2`', '`Other`.`zip_code`',
			'`Other`.`city`', '`Other`.`country`', '`Other`.`phone`', '`Other`.`fax`',
			'`Other`.`url`', '`Other`.`email`', '`Other`.`comments`', '`Other`.`last_login`',
			'`Other`.`created`', '`Other`.`updated`'
		);
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, array(), false);
		$expected = array('id', 'client_id', 'name', 'login', 'passwd', 'addr_1', 'addr_2', 'zip_code', 'city', 'country', 'phone', 'fax', 'url', 'email', 'comments', 'last_login', 'created', 'updated');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, 'COUNT(*)');
		$expected = array('COUNT(*)');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, 'SUM(Thread.unread_buyer) AS ' . $this->testDb->name('sum_unread_buyer'));
		$expected = array('SUM(`Thread`.`unread_buyer`) AS `sum_unread_buyer`');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, 'name, count(*)');
		$expected = array('`TestModel`.`name`', 'count(*)');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, 'count(*), name');
		$expected = array('count(*)', '`TestModel`.`name`');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields(
			$this->Model, null, 'field1, field2, field3, count(*), name'
		);
		$expected = array(
			'`TestModel`.`field1`', '`TestModel`.`field2`',
			'`TestModel`.`field3`', 'count(*)', '`TestModel`.`name`'
		);
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, array('dayofyear(now())'));
		$expected = array('dayofyear(now())');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, array('MAX(Model.field) As Max'));
		$expected = array('MAX(`Model`.`field`) As Max');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, array('Model.field AS AnotherName'));
		$expected = array('`Model`.`field` AS `AnotherName`');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, array('field AS AnotherName'));
		$expected = array('`field` AS `AnotherName`');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, null, array(
			'TestModel.field AS AnotherName'
		));
		$expected = array('`TestModel`.`field` AS `AnotherName`');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->fields($this->Model, 'Foo', array(
			'id', 'title', '(user_count + discussion_count + post_count) AS score'
		));
		$expected = array(
			'`Foo`.`id`',
			'`Foo`.`title`',
			'(user_count + discussion_count + post_count) AS score'
		);
		$this->assertEqual($result, $expected);
	}

/**
 * test that fields() will accept objects made from DboSource::expression
 *
 * @return void
 */
	function testFieldsWithExpression() {
		$expression = $this->testDb->expression("CASE Sample.id WHEN 1 THEN 'Id One' ELSE 'Other Id' END AS case_col");
		$result = $this->testDb->fields($this->Model, null, array("id", $expression));
		$expected = array(
			'`TestModel`.`id`',
			"CASE Sample.id WHEN 1 THEN 'Id One' ELSE 'Other Id' END AS case_col"
		);
		$this->assertEqual($result, $expected);
	}
/**
 * testMergeAssociations method
 *
 * @access public
 * @return void
 */
	function testMergeAssociations() {
		$data = array('Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
		));
		$merge = array('Topic' => array(array(
			'id' => '1', 'topic' => 'Topic', 'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		)));
		$expected = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Topic' => array(
				'id' => '1', 'topic' => 'Topic', 'created' => '2007-03-17 01:16:23',
				'updated' => '2007-03-17 01:18:31'
			)
		);
		$this->testDb->__mergeAssociation($data, $merge, 'Topic', 'hasOne');
		$this->assertEqual($data, $expected);

		$data = array('Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
		));
		$merge = array('User2' => array(array(
			'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
			'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
		)));

		$expected = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'User2' => array(
				'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
			)
		);
		$this->testDb->__mergeAssociation($data, $merge, 'User2', 'belongsTo');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(array('Comment' => false));
		$expected = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Comment' => array()
		);
		$this->testDb->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
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
		$this->testDb->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
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
		$this->testDb->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
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
		$this->testDb->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
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
		$this->testDb->__mergeAssociation($data, $merge, 'Tag', 'hasAndBelongsToMany');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
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
			'Tag' => array('id' => '1', 'tag' => 'Tag 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')
		);
		$this->testDb->__mergeAssociation($data, $merge, 'Tag', 'hasOne');
		$this->assertEqual($data, $expected);
	}

/**
 * testRenderStatement method
 *
 * @access public
 * @return void
 */
	function testRenderStatement() {
		$result = $this->testDb->renderStatement('select', array(
			'fields' => 'id', 'table' => 'table', 'conditions' => 'WHERE 1=1',
			'alias' => '', 'joins' => '', 'order' => '', 'limit' => '', 'group' => ''
		));
		$this->assertPattern('/^\s*SELECT\s+id\s+FROM\s+table\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->testDb->renderStatement('update', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => ''));
		$this->assertPattern('/^\s*UPDATE\s+table\s+SET\s+value=2\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->testDb->renderStatement('update', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => 'alias', 'joins' => ''));
		$this->assertPattern('/^\s*UPDATE\s+table\s+AS\s+alias\s+SET\s+value=2\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->testDb->renderStatement('delete', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => ''));
		$this->assertPattern('/^\s*DELETE\s+FROM\s+table\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->testDb->renderStatement('delete', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => 'alias', 'joins' => ''));
		$this->assertPattern('/^\s*DELETE\s+alias\s+FROM\s+table\s+AS\s+alias\s+WHERE\s+1=1\s*$/', $result);
	}

/**
 * testStatements method
 *
 * @access public
 * @return void
 */
	function testStatements() {
		$Article =& ClassRegistry::init('Article');

		$result = $this->testDb->update($Article, array('field1'), array('value1'));
		$this->assertFalse($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*UPDATE\s+' . $this->testDb->fullTableName('articles') . '\s+SET\s+`field1`\s*=\s*\'value1\'\s+WHERE\s+1 = 1\s*$/', $result);

		$result = $this->testDb->update($Article, array('field1'), array('2'), '2=2');
		$this->assertFalse($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*UPDATE\s+' . $this->testDb->fullTableName('articles') . ' AS `Article`\s+LEFT JOIN\s+' . $this->testDb->fullTableName('users') . ' AS `User` ON \(`Article`.`user_id` = `User`.`id`\)\s+SET\s+`Article`\.`field1`\s*=\s*2\s+WHERE\s+2\s*=\s*2\s*$/', $result);

		$result = $this->testDb->delete($Article);
		$this->assertTrue($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*DELETE\s+FROM\s+' . $this->testDb->fullTableName('articles') . '\s+WHERE\s+1 = 1\s*$/', $result);

		$result = $this->testDb->delete($Article, true);
		$this->assertTrue($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*DELETE\s+`Article`\s+FROM\s+' . $this->testDb->fullTableName('articles') . '\s+AS `Article`\s+LEFT JOIN\s+' . $this->testDb->fullTableName('users') . ' AS `User` ON \(`Article`.`user_id` = `User`.`id`\)\s+WHERE\s+1\s*=\s*1\s*$/', $result);

		$result = $this->testDb->delete($Article, '2=2');
		$this->assertTrue($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*DELETE\s+`Article`\s+FROM\s+' . $this->testDb->fullTableName('articles') . '\s+AS `Article`\s+LEFT JOIN\s+' . $this->testDb->fullTableName('users') . ' AS `User` ON \(`Article`.`user_id` = `User`.`id`\)\s+WHERE\s+2\s*=\s*2\s*$/', $result);

		$result = $this->testDb->hasAny($Article, '1=2');
		$this->assertFalse($result);

		$result = $this->testDb->insertMulti('articles', array('field'), array('(1)', '(2)'));
		$this->assertFalse($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*INSERT INTO\s+' . $this->testDb->fullTableName('articles') . '\s+\(`field`\)\s+VALUES\s+\(1\),\s*\(2\)\s*$/', $result);
	}

/**
 * testSchema method
 *
 * @access public
 * @return void
 */
	function testSchema() {
		$Schema =& new CakeSchema();
		$Schema->tables = array('table' => array(), 'anotherTable' => array());

		$this->expectError();
		$result = $this->testDb->dropSchema(null);
		$this->assertTrue($result === null);

		$result = $this->testDb->dropSchema($Schema, 'non_existing');
		$this->assertTrue(empty($result));

		$result = $this->testDb->dropSchema($Schema, 'table');
		$this->assertPattern('/^\s*DROP TABLE IF EXISTS\s+' . $this->testDb->fullTableName('table') . ';\s*$/s', $result);
	}

/**
 * testMagicMethodQuerying method
 *
 * @access public
 * @return void
 */
	function testMagicMethodQuerying() {
		$result = $this->testDb->query('findByFieldName', array('value'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => 'value'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findByFindBy', array('value'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.find_by' => 'value'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findAllByFieldName', array('value'), $this->Model);
		$expected = array('all', array(
			'conditions' => array('TestModel.field_name' => 'value'),
			'fields' => null, 'order' => null, 'limit' => null,
			'page' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findAllById', array('a'), $this->Model);
		$expected = array('all', array(
			'conditions' => array('TestModel.id' => 'a'),
			'fields' => null, 'order' => null, 'limit' => null,
			'page' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findByFieldName', array(array('value1', 'value2', 'value3')), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => array('value1', 'value2', 'value3')),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findByFieldName', array(null), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => null),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findByFieldName', array('= a'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => '= a'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findByFieldName', array(), $this->Model);
		$expected = false;
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('directCall', array(), $this->Model);
		$this->assertFalse($result);

		$result = $this->testDb->query('directCall', true, $this->Model);
		$this->assertFalse($result);

		$result = $this->testDb->query('directCall', false, $this->Model);
		$this->assertFalse($result);
	}

/**
 * testOrderParsing method
 *
 * @access public
 * @return void
 */
	function testOrderParsing() {
		$result = $this->testDb->order("ADDTIME(Event.time_begin, '-06:00:00') ASC");
		$expected = " ORDER BY ADDTIME(`Event`.`time_begin`, '-06:00:00') ASC";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->order("title, id");
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->testDb->order("title desc, id desc");
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+desc,\s+`id`\s+desc\s*$/', $result);

		$result = $this->testDb->order(array("title desc, id desc"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+desc,\s+`id`\s+desc\s*$/', $result);

		$result = $this->testDb->order(array("title", "id"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->testDb->order(array(array('title'), array('id')));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->testDb->order(array("Post.title" => 'asc', "Post.id" => 'desc'));
		$this->assertPattern('/^\s*ORDER BY\s+`Post`.`title`\s+asc,\s+`Post`.`id`\s+desc\s*$/', $result);

		$result = $this->testDb->order(array(array("Post.title" => 'asc', "Post.id" => 'desc')));
		$this->assertPattern('/^\s*ORDER BY\s+`Post`.`title`\s+asc,\s+`Post`.`id`\s+desc\s*$/', $result);

		$result = $this->testDb->order(array("title"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC\s*$/', $result);

		$result = $this->testDb->order(array(array("title")));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC\s*$/', $result);

		$result = $this->testDb->order("Dealer.id = 7 desc, Dealer.id = 3 desc, Dealer.title asc");
		$expected = " ORDER BY `Dealer`.`id` = 7 desc, `Dealer`.`id` = 3 desc, `Dealer`.`title` asc";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->order(array("Page.name" => "='test' DESC"));
		$this->assertPattern("/^\s*ORDER BY\s+`Page`\.`name`\s*='test'\s+DESC\s*$/", $result);

		$result = $this->testDb->order("Page.name = 'view' DESC");
		$this->assertPattern("/^\s*ORDER BY\s+`Page`\.`name`\s*=\s*'view'\s+DESC\s*$/", $result);

		$result = $this->testDb->order("(Post.views)");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Post`\.`views`\)\s+ASC\s*$/", $result);

		$result = $this->testDb->order("(Post.views)*Post.views");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Post`\.`views`\)\*`Post`\.`views`\s+ASC\s*$/", $result);

		$result = $this->testDb->order("(Post.views) * Post.views");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Post`\.`views`\) \* `Post`\.`views`\s+ASC\s*$/", $result);

		$result = $this->testDb->order("(Model.field1 + Model.field2) * Model.field3");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Model`\.`field1` \+ `Model`\.`field2`\) \* `Model`\.`field3`\s+ASC\s*$/", $result);

		$result = $this->testDb->order("Model.name+0 ASC");
		$this->assertPattern("/^\s*ORDER BY\s+`Model`\.`name`\+0\s+ASC\s*$/", $result);

		$result = $this->testDb->order("Anuncio.destaque & 2 DESC");
		$expected = ' ORDER BY `Anuncio`.`destaque` & 2 DESC';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->order("3963.191 * id");
		$expected = ' ORDER BY 3963.191 * id ASC';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->order(array('Property.sale_price IS NULL'));
		$expected = ' ORDER BY `Property`.`sale_price` IS NULL ASC';
		$this->assertEqual($result, $expected);
	}

/**
 * testComplexSortExpression method
 *
 * @return void
 * @access public
 */
	function testComplexSortExpression() {
		$result = $this->testDb->order(array('(Model.field > 100) DESC', 'Model.field ASC'));
		$this->assertPattern("/^\s*ORDER BY\s+\(`Model`\.`field`\s+>\s+100\)\s+DESC,\s+`Model`\.`field`\s+ASC\s*$/", $result);
	}

/**
 * testCalculations method
 *
 * @access public
 * @return void
 */
	function testCalculations() {
		$result = $this->testDb->calculate($this->Model, 'count');
		$this->assertEqual($result, 'COUNT(*) AS `count`');

		$result = $this->testDb->calculate($this->Model, 'count', array('id'));
		$this->assertEqual($result, 'COUNT(`id`) AS `count`');

		$result = $this->testDb->calculate(
			$this->Model,
			'count',
			array($this->testDb->expression('DISTINCT id'))
		);
		$this->assertEqual($result, 'COUNT(DISTINCT id) AS `count`');

		$result = $this->testDb->calculate($this->Model, 'count', array('id', 'id_count'));
		$this->assertEqual($result, 'COUNT(`id`) AS `id_count`');

		$result = $this->testDb->calculate($this->Model, 'count', array('Model.id', 'id_count'));
		$this->assertEqual($result, 'COUNT(`Model`.`id`) AS `id_count`');

		$result = $this->testDb->calculate($this->Model, 'max', array('id'));
		$this->assertEqual($result, 'MAX(`id`) AS `id`');

		$result = $this->testDb->calculate($this->Model, 'max', array('Model.id', 'id'));
		$this->assertEqual($result, 'MAX(`Model`.`id`) AS `id`');

		$result = $this->testDb->calculate($this->Model, 'max', array('`Model`.`id`', 'id'));
		$this->assertEqual($result, 'MAX(`Model`.`id`) AS `id`');

		$result = $this->testDb->calculate($this->Model, 'min', array('`Model`.`id`', 'id'));
		$this->assertEqual($result, 'MIN(`Model`.`id`) AS `id`');

		$result = $this->testDb->calculate($this->Model, 'min', 'left');
		$this->assertEqual($result, 'MIN(`left`) AS `left`');
	}

/**
 * testLength method
 *
 * @access public
 * @return void
 */
	function testLength() {
		$result = $this->testDb->length('varchar(255)');
		$expected = 255;
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length('int(11)');
		$expected = 11;
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length('float(5,3)');
		$expected = '5,3';
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length('decimal(5,2)');
		$expected = '5,2';
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length("enum('test','me','now')");
		$expected = 4;
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length("set('a','b','cd')");
		$expected = 2;
		$this->assertIdentical($result, $expected);

		$this->expectError();
		$result = $this->testDb->length(false);
		$this->assertTrue($result === null);

		$result = $this->testDb->length('datetime');
		$expected = null;
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length('text');
		$expected = null;
		$this->assertIdentical($result, $expected);
	}

/**
 * testBuildIndex method
 *
 * @access public
 * @return void
 */
	function testBuildIndex() {
		$data = array(
			'PRIMARY' => array('column' => 'id')
		);
		$result = $this->testDb->buildIndex($data);
		$expected = array('PRIMARY KEY  (`id`)');
		$this->assertIdentical($result, $expected);

		$data = array(
			'MyIndex' => array('column' => 'id', 'unique' => true)
		);
		$result = $this->testDb->buildIndex($data);
		$expected = array('UNIQUE KEY `MyIndex` (`id`)');
		$this->assertEqual($result, $expected);

		$data = array(
			'MyIndex' => array('column' => array('id', 'name'), 'unique' => true)
		);
		$result = $this->testDb->buildIndex($data);
		$expected = array('UNIQUE KEY `MyIndex` (`id`, `name`)');
		$this->assertEqual($result, $expected);
	}

/**
 * testBuildColumn method
 *
 * @access public
 * @return void
 */
	function testBuildColumn() {
		$this->expectError();
		$data = array(
			'name' => 'testName',
			'type' => 'varchar(255)',
			'default',
			'null' => true,
			'key'
		);
		$this->testDb->buildColumn($data);

		$data = array(
			'name' => 'testName',
			'type' => 'string',
			'length' => 255,
			'default',
			'null' => true,
			'key'
		);
		$result = $this->testDb->buildColumn($data);
		$expected = '`testName` varchar(255) DEFAULT NULL';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'default' => '',
			'null' => false,
		);
		$restore = $this->testDb->columns;

		$this->testDb->columns = array('integer' => array('name' => 'int', 'limit' => '11', 'formatter' => 'intval'), );
		$result = $this->testDb->buildColumn($data);
		$expected = '`int_field` int(11) NOT NULL';
		$this->assertEqual($result, $expected);

		$this->testDb->fieldParameters['param'] = array(
			'value' => 'COLLATE',
			'quote' => false,
			'join' => ' ',
			'column' => 'Collate',
			'position' => 'beforeDefault',
			'options' => array('GOOD', 'OK')
		);
		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'default' => '',
			'null' => false,
			'param' => 'BAD'
		);
		$result = $this->testDb->buildColumn($data);
		$expected = '`int_field` int(11) NOT NULL';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'default' => '',
			'null' => false,
			'param' => 'GOOD'
		);
		$result = $this->testDb->buildColumn($data);
		$expected = '`int_field` int(11) COLLATE GOOD NOT NULL';
		$this->assertEqual($result, $expected);

		$this->testDb->columns = $restore;

		$data = array(
			'name' => 'created',
			'type' => 'timestamp',
			'default' => 'current_timestamp',
			'null' => false,
 		);
		$result = $this->db->buildColumn($data);
		$expected = '`created` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'created',
			'type' => 'timestamp',
			'default' => 'CURRENT_TIMESTAMP',
			'null' => true,
		);
		$result = $this->db->buildColumn($data);
		$expected = '`created` timestamp DEFAULT CURRENT_TIMESTAMP';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'modified',
			'type' => 'timestamp',
			'null' => true,
		);
		$result = $this->db->buildColumn($data);
		$expected = '`modified` timestamp NULL';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'modified',
			'type' => 'timestamp',
			'default' => null,
			'null' => true,
		);
		$result = $this->db->buildColumn($data);
		$expected = '`modified` timestamp NULL';
		$this->assertEqual($result, $expected);
	}

/**
 * test hasAny()
 *
 * @return void
 */
	function testHasAny() {
		$this->testDb->hasAny($this->Model, array());
		$expected = 'SELECT COUNT(`TestModel`.`id`) AS count FROM `test_models` AS `TestModel` WHERE 1 = 1';
		$this->assertEqual(end($this->testDb->simulated), $expected);

		$this->testDb->hasAny($this->Model, array('TestModel.name' => 'harry'));
		$expected = "SELECT COUNT(`TestModel`.`id`) AS count FROM `test_models` AS `TestModel` WHERE `TestModel`.`name` = 'harry'";
		$this->assertEqual(end($this->testDb->simulated), $expected);
	}

/**
 * testIntrospectType method
 *
 * @access public
 * @return void
 */
	function testIntrospectType() {
		$this->assertEqual($this->testDb->introspectType(0), 'integer');
		$this->assertEqual($this->testDb->introspectType(2), 'integer');
		$this->assertEqual($this->testDb->introspectType('2'), 'string');
		$this->assertEqual($this->testDb->introspectType('2.2'), 'string');
		$this->assertEqual($this->testDb->introspectType(2.2), 'float');
		$this->assertEqual($this->testDb->introspectType('stringme'), 'string');
		$this->assertEqual($this->testDb->introspectType('0stringme'), 'string');

		$data = array(2.2);
		$this->assertEqual($this->testDb->introspectType($data), 'float');

		$data = array('2.2');
		$this->assertEqual($this->testDb->introspectType($data), 'float');

		$data = array(2);
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array('2');
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array('string');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array(2.2, '2.2');
		$this->assertEqual($this->testDb->introspectType($data), 'float');

		$data = array(2, '2');
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array('string one', 'string two');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array('2.2', 3);
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array('2.2', '0stringme');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array(2.2, 3);
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array(2.2, '0stringme');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array(2, 'stringme');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array(2, '2.2', 'stringgme');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array(2, '2.2');
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array(2, 2.2);
		$this->assertEqual($this->testDb->introspectType($data), 'integer');


		// NULL
		$result = $this->testDb->value(null, 'boolean');
		$this->assertEqual($result, 'NULL');

		// EMPTY STRING
		$result = $this->testDb->value('', 'boolean');
		$this->assertEqual($result, "NULL");


		// BOOLEAN
		$result = $this->testDb->value('true', 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value('false', 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value(true, 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value(false, 'boolean');
		$this->assertEqual($result, 0);

		$result = $this->testDb->value(1, 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value(0, 'boolean');
		$this->assertEqual($result, 0);

		$result = $this->testDb->value('abc', 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value(1.234, 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value('1.234e05', 'boolean');
		$this->assertEqual($result, 1);

		// NUMBERS
		$result = $this->testDb->value(123, 'integer');
		$this->assertEqual($result, 123);

		$result = $this->testDb->value('123', 'integer');
		$this->assertEqual($result, '123');

		$result = $this->testDb->value('0123', 'integer');
		$this->assertEqual($result, "'0123'");

		$result = $this->testDb->value('0x123ABC', 'integer');
		$this->assertEqual($result, "'0x123ABC'");

		$result = $this->testDb->value('0x123', 'integer');
		$this->assertEqual($result, "'0x123'");

		$result = $this->testDb->value(1.234, 'float');
		$this->assertEqual($result, 1.234);

		$result = $this->testDb->value('1.234', 'float');
		$this->assertEqual($result, '1.234');

		$result = $this->testDb->value(' 1.234 ', 'float');
		$this->assertEqual($result, "' 1.234 '");

		$result = $this->testDb->value('1.234e05', 'float');
		$this->assertEqual($result, "'1.234e05'");

		$result = $this->testDb->value('1.234e+5', 'float');
		$this->assertEqual($result, "'1.234e+5'");

		$result = $this->testDb->value('1,234', 'float');
		$this->assertEqual($result, "'1,234'");

		$result = $this->testDb->value('FFF', 'integer');
		$this->assertEqual($result, "'FFF'");

		$result = $this->testDb->value('abc', 'integer');
		$this->assertEqual($result, "'abc'");

		// STRINGS
		$result = $this->testDb->value('123', 'string');
		$this->assertEqual($result, "'123'");

		$result = $this->testDb->value(123, 'string');
		$this->assertEqual($result, "'123'");

		$result = $this->testDb->value(1.234, 'string');
		$this->assertEqual($result, "'1.234'");

		$result = $this->testDb->value('abc', 'string');
		$this->assertEqual($result, "'abc'");

		$result = $this->testDb->value(' abc ', 'string');
		$this->assertEqual($result, "' abc '");

		$result = $this->testDb->value('a bc', 'string');
		$this->assertEqual($result, "'a bc'");
	}

/**
 * testValue method
 *
 * @access public
 * @return void
 */
	function testValue() {
		$result = $this->testDb->value('{$__cakeForeignKey__$}');
		$this->assertEqual($result, '{$__cakeForeignKey__$}');

		$result = $this->testDb->value(array('first', 2, 'third'));
		$expected = array('\'first\'', 2, '\'third\'');
		$this->assertEqual($result, $expected);
	}

/**
 * testReconnect method
 *
 * @access public
 * @return void
 */
	function testReconnect() {
		$this->testDb->reconnect(array('prefix' => 'foo'));
		$this->assertTrue($this->testDb->connected);
		$this->assertEqual($this->testDb->config['prefix'], 'foo');
	}

/**
 * testRealQueries method
 *
 * @access public
 * @return void
 */
	function testRealQueries() {
		$this->loadFixtures('Apple', 'Article', 'User', 'Comment', 'Tag');

		$Apple =& ClassRegistry::init('Apple');
		$Article =& ClassRegistry::init('Article');

		$result = $this->db->rawQuery('SELECT color, name FROM ' . $this->db->fullTableName('apples'));
		$this->assertTrue(!empty($result));

		$result = $this->db->fetchRow($result);
		$expected = array($this->db->fullTableName('apples', false) => array(
			'color' => 'Red 1',
			'name' => 'Red Apple 1'
		));
		$this->assertEqual($result, $expected);

		$result = $this->db->fetchAll('SELECT name FROM ' . $this->testDb->fullTableName('apples') . ' ORDER BY id');
		$expected = array(
			array($this->db->fullTableName('apples', false) => array('name' => 'Red Apple 1')),
			array($this->db->fullTableName('apples', false) => array('name' => 'Bright Red Apple')),
			array($this->db->fullTableName('apples', false) => array('name' => 'green blue')),
			array($this->db->fullTableName('apples', false) => array('name' => 'Test Name')),
			array($this->db->fullTableName('apples', false) => array('name' => 'Blue Green')),
			array($this->db->fullTableName('apples', false) => array('name' => 'My new apple')),
			array($this->db->fullTableName('apples', false) => array('name' => 'Some odd color'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->db->field($this->testDb->fullTableName('apples', false), 'SELECT color, name FROM ' . $this->testDb->fullTableName('apples') . ' ORDER BY id');
		$expected = array(
			'color' => 'Red 1',
			'name' => 'Red Apple 1'
		);
		$this->assertEqual($result, $expected);

		$Apple->unbindModel(array(), false);
		$result = $this->db->read($Apple, array(
			'fields' => array($Apple->escapeField('name')),
			'conditions' => null,
			'recursive' => -1
		));
		$expected = array(
			array('Apple' => array('name' => 'Red Apple 1')),
			array('Apple' => array('name' => 'Bright Red Apple')),
			array('Apple' => array('name' => 'green blue')),
			array('Apple' => array('name' => 'Test Name')),
			array('Apple' => array('name' => 'Blue Green')),
			array('Apple' => array('name' => 'My new apple')),
			array('Apple' => array('name' => 'Some odd color'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->db->read($Article, array(
			'fields' => array('id', 'user_id', 'title'),
			'conditions' => null,
			'recursive' => 1
		));

		$this->assertTrue(Set::matches('/Article[id=1]', $result));
		$this->assertTrue(Set::matches('/Comment[id=1]', $result));
		$this->assertTrue(Set::matches('/Comment[id=2]', $result));
		$this->assertFalse(Set::matches('/Comment[id=10]', $result));
	}

/**
 * testName method
 *
 * @access public
 * @return void
 */
	function testName() {
		$result = $this->testDb->name('name');
		$expected = '`name`';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name(array('name', 'Model.*'));
		$expected = array('`name`', '`Model`.*');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('MTD()');
		$expected = 'MTD()';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('(sm)');
		$expected = '(sm)';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('name AS x');
		$expected = '`name` AS `x`';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('Model.name AS x');
		$expected = '`Model`.`name` AS `x`';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('Function(Something.foo)');
		$expected = 'Function(`Something`.`foo`)';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('Function(SubFunction(Something.foo))');
		$expected = 'Function(SubFunction(`Something`.`foo`))';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('Function(Something.foo) AS x');
		$expected = 'Function(`Something`.`foo`) AS `x`';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('name-with-minus');
		$expected = '`name-with-minus`';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name(array('my-name', 'Foo-Model.*'));
		$expected = array('`my-name`', '`Foo-Model`.*');
		$this->assertEqual($result, $expected);
	}

/**
 * testLog method
 *
 * @access public
 * @return void
 */
	function testLog() {
		$this->testDb->logQuery('Query 1');
		$this->testDb->logQuery('Query 2');

		$log = $this->testDb->getLog(false, false);
		$result = Set::extract($log['log'], '/query');
		$expected = array('Query 1', 'Query 2');
		$this->assertEqual($result, $expected);

		$oldError = $this->testDb->error;
		$this->testDb->error = true;
		$result = $this->testDb->logQuery('Error 1');
		$this->assertFalse($result);
		$this->testDb->error = $oldError;

		$log = $this->testDb->getLog(false, false);
		$result = Set::combine($log['log'], '/query', '/error');
		$expected = array('Query 1' => false, 'Query 2' => false, 'Error 1' => true);
		$this->assertEqual($result, $expected);

		Configure::write('debug', 2);
		ob_start();
		$this->testDb->showLog();
		$contents = ob_get_clean();

		$this->assertPattern('/Query 1/s', $contents);
		$this->assertPattern('/Query 2/s', $contents);
		$this->assertPattern('/Error 1/s', $contents);

		ob_start();
		$this->testDb->showLog(true);
		$contents = ob_get_clean();

		$this->assertPattern('/Query 1/s', $contents);
		$this->assertPattern('/Query 2/s', $contents);
		$this->assertPattern('/Error 1/s', $contents);

		$oldError = $this->testDb->error;
		$oldDebug = Configure::read('debug');
		Configure::write('debug', 2);

		$this->testDb->error = true;
		$this->expectError();
		ob_start();
		$this->testDb->showQuery('Error 2');
		$contents = ob_get_clean();

		$this->assertPattern('/Error 2/s', $contents);

		$this->testDb->error = $oldError;
		Configure::write('debug', $oldDebug);
	}

/**
 * test getting the query log as an array.
 *
 * @return void
 */
	function testGetLog() {
		$this->testDb->logQuery('Query 1');
		$this->testDb->logQuery('Query 2');

		$oldError = $this->testDb->error;
		$this->testDb->error = true;
		$result = $this->testDb->logQuery('Error 1');
		$this->assertFalse($result);
		$this->testDb->error = $oldError;

		$log = $this->testDb->getLog();
		$expected = array('query' => 'Query 1', 'error' => '', 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEqual($log['log'][0], $expected);
		$expected = array('query' => 'Query 2', 'error' => '', 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEqual($log['log'][1], $expected);
		$expected = array('query' => 'Error 1', 'error' => true, 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEqual($log['log'][2], $expected);
	}

/**
 * test that execute runs queries.
 *
 * @return void
 */
	function testExecute() {
		$query = 'SELECT * FROM ' . $this->testDb->fullTableName('articles') . ' WHERE 1 = 1';

		$this->db->_result = null;
		$this->db->took = null;
		$this->db->affected = null;
		$result = $this->db->execute($query, array('stats' => false));
		$this->assertNotNull($result, 'No query performed! %s');
		$this->assertNull($this->db->took, 'Stats were set %s');
		$this->assertNull($this->db->affected, 'Stats were set %s');

		$result = $this->db->execute($query);
		$this->assertNotNull($result, 'No query performed! %s');
		$this->assertNotNull($this->db->took, 'Stats were not set %s');
		$this->assertNotNull($this->db->affected, 'Stats were not set %s');
	}

/**
 * test that query() returns boolean values from operations like CREATE TABLE
 *
 * @return void
 */
	function testFetchAllBooleanReturns() {
		$name = $this->db->fullTableName('test_query');
		$query = "CREATE TABLE {$name} (name varchar(10));";
		$result = $this->db->query($query);
		$this->assertTrue($result, 'Query did not return a boolean. %s');

		$query = "DROP TABLE {$name};";
		$result = $this->db->fetchAll($query);
		$this->assertTrue($result, 'Query did not return a boolean. %s');
	}

/**
 * test ShowQuery generation of regular and error messages
 *
 * @return void
 */
	function testShowQuery() {
		$this->testDb->error = false;
		ob_start();
		$this->testDb->showQuery('Some Query');
		$contents = ob_get_clean();
		$this->assertPattern('/Some Query/s', $contents);
		$this->assertPattern('/Aff:/s', $contents);
		$this->assertPattern('/Num:/s', $contents);
		$this->assertPattern('/Took:/s', $contents);

		$this->expectError();
		$this->testDb->error = true;
		ob_start();
		$this->testDb->showQuery('Another Query');
		$contents = ob_get_clean();
		$this->assertPattern('/Another Query/s', $contents);
		$this->assertNoPattern('/Aff:/s', $contents);
		$this->assertNoPattern('/Num:/s', $contents);
		$this->assertNoPattern('/Took:/s', $contents);
	}

/**
 * test fields generating usable virtual fields to use in query
 *
 * @return void
 */
	function testVirtualFields() {
		$this->loadFixtures('Article');

		$Article =& ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
			'comment_count' => 'SELECT COUNT(*) FROM ' . $this->db->fullTableName('comments') .
				' WHERE Article.id = ' . $this->db->fullTableName('comments') . '.article_id'
		);
		$result = $this->db->fields($Article);
		$expected = array(
			'`Article`.`id`',
			'`Article`.`user_id`',
			'`Article`.`title`',
			'`Article`.`body`',
			'`Article`.`published`',
			'`Article`.`created`',
			'`Article`.`updated`',
			'(NOW()) AS  `Article__this_moment`',
			'(1 + 1) AS  `Article__two`',
			'(SELECT COUNT(*) FROM comments WHERE `Article`.`id` = `comments`.`article_id`) AS  `Article__comment_count`'
		);
		$this->assertEqual($expected, $result);

		$result = $this->db->fields($Article, null, array('this_moment', 'title'));
		$expected = array(
			'`Article`.`title`',
			'(NOW()) AS  `Article__this_moment`',
		);
		$this->assertEqual($expected, $result);

		$result = $this->db->fields($Article, null, array('Article.title', 'Article.this_moment'));
		$expected = array(
			'`Article`.`title`',
			'(NOW()) AS  `Article__this_moment`',
		);
		$this->assertEqual($expected, $result);

		$result = $this->db->fields($Article, null, array('Article.this_moment', 'Article.title'));
		$expected = array(
			'`Article`.`title`',
			'(NOW()) AS  `Article__this_moment`',
		);
		$this->assertEqual($expected, $result);
	}

/**
 * test conditions to generate query conditions for virtual fields
 *
 * @return void
 */
	function testVirtualFieldsInConditions() {
		$this->loadFixtures('Article');

		$Article =& ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
			'comment_count' => 'SELECT COUNT(*) FROM ' . $this->db->fullTableName('comments') .
				' WHERE Article.id = ' . $this->db->fullTableName('comments') . '.article_id'
		);
		$conditions = array('two' => 2);
		$result = $this->db->conditions($conditions,true,false,$Article);
		$expected = '(1 + 1) = 2';
		$this->assertEqual($expected, $result);

		$conditions = array('this_moment BETWEEN ? AND ?' => array(1,2));
		$expected = 'NOW() BETWEEN 1 AND 2';
		$result = $this->db->conditions($conditions, true, false, $Article);
		$this->assertEqual($expected, $result);

		$conditions = array('comment_count >' => 5);
		$expected = '(SELECT COUNT(*) FROM comments WHERE `Article`.`id` = `comments`.`article_id`) > 5';
		$result = $this->db->conditions($conditions, true, false, $Article);
		$this->assertEqual($expected, $result);

		$conditions = array('NOT' => array('two' => 2));
		$result = $this->db->conditions($conditions, true, false, $Article);
		$expected = 'NOT ((1 + 1) = 2)';
		$this->assertEqual($expected, $result);
	}

/**
 * test order to generate query order clause for virtual fields
 *
 * @return void
 */
	function testVirtualFieldsInOrder() {
		$this->loadFixtures('Article');

		$Article =& ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
		);
		$order = array('two', 'this_moment');
		$result = $this->db->order($order, 'ASC', $Article);
		$expected = ' ORDER BY (1 + 1) ASC, (NOW()) ASC';
		$this->assertEqual($expected, $result);
	}

/**
 * test calculate to generate claculate statements on virtual fields
 *
 * @return void
 */
	function testVirtualFieldsInCalculate() {
		$this->loadFixtures('Article');

		$Article =& ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
			'comment_count' => 'SELECT COUNT(*) FROM ' . $this->db->fullTableName('comments') .
				' WHERE Article.id = ' . $this->db->fullTableName('comments'). '.article_id'
		);

		$result = $this->db->calculate($Article, 'count', array('this_moment'));
		$expected = 'COUNT(NOW()) AS `count`';
		$this->assertEqual($expected, $result);

		$result = $this->db->calculate($Article, 'max', array('comment_count'));
		$expected = 'MAX(SELECT COUNT(*) FROM comments WHERE `Article`.`id` = `comments`.`article_id`) AS `comment_count`';
		$this->assertEqual($expected, $result);
	}

/**
 * test a full example of using virtual fields
 *
 * @return void
 */
	function testVirtualFieldsFetch() {
		$this->loadFixtures('Article', 'Comment');

		$Article =& ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'comment_count' => 'SELECT COUNT(*) FROM ' . $this->db->fullTableName('comments') .
				' WHERE Article.id = ' . $this->db->fullTableName('comments') . '.article_id'
		);

		$conditions = array('comment_count >' => 2);
		$query = 'SELECT ' . join(',',$this->db->fields($Article, null, array('id', 'comment_count'))) .
				' FROM ' .  $this->db->fullTableName($Article) . ' Article ' . $this->db->conditions($conditions, true, true, $Article);
		$result = $this->db->fetchAll($query);
		$expected = array(array(
			'Article' => array('id' => 1, 'comment_count' => 4)
		));
		$this->assertEqual($expected, $result);
	}

/**
 * test group to generate GROUP BY statements on virtual fields
 *
 * @return void
 */
	function testVirtualFieldsInGroup() {
		$this->loadFixtures('Article');

		$Article =& ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_year' => 'YEAR(Article.created)'
		);

		$result = $this->db->group('this_year',$Article);
		$expected = " GROUP BY (YEAR(`Article`.`created`))";
		$this->assertEqual($expected, $result);
	}
}
?>