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
 * UuidNativeTagFixture
 *
 * @package       Cake.Test.Fixture
 */
class UuidNativeTagFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'uuid', 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => 255),
		'created' => array('type' => 'datetime')
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => '481fc6d0-b920-43e0-e50f-6d1740cf8569', 'name' => 'MyTag', 'created' => '2009-12-09 12:30:00')
	);
}
