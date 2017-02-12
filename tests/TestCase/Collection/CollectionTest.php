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
namespace Cake\Test\TestCase\Collection;

use ArrayIterator;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Collection\CollectionTrait;
use Cake\TestSuite\TestCase;
use NoRewindIterator;

class TestCollection extends \IteratorIterator implements CollectionInterface
{
    use CollectionTrait;


    public function __construct($items)
    {
        if (is_array($items)) {
            $items = new \ArrayIterator($items);
        }

        if (!($items instanceof \Traversable)) {
            $msg = 'Only an array or \Traversable is allowed for Collection';
            throw new \InvalidArgumentException($msg);
        }

        parent::__construct($items);
    }
}

/**
 * CollectionTest
 */
class CollectionTest extends TestCase
{

    /**
     * Tests that it is possible to convert an array into a collection
     *
     * @return void
     */
    public function testArrayIsWrapped()
    {
        $items = [1, 2, 3];
        $collection = new Collection($items);
        $this->assertEquals($items, iterator_to_array($collection));
    }

    /**
     * Tests that it is possible to convert an iterator into a collection
     *
     * @return void
     */
    public function testIteratorIsWrapped()
    {
        $items = new \ArrayObject([1, 2, 3]);
        $collection = new Collection($items);
        $this->assertEquals(iterator_to_array($items), iterator_to_array($collection));
    }

    /**
     * Test running a method over all elements in the collection
     *
     * @return void
     */
    public function testEach()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $callable = $this->getMockBuilder(\StdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a');
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b');
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(3, 'c');
        $collection->each($callable);
    }

    /**
     * Test filter() with no callback.
     *
     * @return void
     */
    public function testFilterNoCallback()
    {
        $items = [1, 2, 0, 3, false, 4, null, 5, ''];
        $collection = new Collection($items);
        $result = $collection->filter()->toArray();
        $expected = [1, 2, 3, 4, 5];
        $this->assertEquals($expected, array_values($result));
    }

    /**
     * Tests that it is possible to chain filter() as it returns a collection object
     *
     * @return void
     */
    public function testFilterChaining()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $callable = $this->getMockBuilder(\StdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->once())
            ->method('__invoke')
            ->with(3, 'c');
        $filtered = $collection->filter(function ($value, $key, $iterator) {
            return $value > 2;
        });

        $this->assertInstanceOf('Cake\Collection\Collection', $filtered);
        $filtered->each($callable);
    }

    /**
     * Tests reject
     *
     * @return void
     */
    public function testReject()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $result = $collection->reject(function ($v, $k, $items) use ($collection) {
            $this->assertSame($collection->getInnerIterator(), $items);

            return $v > 2;
        });
        $this->assertEquals(['a' => 1, 'b' => 2], iterator_to_array($result));
        $this->assertInstanceOf('Cake\Collection\Collection', $result);
    }

    /**
     * Tests every when the callback returns true for all elements
     *
     * @return void
     */
    public function testEveryReturnTrue()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $callable = $this->getMockBuilder(\StdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a')
            ->will($this->returnValue(true));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b')
            ->will($this->returnValue(true));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(3, 'c')
            ->will($this->returnValue(true));
        $this->assertTrue($collection->every($callable));
    }

    /**
     * Tests every when the callback returns false for one of the elements
     *
     * @return void
     */
    public function testEveryReturnFalse()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $callable = $this->getMockBuilder(\StdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a')
            ->will($this->returnValue(true));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b')
            ->will($this->returnValue(false));
        $callable->expects($this->exactly(2))->method('__invoke');
        $this->assertFalse($collection->every($callable));

        $items = [];
        $collection = new Collection($items);
        $callable = $this->getMockBuilder(\StdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->never())
            ->method('__invoke');
        $this->assertFalse($collection->every($callable));
    }

    /**
     * Tests some() when one of the calls return true
     *
     * @return void
     */
    public function testSomeReturnTrue()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $callable = $this->getMockBuilder(\StdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a')
            ->will($this->returnValue(false));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b')
            ->will($this->returnValue(true));
        $callable->expects($this->exactly(2))->method('__invoke');
        $this->assertTrue($collection->some($callable));
    }

    /**
     * Tests some() when none of the calls return true
     *
     * @return void
     */
    public function testSomeReturnFalse()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $callable = $this->getMockBuilder(\StdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a')
            ->will($this->returnValue(false));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b')
            ->will($this->returnValue(false));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(3, 'c')
            ->will($this->returnValue(false));
        $this->assertFalse($collection->some($callable));
    }

    /**
     * Tests contains
     *
     * @return void
     */
    public function testContains()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $this->assertTrue($collection->contains(2));
        $this->assertTrue($collection->contains(1));
        $this->assertFalse($collection->contains(10));
        $this->assertFalse($collection->contains('2'));
    }

    /**
     * Tests map
     *
     * @return void
     */
    public function testMap()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $map = $collection->map(function ($v, $k, $it) use ($collection) {
            $this->assertSame($collection->getInnerIterator(), $it);

            return $v * $v;
        });
        $this->assertInstanceOf('Cake\Collection\Iterator\ReplaceIterator', $map);
        $this->assertEquals(['a' => 1, 'b' => 4, 'c' => 9], iterator_to_array($map));
    }

    /**
     * Tests reduce with initial value
     *
     * @return void
     */
    public function testReduceWithInitialValue()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $callable = $this->getMockBuilder(\StdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(10, 1, 'a')
            ->will($this->returnValue(11));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(11, 2, 'b')
            ->will($this->returnValue(13));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(13, 3, 'c')
            ->will($this->returnValue(16));
        $this->assertEquals(16, $collection->reduce($callable, 10));
    }

    /**
     * Tests reduce without initial value
     *
     * @return void
     */
    public function testReduceWithoutInitialValue()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $collection = new Collection($items);
        $callable = $this->getMockBuilder(\StdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 2, 'b')
            ->will($this->returnValue(3));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(3, 3, 'c')
            ->will($this->returnValue(6));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(6, 4, 'd')
            ->will($this->returnValue(10));
        $this->assertEquals(10, $collection->reduce($callable));
    }

    /**
     * Tests extract
     *
     * @return void
     */
    public function testExtract()
    {
        $items = [['a' => ['b' => ['c' => 1]]], 2];
        $collection = new Collection($items);
        $map = $collection->extract('a.b.c');
        $this->assertInstanceOf('Cake\Collection\Iterator\ExtractIterator', $map);
        $this->assertEquals([1, null], iterator_to_array($map));
    }

    /**
     * Tests sort
     *
     * @return void
     */
    public function testSortString()
    {
        $items = [
            ['a' => ['b' => ['c' => 4]]],
            ['a' => ['b' => ['c' => 10]]],
            ['a' => ['b' => ['c' => 6]]]
        ];
        $collection = new Collection($items);
        $map = $collection->sortBy('a.b.c');
        $this->assertInstanceOf('Cake\Collection\Collection', $map);
        $expected = [
            ['a' => ['b' => ['c' => 10]]],
            ['a' => ['b' => ['c' => 6]]],
            ['a' => ['b' => ['c' => 4]]],
        ];
        $this->assertEquals($expected, $map->toList());
    }

    /**
     * Tests max
     *
     * @return void
     */
    public function testMax()
    {
        $items = [
            ['a' => ['b' => ['c' => 4]]],
            ['a' => ['b' => ['c' => 10]]],
            ['a' => ['b' => ['c' => 6]]]
        ];
        $collection = new Collection($items);
        $this->assertEquals(['a' => ['b' => ['c' => 10]]], $collection->max('a.b.c'));

        $callback = function ($e) {
            return $e['a']['b']['c'] * - 1;
        };
        $this->assertEquals(['a' => ['b' => ['c' => 4]]], $collection->max($callback));
    }

    /**
     * Tests min
     *
     * @return void
     */
    public function testMin()
    {
        $items = [
            ['a' => ['b' => ['c' => 4]]],
            ['a' => ['b' => ['c' => 10]]],
            ['a' => ['b' => ['c' => 6]]]
        ];
        $collection = new Collection($items);
        $this->assertEquals(['a' => ['b' => ['c' => 4]]], $collection->min('a.b.c'));
    }

    /**
     * Tests groupBy
     *
     * @return void
     */
    public function testGroupBy()
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
            ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
            ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
        ];
        $collection = new Collection($items);
        $grouped = $collection->groupBy('parent_id');
        $expected = [
            10 => [
                ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
                ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
            ],
            11 => [
                ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
            ]
        ];
        $this->assertEquals($expected, iterator_to_array($grouped));
        $this->assertInstanceOf('Cake\Collection\Collection', $grouped);

        $grouped = $collection->groupBy(function ($element) {
            return $element['parent_id'];
        });
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests grouping by a deep key
     *
     * @return void
     */
    public function testGroupByDeepKey()
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'thing' => ['parent_id' => 10]],
            ['id' => 2, 'name' => 'bar', 'thing' => ['parent_id' => 11]],
            ['id' => 3, 'name' => 'baz', 'thing' => ['parent_id' => 10]],
        ];
        $collection = new Collection($items);
        $grouped = $collection->groupBy('thing.parent_id');
        $expected = [
            10 => [
                ['id' => 1, 'name' => 'foo', 'thing' => ['parent_id' => 10]],
                ['id' => 3, 'name' => 'baz', 'thing' => ['parent_id' => 10]],
            ],
            11 => [
                ['id' => 2, 'name' => 'bar', 'thing' => ['parent_id' => 11]],
            ]
        ];
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests indexBy
     *
     * @return void
     */
    public function testIndexBy()
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
            ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
            ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
        ];
        $collection = new Collection($items);
        $grouped = $collection->indexBy('id');
        $expected = [
            1 => ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
            3 => ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
            2 => ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
        ];
        $this->assertEquals($expected, iterator_to_array($grouped));
        $this->assertInstanceOf('Cake\Collection\Collection', $grouped);

        $grouped = $collection->indexBy(function ($element) {
            return $element['id'];
        });
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests indexBy with a deep property
     *
     * @return void
     */
    public function testIndexByDeep()
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'thing' => ['parent_id' => 10]],
            ['id' => 2, 'name' => 'bar', 'thing' => ['parent_id' => 11]],
            ['id' => 3, 'name' => 'baz', 'thing' => ['parent_id' => 10]],
        ];
        $collection = new Collection($items);
        $grouped = $collection->indexBy('thing.parent_id');
        $expected = [
            10 => ['id' => 3, 'name' => 'baz', 'thing' => ['parent_id' => 10]],
            11 => ['id' => 2, 'name' => 'bar', 'thing' => ['parent_id' => 11]],
        ];
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests countBy
     *
     * @return void
     */
    public function testCountBy()
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
            ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
            ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
        ];
        $collection = new Collection($items);
        $grouped = $collection->countBy('parent_id');
        $expected = [
            10 => 2,
            11 => 1
        ];
        $this->assertEquals($expected, iterator_to_array($grouped));
        $this->assertInstanceOf('Cake\Collection\Collection', $grouped);

        $grouped = $collection->countBy(function ($element) {
            return $element['parent_id'];
        });
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests shuffle
     *
     * @return void
     */
    public function testShuffle()
    {
        $data = [1, 2, 3, 4];
        $collection = (new Collection($data))->shuffle();
        $this->assertCount(count($data), iterator_to_array($collection));

        foreach ($collection as $value) {
            $this->assertContains($value, $data);
        }
    }

    /**
     * Tests sample
     *
     * @return void
     */
    public function testSample()
    {
        $data = [1, 2, 3, 4];
        $collection = (new Collection($data))->sample(2);
        $this->assertCount(2, iterator_to_array($collection));

        foreach ($collection as $value) {
            $this->assertContains($value, $data);
        }
    }

    /**
     * Test toArray method
     *
     * @return void
     */
    public function testToArray()
    {
        $data = [1, 2, 3, 4];
        $collection = new Collection($data);
        $this->assertEquals($data, $collection->toArray());
    }

    /**
     * Test toList method
     *
     * @return void
     */
    public function testToList()
    {
        $data = [100 => 1, 300 => 2, 500 => 3, 1 => 4];
        $collection = new Collection($data);
        $this->assertEquals(array_values($data), $collection->toList());
    }

    /**
     * Test json encoding
     *
     * @return void
     */
    public function testToJson()
    {
        $data = [1, 2, 3, 4];
        $collection = new Collection($data);
        $this->assertEquals(json_encode($data), json_encode($collection));
    }

    /**
     * Tests that only arrays and Traversables are allowed in the constructor
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Only an array or \Traversable is allowed for Collection
     * @return void
     */
    public function testInvalidConstructorArgument()
    {
        new Collection('Derp');
    }

    /**
     * Tests that issuing a count will throw an exception
     *
     * @expectedException \LogicException
     * @return void
     */
    public function testCollectionCount()
    {
        $data = [1, 2, 3, 4];
        $collection = new Collection($data);
        $collection->count();
    }

    /**
     * Tests take method
     *
     * @return void
     */
    public function testTake()
    {
        $data = [1, 2, 3, 4];
        $collection = new Collection($data);

        $taken = $collection->take(2);
        $this->assertEquals([1, 2], $taken->toArray());

        $taken = $collection->take(3);
        $this->assertEquals([1, 2, 3], $taken->toArray());

        $taken = $collection->take(500);
        $this->assertEquals([1, 2, 3, 4], $taken->toArray());

        $taken = $collection->take(1);
        $this->assertEquals([1], $taken->toArray());

        $taken = $collection->take();
        $this->assertEquals([1], $taken->toArray());

        $taken = $collection->take(2, 2);
        $this->assertEquals([2 => 3, 3 => 4], $taken->toArray());
    }

    /**
     * Tests match
     *
     * @return void
     */
    public function testMatch()
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'thing' => ['parent_id' => 10]],
            ['id' => 2, 'name' => 'bar', 'thing' => ['parent_id' => 11]],
            ['id' => 3, 'name' => 'baz', 'thing' => ['parent_id' => 10]],
        ];
        $collection = new Collection($items);
        $matched = $collection->match(['thing.parent_id' => 10, 'name' => 'baz']);
        $this->assertEquals([2 => $items[2]], $matched->toArray());

        $matched = $collection->match(['thing.parent_id' => 10]);
        $this->assertEquals(
            [0 => $items[0], 2 => $items[2]],
            $matched->toArray()
        );

        $matched = $collection->match(['thing.parent_id' => 500]);
        $this->assertEquals([], $matched->toArray());

        $matched = $collection->match(['parent_id' => 10, 'name' => 'baz']);
        $this->assertEquals([], $matched->toArray());
    }

    /**
     * Tests firstMatch
     *
     * @return void
     */
    public function testFirstMatch()
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'thing' => ['parent_id' => 10]],
            ['id' => 2, 'name' => 'bar', 'thing' => ['parent_id' => 11]],
            ['id' => 3, 'name' => 'baz', 'thing' => ['parent_id' => 10]],
        ];
        $collection = new Collection($items);
        $matched = $collection->firstMatch(['thing.parent_id' => 10]);
        $this->assertEquals(
            ['id' => 1, 'name' => 'foo', 'thing' => ['parent_id' => 10]],
            $matched
        );

        $matched = $collection->firstMatch(['thing.parent_id' => 10, 'name' => 'baz']);
        $this->assertEquals(
            ['id' => 3, 'name' => 'baz', 'thing' => ['parent_id' => 10]],
            $matched
        );
    }

    /**
     * Tests the append method
     *
     * @return void
     */
    public function testAppend()
    {
        $collection = new Collection([1, 2, 3]);
        $combined = $collection->append([4, 5, 6]);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $combined->toArray(false));

        $collection = new Collection(['a' => 1, 'b' => 2]);
        $combined = $collection->append(['c' => 3, 'a' => 4]);
        $this->assertEquals(['a' => 4, 'b' => 2, 'c' => 3], $combined->toArray());
    }

    /**
     * Tests the append method with iterator
     */
    public function testAppendIterator()
    {
        $collection = new Collection([1, 2, 3]);
        $iterator = new ArrayIterator([4, 5, 6]);
        $combined = $collection->append($iterator);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $combined->toList());
    }

    public function testAppendNotCollectionInstance()
    {
        $collection = new TestCollection([1, 2, 3]);
        $combined = $collection->append([4, 5, 6]);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $combined->toList());
    }

    /**
     * Tests that by calling compile internal iteration operations are not done
     * more than once
     *
     * @return void
     */
    public function testCompile()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $callable = $this->getMockBuilder(\StdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 'a')
            ->will($this->returnValue(4));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 'b')
            ->will($this->returnValue(5));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(3, 'c')
            ->will($this->returnValue(6));
        $compiled = $collection->map($callable)->compile();
        $this->assertEquals(['a' => 4, 'b' => 5, 'c' => 6], $compiled->toArray());
        $this->assertEquals(['a' => 4, 'b' => 5, 'c' => 6], $compiled->toArray());
    }

    /**
     * Tests converting a non rewindable iterator into a rewindable one using
     * the buffered method.
     *
     * @return void
     */
    public function testBuffered()
    {
        $items = new NoRewindIterator(new ArrayIterator(['a' => 4, 'b' => 5, 'c' => 6]));
        $buffered = (new Collection($items))->buffered();
        $this->assertEquals(['a' => 4, 'b' => 5, 'c' => 6], $buffered->toArray());
        $this->assertEquals(['a' => 4, 'b' => 5, 'c' => 6], $buffered->toArray());
    }

    /**
     * Tests the combine method
     *
     * @return void
     */
    public function testCombine()
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'parent' => 'a'],
            ['id' => 2, 'name' => 'bar', 'parent' => 'b'],
            ['id' => 3, 'name' => 'baz', 'parent' => 'a']
        ];
        $collection = (new Collection($items))->combine('id', 'name');
        $expected = [1 => 'foo', 2 => 'bar', 3 => 'baz'];
        $this->assertEquals($expected, $collection->toArray());

        $expected = ['foo' => 1, 'bar' => 2, 'baz' => 3];
        $collection = (new Collection($items))->combine('name', 'id');
        $this->assertEquals($expected, $collection->toArray());

        $collection = (new Collection($items))->combine('id', 'name', 'parent');
        $expected = ['a' => [1 => 'foo', 3 => 'baz'], 'b' => [2 => 'bar']];
        $this->assertEquals($expected, $collection->toArray());

        $expected = [
            '0-1' => ['foo-0-1' => '0-1-foo'],
            '1-2' => ['bar-1-2' => '1-2-bar'],
            '2-3' => ['baz-2-3' => '2-3-baz']
        ];
        $collection = (new Collection($items))->combine(
            function ($value, $key) {
                return $value['name'] . '-' . $key;
            },
            function ($value, $key) {
                return $key . '-' . $value['name'];
            },
            function ($value, $key) {
                return $key . '-' . $value['id'];
            }
        );
        $this->assertEquals($expected, $collection->toArray());

        $collection = (new Collection($items))->combine('id', 'crazy');
        $this->assertEquals([1 => null, 2 => null, 3 => null], $collection->toArray());
    }

    /**
     * Tests the nest method with only one level
     *
     * @return void
     */
    public function testNest()
    {
        $items = [
            ['id' => 1, 'parent_id' => null],
            ['id' => 2, 'parent_id' => 1],
            ['id' => 3, 'parent_id' => 1],
            ['id' => 4, 'parent_id' => 1],
            ['id' => 5, 'parent_id' => 6],
            ['id' => 6, 'parent_id' => null],
            ['id' => 7, 'parent_id' => 1],
            ['id' => 8, 'parent_id' => 6],
            ['id' => 9, 'parent_id' => 6],
            ['id' => 10, 'parent_id' => 6]
        ];
        $collection = (new Collection($items))->nest('id', 'parent_id');
        $expected = [
            [
                'id' => 1,
                'parent_id' => null,
                'children' => [
                    ['id' => 2, 'parent_id' => 1, 'children' => []],
                    ['id' => 3, 'parent_id' => 1, 'children' => []],
                    ['id' => 4, 'parent_id' => 1, 'children' => []],
                    ['id' => 7, 'parent_id' => 1, 'children' => []]
                ]
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'children' => [
                    ['id' => 5, 'parent_id' => 6, 'children' => []],
                    ['id' => 8, 'parent_id' => 6, 'children' => []],
                    ['id' => 9, 'parent_id' => 6, 'children' => []],
                    ['id' => 10, 'parent_id' => 6, 'children' => []]
                ]
            ]
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with alternate nesting key
     *
     * @return void
     */
    public function testNestAlternateNestingKey()
    {
        $items = [
            ['id' => 1, 'parent_id' => null],
            ['id' => 2, 'parent_id' => 1],
            ['id' => 3, 'parent_id' => 1],
            ['id' => 4, 'parent_id' => 1],
            ['id' => 5, 'parent_id' => 6],
            ['id' => 6, 'parent_id' => null],
            ['id' => 7, 'parent_id' => 1],
            ['id' => 8, 'parent_id' => 6],
            ['id' => 9, 'parent_id' => 6],
            ['id' => 10, 'parent_id' => 6]
        ];
        $collection = (new Collection($items))->nest('id', 'parent_id', 'nodes');
        $expected = [
            [
                'id' => 1,
                'parent_id' => null,
                'nodes' => [
                    ['id' => 2, 'parent_id' => 1, 'nodes' => []],
                    ['id' => 3, 'parent_id' => 1, 'nodes' => []],
                    ['id' => 4, 'parent_id' => 1, 'nodes' => []],
                    ['id' => 7, 'parent_id' => 1, 'nodes' => []]
                ]
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'nodes' => [
                    ['id' => 5, 'parent_id' => 6, 'nodes' => []],
                    ['id' => 8, 'parent_id' => 6, 'nodes' => []],
                    ['id' => 9, 'parent_id' => 6, 'nodes' => []],
                    ['id' => 10, 'parent_id' => 6, 'nodes' => []]
                ]
            ]
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     *
     * @return void
     */
    public function testNestMultiLevel()
    {
        $items = [
            ['id' => 1, 'parent_id' => null],
            ['id' => 2, 'parent_id' => 1],
            ['id' => 3, 'parent_id' => 2],
            ['id' => 4, 'parent_id' => 2],
            ['id' => 5, 'parent_id' => 3],
            ['id' => 6, 'parent_id' => null],
            ['id' => 7, 'parent_id' => 3],
            ['id' => 8, 'parent_id' => 4],
            ['id' => 9, 'parent_id' => 6],
            ['id' => 10, 'parent_id' => 6]
        ];
        $collection = (new Collection($items))->nest('id', 'parent_id', 'nodes');
        $expected = [
            [
                'id' => 1,
                'parent_id' => null,
                'nodes' => [
                    [
                        'id' => 2,
                        'parent_id' => 1,
                        'nodes' => [
                            [
                                'id' => 3,
                                'parent_id' => 2,
                                'nodes' => [
                                    ['id' => 5, 'parent_id' => 3, 'nodes' => []],
                                    ['id' => 7, 'parent_id' => 3, 'nodes' => []]
                                ]
                            ],
                            [
                                'id' => 4,
                                'parent_id' => 2,
                                'nodes' => [
                                    ['id' => 8, 'parent_id' => 4, 'nodes' => []]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'nodes' => [
                    ['id' => 9, 'parent_id' => 6, 'nodes' => []],
                    ['id' => 10, 'parent_id' => 6, 'nodes' => []]
                ]
            ]
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     *
     * @return void
     */
    public function testNestMultiLevelAlternateNestingKey()
    {
        $items = [
            ['id' => 1, 'parent_id' => null],
            ['id' => 2, 'parent_id' => 1],
            ['id' => 3, 'parent_id' => 2],
            ['id' => 4, 'parent_id' => 2],
            ['id' => 5, 'parent_id' => 3],
            ['id' => 6, 'parent_id' => null],
            ['id' => 7, 'parent_id' => 3],
            ['id' => 8, 'parent_id' => 4],
            ['id' => 9, 'parent_id' => 6],
            ['id' => 10, 'parent_id' => 6]
        ];
        $collection = (new Collection($items))->nest('id', 'parent_id');
        $expected = [
            [
                'id' => 1,
                'parent_id' => null,
                'children' => [
                    [
                        'id' => 2,
                        'parent_id' => 1,
                        'children' => [
                            [
                                'id' => 3,
                                'parent_id' => 2,
                                'children' => [
                                    ['id' => 5, 'parent_id' => 3, 'children' => []],
                                    ['id' => 7, 'parent_id' => 3, 'children' => []]
                                ]
                            ],
                            [
                                'id' => 4,
                                'parent_id' => 2,
                                'children' => [
                                    ['id' => 8, 'parent_id' => 4, 'children' => []]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'children' => [
                    ['id' => 9, 'parent_id' => 6, 'children' => []],
                    ['id' => 10, 'parent_id' => 6, 'children' => []]
                ]
            ]
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     *
     * @return void
     */
    public function testNestObjects()
    {
        $items = [
            new ArrayObject(['id' => 1, 'parent_id' => null]),
            new ArrayObject(['id' => 2, 'parent_id' => 1]),
            new ArrayObject(['id' => 3, 'parent_id' => 2]),
            new ArrayObject(['id' => 4, 'parent_id' => 2]),
            new ArrayObject(['id' => 5, 'parent_id' => 3]),
            new ArrayObject(['id' => 6, 'parent_id' => null]),
            new ArrayObject(['id' => 7, 'parent_id' => 3]),
            new ArrayObject(['id' => 8, 'parent_id' => 4]),
            new ArrayObject(['id' => 9, 'parent_id' => 6]),
            new ArrayObject(['id' => 10, 'parent_id' => 6])
        ];
        $collection = (new Collection($items))->nest('id', 'parent_id');
        $expected = [
            new ArrayObject([
                'id' => 1,
                'parent_id' => null,
                'children' => [
                    new ArrayObject([
                        'id' => 2,
                        'parent_id' => 1,
                        'children' => [
                            new ArrayObject([
                                'id' => 3,
                                'parent_id' => 2,
                                'children' => [
                                    new ArrayObject(['id' => 5, 'parent_id' => 3, 'children' => []]),
                                    new ArrayObject(['id' => 7, 'parent_id' => 3, 'children' => []])
                                ]
                            ]),
                            new ArrayObject([
                                'id' => 4,
                                'parent_id' => 2,
                                'children' => [
                                    new ArrayObject(['id' => 8, 'parent_id' => 4, 'children' => []])
                                ]
                            ])
                        ]
                    ])
                ]
            ]),
            new ArrayObject([
                'id' => 6,
                'parent_id' => null,
                'children' => [
                    new ArrayObject(['id' => 9, 'parent_id' => 6, 'children' => []]),
                    new ArrayObject(['id' => 10, 'parent_id' => 6, 'children' => []])
                ]
            ])
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     *
     * @return void
     */
    public function testNestObjectsAlternateNestingKey()
    {
        $items = [
            new ArrayObject(['id' => 1, 'parent_id' => null]),
            new ArrayObject(['id' => 2, 'parent_id' => 1]),
            new ArrayObject(['id' => 3, 'parent_id' => 2]),
            new ArrayObject(['id' => 4, 'parent_id' => 2]),
            new ArrayObject(['id' => 5, 'parent_id' => 3]),
            new ArrayObject(['id' => 6, 'parent_id' => null]),
            new ArrayObject(['id' => 7, 'parent_id' => 3]),
            new ArrayObject(['id' => 8, 'parent_id' => 4]),
            new ArrayObject(['id' => 9, 'parent_id' => 6]),
            new ArrayObject(['id' => 10, 'parent_id' => 6])
        ];
        $collection = (new Collection($items))->nest('id', 'parent_id', 'nodes');
        $expected = [
            new ArrayObject([
                'id' => 1,
                'parent_id' => null,
                'nodes' => [
                    new ArrayObject([
                        'id' => 2,
                        'parent_id' => 1,
                        'nodes' => [
                            new ArrayObject([
                                'id' => 3,
                                'parent_id' => 2,
                                'nodes' => [
                                    new ArrayObject(['id' => 5, 'parent_id' => 3, 'nodes' => []]),
                                    new ArrayObject(['id' => 7, 'parent_id' => 3, 'nodes' => []])
                                ]
                            ]),
                            new ArrayObject([
                                'id' => 4,
                                'parent_id' => 2,
                                'nodes' => [
                                    new ArrayObject(['id' => 8, 'parent_id' => 4, 'nodes' => []])
                                ]
                            ])
                        ]
                    ])
                ]
            ]),
            new ArrayObject([
                'id' => 6,
                'parent_id' => null,
                'nodes' => [
                    new ArrayObject(['id' => 9, 'parent_id' => 6, 'nodes' => []]),
                    new ArrayObject(['id' => 10, 'parent_id' => 6, 'nodes' => []])
                ]
            ])
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests insert
     *
     * @return void
     */
    public function testInsert()
    {
        $items = [['a' => 1], ['b' => 2]];
        $collection = new Collection($items);
        $iterator = $collection->insert('c', [3, 4]);
        $this->assertInstanceOf('Cake\Collection\Iterator\InsertIterator', $iterator);
        $this->assertEquals(
            [['a' => 1, 'c' => 3], ['b' => 2, 'c' => 4]],
            iterator_to_array($iterator)
        );
    }

    /**
     * Provider for testing each of the directions for listNested
     *
     * @return void
     */
    public function nestedListProvider()
    {
        return [
            ['desc', [1, 2, 3, 5, 7, 4, 8, 6, 9, 10]],
            ['asc', [5, 7, 3, 8, 4, 2, 1, 9, 10, 6]],
            ['leaves', [5, 7, 8, 9, 10]]
        ];
    }

    /**
     * Tests the listNested method with the default 'children' nesting key
     *
     * @dataProvider nestedListProvider
     * @return void
     */
    public function testListNested($dir, $expected)
    {
        $items = [
            ['id' => 1, 'parent_id' => null],
            ['id' => 2, 'parent_id' => 1],
            ['id' => 3, 'parent_id' => 2],
            ['id' => 4, 'parent_id' => 2],
            ['id' => 5, 'parent_id' => 3],
            ['id' => 6, 'parent_id' => null],
            ['id' => 7, 'parent_id' => 3],
            ['id' => 8, 'parent_id' => 4],
            ['id' => 9, 'parent_id' => 6],
            ['id' => 10, 'parent_id' => 6]
        ];
        $collection = (new Collection($items))->nest('id', 'parent_id')->listNested($dir);
        $this->assertEquals($expected, $collection->extract('id')->toArray(false));
    }

    /**
     * Tests using listNested with a different nesting key
     *
     * @return void
     */
    public function testListNestedCustomKey()
    {
        $items = [
            ['id' => 1, 'stuff' => [['id' => 2, 'stuff' => [['id' => 3]]]]],
            ['id' => 4, 'stuff' => [['id' => 5]]]
        ];
        $collection = (new Collection($items))->listNested('desc', 'stuff');
        $this->assertEquals(range(1, 5), $collection->extract('id')->toArray(false));
    }

    /**
     * Tests flattening the collection using a custom callable function
     *
     * @return void
     */
    public function testListNestedWithCallable()
    {
        $items = [
            ['id' => 1, 'stuff' => [['id' => 2, 'stuff' => [['id' => 3]]]]],
            ['id' => 4, 'stuff' => [['id' => 5]]]
        ];
        $collection = (new Collection($items))->listNested('desc', function ($item) {
            return isset($item['stuff']) ? $item['stuff'] : [];
        });
        $this->assertEquals(range(1, 5), $collection->extract('id')->toArray(false));
    }

    /**
     * Tests the sumOf method
     *
     * @return void
     */
    public function testSumOf()
    {
        $items = [
            ['invoice' => ['total' => 100]],
            ['invoice' => ['total' => 200]]
        ];
        $this->assertEquals(300, (new Collection($items))->sumOf('invoice.total'));

        $sum = (new Collection($items))->sumOf(function ($v) {
            return $v['invoice']['total'] * 2;
        });
        $this->assertEquals(600, $sum);
    }

    /**
     * Tests the stopWhen method with a callable
     *
     * @return void
     */
    public function testStopWhenCallable()
    {
        $items = [10, 20, 40, 10, 5];
        $collection = (new Collection($items))->stopWhen(function ($v) {
            return $v > 20;
        });
        $this->assertEquals([10, 20], $collection->toArray());
    }

    /**
     * Tests the stopWhen method with a matching array
     *
     * @return void
     */
    public function testStopWhenWithArray()
    {
        $items = [
            ['foo' => 'bar'],
            ['foo' => 'baz'],
            ['foo' => 'foo']
        ];
        $collection = (new Collection($items))->stopWhen(['foo' => 'baz']);
        $this->assertEquals([['foo' => 'bar']], $collection->toArray());
    }

    /**
     * Tests the unfold method
     *
     * @return void
     */
    public function testUnfold()
    {
        $items = [
            [1, 2, 3, 4],
            [5, 6],
            [7, 8]
        ];

        $collection = (new Collection($items))->unfold();
        $this->assertEquals(range(1, 8), $collection->toArray(false));

        $items = [
            [1, 2],
            new Collection([3, 4])
        ];
        $collection = (new Collection($items))->unfold();
        $this->assertEquals(range(1, 4), $collection->toArray(false));
    }

    /**
     * Tests the unfold method with empty levels
     *
     * @return void
     */
    public function testUnfoldEmptyLevels()
    {
        $items = [[], [1, 2], []];
        $collection = (new Collection($items))->unfold();
        $this->assertEquals(range(1, 2), $collection->toArray(false));

        $items = [];
        $collection = (new Collection($items))->unfold();
        $this->assertEmpty($collection->toArray(false));
    }

    /**
     * Tests the unfold when passing a callable
     *
     * @return void
     */
    public function testUnfoldWithCallable()
    {
        $items = [1, 2, 3];
        $collection = (new Collection($items))->unfold(function ($item) {
            return range($item, $item * 2);
        });
        $expected = [1, 2, 2, 3, 4, 3, 4, 5, 6];
        $this->assertEquals($expected, $collection->toArray(false));
    }

    /**
     * Tests the through() method
     *
     * @return void
     */
    public function testThrough()
    {
        $items = [1, 2, 3];
        $collection = (new Collection($items))->through(function ($collection) {
            return $collection->append($collection->toList());
        });

        $this->assertEquals([1, 2, 3, 1, 2, 3], $collection->toList());
    }

    /**
     * Tests the through method when it returns an array
     *
     * @return void
     */
    public function testThroughReturnArray()
    {
        $items = [1, 2, 3];
        $collection = (new Collection($items))->through(function ($collection) {
            $list = $collection->toList();

            return array_merge($list, $list);
        });

        $this->assertEquals([1, 2, 3, 1, 2, 3], $collection->toList());
    }

    /**
     * Tests that the sortBy method does not die when something that is not a
     * collection is passed
     *
     * @return void
     */
    public function testComplexSortBy()
    {
        $results = collection([3, 7])
            ->unfold(function ($value) {
                return [
                    ['sorting' => $value * 2],
                    ['sorting' => $value * 2]
                ];
            })
            ->sortBy('sorting')
            ->extract('sorting')
            ->toList();
        $this->assertEquals([14, 14, 6, 6], $results);
    }

    /**
     * Tests __debugInfo() or debug() usage
     *
     * @return void
     */
    public function testDebug()
    {
        $items = [1, 2, 3];

        $collection = new Collection($items);

        $result = $collection->__debugInfo();
        $expected = [
            'count' => 3,
        ];
        $this->assertSame($expected, $result);

        // Calling it again will rewind
        $result = $collection->__debugInfo();
        $expected = [
            'count' => 3,
        ];
        $this->assertSame($expected, $result);

        // Make sure it also works with non rewindable iterators
        $iterator = new NoRewindIterator(new ArrayIterator($items));
        $collection = new Collection($iterator);

        $result = $collection->__debugInfo();
        $expected = [
            'count' => 3,
        ];
        $this->assertSame($expected, $result);

        // Calling it again will in this case not rewind
        $result = $collection->__debugInfo();
        $expected = [
            'count' => 0,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Tests the isEmpty() method
     *
     * @return void
     */
    public function testIsEmpty()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertFalse($collection->isEmpty());

        $collection = $collection->map(function () {
            return null;
        });
        $this->assertFalse($collection->isEmpty());

        $collection = $collection->filter();
        $this->assertTrue($collection->isEmpty());
    }

    /**
     * Tests the isEmpty() method does not consume data
     * from buffered iterators.
     *
     * @return void
     */
    public function testIsEmptyDoesNotConsume()
    {
        $array = new \ArrayIterator([1, 2, 3]);
        $inner = new \Cake\Collection\Iterator\BufferedIterator($array);
        $collection = new Collection($inner);
        $this->assertFalse($collection->isEmpty());
        $this->assertCount(3, $collection->toArray());
    }

    /**
     * Tests the zip() method
     *
     * @return void
     */
    public function testZip()
    {
        $collection = new Collection([1, 2]);
        $zipped = $collection->zip([3, 4]);
        $this->assertEquals([[1, 3], [2, 4]], $zipped->toList());

        $collection = new Collection([1, 2]);
        $zipped = $collection->zip([3]);
        $this->assertEquals([[1, 3]], $zipped->toList());

        $collection = new Collection([1, 2]);
        $zipped = $collection->zip([3, 4], [5, 6], [7, 8], [9, 10, 11]);
        $this->assertEquals([
            [1, 3, 5, 7, 9],
            [2, 4, 6, 8, 10]
        ], $zipped->toList());
    }

    /**
     * Tests the zipWith() method
     *
     * @return void
     */
    public function testZipWith()
    {
        $collection = new Collection([1, 2]);
        $zipped = $collection->zipWith([3, 4], function ($a, $b) {
            return $a * $b;
        });
        $this->assertEquals([3, 8], $zipped->toList());

        $zipped = $collection->zipWith([3, 4], [5, 6, 7], function () {
            return array_sum(func_get_args());
        });
        $this->assertEquals([9, 12], $zipped->toList());
    }

    /**
     * Tests the skip() method
     *
     * @return void
     */
    public function testSkip()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals([3, 4, 5], $collection->skip(2)->toList());

        $this->assertEquals([5], $collection->skip(4)->toList());
    }

    /**
     * Tests the last() method
     *
     * @return void
     */
    public function testLast()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(3, $collection->last());

        $collection = $collection->map(function ($e) {
            return $e * 2;
        });
        $this->assertEquals(6, $collection->last());
    }

    /**
     * Tests the last() method when on an empty collection
     *
     * @return void
     */
    public function testLAstWithEmptyCollection()
    {
        $collection = new Collection([]);
        $this->assertNull($collection->last());
    }

    /**
     * Tests sumOf with no parameters
     *
     * @return void
     */
    public function testSumOfWithIdentity()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(6, $collection->sumOf());

        $collection = new Collection(['a' => 1, 'b' => 4, 'c' => 6]);
        $this->assertEquals(11, $collection->sumOf());
    }

    /**
     * Tests using extract with the {*} notation
     *
     * @return void
     */
    public function testUnfoldedExtract()
    {
        $items = [
            ['comments' => [['id' => 1], ['id' => 2]]],
            ['comments' => [['id' => 3], ['id' => 4]]],
            ['comments' => [['id' => 7], ['nope' => 8]]],
        ];

        $extracted = (new Collection($items))->extract('comments.{*}.id');
        $this->assertEquals([1, 2, 3, 4, 7, null], $extracted->toArray());

        $items = [
            [
                'comments' => [
                    [
                        'voters' => [['id' => 1], ['id' => 2]]
                    ]
                ]
            ],
            [
                'comments' => [
                    [
                        'voters' => [['id' => 3], ['id' => 4]]
                    ]
                ]
            ],
            [
                'comments' => [
                    [
                        'voters' => [['id' => 5], ['nope' => 'fail'], ['id' => 6]]
                    ]
                ]
            ],
            [
                'comments' => [
                    [
                        'not_voters' => [['id' => 5]]
                    ]
                ]
            ],
            ['not_comments' => []]
        ];
        $extracted = (new Collection($items))->extract('comments.{*}.voters.{*}.id');
        $expected = [1, 2, 3, 4, 5, null, 6];
        $this->assertEquals($expected, $extracted->toArray());
        $this->assertEquals($expected, $extracted->toList());
    }

    /**
     * Tests serializing a simple collection
     *
     * @return void
     */
    public function testSerializeSimpleCollection()
    {
        $collection = new Collection([1, 2, 3]);
        $serialized = serialize($collection);
        $unserialized = unserialize($serialized);
        $this->assertEquals($collection->toList(), $unserialized->toList());
        $this->assertEquals($collection->toArray(), $unserialized->toArray());
    }

    /**
     * Tests serialization when using append
     *
     * @return void
     */
    public function testSerializeWithAppendIterators()
    {
        $collection = new Collection([1, 2, 3]);
        $collection = $collection->append(['a' => 4, 'b' => 5, 'c' => 6]);
        $serialized = serialize($collection);
        $unserialized = unserialize($serialized);
        $this->assertEquals($collection->toList(), $unserialized->toList());
        $this->assertEquals($collection->toArray(), $unserialized->toArray());
    }

    /**
     * Tests serialization when using nested iterators
     *
     * @return void
     */
    public function testSerializeWithNestedIterators()
    {
        $collection = new Collection([1, 2, 3]);
        $collection = $collection->map(function ($e) {
            return $e * 3;
        });

        $collection = $collection->groupBy(function ($e) {
            return $e % 2;
        });

        $serialized = serialize($collection);
        $unserialized = unserialize($serialized);
        $this->assertEquals($collection->toList(), $unserialized->toList());
        $this->assertEquals($collection->toArray(), $unserialized->toArray());
    }

    /**
     * Tests serializing a zip() call
     *
     * @return void
     */
    public function testSerializeWithZipIterator()
    {
        $collection = new Collection([4, 5]);
        $collection = $collection->zip([1, 2]);
        $serialized = serialize($collection);
        $unserialized = unserialize($serialized);
        $this->assertEquals($collection->toList(), $unserialized->toList());
    }

    /**
     * Tests the chunk method with exact chunks
     *
     * @return void
     */
    public function testChunk()
    {
        $collection = new Collection(range(1, 10));
        $chunked = $collection->chunk(2)->toList();
        $expected = [[1, 2], [3, 4], [5, 6], [7, 8], [9, 10]];
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunk method with overflowing chunk size
     *
     * @return void
     */
    public function testChunkOverflow()
    {
        $collection = new Collection(range(1, 11));
        $chunked = $collection->chunk(2)->toList();
        $expected = [[1, 2], [3, 4], [5, 6], [7, 8], [9, 10], [11]];
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunk method with non-scalar items
     *
     * @return void
     */
    public function testChunkNested()
    {
        $collection = new Collection([1, 2, 3, [4, 5], 6, [7, [8, 9], 10], 11]);
        $chunked = $collection->chunk(2)->toList();
        $expected = [[1, 2], [3, [4, 5]], [6, [7, [8, 9], 10]], [11]];
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests cartesianProduct
     *
     * @return void
     */
    public function testCartesianProduct()
    {
        $collection = new Collection([]);

        $result = $collection->cartesianProduct();

        $expected = [];

        $this->assertEquals($expected, $result->toList());

        $collection = new Collection([['A', 'B', 'C'], [1, 2, 3]]);

        $result = $collection->cartesianProduct();

        $expected = [
            ['A', 1],
            ['A', 2],
            ['A', 3],
            ['B', 1],
            ['B', 2],
            ['B', 3],
            ['C', 1],
            ['C', 2],
            ['C', 3],
        ];

        $this->assertEquals($expected, $result->toList());

        $collection = new Collection([[1, 2, 3], ['A', 'B', 'C'], ['a', 'b', 'c']]);

        $result = $collection->cartesianProduct(function ($value) {
            return [strval($value[0]) . $value[1] . $value[2]];
        }, function ($value) {
            return $value[0] >= 2;
        });

        $expected = [
            ['2Aa'],
            ['2Ab'],
            ['2Ac'],
            ['2Ba'],
            ['2Bb'],
            ['2Bc'],
            ['2Ca'],
            ['2Cb'],
            ['2Cc'],
            ['3Aa'],
            ['3Ab'],
            ['3Ac'],
            ['3Ba'],
            ['3Bb'],
            ['3Bc'],
            ['3Ca'],
            ['3Cb'],
            ['3Cc'],
        ];

        $this->assertEquals($expected, $result->toList());

        $collection = new Collection([['1', '2', '3', '4'], ['A', 'B', 'C'], ['name', 'surname', 'telephone']]);

        $result = $collection->cartesianProduct(function ($value) {
            return [$value[0] => [$value[1] => $value[2]]];
        }, function ($value) {
            return $value[2] !== 'surname';
        });

        $expected = [
            [1 => ['A' => 'name']],
            [1 => ['A' => 'telephone']],
            [1 => ['B' => 'name']],
            [1 => ['B' => 'telephone']],
            [1 => ['C' => 'name']],
            [1 => ['C' => 'telephone']],
            [2 => ['A' => 'name']],
            [2 => ['A' => 'telephone']],
            [2 => ['B' => 'name']],
            [2 => ['B' => 'telephone']],
            [2 => ['C' => 'name']],
            [2 => ['C' => 'telephone']],
            [3 => ['A' => 'name']],
            [3 => ['A' => 'telephone']],
            [3 => ['B' => 'name']],
            [3 => ['B' => 'telephone']],
            [3 => ['C' => 'name']],
            [3 => ['C' => 'telephone']],
            [4 => ['A' => 'name']],
            [4 => ['A' => 'telephone']],
            [4 => ['B' => 'name']],
            [4 => ['B' => 'telephone']],
            [4 => ['C' => 'name']],
            [4 => ['C' => 'telephone']],
        ];

        $this->assertEquals($expected, $result->toList());

        $collection = new Collection([
            [
                'name1' => 'alex',
                'name2' => 'kostas',
                0 => 'leon',
            ],
            [
                'val1' => 'alex@example.com',
                24 => 'kostas@example.com',
                'val2' => 'leon@example.com',
            ],
        ]);

        $result = $collection->cartesianProduct();

        $expected = [
            ['alex', 'alex@example.com'],
            ['alex', 'kostas@example.com'],
            ['alex', 'leon@example.com'],
            ['kostas', 'alex@example.com'],
            ['kostas', 'kostas@example.com'],
            ['kostas', 'leon@example.com'],
            ['leon', 'alex@example.com'],
            ['leon', 'kostas@example.com'],
            ['leon', 'leon@example.com'],
        ];

        $this->assertEquals($expected, $result->toList());
    }

    /**
     * Tests that an exception is thrown if the cartesian product is called with multidimensional arrays
     *
     * @expectedException \LogicException
     * @return void
     */
    public function testCartesianProductMultidimensionalArray()
    {
        $collection = new Collection([
            [
                'names' => [
                    'alex', 'kostas', 'leon'
                ]
            ],
            [
                'locations' => [
                    'crete', 'london', 'paris'
                ]
            ],
        ]);

        $result = $collection->cartesianProduct();
    }

    public function testTranspose()
    {
        $collection = new Collection([
            ['Products', '2012', '2013', '2014'],
            ['Product A', '200', '100', '50'],
            ['Product B', '300', '200', '100'],
            ['Product C', '400', '300', '200'],
            ['Product D', '500', '400', '300'],
        ]);
        $transposed = $collection->transpose();
        $expected = [
            ['Products', 'Product A', 'Product B', 'Product C', 'Product D'],
            ['2012', '200', '300', '400', '500'],
            ['2013', '100', '200', '300', '400'],
            ['2014', '50', '100', '200', '300'],
        ];

        $this->assertEquals($expected, $transposed->toList());
    }

    /**
     * Tests that provided arrays do not have even length
     *
     * @expectedException \LogicException
     * @return void
     */
    public function testTransposeUnEvenLengthShouldThrowException()
    {
        $collection = new Collection([
            ['Products', '2012', '2013', '2014'],
            ['Product A', '200', '100', '50'],
            ['Product B', '300'],
            ['Product C', '400', '300'],
        ]);

        $collection->transpose();
    }
}
