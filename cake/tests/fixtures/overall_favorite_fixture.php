<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.7198
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       cake.tests.fixtures
 */
class OverallFavoriteFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'OverallFavorite'
 * @access public
 */
	public $name = 'OverallFavorite';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'model_type' => array('type' => 'string', 'length' => 255),
		'model_id' => array('type' => 'integer'),
		'priority' => array('type' => 'integer')
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	public $records = array(
		array('id' => 1, 'model_type' => 'Cd', 'model_id' => '1', 'priority' => '1'),
		array('id' => 2, 'model_type' => 'Book', 'model_id' => '1', 'priority' => '2')
	);
}
