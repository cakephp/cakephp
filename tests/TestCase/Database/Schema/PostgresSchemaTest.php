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

use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\PostgresSchema;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Postgres schema test case.
 */
class PostgresSchemaTest extends TestCase
{

    /**
     * Helper method for skipping tests that need a real connection.
     *
     * @return void
     */
    protected function _needsConnection()
    {
        $config = ConnectionManager::config('test');
        $this->skipIf(strpos($config['driver'], 'Postgres') === false, 'Not using Postgres for test config');
    }

    /**
     * Helper method for testing methods.
     *
     * @param \Cake\Datasource\ConnectionInterface $connection
     * @return void
     */
    protected function _createTables($connection)
    {
        $this->_needsConnection();

        $connection->execute('DROP TABLE IF EXISTS schema_articles');
        $connection->execute('DROP TABLE IF EXISTS schema_authors');

        $table = <<<SQL
CREATE TABLE schema_authors (
id SERIAL,
name VARCHAR(50) DEFAULT 'bob',
bio DATE,
position INT DEFAULT 1,
created TIMESTAMP,
PRIMARY KEY (id),
CONSTRAINT "unique_position" UNIQUE ("position")
)
SQL;
        $connection->execute($table);

        $table = <<<SQL
CREATE TABLE schema_articles (
id BIGINT PRIMARY KEY,
title VARCHAR(20),
body TEXT,
author_id INTEGER NOT NULL,
published BOOLEAN DEFAULT false,
views SMALLINT DEFAULT 0,
readingtime TIME,
created TIMESTAMP,
CONSTRAINT "content_idx" UNIQUE ("title", "body"),
CONSTRAINT "author_idx" FOREIGN KEY ("author_id") REFERENCES "schema_authors" ("id") ON DELETE RESTRICT ON UPDATE CASCADE
)
SQL;
        $connection->execute($table);
        $connection->execute('COMMENT ON COLUMN "schema_articles"."title" IS \'a title\'');
        $connection->execute('CREATE INDEX "author_idx" ON "schema_articles" ("author_id")');
    }

    /**
     * Data provider for convert column testing
     *
     * @return array
     */
    public static function convertColumnProvider()
    {
        return [
            // Timestamp
            [
                'TIMESTAMP',
                ['type' => 'timestamp', 'length' => null]
            ],
            [
                'TIMESTAMP WITHOUT TIME ZONE',
                ['type' => 'timestamp', 'length' => null]
            ],
            // Date & time
            [
                'DATE',
                ['type' => 'date', 'length' => null]
            ],
            [
                'TIME',
                ['type' => 'time', 'length' => null]
            ],
            [
                'TIME WITHOUT TIME ZONE',
                ['type' => 'time', 'length' => null]
            ],
            // Integer
            [
                'SMALLINT',
                ['type' => 'integer', 'length' => 5]
            ],
            [
                'INTEGER',
                ['type' => 'integer', 'length' => 10]
            ],
            [
                'SERIAL',
                ['type' => 'integer', 'length' => 10]
            ],
            [
                'BIGINT',
                ['type' => 'biginteger', 'length' => 20]
            ],
            [
                'BIGSERIAL',
                ['type' => 'biginteger', 'length' => 20]
            ],
            // Decimal
            [
                'NUMERIC',
                ['type' => 'decimal', 'length' => null]
            ],
            [
                'DECIMAL(10,2)',
                ['type' => 'decimal', 'length' => null]
            ],
            // String
            [
                'VARCHAR',
                ['type' => 'string', 'length' => null]
            ],
            [
                'VARCHAR(10)',
                ['type' => 'string', 'length' => 10]
            ],
            [
                'CHARACTER VARYING',
                ['type' => 'string', 'length' => null]
            ],
            [
                'CHARACTER VARYING(10)',
                ['type' => 'string', 'length' => 10]
            ],
            [
                'CHAR(10)',
                ['type' => 'string', 'fixed' => true, 'length' => 10]
            ],
            [
                'CHARACTER(10)',
                ['type' => 'string', 'fixed' => true, 'length' => 10]
            ],
            [
                'MONEY',
                ['type' => 'string', 'length' => null]
            ],
            // UUID
            [
                'UUID',
                ['type' => 'uuid', 'length' => null]
            ],
            [
                'INET',
                ['type' => 'string', 'length' => 39]
            ],
            // Text
            [
                'TEXT',
                ['type' => 'text', 'length' => null]
            ],
            // Blob
            [
                'BYTEA',
                ['type' => 'binary', 'length' => null]
            ],
            // Float
            [
                'REAL',
                ['type' => 'float', 'length' => null]
            ],
            [
                'DOUBLE PRECISION',
                ['type' => 'float', 'length' => null]
            ],
        ];
    }

    /**
     * Test parsing Postgres column types from field description.
     *
     * @dataProvider convertColumnProvider
     * @return void
     */
    public function testConvertColumn($type, $expected)
    {
        $field = [
            'name' => 'field',
            'type' => $type,
            'null' => 'YES',
            'default' => 'Default value',
            'comment' => 'Comment section',
            'char_length' => null,
            'collation_name' => 'ja_JP.utf8',
        ];
        $expected += [
            'null' => true,
            'default' => 'Default value',
            'comment' => 'Comment section',
            'collate' => 'ja_JP.utf8',
        ];

        $driver = $this->getMockBuilder('Cake\Database\Driver\Postgres')->getMock();
        $dialect = new PostgresSchema($driver);

        $table = $this->getMockBuilder('Cake\Database\Schema\Table')
            ->setConstructorArgs(['table'])
            ->getMock();
        $table->expects($this->at(0))->method('addColumn')->with('field', $expected);

        $dialect->convertColumnDescription($table, $field);
    }

    /**
     * Test listing tables with Postgres
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
     * Test that describe accepts tablenames containing `schema.table`.
     *
     * @return void
     */
    public function testDescribeWithSchemaName()
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('public.schema_articles');
        $this->assertEquals(['id'], $result->primaryKey());
        $this->assertEquals('schema_articles', $result->name());
    }

    /**
     * Test describing a table with Postgres
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
                'type' => 'biginteger',
                'null' => false,
                'default' => null,
                'length' => 20,
                'precision' => null,
                'unsigned' => null,
                'comment' => null,
                'autoIncrement' => false,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
                'default' => null,
                'length' => 20,
                'precision' => null,
                'comment' => 'a title',
                'fixed' => null,
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
                'length' => 10,
                'precision' => null,
                'unsigned' => null,
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
            'views' => [
                'type' => 'integer',
                'null' => true,
                'default' => 0,
                'length' => 5,
                'precision' => null,
                'unsigned' => null,
                'comment' => null,
                'autoIncrement' => null,
            ],
            'readingtime' => [
                'type' => 'time',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            'created' => [
                'type' => 'timestamp',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
        ];
        $this->assertEquals(['id'], $result->primaryKey());
        foreach ($expected as $field => $definition) {
            $this->assertEquals($definition, $result->column($field));
        }
    }

    /**
     * Test describing a table with postgres and composite keys
     *
     * @return void
     */
    public function testDescribeTableCompositeKey()
    {
        $this->_needsConnection();
        $connection = ConnectionManager::get('test');
        $sql = <<<SQL
CREATE TABLE schema_composite (
    "id" SERIAL,
    "site_id" INTEGER NOT NULL,
    "name" VARCHAR(255),
    PRIMARY KEY("id", "site_id")
);
SQL;
        $connection->execute($sql);
        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_composite');
        $connection->execute('DROP TABLE schema_composite');

        $this->assertEquals(['id', 'site_id'], $result->primaryKey());
        $this->assertTrue($result->column('id')['autoIncrement'], 'id should be autoincrement');
        $this->assertNull($result->column('site_id')['autoIncrement'], 'site_id should not be autoincrement');
    }

    /**
     * Test describing a table containing defaults with Postgres
     *
     * @return void
     */
    public function testDescribeTableWithDefaults()
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_authors');
        $expected = [
            'id' => [
                'type' => 'integer',
                'null' => false,
                'default' => null,
                'length' => 10,
                'precision' => null,
                'unsigned' => null,
                'comment' => null,
                'autoIncrement' => true,
            ],
            'name' => [
                'type' => 'string',
                'null' => true,
                'default' => 'bob',
                'length' => 50,
                'precision' => null,
                'comment' => null,
                'fixed' => null,
                'collate' => null,
            ],
            'bio' => [
                'type' => 'date',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
            'position' => [
                'type' => 'integer',
                'null' => true,
                'default' => '1',
                'length' => 10,
                'precision' => null,
                'comment' => null,
                'unsigned' => null,
                'autoIncrement' => null,
            ],
            'created' => [
                'type' => 'timestamp',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
            ],
        ];
        $this->assertEquals(['id'], $result->primaryKey());
        foreach ($expected as $field => $definition) {
            $this->assertEquals($definition, $result->column($field), "Mismatch in $field column");
        }
    }

    /**
     * Test describing a table with containing keywords
     *
     * @return void
     */
    public function testDescribeTableConstraintsWithKeywords()
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_authors');
        $this->assertInstanceOf('Cake\Database\Schema\Table', $result);
        $expected = [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id'],
                'length' => []
            ],
            'unique_position' => [
                'type' => 'unique',
                'columns' => ['position'],
                'length' => []
            ]
        ];
        $this->assertCount(2, $result->constraints());
        $this->assertEquals($expected['primary'], $result->constraint('primary'));
        $this->assertEquals($expected['unique_position'], $result->constraint('unique_position'));
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
            'content_idx' => [
                'type' => 'unique',
                'columns' => ['title', 'body'],
                'length' => []
            ]
        ];
        $this->assertCount(3, $result->constraints());
        $expected = [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id'],
                'length' => []
            ],
            'content_idx' => [
                'type' => 'unique',
                'columns' => ['title', 'body'],
                'length' => []
            ],
            'author_idx' => [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['schema_authors', 'id'],
                'length' => [],
                'update' => 'cascade',
                'delete' => 'restrict',
            ]
        ];
        $this->assertEquals($expected['primary'], $result->constraint('primary'));
        $this->assertEquals($expected['content_idx'], $result->constraint('content_idx'));
        $this->assertEquals($expected['author_idx'], $result->constraint('author_idx'));

        $this->assertCount(1, $result->indexes());
        $expected = [
            'type' => 'index',
            'columns' => ['author_id'],
            'length' => []
        ];
        $this->assertEquals($expected, $result->index('author_idx'));
    }

    /**
     * Test describing a table with indexes with nulls first
     *
     * @return void
     */
    public function testDescribeTableIndexesNullsFirst()
    {
        $this->_needsConnection();
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS schema_index');

        $table = <<<SQL
CREATE TABLE schema_index (
  id serial NOT NULL,
  user_id integer NOT NULL,
  group_id integer NOT NULL,
  grade double precision
)
WITH (
  OIDS=FALSE
)
SQL;
        $connection->execute($table);

        $index = <<<SQL
CREATE INDEX schema_index_nulls
  ON schema_index
  USING btree
  (group_id, grade DESC NULLS FIRST);
SQL;
        $connection->execute($index);
        $schema = new SchemaCollection($connection);

        $result = $schema->describe('schema_index');
        $this->assertCount(1, $result->indexes());
        $expected = [
            'type' => 'index',
            'columns' => ['group_id', 'grade'],
            'length' => []
        ];
        $this->assertEquals($expected, $result->index('schema_index_nulls'));
        $connection->execute('DROP TABLE schema_index');
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
                '"title" VARCHAR(25) DEFAULT NULL'
            ],
            [
                'id',
                ['type' => 'string', 'length' => 32, 'fixed' => true, 'null' => false],
                '"id" CHAR(32) NOT NULL'
            ],
            [
                'id',
                ['type' => 'uuid', 'length' => 36, 'null' => false],
                '"id" UUID NOT NULL'
            ],
            [
                'role',
                ['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'admin'],
                '"role" VARCHAR(10) NOT NULL DEFAULT \'admin\''
            ],
            [
                'title',
                ['type' => 'string'],
                '"title" VARCHAR'
            ],
            [
                'title',
                ['type' => 'string', 'length' => 255, 'null' => false, 'collate' => 'C'],
                '"title" VARCHAR(255) COLLATE "C" NOT NULL'
            ],
            // Text
            [
                'body',
                ['type' => 'text', 'null' => false],
                '"body" TEXT NOT NULL'
            ],
            [
                'body',
                ['type' => 'text', 'length' => Table::LENGTH_TINY, 'null' => false],
                sprintf('"body" VARCHAR(%s) NOT NULL', Table::LENGTH_TINY)
            ],
            [
                'body',
                ['type' => 'text', 'length' => Table::LENGTH_MEDIUM, 'null' => false],
                '"body" TEXT NOT NULL'
            ],
            [
                'body',
                ['type' => 'text', 'length' => Table::LENGTH_LONG, 'null' => false],
                '"body" TEXT NOT NULL'
            ],
            [
                'body',
                ['type' => 'text', 'null' => false, 'collate' => 'C'],
                '"body" TEXT COLLATE "C" NOT NULL'
            ],
            // Integers
            [
                'post_id',
                ['type' => 'integer', 'length' => 11],
                '"post_id" INTEGER'
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'length' => 20],
                '"post_id" BIGINT'
            ],
            [
                'post_id',
                ['type' => 'integer', 'autoIncrement' => true, 'length' => 11],
                '"post_id" SERIAL'
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'autoIncrement' => true, 'length' => 20],
                '"post_id" BIGSERIAL'
            ],
            // Decimal
            [
                'value',
                ['type' => 'decimal'],
                '"value" DECIMAL'
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 11],
                '"value" DECIMAL(11,0)'
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 12, 'precision' => 5],
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
                ['type' => 'float', 'length' => 11, 'precision' => 3],
                '"value" FLOAT(3)'
            ],
            // Binary
            [
                'img',
                ['type' => 'binary'],
                '"img" BYTEA'
            ],
            // Boolean
            [
                'checked',
                ['type' => 'boolean', 'default' => false],
                '"checked" BOOLEAN DEFAULT FALSE'
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => true, 'null' => false],
                '"checked" BOOLEAN NOT NULL DEFAULT TRUE'
            ],
            // Boolean
            [
                'checked',
                ['type' => 'boolean', 'default' => 0],
                '"checked" BOOLEAN DEFAULT FALSE'
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => 1, 'null' => false],
                '"checked" BOOLEAN NOT NULL DEFAULT TRUE'
            ],
            // datetimes
            [
                'created',
                ['type' => 'datetime'],
                '"created" TIMESTAMP'
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
     * Test generating column definitions
     *
     * @dataProvider columnSqlProvider
     * @return void
     */
    public function testColumnSql($name, $data, $expected)
    {
        $driver = $this->_getMockedDriver();
        $schema = new PostgresSchema($driver);

        $table = (new Table('schema_articles'))->addColumn($name, $data);
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
        $schema = new PostgresSchema($driver);

        $table = new Table('schema_articles');
        $table->addColumn('id', [
                'type' => 'integer',
                'null' => false
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id']
            ]);
        $result = $schema->columnSql($table, 'id');
        $this->assertEquals($result, '"id" SERIAL');
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
                'PRIMARY KEY ("title")'
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
                'REFERENCES "authors" ("id") ON UPDATE RESTRICT ON DELETE RESTRICT DEFERRABLE INITIALLY IMMEDIATE'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'cascade'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE CASCADE ON DELETE RESTRICT DEFERRABLE INITIALLY IMMEDIATE'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'restrict'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE RESTRICT ON DELETE RESTRICT DEFERRABLE INITIALLY IMMEDIATE'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'setNull'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE SET NULL ON DELETE RESTRICT DEFERRABLE INITIALLY IMMEDIATE'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'noAction'],
                'CONSTRAINT "author_id_idx" FOREIGN KEY ("author_id") ' .
                'REFERENCES "authors" ("id") ON UPDATE NO ACTION ON DELETE RESTRICT DEFERRABLE INITIALLY IMMEDIATE'
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
        $schema = new PostgresSchema($driver);

        $table = (new Table('schema_articles'))->addColumn('title', [
            'type' => 'string',
            'length' => 255
        ])->addColumn('author_id', [
            'type' => 'integer',
        ])->addConstraint($name, $data);

        $this->assertTextEquals($expected, $schema->constraintSql($table, $name));
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

        $table = (new Table('posts'))
            ->addColumn('author_id', [
                'type' => 'integer',
                'null' => false
            ])
            ->addColumn('category_id', [
                'type' => 'integer',
                'null' => false
            ])
            ->addColumn('category_name', [
                'type' => 'integer',
                'null' => false
            ])
            ->addConstraint('author_fk', [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['authors', 'id'],
                'update' => 'cascade',
                'delete' => 'cascade'
            ])
            ->addConstraint('category_fk', [
                'type' => 'foreign',
                'columns' => ['category_id', 'category_name'],
                'references' => ['categories', ['id', 'name']],
                'update' => 'cascade',
                'delete' => 'cascade'
            ]);

        $expected = [
            'ALTER TABLE "posts" ADD CONSTRAINT "author_fk" FOREIGN KEY ("author_id") REFERENCES "authors" ("id") ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE;',
            'ALTER TABLE "posts" ADD CONSTRAINT "category_fk" FOREIGN KEY ("category_id", "category_name") REFERENCES "categories" ("id", "name") ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE;'
        ];
        $result = $table->addConstraintSql($connection);
        $this->assertCount(2, $result);
        $this->assertEquals($expected, $result);
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

        $table = (new Table('posts'))
            ->addColumn('author_id', [
                'type' => 'integer',
                'null' => false
            ])
            ->addColumn('category_id', [
                'type' => 'integer',
                'null' => false
            ])
            ->addColumn('category_name', [
                'type' => 'integer',
                'null' => false
            ])
            ->addConstraint('author_fk', [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['authors', 'id'],
                'update' => 'cascade',
                'delete' => 'cascade'
            ])
            ->addConstraint('category_fk', [
                'type' => 'foreign',
                'columns' => ['category_id', 'category_name'],
                'references' => ['categories', ['id', 'name']],
                'update' => 'cascade',
                'delete' => 'cascade'
            ]);

        $expected = [
            'ALTER TABLE "posts" DROP CONSTRAINT "author_fk";',
            'ALTER TABLE "posts" DROP CONSTRAINT "category_fk";'
        ];
        $result = $table->dropConstraintSql($connection);
        $this->assertCount(2, $result);
        $this->assertEquals($expected, $result);
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

        $table = (new Table('schema_articles'))->addColumn('id', [
                'type' => 'integer',
                'null' => false
            ])
            ->addColumn('title', [
                'type' => 'string',
                'null' => false,
                'comment' => 'This is the title',
            ])
            ->addColumn('body', ['type' => 'text'])
            ->addColumn('hash', [
                'type' => 'string',
                'fixed' => true,
                'length' => 40,
                'collate' => 'C',
                'null' => false,
            ])
            ->addColumn('created', 'datetime')
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ])
            ->addIndex('title_idx', [
                'type' => 'index',
                'columns' => ['title'],
            ]);

        $expected = <<<SQL
CREATE TABLE "schema_articles" (
"id" SERIAL,
"title" VARCHAR NOT NULL,
"body" TEXT,
"hash" CHAR(40) COLLATE "C" NOT NULL,
"created" TIMESTAMP,
PRIMARY KEY ("id")
)
SQL;
        $result = $table->createSql($connection);

        $this->assertCount(3, $result);
        $this->assertTextEquals($expected, $result[0]);
        $this->assertEquals(
            'CREATE INDEX "title_idx" ON "schema_articles" ("title")',
            $result[1]
        );
        $this->assertEquals(
            'COMMENT ON COLUMN "schema_articles"."title" IS \'This is the title\'',
            $result[2]
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
        $table = (new Table('schema_articles'))->addColumn('id', [
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

        $table = (new Table('articles_tags'))
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
PRIMARY KEY ("article_id", "tag_id")
)
SQL;
        $result = $table->createSql($connection);
        $this->assertCount(1, $result);
        $this->assertTextEquals($expected, $result[0]);

        $table = (new Table('composite_key'))
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
"id" SERIAL,
"account_id" INTEGER NOT NULL,
PRIMARY KEY ("id", "account_id")
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

        $table = new Table('schema_articles');
        $result = $table->dropSql($connection);
        $this->assertCount(1, $result);
        $this->assertEquals('DROP TABLE "schema_articles" CASCADE', $result[0]);
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

        $table = new Table('schema_articles');
        $table->addColumn('id', 'integer')
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id']
            ]);
        $result = $table->truncateSql($connection);
        $this->assertCount(1, $result);
        $this->assertEquals('TRUNCATE "schema_articles" RESTART IDENTITY CASCADE', $result[0]);
    }

    /**
     * Get a schema instance with a mocked driver/pdo instances
     *
     * @return \Cake\Database\Driver
     */
    protected function _getMockedDriver()
    {
        $driver = new \Cake\Database\Driver\Postgres();
        $mock = $this->getMockBuilder('FakePdo')
            ->setMethods(['quote'])
            ->getMock();
        $mock->expects($this->any())
            ->method('quote')
            ->will($this->returnCallback(function ($value) {
                return "'$value'";
            }));
        $driver->connection($mock);
        return $driver;
    }
}
