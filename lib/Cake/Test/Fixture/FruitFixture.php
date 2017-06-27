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
 * @since         CakePHP(tm) v 1.2.0.7953
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Fruit Fixtures
 *
 * @package       Cake.Test.Fixture
 */
class FruitFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => 255),
		'color' => array('type' => 'string', 'length' => 13),
		'shape' => array('type' => 'string', 'length' => 255),
		'taste' => array('type' => 'string', 'length' => 255)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => '481fc6d0-b920-43e0-a40d-6d1740cf8569', 'name' => 'Orange',
			'color' => 'orange', 'shape' => 'Spherical', 'taste' => 'Tangy & Sweet'
		)
	);
}
