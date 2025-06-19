<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Schema\Column;
use Cake\Database\Schema\PostgresSchemaDialect;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\TestSuite\TestCase;
use RuntimeException;

class ColumnTest extends TestCase
{
    public function testSetName(): void
    {
        $column = new Column();
        $this->assertEquals('', $column->getName());

        $column->setName('id');
        $this->assertSame('id', $column->getName());
    }

    public function testSetType(): void
    {
        $column = new Column();
        $this->assertEquals(TableSchemaInterface::TYPE_STRING, $column->getType());

        $column->setType('integer');
        $this->assertSame('integer', $column->getType());

        // Types are not validated, so that we can preserve types we don't have specific handling
        // for, as drivers and dialects can implement their own types.
        $column->setType('imaginary');
        $this->assertSame('imaginary', $column->getType());
    }

    public function testSetLength(): void
    {
        $column = new Column();
        $this->assertNull($column->getLength());

        $column->setLength(255);
        $this->assertSame(255, $column->getLength());
    }

    public function testSetNull(): void
    {
        $column = new Column();
        $this->assertTrue($column->isNull());
        $this->assertTrue($column->getNull());

        $column->setNull(false);
        $this->assertFalse($column->isNull());
        $this->assertFalse($column->getNull());

        $column->setNull(true);
        $this->assertTrue($column->isNull());
        $this->assertTrue($column->getNull());
    }

    public function testSetDefault(): void
    {
        $column = new Column();
        $this->assertNull($column->getDefault());

        $column->setDefault('default_value');
        $this->assertSame('default_value', $column->getDefault());
    }

    public function testSetGenerated(): void
    {
        $column = new Column();
        $this->assertEquals(PostgresSchemaDialect::GENERATED_BY_DEFAULT, $column->getGenerated());

        $column->setGenerated('by default');
        $this->assertEquals('by default', $column->getGenerated());
    }

    public function testSetIdentity(): void
    {
        $column = new Column();
        $this->assertFalse($column->isIdentity());

        $column->setIdentity(true);
        $this->assertTrue($column->isIdentity());
        $this->assertTrue($column->getIdentity());

        $column->setIdentity(false);
        $this->assertFalse($column->isIdentity());
        $this->assertFalse($column->getIdentity());
    }

    public function testSetOnUpdate(): void
    {
        $column = new Column();
        $this->assertNull($column->getOnUpdate());

        $column->setOnUpdate('CURRENT_TIMESTAMP');
        $this->assertSame('CURRENT_TIMESTAMP', $column->getOnUpdate());
    }

    public function testSetAttributesThrowsExceptionIfOptionIsNotString(): void
    {
        $column = new Column();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"0" is not a valid column option.');

        $column->setAttributes(['identity']);
    }

    public function testSetAfter(): void
    {
        $column = new Column();
        $this->assertNull($column->getAfter());

        $column->setAfter('previous_column');
        $this->assertSame('previous_column', $column->getAfter());
    }

    public function testSetComment(): void
    {
        $column = new Column();
        $this->assertNull($column->getComment());

        $column->setComment('This is a comment');
        $this->assertSame('This is a comment', $column->getComment());
    }

    public function testSetUnsigned(): void
    {
        $column = new Column();
        $this->assertTrue($column->getUnsigned());

        $column->setUnsigned(false);
        $this->assertFalse($column->getUnsigned());

        $column->setUnsigned(true);
        $this->assertTrue($column->getUnsigned());
        $this->assertTrue($column->isUnsigned());
        $this->assertFalse($column->isSigned());
    }

    public function testSetAttributesIdentity(): void
    {
        $column = new Column();
        $this->assertTrue($column->isNull());
        $this->assertFalse($column->isIdentity());

        $column->setAttributes(['identity' => true]);
        $this->assertFalse($column->isNull());
        $this->assertTrue($column->isIdentity());
    }

    public function testSetCollation(): void
    {
        $column = new Column();
        $this->assertNull($column->getCollate());

        $column->setCollate('utf8mb4_general_ci');
        $this->assertSame('utf8mb4_general_ci', $column->getCollate());
    }

    public function testSetSrid(): void
    {
        $column = new Column();
        $this->assertNull($column->getSrid());

        $column->setSrid(4326);
        $this->assertSame(4326, $column->getSrid());
    }

    public function testSetAttributes(): void
    {
        $column = new Column();
        $options = [
            'type' => 'string',
            'length' => 255,
            'null' => false,
            'default' => 'default_value',
            'collate' => 'utf8mb4_general_ci',
        ];
        $column->setAttributes($options);

        $this->assertSame(255, $column->getLength());
        $this->assertFalse($column->isNull());
        $this->assertSame('default_value', $column->getDefault());
        $this->assertSame('utf8mb4_general_ci', $column->getCollate());
    }
}
