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

use Cake\Database\Exception\DatabaseException;
use Cake\Database\Log\QueryLogger;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
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
    protected $fixtures = ['core.Articles', 'core.Authors', 'core.Comments'];

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
     * Test that streaming results cannot be rewound
     */
    public function testRewindStreaming(): void
    {
        $query = $this->table->find('all')->enableBufferedResults(false);
        $results = $query->all();
        $first = $second = [];
        foreach ($results as $result) {
            $first[] = $result;
        }
        $this->expectException(DatabaseException::class);
        foreach ($results as $result) {
            $second[] = $result;
        }
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
     * Test that eagerLoader leaves empty associations unpopulated.
     */
    public function testBelongsToEagerLoaderLeavesEmptyAssociation(): void
    {
        $comments = $this->getTableLocator()->get('Comments');
        $comments->belongsTo('Articles');

        // Clear the articles table so we can trigger an empty belongsTo
        $this->table->deleteAll([]);

        $comment = $comments->find()->where(['Comments.id' => 1])
            ->contain(['Articles'])
            ->enableHydration(false)
            ->first();
        $this->assertSame(1, $comment['id']);
        $this->assertNotEmpty($comment['comment']);
        $this->assertNull($comment['article']);

        $comment = $comments->get(1, ['contain' => ['Articles']]);
        $this->assertNull($comment->article);
        $this->assertSame(1, $comment->id);
        $this->assertNotEmpty($comment->comment);
    }

    /**
     * Test showing associated record is preserved when selecting only field with
     * null value if auto fields is disabled.
     */
    public function testBelongsToEagerLoaderWithAutoFieldsFalse(): void
    {
        $authors = $this->getTableLocator()->get('Authors');

        $author = $authors->newEntity(['name' => null]);
        $authors->save($author);

        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsTo('Authors');

        $article = $articles->newEntity([
            'author_id' => $author->id,
            'title' => 'article with author with null name',
        ]);
        $articles->save($article);

        $result = $articles->find()
            ->select(['Articles.id', 'Articles.title', 'Authors.name'])
            ->contain(['Authors'])
            ->where(['Articles.id' => $article->id])
            ->disableAutoFields()
            ->enableHydration(false)
            ->first();

        $this->assertNotNull($result['author']);
    }

    /**
     * Test that eagerLoader leaves empty associations unpopulated.
     */
    public function testHasOneEagerLoaderLeavesEmptyAssociation(): void
    {
        $this->table->hasOne('Comments');

        // Clear the comments table so we can trigger an empty hasOne.
        $comments = $this->getTableLocator()->get('Comments');
        $comments->deleteAll([]);

        $article = $this->table->get(1, ['contain' => ['Comments']]);
        $this->assertNull($article->comment);
        $this->assertSame(1, $article->id);
        $this->assertNotEmpty($article->title);

        $article = $this->table->find()->where(['articles.id' => 1])
            ->contain(['Comments'])
            ->enableHydration(false)
            ->first();
        $this->assertNull($article['comment']);
        $this->assertSame(1, $article['id']);
        $this->assertNotEmpty($article['title']);
    }

    /**
     * Test that fetching rows does not fail when no fields were selected
     * on the default alias.
     */
    public function testFetchMissingDefaultAlias(): void
    {
        $comments = $this->getTableLocator()->get('Comments');
        $query = $comments->find()->select(['Other__field' => 'test']);
        $query->disableAutoFields();

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
     */
    public function testSourceOnContainAssociations(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $comments = $this->getTableLocator()->get('TestPlugin.Comments');
        $comments->belongsTo('Authors', [
            'className' => 'TestPlugin.Authors',
            'foreignKey' => 'user_id',
        ]);
        $result = $comments->find()->contain(['Authors'])->first();
        $this->assertSame('TestPlugin.Comments', $result->getSource());
        $this->assertSame('TestPlugin.Authors', $result->author->getSource());

        $result = $comments->find()->matching('Authors', function ($q) {
            return $q->where(['Authors.id' => 1]);
        })->first();
        $this->assertSame('TestPlugin.Comments', $result->getSource());
        $this->assertSame('TestPlugin.Authors', $result->_matchingData['Authors']->getSource());
        $this->clearPlugins();
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
        ])->group('author_id');

        $min = $query->all()->min('counter');
        $max = $query->all()->max('counter');

        $this->assertTrue($max > $min);
    }

    /**
     * @see https://github.com/cakephp/cakephp/issues/14676
     */
    public function testQueryLoggingForSelectsWithZeroRows(): void
    {
        Log::setConfig('queries', ['className' => 'Array']);

        $defaultLogger = $this->connection->getLogger();
        $queryLogging = $this->connection->isQueryLoggingEnabled();

        $logger = new QueryLogger();
        $this->connection->setLogger($logger)->enableQueryLogging(true);

        $messages = Log::engine('queries')->read();
        $this->assertCount(0, $messages);

        $results = $this->table->find('all')
            ->enableBufferedResults()
            ->where(['id' => 0])
            ->all();

        $this->assertCount(0, $results);

        $messages = Log::engine('queries')->read();
        $message = array_pop($messages);
        $this->assertStringContainsString('SELECT', $message);

        $this->connection->setLogger($defaultLogger)->enableQueryLogging($queryLogging);
        Log::reset();
    }
}
