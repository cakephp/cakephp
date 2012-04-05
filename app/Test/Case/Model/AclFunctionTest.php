<?php
App::uses('AclFunction', 'Model');

/**
 * AclFunction Test Case
 *
 */
class AclFunctionTestCase extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('app.acl_function', 'app.acl', 'app.acl_role', 'app.role', 'app.user', 'app.roles_user');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->AclFunction = ClassRegistry::init('AclFunction');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->AclFunction);

		parent::tearDown();
	}

}
