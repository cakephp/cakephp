<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Filter;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Filter\AssetDispatcher;
use Cake\TestSuite\TestCase;

class AssetDispatcherTest extends TestCase {

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		Configure::write('Dispatcher.filters', array());
	}

/**
 * Tests that $response->checkNotModified() is called and bypasses
 * file dispatching
 *
 * @return void
 */
	public function testNotModified() {
		$filter = new AssetDispatcher();
		$time = filemtime(App::themePath('TestTheme') . 'webroot/img/cake.power.gif');
		$time = new \DateTime('@' . $time);

		$response = $this->getMock('Cake\Network\Response', array('send', 'checkNotModified'));
		$request = new Request('theme/test_theme/img/cake.power.gif');

		$response->expects($this->once())->method('checkNotModified')
			->with($request)
			->will($this->returnValue(true));
		$event = new Event('DispatcherTest', $this, compact('request', 'response'));

		ob_start();
		$this->assertSame($response, $filter->beforeDispatch($event));
		ob_end_clean();
		$this->assertEquals(200, $response->statusCode());
		$this->assertEquals($time->format('D, j M Y H:i:s') . ' GMT', $response->modified());

		$response = $this->getMock('Cake\Network\Response', array('_sendHeader', 'checkNotModified'));
		$request = new Request('theme/test_theme/img/cake.power.gif');

		$response->expects($this->once())->method('checkNotModified')
			->with($request)
			->will($this->returnValue(true));
		$response->expects($this->never())->method('send');
		$event = new Event('DispatcherTest', $this, compact('request', 'response'));

		$this->assertSame($response, $filter->beforeDispatch($event));
		$this->assertEquals($time->format('D, j M Y H:i:s') . ' GMT', $response->modified());
	}

/**
 * Test that no exceptions are thrown for //index.php type URLs.
 *
 * @return void
 */
	public function test404OnDoubleSlash() {
		$filter = new AssetDispatcher();

		$response = $this->getMock('Response', array('_sendHeader'));
		$request = new Request('//index.php');
		$event = new Event('Dispatcher.beforeRequest', $this, compact('request', 'response'));

		$this->assertNull($filter->beforeDispatch($event));
		$this->assertFalse($event->isStopped());
	}

}
