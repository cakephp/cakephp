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
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       cake.tests.fixtures
 */
class PostsTagFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'PostsTag'
 * @access public
 */
	public $name = 'PostsTag';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'post_id' => array('type' => 'integer', 'null' => false),
		'tag_id' => array('type' => 'string', 'null' => false),
		'indexes' => array('posts_tag' => array('column' => array('tag_id', 'post_id'), 'unique' => 1))
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	public $records = array(
		array('post_id' => 1, 'tag_id' => 'tag1'),
		array('post_id' => 1, 'tag_id' => 'tag2'),
		array('post_id' => 2, 'tag_id' => 'tag1'),
		array('post_id' => 2, 'tag_id' => 'tag3')
	);
}
