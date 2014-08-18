<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * ResultSet test case.
 */
class ResultSetTest extends TestCase {

	public $fixtures = ['core.article', 'core.comment'];

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->connection = ConnectionManager::get('test');
		$this->table = new Table([
			'table' => 'articles',
			'connection' => $this->connection,
		]);

		$this->fixtureData = [
			['id' => 1, 'author_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y'],
			['id' => 2, 'author_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y'],
			['id' => 3, 'author_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y']
		];
	}

/**
 * Test that result sets can be rewound and re-used.
 *
 * @return void
 */
	public function testRewind() {
		$query = $this->table->find('all');
		$results = $query->all();
		$first = $second = [];
		foreach ($results as $result) {
			$first[] = $result;
		}
		foreach ($results as $result) {
			$second[] = $result;
		}
	}

/**
 * Test that streaming results cannot be rewound
 *
 * @return void
 */
	public function testRewindStreaming() {
		$query = $this->table->find('all')->bufferResults(false);
		$results = $query->all();
		$first = $second = [];
		foreach ($results as $result) {
			$first[] = $result;
		}
		$this->setExpectedException('Cake\Database\Exception');
		foreach ($results as $result) {
			$second[] = $result;
		}
	}

/**
 * An integration test for testing serialize and unserialize features.
 *
 * Compare the results of a query with the results iterated, with
 * those of a different query that have been serialized/unserialized.
 *
 * @return void
 */
	public function testSerialization() {
		$query = $this->table->find('all');
		$results = $query->all();
		$expected = $results->toArray();

		$query2 = $this->table->find('all');
		$results2 = $query2->all();
		$serialized = serialize($results2);
		$outcome = unserialize($serialized);
		$this->assertEquals($expected, $outcome->toArray());
	}

/**
 * Test iteration after serialization
 *
 * @return void
 */
	public function testIteratorAfterSerializationNoHydration() {
		$query = $this->table->find('all')->hydrate(false);
		$results = unserialize(serialize($query->all()));

		// Use a loop to test Iterator implementation
		foreach ($results as $i => $row) {
			$this->assertEquals($this->fixtureData[$i], $row, "Row $i does not match");
		}
	}

/**
 * Test iteration after serialization
 *
 * @return void
 */
	public function testIteratorAfterSerializationHydrated() {
		$query = $this->table->find('all');
		$results = unserialize(serialize($query->all()));

		// Use a loop to test Iterator implementation
		foreach ($results as $i => $row) {
			$expected = new \Cake\ORM\Entity($this->fixtureData[$i]);
			$expected->isNew(false);
			$expected->source($this->table->alias());
			$expected->clean();
			$this->assertEquals($expected, $row, "Row $i does not match");
		}
	}

/**
 * Test converting resultsets into json
 *
 * @return void
 */
	public function testJsonSerialize() {
		$query = $this->table->find('all');
		$results = $query->all();

		$expected = json_encode($this->fixtureData);
		$this->assertEquals($expected, json_encode($results));
	}

/**
 * Test first() method with a statement backed result set.
 *
 * @return void
 */
	public function testFirst() {
		$query = $this->table->find('all');
		$results = $query->hydrate(false)->all();

		$row = $results->first();
		$this->assertEquals($this->fixtureData[0], $row);

		$row = $results->first();
		$this->assertEquals($this->fixtureData[0], $row);
	}

/**
 * Test first() method with a result set that has been unserialized
 *
 * @return void
 */
	public function testFirstAfterSerialize() {
		$query = $this->table->find('all');
		$results = $query->hydrate(false)->all();
		$results = unserialize(serialize($results));

		$row = $results->first();
		$this->assertEquals($this->fixtureData[0], $row);

		$this->assertSame($row, $results->first());
		$this->assertSame($row, $results->first());
	}

/**
 * Test the countable interface.
 *
 * @return void
 */
	public function testCount() {
		$query = $this->table->find('all');
		$results = $query->all();

		$this->assertCount(3, $results, 'Should be countable and 3');
	}

/**
 * Test the countable interface after unserialize
 *
 * @return void
 */
	public function testCountAfterSerialize() {
		$query = $this->table->find('all');
		$results = $query->all();
		$results = unserialize(serialize($results));

		$this->assertCount(3, $results, 'Should be countable and 3');
	}

/**
 * Integration test to show methods from CollectionTrait work
 *
 * @return void
 */
	public function testGroupBy() {
		$query = $this->table->find('all');
		$results = $query->all()->groupBy('author_id')->toArray();
		$options = [
			'markNew' => false,
			'markClean' => true,
			'source' => $this->table->alias()
		];
		$expected = [
			1 => [
				new Entity($this->fixtureData[0], $options),
				new Entity($this->fixtureData[2], $options)
			],
			3 => [
				new Entity($this->fixtureData[1], $options),
			]
		];
		$this->assertEquals($expected, $results);
	}

/**
 * Tests __debugInfo
 *
 * @return void
 */
	public function testDebugInfo() {
		$query = $this->table->find('all');
		$results = $query->all();
		$expected = [
			'query' => $query,
			'items' => $results->toArray()
		];
		$this->assertSame($expected, $results->__debugInfo());
	}

/**
 * Test that eagerLoader leaves empty associations unpopulated.
 *
 * @return void
 */
	public function testBelongsToEagerLoaderLeavesEmptyAssocation() {
		$comments = TableRegistry::get('Comments');
		$comments->belongsTo('Articles');

		// Clear the articles table so we can trigger an empty belongsTo
		$this->table->deleteAll([]);

		$comment = $comments->find()->where(['Comments.id' => 1])
			->contain(['Articles'])
			->hydrate(false)
			->first();
		$this->assertEquals(1, $comment['id']);
		$this->assertNotEmpty($comment['comment']);
		$this->assertNull($comment['article']);

		$comment = $comments->get(1, ['contain' => ['Articles']]);
		$this->assertNull($comment->article);
		$this->assertEquals(1, $comment->id);
		$this->assertNotEmpty($comment->comment);
	}

/**
 * Test that eagerLoader leaves empty associations unpopulated.
 *
 * @return void
 */
	public function testHasOneEagerLoaderLeavesEmptyAssocation() {
		$this->table->hasOne('Comments');

		// Clear the comments table so we can trigger an empty hasOne.
		$comments = TableRegistry::get('Comments');
		$comments->deleteAll([]);

		$article = $this->table->get(1, ['contain' => ['Comments']]);
		$this->assertNull($article->comment);
		$this->assertEquals(1, $article->id);
		$this->assertNotEmpty($article->title);

		$article = $this->table->find()->where(['articles.id' => 1])
			->contain(['Comments'])
			->hydrate(false)
			->first();
		$this->assertNull($article['comment']);
		$this->assertEquals(1, $article['id']);
		$this->assertNotEmpty($article['title']);
	}

}
