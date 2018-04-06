<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Routing\DispatcherFactory;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestCase;
use Cake\Test\Fixture\AssertIntegrationTestCase;
use Cake\Utility\Security;

/**
 * Self test of the IntegrationTestCase
 */
class IntegrationTestCaseTest extends IntegrationTestCase
{

    /**
     * Setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        static::setAppNamespace();

        Router::connect('/get/:controller/:action', ['_method' => 'GET'], ['routeClass' => 'InflectedRoute']);
        Router::connect('/head/:controller/:action', ['_method' => 'HEAD'], ['routeClass' => 'InflectedRoute']);
        Router::connect('/options/:controller/:action', ['_method' => 'OPTIONS'], ['routeClass' => 'InflectedRoute']);
        Router::connect('/:controller/:action/*', [], ['routeClass' => 'InflectedRoute']);
        DispatcherFactory::clear();
        DispatcherFactory::add('Routing');
        DispatcherFactory::add('ControllerFactory');
        $this->useHttpServer(false);
    }

    /**
     * Test building a request.
     *
     * @return void
     */
    public function testRequestBuilding()
    {
        $this->configRequest([
            'headers' => [
                'X-CSRF-Token' => 'abc123',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'base' => '',
            'webroot' => '/',
            'environment' => [
                'PHP_AUTH_USER' => 'foo',
                'PHP_AUTH_PW' => 'bar'
            ]
        ]);
        $this->cookie('split_token', 'def345');
        $this->session(['User' => ['id' => 1, 'username' => 'mark']]);
        $request = $this->_buildRequest('/tasks/add', 'POST', ['title' => 'First post']);

        $this->assertEquals('abc123', $request['environment']['HTTP_X_CSRF_TOKEN']);
        $this->assertEquals('application/json', $request['environment']['CONTENT_TYPE']);
        $this->assertEquals('/tasks/add', $request['url']);
        $this->assertArrayHasKey('split_token', $request['cookies']);
        $this->assertEquals('def345', $request['cookies']['split_token']);
        $this->assertEquals(['id' => '1', 'username' => 'mark'], $request['session']->read('User'));
        $this->assertEquals('foo', $request['environment']['PHP_AUTH_USER']);
        $this->assertEquals('bar', $request['environment']['PHP_AUTH_PW']);
    }

    /**
     * Test request building adds csrf tokens
     *
     * @return void
     */
    public function testRequestBuildingCsrfTokens()
    {
        $this->enableCsrfToken();
        $request = $this->_buildRequest('/tasks/add', 'POST', ['title' => 'First post']);

        $this->assertArrayHasKey('csrfToken', $request['cookies']);
        $this->assertArrayHasKey('_csrfToken', $request['post']);
        $this->assertSame($request['cookies']['csrfToken'], $request['post']['_csrfToken']);

        $this->cookie('csrfToken', '');
        $request = $this->_buildRequest('/tasks/add', 'POST', [
            '_csrfToken' => 'fale',
            'title' => 'First post'
        ]);

        $this->assertSame('', $request['cookies']['csrfToken']);
        $this->assertSame('fale', $request['post']['_csrfToken']);
    }

    /**
     * Test multiple actions using CSRF tokens don't fail
     *
     * @return void
     */
    public function testEnableCsrfMultipleRequests()
    {
        $this->enableCsrfToken();
        $first = $this->_buildRequest('/tasks/add', 'POST', ['title' => 'First post']);
        $second = $this->_buildRequest('/tasks/add', 'POST', ['title' => 'Second post']);
        $this->assertSame(
            $first['cookies']['csrfToken'],
            $second['post']['_csrfToken'],
            'Csrf token should match cookie'
        );
        $this->assertSame(
            $first['post']['_csrfToken'],
            $second['post']['_csrfToken'],
            'Tokens should be consistent per test method'
        );
    }

    /**
     * Test pre-determined CSRF tokens.
     *
     * @return void
     */
    public function testEnableCsrfPredeterminedCookie()
    {
        $this->enableCsrfToken();
        $value = 'I am a teapot';
        $this->cookie('csrfToken', $value);
        $request = $this->_buildRequest('/tasks/add', 'POST', ['title' => 'First post']);
        $this->assertSame($value, $request['cookies']['csrfToken'], 'Csrf token should match cookie');
        $this->assertSame($value, $request['post']['_csrfToken'], 'Tokens should match');
    }

    /**
     * Test building a request, with query parameters
     *
     * @return void
     */
    public function testRequestBuildingQueryParameters()
    {
        $request = $this->_buildRequest('/tasks/view?archived=yes', 'GET', []);

        $this->assertSame('/tasks/view', $request['url']);
        $this->assertSame('archived=yes', $request['environment']['QUERY_STRING']);
        $this->assertSame('/tasks/view', $request['environment']['REQUEST_URI']);
    }

    /**
     * Test cookie encrypted
     *
     * @see CookieComponentControllerTest
     */
    public function testCookieEncrypted()
    {
        Security::salt('abcdabcdabcdabcdabcdabcdabcdabcdabcd');
        $this->cookieEncrypted('KeyOfCookie', 'Encrypted with aes by default');
        $request = $this->_buildRequest('/tasks/view', 'GET', []);
        $this->assertStringStartsWith('Q2FrZQ==.', $request['cookies']['KeyOfCookie']);
    }

    /**
     * Test sending get requests.
     *
     * @return void
     */
    public function testGet()
    {
        $this->assertNull($this->_response);

        $this->get('/request_action/test_request_action');
        $this->assertNotEmpty($this->_response);
        $this->assertInstanceOf('Cake\Http\Response', $this->_response);
        $this->assertEquals('This is a test', $this->_response->getBody());

        $this->_response = null;
        $this->get('/get/request_action/test_request_action');
        $this->assertEquals('This is a test', $this->_response->getBody());
    }

    /**
     * Test sending get request and using default `test_app/config/routes.php`.
     *
     * @return void
     */
    public function testGetUsingApplicationWithDefaultRoutes()
    {
        // first clean routes to have Router::$initailized === false
        Router::reload();

        $this->useHttpServer(true);
        $this->configApplication(Configure::read('App.namespace') . '\ApplicationWithDefaultRoutes', null);

        $this->get('/some_alias');
        $this->assertResponseOk();
        $this->assertEquals('5', $this->_getBodyAsString());
    }

    /**
     * Test sending head requests.
     *
     * @return void
     */
    public function testHead()
    {
        $this->assertNull($this->_response);

        $this->head('/request_action/test_request_action');
        $this->assertNotEmpty($this->_response);
        $this->assertInstanceOf('Cake\Http\Response', $this->_response);
        $this->assertResponseSuccess();

        $this->_response = null;
        $this->head('/head/request_action/test_request_action');
        $this->assertResponseSuccess();
    }

    /**
     * Test sending options requests.
     *
     * @return void
     */
    public function testOptions()
    {
        $this->assertNull($this->_response);

        $this->options('/request_action/test_request_action');
        $this->assertNotEmpty($this->_response);
        $this->assertInstanceOf('Cake\Http\Response', $this->_response);
        $this->assertResponseSuccess();

        $this->_response = null;
        $this->options('/options/request_action/test_request_action');
        $this->assertResponseSuccess();
    }

    /**
     * Test sending get requests sets the request method
     *
     * @return void
     */
    public function testGetSpecificRouteHttpServer()
    {
        $this->useHttpServer(true);
        $this->get('/get/request_action/test_request_action');
        $this->assertResponseOk();
        $this->assertEquals('This is a test', $this->_response->getBody());
    }

    /**
     * Test customizing the app class.
     *
     * @return void
     */
    public function testConfigApplication()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot load "TestApp\MissingApp" for use in integration');
        DispatcherFactory::clear();
        $this->useHttpServer(true);
        $this->configApplication('TestApp\MissingApp', []);
        $this->get('/request_action/test_request_action');
    }

    /**
     * Test sending get requests with Http\Server
     *
     * @return void
     */
    public function testGetHttpServer()
    {
        DispatcherFactory::clear();
        $this->useHttpServer(true);
        $this->assertNull($this->_response);

        $this->get('/request_action/test_request_action');
        $this->assertNotEmpty($this->_response);
        $this->assertInstanceOf('Cake\Http\Response', $this->_response);
        $this->assertEquals('This is a test', $this->_response->getBody());
        $this->assertHeader('X-Middleware', 'true');
    }

    /**
     * Test that the PSR7 requests get query string data
     *
     * @return void
     */
    public function testGetQueryStringHttpServer()
    {
        $this->useHttpServer(true);

        $this->configRequest(['headers' => ['Content-Type' => 'text/plain']]);
        $this->get('/request_action/params_pass?q=query');
        $this->assertResponseOk();
        $this->assertResponseContains('"q":"query"');
        $this->assertResponseContains('"contentType":"text\/plain"');
        $this->assertHeader('X-Middleware', 'true');

        $request = $this->_controller->request;
        $this->assertContains('/request_action/params_pass?q=query', $request->here());
        $this->assertContains('/request_action/params_pass?q=query', $request->getRequestTarget());
    }

    /**
     * Test that the PSR7 requests get cookies
     *
     * @return void
     */
    public function testGetCookiesHttpServer()
    {
        $this->useHttpServer(true);

        $this->configRequest(['cookies' => ['split_test' => 'abc']]);
        $this->get('/request_action/cookie_pass');
        $this->assertResponseOk();
        $this->assertResponseContains('"split_test":"abc"');
        $this->assertHeader('X-Middleware', 'true');
    }

    /**
     * Test that the PSR7 requests receive post data
     *
     * @return void
     */
    public function testPostDataHttpServer()
    {
        $this->useHttpServer(true);

        $this->post('/request_action/post_pass', ['title' => 'value']);
        $data = json_decode($this->_response->getBody());
        $this->assertEquals('value', $data->title);
        $this->assertHeader('X-Middleware', 'true');
    }

    /**
     * Test that the PSR7 requests receive encoded data.
     *
     * @return void
     */
    public function testInputDataHttpServer()
    {
        $this->useHttpServer(true);

        $this->post('/request_action/input_test', '{"hello":"world"}');
        if ($this->_response->getBody()->isSeekable()) {
            $this->_response->getBody()->rewind();
        }
        $this->assertSame('world', $this->_response->getBody()->getContents());
        $this->assertHeader('X-Middleware', 'true');
    }

    /**
     * Test that the PSR7 requests receive encoded data.
     *
     * @return void
     */
    public function testInputDataSecurityToken()
    {
        $this->useHttpServer(true);
        $this->enableSecurityToken();

        $this->post('/request_action/input_test', '{"hello":"world"}');
        $this->assertSame('world', '' . $this->_response->getBody());
        $this->assertHeader('X-Middleware', 'true');
    }

    /**
     * Test that the PSR7 requests get cookies
     *
     * @return void
     */
    public function testSessionHttpServer()
    {
        $this->useHttpServer(true);

        $this->session(['foo' => 'session data']);
        $this->get('/request_action/session_test');
        $this->assertResponseOk();
        $this->assertResponseContains('session data');
        $this->assertHeader('X-Middleware', 'true');
    }

    /**
     * Test sending requests stores references to controller/view/layout.
     *
     * @return void
     */
    public function testRequestSetsProperties()
    {
        $this->post('/posts/index');
        $this->assertInstanceOf('Cake\Controller\Controller', $this->_controller);
        $this->assertNotEmpty($this->_viewName, 'View name not set');
        $this->assertContains('Template' . DS . 'Posts' . DS . 'index.ctp', $this->_viewName);
        $this->assertNotEmpty($this->_layoutName, 'Layout name not set');
        $this->assertContains('Template' . DS . 'Layout' . DS . 'default.ctp', $this->_layoutName);

        $this->assertTemplate('index');
        $this->assertLayout('default');
        $this->assertEquals('value', $this->viewVariable('test'));
    }

    /**
     * Test PSR7 requests store references to controller/view/layout
     *
     * @return void
     */
    public function testRequestSetsPropertiesHttpServer()
    {
        $this->useHttpServer(true);
        DispatcherFactory::clear();

        $this->post('/posts/index');
        $this->assertInstanceOf('Cake\Controller\Controller', $this->_controller);
        $this->assertNotEmpty($this->_viewName, 'View name not set');
        $this->assertContains('Template' . DS . 'Posts' . DS . 'index.ctp', $this->_viewName);
        $this->assertNotEmpty($this->_layoutName, 'Layout name not set');
        $this->assertContains('Template' . DS . 'Layout' . DS . 'default.ctp', $this->_layoutName);

        $this->assertTemplate('index');
        $this->assertLayout('default');
        $this->assertEquals('value', $this->viewVariable('test'));
    }

    /**
     * Assert that the stored template doesn't change when cells are rendered.
     *
     * @return void
     */
    public function testAssertTemplateAfterCellRender()
    {
        $this->get('/posts/get');
        $this->assertContains('Template' . DS . 'Posts' . DS . 'get.ctp', $this->_viewName);
        $this->assertTemplate('get');
        $this->assertResponseContains('cellcontent');
    }

    /**
     * Test array URLs
     *
     * @return void
     */
    public function testArrayUrls()
    {
        $this->post(['controller' => 'Posts', 'action' => 'index']);
        $this->assertEquals('value', $this->viewVariable('test'));
    }

    /**
     * Test flash and cookie assertions
     *
     * @return void
     */
    public function testFlashSessionAndCookieAsserts()
    {
        $this->post('/posts/index');

        $this->assertSession('An error message', 'Flash.flash.0.message');
        $this->assertCookie(1, 'remember_me');
        $this->assertCookieNotSet('user_id');
    }

    /**
     * Test flash and cookie assertions
     *
     * @return void
     */
    public function testFlashSessionAndCookieAssertsHttpServer()
    {
        $this->useHttpServer(true);
        $this->post('/posts/index');

        $this->assertSession('An error message', 'Flash.flash.0.message');
        $this->assertCookieNotSet('user_id');
        $this->assertCookie(1, 'remember_me');
    }

    /**
     * Test flash assertions stored with enableRememberFlashMessages() after they
     * are rendered
     *
     * @return void
     */
    public function testFlashAssertionsAfterRender()
    {
        $this->enableRetainFlashMessages();
        $this->get('/posts/index/with_flash');

        $this->assertSession('An error message', 'Flash.flash.0.message');
    }

    /**
     * Test flash assertions stored with enableRememberFlashMessages() even if
     * no view is rendered
     *
     * @return void
     */
    public function testFlashAssertionsWithNoRender()
    {
        $this->enableRetainFlashMessages();
        $this->get('/posts/flashNoRender');
        $this->assertRedirect();

        $this->assertSession('An error message', 'Flash.flash.0.message');
    }

    /**
     * Tests the failure message for assertCookieNotSet
     *
     * @return void
     */
    public function testCookieNotSetFailure()
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Cookie \'remember_me\' has been set');
        $this->post('/posts/index');
        $this->assertCookieNotSet('remember_me');
    }

    /**
     * Tests the failure message for assertCookieNotSet when no
     * response whas generated
     *
     * @return void
     */
    public function testCookieNotSetFailureNoResponse()
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('No response set, cannot assert cookies.');
        $this->assertCookieNotSet('remember_me');
    }

    /**
     * Test error handling and error page rendering.
     *
     * @return void
     */
    public function testPostAndErrorHandling()
    {
        $this->post('/request_action/error_method');
        $this->assertResponseNotEmpty();
        $this->assertResponseContains('Not there or here');
        $this->assertResponseContains('<!DOCTYPE html>');
    }

    /**
     * Test posting to a secured form action.
     *
     * @return void
     */
    public function testPostSecuredForm()
    {
        $this->enableSecurityToken();
        $data = [
            'title' => 'Some title',
            'body' => 'Some text'
        ];
        $this->post('/posts/securePost', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('Request was accepted');
    }

    /**
     * Test posting to a secured form action with nested data.
     *
     * @return void
     */
    public function testPostSecuredFormNestedData()
    {
        $this->enableSecurityToken();
        $data = [
            'title' => 'New post',
            'comments' => [
                ['comment' => 'A new comment']
            ],
            'tags' => ['_ids' => [1, 2, 3, 4]]
        ];
        $this->post('/posts/securePost', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('Request was accepted');
    }

    /**
     * Test posting to a secured form action.
     *
     * @return void
     */
    public function testPostSecuredFormWithQuery()
    {
        $this->enableSecurityToken();
        $data = [
            'title' => 'Some title',
            'body' => 'Some text'
        ];
        $this->post('/posts/securePost?foo=bar', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('Request was accepted');
    }

    /**
     * Test posting to a secured form action with a query that has a part that
     * will be encoded by the security component
     *
     * @return void
     */
    public function testPostSecuredFormWithUnencodedQuery()
    {
        $this->enableSecurityToken();
        $data = [
            'title' => 'Some title',
            'body' => 'Some text'
        ];
        $this->post('/posts/securePost?foo=/', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('Request was accepted');
    }

    /**
     * Test posting to a secured form action action.
     *
     * @return void
     */
    public function testPostSecuredFormFailure()
    {
        $data = [
            'title' => 'Some title',
            'body' => 'Some text'
        ];
        $this->post('/posts/securePost', $data);
        $this->assertResponseError();
    }

    /**
     * Test that exceptions being thrown are handled correctly.
     *
     * @return void
     */
    public function testWithExpectedException()
    {
        $this->get('/tests_apps/throw_exception');
        $this->assertResponseCode(500);
    }

    /**
     * Test that exceptions being thrown are handled correctly by the psr7 stack.
     *
     * @return void
     */
    public function testWithExpectedExceptionHttpServer()
    {
        DispatcherFactory::clear();
        $this->useHttpServer(true);

        $this->get('/tests_apps/throw_exception');
        $this->assertResponseCode(500);
    }

    /**
     * Test that exceptions being thrown are handled correctly.
     *
     * @return void
     */
    public function testWithUnexpectedException()
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->get('/tests_apps/throw_exception');
        $this->assertResponseCode(501);
    }

    /**
     * Test redirecting and integration tests.
     *
     * @return void
     */
    public function testRedirect()
    {
        $this->post('/tests_apps/redirect_to');
        $this->assertResponseSuccess();
        $this->assertResponseCode(302);
    }

    /**
     * Test redirecting and psr7 stack
     *
     * @return void
     */
    public function testRedirectHttpServer()
    {
        DispatcherFactory::clear();
        $this->useHttpServer(true);

        $this->post('/tests_apps/redirect_to');
        $this->assertResponseCode(302);
        $this->assertHeader('X-Middleware', 'true');
    }

    /**
     * Test redirecting and integration tests.
     *
     * @return void
     */
    public function testRedirectPermanent()
    {
        $this->post('/tests_apps/redirect_to_permanent');
        $this->assertResponseSuccess();
        $this->assertResponseCode(301);
    }

    /**
     * Test the responseOk status assertion
     *
     * @return void
     */
    public function testAssertResponseStatusCodes()
    {
        $this->_response = new Response();

        $this->_response = $this->_response->withStatus(200);
        $this->assertResponseOk();

        $this->_response = $this->_response->withStatus(201);
        $this->assertResponseOk();

        $this->_response = $this->_response->withStatus(204);
        $this->assertResponseOk();

        $this->_response = $this->_response->withStatus(202);
        $this->assertResponseSuccess();

        $this->_response = $this->_response->withStatus(302);
        $this->assertResponseSuccess();

        $this->_response = $this->_response->withStatus(400);
        $this->assertResponseError();

        $this->_response = $this->_response->withStatus(417);
        $this->assertResponseError();

        $this->_response = $this->_response->withStatus(500);
        $this->assertResponseFailure();

        $this->_response = $this->_response->withStatus(505);
        $this->assertResponseFailure();

        $this->_response = $this->_response->withStatus(301);
        $this->assertResponseCode(301);
    }

    /**
     * Test the location header assertion.
     *
     * @return void
     */
    public function testAssertRedirect()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withHeader('Location', 'http://localhost/tasks/index');

        $this->assertRedirect();
        $this->assertRedirect('/tasks/index');
        $this->assertRedirect(['controller' => 'Tasks', 'action' => 'index']);

        $this->assertResponseEmpty();
    }

    /**
     * Test the location header assertion.
     *
     * @return void
     */
    public function testAssertNoRedirect()
    {
        $this->_response = new Response();

        $this->assertNoRedirect();
    }

    /**
     * Test the location header assertion.
     *
     * @return void
     */
    public function testAssertNoRedirectFail()
    {
        $test = new AssertIntegrationTestCase('testBadAssertNoRedirect');
        $result = $test->run();

        $this->assertFalse($result->wasSuccessful());
        $this->assertEquals(1, $result->failureCount());
    }

    /**
     * Test the location header assertion string contains
     *
     * @return void
     */
    public function testAssertRedirectContains()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withHeader('Location', 'http://localhost/tasks/index');

        $this->assertRedirectContains('/tasks/index');
    }

    /**
     * Test the header assertion.
     *
     * @return void
     */
    public function testAssertHeader()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withHeader('Etag', 'abc123');

        $this->assertHeader('Etag', 'abc123');
    }

    /**
     * Test the header contains assertion.
     *
     * @return void
     */
    public function testAssertHeaderContains()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withHeader('Etag', 'abc123');

        $this->assertHeaderContains('Etag', 'abc');
    }

    /**
     * Test the content type assertion.
     *
     * @return void
     */
    public function testAssertContentType()
    {
        $this->_response = new Response();
        $this->_response->type('json');

        $this->assertContentType('json');
        $this->assertContentType('application/json');
    }

    /**
     * Test that type() in an action sets the content-type header.
     *
     * @return void
     */
    public function testContentTypeInAction()
    {
        $this->get('/tests_apps/set_type');
        $this->assertHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->assertContentType('json');
        $this->assertContentType('application/json');
    }

    /**
     * Test the content assertion.
     *
     * @return void
     */
    public function testAssertResponseContains()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withStringBody('Some content');

        $this->assertResponseContains('content');
    }

    /**
     * Test the content assertion with no case sensitivity.
     *
     * @return void
     */
    public function testAssertResponseContainsWithIgnoreCaseFlag()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withStringBody('Some content');

        $this->assertResponseContains('some', 'Failed asserting that the body contains given content', true);
    }

    /**
     * Test the negated content assertion.
     *
     * @return void
     */
    public function testAssertResponseNotContains()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withStringBody('Some content');

        $this->assertResponseNotContains('contents');
    }

    /**
     * Test the content regexp assertion.
     *
     * @return void
     */
    public function testAssertResponseRegExp()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withStringBody('Some content');

        $this->assertResponseRegExp('/cont/');
    }

    /**
     * Test the content regexp assertion failing
     *
     * @return void
     */
    public function testAssertResponseRegExpNoResponse()
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('No response set');
        $this->assertResponseRegExp('/cont/');
    }

    /**
     * Test the negated content regexp assertion.
     *
     * @return void
     */
    public function testAssertResponseNotRegExp()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withStringBody('Some content');

        $this->assertResponseNotRegExp('/cant/');
    }

    /**
     * Test negated content regexp assertion failing
     *
     * @return void
     */
    public function testAssertResponseNotRegExpNoResponse()
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('No response set');
        $this->assertResponseNotRegExp('/cont/');
    }

    /**
     * Test that works in tandem with testEventManagerReset2 to
     * test the EventManager reset.
     *
     * The return value is passed to testEventManagerReset2 as
     * an arguments.
     *
     * @return \Cake\Event\EventManager
     */
    public function testEventManagerReset1()
    {
        $eventManager = EventManager::instance();
        $this->assertInstanceOf('Cake\Event\EventManager', $eventManager);

        return $eventManager;
    }

    /**
     * Test if the EventManager is reset between tests.
     *
     * @depends testEventManagerReset1
     * @return void
     */
    public function testEventManagerReset2($prevEventManager)
    {
        $this->assertInstanceOf('Cake\Event\EventManager', $prevEventManager);
        $this->assertNotSame($prevEventManager, EventManager::instance());
    }

    /**
     * Test sending file in requests.
     *
     * @return void
     */
    public function testSendFile()
    {
        $this->get('/posts/file');
        $this->assertFileResponse(TEST_APP . 'TestApp' . DS . 'Controller' . DS . 'PostsController.php');
    }

    /**
     * Test sending file with psr7 stack
     *
     * @return void
     */
    public function testSendFileHttpServer()
    {
        DispatcherFactory::clear();
        $this->useHttpServer(true);

        $this->get('/posts/file');
        $this->assertFileResponse(TEST_APP . 'TestApp' . DS . 'Controller' . DS . 'PostsController.php');
    }

    /**
     * Test that assertFile requires a response
     *
     * @return void
     */
    public function testAssertFileNoResponse()
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('No response set, cannot assert file');
        $this->assertFileResponse('foo');
    }

    /**
     * Test that assertFile requires a file
     *
     * @return void
     */
    public function testAssertFileNoFile()
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('No file was sent in this response');
        $this->get('/posts/get');
        $this->assertFileResponse('foo');
    }

    /**
     * Test disabling the error handler middleware.
     *
     * @return void
     */
    public function testDisableErrorHandlerMiddleware()
    {
        $this->expectException(\Cake\Routing\Exception\MissingRouteException::class);
        $this->expectExceptionMessage('A route matching "/foo" could not be found.');
        $this->disableErrorHandlerMiddleware();
        $this->get('/foo');
    }
}
