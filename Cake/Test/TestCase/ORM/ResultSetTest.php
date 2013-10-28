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
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Configure;
use Cake\Database\ConnectionManager;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * ResultSet test case.
 */
class ResultSetTest extends TestCase {

	public $fixtures = ['core.article'];

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
 * @expectedException Cake\Database\Exception
 * @return void
 */
	public function testRewind() {
		$query = $this->table->find('all');
		$results = $query->execute();
		$first = $second = [];
		foreach ($results as $result) {
			$first[] = $result;
		}
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
		$results = $query->execute();
		$expected = $results->toArray();

		$query2 = $this->table->find('all');
		$results2 = $query2->execute();
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
		$results = unserialize(serialize($query->execute()));

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
		$results = unserialize(serialize($query->execute()));

		// Use a loop to test Iterator implementation
		foreach ($results as $i => $row) {
			$this->assertEquals(new \Cake\ORM\Entity($this->fixtureData[$i]), $row, "Row $i does not match");
		}
	}

/**
 * Test converting resultsets into json
 *
 * @return void
 */
	public function testJsonSerialize() {
		$query = $this->table->find('all');
		$results = $query->execute();

		$expected = json_encode($this->fixtureData);
		$this->assertEquals($expected, json_encode($results));
	}

}
