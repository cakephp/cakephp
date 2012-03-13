<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class SyfileFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Syfile'
 */
	public $name = 'Syfile';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'image_id' => array('type' => 'integer', 'null' => true),
		'name' => array('type' => 'string', 'null' => false),
		'item_count' => array('type' => 'integer', 'null' => true)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('image_id' => 1, 'name' => 'Syfile 1'),
		array('image_id' => 2, 'name' => 'Syfile 2'),
		array('image_id' => 5, 'name' => 'Syfile 3'),
		array('image_id' => 3, 'name' => 'Syfile 4'),
		array('image_id' => 4, 'name' => 'Syfile 5'),
		array('image_id' => null, 'name' => 'Syfile 6')
	);
}
