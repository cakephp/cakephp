<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BakeCommentFixture fixture for testing bake
 *
 */
class BakeCommentFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'otherid' => ['type' => 'integer'],
		'bake_article_id' => ['type' => 'integer', 'null' => false],
		'bake_user_id' => ['type' => 'integer', 'null' => false],
		'comment' => 'text',
		'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
		'created' => 'datetime',
		'updated' => 'datetime',
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['otherid']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array();
}
