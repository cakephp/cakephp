<?php
/**
 * Short description for file.
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
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class BasketFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'type' => array('type' => 'string', 'length' => 255),
		'name' => array('type' => 'string', 'length' => 255),
		'object_id' => array('type' => 'integer'),
		'user_id' => array('type' => 'integer'),
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'type' => 'nonfile', 'name' => 'basket1', 'object_id' => 1, 'user_id' => 1),
		array('id' => 2, 'type' => 'file', 'name' => 'basket2', 'object_id' => 2, 'user_id' => 1),
	);
}
