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
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\CsrfComponent;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
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

        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
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
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'webroot' => '/dir/',
        ]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $this->component->startup($event);

        $cookie = $controller->response->getCookie('csrfToken');
        $this->assertNotEmpty($cookie, 'Should set a token.');
        $this->assertRegExp('/^[a-f0-9]+$/', $cookie['value'], 'Should look like a hash.');
        $this->assertEquals(0, $cookie['expire'], 'session duration.');
        $this->assertEquals('/dir/', $cookie['path'], 'session path.');

        $this->assertEquals($cookie['value'], $controller->request->getParam('_csrfToken'));
    }

    /**
     * Data provider for HTTP method tests.
     *
     * HEAD and GET do not populate $_POST or request->data.
     *
     * @return array
     */
    public static function safeHttpMethodProvider()
    {
        return [
            ['GET'],
            ['HEAD'],
        ];
    }

    /**
     * Test that the CSRF tokens are not required for idempotent operations
     *
     * @dataProvider safeHttpMethodProvider
     * @return void
     */
    public function testSafeMethodNoCsrfRequired($method)
    {
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => 'nope',
            ],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $result = $this->component->startup($event);
        $this->assertNull($result, 'No exception means valid.');
    }

    /**
     * Data provider for HTTP methods that can contain request bodies.
     *
     * @return array
     */
    public static function httpMethodProvider()
    {
        return [
            ['OPTIONS'], ['PATCH'], ['PUT'], ['POST'], ['DELETE'], ['PURGE'], ['INVALIDMETHOD']
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
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => 'testing123',
            ],
            'post' => ['a' => 'b'],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $result = $this->component->startup($event);
        $this->assertNull($result, 'No exception means valid.');
    }

    /**
     * Test that the X-CSRF-Token works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     * @return void
     * @triggers Controller.startup $controller
     */
    public function testInvalidTokenInHeader($method)
    {
        $this->expectException(\Cake\Http\Exception\InvalidCsrfTokenException::class);
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => 'nope',
            ],
            'post' => ['a' => 'b'],
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
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => ['_csrfToken' => 'testing123'],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $result = $this->component->startup($event);
        $this->assertNull($result, 'No exception means valid.');
        $this->assertNull($controller->request->getData('_csrfToken'));
    }

    /**
     * Test that request data works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     * @return void
     */
    public function testInvalidTokenRequestData($method)
    {
        $this->expectException(\Cake\Http\Exception\InvalidCsrfTokenException::class);
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
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
     * @return void
     */
    public function testInvalidTokenRequestDataMissing()
    {
        $this->expectException(\Cake\Http\Exception\InvalidCsrfTokenException::class);
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
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
     * @return void
     */
    public function testInvalidTokenMissingCookie($method)
    {
        $this->expectException(\Cake\Http\Exception\InvalidCsrfTokenException::class);
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method
            ],
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
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'params' => ['requested' => 1],
            'post' => ['_csrfToken' => 'nope'],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $controller->response = new Response();

        $event = new Event('Controller.startup', $controller);
        $result = $this->component->startup($event);
        $this->assertNull($result, 'No error.');
        $this->assertEquals('testing123', $controller->request->getParam('_csrfToken'));
    }

    /**
     * Test that the configuration options work.
     *
     * @return void
     * @triggers Controller.startup $controller
     */
    public function testConfigurationCookieCreate()
    {
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'webroot' => '/dir/'
        ]);
        $controller->response = new Response();

        $component = new CsrfComponent($this->registry, [
            'cookieName' => 'token',
            'expiry' => '+1 hour',
            'secure' => true,
            'httpOnly' => true
        ]);

        $event = new Event('Controller.startup', $controller);
        $component->startup($event);

        $this->assertEmpty($controller->response->getCookie('csrfToken'));
        $cookie = $controller->response->getCookie('token');
        $this->assertNotEmpty($cookie, 'Should set a token.');
        $this->assertRegExp('/^[a-f0-9]+$/', $cookie['value'], 'Should look like a hash.');
        $this->assertWithinRange((new Time('+1 hour'))->format('U'), $cookie['expire'], 1, 'session duration.');
        $this->assertEquals('/dir/', $cookie['path'], 'session path.');
        $this->assertTrue($cookie['secure'], 'cookie security flag missing');
        $this->assertTrue($cookie['httpOnly'], 'cookie httpOnly flag missing');
    }

    /**
     * Test that the configuration options work.
     *
     * @return void
     * @triggers Controller.startup $controller
     */
    public function testConfigurationValidate()
    {
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'POST'],
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
