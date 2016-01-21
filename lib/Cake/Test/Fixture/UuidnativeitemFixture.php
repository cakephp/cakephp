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
 * Class UuidnativeitemFixture
 *
 * @package       Cake.Test.Fixture
 */
class UuidnativeitemFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'uuid', 'key' => 'primary'),
		'published' => array('type' => 'boolean', 'null' => false),
		'name' => array('type' => 'string', 'null' => false)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => '481fc6d0-b920-43e0-a40d-6d1740cf8569', 'published' => 0, 'name' => 'Item 1'),
		array('id' => '48298a29-81c0-4c26-a7fb-413140cf8569', 'published' => 0, 'name' => 'Item 2'),
		array('id' => '482b7756-8da0-419a-b21f-27da40cf8569', 'published' => 0, 'name' => 'Item 3'),
		array('id' => '482cfd4b-0e7c-4ea3-9582-4cec40cf8569', 'published' => 0, 'name' => 'Item 4'),
		array('id' => '4831181b-4020-4983-a29b-131440cf8569', 'published' => 0, 'name' => 'Item 5'),
		array('id' => '483798c8-c7cc-430e-8cf9-4fcc40cf8569', 'published' => 0, 'name' => 'Item 6')
	);
}
