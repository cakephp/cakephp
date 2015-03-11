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

use Cake\Database\Schema\Table;
use Cake\TestSuite\TestCase;

/**
 * Test case for Table
 */
class TableTest extends TestCase
{

    /**
     * Test construction with columns
     *
     * @return void
     */
    public function testConstructWithColumns()
    {
        $columns = [
            'id' => [
                'type' => 'integer',
                'length' => 11,
            ],
            'title' => [
                'type' => 'string',
                'length' => 255
            ]
        ];
        $table = new Table('articles', $columns);
        $this->assertEquals(['id', 'title'], $table->columns());
    }

    /**
     * Test adding columns.
     *
     * @return void
     */
    public function testAddColumn()
    {
        $table = new Table('articles');
        $result = $table->addColumn('title', [
            'type' => 'string',
            'length' => 25,
            'null' => false
        ]);
        $this->assertSame($table, $result);
        $this->assertEquals(['title'], $table->columns());

        $result = $table->addColumn('body', 'text');
        $this->assertSame($table, $result);
        $this->assertEquals(['title', 'body'], $table->columns());
    }

    /**
     * Test isNullable method
     *
     * @return void
     */
    public function testIsNullable()
    {
        $table = new Table('articles');
        $table->addColumn('title', [
            'type' => 'string',
            'length' => 25,
            'null' => false
        ])->addColumn('tagline', [
            'type' => 'string',
            'length' => 25,
            'null' => true
        ]);
        $this->assertFalse($table->isNullable('title'));
        $this->assertTrue($table->isNullable('tagline'));
        $this->assertTrue($table->isNullable('missing'));
    }

    /**
     * Test columnType method
     *
     * @return void
     */
    public function testColumnType()
    {
        $table = new Table('articles');
        $table->addColumn('title', [
            'type' => 'string',
            'length' => 25,
            'null' => false
        ]);
        $this->assertEquals('string', $table->columnType('title'));
        $this->assertNull($table->columnType('not there'));
    }

    /**
     * Test columnType setter method
     *
     * @return void
     */
    public function testColumnTypeSet()
    {
        $table = new Table('articles');
        $table->addColumn('title', [
            'type' => 'string',
            'length' => 25,
            'null' => false
        ]);
        $this->assertEquals('string', $table->columnType('title'));
        $table->columnType('title', 'json');
        $this->assertEquals('json', $table->columnType('title'));
    }

    /**
     * Attribute keys should be filtered and have defaults set.
     *
     * @return void
     */
    public function testAddColumnFiltersAttributes()
    {
        $table = new Table('articles');
        $table->addColumn('title', [
            'type' => 'string'
        ]);
        $result = $table->column('title');
        $expected = [
            'type' => 'string',
            'length' => null,
            'precision' => null,
            'default' => null,
            'null' => null,
            'fixed' => null,
            'comment' => null,
        ];
        $this->assertEquals($expected, $result);

        $table->addColumn('author_id', [
            'type' => 'integer'
        ]);
        $result = $table->column('author_id');
        $expected = [
            'type' => 'integer',
            'length' => null,
            'precision' => null,
            'default' => null,
            'null' => null,
            'unsigned' => null,
            'comment' => null,
            'autoIncrement' => null,
        ];
        $this->assertEquals($expected, $result);

        $table->addColumn('amount', [
            'type' => 'decimal'
        ]);
        $result = $table->column('amount');
        $expected = [
            'type' => 'decimal',
            'length' => null,
            'precision' => null,
            'default' => null,
            'null' => null,
            'unsigned' => null,
            'comment' => null,
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test reading default values.
     *
     * @return void
     */
    public function testDefaultValues()
    {
        $table = new Table('articles');
        $table->addColumn('id', [
            'type' => 'integer',
            'default' => 0
        ])->addColumn('title', [
            'type' => 'string',
            'default' => 'A title'
        ])->addColumn('name', [
            'type' => 'string',
            'null' => false,
            'default' => null,
        ])->addColumn('body', [
            'type' => 'text',
            'null' => true,
            'default' => null,
        ]);
        $result = $table->defaultValues();
        $expected = [
            'id' => 0,
            'title' => 'A title',
            'body' => null
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding an constraint.
     * >
     * @return void
     */
    public function testAddConstraint()
    {
        $table = new Table('articles');
        $table->addColumn('id', [
            'type' => 'integer'
        ]);
        $result = $table->addConstraint('primary', [
            'type' => 'primary',
            'columns' => ['id']
        ]);
        $this->assertSame($result, $table);
        $this->assertEquals(['primary'], $table->constraints());
    }

    /**
     * Dataprovider for invalid addConstraint calls.
     *
     * @return array
     */
    public static function addConstaintErrorProvider()
    {
        return [
            // No properties
            [[]],
            // Empty columns
            [['columns' => '', 'type' => Table::CONSTRAINT_UNIQUE]],
            [['columns' => [], 'type' => Table::CONSTRAINT_UNIQUE]],
            // Missing column
            [['columns' => ['derp'], 'type' => Table::CONSTRAINT_UNIQUE]],
            // Invalid type
            [['columns' => 'author_id', 'type' => 'derp']],
        ];
    }

    /**
     * Test that an exception is raised when constraints
     * are added for fields that do not exist.
     *
     * @dataProvider addConstaintErrorProvider
     * @expectedException \Cake\Database\Exception
     * @return void
     */
    public function testAddConstraintError($props)
    {
        $table = new Table('articles');
        $table->addColumn('author_id', 'integer');
        $table->addConstraint('author_idx', $props);
    }

    /**
     * Test adding an index.
     *
     * @return void
     */
    public function testAddIndex()
    {
        $table = new Table('articles');
        $table->addColumn('title', [
            'type' => 'string'
        ]);
        $result = $table->addIndex('faster', [
            'type' => 'index',
            'columns' => ['title']
        ]);
        $this->assertSame($result, $table);
        $this->assertEquals(['faster'], $table->indexes());
    }

    /**
     * Dataprovider for invalid addIndex calls
     *
     * @return array
     */
    public static function addIndexErrorProvider()
    {
        return [
            // Empty
            [[]],
            // Invalid type
            [['columns' => 'author_id', 'type' => 'derp']],
            // No columns
            [['columns' => ''], 'type' => Table::INDEX_INDEX],
            [['columns' => [], 'type' => Table::INDEX_INDEX]],
            // Missing column
            [['columns' => ['not_there'], 'type' => Table::INDEX_INDEX]],
        ];
    }

    /**
     * Test that an exception is raised when indexes
     * are added for fields that do not exist.
     *
     * @dataProvider addIndexErrorProvider
     * @expectedException \Cake\Database\Exception
     * @return void
     */
    public function testAddIndexError($props)
    {
        $table = new Table('articles');
        $table->addColumn('author_id', 'integer');
        $table->addIndex('author_idx', $props);
    }

    /**
     * Test adding different kinds of indexes.
     *
     * @return void
     */
    public function testAddIndexTypes()
    {
        $table = new Table('articles');
        $table->addColumn('id', 'integer')
            ->addColumn('title', 'string')
            ->addColumn('author_id', 'integer');

        $table->addIndex('author_idx', [
                'columns' => ['author_id'],
                'type' => 'index'
            ])->addIndex('texty', [
                'type' => 'fulltext',
                'columns' => ['title']
            ]);

        $this->assertEquals(
            ['author_idx', 'texty'],
            $table->indexes()
        );
    }

    /**
     * Test getting the primary key.
     *
     * @return void
     */
    public function testPrimaryKey()
    {
        $table = new Table('articles');
        $table->addColumn('id', 'integer')
            ->addColumn('title', 'string')
            ->addColumn('author_id', 'integer')
            ->addConstraint('author_idx', [
                'columns' => ['author_id'],
                'type' => 'unique'
            ])->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id']
            ]);
        $this->assertEquals(['id'], $table->primaryKey());

        $table = new Table('articles');
        $table->addColumn('id', 'integer')
            ->addColumn('title', 'string')
            ->addColumn('author_id', 'integer');
        $this->assertEquals([], $table->primaryKey());
    }

    /**
     * Test the options method.
     *
     * @return void
     */
    public function testOptions()
    {
        $table = new Table('articles');
        $options = [
            'engine' => 'InnoDB'
        ];
        $return = $table->options($options);
        $this->assertInstanceOf('Cake\Database\Schema\Table', $return);
        $this->assertEquals($options, $table->options());
    }

    /**
     * Add a basic foreign key constraint.
     *
     * @return void
     */
    public function testAddConstraintForeignKey()
    {
        $table = new Table('articles');
        $table->addColumn('author_id', 'integer')
            ->addConstraint('author_id_idx', [
                'type' => Table::CONSTRAINT_FOREIGN,
                'columns' => ['author_id'],
                'references' => ['authors', 'id'],
                'update' => 'cascade',
                'delete' => 'cascade',
            ]);
        $this->assertEquals(['author_id_idx'], $table->constraints());
    }

    /**
     * Provider for exceptionally bad foreign key data.
     *
     * @return array
     */
    public static function badForeignKeyProvider()
    {
        return [
            'references is bad' => [[
                'type' => Table::CONSTRAINT_FOREIGN,
                'columns' => ['author_id'],
                'references' => ['authors'],
                'delete' => 'derp',
            ]],
            'bad update value' => [[
                'type' => Table::CONSTRAINT_FOREIGN,
                'columns' => ['author_id'],
                'references' => ['authors', 'id'],
                'update' => 'derp',
            ]],
            'bad delete value' => [[
                'type' => Table::CONSTRAINT_FOREIGN,
                'columns' => ['author_id'],
                'references' => ['authors', 'id'],
                'delete' => 'derp',
            ]],
        ];
    }

    /**
     * Add a foreign key constraint with bad data
     *
     * @dataProvider badForeignKeyProvider
     * @expectedException \Cake\Database\Exception
     * @return void
     */
    public function testAddConstraintForeignKeyBadData($data)
    {
        $table = new Table('articles');
        $table->addColumn('author_id', 'integer')
            ->addConstraint('author_id_idx', $data);
    }

    /**
     * Tests the temporary() method
     *
     * @return void
     */
    public function testTemporary()
    {
        $table = new Table('articles');
        $this->assertFalse($table->temporary());
        $this->assertSame($table, $table->temporary(true));
        $this->assertTrue($table->temporary());
        $table->temporary(false);
        $this->assertFalse($table->temporary());
    }
}
