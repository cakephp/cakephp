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
namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Driver\Mysql;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\MysqlSchema;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Test case for Mysql Schema Dialect.
 */
class MysqlSchemaTest extends TestCase
{

    /**
     * Helper method for skipping tests that need a real connection.
     *
     * @return void
     */
    protected function _needsConnection()
    {
        $config = ConnectionManager::config('test');
        $this->skipIf(strpos($config['driver'], 'Mysql') === false, 'Not using Mysql for test config');
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
                'TIMESTAMP',
                ['type' => 'timestamp', 'length' => null]
            ],
            [
                'TINYINT(1)',
                ['type' => 'boolean', 'length' => null]
            ],
            [
                'TINYINT(2)',
                ['type' => 'tinyinteger', 'length' => 2, 'unsigned' => false]
            ],
            [
                'TINYINT(3)',
                ['type' => 'tinyinteger', 'length' => 3, 'unsigned' => false]
            ],
            [
                'TINYINT(3) UNSIGNED',
                ['type' => 'tinyinteger', 'length' => 3, 'unsigned' => true]
            ],
            [
                'SMALLINT(4)',
                ['type' => 'smallinteger', 'length' => 4, 'unsigned' => false]
            ],
            [
                'SMALLINT(4) UNSIGNED',
                ['type' => 'smallinteger', 'length' => 4, 'unsigned' => true]
            ],
            [
                'INTEGER(11)',
                ['type' => 'integer', 'length' => 11, 'unsigned' => false]
            ],
            [
                'MEDIUMINT(11)',
                ['type' => 'integer', 'length' => 11, 'unsigned' => false]
            ],
            [
                'INTEGER(11) UNSIGNED',
                ['type' => 'integer', 'length' => 11, 'unsigned' => true]
            ],
            [
                'BIGINT',
                ['type' => 'biginteger', 'length' => null, 'unsigned' => false]
            ],
            [
                'BIGINT UNSIGNED',
                ['type' => 'biginteger', 'length' => null, 'unsigned' => true]
            ],
            [
                'VARCHAR(255)',
                ['type' => 'string', 'length' => 255]
            ],
            [
                'CHAR(25)',
                ['type' => 'string', 'length' => 25, 'fixed' => true]
            ],
            [
                'CHAR(36)',
                ['type' => 'uuid', 'length' => null]
            ],
            [
                'TEXT',
                ['type' => 'text', 'length' => null]
            ],
            [
                'TINYTEXT',
                ['type' => 'text', 'length' => TableSchema::LENGTH_TINY]
            ],
            [
                'MEDIUMTEXT',
                ['type' => 'text', 'length' => TableSchema::LENGTH_MEDIUM]
            ],
            [
                'LONGTEXT',
                ['type' => 'text', 'length' => TableSchema::LENGTH_LONG]
            ],
            [
                'TINYBLOB',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_TINY]
            ],
            [
                'BLOB',
                ['type' => 'binary', 'length' => null]
            ],
            [
                'MEDIUMBLOB',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_MEDIUM]
            ],
            [
                'LONGBLOB',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_LONG]
            ],
            [
                'FLOAT',
                ['type' => 'float', 'length' => null, 'precision' => null, 'unsigned' => false]
            ],
            [
                'DOUBLE',
                ['type' => 'float', 'length' => null, 'precision' => null, 'unsigned' => false]
            ],
            [
                'DOUBLE UNSIGNED',
                ['type' => 'float', 'length' => null, 'precision' => null, 'unsigned' => true]
            ],
            [
                'DECIMAL(11,2) UNSIGNED',
                ['type' => 'decimal', 'length' => 11, 'precision' => 2, 'unsigned' => true]
            ],
            [
                'DECIMAL(11,2)',
                ['type' => 'decimal', 'length' => 11, 'precision' => 2, 'unsigned' => false]
            ],
            [
                'FLOAT(11,2)',
                ['type' => 'float', 'length' => 11, 'precision' => 2, 'unsigned' => false]
            ],
            [
                'FLOAT(11,2) UNSIGNED',
                ['type' => 'float', 'length' => 11, 'precision' => 2, 'unsigned' => true]
            ],
            [
                'DOUBLE(10,4)',
                ['type' => 'float', 'length' => 10, 'precision' => 4, 'unsigned' => false]
            ],
            [
                'DOUBLE(10,4) UNSIGNED',
                ['type' => 'float', 'length' => 10, 'precision' => 4, 'unsigned' => true]
            ],
            [
                'JSON',
                ['type' => 'json', 'length' => null]
            ],
        ];
    }

    /**
     * Test parsing MySQL column types from field description.
     *
     * @dataProvider convertColumnProvider
     * @return void
     */
    public function testConvertColumn($type, $expected)
    {
        $field = [
            'Field' => 'field',
            'Type' => $type,
            'Null' => 'YES',
            'Default' => 'Default value',
            'Collation' => 'utf8_general_ci',
            'Comment' => 'Comment section',
        ];
        $expected += [
            'null' => true,
            'default' => 'Default value',
            'collate' => 'utf8_general_ci',
            'comment' => 'Comment section',
        ];

        $driver = $this->getMockBuilder('Cake\Database\Driver\Mysql')->getMock();
        $dialect = new MysqlSchema($driver);

        $table = $this->getMockBuilder(TableSchema::class)
            ->setConstructorArgs(['table'])
            ->getMock();
        $table->expects($this->at(0))->method('addColumn')->with('field', $expected);

        $dialect->convertColumnDescription($table, $field);
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
        $connection->execute('DROP TABLE IF EXISTS schema_json');

        $table = <<<SQL
            CREATE TABLE schema_authors (
                id INT(11) PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(50),
                bio TEXT,
                created DATETIME
            )ENGINE=InnoDB
SQL;
        $connection->execute($table);

        $table = <<<SQL
            CREATE TABLE schema_articles (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(20) COMMENT 'A title',
                body TEXT,
                author_id INT(11) NOT NULL,
                published BOOLEAN DEFAULT 0,
                allow_comments TINYINT(1) DEFAULT 0,
                created DATETIME,
                KEY `author_idx` (`author_id`),
                UNIQUE KEY `length_idx` (`title`(4)),
                FOREIGN KEY `author_idx` (`author_id`) REFERENCES `schema_authors`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB COLLATE=utf8_general_ci
SQL;
        $connection->execute($table);

        if ($connection->driver()->supportsNativeJson()) {
            $table = <<<SQL
                CREATE TABLE schema_json (
                    id INT(11) PRIMARY KEY AUTO_INCREMENT,
                    data JSON NOT NULL
                )
SQL;
            $connection->execute($table);
        }
    }

    /**
     * Integration test for SchemaCollection & MysqlDialect.
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
     * Test describing a table with Mysql
     *
     * @return void
     */
    public function testDescribeTable()
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_articles');
        $this->assertInstanceOf('Cake\Database\Schema\Table', $result);
        $expected = [
            'id' => [
                'type' => 'biginteger',
                'null' => false,
                'unsigned' => false,
                'default' => null,
                'length' => 20,
                'precision' => null,
                'comment' => null,
                'autoIncrement' => true,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
                'default' => null,
                'length' => 20,
                'precision' => null,
                'comment' => 'A title',
                'fixed' => null,
                'collate' => 'utf8_general_ci',
            ],
            'body' => [
                'type' => 'text',
                'null' => true,
                'default' => null,
                'length' => null,
                'precision' => null,
                'comment' => null,
                'collate' => 'utf8_general_ci',
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => false,
                'unsigned' => false,
                'default' => null,
                'length' => 11,
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
            'allow_comments' => [
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
        ];
        $this->assertEquals(['id'], $result->primaryKey());
        foreach ($expected as $field => $definition) {
            $this->assertEquals(
                $definition,
                $result->column($field),
                'Field definition does not match for ' . $field
            );
        }
    }

    /**
     * Test describing a table with indexes in Mysql
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
            'length_idx' => [
                'type' => 'unique',
                'columns' => ['title'],
                'length' => [
                    'title' => 4,
                ]
            ],
            'schema_articles_ibfk_1' => [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['schema_authors', 'id'],
                'length' => [],
                'update' => 'cascade',
                'delete' => 'restrict',
            ]
        ];
        $this->assertEquals($expected['primary'], $result->constraint('primary'));
        $this->assertEquals($expected['length_idx'], $result->constraint('length_idx'));
        $this->assertEquals($expected['schema_articles_ibfk_1'], $result->constraint('schema_articles_ibfk_1'));

        $this->assertCount(1, $result->indexes());
        $expected = [
            'type' => 'index',
            'columns' => ['author_id'],
            'length' => []
        ];
        $this->assertEquals($expected, $result->index('author_idx'));
    }

    /**
     * Test describing a table creates options
     *
     * @return void
     */
    public function testDescribeTableOptions()
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_articles');
        $this->assertArrayHasKey('engine', $result->options());
        $this->assertArrayHasKey('collation', $result->options());
    }

    public function testDescribeNonPrimaryAutoIncrement()
    {
        $this->_needsConnection();
        $connection = ConnectionManager::get('test');

        $sql = <<<SQL
CREATE TABLE `odd_primary_key` (
`id` BIGINT UNSIGNED NOT NULL,
`other_field` INTEGER(11) NOT NULL AUTO_INCREMENT,
PRIMARY KEY (`id`),
UNIQUE KEY `other_field` (`other_field`)
)
SQL;
        $connection->execute($sql);
        $schema = new SchemaCollection($connection);
        $table = $schema->describe('odd_primary_key');
        $connection->execute('DROP TABLE odd_primary_key');

        $column = $table->column('id');
        $this->assertNull($column['autoIncrement'], 'should not autoincrement');
        $this->assertTrue($column['unsigned'], 'should be unsigned');

        $column = $table->column('other_field');
        $this->assertTrue($column['autoIncrement'], 'should not autoincrement');
        $this->assertFalse($column['unsigned'], 'should not be unsigned');

        $output = $table->createSql($connection);
        $this->assertContains('`id` BIGINT UNSIGNED NOT NULL,', $output[0]);
        $this->assertContains('`other_field` INTEGER(11) NOT NULL AUTO_INCREMENT,', $output[0]);
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
                ['type' => 'string', 'length' => 25, 'null' => true, 'default' => null],
                '`title` VARCHAR(25)',
            ],
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => false],
                '`title` VARCHAR(25) NOT NULL'
            ],
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => true, 'default' => 'ignored'],
                '`title` VARCHAR(25) DEFAULT \'ignored\'',
            ],
            [
                'title',
                ['type' => 'string', 'length' => 25, 'null' => true, 'default' => ''],
                '`title` VARCHAR(25) DEFAULT \'\'',
            ],
            [
                'role',
                ['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'admin'],
                '`role` VARCHAR(10) NOT NULL DEFAULT \'admin\''
            ],
            [
                'id',
                ['type' => 'string', 'length' => 32, 'fixed' => true, 'null' => false],
                '`id` CHAR(32) NOT NULL'
            ],
            [
                'title',
                ['type' => 'string'],
                '`title` VARCHAR(255)'
            ],
            [
                'id',
                ['type' => 'uuid'],
                '`id` CHAR(36)'
            ],
            [
                'title',
                ['type' => 'string', 'length' => 255, 'null' => false, 'collate' => 'utf8_unicode_ci'],
                '`title` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL'
            ],
            // Text
            [
                'body',
                ['type' => 'text', 'null' => false],
                '`body` TEXT NOT NULL'
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_TINY, 'null' => false],
                '`body` TINYTEXT NOT NULL'
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_MEDIUM, 'null' => false],
                '`body` MEDIUMTEXT NOT NULL'
            ],
            [
                'body',
                ['type' => 'text', 'length' => TableSchema::LENGTH_LONG, 'null' => false],
                '`body` LONGTEXT NOT NULL'
            ],
            [
                'body',
                ['type' => 'text', 'null' => false, 'collate' => 'utf8_unicode_ci'],
                '`body` TEXT COLLATE utf8_unicode_ci NOT NULL'
            ],
            // Blob / binary
            [
                'body',
                ['type' => 'binary', 'null' => false],
                '`body` BLOB NOT NULL'
            ],
            [
                'body',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_TINY, 'null' => false],
                '`body` TINYBLOB NOT NULL'
            ],
            [
                'body',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_MEDIUM, 'null' => false],
                '`body` MEDIUMBLOB NOT NULL'
            ],
            [
                'body',
                ['type' => 'binary', 'length' => TableSchema::LENGTH_LONG, 'null' => false],
                '`body` LONGBLOB NOT NULL'
            ],
            // Integers
            [
                'post_id',
                ['type' => 'tinyinteger', 'length' => 2],
                '`post_id` TINYINT(2)'
            ],
            [
                'post_id',
                ['type' => 'tinyinteger', 'length' => 2, 'unsigned' => true],
                '`post_id` TINYINT(2) UNSIGNED'
            ],
            [
                'post_id',
                ['type' => 'smallinteger', 'length' => 4],
                '`post_id` SMALLINT(4)'
            ],
            [
                'post_id',
                ['type' => 'smallinteger', 'length' => 4, 'unsigned' => true],
                '`post_id` SMALLINT(4) UNSIGNED'
            ],
            [
                'post_id',
                ['type' => 'integer', 'length' => 11],
                '`post_id` INTEGER(11)'
            ],
            [
                'post_id',
                ['type' => 'integer', 'length' => 11, 'unsigned' => true],
                '`post_id` INTEGER(11) UNSIGNED'
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'length' => 20],
                '`post_id` BIGINT'
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'length' => 20, 'unsigned' => true],
                '`post_id` BIGINT UNSIGNED'
            ],
            [
                'post_id',
                ['type' => 'integer', 'length' => 20, 'autoIncrement' => true],
                '`post_id` INTEGER(20) AUTO_INCREMENT'
            ],
            [
                'post_id',
                ['type' => 'integer', 'length' => 20, 'null' => false, 'autoIncrement' => false],
                '`post_id` INTEGER(20) NOT NULL'
            ],
            [
                'post_id',
                ['type' => 'biginteger', 'length' => 20, 'autoIncrement' => true],
                '`post_id` BIGINT AUTO_INCREMENT'
            ],
            // Decimal
            [
                'value',
                ['type' => 'decimal'],
                '`value` DECIMAL'
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 11, 'unsigned' => true],
                '`value` DECIMAL(11,0) UNSIGNED'
            ],
            [
                'value',
                ['type' => 'decimal', 'length' => 12, 'precision' => 5],
                '`value` DECIMAL(12,5)'
            ],
            // Float
            [
                'value',
                ['type' => 'float', 'unsigned'],
                '`value` FLOAT'
            ],
            [
                'value',
                ['type' => 'float', 'unsigned' => true],
                '`value` FLOAT UNSIGNED'
            ],
            [
                'value',
                ['type' => 'float', 'length' => 11, 'precision' => 3],
                '`value` FLOAT(11,3)'
            ],
            // Boolean
            [
                'checked',
                ['type' => 'boolean', 'default' => false],
                '`checked` BOOLEAN DEFAULT FALSE'
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => false, 'null' => false],
                '`checked` BOOLEAN NOT NULL DEFAULT FALSE'
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => true, 'null' => false],
                '`checked` BOOLEAN NOT NULL DEFAULT TRUE'
            ],
            [
                'checked',
                ['type' => 'boolean', 'default' => false, 'null' => true],
                '`checked` BOOLEAN DEFAULT FALSE'
            ],
            // datetimes
            [
                'created',
                ['type' => 'datetime', 'comment' => 'Created timestamp'],
                '`created` DATETIME COMMENT \'Created timestamp\''
            ],
            [
                'created',
                ['type' => 'datetime', 'null' => false, 'default' => 'current_timestamp'],
                '`created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'
            ],
            [
                'open_date',
                ['type' => 'datetime', 'null' => false, 'default' => '2016-12-07 23:04:00'],
                '`open_date` DATETIME NOT NULL DEFAULT \'2016-12-07 23:04:00\''
            ],
            // Date & Time
            [
                'start_date',
                ['type' => 'date'],
                '`start_date` DATE'
            ],
            [
                'start_time',
                ['type' => 'time'],
                '`start_time` TIME'
            ],
            // timestamps
            [
                'created',
                ['type' => 'timestamp', 'null' => true],
                '`created` TIMESTAMP NULL'
            ],
            [
                'created',
                ['type' => 'timestamp', 'null' => false, 'default' => 'current_timestamp'],
                '`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
            ],
            [
                'open_date',
                ['type' => 'timestamp', 'null' => false, 'default' => '2016-12-07 23:04:00'],
                '`open_date` TIMESTAMP NOT NULL DEFAULT \'2016-12-07 23:04:00\''
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
        $schema = new MysqlSchema($driver);

        $table = (new TableSchema('articles'))->addColumn($name, $data);
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
                'PRIMARY KEY (`title`)'
            ],
            [
                'unique_idx',
                ['type' => 'unique', 'columns' => ['title', 'author_id']],
                'UNIQUE KEY `unique_idx` (`title`, `author_id`)'
            ],
            [
                'length_idx',
                [
                    'type' => 'unique',
                    'columns' => ['author_id', 'title'],
                    'length' => ['author_id' => 5, 'title' => 4]
                ],
                'UNIQUE KEY `length_idx` (`author_id`(5), `title`(4))'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id']],
                'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
                'REFERENCES `authors` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'cascade'],
                'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
                'REFERENCES `authors` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'restrict'],
                'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
                'REFERENCES `authors` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'setNull'],
                'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
                'REFERENCES `authors` (`id`) ON UPDATE SET NULL ON DELETE RESTRICT'
            ],
            [
                'author_id_idx',
                ['type' => 'foreign', 'columns' => ['author_id'], 'references' => ['authors', 'id'], 'update' => 'noAction'],
                'CONSTRAINT `author_id_idx` FOREIGN KEY (`author_id`) ' .
                'REFERENCES `authors` (`id`) ON UPDATE NO ACTION ON DELETE RESTRICT'
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
        $schema = new MysqlSchema($driver);

        $table = (new TableSchema('articles'))->addColumn('title', [
            'type' => 'string',
            'length' => 255
        ])->addColumn('author_id', [
            'type' => 'integer',
        ])->addConstraint($name, $data);

        $this->assertEquals($expected, $schema->constraintSql($table, $name));
    }

    /**
     * Test provider for indexSql()
     *
     * @return array
     */
    public static function indexSqlProvider()
    {
        return [
            [
                'key_key',
                ['type' => 'index', 'columns' => ['author_id']],
                'KEY `key_key` (`author_id`)'
            ],
            [
                'full_text',
                ['type' => 'fulltext', 'columns' => ['title']],
                'FULLTEXT KEY `full_text` (`title`)'
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
        $schema = new MysqlSchema($driver);

        $table = (new TableSchema('articles'))->addColumn('title', [
            'type' => 'string',
            'length' => 255
        ])->addColumn('author_id', [
            'type' => 'integer',
        ])->addIndex($name, $data);

        $this->assertEquals($expected, $schema->indexSql($table, $name));
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
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));

        $table = (new TableSchema('posts'))
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
            'ALTER TABLE `posts` ADD CONSTRAINT `author_fk` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;',
            'ALTER TABLE `posts` ADD CONSTRAINT `category_fk` FOREIGN KEY (`category_id`, `category_name`) REFERENCES `categories` (`id`, `name`) ON UPDATE CASCADE ON DELETE CASCADE;'
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
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));

        $table = (new TableSchema('posts'))
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
            'ALTER TABLE `posts` DROP FOREIGN KEY `author_fk`;',
            'ALTER TABLE `posts` DROP FOREIGN KEY `category_fk`;'
        ];
        $result = $table->dropConstraintSql($connection);
        $this->assertCount(2, $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test generating a column that is a primary key.
     *
     * @return void
     */
    public function testColumnSqlPrimaryKey()
    {
        $driver = $this->_getMockedDriver();
        $schema = new MysqlSchema($driver);

        $table = new TableSchema('articles');
        $table->addColumn('id', [
                'type' => 'integer',
                'null' => false,
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id']
            ]);
        $result = $schema->columnSql($table, 'id');
        $this->assertEquals($result, '`id` INTEGER NOT NULL AUTO_INCREMENT');

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
        $this->assertEquals($result, '`id` BIGINT NOT NULL AUTO_INCREMENT');
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
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));

        $driver->connection()
            ->expects($this->any())
            ->method('getAttribute')
            ->will($this->returnValue('5.6.0'));

        $table = (new TableSchema('posts'))->addColumn('id', [
                'type' => 'integer',
                'null' => false
            ])
            ->addColumn('title', [
                'type' => 'string',
                'null' => false,
                'comment' => 'The title'
            ])
            ->addColumn('body', [
                'type' => 'text',
                'comment' => ''
            ])
            ->addColumn('data', [
                'type' => 'json'
            ])
            ->addColumn('hash', [
                'type' => 'string',
                'fixed' => true,
                'length' => 40,
                'collate' => 'latin1_bin',
                'null' => false,
            ])
            ->addColumn('created', 'datetime')
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id']
            ])
            ->options([
                'engine' => 'InnoDB',
                'charset' => 'utf8',
                'collate' => 'utf8_general_ci',
            ]);

        $expected = <<<SQL
CREATE TABLE `posts` (
`id` INTEGER NOT NULL AUTO_INCREMENT,
`title` VARCHAR(255) NOT NULL COMMENT 'The title',
`body` TEXT,
`data` LONGTEXT,
`hash` CHAR(40) COLLATE latin1_bin NOT NULL,
`created` DATETIME,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL;
        $result = $table->createSql($connection);
        $this->assertCount(1, $result);
        $this->assertTextEquals($expected, $result[0]);
    }

    /**
     * Integration test for converting a Schema\Table with native JSON
     *
     * @return void
     */
    public function testCreateSqlJson()
    {
        $driver = $this->_getMockedDriver();
        $connection = $this->getMockBuilder('Cake\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('getDriver')
            ->will($this->returnValue($driver));

        $driver->connection()
            ->expects($this->any())
            ->method('getAttribute')
            ->will($this->returnValue('5.7.0'));

        $table = (new TableSchema('posts'))->addColumn('id', [
                'type' => 'integer',
                'null' => false
            ])
            ->addColumn('data', [
                'type' => 'json'
            ])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id']
            ])
            ->options([
                'engine' => 'InnoDB',
                'charset' => 'utf8',
                'collate' => 'utf8_general_ci',
            ]);

        $expected = <<<SQL
CREATE TABLE `posts` (
`id` INTEGER NOT NULL AUTO_INCREMENT,
`data` JSON,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL;
        $result = $table->createSql($connection);
        $this->assertCount(1, $result);
        $this->assertTextEquals($expected, $result[0]);
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
        $connection->expects($this->any())->method('getDriver')
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
        $connection->expects($this->any())->method('getDriver')
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
CREATE TABLE `articles_tags` (
`article_id` INTEGER NOT NULL,
`tag_id` INTEGER NOT NULL,
PRIMARY KEY (`article_id`, `tag_id`)
)
SQL;
        $result = $table->createSql($connection);
        $this->assertCount(1, $result);
        $this->assertTextEquals($expected, $result[0]);

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
CREATE TABLE `composite_key` (
`id` INTEGER NOT NULL AUTO_INCREMENT,
`account_id` INTEGER NOT NULL,
PRIMARY KEY (`id`, `account_id`)
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
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));

        $table = new TableSchema('articles');
        $result = $table->dropSql($connection);
        $this->assertCount(1, $result);
        $this->assertEquals('DROP TABLE `articles`', $result[0]);
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
        $connection->expects($this->any())->method('getDriver')
            ->will($this->returnValue($driver));

        $table = new TableSchema('articles');
        $result = $table->truncateSql($connection);
        $this->assertCount(1, $result);
        $this->assertEquals('TRUNCATE TABLE `articles`', $result[0]);
    }

    /**
     * Test that constructing a schema dialect connects the driver.
     *
     * @return void
     */
    public function testConstructConnectsDriver()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
        $driver->expects($this->once())
            ->method('connect');
        $schema = new MysqlSchema($driver);
    }

    /**
     * Tests json column parsing on Mysql 5.7+
     *
     * @return void
     */
    public function testDescribeJson()
    {
        $connection = ConnectionManager::get('test');
        $this->_createTables($connection);
        $this->skipIf(!$connection->driver()->supportsNativeJson(), 'Does not support native json');

        $schema = new SchemaCollection($connection);
        $result = $schema->describe('schema_json');
        $this->assertInstanceOf('Cake\Database\Schema\Table', $result);
        $expected = [
            'type' => 'json',
            'null' => false,
            'default' => null,
            'length' => null,
            'precision' => null,
            'comment' => null,
        ];
        $this->assertEquals(
            $expected,
            $result->column('data'),
            'Field definition does not match for data'
        );
    }

    /**
     * Get a schema instance with a mocked driver/pdo instances
     *
     * @return MysqlSchema
     */
    protected function _getMockedDriver()
    {
        $driver = new Mysql();
        $mock = $this->getMockBuilder(PDO::class)
            ->setMethods(['quote', 'quoteIdentifier', 'getAttribute'])
            ->disableOriginalConstructor()
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
