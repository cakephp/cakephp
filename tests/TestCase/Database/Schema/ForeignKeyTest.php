<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Schema\ForeignKey;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * Tests for the ForeignKey class.
 */
class ForeignKeyTest extends TestCase
{
    public function testSetType(): void
    {
        $key = new ForeignKey('user_fk', ['user_id'], 'users', ['id']);
        $this->assertSame(ForeignKey::FOREIGN, $key->getType());

        // types are not checked.
        $key->setType('derp');
        $this->assertSame('derp', $key->getType());
    }

    public function testSetColumns(): void
    {
        $key = new ForeignKey('title_idx', []);
        $this->assertSame([], $key->getColumns());

        $key->setColumns(['title']);
        $this->assertSame(['title'], $key->getColumns());

        $key->setColumns(['title', 'name']);
        $this->assertSame(['title', 'name'], $key->getColumns());
    }

    public function testSetName(): void
    {
        $key = new ForeignKey('title_idx', ['title'], 'users', ['id']);
        $this->assertSame('title_idx', $key->getName());

        $key->setName('my_index');
        $this->assertSame('my_index', $key->getName());
    }

    public function testSetReferencedTable(): void
    {
        $key = new ForeignKey('title_idx', ['title'], 'users', ['id']);
        $this->assertSame('users', $key->getReferencedTable());

        $key->setReferencedTable('users_new');
        $this->assertSame('users_new', $key->getReferencedTable());
    }

    public function testSetReferencedColumns(): void
    {
        $key = new ForeignKey('title_idx', ['title'], 'users', ['id']);
        $this->assertSame(['id'], $key->getReferencedColumns());

        $key->setReferencedColumns(['id', 'name']);
        $this->assertSame(['id', 'name'], $key->getReferencedColumns());
    }

    public function testSetOnDelete(): void
    {
        $key = new ForeignKey('title_idx', ['title'], 'users', ['id']);
        $this->assertSame(ForeignKey::NO_ACTION, $key->getOnDelete());

        $key->setOnDelete(ForeignKey::CASCADE);
        $this->assertSame(ForeignKey::CASCADE, $key->getOnDelete());

        $key->setOnDelete(ForeignKey::RESTRICT);
        $this->assertSame(ForeignKey::RESTRICT, $key->getOnDelete());
    }

    public function testSetOnDeleteValidateConstructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ForeignKey('title_idx', ['title'], 'users', ['id'], onDelete: 'invalid');
    }

    public function testSetOnUpdateValidateConstructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ForeignKey('title_idx', ['title'], 'users', ['id'], onUpdate: 'invalid');
    }

    public function testSetOnUpdateValidateSetter(): void
    {
        $key = new ForeignKey('title_idx', ['title'], 'users', ['id']);

        $this->expectException(InvalidArgumentException::class);
        $key->setOnUpdate('invalid');
    }

    public function testSetOnDeleteValidateSetter(): void
    {
        $key = new ForeignKey('title_idx', ['title'], 'users', ['id']);

        $this->expectException(InvalidArgumentException::class);
        $key->setOnDelete('invalid');
    }
}
