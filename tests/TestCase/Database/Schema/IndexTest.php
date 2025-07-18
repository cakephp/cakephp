<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Schema\Index;
use Cake\TestSuite\TestCase;

/**
 * Tests for the Index class.
 */
class IndexTest extends TestCase
{
    public function testSetType(): void
    {
        $index = new Index('title_idx', ['title']);
        $this->assertSame(Index::INDEX, $index->getType());

        $index->setType(Index::FULLTEXT);
        $this->assertSame(Index::FULLTEXT, $index->getType());

        $index->setType(Index::INDEX);
        $this->assertSame(Index::INDEX, $index->getType());
    }

    public function testSetColumns(): void
    {
        $index = new Index('title_idx', []);
        $this->assertSame([], $index->getColumns());

        $index->setColumns(['title']);
        $this->assertSame(['title'], $index->getColumns());

        $index->setColumns(['title', 'name']);
        $this->assertSame(['title', 'name'], $index->getColumns());
    }

    public function testSetName(): void
    {
        $index = new Index('title_idx', ['title']);
        $this->assertSame('title_idx', $index->getName());

        $index->setName('my_index');
        $this->assertSame('my_index', $index->getName());
    }

    public function testSetLength(): void
    {
        $index = new Index('title_idx', ['title']);
        $this->assertNull($index->getLength());

        $index->setLength(255);
        $this->assertSame(255, $index->getLength());

        // MySQL supports per-column limits for indexes.
        $index->setLength(['title' => 100, 'name' => 50]);
        $this->assertSame(['title' => 100, 'name' => 50], $index->getLength());
    }

    public function testSetOrder(): void
    {
        $index = new Index('title_idx', ['title']);
        $this->assertNull($index->getOrder());

        $index->setOrder(['title' => 'ASC']);
        $this->assertSame(['title' => 'ASC'], $index->getOrder());

        $index->setOrder(['title' => 'ASC', 'name' => 'DESC']);
        $this->assertSame(['title' => 'ASC', 'name' => 'DESC'], $index->getOrder());
    }

    public function testSetInclude(): void
    {
        $index = new Index('title_idx', ['title']);
        $this->assertNull($index->getInclude());

        $index->setInclude(['title']);
        $this->assertSame(['title'], $index->getInclude());

        $index->setInclude(['title', 'name']);
        $this->assertSame(['title', 'name'], $index->getInclude());

        $index->setInclude(['title', 'name']);
        $this->assertSame(['title', 'name'], $index->getInclude());
    }

    public function testSetConcurrent(): void
    {
        $index = new Index('title_idx', ['title']);
        $this->assertFalse($index->getConcurrent());

        $index->setConcurrent(true);
        $this->assertTrue($index->getConcurrent());

        $index->setConcurrent(false);
        $this->assertFalse($index->getConcurrent());
    }

    public function testSetWhere(): void
    {
        $index = new Index('title_idx', ['title']);
        $this->assertNull($index->getWhere());

        $index->setWhere('status = 1');
        $this->assertSame('status = 1', $index->getWhere());

        $index->setWhere('status = 1 AND type = "active"');
        $this->assertSame('status = 1 AND type = "active"', $index->getWhere());
    }

    public function testSetAttributes(): void
    {
        $index = new Index('title_idx', ['title']);
        $attrs = [
            'name' => 'index-name',
            'columns' => ['title', 'name'],
        ];
        $index->setAttributes($attrs);
        foreach ($attrs as $key => $value) {
            $method = 'get' . ucfirst($key);
            $this->assertSame($value, $index->{$method}());
        }
    }
}
