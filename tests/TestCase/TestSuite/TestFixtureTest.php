<?php
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

use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\TestSuite\Fixture\TestFixture;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * ArticlesFixture class
 */
class ArticlesFixture extends TestFixture
{

    /**
     * Table property
     *
     * @var string
     */
    public $table = 'articles';

    /**
     * Fields array
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'name' => ['type' => 'string', 'length' => '255'],
        'created' => ['type' => 'datetime'],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']]
        ]
    ];

    /**
     * Records property
     *
     * @var array
     */
    public $records = [
        ['name' => 'Gandalf', 'created' => '2009-04-28 19:20:00'],
        ['name' => 'Captain Picard', 'created' => '2009-04-28 19:20:00'],
        ['name' => 'Chewbacca', 'created' => '2009-04-28 19:20:00']
    ];
}

/**
 * StringsTestsFixture class
 */
class StringsTestsFixture extends TestFixture
{

    /**
     * Table property
     *
     * @var string
     */
    public $table = 'strings';

    /**
     * Fields array
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'name' => ['type' => 'string', 'length' => '255'],
        'email' => ['type' => 'string', 'length' => '255'],
        'age' => ['type' => 'integer', 'default' => 10]
    ];

    /**
     * Records property
     *
     * @var array
     */
    public $records = [
        ['name' => 'Mark Doe', 'email' => 'mark.doe@email.com'],
        ['name' => 'John Doe', 'email' => 'john.doe@email.com', 'age' => 20],
        ['email' => 'jane.doe@email.com', 'name' => 'Jane Doe', 'age' => 30]
    ];
}

/**
 * ImportsFixture class
 */
class ImportsFixture extends TestFixture
{

    /**
     * Import property
     *
     * @var mixed
     */
    public $import = ['table' => 'posts', 'connection' => 'test'];

    /**
     * Records property
     *
     * @var array
     */
    public $records = [
        ['title' => 'Hello!', 'body' => 'Hello world!']
    ];
}

/**
 * This class allows testing the fixture data insertion when the properties
 * $fields and $import are not set
 */
class LettersFixture extends TestFixture
{

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['letter' => 'a'],
        ['letter' => 'b'],
        ['letter' => 'c']
    ];
}

/**
 * Test case for TestFixture
 */
class TestFixtureTest extends TestCase
{

    /**
     * Fixtures for this test.
     *
     * @var array
     */
    public $fixtures = ['core.Posts'];

    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Log::reset();
    }

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Log::reset();
    }

    /**
     * test initializing a static fixture
     *
     * @return void
     */
    public function testInitStaticFixture()
    {
        $Fixture = new ArticlesFixture();
        $this->assertEquals('articles', $Fixture->table);

        $Fixture = new ArticlesFixture();
        $Fixture->table = null;
        $Fixture->init();
        $this->assertEquals('articles', $Fixture->table);

        $schema = $Fixture->getTableSchema();
        $this->assertInstanceOf('Cake\Database\Schema\TableSchema', $schema);

        $fields = $Fixture->fields;
        unset($fields['_constraints'], $fields['_indexes']);
        $this->assertEquals(
            array_keys($fields),
            $schema->columns(),
            'Fields do not match'
        );
        $this->assertEquals(array_keys($Fixture->fields['_constraints']), $schema->constraints());
        $this->assertEmpty($schema->indexes());
    }

    /**
     * test import fixture initialization
     *
     * @return void
     */
    public function testInitImport()
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
        $this->assertEquals($expected, $fixture->getTableSchema()->columns());
    }

    /**
     * test import fixture initialization
     *
     * @return void
     */
    public function testInitImportModel()
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
        $this->assertEquals($expected, $fixture->getTableSchema()->columns());
    }

    /**
     * test schema reflection without $import or $fields and without the table existing
     * it will throw an exception
     *
     * @return void
     */
    public function testInitNoImportNoFieldsException()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $this->expectExceptionMessage('Cannot describe schema for table `letters` for fixture `Cake\Test\TestCase\TestSuite\LettersFixture` : the table does not exist.');
        $fixture = new LettersFixture();
        $fixture->init();
    }

    /**
     * test schema reflection without $import or $fields will reflect the schema
     *
     * @return void
     */
    public function testInitNoImportNoFields()
    {
        $db = ConnectionManager::get('test');
        $table = new TableSchema('letters', [
            'id' => ['type' => 'integer'],
            'letter' => ['type' => 'string', 'length' => 1]
        ]);
        $table->addConstraint('primary', ['type' => 'primary', 'columns' => ['id']]);
        $sql = $table->createSql($db);

        foreach ($sql as $stmt) {
            $db->execute($stmt);
        }

        $fixture = new LettersFixture();
        $fixture->init();
        $this->assertEquals(['id', 'letter'], $fixture->getTableSchema()->columns());

        $db = $this->getMockBuilder('Cake\Database\Connection')
            ->setMethods(['prepare', 'execute'])
            ->disableOriginalConstructor()
            ->getMock();
        $db->expects($this->never())
            ->method('prepare');
        $db->expects($this->never())
            ->method('execute');
        $this->assertTrue($fixture->create($db));
        $this->assertTrue($fixture->drop($db));

        // Cleanup.
        $db = ConnectionManager::get('test');
        $db->execute('DROP TABLE letters');
    }

    /**
     * test create method
     *
     * @return void
     */
    public function testCreate()
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

        $statement = $this->getMockBuilder('\PDOStatement')
            ->setMethods(['execute', 'closeCursor'])
            ->getMock();
        $statement->expects($this->atLeastOnce())->method('closeCursor');
        $statement->expects($this->atLeastOnce())->method('execute');
        $db->expects($this->exactly(2))
            ->method('prepare')
            ->will($this->returnValue($statement));
        $this->assertTrue($fixture->create($db));
    }

    /**
     * test create method, trigger error
     *
     * @return void
     */
    public function testCreateError()
    {
        $this->expectException(\PHPUnit\Framework\Error\Error::class);
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
     *
     * @return void
     */
    public function testInsert()
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
            ['name' => 'Chewbacca', 'created' => '2009-04-28 19:20:00']
        ];
        $query->expects($this->at(2))
            ->method('values')
            ->with($expected[0])
            ->will($this->returnSelf());
        $query->expects($this->at(3))
            ->method('values')
            ->with($expected[1])
            ->will($this->returnSelf());
        $query->expects($this->at(4))
            ->method('values')
            ->with($expected[2])
            ->will($this->returnSelf());

        $statement = $this->getMockBuilder('\PDOStatement')
            ->setMethods(['closeCursor'])
            ->getMock();
        $statement->expects($this->once())->method('closeCursor');
        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($statement));

        $this->assertSame($statement, $fixture->insert($db));
    }

    /**
     * test the insert method
     *
     * @return void
     */
    public function testInsertImport()
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
        $query->expects($this->at(2))
            ->method('values')
            ->with($expected[0])
            ->will($this->returnSelf());

        $statement = $this->getMockBuilder('\PDOStatement')
            ->setMethods(['closeCursor'])
            ->getMock();
        $statement->expects($this->once())->method('closeCursor');
        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($statement));

        $this->assertSame($statement, $fixture->insert($db));
    }

    /**
     * test the insert method
     *
     * @return void
     */
    public function testInsertStrings()
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
        $query->expects($this->at(2))
            ->method('values')
            ->with($expected[0])
            ->will($this->returnSelf());
        $query->expects($this->at(3))
            ->method('values')
            ->with($expected[1])
            ->will($this->returnSelf());
        $query->expects($this->at(4))
            ->method('values')
            ->with($expected[2])
            ->will($this->returnSelf());

        $statement = $this->getMockBuilder('\PDOStatement')
            ->setMethods(['closeCursor'])
            ->getMock();
        $statement->expects($this->once())->method('closeCursor');
        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($statement));

        $this->assertSame($statement, $fixture->insert($db));
    }

    /**
     * Test the drop method
     *
     * @return void
     */
    public function testDrop()
    {
        $fixture = new ArticlesFixture();

        $db = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $statement = $this->getMockBuilder('\PDOStatement')
            ->setMethods(['closeCursor'])
            ->getMock();
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
     *
     * @return void
     */
    public function testTruncate()
    {
        $fixture = new ArticlesFixture();

        $db = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $statement = $this->getMockBuilder('\PDOStatement')
            ->setMethods(['closeCursor'])
            ->getMock();
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
