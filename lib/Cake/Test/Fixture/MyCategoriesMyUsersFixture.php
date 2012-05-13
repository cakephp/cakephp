<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
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
class MyCategoriesMyUsersFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'MyCategoriesMyUsers'
 */
	public $name = 'MyCategoriesMyUsers';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'my_category_id' => array('type' => 'integer'),
		'my_user_id' => array('type' => 'integer'),
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('my_category_id' => 1, 'my_user_id' => 1),
		array('my_category_id' => 3, 'my_user_id' => 1),
		array('my_category_id' => 1, 'my_user_id' => 2),
		array('my_category_id' => 2, 'my_user_id' => 2),
	);
}
