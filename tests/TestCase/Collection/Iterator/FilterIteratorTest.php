<?php
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

use Cake\Collection\Iterator\FilterIterator;
use Cake\TestSuite\TestCase;

/**
 * FilterIterator test
 */
class FilterIteratorTest extends TestCase
{

    /**
     * Tests that the iterator works correctly
     *
     * @return void
     */
    public function testFilter()
    {
        $items = new \ArrayIterator([1, 2, 3]);
        $callable = $this->getMockBuilder(\StdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 0, $items)
            ->will($this->returnValue(false));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 1, $items)
            ->will($this->returnValue(true));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(3, 2, $items)
            ->will($this->returnValue(false));

        $filter = new FilterIterator($items, $callable);
        $this->assertEquals([1 => 2], iterator_to_array($filter));
    }
}
