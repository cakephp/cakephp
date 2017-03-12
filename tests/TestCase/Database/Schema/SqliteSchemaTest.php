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
namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Driver\Sqlite;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\SqliteSchema;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Test case for Sqlite Schema Dialect.
 */
class SqliteSchemaTest extends TestCase
{

    /**
     * Helper method for skipping tests that need a real connection.
     *
     * @return void
     */
    protected function _needsConnection()
    {
        $config = ConnectionManager::config('test');
        $this->skipIf(strpos($config['driver'], 'Sqlite') === false, 'Not using Sqlite for test config');
    }

    /**
     * Data provider for convert column testing
     *
     * @return array
     */
    public static function convertColumnProvider()
    {
        return [
            [
                'DATETIME',
                ['type' => 'datetime', 'length' => null]
            ],
            [
                'DATE',
                ['type' => 'date', 'length' => null]
            ],
            [
                'TIME',
                ['type' => 'time', 'length' => null]
            ],
            [
                'BOOLEAN',
                ['type' => 'boolean', 'length' => null]
            ],
            [
                'BIGINT',
                ['type' => 'biginteger', 'length' => null, 'unsigned' => false]
            ],
            [
                'UNSIGNED BIGINT',
                ['type' => 'biginteger', 'length' => null, 'unsigned' => true]
            ],
            [
                'VARCHAR(255)',
                ['type' => 'string', 'length' => 255]
            ],
            [
                'CHAR(25)',
                ['type' => 'string', 'fixed' => true, 'length' => 25]
            ],
            [
                'CHAR(36)',
                ['type' => 'uuid', 'length' => null]
            ],
            [
                'BLOB',
                ['type' => 'binary', 'length' => null]
            ],
            [
                'INTEGER(11)',
                ['type' => 'integer', 'length' => 11, 'unsigned' => false]
            ],
            [
                'UNSIGNED INTEGER(11)',
                ['type' => 'integer', 'length' => 11, 'unsigned' => true]
            ],
            [
                'TINYINT(3)',
                ['type' => 'tinyinteger', 'length' => 3, 'unsigned' => false]
            ],
            [
                'UNSIGNED TINYINT(3)',
                ['type' => 'tinyinteger', 'length' => 3, 'unsigned' => true]
            ],
            [
                'SMALLINT(5)',
                ['type' => 'smallinteger', 'length' => 5, 'unsigned' => false]
            ],
            [
                'UNSIGNED SMALLINT(5)',
                ['type' => 'smallinteger', 'length' => 5, 'unsigned' => true]
            ],
            [
                'MEDIUMINT(10)',
                ['type' => 'integer', 'length' => 10, 'unsigned' => false]
            ],
            [
                'FLOAT',
                ['type' => 'float', 'length' => null, 'unsigned' => false]
            ],
            [
                'DOUBLE',
                ['type' => 'float', 'length' => null, 'unsigned' => false]
            ],
            [
                'UNSIGNED DOUBLE',
                ['type' => 'float', 'length' => null, 'unsigned' => true]
            ],
            [
                'REAL',
                ['type' => 'float', 'length' => null, 'unsigned' => false]
            ],
            [
                'DECIMAL(11,2)',
                ['type' => 'decimal', 'length' => null, 'unsigned' => false]
            ],
            [
                'UNSIGNED DECIMAL(11,2)',
                ['type' => 'decimal', 'length' => null, 'unsigned' => true]
            ],
        ];
    }

    /**
     * Test parsing SQLite column types from field description.
     *
     * @dataProvider convertColumnProvider
     * @return void
     */
    public function testConvertColumn($type, $expected)
    {
        $field = [
            'pk' => false,
            'name' => 'field',
            'type' => $type,
            'notnull' => false,
            'dflt_value' => 'Default value',
        ];
        $expected += [
            'null' => true,
            'default' => 'Default value',
        ];

        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlite')->getMock();
        $dialect = new SqliteSchema($driver);

        $table = $this->getMockBuilder(TableSchema::class)
            ->setConstructorArgs(['table'])
            ->getMock();
        $table->expects($this->at(1))->method('addColumn')->with('field', $expected);

        $dialect->convertColumnDescription($table, $field);
    }

    /**
     * Tests converting multiple rows into a primary constraint with multiple
     * columns
     *
     * @return void
     */
    public function testConvertCompositePrimaryKey()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlite')->getMock();
        $dialect = new SqliteSchema($driver);

        $field1 = [
            'pk' => true,
            'name' => 'field1',
            'type' => 'INTEGER(11)',
            'notnull' => false,
            'dflt_value' => 0,
        ];
        $field2 = [
            'pk' => true,
            'name' => 'field2',
            'type' => 'INTEGER(11)',
            'notnull' => false,
            'dflt_value' => 1,
        ];

        $table = new TableSchema('table');
        $dialect->convertColumnDescription($table, $field1);
        $dialect->convertColumnDescription($table, $field2);
        $this->assertEquals(['field1', 'field2'], $table->primaryKey());
    }

    /**
     * Creates tables for testing listTables/describe()
     *
     * @param \Cake\Database\Connection $connection
     * @return void
     */
    protected function _createTables($connection)
    {
        $this->_needsConnection();

        $schema = new SchemaCollection($connection);
        $result = $schema->listTables();
        if (in_array('schema_articles', $result) &&
            in_array('schema_authors', $result)
        ) {
            return;
        }

        $table = <<<SQL
CREATE TABLE schema_authors (
id INTEGER PRIMARY KEY AUTOINCREMENT,
name VARCHAR(50),
bio TEXT,
created DATETIME
)
SQL;
        $connection->execute($table);

        $table = <<<SQL
CREATE TABLE schema_articles (
id INTEGER PRIMARY KEY AUTOINCREMENT,
title VARCHAR(20) DEFAULT 'Let ''em eat cake',
body TEXT,
author_id INT(11) NOT NULL,
published BOOLEAN DEFAULT 0,
created DATETIME,
field1 VARCHAR(10) DEFAULT NULL,
field2 VARCHAR(10) DEFAULT 'NULL',
CONSTRAINT "title_idx" UNIQUE ("title", "body")
CONSTRAINT "author_idx" FOREIGN KEY ("author_id") REFERENCES "schema_authors" ("id") ON UPDATE CASCADE ON DELETE RESTRICT
);
SQL;
        $connection->execute($table);
        $connection->execute('CREATE INDEX "created_idx" ON "schema_articles" ("created")');

        $sql = <<<SQL
CREATE TABLE schema_composite (
    "id" INTEGER NOT NULL,
    "site_id" INTEGER NOT NULL,
    "name" VARCHAR(255),
    PRIMARY KEY("id", "site_id")
);
SQL;
        $connection->execute($sql);
    }

    /**
     * Test SchemaCollection listing tables with Sqlite
     *
     * @return void
     */
    public function testListTables()
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->listTables();

        $this->assertInternalType('array', $result);
        $this->assertContains('schema_articles', $result);
        $this->assertContains('schema_authors', $result);
    }

    /**
     * Test describing a table with Sqlite
     *
     * @return void
     */
    public function testDescribeTable()
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_articles');
        $expected = [
            'id' => [
                'type' => 'integer',
                'null' => false,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
                'unsigned' => false,
                'autoIncrement' => true,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
                'default' => 'Let \'em eat cake',
                'length' => 20,
                'precision' => null,
                'fixed' => null,
                'comment' => null,
                'collate' => null,
            ],
            'body' => [
                'type' => 'text',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
                'collate' => null,
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => false,
                'default' => null,
                'length' => 11,
                'unsigned' => false,
                'precision' => null,
                'comment' => null,
                'autoIncrement' => null,
            ],
            'published' => [
                'type' => 'boolean',
                'null' => true,
                'default' => 0,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            'created' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            'field1' => [
                'type' => 'string',
                'null' => true,
                'default' => null,
                'length' => 10,
                'precision' => null,
                'fixed' => null,
                'comment' => null,
                'collate' => null,
            ],
            'field2' => [
                'type' => 'string',
                'null' => true,
                'default' => 'NULL',
                'length' => 10,
                'precision' => null,
                'fixed' => null,
                'comment' => null,
                'collate' => null,
            ],
        ];
        $this->assertInstanceOf('Cake\Database\Schema\Table', $result);
        $this->assertEquals(['id'], $result->primaryKey());
        foreach ($expected as $field => $definition) {
            $this->assertEquals($definition, $result->column($field));
        }
    }

    /**
     * Test describing a table with Sqlite and composite keys
     *
     * Composite keys in SQLite are never autoincrement, and shouldn't be marked
     * as such.
     *
     * @return void
     */
    public function testDescribeTableCompositeKey()
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);
        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_composite');

        $this->assertEquals(['id', 'site_id'], $result->primaryKey());
        $this->assertNull($result->column('site_id')['autoIncrement'], 'site_id should not be autoincrement');
        $this->assertNull($result->column('id')['autoIncrement'], 'id should not be autoincrement');
    }

    /**
     * Test describing a table with indexes
     *
     * @return void
     */
    public function testDescribeTableIndexes()
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_articles');
        $this->assertInstanceOf('Cake\Database\Schema\Table', $result);
        $expected = [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id'],
                'length' => []
            ],
            'sqlite_autoindex_schema_articles_1' => [
                'type' => 'unique',
                'columns' => ['title', 'body'],
                'length' => []
            ],
            'author_id_fk' => [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['schema_authors', 'id'],
                'length' => [],
                'update' => 'cascade',
                'delete' => 'restrict',
            ]
        ];
        $this->assertCount(3, $result->constraints());
        $this->assertEquals($expected['primary'], $result->constraint('primary'));
        $this->assertEquals(
            $expected['sqlite_autoindex_schema_articles_1'],
            $result->constraint('sqlite_autoindex_schema_articles_1')
        );
        $this->assertEquals(
            $expected['author_id_fk'],
            $result->constraint('author_id_fk')
        );

        $this->assertCount(1, $result->indexes());
        $expected = [
            'type' => 'index',
            'columns' => ['created'],
            'length' => []
        ];
        $this->assertEquals($expected, $result->index('created_idx'));
    }

    /**
     * Column provider for creating column sql
     *
     * @return array
     */
    public static function columnSqlProvider()
    {
        return [
            // strings
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => false],
                '"title" VARCHAR(25) NOT NULL'
            ],
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => true, 'default' => 'ignored'],
                '"title" VARCHAR(25) DEFAULT "ignored"',
            ],
            [
                'id',
                ['type' => 'string', 'length' => 32, 'fixed' => true, 'null' => false],
                '"id" VARCHAR(32) NOT NULL'
            ],
            [
                'role',
                ['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'admin'],
                '"role" VARCHAR(10) NOT NULL DEFAULT "admin"'
            ],
            [
                'title',
                ['type' => 'string'],
                '"title" VARCHAR'
            ],
            [
                'id',
                ['type' => 'uuid'],
                '"id" CHAR(36)'
            ],
            // Text
            [
                'body',
                ['type' => 'text', 'null' => false],
                '"body" TEXT NOT NULL'
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_TINY, 'null' => false],
                '"body" VARCHAR(' . TableSchema::LENGTH_TINY . ') NOT NULL'
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_MEDIUM, 'null' => false],
                '"body" TEXT NOT NULL'
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_LONG, 'null' => false],
                '"body" TEXT NOT NULL'
            ],
            // Integers
            [
                'post_id',
                ['type' => 'smallinteger', 'length' => 5, 'unsigned' => false],
                '"post_id" SMALLINT(5)'
            ],
            [
                'post_id',
                ['type' => 'smallinteger', 'length' => 5, 'unsigned' => true],
                '"post_id" UNSIGNED SMALLINT(5)'
            ],
            [
                'post_id',
                ['type' => 'tinyinteger', 'length' => 3, 'unsigned' => false],
                '"post_id" TINYINT(3)'
            ],
            [
                'post_id',
                ['type' => 'tinyinteger', 'length' => 3, 'unsigned' => true],
                '"post_id" UNSIGNED TINYINT(3)'
            ],
            [
                'post_id',
                ['type' => 'integer', 'length' => 11, 'unsigned' => false],
                '"post_id" INTEGER(11)'
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'length' => 20, 'unsigned' => false],
                '"post_id" BIGINT'
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'length' => 20, 'unsigned' => true],
                '"post_id" UNSIGNED BIGINT'
            ],
            // Decimal
            [
                'value',
                ['type' => 'decimal', 'unsigned' => false],
                '"value" DECIMAL'
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 11, 'unsigned' => false],
                '"value" DECIMAL(11,0)'
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 11, 'unsigned' => true],
                '"value" UNSIGNED DECIMAL(11,0)'
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 12, 'precision' => 5, 'unsigned' => false],
                '"value" DECIMAL(12,5)'
            ],
            // Float
            [
                'value',
                ['type' => 'float'],
                '"value" FLOAT'
            ],
            [
                'value',
                ['type' => 'float', 'length' => 11, 'precision' => 3, 'unsigned' => false],
                '"value" FLOAT(11,3)'
            ],
            [
                'value',
                ['type' => 'float', 'length' => 11, 'precision' => 3, 'unsigned' => true],
                '"value" UNSIGNED FLOAT(11,3)'
            ],
            // Boolean
            [
                'checked',
                ['type' => 'boolean', 'null' => true, 'default' => false],
                '"checked" BOOLEAN DEFAULT FALSE'
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => true, 'null' => false],
                '"checked" BOOLEAN NOT NULL DEFAULT TRUE'
            ],
            // datetimes
            [
                'created',
                ['type' => 'datetime'],
                '"created" DATETIME'
            ],
            [
                'open_date',
                ['type' => 'datetime', 'null' => false, 'default' => '2016-12-07 23:04:00'],
                '"open_date" DATETIME NOT NULL DEFAULT "2016-12-07 23:04:00"'
            ],
            // Date & Time
            [
                'start_date',
                ['type' => 'date'],
                '"start_date" DATE'
            ],
            [
                'start_time',
                ['type' => 'time'],
                '"start_time" TIME'
            ],
            // timestamps
            [
                'created',
                ['type' => 'timestamp', 'null' => true],
                '"created" TIMESTAMP DEFAULT NULL'
            ],
        ];
    }

    /**
     * Test the addConstraintSql method.
     *
     * @return void
     */
    public function testAddConstraintSql()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('driver')
            ->will($this->returnValue($driver));

        $table = new TableSchema('posts');

        $result = $table->addConstraintSql($connection);
        $this->assertEmpty($result);
    }

    /**
     * Test the dropConstraintSql method.
     *
     * @return void
     */
    public function testDropConstraintSql()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('driver')
            ->will($this->returnValue($driver));

        $table = new TableSchema('posts');
        $result = $table->dropConstraintSql($connection);
        $this->assertEmpty($result);
    }

    /**
     * Test generating column definitions
     *
     * @dataProvider columnSqlProvider
     * @return void
     */
    public function testColumnSql($name, $data, $expected)
    {
        $driver = $this->_getMockedDriver();
        $schema = new SqliteSchema($driver);

        $table = (new TableSchema('articles'))->addColumn($name, $data);
        $this->assertEquals($expected, $schema->columnSql($table, $name));
    }

    /**
     * Test generating a column that is a primary key.
     *
     * @return void
     */
    public function testColumnSqlPrimaryKey()
    {
        $driver = $this->_getMockedDriver();
        $schema = new SqliteSchema($driver);

        $table = new TableSchema('articles');
        $table->addColumn('id', [
                'type' => 'integer',
                'null' => false,
                'length' => 11,
                'unsigned' => true
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id']
            ]);
        $result = $schema->columnSql($table, 'id');
        $this->assertEquals($result, '"id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT');

        $result = $schema->constraintSql($table, 'primary');
        $this->assertEquals('', $result, 'Integer primary keys are special in sqlite.');
    }

    /**
     * Test generating a bigint column that is a primary key.
     *
     * @return void
     */
    public function testColumnSqlPrimaryKeyBigInt()
    {
        $driver = $this->_getMockedDriver();
        $schema = new SqliteSchema($driver);

        $table = new TableSchema('articles');
        $table->addColumn('id', [
                'type' => 'biginteger',
                'null' => false
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id']
            ]);
        $result = $schema->columnSql($table, 'id');
        $this->assertEquals($result, '"id" BIGINT NOT NULL');

        $result = $schema->constraintSql($table, 'primary');
        $this->assertEquals('CONSTRAINT "primary" PRIMARY KEY ("id")', $result, 'Bigint primary keys are not special.');
    }

    /**
     * Provide data for testing constraintSql
     *
     * @return array
     */
    public static function constraintSqlProvider()
    {
        return [
            [
                'primary',
                ['type' => 'primary', 'columns' => ['title']],
                'CONSTRAINT "primary" PRIMARY KEY ("title")'
            ],
            [
                'unique_idx',
                ['type' => 'unique', 'columns' => ['title', 'author_id']],
                'CONSTRAINT "unique_idx" UNIQUE ("title", "author_id")'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id']],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE RESTRICT ON DELETE RESTRICT'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'cascade'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE CASCADE ON DELETE RESTRICT'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'restrict'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE RESTRICT ON DELETE RESTRICT'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'setNull'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE SET NULL ON DELETE RESTRICT'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'noAction'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE NO ACTION ON DELETE RESTRICT'
            ],
        ];
    }

    /**
     * Test the constraintSql method.
     *
     * @dataProvider constraintSqlProvider
     */
    public function testConstraintSql($name, $data, $expected)
    {
        $driver = $this->_getMockedDriver();
        $schema = new SqliteSchema($driver);

        $table = (new TableSchema('articles'))->addColumn('title', [
            'type' => 'string',
            'length' => 255
        ])->addColumn('author_id', [
            'type' => 'integer',
        ])->addConstraint($name, $data);

        $this->assertEquals($expected, $schema->constraintSql($table, $name));
    }

    /**
     * Provide data for testing indexSql
     *
     * @return array
     */
    public static function indexSqlProvider()
    {
        return [
            [
                'author_idx',
                ['type' => 'index', 'columns' => ['title', 'author_id']],
                'CREATE INDEX "author_idx" ON "articles" ("title", "author_id")'
            ],
        ];
    }

    /**
     * Test the indexSql method.
     *
     * @dataProvider indexSqlProvider
     */
    public function testIndexSql($name, $data, $expected)
    {
        $driver = $this->_getMockedDriver();
        $schema = new SqliteSchema($driver);

        $table = (new TableSchema('articles'))->addColumn('title', [
            'type' => 'string',
            'length' => 255
        ])->addColumn('author_id', [
            'type' => 'integer',
        ])->addIndex($name, $data);

        $this->assertEquals($expected, $schema->indexSql($table, $name));
    }

    /**
     * Integration test for converting a Schema\Table into MySQL table creates.
     *
     * @return void
     */
    public function testCreateSql()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('driver')
            ->will($this->returnValue($driver));

        $table = (new TableSchema('articles'))->addColumn('id', [
                'type' => 'integer',
                'null' => false
            ])
            ->addColumn('title', [
                'type' => 'string',
                'null' => false,
            ])
            ->addColumn('body', ['type' => 'text'])
            ->addColumn('data', ['type' => 'json'])
            ->addColumn('created', 'datetime')
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id']
            ])
            ->addIndex('title_idx', [
                'type' => 'index',
                'columns' => ['title']
            ]);

        $expected = <<<SQL
CREATE TABLE "articles" (
"id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
"title" VARCHAR NOT NULL,
"body" TEXT,
"data" TEXT,
"created" DATETIME
)
SQL;
        $result = $table->createSql($connection);
        $this->assertCount(2, $result);
        $this->assertTextEquals($expected, $result[0]);
        $this->assertEquals(
            'CREATE INDEX "title_idx" ON "articles" ("title")',
            $result[1]
        );
    }

    /**
     * Tests creating temporary tables
     *
     * @return void
     */
    public function testCreateTemporary()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('driver')
            ->will($this->returnValue($driver));
        $table = (new TableSchema('schema_articles'))->addColumn('id', [
            'type' => 'integer',
            'null' => false
        ]);
        $table->temporary(true);
        $sql = $table->createSql($connection);
        $this->assertContains('CREATE TEMPORARY TABLE', $sql[0]);
    }

    /**
     * Test primary key generation & auto-increment.
     *
     * @return void
     */
    public function testCreateSqlCompositeIntegerKey()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('driver')
            ->will($this->returnValue($driver));

        $table = (new TableSchema('articles_tags'))
            ->addColumn('article_id', [
                'type' => 'integer',
                'null' => false
            ])
            ->addColumn('tag_id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['article_id', 'tag_id']
            ]);

        $expected = <<<SQL
CREATE TABLE "articles_tags" (
"article_id" INTEGER NOT NULL,
"tag_id" INTEGER NOT NULL,
CONSTRAINT "primary" PRIMARY KEY ("article_id", "tag_id")
)
SQL;
        $result = $table->createSql($connection);
        $this->assertCount(1, $result);
        $this->assertTextEquals($expected, $result[0]);

        // Sqlite only supports AUTO_INCREMENT on single column primary
        // keys. Ensure that schema data follows the limitations of Sqlite.
        $table = (new TableSchema('composite_key'))
            ->addColumn('id', [
                'type' => 'integer',
                'null' => false,
                'autoIncrement' => true
            ])
            ->addColumn('account_id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id', 'account_id']
            ]);

        $expected = <<<SQL
CREATE TABLE "composite_key" (
"id" INTEGER NOT NULL,
"account_id" INTEGER NOT NULL,
CONSTRAINT "primary" PRIMARY KEY ("id", "account_id")
)
SQL;
        $result = $table->createSql($connection);
        $this->assertCount(1, $result);
        $this->assertTextEquals($expected, $result[0]);
    }

    /**
     * test dropSql
     *
     * @return void
     */
    public function testDropSql()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('driver')
            ->will($this->returnValue($driver));

        $table = new TableSchema('articles');
        $result = $table->dropSql($connection);
        $this->assertCount(1, $result);
        $this->assertEquals('DROP TABLE "articles"', $result[0]);
    }

    /**
     * Test truncateSql()
     *
     * @return void
     */
    public function testTruncateSql()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('driver')
            ->will($this->returnValue($driver));

        $statement = $this->getMockBuilder('\PDOStatement')
            ->setMethods(['execute', 'rowCount', 'closeCursor', 'fetch'])
            ->getMock();
        $driver->connection()->expects($this->once())->method('prepare')
            ->with('SELECT 1 FROM sqlite_master WHERE name = "sqlite_sequence"')
            ->will($this->returnValue($statement));
        $statement->expects($this->at(0))->method('fetch')
            ->will($this->returnValue(['1']));
        $statement->expects($this->at(2))->method('fetch')
            ->will($this->returnValue(false));

        $table = new TableSchema('articles');
        $result = $table->truncateSql($connection);
        $this->assertCount(2, $result);
        $this->assertEquals('DELETE FROM sqlite_sequence WHERE name="articles"', $result[0]);
        $this->assertEquals('DELETE FROM "articles"', $result[1]);
    }

    /**
     * Test truncateSql() with no sequences
     *
     * @return void
     */
    public function testTruncateSqlNoSequences()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('driver')
            ->will($this->returnValue($driver));

        $statement = $this->getMockBuilder('\PDOStatement')
            ->setMethods(['execute', 'rowCount', 'closeCursor', 'fetch'])
            ->getMock();
        $driver->connection()->expects($this->once())->method('prepare')
            ->with('SELECT 1 FROM sqlite_master WHERE name = "sqlite_sequence"')
            ->will($this->returnValue($statement));
        $statement->expects($this->once())->method('fetch')
            ->will($this->returnValue(false));

        $table = new TableSchema('articles');
        $result = $table->truncateSql($connection);
        $this->assertCount(1, $result);
        $this->assertEquals('DELETE FROM "articles"', $result[0]);
    }

    /**
     * Get a schema instance with a mocked driver/pdo instances
     *
     * @return \Cake\Database\Driver
     */
    protected function _getMockedDriver()
    {
        $driver = new Sqlite();
        $mock = $this->getMockBuilder(PDO::class)
            ->setMethods(['quote', 'prepare'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('quote')
            ->will($this->returnCallback(function ($value) {
                return '"' . $value . '"';
            }));
        $driver->connection($mock);

        return $driver;
    }
}
