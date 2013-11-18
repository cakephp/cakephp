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
class ArosAcoTwoFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'aro_id' => array('type' => 'integer', 'length' => 10, 'null' => false),
		'aco_id' => array('type' => 'integer', 'length' => 10, 'null' => false),
		'_create' => array('type' => 'string', 'length' => 2, 'default' => 0),
		'_read' => array('type' => 'string', 'length' => 2, 'default' => 0),
		'_update' => array('type' => 'string', 'length' => 2, 'default' => 0),
		'_delete' => array('type' => 'string', 'length' => 2, 'default' => 0)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('aro_id' => '1', 'aco_id' => '1', '_create' => '-1', '_read' => '-1', '_update' => '-1', '_delete' => '-1'),
		array('aro_id' => '2', 'aco_id' => '1', '_create' => '0', '_read' => '1', '_update' => '1', '_delete' => '1'),
		array('aro_id' => '3', 'aco_id' => '2', '_create' => '0', '_read' => '1', '_update' => '0', '_delete' => '0'),
		array('aro_id' => '4', 'aco_id' => '2', '_create' => '1', '_read' => '1', '_update' => '0', '_delete' => '-1'),
		array('aro_id' => '4', 'aco_id' => '6', '_create' => '1', '_read' => '1', '_update' => '0', '_delete' => '0'),
		array('aro_id' => '5', 'aco_id' => '1', '_create' => '1', '_read' => '1', '_update' => '1', '_delete' => '1'),
		array('aro_id' => '6', 'aco_id' => '3', '_create' => '-1', '_read' => '1', '_update' => '-1', '_delete' => '-1'),
		array('aro_id' => '6', 'aco_id' => '4', '_create' => '-1', '_read' => '1', '_update' => '-1', '_delete' => '1'),
		array('aro_id' => '6', 'aco_id' => '6', '_create' => '-1', '_read' => '1', '_update' => '1', '_delete' => '-1'),
		array('aro_id' => '7', 'aco_id' => '2', '_create' => '-1', '_read' => '-1', '_update' => '-1', '_delete' => '-1'),
		array('aro_id' => '7', 'aco_id' => '7', '_create' => '1', '_read' => '1', '_update' => '1', '_delete' => '0'),
		array('aro_id' => '7', 'aco_id' => '8', '_create' => '1', '_read' => '1', '_update' => '1', '_delete' => '0'),
		array('aro_id' => '7', 'aco_id' => '9', '_create' => '1', '_read' => '1', '_update' => '1', '_delete' => '1'),
		array('aro_id' => '7', 'aco_id' => '10', '_create' => '0', '_read' => '0', '_update' => '0', '_delete' => '1'),
		array('aro_id' => '8', 'aco_id' => '10', '_create' => '1', '_read' => '1', '_update' => '1', '_delete' => '1'),
		array('aro_id' => '8', 'aco_id' => '2', '_create' => '-1', '_read' => '-1', '_update' => '-1', '_delete' => '-1'),
		array('aro_id' => '9', 'aco_id' => '4', '_create' => '1', '_read' => '1', '_update' => '1', '_delete' => '-1'),
		array('aro_id' => '9', 'aco_id' => '9', '_create' => '0', '_read' => '0', '_update' => '1', '_delete' => '1'),
		array('aro_id' => '10', 'aco_id' => '9', '_create' => '1', '_read' => '1', '_update' => '1', '_delete' => '1'),
		array('aro_id' => '10', 'aco_id' => '10', '_create' => '-1', '_read' => '-1', '_update' => '-1', '_delete' => '-1'),
	);
}
