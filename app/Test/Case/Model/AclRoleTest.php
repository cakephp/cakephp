<?php
/* AclRole Test cases generated on: 2012-01-31 22:04:36 : 1328069076*/
App::uses('AclRole', 'Model');

/**
 * AclRole Test Case
 *
 */
class AclRoleTestCase extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('app.acl_role', 'app.acl', 'app.acl_function', 'app.role', 'app.user', 'app.roles_user');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->AclRole = ClassRegistry::init('AclRole');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->AclRole);

		parent::tearDown();
	}

}
