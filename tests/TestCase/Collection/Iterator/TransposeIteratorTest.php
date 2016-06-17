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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Collection\Iterator;

use Cake\Collection\Collection;
use Cake\TestSuite\TestCase;

class TransposeIteratorTest extends TestCase
{

    public function testTranspose()
    {
        $collection = new Collection([
            ['Products', '2012', '2013', '2014'],
            ['Product A', '200', '100', '50'],
            ['Product B', '300', '200', '100'],
            ['Product C', '400', '300', '200'],
        ]);
        $transposed = $collection->transpose();
        $expected = [
            ['Products', 'Product A', 'Product B', 'Product C'],
            ['2012', '200', '300', '400'],
            ['2013', '100', '200', '300'],
            ['2014', '50', '100', '200'],
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
