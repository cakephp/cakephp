<?php
declare(strict_types=1);

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
namespace Cake\Test\TestCase\Collection;

use ArrayIterator;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Collection\Iterator\BufferedIterator;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use CallbackFilterIterator;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use NoRewindIterator;
use stdClass;
use TestApp\Collection\CountableIterator;
use TestApp\Collection\TestCollection;
use function Cake\Collection\collection;

/**
 * Collection Test
 *
 * @coversDefaultClass \Cake\Collection\Collection
 */
class CollectionTest extends TestCase
{
    /**
     * Tests that it is possible to convert an array into a collection
     */
    public function testArrayIsWrapped(): void
    {
        $items = [1, 2, 3];
        $collection = new Collection($items);
        $this->assertEquals($items, iterator_to_array($collection));
    }

    /**
     * Provider for average tests
     *
     * @return array
     */
    public function avgProvider(): array
    {
        $items = [1, 2, 3];

        return [
            'array' => [$items],
            'iterator' => [$this->yieldItems($items)],
        ];
    }

    /**
     * Tests the avg method
     *
     * @dataProvider avgProvider
     */
    public function testAvg(iterable $items): void
    {
        $collection = new Collection($items);
        $this->assertSame(2, $collection->avg());

        $items = [['foo' => 1], ['foo' => 2], ['foo' => 3]];
        $collection = new Collection($items);
        $this->assertSame(2, $collection->avg('foo'));
    }

    /**
     * Tests the avg method when on an empty collection
     */
    public function testAvgWithEmptyCollection(): void
    {
        $collection = new Collection([]);
        $this->assertNull($collection->avg());

        $collection = new Collection([null, null]);
        $this->assertSame(0, $collection->avg());
    }

    /**
     * Provider for average tests with use of a matcher
     *
     * @return array
     */
    public function avgWithMatcherProvider(): array
    {
        $items = [['foo' => 1], ['foo' => 2], ['foo' => 3]];

        return [
            'array' => [$items],
            'iterator' => [$this->yieldItems($items)],
        ];
    }

    /**
     * ests the avg method
     *
     * @dataProvider avgWithMatcherProvider
     */
    public function testAvgWithMatcher(iterable $items): void
    {
        $collection = new Collection($items);
        $this->assertSame(2, $collection->avg('foo'));
    }

    /**
     * Provider for some median tests
     *
     * @return array
     */
    public function medianProvider(): array
    {
        $items = [5, 2, 4];

        return [
            'array' => [$items],
            'iterator' => [$this->yieldItems($items)],
        ];
    }

    /**
     * Tests the median method
     *
     * @dataProvider medianProvider
     */
    public function testMedian(iterable $items): void
    {
        $collection = new Collection($items);
        $this->assertSame(4, $collection->median());
    }

    /**
     * Tests the median method when on an empty collection
     */
    public function testMedianWithEmptyCollection(): void
    {
        $collection = new Collection([]);
        $this->assertNull($collection->median());

        $collection = new Collection([null, null]);
        $this->assertSame(0, $collection->median());
    }

    /**
     * Tests the median method
     *
     * @dataProvider simpleProvider
     */
    public function testMedianEven(iterable $items): void
    {
        $collection = new Collection($items);
        $this->assertSame(2.5, $collection->median());
    }

    /**
     * Provider for median tests with use of a matcher
     *
     * @return array
     */
    public function medianWithMatcherProvider(): array
    {
        $items = [
            ['invoice' => ['total' => 400]],
            ['invoice' => ['total' => 500]],
            ['invoice' => ['total' => 200]],
            ['invoice' => ['total' => 100]],
            ['invoice' => ['total' => 333]],
        ];

        return [
            'array' => [$items],
            'iterator' => [$this->yieldItems($items)],
        ];
    }

    /**
     * Tests the median method
     *
     * @dataProvider medianWithMatcherProvider
     */
    public function testMedianWithMatcher(iterable $items): void
    {
        $this->assertSame(333, (new Collection($items))->median('invoice.total'));
    }

    /**
     * Tests that it is possible to convert an iterator into a collection
     */
    public function testIteratorIsWrapped(): void
    {
        $items = new ArrayObject([1, 2, 3]);
        $collection = new Collection($items);
        $this->assertEquals(iterator_to_array($items), iterator_to_array($collection));
    }

    /**
     * Test running a method over all elements in the collection
     */
    public function testEach(): void
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);

        $results = [];
        $collection->each(function ($value, $key) use (&$results): void {
            $results[] = [$key => $value];
        });
        $this->assertSame([['a' => 1], ['b' => 2], ['c' => 3]], $results);
    }

    public function filterProvider(): array
    {
        $items = [1, 2, 0, 3, false, 4, null, 5, ''];

        return [
            'array' => [$items],
            'iterator' => [$this->yieldItems($items)],
        ];
    }

    /**
     * Test filter() with no callback.
     *
     * @dataProvider filterProvider
     */
    public function testFilterNoCallback(iterable $items): void
    {
        $collection = new Collection($items);
        $result = $collection->filter()->toArray();
        $expected = [1, 2, 3, 4, 5];
        $this->assertSame($expected, array_values($result));
    }

    /**
     * Tests that it is possible to chain filter() as it returns a collection object
     */
    public function testFilterChaining(): void
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);

        $filtered = $collection->filter(function ($value, $key, $iterator) {
            return $value > 2;
        });
        $this->assertInstanceOf(Collection::class, $filtered);

        $results = [];
        $filtered->each(function ($value, $key) use (&$results): void {
            $results[] = [$key => $value];
        });
        $this->assertSame([['c' => 3]], $results);
    }

    /**
     * Tests reject
     */
    public function testReject(): void
    {
        $collection = new Collection([]);
        $result = $collection->reject(function ($v) {
            return false;
        });
        $this->assertSame([], iterator_to_array($result));

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
     */
    public function testEveryReturnTrue(): void
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);

        $results = [];
        $this->assertTrue($collection->every(function ($value, $key) use (&$results) {
            $results[] = [$key => $value];

            return true;
        }));
        $this->assertSame([['a' => 1], ['b' => 2], ['c' => 3]], $results);
    }

    /**
     * Tests every when the callback returns false for one of the elements
     */
    public function testEveryReturnFalse(): void
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);

        $results = [];
        $this->assertFalse($collection->every(function ($value, $key) use (&$results) {
            $results[] = [$key => $value];

            return $key !== 'b';
        }));
        $this->assertSame([['a' => 1], ['b' => 2]], $results);
    }

    /**
     * Tests some() when one of the calls return true
     */
    public function testSomeReturnTrue(): void
    {
        $collection = new Collection([]);
        $result = $collection->some(function ($v) {
            return true;
        });
        $this->assertFalse($result);

        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);

        $results = [];
        $this->assertTrue($collection->some(function ($value, $key) use (&$results) {
            $results[] = [$key => $value];

            return $key === 'b';
        }));
        $this->assertSame([['a' => 1], ['b' => 2]], $results);
    }

    /**
     * Tests some() when none of the calls return true
     */
    public function testSomeReturnFalse(): void
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);

        $results = [];
        $this->assertFalse($collection->some(function ($value, $key) use (&$results) {
            $results[] = [$key => $value];

            return false;
        }));
        $this->assertSame([['a' => 1], ['b' => 2], ['c' => 3]], $results);
    }

    /**
     * Tests contains
     */
    public function testContains(): void
    {
        $collection = new Collection([]);
        $this->assertFalse($collection->contains('a'));

        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);
        $this->assertTrue($collection->contains(2));
        $this->assertTrue($collection->contains(1));
        $this->assertFalse($collection->contains(10));
        $this->assertFalse($collection->contains('2'));
    }

    /**
     * Provider for some simple tests
     *
     * @return array
     */
    public function simpleProvider(): array
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];

        return [
            'array' => [$items],
            'iterator' => [$this->yieldItems($items)],
        ];
    }

    /**
     * Tests map
     *
     * @dataProvider simpleProvider
     */
    public function testMap(iterable $items): void
    {
        $collection = new Collection($items);
        $map = $collection->map(function ($v, $k, $it) use ($collection) {
            $this->assertSame($collection->getInnerIterator(), $it);

            return $v * $v;
        });
        $this->assertInstanceOf('Cake\Collection\Iterator\ReplaceIterator', $map);
        $this->assertEquals(['a' => 1, 'b' => 4, 'c' => 9, 'd' => 16], iterator_to_array($map));
    }

    /**
     * Tests reduce with initial value
     *
     * @dataProvider simpleProvider
     */
    public function testReduceWithInitialValue(iterable $items): void
    {
        $collection = new Collection($items);
        $this->assertSame(20, $collection->reduce(function ($reduction, $value, $key) {
            return $value + $reduction;
        }, 10));
    }

    /**
     * Tests reduce without initial value
     *
     * @dataProvider simpleProvider
     */
    public function testReduceWithoutInitialValue(iterable $items): void
    {
        $collection = new Collection($items);
        $this->assertSame(10, $collection->reduce(function ($reduction, $value, $key) {
            return $value + $reduction;
        }));
    }

    /**
     * Provider for some extract tests
     *
     * @return array
     */
    public function extractProvider(): array
    {
        $items = [['a' => ['b' => ['c' => 1]]], 2];

        return [
            'array' => [$items],
            'iterator' => [$this->yieldItems($items)],
        ];
    }

    /**
     * Tests extract
     *
     * @dataProvider extractProvider
     */
    public function testExtract(iterable $items): void
    {
        $collection = new Collection($items);
        $map = $collection->extract('a.b.c');
        $this->assertInstanceOf('Cake\Collection\Iterator\ExtractIterator', $map);
        $this->assertEquals([1, null], iterator_to_array($map));
    }

    /**
     * Provider for some sort tests
     *
     * @return array
     */
    public function sortProvider(): array
    {
        $items = [
            ['a' => ['b' => ['c' => 4]]],
            ['a' => ['b' => ['c' => 10]]],
            ['a' => ['b' => ['c' => 6]]],
        ];

        return [
            'array' => [$items],
            'iterator' => [$this->yieldItems($items)],
        ];
    }

    /**
     * Tests sort
     *
     * @dataProvider sortProvider
     */
    public function testSortString(iterable $items): void
    {
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
     * @dataProvider sortProvider
     */
    public function testMax(iterable $items): void
    {
        $collection = new Collection($items);
        $this->assertEquals(['a' => ['b' => ['c' => 10]]], $collection->max('a.b.c'));
    }

    /**
     * Tests max
     *
     * @dataProvider sortProvider
     */
    public function testMaxCallback(iterable $items): void
    {
        $collection = new Collection($items);
        $callback = function ($e) {
            return $e['a']['b']['c'] * -1;
        };
        $this->assertEquals(['a' => ['b' => ['c' => 4]]], $collection->max($callback));
    }

    /**
     * Tests max
     *
     * @dataProvider sortProvider
     */
    public function testMaxCallable(iterable $items): void
    {
        $collection = new Collection($items);
        $this->assertEquals(['a' => ['b' => ['c' => 4]]], $collection->max(function ($e) {
            return $e['a']['b']['c'] * -1;
        }));
    }

    /**
     * Test max with a collection of Entities
     */
    public function testMaxWithEntities(): void
    {
        $collection = new Collection([
            new Entity(['id' => 1, 'count' => 18]),
            new Entity(['id' => 2, 'count' => 9]),
            new Entity(['id' => 3, 'count' => 42]),
            new Entity(['id' => 4, 'count' => 4]),
            new Entity(['id' => 5, 'count' => 22]),
        ]);

        $expected = new Entity(['id' => 3, 'count' => 42]);

        $this->assertEquals($expected, $collection->max('count'));
    }

    /**
     * Tests min
     *
     * @dataProvider sortProvider
     */
    public function testMin(iterable $items): void
    {
        $collection = new Collection($items);
        $this->assertEquals(['a' => ['b' => ['c' => 4]]], $collection->min('a.b.c'));
    }

    /**
     * Test min with a collection of Entities
     */
    public function testMinWithEntities(): void
    {
        $collection = new Collection([
            new Entity(['id' => 1, 'count' => 18]),
            new Entity(['id' => 2, 'count' => 9]),
            new Entity(['id' => 3, 'count' => 42]),
            new Entity(['id' => 4, 'count' => 4]),
            new Entity(['id' => 5, 'count' => 22]),
        ]);

        $expected = new Entity(['id' => 4, 'count' => 4]);

        $this->assertEquals($expected, $collection->min('count'));
    }

    /**
     * Provider for some groupBy tests
     *
     * @return array
     */
    public function groupByProvider(): array
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
            ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
            ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
        ];

        return [
            'array' => [$items],
            'iterator' => [$this->yieldItems($items)],
        ];
    }

    /**
     * Tests groupBy
     *
     * @dataProvider groupByProvider
     */
    public function testGroupBy(iterable $items): void
    {
        $collection = new Collection($items);
        $grouped = $collection->groupBy('parent_id');
        $expected = [
            10 => [
                ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
                ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
            ],
            11 => [
                ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
            ],
        ];
        $this->assertEquals($expected, iterator_to_array($grouped));
        $this->assertInstanceOf('Cake\Collection\Collection', $grouped);
    }

    /**
     * Tests groupBy
     *
     * @dataProvider groupByProvider
     */
    public function testGroupByCallback(iterable $items): void
    {
        $collection = new Collection($items);
        $expected = [
            10 => [
                ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
                ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
            ],
            11 => [
                ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
            ],
        ];
        $grouped = $collection->groupBy(function ($element) {
            return $element['parent_id'];
        });
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests grouping by a deep key
     */
    public function testGroupByDeepKey(): void
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
            ],
        ];
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests passing an invalid path to groupBy.
     */
    public function testGroupByInvalidPath(): void
    {
        $items = [
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
        ];
        $collection = new Collection($items);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot group by path that does not exist or contains a null value.');
        $collection->groupBy('missing');
    }

    /**
     * Provider for some indexBy tests
     *
     * @return array
     */
    public function indexByProvider(): array
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
            ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
            ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
        ];

        return [
            'array' => [$items],
            'iterator' => [$this->yieldItems($items)],
        ];
    }

    /**
     * Tests indexBy
     *
     * @dataProvider indexByProvider
     */
    public function testIndexBy(iterable $items): void
    {
        $collection = new Collection($items);
        $grouped = $collection->indexBy('id');
        $expected = [
            1 => ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
            3 => ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
            2 => ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
        ];
        $this->assertEquals($expected, iterator_to_array($grouped));
        $this->assertInstanceOf('Cake\Collection\Collection', $grouped);
    }

    /**
     * Tests indexBy
     *
     * @dataProvider indexByProvider
     */
    public function testIndexByCallback(iterable $items): void
    {
        $collection = new Collection($items);
        $grouped = $collection->indexBy(function ($element) {
            return $element['id'];
        });
        $expected = [
            1 => ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
            3 => ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
            2 => ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
        ];
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests indexBy with a deep property
     */
    public function testIndexByDeep(): void
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
     * Tests passing an invalid path to indexBy.
     */
    public function testIndexByInvalidPath(): void
    {
        $items = [
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
        ];
        $collection = new Collection($items);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot index by path that does not exist or contains a null value');
        $collection->indexBy('missing');
    }

    /**
     * Tests passing an invalid path to indexBy.
     */
    public function testIndexByInvalidPathCallback(): void
    {
        $items = [
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
        ];
        $collection = new Collection($items);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot index by path that does not exist or contains a null value');
        $collection->indexBy(function ($e) {
            return null;
        });
    }

    /**
     * Tests countBy
     *
     * @dataProvider groupByProvider
     */
    public function testCountBy(iterable $items): void
    {
        $collection = new Collection($items);
        $grouped = $collection->countBy('parent_id');
        $expected = [
            10 => 2,
            11 => 1,
        ];
        $result = iterator_to_array($grouped);
        $this->assertInstanceOf('Cake\Collection\Collection', $grouped);
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests countBy
     *
     * @dataProvider groupByProvider
     */
    public function testCountByCallback(iterable $items): void
    {
        $expected = [
            10 => 2,
            11 => 1,
        ];
        $collection = new Collection($items);
        $grouped = $collection->countBy(function ($element) {
            return $element['parent_id'];
        });
        $this->assertEquals($expected, iterator_to_array($grouped));
    }

    /**
     * Tests shuffle
     *
     * @dataProvider simpleProvider
     */
    public function testShuffle(iterable $data): void
    {
        $collection = (new Collection($data))->shuffle();
        $result = $collection->toArray();
        $this->assertCount(4, $result);
        $this->assertContains(1, $result);
        $this->assertContains(2, $result);
        $this->assertContains(3, $result);
        $this->assertContains(4, $result);
    }

    /**
     * Tests shuffle with duplicate keys.
     */
    public function testShuffleDuplicateKeys(): void
    {
        $collection = (new Collection(['a' => 1]))->append(['a' => 2])->shuffle();
        $result = $collection->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals([0, 1], array_keys($result));
        $this->assertContainsEquals(1, $result);
        $this->assertContainsEquals(2, $result);
    }

    /**
     * Tests sample
     *
     * @dataProvider simpleProvider
     */
    public function testSample(iterable $data): void
    {
        $result = (new Collection($data))->sample(2)->toArray();
        $this->assertCount(2, $result);
        foreach ($result as $number) {
            $this->assertContains($number, [1, 2, 3, 4]);
        }
    }

    /**
     * Tests the sample() method with a traversable non-iterator
     */
    public function testSampleWithTraversableNonIterator(): void
    {
        $collection = new Collection($this->datePeriod('2017-01-01', '2017-01-07'));
        $result = $collection->sample(3)->toList();
        $list = [
            '2017-01-01',
            '2017-01-02',
            '2017-01-03',
            '2017-01-04',
            '2017-01-05',
            '2017-01-06',
        ];
        $this->assertCount(3, $result);
        foreach ($result as $date) {
            $this->assertContains($date->format('Y-m-d'), $list);
        }
    }

    /**
     * Test toArray method
     */
    public function testToArray(): void
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $collection = new Collection($data);
        $this->assertEquals($data, $collection->toArray());
    }

    /**
     * Test toList method
     *
     * @dataProvider simpleProvider
     */
    public function testToList(iterable $data): void
    {
        $collection = new Collection($data);
        $this->assertEquals([1, 2, 3, 4], $collection->toList());
    }

    /**
     * Test JSON encoding
     */
    public function testToJson(): void
    {
        $data = [1, 2, 3, 4];
        $collection = new Collection($data);
        $this->assertEquals(json_encode($data), json_encode($collection));
    }

    /**
     * Tests that Count returns the number of elements
     *
     * @dataProvider simpleProvider
     */
    public function testCollectionCount(iterable $list): void
    {
        $list = (new Collection($list))->buffered();
        $collection = new Collection($list);
        $this->assertSame(8, $collection->append($list)->count());
    }

    /**
     * Tests that countKeys returns the number of unique keys
     *
     * @dataProvider simpleProvider
     */
    public function testCollectionCountKeys(iterable $list): void
    {
        $list = (new Collection($list))->buffered();
        $collection = new Collection($list);
        $this->assertSame(4, $collection->append($list)->countKeys());
    }

    /**
     * Tests take method
     */
    public function testTake(): void
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
     * Tests the take() method with a traversable non-iterator
     */
    public function testTakeWithTraversableNonIterator(): void
    {
        $collection = new Collection($this->datePeriod('2017-01-01', '2017-01-07'));
        $result = $collection->take(3, 1)->toList();
        $expected = [
            new DateTime('2017-01-02'),
            new DateTime('2017-01-03'),
            new DateTime('2017-01-04'),
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests match
     */
    public function testMatch(): void
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
     */
    public function testFirstMatch(): void
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
     */
    public function testAppend(): void
    {
        $collection = new Collection([1, 2, 3]);
        $combined = $collection->append([4, 5, 6]);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $combined->toArray(false));

        $collection = new Collection(['a' => 1, 'b' => 2]);
        $combined = $collection->append(['c' => 3, 'a' => 4]);
        $this->assertEquals(['a' => 4, 'b' => 2, 'c' => 3], $combined->toArray());
    }

    /**
     * Tests the appendItem method
     */
    public function testAppendItem(): void
    {
        $collection = new Collection([1, 2, 3]);
        $combined = $collection->appendItem(4);
        $this->assertEquals([1, 2, 3, 4], $combined->toArray(false));

        $collection = new Collection(['a' => 1, 'b' => 2]);
        $combined = $collection->appendItem(3, 'c');
        $combined = $combined->appendItem(4, 'a');
        $this->assertEquals(['a' => 4, 'b' => 2, 'c' => 3], $combined->toArray());
    }

    /**
     * Tests the prepend method
     */
    public function testPrepend(): void
    {
        $collection = new Collection([1, 2, 3]);
        $combined = $collection->prepend(['a']);
        $this->assertEquals(['a', 1, 2, 3], $combined->toList());

        $collection = new Collection(['c' => 3, 'd' => 4]);
        $combined = $collection->prepend(['a' => 1, 'b' => 2]);
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $combined->toArray());
    }

    /**
     * Tests prependItem method
     */
    public function testPrependItem(): void
    {
        $collection = new Collection([1, 2, 3]);
        $combined = $collection->prependItem('a');
        $this->assertEquals(['a', 1, 2, 3], $combined->toList());

        $collection = new Collection(['c' => 3, 'd' => 4]);
        $combined = $collection->prependItem(2, 'b');
        $combined = $combined->prependItem(1, 'a');
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $combined->toArray());
    }

    /**
     * Tests prependItem method
     */
    public function testPrependItemPreserveKeys(): void
    {
        $collection = new Collection([1, 2, 3]);
        $combined = $collection->prependItem('a');
        $this->assertEquals(['a', 1, 2, 3], $combined->toList());

        $collection = new Collection(['c' => 3, 'd' => 4]);
        $combined = $collection->prependItem(2, 'b');
        $combined = $combined->prependItem(1, 'a');
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $combined->toArray());
    }

    /**
     * Tests the append method with iterator
     */
    public function testAppendIterator(): void
    {
        $collection = new Collection([1, 2, 3]);
        $iterator = new ArrayIterator([4, 5, 6]);
        $combined = $collection->append($iterator);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $combined->toList());
    }

    public function testAppendNotCollectionInstance(): void
    {
        $collection = new TestCollection([1, 2, 3]);
        $combined = $collection->append([4, 5, 6]);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $combined->toList());
    }

    /**
     * Tests that by calling compile internal iteration operations are not done
     * more than once
     */
    public function testCompile(): void
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = new Collection($items);

        $results = [];
        $compiled = $collection
            ->map(function ($value, $key) use (&$results) {
                $results[] = [$key => $value];

                return $value + 3;
            })
            ->compile();
        $this->assertSame(['a' => 4, 'b' => 5, 'c' => 6], $compiled->toArray());
        $this->assertSame(['a' => 4, 'b' => 5, 'c' => 6], $compiled->toArray());
        $this->assertSame([['a' => 1], ['b' => 2], ['c' => 3]], $results);
    }

    /**
     * Tests converting a non rewindable iterator into a rewindable one using
     * the buffered method.
     */
    public function testBuffered(): void
    {
        $items = new NoRewindIterator(new ArrayIterator(['a' => 4, 'b' => 5, 'c' => 6]));
        $buffered = (new Collection($items))->buffered();
        $this->assertEquals(['a' => 4, 'b' => 5, 'c' => 6], $buffered->toArray());
        $this->assertEquals(['a' => 4, 'b' => 5, 'c' => 6], $buffered->toArray());
    }

    public function testBufferedIterator(): void
    {
        $data = [
            ['myField' => '1'],
            ['myField' => '2'],
            ['myField' => '3'],
        ];
        $buffered = (new Collection($data))->buffered();
        // Check going forwards
        $this->assertNotEmpty($buffered->firstMatch(['myField' => '1']));
        $this->assertNotEmpty($buffered->firstMatch(['myField' => '2']));
        $this->assertNotEmpty($buffered->firstMatch(['myField' => '3']));

        // And backwards.
        $this->assertNotEmpty($buffered->firstMatch(['myField' => '3']));
        $this->assertNotEmpty($buffered->firstMatch(['myField' => '2']));
        $this->assertNotEmpty($buffered->firstMatch(['myField' => '1']));
    }

    /**
     * Tests the combine method
     */
    public function testCombine(): void
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'parent' => 'a'],
            ['id' => 2, 'name' => 'bar', 'parent' => 'b'],
            ['id' => 3, 'name' => 'baz', 'parent' => 'a'],
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
            '2-3' => ['baz-2-3' => '2-3-baz'],
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

    public function testCombineNullKey(): void
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'parent' => 'a'],
            ['id' => null, 'name' => 'bar', 'parent' => 'b'],
            ['id' => 3, 'name' => 'baz', 'parent' => 'a'],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot index by path that does not exist or contains a null value');
        (new Collection($items))->combine('id', 'name');
    }

    public function testCombineNullGroup(): void
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'parent' => 'a'],
            ['id' => 2, 'name' => 'bar', 'parent' => 'b'],
            ['id' => 3, 'name' => 'baz', 'parent' => null],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot group by path that does not exist or contains a null value');
        (new Collection($items))->combine('id', 'name', 'parent');
    }

    public function testCombineGroupNullKey(): void
    {
        $items = [
            ['id' => 1, 'name' => 'foo', 'parent' => 'a'],
            ['id' => 2, 'name' => 'bar', 'parent' => 'b'],
            ['id' => null, 'name' => 'baz', 'parent' => 'a'],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot index by path that does not exist or contains a null value');
        (new Collection($items))->combine('id', 'name', 'parent');
    }

    /**
     * Tests the nest method with only one level
     */
    public function testNest(): void
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
            ['id' => 10, 'parent_id' => 6],
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
                    ['id' => 7, 'parent_id' => 1, 'children' => []],
                ],
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'children' => [
                    ['id' => 5, 'parent_id' => 6, 'children' => []],
                    ['id' => 8, 'parent_id' => 6, 'children' => []],
                    ['id' => 9, 'parent_id' => 6, 'children' => []],
                    ['id' => 10, 'parent_id' => 6, 'children' => []],
                ],
            ],
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with alternate nesting key
     */
    public function testNestAlternateNestingKey(): void
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
            ['id' => 10, 'parent_id' => 6],
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
                    ['id' => 7, 'parent_id' => 1, 'nodes' => []],
                ],
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'nodes' => [
                    ['id' => 5, 'parent_id' => 6, 'nodes' => []],
                    ['id' => 8, 'parent_id' => 6, 'nodes' => []],
                    ['id' => 9, 'parent_id' => 6, 'nodes' => []],
                    ['id' => 10, 'parent_id' => 6, 'nodes' => []],
                ],
            ],
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     */
    public function testNestMultiLevel(): void
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
            ['id' => 10, 'parent_id' => 6],
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
                                    ['id' => 7, 'parent_id' => 3, 'nodes' => []],
                                ],
                            ],
                            [
                                'id' => 4,
                                'parent_id' => 2,
                                'nodes' => [
                                    ['id' => 8, 'parent_id' => 4, 'nodes' => []],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'nodes' => [
                    ['id' => 9, 'parent_id' => 6, 'nodes' => []],
                    ['id' => 10, 'parent_id' => 6, 'nodes' => []],
                ],
            ],
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     */
    public function testNestMultiLevelAlternateNestingKey(): void
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
            ['id' => 10, 'parent_id' => 6],
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
                                    ['id' => 7, 'parent_id' => 3, 'children' => []],
                                ],
                            ],
                            [
                                'id' => 4,
                                'parent_id' => 2,
                                'children' => [
                                    ['id' => 8, 'parent_id' => 4, 'children' => []],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'children' => [
                    ['id' => 9, 'parent_id' => 6, 'children' => []],
                    ['id' => 10, 'parent_id' => 6, 'children' => []],
                ],
            ],
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     */
    public function testNestObjects(): void
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
            new ArrayObject(['id' => 10, 'parent_id' => 6]),
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
                                    new ArrayObject(['id' => 7, 'parent_id' => 3, 'children' => []]),
                                ],
                            ]),
                            new ArrayObject([
                                'id' => 4,
                                'parent_id' => 2,
                                'children' => [
                                    new ArrayObject(['id' => 8, 'parent_id' => 4, 'children' => []]),
                                ],
                            ]),
                        ],
                    ]),
                ],
            ]),
            new ArrayObject([
                'id' => 6,
                'parent_id' => null,
                'children' => [
                    new ArrayObject(['id' => 9, 'parent_id' => 6, 'children' => []]),
                    new ArrayObject(['id' => 10, 'parent_id' => 6, 'children' => []]),
                ],
            ]),
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests the nest method with more than one level
     */
    public function testNestObjectsAlternateNestingKey(): void
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
            new ArrayObject(['id' => 10, 'parent_id' => 6]),
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
                                    new ArrayObject(['id' => 7, 'parent_id' => 3, 'nodes' => []]),
                                ],
                            ]),
                            new ArrayObject([
                                'id' => 4,
                                'parent_id' => 2,
                                'nodes' => [
                                    new ArrayObject(['id' => 8, 'parent_id' => 4, 'nodes' => []]),
                                ],
                            ]),
                        ],
                    ]),
                ],
            ]),
            new ArrayObject([
                'id' => 6,
                'parent_id' => null,
                'nodes' => [
                    new ArrayObject(['id' => 9, 'parent_id' => 6, 'nodes' => []]),
                    new ArrayObject(['id' => 10, 'parent_id' => 6, 'nodes' => []]),
                ],
            ]),
        ];
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * Tests insert
     */
    public function testInsert(): void
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
     * @return array
     */
    public function nestedListProvider(): array
    {
        return [
            ['desc', [1, 2, 3, 5, 7, 4, 8, 6, 9, 10]],
            ['asc', [5, 7, 3, 8, 4, 2, 1, 9, 10, 6]],
            ['leaves', [5, 7, 8, 9, 10]],
        ];
    }

    /**
     * Tests the listNested method with the default 'children' nesting key
     *
     * @dataProvider nestedListProvider
     */
    public function testListNested(string $dir, array $expected): void
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
            ['id' => 10, 'parent_id' => 6],
        ];
        $collection = (new Collection($items))->nest('id', 'parent_id')->listNested($dir);
        $this->assertEquals($expected, $collection->extract('id')->toArray(false));
    }

    /**
     * Tests the listNested spacer output.
     */
    public function testListNestedSpacer(): void
    {
        $items = [
            ['id' => 1, 'parent_id' => null, 'name' => 'Birds'],
            ['id' => 2, 'parent_id' => 1, 'name' => 'Land Birds'],
            ['id' => 3, 'parent_id' => 1, 'name' => 'Eagle'],
            ['id' => 4, 'parent_id' => 1, 'name' => 'Seagull'],
            ['id' => 5, 'parent_id' => 6, 'name' => 'Clown Fish'],
            ['id' => 6, 'parent_id' => null, 'name' => 'Fish'],
        ];
        $collection = (new Collection($items))->nest('id', 'parent_id')->listNested();
        $expected = [
            'Birds',
            '---Land Birds',
            '---Eagle',
            '---Seagull',
            'Fish',
            '---Clown Fish',
        ];
        $this->assertSame($expected, $collection->printer('name', 'id', '---')->toList());
    }

    /**
     * Tests using listNested with a different nesting key
     */
    public function testListNestedCustomKey(): void
    {
        $items = [
            ['id' => 1, 'stuff' => [['id' => 2, 'stuff' => [['id' => 3]]]]],
            ['id' => 4, 'stuff' => [['id' => 5]]],
        ];
        $collection = (new Collection($items))->listNested('desc', 'stuff');
        $this->assertEquals(range(1, 5), $collection->extract('id')->toArray(false));
    }

    /**
     * Tests flattening the collection using a custom callable function
     */
    public function testListNestedWithCallable(): void
    {
        $items = [
            ['id' => 1, 'stuff' => [['id' => 2, 'stuff' => [['id' => 3]]]]],
            ['id' => 4, 'stuff' => [['id' => 5]]],
        ];
        $collection = (new Collection($items))->listNested('desc', function ($item) {
            return $item['stuff'] ?? [];
        });
        $this->assertEquals(range(1, 5), $collection->extract('id')->toArray(false));
    }

    /**
     * Provider for sumOf tests
     *
     * @return array
     */
    public function sumOfProvider(): array
    {
        $items = [
            ['invoice' => ['total' => 100]],
            ['invoice' => ['total' => 200]],
        ];

        $floatItems = [
            ['invoice' => ['total' => 100.0]],
            ['invoice' => ['total' => 200.0]],
        ];

        return [
            'array' => [$items, 300],
            'iterator' => [$this->yieldItems($items), 300],
            'floatArray' => [$floatItems, 300.0],
            'floatIterator' => [$this->yieldItems($floatItems), 300.0],
        ];
    }

    /**
     * Tests the sumOf method
     *
     * @dataProvider sumOfProvider
     * @param float|int $expected
     */
    public function testSumOf(iterable $items, $expected): void
    {
        $this->assertEquals($expected, (new Collection($items))->sumOf('invoice.total'));
    }

    /**
     * Tests the sumOf method
     *
     * @dataProvider sumOfProvider
     * @param float|int $expected
     */
    public function testSumOfCallable(iterable $items, $expected): void
    {
        $sum = (new Collection($items))->sumOf(function ($v) {
            return $v['invoice']['total'];
        });
        $this->assertEquals($expected, $sum);
    }

    /**
     * Tests the stopWhen method with a callable
     *
     * @dataProvider simpleProvider
     */
    public function testStopWhenCallable(iterable $items): void
    {
        $collection = (new Collection($items))->stopWhen(function ($v) {
            return $v > 3;
        });
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $collection->toArray());
    }

    /**
     * Tests the stopWhen method with a matching array
     */
    public function testStopWhenWithArray(): void
    {
        $items = [
            ['foo' => 'bar'],
            ['foo' => 'baz'],
            ['foo' => 'foo'],
        ];
        $collection = (new Collection($items))->stopWhen(['foo' => 'baz']);
        $this->assertEquals([['foo' => 'bar']], $collection->toArray());
    }

    /**
     * Tests the unfold method
     */
    public function testUnfold(): void
    {
        $items = [
            [1, 2, 3, 4],
            [5, 6],
            [7, 8],
        ];

        $collection = (new Collection($items))->unfold();
        $this->assertEquals(range(1, 8), $collection->toArray(false));

        $items = [
            [1, 2],
            new Collection([3, 4]),
        ];
        $collection = (new Collection($items))->unfold();
        $this->assertEquals(range(1, 4), $collection->toArray(false));
    }

    /**
     * Tests the unfold method with empty levels
     */
    public function testUnfoldEmptyLevels(): void
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
     */
    public function testUnfoldWithCallable(): void
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
     */
    public function testThrough(): void
    {
        $items = [1, 2, 3];
        $collection = (new Collection($items))->through(function ($collection) {
            return $collection->append($collection->toList());
        });

        $this->assertEquals([1, 2, 3, 1, 2, 3], $collection->toList());
    }

    /**
     * Tests the through method when it returns an array
     */
    public function testThroughReturnArray(): void
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
     */
    public function testComplexSortBy(): void
    {
        $results = collection([3, 7])
            ->unfold(function ($value) {
                return [
                    ['sorting' => $value * 2],
                    ['sorting' => $value * 2],
                ];
            })
            ->sortBy('sorting')
            ->extract('sorting')
            ->toList();
        $this->assertEquals([14, 14, 6, 6], $results);
    }

    /**
     * Tests __debugInfo() or debug() usage
     */
    public function testDebug(): void
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

        $filter = function ($value) {
            throw new Exception('filter exception');
        };
        $iterator = new CallbackFilterIterator(new ArrayIterator($items), $filter);
        $collection = new Collection($iterator);

        $result = $collection->__debugInfo();
        $expected = [
            'count' => 'An exception occurred while getting count',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Tests the isEmpty() method
     */
    public function testIsEmpty(): void
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
     */
    public function testIsEmptyDoesNotConsume(): void
    {
        $array = new ArrayIterator([1, 2, 3]);
        $inner = new BufferedIterator($array);
        $collection = new Collection($inner);
        $this->assertFalse($collection->isEmpty());
        $this->assertCount(3, $collection->toArray());
    }

    /**
     * Tests the zip() method
     */
    public function testZip(): void
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
            [2, 4, 6, 8, 10],
        ], $zipped->toList());
    }

    /**
     * Tests the zipWith() method
     */
    public function testZipWith(): void
    {
        $collection = new Collection([1, 2]);
        $zipped = $collection->zipWith([3, 4], function ($a, $b) {
            return $a * $b;
        });
        $this->assertEquals([3, 8], $zipped->toList());

        $zipped = $collection->zipWith([3, 4], [5, 6, 7], function (...$args) {
            return array_sum($args);
        });
        $this->assertEquals([9, 12], $zipped->toList());
    }

    /**
     * Tests the skip() method
     */
    public function testSkip(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals([3, 4, 5], $collection->skip(2)->toList());

        $this->assertEquals([1, 2, 3, 4, 5], $collection->skip(0)->toList());
        $this->assertEquals([4, 5], $collection->skip(3)->toList());
        $this->assertEquals([5], $collection->skip(4)->toList());
    }

    /**
     * Test skip() with an overflow
     */
    public function testSkipOverflow(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals([], $collection->skip(3)->toArray());
        $this->assertEquals([], $collection->skip(4)->toArray());
    }

    /**
     * Tests the skip() method with a traversable non-iterator
     */
    public function testSkipWithTraversableNonIterator(): void
    {
        $collection = new Collection($this->datePeriod('2017-01-01', '2017-01-07'));
        $result = $collection->skip(3)->toList();
        $expected = [
            new DateTime('2017-01-04'),
            new DateTime('2017-01-05'),
            new DateTime('2017-01-06'),
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the first() method with a traversable non-iterator
     */
    public function testFirstWithTraversableNonIterator(): void
    {
        $collection = new Collection($this->datePeriod('2017-01-01', '2017-01-07'));
        $date = $collection->first();
        $this->assertInstanceOf('DateTime', $date);
        $this->assertSame('2017-01-01', $date->format('Y-m-d'));
    }

    /**
     * Tests the last() method
     */
    public function testLast(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertSame(3, $collection->last());

        $collection = $collection->map(function ($e) {
            return $e * 2;
        });
        $this->assertSame(6, $collection->last());
    }

    /**
     * Tests the last() method when on an empty collection
     */
    public function testLastWithEmptyCollection(): void
    {
        $collection = new Collection([]);
        $this->assertNull($collection->last());
    }

    /**
     * Tests the last() method with a countable object
     */
    public function testLastWithCountable(): void
    {
        $collection = new Collection(new ArrayObject([1, 2, 3]));
        $this->assertSame(3, $collection->last());
    }

    /**
     * Tests the last() method with an empty countable object
     */
    public function testLastWithEmptyCountable(): void
    {
        $collection = new Collection(new ArrayObject([]));
        $this->assertNull($collection->last());
    }

    /**
     * Tests the last() method with a non-rewindable iterator
     */
    public function testLastWithNonRewindableIterator(): void
    {
        $iterator = new NoRewindIterator(new ArrayIterator([1, 2, 3]));
        $collection = new Collection($iterator);
        $this->assertSame(3, $collection->last());
    }

    /**
     * Tests the last() method with a traversable non-iterator
     */
    public function testLastWithTraversableNonIterator(): void
    {
        $collection = new Collection($this->datePeriod('2017-01-01', '2017-01-07'));
        $date = $collection->last();
        $this->assertInstanceOf('DateTime', $date);
        $this->assertSame('2017-01-06', $date->format('Y-m-d'));
    }

    /**
     * Tests the takeLast() method
     *
     * @dataProvider simpleProvider
     * @param iterable $data The data to test with.
     * @covers ::takeLast
     */
    public function testLastN($data): void
    {
        $collection = new Collection($data);
        $result = $collection->takeLast(3)->toArray();
        $expected = ['b' => 2, 'c' => 3, 'd' => 4];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the takeLast() method with overflow
     *
     * @dataProvider simpleProvider
     * @param iterable $data The data to test with.
     * @covers ::takeLast
     */
    public function testLastNtWithOverflow($data): void
    {
        $collection = new Collection($data);
        $result = $collection->takeLast(10)->toArray();
        $expected = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the takeLast() with an odd numbers collection
     *
     * @dataProvider simpleProvider
     * @param iterable $data The data to test with.
     * @covers ::takeLast
     */
    public function testLastNtWithOddData($data): void
    {
        $collection = new Collection($data);
        $result = $collection->take(3)->takeLast(2)->toArray();
        $expected = ['b' => 2, 'c' => 3];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the takeLast() with countable collection
     *
     * @covers ::takeLast
     */
    public function testLastNtWithCountable(): void
    {
        $rangeZeroToFive = range(0, 5);

        $collection = new Collection(new CountableIterator($rangeZeroToFive));
        $result = $collection->takeLast(2)->toList();
        $this->assertEquals([4, 5], $result);

        $collection = new Collection(new CountableIterator($rangeZeroToFive));
        $result = $collection->takeLast(1)->toList();
        $this->assertEquals([5], $result);
    }

    /**
     * Tests the takeLast() with countable collection
     *
     * @dataProvider simpleProvider
     * @param iterable $data The data to test with.
     * @covers ::takeLast
     */
    public function testLastNtWithNegative($data): void
    {
        $collection = new Collection($data);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The takeLast method requires a number greater than 0.');
        $collection->takeLast(-1)->toArray();
    }

    /**
     * Tests sumOf with no parameters
     */
    public function testSumOfWithIdentity(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertSame(6, $collection->sumOf());

        $collection = new Collection(['a' => 1, 'b' => 4, 'c' => 6]);
        $this->assertSame(11, $collection->sumOf());
    }

    /**
     * Tests using extract with the {*} notation
     */
    public function testUnfoldedExtract(): void
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
                        'voters' => [['id' => 1], ['id' => 2]],
                    ],
                ],
            ],
            [
                'comments' => [
                    [
                        'voters' => [['id' => 3], ['id' => 4]],
                    ],
                ],
            ],
            [
                'comments' => [
                    [
                        'voters' => [['id' => 5], ['nope' => 'fail'], ['id' => 6]],
                    ],
                ],
            ],
            [
                'comments' => [
                    [
                        'not_voters' => [['id' => 5]],
                    ],
                ],
            ],
            ['not_comments' => []],
        ];
        $extracted = (new Collection($items))->extract('comments.{*}.voters.{*}.id');
        $expected = [1, 2, 3, 4, 5, null, 6];
        $this->assertEquals($expected, $extracted->toArray());
        $this->assertEquals($expected, $extracted->toList());
    }

    /**
     * Tests serializing a simple collection
     */
    public function testSerializeSimpleCollection(): void
    {
        $collection = new Collection([1, 2, 3]);
        $serialized = serialize($collection);
        $unserialized = unserialize($serialized);
        $this->assertEquals($collection->toList(), $unserialized->toList());
        $this->assertEquals($collection->toArray(), $unserialized->toArray());
    }

    /**
     * Tests serialization when using append
     */
    public function testSerializeWithAppendIterators(): void
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
     */
    public function testSerializeWithNestedIterators(): void
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
     */
    public function testSerializeWithZipIterator(): void
    {
        $collection = new Collection([4, 5]);
        $collection = $collection->zip([1, 2]);
        $serialized = serialize($collection);
        $unserialized = unserialize($serialized);
        $this->assertEquals($collection->toList(), $unserialized->toList());
    }

    /**
     * Provider for some chunk tests
     *
     * @return array
     */
    public function chunkProvider(): array
    {
        $items = range(1, 10);

        return [
            'array' => [$items],
            'iterator' => [$this->yieldItems($items)],
        ];
    }

    /**
     * Tests the chunk method with exact chunks
     *
     * @dataProvider chunkProvider
     */
    public function testChunk(iterable $items): void
    {
        $collection = new Collection($items);
        $chunked = $collection->chunk(2)->toList();
        $expected = [[1, 2], [3, 4], [5, 6], [7, 8], [9, 10]];
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunk method with overflowing chunk size
     */
    public function testChunkOverflow(): void
    {
        $collection = new Collection(range(1, 11));
        $chunked = $collection->chunk(2)->toList();
        $expected = [[1, 2], [3, 4], [5, 6], [7, 8], [9, 10], [11]];
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunk method with non-scalar items
     */
    public function testChunkNested(): void
    {
        $collection = new Collection([1, 2, 3, [4, 5], 6, [7, [8, 9], 10], 11]);
        $chunked = $collection->chunk(2)->toList();
        $expected = [[1, 2], [3, [4, 5]], [6, [7, [8, 9], 10]], [11]];
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunkWithKeys method with exact chunks
     */
    public function testChunkWithKeys(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6]);
        $chunked = $collection->chunkWithKeys(2)->toList();
        $expected = [['a' => 1, 'b' => 2], ['c' => 3, 'd' => 4], ['e' => 5, 'f' => 6]];
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunkWithKeys method with overflowing chunk size
     */
    public function testChunkWithKeysOverflow(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7]);
        $chunked = $collection->chunkWithKeys(2)->toList();
        $expected = [['a' => 1, 'b' => 2], ['c' => 3, 'd' => 4], ['e' => 5, 'f' => 6], ['g' => 7]];
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunkWithKeys method with non-scalar items
     */
    public function testChunkWithKeysNested(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3, 'd' => [4, 5], 'e' => 6, 'f' => [7, [8, 9], 10], 'g' => 11]);
        $chunked = $collection->chunkWithKeys(2)->toList();
        $expected = [['a' => 1, 'b' => 2], ['c' => 3, 'd' => [4, 5]], ['e' => 6, 'f' => [7, [8, 9], 10]], ['g' => 11]];
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests the chunkWithKeys method without preserving keys
     */
    public function testChunkWithKeysNoPreserveKeys(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7]);
        $chunked = $collection->chunkWithKeys(2, false)->toList();
        $expected = [[0 => 1, 1 => 2], [0 => 3, 1 => 4], [0 => 5, 1 => 6], [0 => 7]];
        $this->assertEquals($expected, $chunked);
    }

    /**
     * Tests cartesianProduct
     */
    public function testCartesianProduct(): void
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
     */
    public function testCartesianProductMultidimensionalArray(): void
    {
        $this->expectException(LogicException::class);
        $collection = new Collection([
            [
                'names' => [
                    'alex', 'kostas', 'leon',
                ],
            ],
            [
                'locations' => [
                    'crete', 'london', 'paris',
                ],
            ],
        ]);

        $result = $collection->cartesianProduct();
    }

    public function testTranspose(): void
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
     */
    public function testTransposeUnEvenLengthShouldThrowException(): void
    {
        $this->expectException(LogicException::class);
        $collection = new Collection([
            ['Products', '2012', '2013', '2014'],
            ['Product A', '200', '100', '50'],
            ['Product B', '300'],
            ['Product C', '400', '300'],
        ]);

        $collection->transpose();
    }

    /**
     * Yields all the elements as passed
     *
     * @param iterable $items the elements to be yielded
     * @return \Generator<array>
     */
    protected function yieldItems(iterable $items): Generator
    {
        foreach ($items as $k => $v) {
            yield $k => $v;
        }
    }

    /**
     * Create a DatePeriod object.
     *
     * @param string $start Start date
     * @param string $end End date
     */
    protected function datePeriod($start, $end): DatePeriod
    {
        return new DatePeriod(new DateTime($start), new DateInterval('P1D'), new DateTime($end));
    }

    /**
     * Tests that elements in a lazy collection are not fetched immediately.
     */
    public function testLazy(): void
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3];
        $collection = (new Collection($items))->lazy();
        $callable = $this->getMockBuilder(stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->never())->method('__invoke');
        $collection->filter($callable)->filter($callable);
    }
}
