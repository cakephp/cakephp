<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class StoriesTagFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'StoriesTag'
 */
	public $name = 'StoriesTag';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'story' => array('type' => 'integer', 'null' => false),
		'tag_id' => array('type' => 'integer', 'null' => false),
		'indexes' => array('UNIQUE_STORY_TAG' => array('column' => array('story', 'tag_id'), 'unique' => 1))
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('story' => 1, 'tag_id' => 1)
	);
}
