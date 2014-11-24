<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ArticleFixture
 *
 */
class ArticleFixture extends TestFixture {

/**
 * Import
 *
 * @var array
 */
	public $import = ['model' => 'Article', 'connection' => 'test'];

/**
 * Records
 *
 * @var array
 */
	public $records = [
		[
			'id' => 1,
			'author_id' => 1,
			'title' => 'First Article',
			'body' => 'Body "value"',
			'published' => 'Y'
		],
		[
			'id' => 2,
			'author_id' => 3,
			'title' => 'Second Article',
			'body' => 'Body "value"',
			'published' => 'Y'
		],
		[
			'id' => 3,
			'author_id' => 1,
			'title' => 'Third Article',
			'body' => 'Body "value"',
			'published' => 'Y'
		],
	];

}
