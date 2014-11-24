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
	public $table = 'datatypes';

/**
 * Fields
 *
 * @var array
 */
	public $fields = [
		'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true, 'precision' => null, 'comment' => null],
		'decimal_field' => ['type' => 'decimal', 'length' => null, 'unsigned' => false, 'null' => true, 'default' => '0.000', 'precision' => null, 'comment' => null],
		'float_field' => ['type' => 'float', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'precision' => null, 'comment' => null],
		'huge_int' => ['type' => 'biginteger', 'length' => null, 'unsigned' => false, 'null' => true, 'default' => null, 'precision' => null, 'comment' => null, 'autoIncrement' => null],
		'bool' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => 'FALSE', 'precision' => null, 'comment' => null],
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
			'decimal_field' => '',
			'float_field' => 1,
			'huge_int' => '',
			'bool' => 1
		],
	];

}
