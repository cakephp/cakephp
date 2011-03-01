<?php
/**
 * CakeEmailTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'CakeEmail');

/**
 * CakeEmailTest class
 *
 * @package       cake.tests.cases.libs
 */
class CakeEmailTest extends CakeTestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->CakeEmail = new CakeEmail();
	}

/**
 * testFrom method
 *
 * @return void
 */
	public function testFrom() {
		$this->assertIdentical($this->CakeEmail->getFrom(), array());

		$this->CakeEmail->setFrom('cake@cakephp.org');
		$expected = array('cake@cakephp.org' => 'cake@cakephp.org');
		$this->assertIdentical($this->CakeEmail->getFrom(), $expected);

		$this->CakeEmail->setFrom('cake@cakephp.org', 'CakePHP');
		$expected = array('cake@cakephp.org' => 'CakePHP');
		$this->assertIdentical($this->CakeEmail->getFrom(), $expected);
	}

/**
 * testHeaders method
 *
 * @return void
 */
	public function testHeaders() {
	}

/**
 * testSend method
 *
 * @return void
 */
	public function testSend() {
	}

/**
 * testReset method
 *
 * @return void
 */
	public function testReset() {
	}

}
