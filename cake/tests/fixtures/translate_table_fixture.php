<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class TranslateTableFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'TranslateTable'
 * @access public
 */
	var $name = 'TranslateTable';

/**
 * table property
 *
 * @var string 'another_i18n'
 * @access public
 */
	var $table = 'another_i18n';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
			'id' => array('type' => 'integer', 'key' => 'primary'),
			'locale' => array('type' => 'string', 'length' => 6, 'null' => false),
			'model' => array('type' => 'string', 'null' => false),
			'foreign_key' => array('type' => 'integer', 'null' => false),
			'field' => array('type' => 'string', 'null' => false),
			'content' => array('type' => 'text'));

/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array('locale' => 'eng', 'model' => 'TranslatedItemWithTable', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Another Title #1'),
		array('locale' => 'eng', 'model' => 'TranslatedItemWithTable', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Another Content #1')
	);
}
