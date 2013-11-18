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
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 */
class DeviceTypeFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'device_type_category_id' => ['type' => 'integer', 'null' => false],
		'feature_set_id' => ['type' => 'integer', 'null' => false],
		'exterior_type_category_id' => ['type' => 'integer', 'null' => false],
		'image_id' => ['type' => 'integer', 'null' => false],
		'extra1_id' => ['type' => 'integer', 'null' => false],
		'extra2_id' => ['type' => 'integer', 'null' => false],
		'name' => ['type' => 'string', 'null' => false],
		'order' => ['type' => 'integer', 'null' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('device_type_category_id' => 1, 'feature_set_id' => 1, 'exterior_type_category_id' => 1, 'image_id' => 1, 'extra1_id' => 1, 'extra2_id' => 1, 'name' => 'DeviceType 1', 'order' => 0)
	);
}
