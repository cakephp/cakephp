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

use Cake\Collection\Iterator\ReplaceIterator;
use Cake\TestSuite\TestCase;

/**
 * ReplaceIterator Test
 *
 */
class ReplaceIteratorTest extends TestCase
{

    /**
     * Tests that the iterator works correctly
     *
     * @return void
     */
    public function testReplace()
    {
        $items = new \ArrayIterator([1, 2, 3]);
        $callable = $this->getMock('stdClass', ['__invoke']);
        $callable->expects($this->at(0))
            ->method('__invoke')
            ->with(1, 0, $items)
            ->will($this->returnValue(1));
        $callable->expects($this->at(1))
            ->method('__invoke')
            ->with(2, 1, $items)
            ->will($this->returnValue(4));
        $callable->expects($this->at(2))
            ->method('__invoke')
            ->with(3, 2, $items)
            ->will($this->returnValue(9));

        $map = new ReplaceIterator($items, $callable);
        $this->assertEquals([1, 4, 9], iterator_to_array($map));
    }
}
