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
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class ItemFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Item'
 */
	public $name = 'Item';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'syfile_id' => array('type' => 'integer', 'null' => false),
		'published' => array('type' => 'boolean', 'null' => false),
		'name' => array('type' => 'string', 'null' => false)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('syfile_id' => 1, 'published' => 0, 'name' => 'Item 1'),
		array('syfile_id' => 2, 'published' => 0, 'name' => 'Item 2'),
		array('syfile_id' => 3, 'published' => 0, 'name' => 'Item 3'),
		array('syfile_id' => 4, 'published' => 0, 'name' => 'Item 4'),
		array('syfile_id' => 5, 'published' => 0, 'name' => 'Item 5'),
		array('syfile_id' => 6, 'published' => 0, 'name' => 'Item 6')
	);
}
