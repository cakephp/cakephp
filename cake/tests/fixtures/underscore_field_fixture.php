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
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * UnderscoreFieldFixture class
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class UnderscoreFieldFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'UnderscoreField'
 * @access public
 */
	var $name = 'UnderscoreField';
	/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'user_id' => array('type' => 'integer', 'null' => false),
		'my_model_has_a_field' => array('type' => 'string', 'null' => false),
		'body_field' => 'text',
		'published' => array('type' => 'string', 'length' => 1, 'default' => 'N'),
		'another_field' => array('type' => 'integer', 'length' => 3),
	);
	/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array('user_id' => 1, 'my_model_has_a_field' => 'First Article', 'body_field' => 'First Article Body', 'published' => 'Y', 'another_field' => 2),
		array('user_id' => 3, 'my_model_has_a_field' => 'Second Article', 'body_field' => 'Second Article Body', 'published' => 'Y', 'another_field' => 3),
		array('user_id' => 1, 'my_model_has_a_field' => 'Third Article', 'body_field' => 'Third Article Body', 'published' => 'Y', 'another_field' => 5),
	);

}

?>