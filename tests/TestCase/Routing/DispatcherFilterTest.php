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

use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\DispatcherFilter;
use Cake\TestSuite\TestCase;

/**
 * Dispatcher filter test.
 */
class DispatcherFilterTest extends TestCase
{

    /**
     * Test that the constructor takes config.
     *
     * @return void
     */
    public function testConstructConfig()
    {
        $filter = new DispatcherFilter(['one' => 'value', 'on' => '/blog']);
        $this->assertEquals('value', $filter->config('one'));
    }

    /**
     * Test setting priority
     *
     * @return void
     */
    public function testConstructPriority()
    {
        $filter = new DispatcherFilter();
        $this->assertEquals(10, $filter->config('priority'));

        $filter = new DispatcherFilter(['priority' => 100]);
        $this->assertEquals(100, $filter->config('priority'));
    }

    /**
     * Test implemented events
     *
     * @return void
     */
    public function testImplementedEvents()
    {
        $filter = new DispatcherFilter(['priority' => 100]);
        $events = $filter->implementedEvents();
        $this->assertEquals(100, $events['Dispatcher.beforeDispatch']['priority']);
        $this->assertEquals(100, $events['Dispatcher.afterDispatch']['priority']);
    }

    /**
     * Test constructor error invalid when
     *
     * @return void
     */
    public function testConstructorInvalidWhen()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"when" conditions must be a callable.');
        new DispatcherFilter(['when' => 'nope']);
    }

    /**
     * Test basic matching with for option.
     *
     * @return void
     * @triggers Dispatcher.beforeDispatch $this, compact('request')
     * @triggers Dispatcher.beforeDispatch $this, compact('request')
     * @triggers Dispatcher.beforeDispatch $this, compact('request')
     * @triggers Dispatcher.beforeDispatch $this, compact('request')
     */
    public function testMatchesWithFor()
    {
        $request = new ServerRequest(['url' => '/articles/view']);
        $event = new Event('Dispatcher.beforeDispatch', $this, compact('request'));
        $filter = new DispatcherFilter(['for' => '/articles']);
        $this->assertTrue($filter->matches($event));

        $request = new ServerRequest(['url' => '/blog/articles']);
        $event = new Event('Dispatcher.beforeDispatch', $this, compact('request'));
        $this->assertFalse($filter->matches($event), 'Does not start with /articles');

        $request = new ServerRequest(['url' => '/articles/edit/1']);
        $event = new Event('Dispatcher.beforeDispatch', $this, compact('request'));
        $filter = new DispatcherFilter(['for' => 'preg:#^/articles/edit/\d+$#']);
        $this->assertTrue($filter->matches($event));

        $request = new ServerRequest(['url' => '/blog/articles/edit/1']);
        $event = new Event('Dispatcher.beforeDispatch', $this, compact('request'));
        $this->assertFalse($filter->matches($event), 'Does not start with /articles');
    }

    /**
     * Test matching with when option.
     *
     * @return void
     * @triggers Dispatcher.beforeDispatch $this, compact('response', 'request')
     */
    public function testMatchesWithWhen()
    {
        $matcher = function ($request, $response) {
            $this->assertInstanceOf('Cake\Http\ServerRequest', $request);
            $this->assertInstanceOf('Cake\Http\Response', $response);

            return true;
        };

        $request = new ServerRequest(['url' => '/articles/view']);
        $response = new Response();
        $event = new Event('Dispatcher.beforeDispatch', $this, compact('response', 'request'));
        $filter = new DispatcherFilter(['when' => $matcher]);
        $this->assertTrue($filter->matches($event));

        $matcher = function () {
            return false;
        };
        $filter = new DispatcherFilter(['when' => $matcher]);
        $this->assertFalse($filter->matches($event));
    }

    /**
     * Test matching with for & when option.
     *
     * @return void
     * @triggers Dispatcher.beforeDispatch $this, compact('response', 'request')
     */
    public function testMatchesWithForAndWhen()
    {
        $request = new ServerRequest(['url' => '/articles/view']);
        $response = new Response();

        $matcher = function () {
            return true;
        };
        $event = new Event('Dispatcher.beforeDispatch', $this, compact('response', 'request'));
        $filter = new DispatcherFilter(['for' => '/admin', 'when' => $matcher]);
        $this->assertFalse($filter->matches($event));

        $filter = new DispatcherFilter(['for' => '/articles', 'when' => $matcher]);
        $this->assertTrue($filter->matches($event));

        $matcher = function () {
            return false;
        };
        $filter = new DispatcherFilter(['for' => '/admin', 'when' => $matcher]);
        $this->assertFalse($filter->matches($event));

        $filter = new DispatcherFilter(['for' => '/articles', 'when' => $matcher]);
        $this->assertFalse($filter->matches($event));
    }

    /**
     * Test event bindings have use condition checker
     *
     * @return void
     * @triggers Dispatcher.beforeDispatch $this, compact('response', 'request')
     * @triggers Dispatcher.afterDispatch $this, compact('response', 'request')
     */
    public function testImplementedEventsMethodName()
    {
        $request = new ServerRequest(['url' => '/articles/view']);
        $response = new Response();

        $beforeEvent = new Event('Dispatcher.beforeDispatch', $this, compact('response', 'request'));
        $afterEvent = new Event('Dispatcher.afterDispatch', $this, compact('response', 'request'));

        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch', 'afterDispatch'])
            ->getMock();
        $filter->expects($this->at(0))
            ->method('beforeDispatch')
            ->with($beforeEvent);
        $filter->expects($this->at(1))
            ->method('afterDispatch')
            ->with($afterEvent);

        $filter->handle($beforeEvent);
        $filter->handle($afterEvent);
    }

    /**
     * Test handle applies for conditions
     *
     * @return void
     * @triggers Dispatcher.beforeDispatch $this, compact('response', 'request')
     */
    public function testHandleAppliesFor()
    {
        $request = new ServerRequest(['url' => '/articles/view']);
        $response = new Response();

        $event = new Event('Dispatcher.beforeDispatch', $this, compact('response', 'request'));

        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch'])
            ->setConstructorArgs([['for' => '/admin']])
            ->getMock();
        $filter->expects($this->never())
            ->method('beforeDispatch');

        $filter->handle($event);
    }

    /**
     * Test handle applies when conditions
     *
     * @return void
     * @triggers Dispatcher.beforeDispatch $this, compact('response', 'request')
     */
    public function testHandleAppliesWhen()
    {
        $request = new ServerRequest(['url' => '/articles/view']);
        $response = new Response();

        $event = new Event('Dispatcher.beforeDispatch', $this, compact('response', 'request'));
        $matcher = function () {
            return false;
        };

        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch'])
            ->setConstructorArgs([['when' => $matcher]])
            ->getMock();
        $filter->expects($this->never())
            ->method('beforeDispatch');

        $filter->handle($event);
    }
}
