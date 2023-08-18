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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Database\Log\QueryLogger;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\ORM\ResultSetFactory;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * ResultSetFactory test case.
 */
class ResultSetFactoryTest extends TestCase
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
     * @var \Cake\ORM\ResultSetFactory
     */
    protected $factory;

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
        $this->factory = new ResultSetFactory();

        $this->fixtureData = [
            ['id' => 1, 'author_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y'],
            ['id' => 2, 'author_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y'],
            ['id' => 3, 'author_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y'],
        ];
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

        $comment = $comments->get(1, ...['contain' => ['Articles']]);
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

        $article = $this->table->get(1, ...['contain' => ['Comments']]);
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
        $statement = $this->createMock(StatementInterface::class);
        $statement->method('fetchAll')
            ->willReturn([$row]);

        $results = $this->factory->createResultSet($query, $statement->fetchAll());
        $this->assertNotEmpty($results);
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
     * @see https://github.com/cakephp/cakephp/issues/14676
     */
    public function testQueryLoggingForSelectsWithZeroRows(): void
    {
        Log::setConfig('queries', ['className' => 'Array']);

        $logger = new QueryLogger();
        $this->connection->getDriver()->setLogger($logger);

        $messages = Log::engine('queries')->read();
        $this->assertCount(0, $messages);

        $results = $this->table->find('all')
            ->where(['id' => 0])
            ->all();

        $this->assertCount(0, $results);

        $messages = Log::engine('queries')->read();
        $message = array_pop($messages);
        $this->assertStringContainsString('SELECT', $message);

        Log::reset();
    }
}
