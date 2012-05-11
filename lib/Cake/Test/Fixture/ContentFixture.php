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
class ContentFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Aco'
 */
	public $name = 'Content';

	public $table = 'Content';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'iContentId' => array('type' => 'integer', 'key' => 'primary'),
		'cDescription' => array('type' => 'string', 'length' => 50, 'null' => true)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('cDescription' => 'Test Content 1'),
		array('cDescription' => 'Test Content 2'),
		array('cDescription' => 'Test Content 3'),
		array('cDescription' => 'Test Content 4')
	);
}
