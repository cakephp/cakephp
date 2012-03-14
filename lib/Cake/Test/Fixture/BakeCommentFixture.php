<?php
/**
 * BakeCommentFixture
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * BakeCommentFixture fixture for testing bake
 *
 * @package       Cake.Test.Fixture
 */
class BakeCommentFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Comment'
 */
	public $name = 'BakeComment';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'otherid' => array('type' => 'integer', 'key' => 'primary'),
		'bake_article_id' => array('type' => 'integer', 'null' => false),
		'bake_user_id' => array('type' => 'integer', 'null' => false),
		'comment' => 'text',
		'published' => array('type' => 'string', 'length' => 1, 'default' => 'N'),
		'created' => 'datetime',
		'updated' => 'datetime'
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array();
}
