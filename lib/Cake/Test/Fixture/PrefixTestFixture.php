<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/opengroup.php Open Group Test Suite License
 */

/**
 * PrefixTestFixture
 *
 * @package       Cake.Test.Fixture
 */
class PrefixTestFixture extends CakeTestFixture {

	public $name = 'PrefixTest';

	public $table = 'prefix_prefix_tests';

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
	);

}
