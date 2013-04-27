<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Schema\Table;
use Cake\TestSuite\TestCase;

/**
 * Test case for Table
 */
class TableTest extends TestCase {

/**
 * Test construction with columns
 *
 * @return void
 */
	public function testConstructWithColumns() {
		$columns = [
			'id' => [
				'type' => 'integer',
				'length' => 11,
			],
			'title' => [
				'type' => 'string',
				'length' => 255
			]
		];
		$table = new Table('articles', $columns);
		$this->assertEquals(['id', 'title'], $table->columns());
	}

/**
 * Test adding columns.
 *
 * @return void
 */
	public function testAddColumn() {
		$table = new Table('articles');
		$result = $table->addColumn('title', [
			'type' => 'string',
			'length' => 25,
			'null' => false
		]);
		$this->assertSame($table, $result);
		$this->assertEquals(['title'], $table->columns());

		$result = $table->addColumn('body', 'text');
		$this->assertSame($table, $result);
		$this->assertEquals(['title', 'body'], $table->columns());
	}

/**
 * Attribute keys should be filtered and have defaults set.
 *
 * @return void
 */
	public function testAddColumnFiltersAttributes() {
		$table = new Table('articles');
		$table->addColumn('title', [
			'type' => 'string'
		]);
		$result = $table->column('title');
		$expected = [
			'type' => 'string',
			'length' => null,
			'default' => null,
			'null' => null,
			'fixed' => null,
			'comment' => null,
			'collate' => null,
			'charset' => null,
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Test adding an index.
 *
 * @return void
 */
	public function testAddIndex() {
		$table = new Table('articles');
		$table->addColumn('id', [
			'type' => 'integer'
		]);
		$result = $table->addIndex('primary', [
			'type' => 'primary',
			'columns' => ['id']
		]);
		$this->assertSame($result, $table);
		$this->assertEquals(['primary'], $table->indexes());
	}

/**
 * Test that an exception is raised when indexes
 * are added for fields that do not exist.
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testAddIndexErrorWhenFieldIsMissing() {
		$table = new Table('articles');
		$table->addIndex('author_idx', [
			'columns' => ['author_id']
		]);
	}

/**
 * Test that exceptions are raised when indexes
 * are added with invalid types
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testAddIndexErrorWrongType() {
		$table = new Table('articles');
		$table->addColumn('author_id', 'integer')
			->addIndex('author_idx', [
				'type' => 'derp',
				'columns' => ['author_id']
			]);
	}

/**
 * Test adding different kinds of indexes.
 *
 * @return void
 */
	public function testAddIndexTypes() {
		$table = new Table('articles');
		$table->addColumn('id', 'integer')
			->addColumn('title', 'string')
			->addColumn('author_id', 'integer');

		$table->addIndex('author_idx', [
			'columns' => ['author_id'],
			'type' => 'unique'
			])->addIndex('primary', [
				'type' => 'primary',
				'columns' => ['id']
			]);

		$this->assertEquals(
			['author_idx', 'primary'],
			$table->indexes()
		);
	}

/**
 * Test getting the primary key.
 *
 * @return void
 */
	public function testPrimaryKey() {
		$table = new Table('articles');
		$table->addColumn('id', 'integer')
			->addColumn('title', 'string')
			->addColumn('author_id', 'integer')
			->addIndex('author_idx', [
				'columns' => ['author_id'],
				'type' => 'unique'
			])->addIndex('primary', [
				'type' => 'primary',
				'columns' => ['id']
			]);
		$this->assertEquals(['id'], $table->primaryKey());
	}

}
