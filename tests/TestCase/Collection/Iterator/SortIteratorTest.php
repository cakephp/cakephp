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
namespace Cake\Test\TestCase\Collection\Iterator;

use ArrayObject;
use Cake\Collection\Iterator\SortIterator;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;

/**
 * SortIterator Test
 *
 */
class SortIteratorTest extends TestCase
{

    /**
     * Tests sorting numbers with an identity callbacks
     *
     * @return void
     */
    public function testSortNumbersIdentity()
    {
        $items = new ArrayObject([3, 5, 1, 2, 4]);
        $identity = function ($a) {
            return $a;
        };
        $sorted = new SortIterator($items, $identity);
        $expected = range(5, 1);
        $this->assertEquals($expected, $sorted->toList());

        $sorted = new SortIterator($items, $identity, SORT_ASC);
        $expected = range(1, 5);
        $this->assertEquals($expected, $sorted->toList());
    }

    /**
     * Tests sorting numbers with custom callback
     *
     * @return void
     */
    public function testSortNumbersCustom()
    {
        $items = new ArrayObject([3, 5, 1, 2, 4]);
        $callback = function ($a) {
            return $a * -1;
        };
        $sorted = new SortIterator($items, $callback);
        $expected = range(1, 5);
        $this->assertEquals($expected, $sorted->toList());

        $sorted = new SortIterator($items, $callback, SORT_ASC);
        $expected = range(5, 1);
        $this->assertEquals($expected, $sorted->toList());
    }

    /**
     * Tests sorting a complex structure with numeric sort
     *
     * @return void
     */
    public function testSortComplexNumeric()
    {
        $items = new ArrayObject([
            ['foo' => 1, 'bar' => 'a'],
            ['foo' => 10, 'bar' => 'a'],
            ['foo' => 2, 'bar' => 'a'],
            ['foo' => 13, 'bar' => 'a'],
        ]);
        $callback = function ($a) {
            return $a['foo'];
        };
        $sorted = new SortIterator($items, $callback, SORT_DESC, SORT_NUMERIC);
        $expected = [
            ['foo' => 13, 'bar' => 'a'],
            ['foo' => 10, 'bar' => 'a'],
            ['foo' => 2, 'bar' => 'a'],
            ['foo' => 1, 'bar' => 'a'],
        ];
        $this->assertEquals($expected, $sorted->toList());

        $sorted = new SortIterator($items, $callback, SORT_ASC, SORT_NUMERIC);
        $expected = [
            ['foo' => 1, 'bar' => 'a'],
            ['foo' => 2, 'bar' => 'a'],
            ['foo' => 10, 'bar' => 'a'],
            ['foo' => 13, 'bar' => 'a'],
        ];
        $this->assertEquals($expected, $sorted->toList());
    }

    /**
     * Tests sorting a complex structure with natural sort
     *
     * @return void
     */
    public function testSortComplexNatural()
    {
        $items = new ArrayObject([
            ['foo' => 'foo_1', 'bar' => 'a'],
            ['foo' => 'foo_10', 'bar' => 'a'],
            ['foo' => 'foo_2', 'bar' => 'a'],
            ['foo' => 'foo_13', 'bar' => 'a'],
        ]);
        $callback = function ($a) {
            return $a['foo'];
        };
        $sorted = new SortIterator($items, $callback, SORT_DESC, SORT_NATURAL);
        $expected = [
            ['foo' => 'foo_13', 'bar' => 'a'],
            ['foo' => 'foo_10', 'bar' => 'a'],
            ['foo' => 'foo_2', 'bar' => 'a'],
            ['foo' => 'foo_1', 'bar' => 'a'],
        ];
        $this->assertEquals($expected, $sorted->toList());

        $sorted = new SortIterator($items, $callback, SORT_ASC, SORT_NATURAL);
        $expected = [
            ['foo' => 'foo_1', 'bar' => 'a'],
            ['foo' => 'foo_2', 'bar' => 'a'],
            ['foo' => 'foo_10', 'bar' => 'a'],
            ['foo' => 'foo_13', 'bar' => 'a'],
        ];
        $this->assertEquals($expected, $sorted->toList());
        $this->assertEquals($expected, $sorted->toList(), 'Iterator should rewind');
    }

    /**
     * Tests sorting a complex structure with natural sort with string callback
     *
     * @return void
     */
    public function testSortComplexNaturalWithPath()
    {
        $items = new ArrayObject([
            ['foo' => 'foo_1', 'bar' => 'a'],
            ['foo' => 'foo_10', 'bar' => 'a'],
            ['foo' => 'foo_2', 'bar' => 'a'],
            ['foo' => 'foo_13', 'bar' => 'a'],
        ]);
        $sorted = new SortIterator($items, 'foo', SORT_DESC, SORT_NATURAL);
        $expected = [
            ['foo' => 'foo_13', 'bar' => 'a'],
            ['foo' => 'foo_10', 'bar' => 'a'],
            ['foo' => 'foo_2', 'bar' => 'a'],
            ['foo' => 'foo_1', 'bar' => 'a'],
        ];
        $this->assertEquals($expected, $sorted->toList());

        $sorted = new SortIterator($items, 'foo', SORT_ASC, SORT_NATURAL);
        $expected = [
            ['foo' => 'foo_1', 'bar' => 'a'],
            ['foo' => 'foo_2', 'bar' => 'a'],
            ['foo' => 'foo_10', 'bar' => 'a'],
            ['foo' => 'foo_13', 'bar' => 'a'],
        ];
        $this->assertEquals($expected, $sorted->toList());
        $this->assertEquals($expected, $sorted->toList(), 'Iterator should rewind');
    }

    /**
     * Tests sorting a complex structure with a deep path
     *
     * @return void
     */
    public function testSortComplexDeepPath()
    {
        $items = new ArrayObject([
            ['foo' => ['bar' => 1], 'bar' => 'a'],
            ['foo' => ['bar' => 12], 'bar' => 'a'],
            ['foo' => ['bar' => 10], 'bar' => 'a'],
            ['foo' => ['bar' => 2], 'bar' => 'a'],
        ]);
        $sorted = new SortIterator($items, 'foo.bar', SORT_ASC, SORT_NUMERIC);
        $expected = [
            ['foo' => ['bar' => 1], 'bar' => 'a'],
            ['foo' => ['bar' => 2], 'bar' => 'a'],
            ['foo' => ['bar' => 10], 'bar' => 'a'],
            ['foo' => ['bar' => 12], 'bar' => 'a'],
        ];
        $this->assertEquals($expected, $sorted->toList());
    }

    /**
     * Tests sorting datetime
     *
     * @return void
     */
    public function testSortDateTime()
    {
        $items = new ArrayObject([
            new \DateTime('2014-07-21'),
            new \DateTime('2015-06-30'),
            new \DateTimeImmutable('2013-08-12')
        ]);

        $callback = function ($a) {
            return $a->add(new \DateInterval('P1Y'));
        };
        $sorted = new SortIterator($items, $callback);
        $expected = [
            new \DateTime('2016-06-30'),
            new \DateTime('2015-07-21'),
            new \DateTimeImmutable('2013-08-12')

        ];
        $this->assertEquals($expected, $sorted->toList());

        $items = new ArrayObject([
            new \DateTime('2014-07-21'),
            new \DateTime('2015-06-30'),
            new \DateTimeImmutable('2013-08-12')
        ]);

        $sorted = new SortIterator($items, $callback, SORT_ASC);
        $expected = [
            new \DateTimeImmutable('2013-08-12'),
            new \DateTime('2015-07-21'),
            new \DateTime('2016-06-30'),
        ];
        $this->assertEquals($expected, $sorted->toList());
    }
}
