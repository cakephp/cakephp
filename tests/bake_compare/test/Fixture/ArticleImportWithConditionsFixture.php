<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ArticlesFixture
 *
 */
class ArticlesFixture extends TestFixture {

/**
 * Import
 *
 * @var array
 */
	public $import = ['model' => 'Articles', 'connection' => 'test'];

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
			'body' => 'First Article Body',
			'published' => 'Y'
		],
		[
			'id' => 2,
			'author_id' => 3,
			'title' => 'Second Article',
			'body' => 'Second Article Body',
			'published' => 'Y'
		],
		[
			'id' => 3,
			'author_id' => 1,
			'title' => 'Third Article',
			'body' => 'Third Article Body',
			'published' => 'Y'
		],
	];

}
