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
namespace Cake\Test\TestCase\Routing;

use Cake\Routing\DispatcherFactory;
use Cake\TestSuite\TestCase;

/**
 * Dispatcher factory test case.
 */
class DispatcherFactoryTest extends TestCase
{

    /**
     * setup function
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        DispatcherFactory::clear();
    }

    /**
     * Test add filter
     *
     * @return void
     */
    public function testAddFilter()
    {
        $mw = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch'])
            ->getMock();
        $result = DispatcherFactory::add($mw);
        $this->assertSame($mw, $result);
    }

    /**
     * Test add filter as a string
     *
     * @return void
     */
    public function testAddFilterString()
    {
        $result = DispatcherFactory::add('Routing');
        $this->assertInstanceOf('Cake\Routing\Filter\RoutingFilter', $result);
    }

    /**
     * Test add filter missing
     *
     * @expectedException \Cake\Routing\Exception\MissingDispatcherFilterException
     * @return void
     */
    public function testAddFilterMissing()
    {
        DispatcherFactory::add('NopeSauce');
    }

    /**
     * Test add filter
     *
     * @return void
     */
    public function testAddFilterWithOptions()
    {
        $config = ['config' => 'value', 'priority' => 999];
        $result = DispatcherFactory::add('Routing', $config);
        $this->assertInstanceOf('Cake\Routing\Filter\RoutingFilter', $result);
        $this->assertEquals($config['config'], $result->config('config'));
        $this->assertEquals($config['priority'], $result->config('priority'));
    }

    /**
     * Test creating a dispatcher with the factory
     *
     * @return void
     */
    public function testCreate()
    {
        $mw = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch'])
            ->getMock();
        DispatcherFactory::add($mw);
        $result = DispatcherFactory::create();
        $this->assertInstanceOf('Cake\Routing\Dispatcher', $result);
        $this->assertCount(1, $result->filters());
    }
}
