<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Schema\UniqueKey;
use Cake\TestSuite\TestCase;

/**
 * Tests for the UniqueKey class.
 */
class UniqueKeyTest extends TestCase
{
    public function testSetType(): void
    {
        $index = new UniqueKey('title_idx', ['title']);
        $this->assertSame(UniqueKey::UNIQUE, $index->getType());

        // types are not checked.
        $index->setType('check');
        $this->assertSame('check', $index->getType());
    }

    public function testSetColumns(): void
    {
        $index = new UniqueKey('title_idx', []);
        $this->assertSame([], $index->getColumns());

        $index->setColumns(['title']);
        $this->assertSame(['title'], $index->getColumns());

        $index->setColumns(['title', 'name']);
        $this->assertSame(['title', 'name'], $index->getColumns());
    }

    public function testSetName(): void
    {
        $index = new UniqueKey('title_idx', ['title']);
        $this->assertSame('title_idx', $index->getName());

        $index->setName('my_index');
        $this->assertSame('my_index', $index->getName());
    }

    public function testSetLength(): void
    {
        $index = new UniqueKey('title_idx', ['title']);
        $this->assertNull($index->getLength());

        $index->setLength(['title' => 10]);
        $this->assertSame(['title' => 10], $index->getLength());
    }
}
