<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class PostsTagFixture
 *
 */
class PostsTagFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'post_id' => ['type' => 'integer', 'null' => false],
		'tag_id' => ['type' => 'string', 'null' => false],
		'_constraints' => ['posts_tag' => ['type' => 'unique', 'columns' => ['tag_id', 'post_id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('post_id' => 1, 'tag_id' => 'tag1'),
		array('post_id' => 1, 'tag_id' => 'tag2'),
		array('post_id' => 2, 'tag_id' => 'tag1'),
		array('post_id' => 2, 'tag_id' => 'tag3')
	);
}
