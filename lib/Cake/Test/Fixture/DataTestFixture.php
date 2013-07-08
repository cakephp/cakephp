<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.6700
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class DataTestFixture extends CakeTestFixture {

/**
 * Fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'count' => array('type' => 'integer', 'default' => 0),
		'float' => array('type' => 'float', 'default' => 0),
		'created' => array('type' => 'datetime', 'default' => null),
		'updated' => array('type' => 'datetime', 'default' => null)
	);

/**
 * Records property
 *
 * @var array
 */
	public $records = array(
		array(
			'count' => 2,
			'float' => 2.4,
			'created' => '2010-09-06 12:28:00',
			'updated' => '2010-09-06 12:28:00'
		)
	);
}
