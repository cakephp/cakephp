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

use ArrayObject;
use Cake\Collection\Iterator\BufferedIterator;
use Cake\TestSuite\TestCase;
use NoRewindIterator;

/**
 * BufferedIterator Test
 */
class BufferedIteratorTest extends TestCase
{
    /**
     * Tests that items are cached once iterated over them
     */
    public function testBufferItems(): void
    {
        $items = new ArrayObject([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]);
        $iterator = new BufferedIterator($items);
        $expected = (array)$items;
        $this->assertSame($expected, $iterator->toArray());

        $items['c'] = 5;
        $buffered = $iterator->toArray();
        $this->assertSame($expected, $buffered);
    }

    /**
     * Tests that items are cached once iterated over them
     */
    public function testCount(): void
    {
        $items = new ArrayObject([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]);
        $iterator = new BufferedIterator($items);
        $this->assertCount(3, $iterator);
        $buffered = $iterator->toArray();
        $this->assertSame((array)$items, $buffered);

        $iterator = new BufferedIterator(new NoRewindIterator($items->getIterator()));
        $this->assertCount(3, $iterator);
        $buffered = $iterator->toArray();
        $this->assertSame((array)$items, $buffered);
    }

    /**
     * Tests that partial iteration can be reset.
     */
    public function testBufferPartial(): void
    {
        $items = new ArrayObject([1, 2, 3]);
        $iterator = new BufferedIterator($items);
        foreach ($iterator as $key => $value) {
            if ($key == 1) {
                break;
            }
        }
        $result = [];
        foreach ($iterator as $value) {
            $result[] = $value;
        }
        $this->assertEquals([1, 2, 3], $result);
    }

    /**
     * Testing serialize and unserialize features.
     */
    public function testSerialization(): void
    {
        $items = new ArrayObject([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]);
        $expected = (array)$items;

        $iterator = new BufferedIterator($items);

        $serialized = serialize($iterator);
        $outcome = unserialize($serialized);
        $this->assertEquals($expected, $outcome->toArray());
    }
}
