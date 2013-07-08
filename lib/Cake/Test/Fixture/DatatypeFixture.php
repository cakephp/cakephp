<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.7026
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class DatatypeFixture extends CakeTestFixture {

/**
 * Fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'),
		'float_field' => array('type' => 'float', 'length' => '5,2', 'null' => false, 'default' => null),
		'huge_int' => array('type' => 'biginteger'),
		'bool' => array('type' => 'boolean', 'null' => false, 'default' => false),
	);

/**
 * Records property
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'float_field' => 42.23, 'huge_int' => '1234567891234567891', 'bool' => 0),
	);
}
