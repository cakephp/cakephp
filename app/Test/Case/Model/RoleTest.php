<?php
/* Role Test cases generated on: 2012-01-31 22:05:35 : 1328069135*/
App::uses('Role', 'Model');

/**
 * Role Test Case
 *
 */
class RoleTestCase extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('app.role', 'app.acl_role', 'app.acl', 'app.acl_function', 'app.user', 'app.roles_user');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->Role = ClassRegistry::init('Role');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Role);

		parent::tearDown();
	}

}
