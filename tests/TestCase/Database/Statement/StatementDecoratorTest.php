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
namespace Cake\Test\TestCase\Database\Statement;

use Cake\Database\Statement\StatementDecorator;
use Cake\TestSuite\TestCase;

/**
 * Tests StatementDecorator class
 */
class StatementDecoratorTest extends TestCase
{

    /**
     * Tests that calling lastInsertId will proxy it to
     * the driver's lastInsertId method
     *
     * @return void
     */
    public function testLastInsertId()
    {
        $statement = $this->getMockBuilder('\PDOStatement')->getMock();
        $driver = $this->getMockBuilder('\Cake\Database\Driver')->getMock();
        $statement = new StatementDecorator($statement, $driver);

        $driver->expects($this->once())->method('lastInsertId')
            ->with('users')
            ->will($this->returnValue(2));
        $this->assertEquals(2, $statement->lastInsertId('users'));
    }

    /**
     * Tests that calling lastInsertId will get the last insert id by
     * column name
     *
     * @return void
     */
    public function testLastInsertIdWithReturning()
    {
        $internal = $this->getMockBuilder('\PDOStatement')->getMock();
        $driver = $this->getMockBuilder('\Cake\Database\Driver')->getMock();
        $statement = new StatementDecorator($internal, $driver);

        $internal->expects($this->once())->method('columnCount')
            ->will($this->returnValue(1));
        $internal->expects($this->once())->method('fetch')
            ->with('assoc')
            ->will($this->returnValue(['id' => 2]));
        $driver->expects($this->never())->method('lastInsertId');
        $this->assertEquals(2, $statement->lastInsertId('users', 'id'));
    }

    /**
     * Tests that the statement will not be executed twice if the iterator
     * is requested more than once
     *
     * @return void
     */
    public function testNoDoubleExecution()
    {
        $inner = $this->getMockBuilder('\PDOStatement')->getMock();
        $driver = $this->getMockBuilder('\Cake\Database\Driver')->getMock();
        $statement = new StatementDecorator($inner, $driver);

        $inner->expects($this->once())->method('execute');
        $this->assertSame($inner, $statement->getIterator());
        $this->assertSame($inner, $statement->getIterator());
    }
}
