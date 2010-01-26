<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.7198
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class BookFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Book'
 * @access public
 */
	var $name = 'Book';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'isbn' => array('type' => 'string', 'length' => 13),
		'title' => array('type' => 'string', 'length' =>  255),
		'author' => array('type' => 'string', 'length' => 255),
		'year' => array('type' => 'integer', 'null' => true),
		'pages' => array('type' => 'integer', 'null' => true)
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array('id' => 1, 'isbn' => '1234567890', 'title' => 'Faust', 'author' => 'Johann Wolfgang von Goethe')
	);
}
?>