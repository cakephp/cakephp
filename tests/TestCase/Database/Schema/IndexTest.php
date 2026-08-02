<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Database\Schema;

use Cake\Database\Schema\Index;
use Cake\TestSuite\TestCase;
use Closure;

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

    public function testSetWhere(): void
    {
        $index = new Index('title_idx', ['title']);
        $this->assertNull($index->getWhere());

        $index->setWhere('status = 1');
        $this->assertSame('status = 1', $index->getWhere());

        $index->setWhere('status = 1 AND type = "active"');
        $this->assertSame('status = 1 AND type = "active"', $index->getWhere());
    }

    public function testSetAccessMethod(): void
    {
        $index = new Index('title_idx', ['title']);
        $this->assertNull($index->getAccessMethod());

        $index->setAccessMethod(Index::GIN);
        $this->assertSame(Index::GIN, $index->getAccessMethod());

        $index->setAccessMethod(Index::GIST);
        $this->assertSame(Index::GIST, $index->getAccessMethod());

        $index->setAccessMethod(null);
        $this->assertNull($index->getAccessMethod());
    }

    public function testToArray(): void
    {
        $index = new Index('title_idx', ['title']);
        $result = $index->toArray();

        $this->assertSame('title_idx', $result['name']);
        $this->assertSame(['title'], $result['columns']);
        $this->assertArrayNotHasKey('accessMethod', $result);

        $index->setAccessMethod(Index::GIN);
        $result = $index->toArray();
        $this->assertSame(Index::GIN, $result['accessMethod']);
    }

    /**
     * Unserializing index data created by an older CakePHP version (where a
     * nullable property such as `accessMethod` did not yet exist) must not throw
     * "must not be accessed before initialization" when the property is read.
     *
     * @link https://github.com/cakephp/cakephp/issues/19562
     * @return void
     */
    public function testUnserializeLegacyDataWithoutAccessMethod(): void
    {
        $index = new Index('title_idx', ['title']);
        // Simulate a legacy serialized payload: remove the property so it is
        // absent from the serialized data, exactly like a pre-5.4 dump.
        Closure::bind(function (): void {
            unset($this->accessMethod);
        }, $index, Index::class)();

        $restored = unserialize(serialize($index));

        $this->assertInstanceOf(Index::class, $restored);
        $this->assertNull($restored->getAccessMethod());
        $this->assertArrayNotHasKey('accessMethod', $restored->toArray());
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
