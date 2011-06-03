<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
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
class ContentFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Aco'
 * @access public
 */
	var $name = 'Content';
	var $table = 'Content';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'iContentId'		=> array('type' => 'integer', 'key' => 'primary'),
		'cDescription'	=> array('type' => 'string', 'length' => 50, 'null' => true)
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array('cDescription' => 'Test Content 1'),
		array('cDescription' => 'Test Content 2'),
		array('cDescription' => 'Test Content 3'),
		array('cDescription' => 'Test Content 4')
	);
}
