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
use Cake\Collection\Iterator\FilterIterator;
use Cake\TestSuite\TestCase;

/**
 * FilterIterator test
 */
class FilterIteratorTest extends TestCase
{
    /**
     * Tests that the iterator works correctly
     */
    public function testFilter(): void
    {
        $items = new ArrayIterator([1, 2, 3]);
        $callable = function ($value, $key, $itemArg) use ($items) {
            $this->assertSame($items, $itemArg);
            $this->assertContains($value, $items);
            $this->assertContains($key, [0, 1, 2]);

            return $value === 2;
        };

        $filter = new FilterIterator($items, $callable);
        $this->assertEquals([1 => 2], iterator_to_array($filter));
    }
}
