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
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\CsrfComponent;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;

/**
 * CsrfComponent test.
 */
class CsrfComponentTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $this->registry = new ComponentRegistry($controller);
        $this->component = new CsrfComponent($this->registry);
    }

    /**
     * teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->component);
    }

    /**
     * Test setting the cookie value
     *
     * @return void
     * @triggers Controller.startup $controller
     */
    public function testSettingCookie()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $controller->request = new Request(['webroot' => '/dir/']);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $this->component->startup($event);

        $cookie = $controller->response->cookie('csrfToken');
        $this->assertNotEmpty($cookie, 'Should set a token.');
        $this->assertRegExp('/^[a-f0-9]+$/', $cookie['value'], 'Should look like a hash.');
        $this->assertEquals(0, $cookie['expire'], 'session duration.');
        $this->assertEquals('/dir/', $cookie['path'], 'session path.');

        $this->assertEquals($cookie['value'], $controller->request->params['_csrfToken']);
    }

    /**
     * Data provider for HTTP method tests.
     *
     * @return void
     */
    public static function httpMethodProvider()
    {
        return [
            ['PATCH'], ['PUT'], ['POST'], ['DELETE']
        ];
    }

    /**
     * Test that the X-CSRF-Token works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     * @return void
     * @triggers Controller.startup $controller
     */
    public function testValidTokenInHeader($method)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'testing123';

        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $controller->request = new Request(['cookies' => ['csrfToken' => 'testing123']]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $result = $this->component->startup($event);
        $this->assertNull($result, 'No exception means valid.');
    }

    /**
     * Test that the X-CSRF-Token works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     * @expectedException \Cake\Network\Exception\InvalidCsrfTokenException
     * @return void
     * @triggers Controller.startup $controller
     */
    public function testInvalidTokenInHeader($method)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'nope';

        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $controller->request = new Request([
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $this->component->startup($event);
    }

    /**
     * Test that request data works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     * @return void
     * @triggers Controller.startup $controller
     */
    public function testValidTokenRequestData($method)
    {
        $_SERVER['REQUEST_METHOD'] = $method;

        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $controller->request = new Request([
            'post' => ['_csrfToken' => 'testing123'],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $result = $this->component->startup($event);
        $this->assertNull($result, 'No exception means valid.');
    }

    /**
     * Test that request data works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     * @expectedException \Cake\Network\Exception\InvalidCsrfTokenException
     * @return void
     */
    public function testInvalidTokenRequestData($method)
    {
        $_SERVER['REQUEST_METHOD'] = $method;

        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $controller->request = new Request([
            'post' => ['_csrfToken' => 'nope'],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $this->component->startup($event);
    }

    /**
     * Test that missing post field fails
     *
     * @expectedException \Cake\Network\Exception\InvalidCsrfTokenException
     * @return void
     */
    public function testInvalidTokenRequestDataMissing()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $controller->request = new Request([
            'post' => [],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $this->component->startup($event);
    }

    /**
     * Test that missing header and cookie fails
     *
     * @dataProvider httpMethodProvider
     * @expectedException \Cake\Network\Exception\InvalidCsrfTokenException
     * @return void
     */
    public function testInvalidTokenMissingCookie($method)
    {
        $_SERVER['REQUEST_METHOD'] = $method;

        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $controller->request = new Request([
            'post' => ['_csrfToken' => 'could-be-valid'],
            'cookies' => []
        ]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $this->component->startup($event);
    }

    /**
     * Test that CSRF checks are not applied to request action requests.
     *
     * @return void
     * @triggers Controller.startup $controller
     */
    public function testCsrfValidationSkipsRequestAction()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $controller->request = new Request([
            'params' => ['requested' => 1],
            'post' => ['_csrfToken' => 'nope'],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $result = $this->component->startup($event);
        $this->assertNull($result, 'No error.');
        $this->assertEquals('testing123', $controller->request->params['_csrfToken']);
    }

    /**
     * Test that the configuration options work.
     *
     * @return void
     * @triggers Controller.startup $controller
     */
    public function testConfigurationCookieCreate()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $controller->request = new Request(['webroot' => '/dir/']);
        $controller->response = new Response();

        $component = new CsrfComponent($this->registry, [
            'cookieName' => 'token',
            'expiry' => '+1 hour',
            'secure' => true
        ]);

        $event = new Event('Controller.startup', $controller);
        $component->startup($event);

        $this->assertEmpty($controller->response->cookie('csrfToken'));
        $cookie = $controller->response->cookie('token');
        $this->assertNotEmpty($cookie, 'Should set a token.');
        $this->assertRegExp('/^[a-f0-9]+$/', $cookie['value'], 'Should look like a hash.');
        $this->assertWithinRange((new Time('+1 hour'))->format('U'), $cookie['expire'], 1, 'session duration.');
        $this->assertEquals('/dir/', $cookie['path'], 'session path.');
        $this->assertTrue($cookie['secure'], 'cookie security flag missing');
    }

    /**
     * Test that the configuration options work.
     *
     * @return void
     * @triggers Controller.startup $controller
     */
    public function testConfigurationValidate()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $controller->request = new Request([
            'cookies' => ['csrfToken' => 'nope', 'token' => 'yes'],
            'post' => ['_csrfToken' => 'no match', 'token' => 'yes'],
        ]);
        $controller->response = new Response();

        $component = new CsrfComponent($this->registry, [
            'cookieName' => 'token',
            'field' => 'token',
            'expiry' => 90,
        ]);

        $event = new Event('Controller.startup', $controller);
        $result = $component->startup($event);
        $this->assertNull($result, 'Config settings should work.');
    }
}
