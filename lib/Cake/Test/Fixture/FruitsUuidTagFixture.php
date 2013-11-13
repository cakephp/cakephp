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
 * @since         CakePHP(tm) v 1.2.0.7953
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class FruitsUuidTagFixture
 *
 * @package       Cake.Test.Fixture
 */
class FruitsUuidTagFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'fruit_id' => array('type' => 'string', 'null' => false, 'length' => 36, 'key' => 'primary'),
		'uuid_tag_id' => array('type' => 'string', 'null' => false, 'length' => 36, 'key' => 'primary'),
		'indexes' => array(
			'unique_fruits_tags' => array('unique' => true, 'column' => array('fruit_id', 'uuid_tag_id')),
		),
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('fruit_id' => '481fc6d0-b920-43e0-a40d-6d1740cf8569', 'uuid_tag_id' => '481fc6d0-b920-43e0-e50f-6d1740cf8569')
	);
}
