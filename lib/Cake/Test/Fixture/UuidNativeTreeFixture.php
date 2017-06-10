<?php
/**
 * UUID Tree behavior fixture.
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
 * @since         CakePHP(tm) v 1.2.0.7984
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * UuidNativeTreeFixture class
 *
 * @uses          CakeTestFixture
 * @package       Cake.Test.Fixture
 */
class UuidNativeTreeFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id'	=> array('type' => 'uuid', 'key' => 'primary'),
		'name'	=> array('type' => 'string', 'null' => false),
		'parent_id' => array('type' => 'string', 'length' => 36, 'null' => true),
		'lft'	=> array('type' => 'integer', 'null' => false),
		'rght'	=> array('type' => 'integer', 'null' => false)
	);
}
