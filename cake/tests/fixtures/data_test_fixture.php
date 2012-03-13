<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.6700
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class DataTestFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'DataTest'
 * @access public
 */
	var $name = 'DataTest';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'count' => array('type' => 'integer', 'default' => 0),
		'float' => array('type' => 'float', 'default' => 0),
		//'timestamp' => array('type' => 'timestamp', 'default' => null, 'null' => true),
		'created' => array('type' => 'datetime', 'default' => null),
		'updated' => array('type' => 'datetime', 'default' => null)
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array(
			'count' => 2,
			'float' => 2.4,
			'created' => '2010-09-06 12:28:00',
			'updated' => '2010-09-06 12:28:00'
		)
	);
}
