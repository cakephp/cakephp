<?php
/**
 * PhpAclTest file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Controller.Component.Acl
 * @since         CakePHP(tm) v 2.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AclComponent', 'Controller/Component');
App::uses('PhpAcl', 'Controller/Component/Acl');
class_exists('AclComponent');

/**
 * Test case for the PhpAcl implementation
 *
 * @package       Cake.Test.Case.Controller.Component.Acl
 */
class PhpAclTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		Configure::write('Acl.classname', 'PhpAcl');
		$Collection = new ComponentCollection();
		$this->PhpAcl = new PhpAcl();
		$this->Acl = new AclComponent($Collection, array(
			'adapter' => array(
				'config' => CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'acl.php',
			),
		));
	}

	public function testRoleInheritance() {
		$roles = $this->Acl->Aro->roles('User/peter');
		$this->assertEquals(array('Role/accounting'), $roles[0]);
		$this->assertEquals(array('User/peter'), $roles[1]);

		$roles = $this->Acl->Aro->roles('hardy');
		$this->assertEquals(array('Role/database_manager', 'Role/data_acquirer'), $roles[0]);
		$this->assertEquals(array('Role/accounting', 'Role/data_analyst'), $roles[1]);
		$this->assertEquals(array('Role/accounting_manager', 'Role/reports'), $roles[2]);
		$this->assertEquals(array('User/hardy'), $roles[3]);
	}

	public function testAddRole() {
		$this->assertEquals(array(array(PhpAro::DEFAULT_ROLE)), $this->Acl->Aro->roles('foobar'));
		$this->Acl->Aro->addRole(array('User/foobar' => 'Role/accounting'));
		$this->assertEquals(array(array('Role/accounting'), array('User/foobar')), $this->Acl->Aro->roles('foobar'));
	}

	public function testAroResolve() {
		$map = $this->Acl->Aro->map;
		$this->Acl->Aro->map = array(
			'User' => 'FooModel/nickname',
			'Role' => 'FooModel/role',
		);

		$this->assertEquals('Role/default', $this->Acl->Aro->resolve('Foo.bar'));
		$this->assertEquals('User/hardy', $this->Acl->Aro->resolve('FooModel/hardy'));
		$this->assertEquals('User/hardy', $this->Acl->Aro->resolve('hardy'));
		$this->assertEquals('User/hardy', $this->Acl->Aro->resolve(array('FooModel' => array('nickname' => 'hardy'))));
		$this->assertEquals('Role/admin', $this->Acl->Aro->resolve(array('FooModel' => array('role' => 'admin'))));
		$this->assertEquals('Role/admin', $this->Acl->Aro->resolve('Role/admin'));

		$this->assertEquals('Role/admin', $this->Acl->Aro->resolve('admin'));
		$this->assertEquals('Role/admin', $this->Acl->Aro->resolve('FooModel/admin'));
		$this->assertEquals('Role/accounting', $this->Acl->Aro->resolve('accounting'));

		$this->assertEquals(PhpAro::DEFAULT_ROLE, $this->Acl->Aro->resolve('bla'));
		$this->assertEquals(PhpAro::DEFAULT_ROLE, $this->Acl->Aro->resolve(array('FooModel' => array('role' => 'hardy'))));
	}

/**
 * test correct resolution of defined aliases
 */
	public function testAroAliases() {
		$this->Acl->Aro->map = array(
			'User' => 'User/username',
			'Role' => 'User/group_id',
		);

		$this->Acl->Aro->aliases = array(
			'Role/1' => 'Role/admin',
			'Role/24' => 'Role/accounting',
		);

		$user = array(
			'User' => array(
				'username' => 'unknown_user',
				'group_id' => '1',
			),
		);
		// group/1
		$this->assertEquals('Role/admin', $this->Acl->Aro->resolve($user));
		// group/24
		$this->assertEquals('Role/accounting', $this->Acl->Aro->resolve('Role/24'));
		$this->assertEquals('Role/accounting', $this->Acl->Aro->resolve('24'));

		// check department
		$user = array(
			'User' => array(
				'username' => 'foo',
				'group_id' => '25',
			),
		);

		$this->Acl->Aro->addRole(array('Role/IT' => null));
		$this->Acl->Aro->addAlias(array('Role/25' => 'Role/IT'));
		$this->Acl->allow('Role/IT', '/rules/debugging/*');

		$this->assertEquals(array(array('Role/IT', )), $this->Acl->Aro->roles($user));
		$this->assertTrue($this->Acl->check($user, '/rules/debugging/stats/pageload'));
		$this->assertTrue($this->Acl->check($user, '/rules/debugging/sql/queries'));
		// Role/default is allowed users dashboard, but not Role/IT
		$this->assertFalse($this->Acl->check($user, '/controllers/users/dashboard'));

		$this->assertFalse($this->Acl->check($user, '/controllers/invoices/send'));
		// wee add an more specific entry for user foo to also inherit from Role/accounting
		$this->Acl->Aro->addRole(array('User/foo' => 'Role/IT, Role/accounting'));
		$this->assertTrue($this->Acl->check($user, '/controllers/invoices/send'));
	}

/**
 * test check method
 *
 * @return void
 */
	public function testCheck() {
		$this->assertTrue($this->Acl->check('jan', '/controllers/users/Dashboard'));
		$this->assertTrue($this->Acl->check('some_unknown_role', '/controllers/users/Dashboard'));
		$this->assertTrue($this->Acl->check('Role/admin', 'foo/bar'));
		$this->assertTrue($this->Acl->check('role/admin', '/foo/bar'));
		$this->assertTrue($this->Acl->check('jan', 'foo/bar'));
		$this->assertTrue($this->Acl->check('user/jan', 'foo/bar'));
		$this->assertTrue($this->Acl->check('Role/admin', 'controllers/bar'));
		$this->assertTrue($this->Acl->check(array('User' => array('username' => 'jan')), '/controllers/bar/bll'));
		$this->assertTrue($this->Acl->check('Role/database_manager', 'controllers/db/create'));
		$this->assertTrue($this->Acl->check('User/db_manager_2', 'controllers/db/create'));
		$this->assertFalse($this->Acl->check('db_manager_2', '/controllers/users/Dashboard'));

		// inheritance: hardy -> reports -> data_analyst -> database_manager
		$this->assertTrue($this->Acl->check('User/hardy', 'controllers/db/create'));
		$this->assertFalse($this->Acl->check('User/jeff', 'controllers/db/create'));

		$this->assertTrue($this->Acl->check('Role/database_manager', 'controllers/db/select'));
		$this->assertTrue($this->Acl->check('User/db_manager_2', 'controllers/db/select'));
		$this->assertFalse($this->Acl->check('User/jeff', 'controllers/db/select'));

		$this->assertTrue($this->Acl->check('Role/database_manager', 'controllers/db/drop'));
		$this->assertTrue($this->Acl->check('User/db_manager_1', 'controllers/db/drop'));
		$this->assertFalse($this->Acl->check('db_manager_2', 'controllers/db/drop'));

		$this->assertTrue($this->Acl->check('db_manager_2', 'controllers/invoices/edit'));
		$this->assertFalse($this->Acl->check('database_manager', 'controllers/invoices/edit'));
		$this->assertFalse($this->Acl->check('db_manager_1', 'controllers/invoices/edit'));

		// Role/manager is allowed /controllers/*/*_manager
		$this->assertTrue($this->Acl->check('stan', 'controllers/invoices/manager_edit'));
		$this->assertTrue($this->Acl->check('Role/manager', 'controllers/baz/manager_foo'));
		$this->assertFalse($this->Acl->check('User/stan', 'custom/foo/manager_edit'));
		$this->assertFalse($this->Acl->check('stan', 'bar/baz/manager_foo'));
		$this->assertFalse($this->Acl->check('Role/accounting', 'bar/baz/manager_foo'));
		$this->assertFalse($this->Acl->check('accounting', 'controllers/baz/manager_foo'));

		$this->assertTrue($this->Acl->check('User/stan', 'controllers/articles/edit'));
		$this->assertTrue($this->Acl->check('stan', 'controllers/articles/add'));
		$this->assertTrue($this->Acl->check('stan', 'controllers/articles/publish'));
		$this->assertFalse($this->Acl->check('User/stan', 'controllers/articles/delete'));
		$this->assertFalse($this->Acl->check('accounting', 'controllers/articles/edit'));
		$this->assertFalse($this->Acl->check('accounting', 'controllers/articles/add'));
		$this->assertFalse($this->Acl->check('role/accounting', 'controllers/articles/publish'));
	}

/**
 * lhs of defined rules are case insensitive
 */
	public function testCheckIsCaseInsensitive() {
		$this->assertTrue($this->Acl->check('hardy', 'controllers/forms/new'));
		$this->assertTrue($this->Acl->check('Role/data_acquirer', 'controllers/forms/new'));
		$this->assertTrue($this->Acl->check('hardy', 'controllers/FORMS/NEW'));
		$this->assertTrue($this->Acl->check('Role/data_acquirer', 'controllers/FORMS/NEW'));
	}

/**
 * allow should work in-memory
 */
	public function testAllow() {
		$this->assertFalse($this->Acl->check('jeff', 'foo/bar'));

		$this->Acl->allow('jeff', 'foo/bar');

		$this->assertTrue($this->Acl->check('jeff', 'foo/bar'));
		$this->assertFalse($this->Acl->check('peter', 'foo/bar'));
		$this->assertFalse($this->Acl->check('hardy', 'foo/bar'));

		$this->Acl->allow('Role/accounting', 'foo/bar');

		$this->assertTrue($this->Acl->check('peter', 'foo/bar'));
		$this->assertTrue($this->Acl->check('hardy', 'foo/bar'));

		$this->assertFalse($this->Acl->check('Role/reports', 'foo/bar'));
	}

/**
 * deny should work in-memory
 */
	public function testDeny() {
		$this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_foo'));

		$this->Acl->deny('stan', 'controllers/baz/manager_foo');

		$this->assertFalse($this->Acl->check('stan', 'controllers/baz/manager_foo'));
		$this->assertTrue($this->Acl->check('Role/manager', 'controllers/baz/manager_foo'));
		$this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_bar'));
		$this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_foooooo'));
	}

/**
 * test that a deny rule wins over an equally specific allow rule
 */
	public function testDenyRuleIsStrongerThanAllowRule() {
		$this->assertFalse($this->Acl->check('peter', 'baz/bam'));
		$this->Acl->allow('peter', 'baz/bam');
		$this->assertTrue($this->Acl->check('peter', 'baz/bam'));
		$this->Acl->deny('peter', 'baz/bam');
		$this->assertFalse($this->Acl->check('peter', 'baz/bam'));

		$this->assertTrue($this->Acl->check('stan', 'controllers/reports/foo'));
		// stan is denied as he's sales and sales is denied /controllers/*/delete
		$this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));
		$this->Acl->allow('stan', 'controllers/reports/delete');
		$this->assertFalse($this->Acl->check('Role/sales', 'controllers/reports/delete'));
		$this->assertTrue($this->Acl->check('stan', 'controllers/reports/delete'));
		$this->Acl->deny('stan', 'controllers/reports/delete');
		$this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));

		// there is already an equally specific deny rule that will win
		$this->Acl->allow('stan', 'controllers/reports/delete');
		$this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));
	}

/**
 * test that an invalid configuration throws exception
 */
	public function testInvalidConfigWithAroMissing() {
		$this->setExpectedException(
			'AclException',
			'"roles" section not found in configuration'
		);
		$config = array('aco' => array('allow' => array('foo' => '')));
		$this->PhpAcl->build($config);
	}

	public function testInvalidConfigWithAcosMissing() {
		$this->setExpectedException(
			'AclException',
			'Neither "allow" nor "deny" rules were provided in configuration.'
		);

		$config = array(
			'roles' => array('Role/foo' => null),
		);

		$this->PhpAcl->build($config);
	}

/**
 * test resolving of ACOs
 */
	public function testAcoResolve() {
		$this->assertEquals(array('foo', 'bar'), $this->Acl->Aco->resolve('foo/bar'));
		$this->assertEquals(array('foo', 'bar'), $this->Acl->Aco->resolve('foo/bar'));
		$this->assertEquals(array('foo', 'bar', 'baz'), $this->Acl->Aco->resolve('foo/bar/baz'));
		$this->assertEquals(array('foo', '*-bar', '?-baz'), $this->Acl->Aco->resolve('foo/*-bar/?-baz'));

		$this->assertEquals(array('foo', 'bar', '[a-f0-9]{24}', '*_bla', 'bla'), $this->Acl->Aco->resolve('foo/bar/[a-f0-9]{24}/*_bla/bla'));

		// multiple slashes will be squashed to a single, trimmed and then exploded
		$this->assertEquals(array('foo', 'bar'), $this->Acl->Aco->resolve('foo//bar'));
		$this->assertEquals(array('foo', 'bar'), $this->Acl->Aco->resolve('//foo///bar/'));
		$this->assertEquals(array('foo', 'bar'), $this->Acl->Aco->resolve('/foo//bar//'));
		$this->assertEquals(array('foo', 'bar'), $this->Acl->Aco->resolve('/foo // bar'));
		$this->assertEquals(array(), $this->Acl->Aco->resolve('/////'));
	}

/**
 * test that declaring cyclic dependencies should give an error when building the tree
 */
	public function testAroDeclarationContainsCycles() {
		$config = array(
			'roles' => array(
				'Role/a' => null,
				'Role/b' => 'User/b',
				'User/a' => 'Role/a, Role/b',
				'User/b' => 'User/a',

			),
			'rules' => array(
				'allow' => array(
					'*' => 'Role/a',
				),
			),
		);

		$this->expectError('PHPUnit_Framework_Error', 'cycle detected' /* ... */);
		$this->PhpAcl->build($config);
	}

/**
 * test that with policy allow, only denies count
 */
	public function testPolicy() {
		// allow by default
		$this->Acl->settings['adapter']['policy'] = PhpAcl::ALLOW;
		$this->Acl->adapter($this->PhpAcl);

		$this->assertTrue($this->Acl->check('Role/sales', 'foo'));
		$this->assertTrue($this->Acl->check('Role/sales', 'controllers/bla/create'));
		$this->assertTrue($this->Acl->check('Role/default', 'foo'));
		// undefined user, undefined aco
		$this->assertTrue($this->Acl->check('foobar', 'foo/bar'));

		// deny rule: Role.sales -> controllers.*.delete
		$this->assertFalse($this->Acl->check('Role/sales', 'controllers/bar/delete'));
		$this->assertFalse($this->Acl->check('Role/sales', 'controllers/bar', 'delete'));
	}
}
