<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * UnderscoreFieldFixture class
 *
 * @package       Cake.Test.Fixture
 */
class UnderscoreFieldFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'UnderscoreField'
 */
	public $name = 'UnderscoreField';
	/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'user_id' => array('type' => 'integer', 'null' => false),
		'my_model_has_a_field' => array('type' => 'string', 'null' => false),
		'body_field' => 'text',
		'published' => array('type' => 'string', 'length' => 1, 'default' => 'N'),
		'another_field' => array('type' => 'integer', 'length' => 3),
	);
	/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('user_id' => 1, 'my_model_has_a_field' => 'First Article', 'body_field' => 'First Article Body', 'published' => 'Y', 'another_field' => 2),
		array('user_id' => 3, 'my_model_has_a_field' => 'Second Article', 'body_field' => 'Second Article Body', 'published' => 'Y', 'another_field' => 3),
		array('user_id' => 1, 'my_model_has_a_field' => 'Third Article', 'body_field' => 'Third Article Body', 'published' => 'Y', 'another_field' => 5),
	);

}
