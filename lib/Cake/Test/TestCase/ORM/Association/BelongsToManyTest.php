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

use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Table;
use Cake\ORM\Query;

/**
 * Tests BelongsToMany class
 *
 */
class BelongsToManyTest extends \Cake\TestSuite\TestCase {

/**
 * Set up
 *
 * @return void
 */
	public function setUp() {
		$this->tag = $this->getMock(
			'Cake\ORM\Table', ['find'], [['alias' => 'Tag', 'table' => 'tags']]
		);
		$this->tag->schema([
			'id' => ['type' => 'integer'],
			'name' => ['type' => 'string'],
		]);
		$this->article = $this->getMock(
			'Cake\ORM\Table', ['find'], [['alias' => 'Article', 'table' => 'articles']]
		);
		$this->article->schema([
			'id' => ['type' => 'integer'],
			'name' => ['type' => 'string'],
		]);
		Table::instance('Article', $this->article);
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
		$assoc = new BelongsToMany('Test');
		$this->assertFalse($assoc->canBeJoined());
	}

/**
 * Tests sort() method
 *
 * @return void
 */
	public function testSort() {
		$assoc = new BelongsToMany('Test');
		$this->assertNull($assoc->sort());
		$assoc->sort(['id' => 'ASC']);
		$this->assertEquals(['id' => 'ASC'], $assoc->sort());
	}

/**
 * Tests requiresKeys() method
 *
 * @return void
 */
	public function testRequiresKeys() {
		$assoc = new BelongsToMany('Test');
		$this->assertTrue($assoc->requiresKeys());
		$assoc->strategy(BelongsToMany::STRATEGY_SUBQUERY);
		$this->assertFalse($assoc->requiresKeys());
		$assoc->strategy(BelongsToMany::STRATEGY_SELECT);
		$this->assertTrue($assoc->requiresKeys());
	}

/**
 * Tests the pivot method
 *
 * @return void
 */
	public function testPivot() {
		$assoc = new BelongsToMany('Test', [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag
		]);
		$pivot = $assoc->pivot();
		$this->assertInstanceOf('\Cake\ORM\Table', $pivot);
		$this->assertEquals('ArticleTag', $pivot->alias());
		$this->assertEquals('articles_tags', $pivot->table());
		$this->assertSame($this->article, $pivot->association('Article')->source());
		$this->assertSame($this->tag, $pivot->association('Tag')->target());

		$belongsTo = '\Cake\ORM\Association\BelongsTo';
		$this->assertInstanceOf($belongsTo, $pivot->association('Article'));
		$this->assertInstanceOf($belongsTo, $pivot->association('Tag'));

		$this->assertSame($pivot, $this->tag->association('ArticleTag')->target());
		$this->assertSame($this->article, $this->tag->association('Article')->target());

		$hasMany = '\Cake\ORM\Association\HasMany';
		$belongsToMany = '\Cake\ORM\Association\BelongsToMany';
		$this->assertInstanceOf($belongsToMany, $this->tag->association('Article'));
		$this->assertInstanceOf($hasMany, $this->tag->association('ArticleTag'));

		$this->assertSame($pivot, $assoc->pivot());
		$pivot2 = Table::build('Foo');
		$assoc->pivot($pivot2);
		$this->assertSame($pivot2, $assoc->pivot());

		$assoc->pivot('ArticleTag');
		$this->assertSame($pivot, $assoc->pivot());
	}

/**
 * Tests it is possible to set the table name for the join table
 *
 * @return void
 */
	public function testPivotWithDefaultTableName() {
		$assoc = new BelongsToMany('Test', [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'joinTable' => 'tags_articles'
		]);
		$pivot = $assoc->pivot();
		$this->assertEquals('ArticleTag', $pivot->alias());
		$this->assertEquals('tags_articles', $pivot->table());
	}

/**
 * Tests that the correct join and fields are attached to a query depending on
 * the association config
 *
 * @return void
 */
	public function testAttachTo() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null]);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tag.name' => 'cake']
		];
		Table::build('ArticleTag', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer']
			]
		]);
		$association = new BelongsToMany('Tag', $config);
		$query->expects($this->at(0))->method('join')->with([
			'Tag' => [
				'conditions' => [
					'Tag.name' => 'cake'
				],
				'type' => 'INNER',
				'table' => 'tags'
			]
		]);
		$query->expects($this->at(2))->method('join')->with([
			'ArticleTag' => [
				'conditions' => [
					'Tag.id = ArticleTag.tag_id'
				],
				'type' => 'INNER',
				'table' => 'articles_tags'
			]
		]);
		$query->expects($this->at(1))->method('select')->with([
			'Tag__id' => 'Tag.id',
			'Tag__name' => 'Tag.name',
		]);
		$query->expects($this->at(3))->method('select')->with([
			'ArticleTag__article_id' => 'ArticleTag.article_id',
			'ArticleTag__tag_id' => 'ArticleTag.tag_id',
		]);
		$association->attachTo($query);
	}

/**
 * Tests that it is possible to avoid fields inclusion for the associated table
 *
 * @return void
 */
	public function testAttachToNoFields() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null]);
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tag.name' => 'cake']
		];
		Table::build('ArticleTag', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer']
			]
		]);
		$association = new BelongsToMany('Tag', $config);
		$query->expects($this->at(0))->method('join')->with([
			'Tag' => [
				'conditions' => [
					'Tag.name' => 'cake'
				],
				'type' => 'INNER',
				'table' => 'tags'
			]
		]);
		$query->expects($this->at(1))->method('join')->with([
			'ArticleTag' => [
				'conditions' => [
					'Tag.id = ArticleTag.tag_id'
				],
				'type' => 'INNER',
				'table' => 'articles_tags'
			]
		]);
		$query->expects($this->never())->method('select');
		$association->attachTo($query, ['includeFields' => false]);
	}

/**
 * Test the eager loader method with no extra options
 *
 * @return void
 */
	public function testEagerLoader() {
		$config = [
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
		];
		Table::build('ArticleTag', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer']
			]
		]);
		$association = new BelongsToMany('Tag', $config);
		$keys = [1, 2, 3, 4];
		$query = $this->getMock('Cake\ORM\Query', ['execute', 'contain'], [null]);
		$this->tag->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'name' => 'foo', 'ArticleTag' => ['article_id' => 1]],
			['id' => 2, 'name' => 'bar', 'ArticleTag' => ['article_id' => 2]]
		];
		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('contain')->with([
			'ArticleTag' => [
				'conditions' => ['ArticleTag.article_id in' => $keys],
				'filtering' => true
			]
		]);

		$callable = $association->eagerLoader(compact('keys'));
		$row = ['Article__id' => 1, 'title' => 'article 1'];
		$result = $callable($row);
		$row['Article__Tag'] = [
			['id' => 1, 'name' => 'foo', 'ArticleTag' => ['article_id' => 1]]
		];
		$this->assertEquals($row, $result);

		$row = ['Article__id' => 2, 'title' => 'article 2'];
		$result = $callable($row);
		$row['Article__Tag'] = [
			['id' => 2, 'name' => 'bar', 'ArticleTag' => ['article_id' => 2]]
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
			'sourceTable' => $this->article,
			'targetTable' => $this->tag,
			'conditions' => ['Tag.name' => 'foo'],
			'sort' => ['id' => 'ASC'],
		];
		Table::build('ArticleTag', [
			'table' => 'articles_tags',
			'schema' => [
				'article_id' => ['type' => 'integer'],
				'tag_id' => ['type' => 'integer']
			]
		]);
		$association = new BelongsToMany('Tag', $config);
		$keys = [1, 2, 3, 4];
		$methods = ['execute', 'contain', 'where', 'order'];
		$query = $this->getMock('Cake\ORM\Query', $methods, [null]);
		$this->tag->expects($this->once())->method('find')->with('all')
			->will($this->returnValue($query));
		$results = [
			['id' => 1, 'name' => 'foo', 'ArticleTag' => ['article_id' => 1]],
			['id' => 2, 'name' => 'bar', 'ArticleTag' => ['article_id' => 2]]
		];
		$query->expects($this->once())->method('execute')
			->will($this->returnValue($results));

		$query->expects($this->once())->method('contain')->with([
			'ArticleTag' => [
				'conditions' => ['ArticleTag.article_id in' => $keys],
				'filtering' => true
			]
		]);

		$query->expects($this->once())->method('where')
			->with(['Tag.name' => 'foo'])
			->will($this->returnValue($query));

		$query->expects($this->once())->method('order')
			->with(['id' => 'ASC'])
			->will($this->returnValue($query));

		$callable = $association->eagerLoader(compact('keys'));
	}


}
