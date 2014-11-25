<?php
namespace TestApp\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use TestApp\Controller\Component\AppleComponent;

/**
 * TestApp\Controller\Component\AppleComponent Test Case
 */
class AppleComponentTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$registry = new ComponentRegistry();
		$this->Apple = new AppleComponent($registry);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Apple);

		parent::tearDown();
	}

/**
 * Test startup method
 *
 * @return void
 */
	public function testStartup() {
		$this->markTestIncomplete('Not implemented yet.');
	}

}
