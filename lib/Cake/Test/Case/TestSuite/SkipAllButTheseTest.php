<?php
/**
 * SkipAllButTheseTest file
 *
 * Test Case for CakeTestCase::skipAllButThese method
 *
 * PHP version 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP v 2.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * SkipAllButTheseTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class SkipAllButTheseTest extends CakeTestCase {
/**
 * setUpBeforeClass
 *
 * @return void
 */
	public static function setUpBeforeClass() {
		self::skipAllButThese(
			array('testDontSkipMe', 'testDontSkipMeEither'),
			'This test should have been skipped by skipAllButThese()'
		);
	}

/**
 * testDontSkipMe
 *
 * @return void
 */
	public function testDontSkipMe() {
		$this->assertTrue(true);
	}

/**
 * testButYouShouldSkipMe
 *
 * @return void
 */
	public function testButYouShouldSkipMe() {
		$this->fail('This test method should have been skipped.');
	}

/**
 * testDontSkipMeEither
 *
 * @return void
 */
	public function testDontSkipMeEither() {
		$this->assertTrue(true);
	}

/**
 * testButYouShouldAlsoSkipMe
 *
 * @return void
 */
	public function testButYouShouldAlsoSkipMe() {
		$this->fail('This test method should have been skipped.');
	}
}
