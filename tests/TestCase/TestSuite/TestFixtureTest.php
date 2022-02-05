<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Core\Exception\CakeException;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Statement;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\Test\Fixture\ArticlesFixture;
use Cake\TestSuite\TestCase;
use TestApp\Test\Fixture\LettersFixture;

/**
 * Test case for TestFixture
 */
class TestFixtureTest extends TestCase
{
    /**
     * Fixtures for this test.
     *
     * @var array<string>
     */
    protected array $fixtures = ['core.Articles', 'core.Posts'];

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::reset();
    }

    /**
     * Tear down
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Log::reset();
        ConnectionManager::get('test')->execute('DROP TABLE IF EXISTS letters');
    }

    /**
     * test initializing a static fixture
     */
    public function testInitStaticFixture(): void
    {
        $Fixture = new ArticlesFixture();
        $this->assertSame('articles', $Fixture->table);

        $Fixture = new ArticlesFixture();
        $Fixture->table = '';
        $Fixture->init();
        $this->assertSame('articles', $Fixture->table);

        $schema = $Fixture->getTableSchema();
        $this->assertInstanceOf('Cake\Database\Schema\TableSchema', $schema);
    }

    /**
     * Tests that trying to reflect with a table that doesn't exist throws an exception.
     */
    public function testReflectionMissingTable(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Cannot describe schema for table `letters` for fixture `%s`. The table does not exist.',
                LettersFixture::class
            ),
        );

        new LettersFixture();
    }

    /**
     * Tests schema reflection.
     */
    public function testReflection(): void
    {
        $db = ConnectionManager::get('test');
        $table = new TableSchema('letters', [
            'id' => ['type' => 'integer'],
            'letter' => ['type' => 'string', 'length' => 1],
        ]);
        $table->addConstraint('primary', ['type' => 'primary', 'columns' => ['id']]);
        $sql = $table->createSql($db);

        foreach ($sql as $stmt) {
            $db->execute($stmt);
        }

        $fixture = new LettersFixture();
        $this->assertSame(['id', 'letter'], $fixture->getTableSchema()->columns());
    }

    /**
     * Tests that schema reflection picks up dynamically configured column types.
     */
    public function testReflectionWithDynamicTypes(): void
    {
        $db = ConnectionManager::get('test');
        $table = new TableSchema('letters', [
            'id' => ['type' => 'integer'],
            'letter' => ['type' => 'string', 'length' => 1],
            'complex_field' => ['type' => 'text'],
        ]);
        $table->addConstraint('primary', ['type' => 'primary', 'columns' => ['id']]);
        $sql = $table->createSql($db);

        foreach ($sql as $stmt) {
            $db->execute($stmt);
        }

        $table = $this->fetchTable('Letters', ['connection' => $db]);
        $table->getSchema()->setColumnType('complex_field', 'json');

        $fixture = new LettersFixture();
        $fixtureSchema = $fixture->getTableSchema();
        $this->assertSame(['id', 'letter', 'complex_field'], $fixtureSchema->columns());
        $this->assertSame('json', $fixtureSchema->getColumnType('complex_field'));
    }

    /**
     * test the insert method
     */
    public function testInsert(): void
    {
        $fixture = new ArticlesFixture();

        $db = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Cake\Database\Query')
            ->setConstructorArgs([$db])
            ->getMock();
        $db->expects($this->once())
            ->method('newQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('insert')
            ->with(['author_id', 'title', 'body', 'published'], ['author_id' => 'integer', 'title' => 'string', 'body' => 'text', 'published' => 'string'])
            ->will($this->returnSelf());

        $query->expects($this->once())
            ->method('into')
            ->with('articles')
            ->will($this->returnSelf());

        $expected = [
            ['author_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y'],
            ['author_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y'],
            ['author_id' => 1, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y'],
        ];
        $query->expects($this->exactly(3))
            ->method('values')
            ->withConsecutive(
                [$expected[0]],
                [$expected[1]],
                [$expected[2]]
            )
            ->will($this->returnSelf());

        $statement = $this->createMock(Statement::class);
        $statement->expects($this->once())->method('closeCursor');

        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($statement));

        $this->assertSame($statement, $fixture->insert($db));
    }

    /**
     * Test the truncate method.
     */
    public function testTruncate(): void
    {
        $fixture = new ArticlesFixture();
        $articles = $this->getTableLocator()->get('Articles');

        $this->assertTrue($fixture->truncate(ConnectionManager::get('test')));
        $this->assertEmpty($articles->find()->all());
    }
}
