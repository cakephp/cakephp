<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.3.14
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class BiddingMessageFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'BiddingMessage'
 * @access public
 */
	var $name = 'BiddingMessage';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'bidding' => array('type' => 'string', 'null' => false, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false)
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array('bidding' => 'One', 'name' => 'Message 1'),
		array('bidding' => 'Two', 'name' => 'Message 2'),
		array('bidding' => 'Three', 'name' => 'Message 3'),
		array('bidding' => 'Four', 'name' => 'Message 4')
	);
}
