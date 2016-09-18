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
 * @since         3.3.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Driver;

use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\TestSuite\TestCase;

/**
 * Adds mocking short cuts for testing common driver features.
 *
 * @property string $_driver_class Defines what driver to mock.
 * @property string $_connection_class Defines what connection class to mock.
 *
 * @mixin TestCase
 */
trait MockDriverTrait
{
    /**
     * @param callable $callable
     * @param array $options
     */
    protected function mockDriver(callable $callable, array $options = [[]])
    {
        $this->assertNotNull($this->_driver_class);

        $driver = $this->getMockBuilder($this->_driver_class)
            ->setMethods(['_connect', 'connection'])
            ->setConstructorArgs($options)
            ->getMock();

        $callable($driver);
    }

    /**
     * @param callable $callable
     */
    protected function mockConnection(callable $callable)
    {
        $this->assertNotNull($this->_connection_class);

        $this->mockDriver(function (Driver $driver) use ($callable) {
            $connection = $this->getMockBuilder($this->_connection_class)
                ->setMethods(['connect', 'driver'])
                ->setConstructorArgs([['log' => false]])
                ->getMock();

            $connection
                ->expects($this->any())
                ->method('driver')
                ->will($this->returnValue($driver));

            $callable($connection);
        });
    }

    /**
     * Creates a new Query object.
     *
     * @param callable $callable A callable that executes tests against the Query object.
     */
    protected function newQuery(callable $callable)
    {
        $this->mockConnection(function (Connection $connection) use ($callable) {
            $query = new \Cake\Database\Query($connection);
            $callable($query);
        });
    }
}
