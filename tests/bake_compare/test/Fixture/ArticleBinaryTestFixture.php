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
	public $table = 'binary_tests';

/**
 * Fields
 *
 * @var array
 */
	public $fields = [
		'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true, 'precision' => null, 'comment' => null],
		'data' => ['type' => 'binary', 'length' => null, 'null' => true, 'default' => null, 'precision' => null, 'comment' => null],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
		],
	];

/**
 * Records
 *
 * @var array
 */
	public $records = [
		[
			'id' => 1,
			'data' => 'Lorem ipsum dolor sit amet'
		],
	];

}
