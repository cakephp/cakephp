<?php
/**
 * Short description for file.
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * JoinThingFixture
 *
 * @package       Cake.Test.Fixture
 */
class JoinThingFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'something_id' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'something_else_id' => array('type' => 'integer', 'default' => null),
		'doomed' => array('type' => 'boolean', 'default' => '0'),
		'created' => array('type' => 'datetime', 'null' => true),
		'updated' => array('type' => 'datetime', 'null' => true)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('something_id' => 1, 'something_else_id' => 2, 'doomed' => '1', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
		array('something_id' => 2, 'something_else_id' => 3, 'doomed' => '0', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
		array('something_id' => 3, 'something_else_id' => 1, 'doomed' => '1', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')
	);
}
