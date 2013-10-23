<?php
/**
 * AclNodeTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DbAcl', 'Controller/Component/Acl');
App::uses('AclNode', 'Model');

/**
 * DB ACL wrapper test class
 *
 * @package       Cake.Test.Case.Model
 */
class DbAclNodeTestBase extends AclNode {

/**
 * useDbConfig property
 *
 * @var string
 */
	public $useDbConfig = 'test';

/**
 * cacheSources property
 *
 * @var boolean
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
 * useTable property
 *
 * @var string
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
 * useTable property
 *
 * @var string
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
 * useTable property
 *
 * @var string
 */
	public $useTable = 'aros_acos';

/**
 * cacheQueries property
 *
 * @var boolean
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
 * useTable property
 *
 * @var string
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
 * @var string
 */
	public $name = 'AuthUser';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'auth_users';

/**
 * bindNode method
 *
 * @param string|array|Model $ref
 * @return void
 */
	public function bindNode($ref = null) {
		if (Configure::read('DbAclbindMode') === 'string') {
			return 'ROOT/admins/Gandalf';
		} elseif (Configure::read('DbAclbindMode') === 'array') {
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
 */
	public function __construct() {
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
		$result = Hash::extract($Aco->node('Controller1'), '{n}.DbAcoTest.id');
		$expected = array(2, 1);
		$this->assertEquals($expected, $result);

		$result = Hash::extract($Aco->node('Controller1/action1'), '{n}.DbAcoTest.id');
		$expected = array(3, 2, 1);
		$this->assertEquals($expected, $result);

		$result = Hash::extract($Aco->node('Controller2/action1'), '{n}.DbAcoTest.id');
		$expected = array(7, 6, 1);
		$this->assertEquals($expected, $result);

		$result = Hash::extract($Aco->node('Controller1/action2'), '{n}.DbAcoTest.id');
		$expected = array(5, 2, 1);
		$this->assertEquals($expected, $result);

		$result = Hash::extract($Aco->node('Controller1/action1/record1'), '{n}.DbAcoTest.id');
		$expected = array(4, 3, 2, 1);
		$this->assertEquals($expected, $result);

		$result = Hash::extract($Aco->node('Controller2/action1/record1'), '{n}.DbAcoTest.id');
		$expected = array(8, 7, 6, 1);
		$this->assertEquals($expected, $result);

		$this->assertFalse($Aco->node('Controller2/action3'));

		$this->assertFalse($Aco->node('Controller2/action3/record5'));

		$result = $Aco->node('');
		$this->assertEquals(null, $result);
	}

/**
 * test that node() doesn't dig deeper than it should.
 *
 * @return void
 */
	public function testNodeWithDuplicatePathSegments() {
		$Aco = new DbAcoTest();
		$nodes = $Aco->node('ROOT/Users');
		$this->assertEquals(1, $nodes[0]['DbAcoTest']['parent_id'], 'Parent id does not point at ROOT. %s');
	}

/**
 * testNodeArrayFind method
 *
 * @return void
 */
	public function testNodeArrayFind() {
		$Aro = new DbAroTest();
		Configure::write('DbAclbindMode', 'string');
		$result = Hash::extract($Aro->node(array('DbAroUserTest' => array('id' => '1', 'foreign_key' => '1'))), '{n}.DbAroTest.id');
		$expected = array(3, 2, 1);
		$this->assertEquals($expected, $result);

		Configure::write('DbAclbindMode', 'array');
		$result = Hash::extract($Aro->node(array('DbAroUserTest' => array('id' => 4, 'foreign_key' => 2))), '{n}.DbAroTest.id');
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
		$result = Hash::extract($Aro->node($Model), '{n}.DbAroTest.id');
		$expected = array(3, 2, 1);
		$this->assertEquals($expected, $result);

		$Model->id = 2;
		$result = Hash::extract($Aro->node($Model), '{n}.DbAroTest.id');
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
			array('DbAcoTest' => array('id' => '2', 'parent_id' => '1', 'model' => null, 'foreign_key' => null, 'alias' => 'Pages', 'lft' => '2', 'rght' => '3'), 'DbAroTest' => array())
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testNodeActionAuthorize method
 *
 * @return void
 */
	public function testNodeActionAuthorize() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load('TestPlugin');

		$Aro = new DbAroTest();
		$Aro->create();
		$Aro->save(array('model' => 'TestPluginAuthUser', 'foreign_key' => 1));
		$result = $Aro->id;
		$expected = 5;
		$this->assertEquals($expected, $result);

		$node = $Aro->node(array('TestPlugin.TestPluginAuthUser' => array('id' => 1, 'user' => 'mariano')));
		$result = Hash::get($node, '0.DbAroTest.id');
		$expected = $Aro->id;
		$this->assertEquals($expected, $result);
		CakePlugin::unload('TestPlugin');
	}
}
