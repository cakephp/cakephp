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
namespace Cake\Test\TestCase\ORM\Association;

use Cake\ORM\Association\HasMany;
use Cake\ORM\Table;
use Cake\ORM\Query;

/**
 * Tests HasMany class
 *
 */
class HasOneTest extends \Cake\TestSuite\TestCase {

/**
 * Set up
 *
 * @return void
 */
	public function setUp() {
		$this->author = Table::build('Author', [
			'schema' => [
				'id' => ['type' => 'integer'],
				'username' => ['type' => 'string'],
			]
		]);
		$this->article = $this->getMock(
			'Cake\ORM\Table', ['find'], [['alias' => 'Article']]
		);
		$this->article->schema([
			'id' => ['type' => 'integer'],
			'title' => ['type' => 'string'],
			'author_id' => ['type' => 'integer'],
		]);
	}

/**
 * Tear down
 *
 * @return void
 */
	public function tearDown() {
		Table::clearRegistry();
	}

/**
 * Tests that the association reports it can be joined
 *
 * @return void
 */
	public function testCanBeJoined() {
		$assoc = new HasMany('Test');
		$this->assertFalse($assoc->canBeJoined());
	}

/**
 * Tests sort() method
 *
 * @return void
 */
	public function testSort() {
		$assoc = new HasMany('Test');
		$this->assertNull($assoc->sort());
		$assoc->sort(['id' => 'ASC']);
		$this->assertEquals(['id' => 'ASC'], $assoc->sort());
	}

/**
 * Test the eager loader method with no extra options
 *
 * @return void
 */
	public function testEagerLoader() {
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article
		];
		$association = new HasMany('Article', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock('Cake\ORM\Query', ['execute'], [null]);
		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];
		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$callable = $association->eagerLoader($keys);
		$row = ['Author__id' => 1, 'username' => 'author 1'];
		$result = $callable($row);
		$row['Author__Article'] = [
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
			];
		$this->assertEquals($row, $result);

		$row = ['Author__id' => 2, 'username' => 'author 2'];
		$result = $callable($row);
		$row['Author__Article'] = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2]
			];
		$this->assertEquals($row, $result);
	}

/**
 * Test the eager loader method with default query clauses
 *
 * @return void
 */
	public function testEagerLoaderWithDefaults() {
		$config = [
			'sourceTable' => $this->author,
			'targetTable' => $this->article,
			'conditions' => ['Article.is_active' => true],
			'sort' => ['id' => 'ASC']
		];
		$association = new HasMany('Article', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock(
			'Cake\ORM\Query',
			['execute', 'where', 'andWhere', 'order'],
			[null]
		);
		$this->article->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'title' => 'article 1', 'author_id' => 2],
			['id' => 2, 'title' => 'article 2', 'author_id' => 1]
		];

		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('where')
			->with(['Article.is_active' => true])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('andWhere')
			->with(['Article.author_id in' => $keys])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('order')
			->with(['id' => 'ASC'])
			->will($this->returnValue($query));

		$association->eagerLoader($keys);
	}

}
