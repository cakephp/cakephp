<?php
/**
 * BakeCommentFixture
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class BakeArticlesBakeTagFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'ArticlesTag'
 */
	public $name = 'BakeArticlesBakeTag';

/**
 * fields property
 *
 * @var array
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
 */
	public $records = array();
}
