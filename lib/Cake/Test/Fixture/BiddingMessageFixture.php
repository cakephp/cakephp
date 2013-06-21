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
 * @since         CakePHP(tm) v 1.3.14
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class BiddingMessageFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'BiddingMessage'
 */
	public $name = 'BiddingMessage';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'bidding' => array('type' => 'string', 'null' => false, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('bidding' => 'One', 'name' => 'Message 1'),
		array('bidding' => 'Two', 'name' => 'Message 2'),
		array('bidding' => 'Three', 'name' => 'Message 3'),
		array('bidding' => 'Four', 'name' => 'Message 4')
	);
}
