<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class ArosAcoTwoFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'ArosAcoTwo'
 * @access public
 */
	var $name = 'ArosAcoTwo';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
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
 * @access public
 */
	var $records = array(
		array('id' => 1, 'aro_id' => '1', 'aco_id' => '1', '_create' => '-1',  '_read' => '-1', '_update' => '-1', '_delete' => '-1'),
		array('id' => 2, 'aro_id' => '2', 'aco_id' => '1', '_create' => '0',  '_read' => '1', '_update' => '1', '_delete' => '1'),
		array('id' => 3, 'aro_id' => '3', 'aco_id' => '2', '_create' => '0',  '_read' => '1', '_update' => '0', '_delete' => '0'),
		array('id' => 4, 'aro_id' => '4', 'aco_id' => '2', '_create' => '1',  '_read' => '1', '_update' => '0', '_delete' => '-1'),
		array('id' => 5, 'aro_id' => '4', 'aco_id' => '6', '_create' => '1',  '_read' => '1', '_update' => '0', '_delete' => '0'),
		array('id' => 6, 'aro_id' => '5', 'aco_id' => '1', '_create' => '1',  '_read' => '1', '_update' => '1', '_delete' => '1'),
		array('id' => 7, 'aro_id' => '6', 'aco_id' => '3', '_create' => '-1',  '_read' => '1', '_update' => '-1', '_delete' => '-1'),
		array('id' => 8, 'aro_id' => '6', 'aco_id' => '4', '_create' => '-1',  '_read' => '1', '_update' => '-1', '_delete' => '1'),
		array('id' => 9, 'aro_id' => '6', 'aco_id' => '6', '_create' => '-1',  '_read' => '1', '_update' => '1', '_delete' => '-1'),
		array('id' => 10, 'aro_id' => '7', 'aco_id' => '2', '_create' => '-1',  '_read' => '-1', '_update' => '-1', '_delete' => '-1'),
		array('id' => 11, 'aro_id' => '7', 'aco_id' => '7', '_create' => '1',  '_read' => '1', '_update' => '1', '_delete' => '0'),
		array('id' => 12, 'aro_id' => '7', 'aco_id' => '8', '_create' => '1',  '_read' => '1', '_update' => '1', '_delete' => '0'),
		array('id' => 13, 'aro_id' => '7', 'aco_id' => '9', '_create' => '1',  '_read' => '1', '_update' => '1', '_delete' => '1'),
		array('id' => 14, 'aro_id' => '7', 'aco_id' => '10', '_create' => '0',  '_read' => '0', '_update' => '0', '_delete' => '1'),
		array('id' => 15, 'aro_id' => '8', 'aco_id' => '10', '_create' => '1',  '_read' => '1', '_update' => '1', '_delete' => '1'),
		array('id' => 16, 'aro_id' => '8', 'aco_id' => '2', '_create' => '-1',  '_read' => '-1', '_update' => '-1', '_delete' => '-1'),
		array('id' => 17, 'aro_id' => '9', 'aco_id' => '4', '_create' => '1',  '_read' => '1', '_update' => '1', '_delete' => '-1'),
		array('id' => 18, 'aro_id' => '9', 'aco_id' => '9', '_create' => '0',  '_read' => '0', '_update' => '1', '_delete' => '1'),
		array('id' => 19, 'aro_id' => '10', 'aco_id' => '9', '_create' => '1',  '_read' => '1', '_update' => '1', '_delete' => '1'),
		array('id' => 20, 'aro_id' => '10', 'aco_id' => '10', '_create' => '-1',  '_read' => '-1', '_update' => '-1', '_delete' => '-1'),
	);
}

?>