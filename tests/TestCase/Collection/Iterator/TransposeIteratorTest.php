<?php
/**
 * Created by PhpStorm.
 * User: xu
 * Date: 8/6/16
 * Time: 7:31 PM
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
}
