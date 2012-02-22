<?php
/* AclFunction Test cases generated on: 2012-01-31 21:59:27 : 1328068767*/
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
	public $fixtures = array('app.acl_function', 'app.acl', 'app.acl_role');

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
