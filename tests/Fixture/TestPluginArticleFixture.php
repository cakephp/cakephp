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
 * @since         0.0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class TestPluginArticleFixture
 *
 */
class TestPluginArticleFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'user_id' => ['type' => 'integer', 'null' => false],
		'title' => ['type' => 'string', 'null' => false],
		'body' => 'text',
		'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
		'created' => 'datetime',
		'updated' => 'datetime',
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('user_id' => 1, 'title' => 'First Plugin Article', 'body' => 'First Plugin Article Body', 'published' => 'Y', 'created' => '2008-09-24 10:39:23', 'updated' => '2008-09-24 10:41:31'),
		array('user_id' => 3, 'title' => 'Second Plugin Article', 'body' => 'Second Plugin Article Body', 'published' => 'Y', 'created' => '2008-09-24 10:41:23', 'updated' => '2008-09-24 10:43:31'),
		array('user_id' => 1, 'title' => 'Third Plugin Article', 'body' => 'Third Plugin Article Body', 'published' => 'Y', 'created' => '2008-09-24 10:43:23', 'updated' => '2008-09-24 10:45:31')
	);
}
