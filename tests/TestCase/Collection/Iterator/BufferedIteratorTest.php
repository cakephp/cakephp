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
     *
     * @return void
     */
    public function testBuffer()
    {
        $items = new ArrayObject([
            'a' => 1,
            'b' => 2,
            'c' => 3
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
     *
     * @return void
     */
    public function testCount()
    {
        $items = new ArrayObject([
            'a' => 1,
            'b' => 2,
            'c' => 3
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
}
