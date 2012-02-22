<?php
/* Acl Test cases generated on: 2012-01-31 22:00:43 : 1328068843*/
App::uses('Acl', 'Model');

/**
 * Acl Test Case
 *
 */
class AclTestCase extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('app.acl', 'app.acl_function', 'app.acl_role', 'app.role');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->Acl = ClassRegistry::init('Acl');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Acl);

		parent::tearDown();
	}

}
