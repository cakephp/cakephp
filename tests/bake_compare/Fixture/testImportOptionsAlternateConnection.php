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
			'title' => 'Lorem ipsum dolor sit amet',
			'body' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'published' => 'Lorem ipsum dolor sit ame'
		],
	];

}
