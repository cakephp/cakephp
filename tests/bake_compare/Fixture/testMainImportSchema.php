<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ArticleFixture
 *
 */
class ArticleFixture extends TestFixture {

/**
 * Table name
 *
 * @var string
 */
	public $table = 'comments';

/**
 * Import
 *
 * @var array
 */
	public $import = ['model' => 'Article', 'records' => true, 'connection' => 'test'];

}
