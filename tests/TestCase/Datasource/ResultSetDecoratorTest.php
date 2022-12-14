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
namespace Cake\Test\TestCase\Datasource;

use ArrayIterator;
use Cake\Datasource\ResultSetDecorator;
use Cake\TestSuite\TestCase;

/**
 * Tests ResultSetDecorator class
 */
class ResultSetDecoratorTest extends TestCase
{
    /**
     * Tests the decorator can wrap a simple iterator
     */
    public function testDecorateSimpleIterator(): void
    {
        $data = new ArrayIterator([1, 2, 3]);
        $decorator = new ResultSetDecorator($data);
        $this->assertEquals([1, 2, 3], iterator_to_array($decorator));
    }

    /**
     * Tests it toArray() method
     */
    public function testToArray(): void
    {
        $data = new ArrayIterator([1, 2, 3]);
        $decorator = new ResultSetDecorator($data);
        $this->assertEquals([1, 2, 3], $decorator->toArray());
    }

    /**
     * Tests JSON encoding method
     */
    public function testToJson(): void
    {
        $data = new ArrayIterator([1, 2, 3]);
        $decorator = new ResultSetDecorator($data);
        $this->assertEquals(json_encode([1, 2, 3]), json_encode($decorator));
    }

    /**
     * Tests serializing and unserializing the decorator
     */
    public function testSerialization(): void
    {
        $data = new ArrayIterator([1, 2, 3]);
        $decorator = new ResultSetDecorator($data);
        $serialized = serialize($decorator);
        $this->assertEquals([1, 2, 3], unserialize($serialized)->toArray());
    }

    /**
     * Test the first() method which is part of the ResultSet duck type.
     */
    public function testFirst(): void
    {
        $data = new ArrayIterator([1, 2, 3]);
        $decorator = new ResultSetDecorator($data);

        $this->assertSame(1, $decorator->first());
        $this->assertSame(1, $decorator->first());
    }

    /**
     * Test the count() method which is part of the ResultSet duck type.
     */
    public function testCount(): void
    {
        $data = new ArrayIterator([1, 2, 3]);
        $decorator = new ResultSetDecorator($data);

        $this->assertSame(3, $decorator->count());
        $this->assertCount(3, $decorator);
    }
}
