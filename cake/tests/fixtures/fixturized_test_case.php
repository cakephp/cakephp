<?php
/**
 * This class helps in testing the life-cycle of fixtures inside a CakeTestCase
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class FixturizedTestCase extends CakeTestCase {
	
	public $name = 'FixturizedTestCase';
	public $fixtures = array('core.category');

	public function testFixturePresent() {
		$this->assertType('CakeFixtureManager', $this->sharedFixture);
		//debug($this->sharedFixture);
	}
	
}