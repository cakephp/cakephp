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
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class ArticlesTagFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'ArticlesTag'
 * @access public
 */
	var $name = 'ArticlesTag';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'article_id' => array('type' => 'integer', 'null' => false),
		'tag_id' => array('type' => 'integer', 'null' => false),
		'indexes' => array('UNIQUE_TAG' => array('column'=> array('article_id', 'tag_id'), 'unique'=>1))
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array('article_id' => 1, 'tag_id' => 1),
		array('article_id' => 1, 'tag_id' => 2),
		array('article_id' => 2, 'tag_id' => 1),
		array('article_id' => 2, 'tag_id' => 3)
	);
}
