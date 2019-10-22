<?php
App::uses('Widget', 'Model');

/**
 * Widget Test Case
 */
class WidgetTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.widget'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Widget = ClassRegistry::init('Widget');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Widget);

		parent::tearDown();
	}

}
