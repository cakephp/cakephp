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
namespace Cake\Test\TestCase\Collection\Iterator;

use ArrayIterator;
use Cake\Collection\Iterator\InsertIterator;
use Cake\TestSuite\TestCase;

/**
 * InsertIterator Test
 */
class InsertIteratorTest extends TestCase
{
    /**
     * Test insert simple path
     */
    public function testInsertSimplePath(): void
    {
        $items = [
            'a' => ['name' => 'Derp'],
            'b' => ['name' => 'Derpina'],
        ];
        $values = [20, 21];
        $iterator = new InsertIterator($items, 'age', $values);
        $result = $iterator->toArray();
        $expected = [
            'a' => ['name' => 'Derp', 'age' => 20],
            'b' => ['name' => 'Derpina', 'age' => 21],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test insert deep path
     */
    public function testInsertDeepPath(): void
    {
        $items = [
            'a' => ['name' => 'Derp', 'a' => ['deep' => ['thing' => 1]]],
            'b' => ['name' => 'Derpina', 'a' => ['deep' => ['thing' => 2]]],
        ];
        $values = new ArrayIterator([20, 21]);
        $iterator = new InsertIterator($items, 'a.deep.path', $values);
        $result = $iterator->toArray();
        $expected = [
            'a' => ['name' => 'Derp', 'a' => ['deep' => ['thing' => 1, 'path' => 20]]],
            'b' => ['name' => 'Derpina', 'a' => ['deep' => ['thing' => 2, 'path' => 21]]],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test that missing properties in the path will skip inserting
     */
    public function testInsertDeepPathMissingStep(): void
    {
        $items = [
            'a' => ['name' => 'Derp', 'a' => ['deep' => ['thing' => 1]]],
            'b' => ['name' => 'Derpina', 'a' => ['nested' => 2]],
        ];
        $values = [20, 21];
        $iterator = new InsertIterator($items, 'a.deep.path', $values);
        $result = $iterator->toArray();
        $expected = [
            'a' => ['name' => 'Derp', 'a' => ['deep' => ['thing' => 1, 'path' => 20]]],
            'b' => ['name' => 'Derpina', 'a' => ['nested' => 2]],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Tests that the iterator will insert values as long as there still exist
     * some in the values array
     */
    public function testInsertTargetCountBigger(): void
    {
        $items = [
            'a' => ['name' => 'Derp'],
            'b' => ['name' => 'Derpina'],
        ];
        $values = [20];
        $iterator = new InsertIterator($items, 'age', $values);
        $result = $iterator->toArray();
        $expected = [
            'a' => ['name' => 'Derp', 'age' => 20],
            'b' => ['name' => 'Derpina'],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Tests that the iterator will insert values as long as there still exist
     * some in the values array
     */
    public function testInsertSourceBigger(): void
    {
        $items = [
            'a' => ['name' => 'Derp'],
            'b' => ['name' => 'Derpina'],
        ];
        $values = [20, 21, 23];
        $iterator = new InsertIterator($items, 'age', $values);
        $result = $iterator->toArray();
        $expected = [
            'a' => ['name' => 'Derp', 'age' => 20],
            'b' => ['name' => 'Derpina', 'age' => 21],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Tests the iterator can be rewound
     */
    public function testRewind(): void
    {
        $items = [
            'a' => ['name' => 'Derp'],
            'b' => ['name' => 'Derpina'],
        ];
        $values = [20, 21];
        $iterator = new InsertIterator($items, 'age', $values);
        $iterator->next();
        $this->assertEquals(['name' => 'Derpina', 'age' => 21], $iterator->current());
        $iterator->rewind();

        $result = $iterator->toArray();
        $expected = [
            'a' => ['name' => 'Derp', 'age' => 20],
            'b' => ['name' => 'Derpina', 'age' => 21],
        ];
        $this->assertSame($expected, $result);
    }
}
