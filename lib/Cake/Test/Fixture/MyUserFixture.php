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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class MyUserFixture
 *
 * @package       Cake.Test.Fixture
 */
class MyUserFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'MyUser'
 */
	public $name = 'MyUser';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'firstname' => array('type' => 'string', 'null' => false),
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => 1, 'firstname' => 'userA'),
		array('id' => 2, 'firstname' => 'userB')
	);
}
