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
namespace Cake\Test\TestCase\Routing;

use Cake\Http\ServerRequest;
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
        static::setAppNamespace();
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

    /**
     * test create() -> dispatch() -> response flow.
     *
     * @return void
     */
    public function testCreateDispatchWithFilters()
    {
        $url = new ServerRequest([
            'url' => 'posts',
            'params' => [
                'controller' => 'Posts',
                'action' => 'index',
                'pass' => [],
                'bare' => true,
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['send'])
            ->getMock();
        DispatcherFactory::add('ControllerFactory');
        DispatcherFactory::add('Append');

        $dispatcher = DispatcherFactory::create();
        $result = $dispatcher->dispatch($url, $response);
        $this->assertNull($result);
        $this->assertEquals('posts index appended content', $response->body());
    }
}
