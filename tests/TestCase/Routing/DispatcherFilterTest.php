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

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\DispatcherFilter;
use Cake\TestSuite\TestCase;

/**
 * Dispatcher filter test.
 */
class DispatcherFilterTest extends TestCase {

/**
 * Test that the constructor takes config.
 *
 * @return void
 */
	public function testConstructConfig() {
		$filter = new DispatcherFilter(['one' => 'value', 'on' => '/blog']);
		$this->assertEquals('value', $filter->config('one'));
	}

/**
 * Test setting priority
 *
 * @return void
 */
	public function testConstructPriority() {
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
	public function testImplementedEvents() {
		$filter = new DispatcherFilter(['priority' => 100]);
		$events = $filter->implementedEvents();
		$this->assertEquals(100, $events['Dispatcher.beforeDispatch']['priority']);
		$this->assertEquals(100, $events['Dispatcher.afterDispatch']['priority']);
	}

/**
 * Test constructor error invalid when
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage "when" conditions must be a callable.
 * @return void
 */
	public function testConstructorInvalidWhen() {
		new DispatcherFilter(['when' => 'nope']);
	}

/**
 * Test basic matching with for option.
 *
 * @return void
 */
	public function testMatchesWithFor() {
		$request = new Request(['url' => '/articles/view']);
		$event = new Event('Dispatcher.beforeDispatch', $this, compact('request'));
		$filter = new DispatcherFilter(['for' => '/articles']);
		$this->assertTrue($filter->matches($event));

		$request = new Request(['url' => '/blog/articles']);
		$event = new Event('Dispatcher.beforeDispatch', $this, compact('request'));
		$this->assertFalse($filter->matches($event), 'Does not start with /articles');

		$request = new Request(['url' => '/articles/edit/1']);
		$event = new Event('Dispatcher.beforeDispatch', $this, compact('request'));
		$filter = new DispatcherFilter(['for' => 'preg:#^/articles/edit/\d+$#']);
		$this->assertTrue($filter->matches($event));

		$request = new Request(['url' => '/blog/articles/edit/1']);
		$event = new Event('Dispatcher.beforeDispatch', $this, compact('request'));
		$this->assertFalse($filter->matches($event), 'Does not start with /articles');
	}

/**
 * Test matching with when option.
 *
 * @return void
 */
	public function testMatchesWithWhen() {
		$matcher = function ($request, $response) {
			$this->assertInstanceOf('Cake\Network\Request', $request);
			$this->assertInstanceOf('Cake\Network\Response', $response);
			return true;
		};

		$request = new Request(['url' => '/articles/view']);
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
 */
	public function testMatchesWithForAndWhen() {
		$request = new Request(['url' => '/articles/view']);
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
 */
	public function testImplementedEventsMethodName() {
		$request = new Request(['url' => '/articles/view']);
		$response = new Response();

		$beforeEvent = new Event('Dispatcher.beforeDispatch', $this, compact('response', 'request'));
		$afterEvent = new Event('Dispatcher.afterDispatch', $this, compact('response', 'request'));

		$filter = $this->getMock('Cake\Routing\DispatcherFilter', ['beforeDispatch', 'afterDispatch']);
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
 */
	public function testHandleAppliesFor() {
		$request = new Request(['url' => '/articles/view']);
		$response = new Response();

		$event = new Event('Dispatcher.beforeDispatch', $this, compact('response', 'request'));

		$filter = $this->getMock(
			'Cake\Routing\DispatcherFilter',
			['beforeDispatch'],
			[['for' => '/admin']]
		);
		$filter->expects($this->never())
			->method('beforeDispatch');

		$filter->handle($event);
	}

/**
 * Test handle applies when conditions
 *
 * @return void
 */
	public function testHandleAppliesWhen() {
		$request = new Request(['url' => '/articles/view']);
		$response = new Response();

		$event = new Event('Dispatcher.beforeDispatch', $this, compact('response', 'request'));
		$matcher = function () {
			return false;
		};

		$filter = $this->getMock(
			'Cake\Routing\DispatcherFilter',
			['beforeDispatch'],
			[['when' => $matcher]]
		);
		$filter->expects($this->never())
			->method('beforeDispatch');

		$filter->handle($event);
	}

}
