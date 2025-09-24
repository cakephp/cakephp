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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource\Paging;

use Cake\Datasource\Paging\SortField;
use Cake\Datasource\Paging\SortMap;
use Cake\TestSuite\TestCase;

/**
 * SortMap Test Case
 */
class SortMapTest extends TestCase
{
    /**
     * Test constructor and getters
     *
     * @return void
     */
    public function testPushAndResolve(): void
    {
        $map = (new SortMap())
            ->push('active',
                new SortField("active"),
                "name",
                SortField::locked("created", SortField::DESC),
            )
            ->push('title')
            ->push("group", "Groups.name");

        $actual = $map->resolve("active", SortField::ASC);
        $expected = [
            "active" => "asc",
            "name" => "asc",
            "created" => "desc",//fixed, should ignore the direction
        ];
        $this->assertSame($expected, $actual);

        $actual = $map->resolve("active", SortField::DESC);
        $expected = [
            "active" => "desc",
            "name" => "desc",
            "created" => "desc",
        ];
        $this->assertSame($expected, $actual);

        //When only key is defined
        $actual = $map->resolve("title", SortField::ASC);
        $expected = ['title' => 'asc'];
        $this->assertSame($expected, $actual);

        $actual = $map->resolve("title", SortField::DESC);
        $expected = ['title' => 'desc'];
        $this->assertSame($expected, $actual);

        //When used string for field
        $actual = $map->resolve("group", SortField::ASC);
        $expected = ['Groups.name' => 'asc'];
        $this->assertSame($expected, $actual);

        $actual = $map->resolve("group", SortField::DESC);
        $expected = ['Groups.name' => 'desc'];
        $this->assertSame($expected, $actual);

        //When key is not defined
        $actual = $map->resolve("modified", SortField::ASC);
        $this->assertNull($actual);
    }
}
