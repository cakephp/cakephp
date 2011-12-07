<?php
/**
 * DbAclTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AclComponent', 'Controller/Component');
App::uses('AclNode', 'Model');
class_exists('AclComponent');

/**
 * DB ACL wrapper test class
 *
 * @package       Cake.Test.Case.Model
 */
class DbAclNodeTestBase extends AclNode {

/**
 * useDbConfig property
 *
 * @var string 'test'
 */
	public $useDbConfig = 'test';

/**
 * cacheSources property
 *
 * @var bool false
 */
	public $cacheSources = false;
}

/**
 * Aro Test Wrapper
 *
 * @package       Cake.Test.Case.Model
 */
class DbAroTest extends DbAclNodeTestBase {

/**
 * name property
 *
 * @var string 'DbAroTest'
 */
	public $name = 'DbAroTest';

/**
 * useTable property
 *
 * @var string 'aros'
 */
	public $useTable = 'aros';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('DbAcoTest' => array('with' => 'DbPermissionTest'));
}

/**
 * Aco Test Wrapper
 *
 * @package       Cake.Test.Case.Model
 */
class DbAcoTest extends DbAclNodeTestBase {

/**
 * name property
 *
 * @var string 'DbAcoTest'
 */
	public $name = 'DbAcoTest';

/**
 * useTable property
 *
 * @var string 'acos'
 */
	public $useTable = 'acos';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('DbAroTest' => array('with' => 'DbPermissionTest'));
}

/**
 * Permission Test Wrapper
 *
 * @package       Cake.Test.Case.Model
 */
class DbPermissionTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DbPermissionTest'
 */
	public $name = 'DbPermissionTest';

/**
 * useTable property
 *
 * @var string 'aros_acos'
 */
	public $useTable = 'aros_acos';

/**
 * cacheQueries property
 *
 * @var bool false
 */
	public $cacheQueries = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('DbAroTest' => array('foreignKey' => 'aro_id'), 'DbAcoTest' => array('foreignKey' => 'aco_id'));
}

/**
 * DboActionTest class
 *
 * @package       Cake.Test.Case.Model
 */
class DbAcoActionTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DbAcoActionTest'
 */
	public $name = 'DbAcoActionTest';

/**
 * useTable property
 *
 * @var string 'aco_actions'
 */
	public $useTable = 'aco_actions';

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('DbAcoTest' => array('foreignKey' => 'aco_id'));
}

/**
 * DbAroUserTest class
 *
 * @package       Cake.Test.Case.Model
 */
class DbAroUserTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuthUser'
 */
	public $name = 'AuthUser';

/**
 * useTable property
 *
 * @var string 'auth_users'
 */
	public $useTable = 'auth_users';

/**
 * bindNode method
 *
 * @param mixed $ref
 * @return void
 */
	public function bindNode($ref = null) {
		if (Configure::read('DbAclbindMode') == 'string') {
			return 'ROOT/admins/Gandalf';
		} elseif (Configure::read('DbAclbindMode') == 'array') {
			return array('DbAroTest' => array('DbAroTest.model' => 'AuthUser', 'DbAroTest.foreign_key' => 2));
		}
	}
}

/**
 * TestDbAcl class
 *
 * @package       Cake.Test.Case.Model
 */
class TestDbAcl extends DbAcl {

/**
 * construct method
 *
 * @return void
 */
	function __construct() {
		$this->Aro = new DbAroTest();
		$this->Aro->Permission = new DbPermissionTest();
		$this->Aco = new DbAcoTest();
		$this->Aro->Permission = new DbPermissionTest();
	}
}

/**
 * AclNodeTest class
 *
 * @package       Cake.Test.Case.Model
 */
class AclNodeTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action', 'core.auth_user');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Acl.classname', 'TestDbAcl');
		Configure::write('Acl.database', 'test');
	}

/**
 * testNode method
 *
 * @return void
 */
	public function testNode() {
		$Aco = new DbAcoTest();
		$result = Set::extract($Aco->node('Controller1'), '{n}.DbAcoTest.id');
		$expected = array(2, 1);
		$this->assertEquals($expected, $result);

		$result = Set::extract($Aco->node('Controller1/action1'), '{n}.DbAcoTest.id');
		$expected = array(3, 2, 1);
		$this->assertEquals($expected, $result);

		$result = Set::extract($Aco->node('Controller2/action1'), '{n}.DbAcoTest.id');
		$expected = array(7, 6, 1);
		$this->assertEquals($expected, $result);

		$result = Set::extract($Aco->node('Controller1/action2'), '{n}.DbAcoTest.id');
		$expected = array(5, 2, 1);
		$this->assertEquals($expected, $result);

		$result = Set::extract($Aco->node('Controller1/action1/record1'), '{n}.DbAcoTest.id');
		$expected = array(4, 3, 2, 1);
		$this->assertEquals($expected, $result);

		$result = Set::extract($Aco->node('Controller2/action1/record1'), '{n}.DbAcoTest.id');
		$expected = array(8, 7, 6, 1);
		$this->assertEquals($expected, $result);

		$result = Set::extract($Aco->node('Controller2/action3'), '{n}.DbAcoTest.id');
		$this->assertNull($result);

		$result = Set::extract($Aco->node('Controller2/action3/record5'), '{n}.DbAcoTest.id');
		$this->assertNull($result);

		$result = $Aco->node('');
		$this->assertEquals($result, null);
	}

/**
 * test that node() doesn't dig deeper than it should.
 *
 * @return void
 */
	public function testNodeWithDuplicatePathSegments() {
		$Aco = new DbAcoTest();
		$nodes = $Aco->node('ROOT/Users');
		$this->assertEquals($nodes[0]['DbAcoTest']['parent_id'], 1, 'Parent id does not point at ROOT. %s');
	}

/**
 * testNodeArrayFind method
 *
 * @return void
 */
	public function testNodeArrayFind() {
		$Aro = new DbAroTest();
		Configure::write('DbAclbindMode', 'string');
		$result = Set::extract($Aro->node(array('DbAroUserTest' => array('id' => '1', 'foreign_key' => '1'))), '{n}.DbAroTest.id');
		$expected = array(3, 2, 1);
		$this->assertEquals($expected, $result);

		Configure::write('DbAclbindMode', 'array');
		$result = Set::extract($Aro->node(array('DbAroUserTest' => array('id' => 4, 'foreign_key' => 2))), '{n}.DbAroTest.id');
		$expected = array(4);
		$this->assertEquals($expected, $result);
	}
	/**
 * testNodeObjectFind method
 *
 * @return void
 */
	public function testNodeObjectFind() {
		$Aro = new DbAroTest();
		$Model = new DbAroUserTest();
		$Model->id = 1;
		$result = Set::extract($Aro->node($Model), '{n}.DbAroTest.id');
		$expected = array(3, 2, 1);
		$this->assertEquals($expected, $result);

		$Model->id = 2;
		$result = Set::extract($Aro->node($Model), '{n}.DbAroTest.id');
		$expected = array(4, 2, 1);
		$this->assertEquals($expected, $result);

	}

/**
 * testNodeAliasParenting method
 *
 * @return void
 */
	public function testNodeAliasParenting() {
		$Aco = ClassRegistry::init('DbAcoTest');
		$db = $Aco->getDataSource();
		$db->truncate($Aco);

		$Aco->create(array('model' => null, 'foreign_key' => null, 'parent_id' => null, 'alias' => 'Application'));
		$Aco->save();

		$Aco->create(array('model' => null, 'foreign_key' => null, 'parent_id' => $Aco->id, 'alias' => 'Pages'));
		$Aco->save();

		$result = $Aco->find('all');
		$expected = array(
			array('DbAcoTest' => array('id' => '1', 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'Application', 'lft' => '1', 'rght' => '4'), 'DbAroTest' => array()),
			array('DbAcoTest' => array('id' => '2', 'parent_id' => '1', 'model' => null, 'foreign_key' => null, 'alias' => 'Pages', 'lft' => '2', 'rght' => '3', ), 'DbAroTest' => array())
		);
		$this->assertEquals($expected, $result);
	}
}
