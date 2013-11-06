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
 * @since         CakePHP(tm) v3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\Component\CsrfComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;

/**
 * CsrfComponent test.
 */
class CsrfComponentTest extends TestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$controller = $this->getMock('Cake\Controller\Controller');
		$this->registry = new ComponentRegistry($controller);
		$this->component = new CsrfComponent($this->registry);
	}

/**
 * teardown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->component);
	}

/**
 * Test setting the cookie value
 *
 * @return void
 */
	public function testSettingCookie() {
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$controller = $this->getMock('Cake\Controller\Controller');
		$controller->request = new Request(['base' => '/dir']);
		$controller->response = new Response();
		
		$event = new Event('Controller.startup', $controller);
		$this->component->startUp($event);

		$cookie = $controller->response->cookie('csrfToken');
		$this->assertNotEmpty($cookie, 'Should set a token.');
		$this->assertRegExp('/^[a-f0-9]+$/', $cookie['value'], 'Should look like a hash.');
		$this->assertEquals(0, $cookie['expiry'], 'session duration.');
	}

}
