<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * ResultSet test case.
 */
class ResultSetTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = ['core.Articles', 'core.Authors', 'core.Comments'];

    /**
     * @var \Cake\ORM\Table
     */
    protected $table;

    /**
     * @var array
     */
    protected $fixtureData;

    /**
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $connection;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->table = new Table([
            'table' => 'articles',
            'connection' => $this->connection,
        ]);

        $this->fixtureData = [
            ['id' => 1, 'author_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y'],
            ['id' => 2, 'author_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y'],
            ['id' => 3, 'author_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y'],
        ];
    }

    /**
     * Test that result sets can be rewound and re-used.
     */
    public function testRewind(): void
    {
        $query = $this->table->find('all');
        $results = $query->all();
        $first = $second = [];
        foreach ($results as $result) {
            $first[] = $result;
        }
        foreach ($results as $result) {
            $second[] = $result;
        }
        $this->assertEquals($first, $second);
    }

    /**
     * An integration test for testing serialize and unserialize features.
     *
     * Compare the results of a query with the results iterated, with
     * those of a different query that have been serialized/unserialized.
     */
    public function testSerialization(): void
    {
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
     */
    public function testIteratorAfterSerializationNoHydration(): void
    {
        $query = $this->table->find('all')->enableHydration(false);
        $results = unserialize(serialize($query->all()));

        // Use a loop to test Iterator implementation
        foreach ($results as $i => $row) {
            $this->assertEquals($this->fixtureData[$i], $row, "Row $i does not match");
        }
    }

    /**
     * Test iteration after serialization
     */
    public function testIteratorAfterSerializationHydrated(): void
    {
        $query = $this->table->find('all');
        $results = unserialize(serialize($query->all()));

        // Use a loop to test Iterator implementation
        foreach ($results as $i => $row) {
            $expected = new Entity($this->fixtureData[$i]);
            $expected->setNew(false);
            $expected->setSource($this->table->getAlias());
            $expected->clean();
            $this->assertEquals($expected, $row, "Row $i does not match");
        }
    }

    /**
     * Test converting resultsets into JSON
     */
    public function testJsonSerialize(): void
    {
        $query = $this->table->find('all');
        $results = $query->all();

        $expected = json_encode($this->fixtureData);
        $this->assertEquals($expected, json_encode($results));
    }

    /**
     * Test first() method with a statement backed result set.
     */
    public function testFirst(): void
    {
        $query = $this->table->find('all');
        $results = $query->enableHydration(false)->all();

        $row = $results->first();
        $this->assertEquals($this->fixtureData[0], $row);

        $row = $results->first();
        $this->assertEquals($this->fixtureData[0], $row);
    }

    /**
     * Test first() method with a result set that has been unserialized
     */
    public function testFirstAfterSerialize(): void
    {
        $query = $this->table->find('all');
        $results = $query->enableHydration(false)->all();
        $results = unserialize(serialize($results));

        $row = $results->first();
        $this->assertEquals($this->fixtureData[0], $row);

        $this->assertSame($row, $results->first());
        $this->assertSame($row, $results->first());
    }

    /**
     * Test the countable interface.
     */
    public function testCount(): void
    {
        $query = $this->table->find('all');
        $results = $query->all();

        $this->assertCount(3, $results, 'Should be countable and 3');
    }

    /**
     * Test the countable interface after unserialize
     */
    public function testCountAfterSerialize(): void
    {
        $query = $this->table->find('all');
        $results = $query->all();
        $results = unserialize(serialize($results));

        $this->assertCount(3, $results, 'Should be countable and 3');
    }

    /**
     * Integration test to show methods from CollectionTrait work
     */
    public function testGroupBy(): void
    {
        $query = $this->table->find('all');
        $results = $query->all()->groupBy('author_id')->toArray();
        $options = [
            'markNew' => false,
            'markClean' => true,
            'source' => $this->table->getAlias(),
        ];
        $expected = [
            1 => [
                new Entity($this->fixtureData[0], $options),
                new Entity($this->fixtureData[2], $options),
            ],
            3 => [
                new Entity($this->fixtureData[1], $options),
            ],
        ];
        $this->assertEquals($expected, $results);
    }

    /**
     * Tests __debugInfo
     */
    public function testDebugInfo(): void
    {
        $query = $this->table->find('all');
        $results = $query->all();
        $expected = [
            'items' => $results->toArray(),
        ];
        $this->assertSame($expected, $results->__debugInfo());
    }

    /**
     * Ensure that isEmpty() on a ResultSet doesn't result in loss
     * of records. This behavior is provided by CollectionTrait.
     */
    public function testIsEmptyDoesNotConsumeData(): void
    {
        $table = $this->getTableLocator()->get('Comments');
        $query = $table->find()
            ->formatResults(function ($results) {
                return $results;
            });
        $res = $query->all();
        $res->isEmpty();
        $this->assertCount(6, $res->toArray());
    }

    /**
     * Test that ResultSet
     */
    public function testCollectionMinAndMax(): void
    {
        $query = $this->table->find('all');

        $min = $query->all()->min('id');
        $minExpected = $this->table->get(1);

        $max = $query->all()->max('id');
        $maxExpected = $this->table->get(3);

        $this->assertEquals($minExpected, $min);
        $this->assertEquals($maxExpected, $max);
    }

    /**
     * Test that ResultSet
     */
    public function testCollectionMinAndMaxWithAggregateField(): void
    {
        $query = $this->table->find();
        $query->select([
            'counter' => 'COUNT(*)',
        ])->groupBy('author_id');

        $min = $query->all()->min('counter');
        $max = $query->all()->max('counter');

        $this->assertTrue($max > $min);
    }
}
