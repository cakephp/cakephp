<?php
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

use Cake\Core\Plugin;
use Cake\Database\Exception;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * ResultSet test case.
 */
class ResultSetTest extends TestCase
{

    public $fixtures = ['core.articles', 'core.authors', 'core.comments'];

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
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
            ['id' => 3, 'author_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y']
        ];
    }

    /**
     * Test that result sets can be rewound and re-used.
     *
     * @return void
     */
    public function testRewind()
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
     * Test that streaming results cannot be rewound
     *
     * @return void
     */
    public function testRewindStreaming()
    {
        $query = $this->table->find('all')->enableBufferedResults(false);
        $results = $query->all();
        $first = $second = [];
        foreach ($results as $result) {
            $first[] = $result;
        }
        $this->expectException(Exception::class);
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
    public function testSerialization()
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
     *
     * @return void
     */
    public function testIteratorAfterSerializationNoHydration()
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
     *
     * @return void
     */
    public function testIteratorAfterSerializationHydrated()
    {
        $query = $this->table->find('all');
        $results = unserialize(serialize($query->all()));

        // Use a loop to test Iterator implementation
        foreach ($results as $i => $row) {
            $expected = new Entity($this->fixtureData[$i]);
            $expected->isNew(false);
            $expected->setSource($this->table->getAlias());
            $expected->clean();
            $this->assertEquals($expected, $row, "Row $i does not match");
        }
    }

    /**
     * Test converting resultsets into json
     *
     * @return void
     */
    public function testJsonSerialize()
    {
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
    public function testFirst()
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
     *
     * @return void
     */
    public function testFirstAfterSerialize()
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
     *
     * @return void
     */
    public function testCount()
    {
        $query = $this->table->find('all');
        $results = $query->all();

        $this->assertCount(3, $results, 'Should be countable and 3');
    }

    /**
     * Test the countable interface after unserialize
     *
     * @return void
     */
    public function testCountAfterSerialize()
    {
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
    public function testGroupBy()
    {
        $query = $this->table->find('all');
        $results = $query->all()->groupBy('author_id')->toArray();
        $options = [
            'markNew' => false,
            'markClean' => true,
            'source' => $this->table->getAlias()
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
    public function testDebugInfo()
    {
        $query = $this->table->find('all');
        $results = $query->all();
        $expected = [
            'items' => $results->toArray()
        ];
        $this->assertSame($expected, $results->__debugInfo());
    }

    /**
     * Test that eagerLoader leaves empty associations unpopulated.
     *
     * @return void
     */
    public function testBelongsToEagerLoaderLeavesEmptyAssociation()
    {
        $comments = $this->getTableLocator()->get('Comments');
        $comments->belongsTo('Articles');

        // Clear the articles table so we can trigger an empty belongsTo
        $this->table->deleteAll([]);

        $comment = $comments->find()->where(['Comments.id' => 1])
            ->contain(['Articles'])
            ->enableHydration(false)
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
     * Test showing associated record is preserved when selecting only field with
     * null value if auto fields is disabled.
     *
     * @return void
     */
    public function testBelongsToEagerLoaderWithAutoFieldsFalse()
    {
        $authors = $this->getTableLocator()->get('Authors');

        $author = $authors->newEntity(['name' => null]);
        $authors->save($author);

        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsTo('Authors');

        $article = $articles->newEntity([
            'author_id' => $author->id,
            'title' => 'article with author with null name'
        ]);
        $articles->save($article);

        $result = $articles->find()
            ->select(['Articles.id', 'Articles.title', 'Authors.name'])
            ->contain(['Authors'])
            ->where(['Articles.id' => $article->id])
            ->enableAutoFields(false)
            ->enableHydration(false)
            ->first();

        $this->assertNotNull($result['author']);
    }

    /**
     * Test that eagerLoader leaves empty associations unpopulated.
     *
     * @return void
     */
    public function testHasOneEagerLoaderLeavesEmptyAssociation()
    {
        $this->table->hasOne('Comments');

        // Clear the comments table so we can trigger an empty hasOne.
        $comments = $this->getTableLocator()->get('Comments');
        $comments->deleteAll([]);

        $article = $this->table->get(1, ['contain' => ['Comments']]);
        $this->assertNull($article->comment);
        $this->assertEquals(1, $article->id);
        $this->assertNotEmpty($article->title);

        $article = $this->table->find()->where(['articles.id' => 1])
            ->contain(['Comments'])
            ->enableHydration(false)
            ->first();
        $this->assertNull($article['comment']);
        $this->assertEquals(1, $article['id']);
        $this->assertNotEmpty($article['title']);
    }

    /**
     * Test that fetching rows does not fail when no fields were selected
     * on the default alias.
     *
     * @return void
     */
    public function testFetchMissingDefaultAlias()
    {
        $comments = $this->getTableLocator()->get('Comments');
        $query = $comments->find()->select(['Other__field' => 'test']);
        $query->enableAutoFields(false);

        $row = ['Other__field' => 'test'];
        $statement = $this->getMockBuilder('Cake\Database\StatementInterface')->getMock();
        $statement->method('fetch')
            ->will($this->onConsecutiveCalls($row, $row));
        $statement->method('rowCount')
            ->will($this->returnValue(1));

        $result = new ResultSet($query, $statement);

        $result->valid();
        $this->assertNotEmpty($result->current());
    }

    /**
     * Test that associations have source() correctly set.
     *
     * @return void
     */
    public function testSourceOnContainAssociations()
    {
        Plugin::load('TestPlugin');
        $comments = $this->getTableLocator()->get('TestPlugin.Comments');
        $comments->belongsTo('Authors', [
            'className' => 'TestPlugin.Authors',
            'foreignKey' => 'user_id'
        ]);
        $result = $comments->find()->contain(['Authors'])->first();
        $this->assertEquals('TestPlugin.Comments', $result->getSource());
        $this->assertEquals('TestPlugin.Authors', $result->author->getSource());

        $result = $comments->find()->matching('Authors', function ($q) {
            return $q->where(['Authors.id' => 1]);
        })->first();
        $this->assertEquals('TestPlugin.Comments', $result->getSource());
        $this->assertEquals('TestPlugin.Authors', $result->_matchingData['Authors']->getSource());
    }

    /**
     * Ensure that isEmpty() on a ResultSet doesn't result in loss
     * of records. This behavior is provided by CollectionTrait.
     *
     * @return void
     */
    public function testIsEmptyDoesNotConsumeData()
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
}
