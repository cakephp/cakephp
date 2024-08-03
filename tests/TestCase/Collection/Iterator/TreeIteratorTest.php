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

use Cake\Collection\Iterator\NestIterator;
use Cake\Collection\Iterator\TreeIterator;
use Cake\TestSuite\TestCase;

/**
 * TreeIterator Test
 */
class TreeIteratorTest extends TestCase
{
    /**
     * Tests the printer function with defaults
     */
    public function testPrinter(): void
    {
        $items = [
            [
                'id' => 1,
                'name' => 'a',
                'stuff' => [
                    ['id' => 2, 'name' => 'b', 'stuff' => [['id' => 3, 'name' => 'c']]],
                ],
            ],
            ['id' => 4, 'name' => 'd', 'stuff' => [['id' => 5, 'name' => 'e']]],
        ];
        $items = new NestIterator($items, 'stuff');
        $result = (new TreeIterator($items))->printer('name')->toArray();
        $expected = [
            'a',
            '__b',
            '____c',
            'd',
            '__e',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the printer function with a custom key extractor and spacer
     */
    public function testPrinterCustomKeyAndSpacer(): void
    {
        $items = [
            [
                'id' => 1,
                'name' => 'a',
                'stuff' => [
                    ['id' => 2, 'name' => 'b', 'stuff' => [['id' => 3, 'name' => 'c']]],
                ],
            ],
            ['id' => 4, 'name' => 'd', 'stuff' => [['id' => 5, 'name' => 'e']]],
        ];
        $items = new NestIterator($items, 'stuff');
        $result = (new TreeIterator($items))->printer('id', 'name', '@@')->toArray();
        $expected = [
            'a' => '1',
            'b' => '@@2',
            'c' => '@@@@3',
            'd' => '4',
            'e' => '@@5',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the printer function with a closure extractor
     */
    public function testPrinterWithClosure(): void
    {
        $items = [
            [
                'id' => 1,
                'name' => 'a',
                'stuff' => [
                    ['id' => 2, 'name' => 'b', 'stuff' => [['id' => 3, 'name' => 'c']]],
                ],
            ],
            ['id' => 4, 'name' => 'd', 'stuff' => [['id' => 5, 'name' => 'e']]],
        ];
        $items = new NestIterator($items, 'stuff');
        $result = (new TreeIterator($items))
            ->printer(function ($element, $key, $iterator) {
                return ($iterator->getDepth() + 1 ) . '.' . $key . ' ' . $element['name'];
            }, null, '')
            ->toArray();
        $expected = [
            '1.0 a',
            '2.0 b',
            '3.0 c',
            '1.1 d',
            '2.0 e',
        ];
        $this->assertEquals($expected, $result);
    }
}
