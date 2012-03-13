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
class JoinThingFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'JoinThing'
 */
	public $name = 'JoinThing';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'something_id' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'something_else_id' => array('type' => 'integer', 'default' => null),
		'doomed' => array('type' => 'boolean', 'default' => '0'),
		'created' => array('type' => 'datetime', 'null' => true),
		'updated' => array('type' => 'datetime', 'null' => true)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('something_id' => 1, 'something_else_id' => 2, 'doomed' => '1', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
		array('something_id' => 2, 'something_else_id' => 3, 'doomed' => '0', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
		array('something_id' => 3, 'something_else_id' => 1, 'doomed' => '1', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')
	);
}
