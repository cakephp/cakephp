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
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\Test\Fixture\PostsFixture;
use Cake\TestSuite\TestCase;
use Exception;
use TestApp\Test\Fixture\ArticlesFixture;
use TestApp\Test\Fixture\FeaturedTagsFixture;
use TestApp\Test\Fixture\ImportsFixture;
use TestApp\Test\Fixture\LettersFixture;
use TestApp\Test\Fixture\StringsTestsFixture;

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
    protected $fixtures = ['core.Posts'];

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
        $Fixture->table = null;
        $Fixture->init();
        $this->assertSame('articles', $Fixture->table);

        $schema = $Fixture->getTableSchema();
        $this->assertInstanceOf('Cake\Database\Schema\TableSchema', $schema);

        $fields = $Fixture->fields;
        unset($fields['_constraints'], $fields['_indexes']);
        $this->assertSame(
            array_keys($fields),
            $schema->columns(),
            'Fields do not match'
        );
        $this->assertSame(array_keys($Fixture->fields['_constraints']), $schema->constraints());
        $this->assertEmpty($schema->indexes());
    }

    /**
     * test import fixture initialization
     */
    public function testInitImport(): void
    {
        $fixture = new ImportsFixture();
        $fixture->fields = $fixture->records = null;
        $fixture->import = [
            'table' => 'posts',
            'connection' => 'test',
        ];
        $fixture->init();

        $expected = [
            'id',
            'author_id',
            'title',
            'body',
            'published',
        ];
        $this->assertSame($expected, $fixture->getTableSchema()->columns());
    }

    /**
     * test import fixture initialization
     */
    public function testInitImportModel(): void
    {
        $fixture = new ImportsFixture();
        $fixture->fields = $fixture->records = null;
        $fixture->import = [
            'model' => 'Posts',
            'connection' => 'test',
        ];
        $fixture->init();

        $expected = [
            'id',
            'author_id',
            'title',
            'body',
            'published',
        ];
        $this->assertSame($expected, $fixture->getTableSchema()->columns());
    }

    /**
     * test schema reflection without $import or $fields and without the table existing
     * it will throw an exception
     */
    public function testInitNoImportNoFieldsException(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Cannot describe schema for table `letters` for fixture `' . LettersFixture::class . '`. The table does not exist.');
        $fixture = new LettersFixture();
    }

    /**
     * test schema reflection without $import or $fields will reflect the schema
     */
    public function testInitNoImportNoFields(): void
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
     * test schema reflection without $import or $fields will reflect the schema
     */
    public function testInitReflectSchemaCustomTypes(): void
    {
        $db = ConnectionManager::get('test');
        $table = new TableSchema('letters', [
            'id' => ['type' => 'integer'],
            'letter' => ['type' => 'string', 'length' => 1],
            'complex_field' => ['type' => 'json'],
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
     * test init with other tables used in initialize()
     *
     * The FeaturedTagsTable uses PostsTable, then when PostsFixture
     * reflects schema it should not raise an error.
     */
    public function testInitInitializeUsesRegistry(): void
    {
        $this->setAppNamespace();

        $fixture = new FeaturedTagsFixture();

        $posts = new PostsFixture();
        $posts->fields = [];
        $posts->init();

        $expected = ['tag_id', 'priority'];
        $this->assertSame($expected, $fixture->getTableSchema()->columns());
    }

    /**
     * test create method
     */
    public function testCreate(): void
    {
        $fixture = new ArticlesFixture();
        $db = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $table = $this->getMockBuilder('Cake\Database\Schema\TableSchema')
            ->setConstructorArgs(['articles'])
            ->getMock();
        $table->expects($this->once())
            ->method('createSql')
            ->with($db)
            ->will($this->returnValue(['sql', 'sql']));
        $fixture->setTableSchema($table);

        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->atLeastOnce())->method('closeCursor');
        $statement->expects($this->atLeastOnce())->method('execute');

        $db->expects($this->exactly(2))
            ->method('prepare')
            ->will($this->returnValue($statement));
        $this->assertTrue($fixture->create($db));
    }

    /**
     * test create method, trigger error
     */
    public function testCreateError(): void
    {
        $this->expectError();
        $fixture = new ArticlesFixture();
        $db = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $table = $this->getMockBuilder('Cake\Database\Schema\TableSchema')
            ->setConstructorArgs(['articles'])
            ->getMock();
        $table->expects($this->once())
            ->method('createSql')
            ->with($db)
            ->will($this->throwException(new Exception('oh noes')));
        $fixture->setTableSchema($table);

        $fixture->create($db);
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
            ->with(['name', 'created'], ['name' => 'string', 'created' => 'datetime'])
            ->will($this->returnSelf());

        $query->expects($this->once())
            ->method('into')
            ->with('articles')
            ->will($this->returnSelf());

        $expected = [
            ['name' => 'Gandalf', 'created' => '2009-04-28 19:20:00'],
            ['name' => 'Captain Picard', 'created' => '2009-04-28 19:20:00'],
            ['name' => 'Chewbacca', 'created' => '2009-04-28 19:20:00'],
        ];
        $query->expects($this->exactly(3))
            ->method('values')
            ->withConsecutive(
                [$expected[0]],
                [$expected[1]],
                [$expected[2]]
            )
            ->will($this->returnSelf());

        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->once())->method('closeCursor');

        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($statement));

        $this->assertSame($statement, $fixture->insert($db));
    }

    /**
     * test the insert method
     */
    public function testInsertImport(): void
    {
        $fixture = new ImportsFixture();

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
            ->with(['title', 'body'], ['title' => 'string', 'body' => 'text'])
            ->will($this->returnSelf());

        $query->expects($this->once())
            ->method('into')
            ->with('posts')
            ->will($this->returnSelf());

        $expected = [
            ['title' => 'Hello!', 'body' => 'Hello world!'],
        ];
        $query->expects($this->once())
            ->method('values')
            ->with($expected[0])
            ->will($this->returnSelf());

        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->once())->method('closeCursor');

        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($statement));

        $this->assertSame($statement, $fixture->insert($db));
    }

    /**
     * test the insert method
     */
    public function testInsertStrings(): void
    {
        $fixture = new StringsTestsFixture();

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
            ->with(['name', 'email', 'age'], ['name' => 'string', 'email' => 'string', 'age' => 'integer'])
            ->will($this->returnSelf());

        $query->expects($this->once())
            ->method('into')
            ->with('strings')
            ->will($this->returnSelf());

        $expected = [
            ['name' => 'Mark Doe', 'email' => 'mark.doe@email.com', 'age' => null],
            ['name' => 'John Doe', 'email' => 'john.doe@email.com', 'age' => 20],
            ['name' => 'Jane Doe', 'email' => 'jane.doe@email.com', 'age' => 30],
        ];
        $query->expects($this->exactly(3))
            ->method('values')
            ->withConsecutive(
                [$expected[0]],
                [$expected[1]],
                [$expected[2]]
            )
            ->will($this->returnSelf());

        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->once())->method('closeCursor');

        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($statement));

        $this->assertSame($statement, $fixture->insert($db));
    }

    /**
     * Test the drop method
     */
    public function testDrop(): void
    {
        $fixture = new ArticlesFixture();

        $db = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->once())->method('closeCursor');
        $db->expects($this->once())->method('execute')
            ->with('sql')
            ->will($this->returnValue($statement));

        $table = $this->getMockBuilder('Cake\Database\Schema\TableSchema')
            ->setConstructorArgs(['articles'])
            ->getMock();
        $table->expects($this->once())
            ->method('dropSql')
            ->with($db)
            ->will($this->returnValue(['sql']));
        $fixture->setTableSchema($table);

        $this->assertTrue($fixture->drop($db));
    }

    /**
     * Test the truncate method.
     */
    public function testTruncate(): void
    {
        $fixture = new ArticlesFixture();

        $db = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->once())->method('closeCursor');

        $db->expects($this->once())->method('execute')
            ->with('sql')
            ->will($this->returnValue($statement));

        $table = $this->getMockBuilder('Cake\Database\Schema\TableSchema')
            ->setConstructorArgs(['articles'])
            ->getMock();
        $table->expects($this->once())
            ->method('truncateSql')
            ->with($db)
            ->will($this->returnValue(['sql']));
        $fixture->setTableSchema($table);

        $this->assertTrue($fixture->truncate($db));
    }
}
