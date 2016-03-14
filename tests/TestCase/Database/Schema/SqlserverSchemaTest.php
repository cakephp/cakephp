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

use Cake\Core\Configure;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\SqlserverSchema;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * SQL Server schema test case.
 */
class SqlserverSchemaTest extends TestCase
{
    /**
     * Helper method for skipping tests that need a real connection.
     *
     * @return void
     */
    protected function _needsConnection()
    {
        $config = ConnectionManager::config('test');
        $this->skipIf(strpos($config['driver'], 'Sqlserver') === false, 'Not using Sqlserver for test config');
    }

    /**
     * Helper method for testing methods.
     *
     * @return void
     */
    protected function _createTables($connection)
    {
        $this->_needsConnection();

        $connection->execute("IF OBJECT_ID('schema_articles', 'U') IS NOT NULL DROP TABLE schema_articles");
        $connection->execute("IF OBJECT_ID('schema_authors', 'U') IS NOT NULL DROP TABLE schema_authors");

        $table = <<<SQL
CREATE TABLE schema_authors (
id int IDENTITY(1,1) PRIMARY KEY,
name VARCHAR(50),
bio DATE,
created DATETIME
)
SQL;
        $connection->execute($table);

        $table = <<<SQL
CREATE TABLE schema_articles (
id BIGINT PRIMARY KEY,
title VARCHAR(20),
body VARCHAR(1000),
author_id INTEGER NOT NULL,
published BIT DEFAULT 0,
views SMALLINT DEFAULT 0,
created DATETIME,
CONSTRAINT [content_idx] UNIQUE ([title], [body]),
CONSTRAINT [author_idx] FOREIGN KEY ([author_id]) REFERENCES [schema_authors] ([id]) ON DELETE CASCADE ON UPDATE CASCADE
)
SQL;
        $connection->execute($table);
        $connection->execute('CREATE INDEX [author_idx] ON [schema_articles] ([author_id])');
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
                null,
                null,
                null,
                ['type' => 'timestamp', 'length' => null]
            ],
            [
                'DATE',
                null,
                null,
                null,
                ['type' => 'date', 'length' => null]
            ],
            [
                'TIME',
                null,
                null,
                null,
                ['type' => 'time', 'length' => null]
            ],
            [
                'SMALLINT',
                null,
                4,
                null,
                ['type' => 'integer', 'length' => 4]
            ],
            [
                'INTEGER',
                null,
                null,
                null,
                ['type' => 'integer', 'length' => 10]
            ],
            [
                'INTEGER',
                null,
                8,
                null,
                ['type' => 'integer', 'length' => 8]
            ],
            [
                'BIGINT',
                null,
                null,
                null,
                ['type' => 'biginteger', 'length' => 20]
            ],
            [
                'NUMERIC',
                null,
                15,
                5,
                ['type' => 'decimal', 'length' => 15, 'precision' => 5]
            ],
            [
                'DECIMAL',
                null,
                11,
                3,
                ['type' => 'decimal', 'length' => 11, 'precision' => 3]
            ],
            [
                'MONEY',
                null,
                null,
                null,
                ['type' => 'decimal', 'length' => null, 'precision' => null]
            ],
            [
                'VARCHAR',
                null,
                null,
                null,
                ['type' => 'string', 'length' => 255]
            ],
            [
                'VARCHAR',
                10,
                null,
                null,
                ['type' => 'string', 'length' => 10]
            ],
            [
                'NVARCHAR',
                50,
                null,
                null,
                ['type' => 'string', 'length' => 50]
            ],
            [
                'CHAR',
                10,
                null,
                null,
                ['type' => 'string', 'fixed' => true, 'length' => 10]
            ],
            [
                'NCHAR',
                10,
                null,
                null,
                ['type' => 'string', 'fixed' => true, 'length' => 10]
            ],
            [
                'UNIQUEIDENTIFIER',
                null,
                null,
                null,
                ['type' => 'uuid']
            ],
            [
                'TEXT',
                null,
                null,
                null,
                ['type' => 'text', 'length' => null]
            ],
            [
                'REAL',
                null,
                null,
                null,
                ['type' => 'float', 'length' => null]
            ],
            [
                'VARCHAR',
                -1,
                null,
                null,
                ['type' => 'text', 'length' => null]
            ],
        ];
    }

    /**
     * Test parsing Sqlserver column types from field description.
     *
     * @dataProvider convertColumnProvider
     * @return void
     */
    public function testConvertColumn($type, $length, $precision, $scale, $expected)
    {
        $field = [
            'name' => 'field',
            'type' => $type,
            'null' => '1',
            'default' => 'Default value',
            'char_length' => $length,
            'precision' => $precision,
            'scale' => $scale
        ];
        $expected += [
            'null' => true,
            'default' => 'Default value',
        ];

        $driver = $this->getMock('Cake\Database\Driver\Sqlserver');
        $dialect = new SqlserverSchema($driver);

        $table = $this->getMock('Cake\Database\Schema\Table', [], ['table']);
        $table->expects($this->at(0))->method('addColumn')->with('field', $expected);

        $dialect->convertColumnDescription($table, $field);
    }

    /**
     * Test listing tables with Sqlserver
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
     * Test describing a table with Sqlserver
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
                'length' => 19,
                'precision' => null,
                'unsigned' => null,
                'autoIncrement' => null,
                'comment' => null,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
                'default' => null,
                'length' => 20,
                'precision' => null,
                'comment' => null,
                'fixed' => null,
            ],
            'body' => [
                'type' => 'string',
                'null' => true,
                'default' => null,
                'length' => 1000,
                'precision' => null,
                'fixed' => null,
                'comment' => null,
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => false,
                'default' => null,
                'length' => 10,
                'precision' => null,
                'unsigned' => null,
                'autoIncrement' => null,
                'comment' => null,
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
                'autoIncrement' => null,
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
            $this->assertEquals($definition, $result->column($field), 'Failed to match field ' . $field);
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
    [id] INTEGER IDENTITY(1, 1),
    [site_id] INTEGER NOT NULL,
    [name] VARCHAR(255),
    PRIMARY KEY([id], [site_id])
);
SQL;
        $connection->execute($sql);
        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_composite');
        $connection->execute('DROP TABLE schema_composite');

        $this->assertEquals(['id', 'site_id'], $result->primaryKey());
        $this->assertNull($result->column('site_id')['autoIncrement'], 'site_id should not be autoincrement');
        $this->assertTrue($result->column('id')['autoIncrement'], 'id should be autoincrement');
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
        $result = $schema->describe('dbo.schema_articles');
        $this->assertEquals(['id'], $result->primaryKey());
        $this->assertEquals('schema_articles', $result->name());
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
                'delete' => 'cascade',
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
                '[title] NVARCHAR(25) NOT NULL'
            ],
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => true, 'default' => 'ignored'],
                '[title] NVARCHAR(25) DEFAULT NULL'
            ],
            [
                'id',
                ['type' => 'string', 'length' => 32, 'fixed' => true, 'null' => false],
                '[id] NCHAR(32) NOT NULL'
            ],
            [
                'id',
                ['type' => 'uuid', 'null' => false],
                '[id] UNIQUEIDENTIFIER NOT NULL'
            ],
            [
                'role',
                ['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'admin'],
                "[role] NVARCHAR(10) NOT NULL DEFAULT [admin]"
            ],
            [
                'title',
                ['type' => 'string'],
                '[title] NVARCHAR(255)'
            ],
            // Text
            [
                'body',
                ['type' => 'text', 'null' => false],
                '[body] NVARCHAR(MAX) NOT NULL'
            ],
            // Integers
            [
                'post_id',
                ['type' => 'integer', 'length' => 11],
                '[post_id] INTEGER'
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'length' => 20],
                '[post_id] BIGINT'
            ],
            // Decimal
            [
                'value',
                ['type' => 'decimal'],
                '[value] DECIMAL'
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 11],
                '[value] DECIMAL(11,0)'
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 12, 'precision' => 5],
                '[value] DECIMAL(12,5)'
            ],
            // Float
            [
                'value',
                ['type' => 'float'],
                '[value] FLOAT'
            ],
            [
                'value',
                ['type' => 'float', 'length' => 11, 'precision' => 3],
                '[value] FLOAT(3)'
            ],
            // Binary
            [
                'img',
                ['type' => 'binary'],
                '[img] VARBINARY(MAX)'
            ],
            // Boolean
            [
                'checked',
                ['type' => 'boolean', 'default' => false],
                '[checked] BIT DEFAULT 0'
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => true, 'null' => false],
                '[checked] BIT NOT NULL DEFAULT 1'
            ],
            // datetimes
            [
                'created',
                ['type' => 'datetime'],
                '[created] DATETIME'
            ],
            // Date & Time
            [
                'start_date',
                ['type' => 'date'],
                '[start_date] DATE'
            ],
            [
                'start_time',
                ['type' => 'time'],
                '[start_time] TIME'
            ],
            // timestamps
            [
                'created',
                ['type' => 'timestamp', 'null' => true],
                '[created] DATETIME DEFAULT NULL'
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
        $schema = new SqlserverSchema($driver);

        $table = (new Table('schema_articles'))->addColumn($name, $data);
        $this->assertEquals($expected, $schema->columnSql($table, $name));
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
                'PRIMARY KEY ([title])'
            ],
            [
                'unique_idx',
                ['type' => 'unique', 'columns' => ['title', 'author_id']],
                'CONSTRAINT [unique_idx] UNIQUE ([title], [author_id])'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id']],
                'CONSTRAINT [author_id_idx] FOREIGN KEY ([author_id]) ' .
                'REFERENCES [authors] ([id]) ON UPDATE SET NULL ON DELETE SET NULL'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'cascade'],
                'CONSTRAINT [author_id_idx] FOREIGN KEY ([author_id]) ' .
                'REFERENCES [authors] ([id]) ON UPDATE CASCADE ON DELETE SET NULL'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'setDefault'],
                'CONSTRAINT [author_id_idx] FOREIGN KEY ([author_id]) ' .
                'REFERENCES [authors] ([id]) ON UPDATE SET DEFAULT ON DELETE SET NULL'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'setNull'],
                'CONSTRAINT [author_id_idx] FOREIGN KEY ([author_id]) ' .
                'REFERENCES [authors] ([id]) ON UPDATE SET NULL ON DELETE SET NULL'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'noAction'],
                'CONSTRAINT [author_id_idx] FOREIGN KEY ([author_id]) ' .
                'REFERENCES [authors] ([id]) ON UPDATE NO ACTION ON DELETE SET NULL'
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
        $schema = new SqlserverSchema($driver);

        $table = (new Table('schema_articles'))->addColumn('title', [
            'type' => 'string',
            'length' => 255
        ])->addColumn('author_id', [
            'type' => 'integer',
        ])->addConstraint($name, $data);

        $this->assertEquals($expected, $schema->constraintSql($table, $name));
    }

    /**
     * Test the addConstraintSql method.
     *
     * @return void
     */
    public function testAddConstraintSql()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMock('Cake\Database\Connection', [], [], '', false);
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
            'ALTER TABLE [posts] ADD CONSTRAINT [author_fk] FOREIGN KEY ([author_id]) REFERENCES [authors] ([id]) ON UPDATE CASCADE ON DELETE CASCADE;',
            'ALTER TABLE [posts] ADD CONSTRAINT [category_fk] FOREIGN KEY ([category_id], [category_name]) REFERENCES [categories] ([id], [name]) ON UPDATE CASCADE ON DELETE CASCADE;'
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
        $connection = $this->getMock('Cake\Database\Connection', [], [], '', false);
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
            'ALTER TABLE [posts] DROP CONSTRAINT [author_fk];',
            'ALTER TABLE [posts] DROP CONSTRAINT [category_fk];'
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
        $connection = $this->getMock('Cake\Database\Connection', [], [], '', false);
        $connection->expects($this->any())->method('driver')
            ->will($this->returnValue($driver));

        $table = (new Table('schema_articles'))->addColumn('id', [
                'type' => 'integer',
                'null' => false
            ])
            ->addColumn('title', [
                'type' => 'string',
                'null' => false,
            ])
            ->addColumn('body', ['type' => 'text'])
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
CREATE TABLE [schema_articles] (
[id] INTEGER IDENTITY(1, 1),
[title] NVARCHAR(255) NOT NULL,
[body] NVARCHAR(MAX),
[created] DATETIME,
PRIMARY KEY ([id])
)
SQL;
        $result = $table->createSql($connection);

        $this->assertCount(2, $result);
        $this->assertEquals(str_replace("\r\n", "\n", $expected), str_replace("\r\n", "\n", $result[0]));
        $this->assertEquals(
            'CREATE INDEX [title_idx] ON [schema_articles] ([title])',
            $result[1]
        );
    }

    /**
     * test dropSql
     *
     * @return void
     */
    public function testDropSql()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMock('Cake\Database\Connection', [], [], '', false);
        $connection->expects($this->any())->method('driver')
            ->will($this->returnValue($driver));

        $table = new Table('schema_articles');
        $result = $table->dropSql($connection);
        $this->assertCount(1, $result);
        $this->assertEquals('DROP TABLE [schema_articles]', $result[0]);
    }

    /**
     * Test truncateSql()
     *
     * @return void
     */
    public function testTruncateSql()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMock('Cake\Database\Connection', [], [], '', false);
        $connection->expects($this->any())->method('driver')
            ->will($this->returnValue($driver));

        $table = new Table('schema_articles');
        $table->addColumn('id', 'integer')
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id']
            ]);
        $result = $table->truncateSql($connection);
        $this->assertCount(2, $result);
        $this->assertEquals('DELETE FROM [schema_articles]', $result[0]);
        $this->assertEquals("DBCC CHECKIDENT('schema_articles', RESEED, 0)", $result[1]);
    }

    /**
     * Get a schema instance with a mocked driver/pdo instances
     *
     * @return Driver
     */
    protected function _getMockedDriver()
    {
        $driver = new \Cake\Database\Driver\Sqlserver();
        $mock = $this->getMock('FakePdo', ['quote', 'quoteIdentifier']);
        $mock->expects($this->any())
            ->method('quote')
            ->will($this->returnCallback(function ($value) {
                return '[' . $value . ']';
            }));
        $mock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnCallback(function ($value) {
                return '[' . $value . ']';
            }));
        $driver->connection($mock);
        return $driver;
    }
}
