<?php
/**
 * This class helps in testing the life-cycle of fixtures inside a CakeTestCase
 *
 * @package       Cake.Test.Fixture
 */
class FixturizedTestCase extends CakeTestCase {

/**
 * Fixtures to use in this thes
 * @var array
 */
	public $fixtures = array('core.category');

/**
 * test that the shared fixture is correctly set
 *
 * @return void
 */
	public function testFixturePresent() {
		$this->assertInstanceOf('CakeFixtureManager', $this->fixtureManager);
	}

/**
 * test that it is possible to load fixtures on demand
 *
 * @return void
 */
	public function testFixtureLoadOnDemand() {
		$this->loadFixtures('Category');
	}

/**
 * test that a test is marked as skipped using skipIf and its first parameter evaluates to true
 *
 * @return void
 */
	public function testSkipIfTrue() {
		$this->skipIf(true);
	}

/**
 * test that a test is not marked as skipped using skipIf and its first parameter evaluates to false
 *
 * @return void
 */
	public function testSkipIfFalse() {
		$this->skipIf(false);
	}

/**
 * test that a fixtures are unoaded even if the test throws exceptions
 *
 * @return void
 */
	public function testThrowException() {
		throw new Exception();
	}
}
