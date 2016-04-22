<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Network\Response;
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
        Configure::write('App.namespace', 'TestApp');

        Router::connect('/:controller/:action/*', [], ['routeClass' => 'InflectedRoute']);
        DispatcherFactory::clear();
        DispatcherFactory::add('Routing');
        DispatcherFactory::add('ControllerFactory');
    }

    /**
     * Test building a request.
     *
     * @return void
     */
    public function testRequestBuilding()
    {
        $this->configRequest([
            'headers' => ['X-CSRF-Token' => 'abc123'],
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

        $this->assertEquals('abc123', $request->header('X-CSRF-Token'));
        $this->assertEquals('tasks/add', $request->url);
        $this->assertArrayHasKey('split_token', $request->cookies);
        $this->assertEquals('def345', $request->cookies['split_token']);
        $this->assertEquals(['id' => '1', 'username' => 'mark'], $request->session()->read('User'));
        $this->assertEquals('foo', $request->env('PHP_AUTH_USER'));
        $this->assertEquals('bar', $request->env('PHP_AUTH_PW'));
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

        $this->assertArrayHasKey('csrfToken', $request->cookies);
        $this->assertArrayHasKey('_csrfToken', $request->data);
        $this->assertSame($request->cookies['csrfToken'], $request->data['_csrfToken']);

        $this->cookie('csrfToken', '');
        $request = $this->_buildRequest('/tasks/add', 'POST', [
            '_csrfToken' => 'fale',
            'title' => 'First post'
        ]);

        $this->assertSame('', $request->cookies['csrfToken']);
        $this->assertSame('fale', $request->data['_csrfToken']);
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
        $this->assertSame($first->cookies['csrfToken'], $second->data['_csrfToken'], 'Csrf token should match cookie');
        $this->assertSame(
            $first->data['_csrfToken'],
            $second->data['_csrfToken'],
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
        $this->assertSame($value, $request->cookies['csrfToken'], 'Csrf token should match cookie');
        $this->assertSame($value, $request->data['_csrfToken'], 'Tokens should match');
    }

    /**
     * Test building a request, with query parameters
     *
     * @return void
     */
    public function testRequestBuildingQueryParameters()
    {
        $request = $this->_buildRequest('/tasks/view?archived=yes', 'GET', []);

        $this->assertEquals('/tasks/view?archived=yes', $request->here());
        $this->assertEquals('yes', $request->query('archived'));
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
        $this->assertStringStartsWith('Q2FrZQ==.', $request->cookies['KeyOfCookie']);
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
        $this->assertInstanceOf('Cake\Network\Response', $this->_response);
        $this->assertEquals('This is a test', $this->_response->body());
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
     * Tests the failure message for assertCookieNotSet
     *
     * @expectedException PHPUnit_Framework_AssertionFailedError
     * @expectedExceptionMessage Cookie 'remember_me' has been set
     * @return void
     */
    public function testCookieNotSetFailure()
    {
        $this->post('/posts/index');
        $this->assertCookieNotSet('remember_me');
    }


    /**
     * Tests the failure message for assertCookieNotSet when no
     * response whas generated
     *
     * @expectedException PHPUnit_Framework_AssertionFailedError
     * @expectedExceptionMessage No response set, cannot assert cookies.
     * @return void
     */
    public function testCookieNotSetFailureNoResponse()
    {
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
     * Test that exceptions being thrown are handled correctly.
     *
     * @expectedException PHPUnit_Framework_AssertionFailedError
     * @return void
     */
    public function testWithUnexpectedException()
    {
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

        $this->_response->statusCode(200);
        $this->assertResponseOk();

        $this->_response->statusCode(201);
        $this->assertResponseOk();

        $this->_response->statusCode(204);
        $this->assertResponseOk();

        $this->_response->statusCode(202);
        $this->assertResponseSuccess();

        $this->_response->statusCode(302);
        $this->assertResponseSuccess();

        $this->_response->statusCode(400);
        $this->assertResponseError();

        $this->_response->statusCode(417);
        $this->assertResponseError();

        $this->_response->statusCode(500);
        $this->assertResponseFailure();

        $this->_response->statusCode(505);
        $this->assertResponseFailure();

        $this->_response->statusCode(301);
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
        $this->_response->header('Location', 'http://localhost/tasks/index');

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
        ob_start();
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
        $this->_response->header('Location', 'http://localhost/tasks/index');

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
        $this->_response->header('Etag', 'abc123');

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
        $this->_response->header('Etag', 'abc123');

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
        $this->_response->body('Some content');

        $this->assertResponseContains('content');
    }

    /**
     * Test the negated content assertion.
     *
     * @return void
     */
    public function testAssertResponseNotContains()
    {
        $this->_response = new Response();
        $this->_response->body('Some content');

        $this->assertResponseNotContains('contents');
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
        return EventManager::instance();
    }

    /**
     * Test if the EventManager is reset between tests.
     *
     * @depends testEventManagerReset1
     * @return void
     */
    public function testEventManagerReset2($prevEventManager)
    {
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
     * Test that assertFile requires a response
     *
     * @expectedException PHPUnit_Framework_AssertionFailedError
     * @expectedExceptionMessage No response set, cannot assert file
     * @return void
     */
    public function testAssertFileNoReponse()
    {
        $this->assertFileResponse('foo');
    }

    /**
     * Test that assertFile requires a file
     *
     * @expectedException PHPUnit_Framework_AssertionFailedError
     * @expectedExceptionMessage No file was sent in this response
     * @return void
     */
    public function testAssertFileNoFile()
    {
        $this->get('/posts/get');
        $this->assertFileResponse('foo');
    }
}
