<?php
/**
 * BakeCommentFixture
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.fixtures
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       cake.tests.fixtures
 */
class BakeArticlesBakeTagFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'ArticlesTag'
 * @access public
 */
	public $name = 'BakeArticlesBakeTag';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'bake_article_id' => array('type' => 'integer', 'null' => false),
		'bake_tag_id' => array('type' => 'integer', 'null' => false),
		'indexes' => array('UNIQUE_TAG' => array('column'=> array('bake_article_id', 'bake_tag_id'), 'unique'=>1))
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	public $records = array();
}
