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
class SessionFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Session'
 */
	public $name = 'Session';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 128, 'key' => 'primary'),
		'data' => array('type' => 'text','null' => true),
		'expires' => array('type' => 'integer', 'length' => 11, 'null' => true)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array();
}
