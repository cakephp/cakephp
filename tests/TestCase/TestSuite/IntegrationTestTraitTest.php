<?php
declare(strict_types=1);

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

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Middleware\EncryptedCookieMiddleware;
use Cake\Http\Response;
use Cake\Http\Session;
use Cake\Routing\Route\InflectedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Test\Fixture\AssertIntegrationTestCase;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Self test of the IntegrationTestTrait
 */
class IntegrationTestTraitTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * stub encryption key.
     *
     * @var string
     */
    protected $key = 'abcdabcdabcdabcdabcdabcdabcdabcdabcd';

    /**
     * Setup method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();

        Router::reload();
        Router::extensions(['json']);
        Router::scope('/', function (RouteBuilder $routes) {
            $routes->registerMiddleware('cookie', new EncryptedCookieMiddleware(['secrets'], $this->key));
            $routes->applyMiddleware('cookie');

            $routes->setRouteClass(InflectedRoute::class);
            $routes->get('/get/:controller/:action', []);
            $routes->head('/head/:controller/:action', []);
            $routes->options('/options/:controller/:action', []);
            $routes->connect('/:controller/:action/*', []);
        });

        $this->configApplication(Configure::read('App.namespace') . '\Application', null);
    }

    /**
     * Tests that all data that used by the request is cast to strings
     *
     * @return void
     */
    public function testDataCastToString()
    {
        $data = [
            'title' => 'Blog Post',
            'status' => 1,
            'published' => true,
            'not_published' => false,
            'comments' => [
                [
                    'body' => 'Comment',
                    'status' => 1,
                ],
            ],
            'file' => [
                'tmp_name' => __FILE__,
                'size' => 42,
                'error' => 0,
                'type' => 'text/plain',
                'name' => 'Uploaded file',
            ],
            'pictures' => [
                'name' => [
                    ['file' => 'a-file.png'],
                    ['file' => 'a-moose.png'],
                ],
                'type' => [
                    ['file' => 'image/png'],
                    ['file' => 'image/jpg'],
                ],
                'tmp_name' => [
                    ['file' => __FILE__],
                    ['file' => __FILE__],
                ],
                'error' => [
                    ['file' => 0],
                    ['file' => 0],
                ],
                'size' => [
                    ['file' => 17188],
                    ['file' => 2010],
                ],
            ],
            'upload' => new UploadedFile(__FILE__, 42, 0),
        ];
        $request = $this->_buildRequest('/posts/add', 'POST', $data);
        $this->assertIsString($request['post']['status']);
        $this->assertIsString($request['post']['published']);
        $this->assertSame('0', $request['post']['not_published']);
        $this->assertIsString($request['post']['comments'][0]['status']);
        $this->assertIsInt($request['post']['file']['error']);
        $this->assertIsInt($request['post']['file']['size']);
        $this->assertIsInt($request['post']['pictures']['error'][0]['file']);
        $this->assertIsInt($request['post']['pictures']['error'][1]['file']);
        $this->assertIsInt($request['post']['pictures']['size'][0]['file']);
        $this->assertIsInt($request['post']['pictures']['size'][1]['file']);
        $this->assertInstanceOf(UploadedFile::class, $request['post']['upload']);
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
                'Accept' => 'application/json',
            ],
            'base' => '',
            'webroot' => '/',
            'environment' => [
                'PHP_AUTH_USER' => 'foo',
                'PHP_AUTH_PW' => 'bar',
            ],
        ]);
        $this->cookie('split_token', 'def345');
        $this->session(['User' => ['id' => '1', 'username' => 'mark']]);
        $request = $this->_buildRequest('/tasks/add', 'POST', ['title' => 'First post']);

        $this->assertSame('abc123', $request['environment']['HTTP_X_CSRF_TOKEN']);
        $this->assertSame('application/json', $request['environment']['CONTENT_TYPE']);
        $this->assertSame('/tasks/add', $request['url']);
        $this->assertArrayHasKey('split_token', $request['cookies']);
        $this->assertSame('def345', $request['cookies']['split_token']);
        $this->assertSame(['id' => '1', 'username' => 'mark'], $request['session']->read('User'));
        $this->assertSame('foo', $request['environment']['PHP_AUTH_USER']);
        $this->assertSame('bar', $request['environment']['PHP_AUTH_PW']);
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
            'title' => 'First post',
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
        Security::setSalt($this->key);
        $this->cookieEncrypted('KeyOfCookie', 'Encrypted with aes by default');
        $request = $this->_buildRequest('/tasks/view', 'GET', []);
        $this->assertStringStartsWith('Q2FrZQ==.', $request['cookies']['KeyOfCookie']);
    }

    /**
     * Test sending get request and using default `test_app/config/routes.php`.
     *
     * @return void
     */
    public function testGetUsingApplicationWithPluginRoutes()
    {
        // first clean routes to have Router::$initailized === false
        Router::reload();
        $this->clearPlugins();

        $this->configApplication(Configure::read('App.namespace') . '\ApplicationWithPluginRoutes', null);

        $this->get('/test_plugin');
        $this->assertResponseOk();
    }

    /**
     * Test sending get request and using default `test_app/config/routes.php`.
     *
     * @return void
     */
    public function testGetUsingApplicationWithDefaultRoutes()
    {
        // first clean routes to have Router::$initialized === false
        Router::reload();

        $this->configApplication(Configure::read('App.namespace') . '\ApplicationWithDefaultRoutes', null);

        $this->get('/some_alias');
        $this->assertResponseOk();
        $this->assertSame('5', $this->_getBodyAsString());
    }

    public function testExceptionsInMiddlewareJsonView()
    {
        Router::reload();
        Router::connect('/json_response/api_get_data', [
            'controller' => 'JsonResponse',
            'action' => 'apiGetData',
        ]);

        $this->configApplication(Configure::read('App.namespace') . '\ApplicationWithExceptionsInMiddleware', null);

        $this->_request['headers'] = [ 'Accept' => 'application/json' ];
        $this->get('/json_response/api_get_data');
        $this->assertResponseCode(403);
        $this->assertHeader('Content-Type', 'application/json');
        $this->assertResponseContains('"message": "Sample Message"');
        $this->assertResponseContains('"code": 403');
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
    }

    /**
     * Test sending head requests.
     *
     * @return void
     */
    public function testHeadMethodRoute()
    {
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
    }

    /**
     * Test sending options requests.
     *
     * @return void
     */
    public function testOptionsMethodRoute()
    {
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
        $this->get('/get/request_action/test_request_action');
        $this->assertResponseOk();
        $this->assertSame('This is a test', (string)$this->_response->getBody());
    }

    /**
     * Test customizing the app class.
     *
     * @return void
     */
    public function testConfigApplication()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot load `TestApp\MissingApp` for use in integration');
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
        $this->assertNull($this->_response);

        $this->get('/request_action/test_request_action');
        $this->assertNotEmpty($this->_response);
        $this->assertInstanceOf('Cake\Http\Response', $this->_response);
        $this->assertSame('This is a test', (string)$this->_response->getBody());
        $this->assertHeader('X-Middleware', 'true');
    }

    /**
     * Test that the PSR7 requests get query string data
     *
     * @return void
     */
    public function testGetQueryStringHttpServer()
    {
        $this->configRequest(['headers' => ['Content-Type' => 'text/plain']]);
        $this->get('/request_action/params_pass?q=query');
        $this->assertResponseOk();
        $this->assertResponseContains('"q":"query"');
        $this->assertResponseContains('"contentType":"text\/plain"');
        $this->assertHeader('X-Middleware', 'true');

        $request = $this->_controller->getRequest();
        $this->assertStringContainsString('/request_action/params_pass?q=query', $request->getRequestTarget());
    }

    /**
     * Test that the PSR7 requests get query string data
     *
     * @return void
     */
    public function testGetQueryStringSetsHere()
    {
        $this->configRequest(['headers' => ['Content-Type' => 'text/plain']]);
        $this->get('/request_action/params_pass?q=query');
        $this->assertResponseOk();
        $this->assertResponseContains('"q":"query"');
        $this->assertResponseContains('"contentType":"text\/plain"');
        $this->assertHeader('X-Middleware', 'true');

        $request = $this->_controller->getRequest();
        $this->assertStringContainsString('/request_action/params_pass?q=query', $request->getRequestTarget());
        $this->assertStringContainsString('/request_action/params_pass', $request->getAttribute('here'));
    }

    /**
     * Test that the PSR7 requests get cookies
     *
     * @return void
     */
    public function testGetCookiesHttpServer()
    {
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
        $this->post('/request_action/post_pass', ['title' => 'value']);
        $data = json_decode('' . $this->_response->getBody());
        $this->assertSame('value', $data->title);
        $this->assertHeader('X-Middleware', 'true');
    }

    /**
     * Test that the PSR7 requests receive put data
     *
     * @return void
     */
    public function testPutDataFormUrlEncoded()
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);
        $this->put('/request_action/post_pass', ['title' => 'value']);
        $this->assertResponseOk();
        $data = json_decode('' . $this->_response->getBody());
        $this->assertSame('value', $data->title);
    }

    /**
     * Test that the uploaded files are passed correctly to the request
     *
     * @return void
     */
    public function testUploadedFiles()
    {
        $this->configRequest([
            'files' => [
                'file' => [
                    'tmp_name' => __FILE__,
                    'size' => 42,
                    'error' => 0,
                    'type' => 'text/plain',
                    'name' => 'Uploaded file',
                ],
                'pictures' => [
                    'name' => [
                        ['file' => 'a-file.png'],
                        ['file' => 'a-moose.png'],
                    ],
                    'type' => [
                        ['file' => 'image/png'],
                        ['file' => 'image/jpg'],
                    ],
                    'tmp_name' => [
                        ['file' => __FILE__],
                        ['file' => __FILE__],
                    ],
                    'error' => [
                        ['file' => 0],
                        ['file' => 0],
                    ],
                    'size' => [
                        ['file' => 17188],
                        ['file' => 2010],
                    ],
                ],
                'upload' => new UploadedFile(__FILE__, 42, 0),
            ],
        ]);
        $this->post('/request_action/uploaded_files');
        $this->assertHeader('X-Middleware', 'true');
        $data = json_decode((string)$this->_response->getBody(), true);

        $this->assertSame([
            'file' => 'Uploaded file',
            'pictures.0.file' => 'a-file.png',
            'pictures.1.file' => 'a-moose.png',
            'upload' => null,
        ], $data);
    }

    /**
     * Test that the PSR7 requests receive encoded data.
     *
     * @return void
     */
    public function testInputDataHttpServer()
    {
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
        $this->assertStringContainsString('templates' . DS . 'Posts' . DS . 'index.php', $this->_viewName);
        $this->assertNotEmpty($this->_layoutName, 'Layout name not set');
        $this->assertStringContainsString('templates' . DS . 'layout' . DS . 'default.php', $this->_layoutName);

        $this->assertTemplate('index');
        $this->assertLayout('default');
        $this->assertSame('value', $this->viewVariable('test'));
    }

    /**
     * Test PSR7 requests store references to controller/view/layout
     *
     * @return void
     */
    public function testRequestSetsPropertiesHttpServer()
    {
        $this->post('/posts/index');
        $this->assertInstanceOf('Cake\Controller\Controller', $this->_controller);
        $this->assertNotEmpty($this->_viewName, 'View name not set');
        $this->assertStringContainsString('templates' . DS . 'Posts' . DS . 'index.php', $this->_viewName);
        $this->assertNotEmpty($this->_layoutName, 'Layout name not set');
        $this->assertStringContainsString('templates' . DS . 'layout' . DS . 'default.php', $this->_layoutName);

        $this->assertTemplate('index');
        $this->assertLayout('default');
        $this->assertSame('value', $this->viewVariable('test'));
    }

    /**
     * Tests URLs containing extensions.
     *
     * @return void
     */
    public function testRequestWithExt()
    {
        $this->get(['controller' => 'Posts', 'action' => 'ajax', '_ext' => 'json']);

        $this->assertResponseCode(200);
    }

    /**
     * Assert that the stored template doesn't change when cells are rendered.
     *
     * @return void
     */
    public function testAssertTemplateAfterCellRender()
    {
        $this->get('/posts/get');
        $this->assertStringContainsString('templates' . DS . 'Posts' . DS . 'get.php', $this->_viewName);
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
        $this->post(['controller' => 'Posts', 'action' => 'index', '_method' => 'POST']);
        $this->assertResponseOk();
        $this->assertSame('value', $this->viewVariable('test'));
    }

    /**
     * Test array URL with host
     *
     * @return void
     */
    public function testArrayUrlWithHost()
    {
        $this->get([
            'controller' => 'Posts',
            'action' => 'hostData',
            '_host' => 'app.example.org',
            '_ssl' => true,
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('"isSsl":true');
        $this->assertResponseContains('"host":"app.example.org"');
    }

    /**
     * Test array URLs with an empty router.
     *
     * @return void
     */
    public function testArrayUrlsEmptyRouter()
    {
        Router::reload();
        $this->assertEmpty(Router::getRouteCollection()->routes());

        $this->get(['controller' => 'Posts', 'action' => 'index']);
        $this->assertResponseOk();
        $this->assertSame('value', $this->viewVariable('test'));
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
     * Tests assertCookieNotSet assertion
     *
     * @return void
     */
    public function testAssertCookieNotSet()
    {
        $this->cookie('test', 'value');
        $this->get('/cookie_component_test/remove_cookie/test');
        $this->assertCookieNotSet('test');
    }

    /**
     * Tests the failure message for assertCookieNotSet
     *
     * @return void
     */
    public function testCookieNotSetFailure()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that \'remember_me\' cookie is not set');
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
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No response set, cannot assert content.');
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
            'body' => 'Some text',
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
                ['comment' => 'A new comment'],
            ],
            'tags' => ['_ids' => [1, 2, 3, 4]],
        ];
        $this->post('/posts/securePost', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('Request was accepted');
    }

    /**
     * Test posting to a secured form action with unlocked fields
     *
     * @return void
     */
    public function testPostSecuredFormUnlockedFieldsFails()
    {
        $this->enableSecurityToken();
        $data = [
            'title' => 'New post',
            'comments' => [
                ['comment' => 'A new comment'],
            ],
            'tags' => ['_ids' => [1, 2, 3, 4]],
            'some_unlocked_field' => 'Unlocked data',
        ];
        $this->post('/posts/securePost', $data);
        $this->assertResponseCode(400);
        $this->assertResponseContains('Invalid form protection debug token.');
    }

    /**
     * Test posting to a secured form action with unlocked fields
     *
     * @return void
     */
    public function testPostSecuredFormUnlockedFieldsWithSet()
    {
        $this->enableSecurityToken();
        $data = [
            'title' => 'New post',
            'comments' => [
                ['comment' => 'A new comment'],
            ],
            'tags' => ['_ids' => [1, 2, 3, 4]],
            'some_unlocked_field' => 'Unlocked data',
        ];
        $this->setUnlockedFields(['some_unlocked_field']);
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
            'body' => 'Some text',
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
            'body' => 'Some text',
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
            'body' => 'Some text',
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
        $this->expectException(AssertionFailedError::class);
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
        $this->_response = $this->_response->withHeader('Location', 'http://localhost/get/tasks/index');

        $this->assertRedirect();
        $this->assertRedirect('/get/tasks/index');
        $this->assertRedirect(['controller' => 'Tasks', 'action' => 'index']);

        $this->assertResponseEmpty();
    }

    /**
     * Test the location header assertion.
     *
     * @return void
     */
    public function testAssertRedirectEquals()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withHeader('Location', '/get/tasks/index');

        $this->assertRedirect();
        $this->assertRedirectEquals('/get/tasks/index');
        $this->assertRedirectEquals(['controller' => 'Tasks', 'action' => 'index']);

        $this->assertResponseEmpty();
    }

    /**
     * Test the location header assertion string not contains
     *
     * @return void
     */
    public function testAssertRedirectNotContains()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withHeader('Location', 'http://localhost/tasks/index');
        $this->assertRedirectNotContains('test');
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
        $this->assertSame(1, $result->failureCount());
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
     * Test the header not contains assertion.
     *
     * @return void
     */
    public function testAssertHeaderNotContains()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withHeader('Etag', 'abc123');

        $this->assertHeaderNotContains('Etag', 'xyz');
    }

    /**
     * Test the content type assertion.
     *
     * @return void
     */
    public function testAssertContentType()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withType('json');

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
        $this->assertHeader('Content-Type', 'application/json');
        $this->assertContentType('json');
        $this->assertContentType('application/json');
    }

    /**
     * Test the content assertion.
     *
     * @return void
     */
    public function testAssertResponseEquals()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withStringBody('Some content');

        $this->assertResponseEquals('Some content');
    }

    /**
     * Test the negated content assertion.
     *
     * @return void
     */
    public function testAssertResponseNotEquals()
    {
        $this->_response = new Response();
        $this->_response = $this->_response->withStringBody('Some content');

        $this->assertResponseNotEquals('Some Content');
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
        $this->expectException(AssertionFailedError::class);
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
        $this->expectException(AssertionFailedError::class);
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
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No response set, cannot assert content');
        $this->assertFileResponse('foo');
    }

    /**
     * Test that assertFile requires a file
     *
     * @return void
     */
    public function testAssertFileNoFile()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that file was sent.');
        $this->get('/posts/get');
        $this->assertFileResponse('foo');
    }

    /**
     * Test disabling the error handler middleware with exceptions
     * in controllers.
     *
     * @return void
     */
    public function testDisableErrorHandlerMiddleware()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('oh no!');
        $this->disableErrorHandlerMiddleware();
        $this->get('/posts/throw_exception');
    }

    /**
     * tests getting a secure action while passing a query string
     *
     * @return void
     * @dataProvider methodsProvider
     */
    public function testSecureWithQueryString($method)
    {
        $this->enableSecurityToken();
        $this->{$method}('/posts/securePost/?ids[]=1&ids[]=2');
        $this->assertResponseOk();
    }

    /**
     * Tests flash assertions
     *
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function testAssertFlashMessage()
    {
        $this->get('/posts/stacked_flash');

        $this->assertFlashElement('flash/error');
        $this->assertFlashElement('flash/success', 'custom');

        $this->assertFlashMessage('Error 1');
        $this->assertFlashMessageAt(0, 'Error 1');
        $this->assertFlashElementAt(0, 'flash/error');

        $this->assertFlashMessage('Error 2');
        $this->assertFlashMessageAt(1, 'Error 2');
        $this->assertFlashElementAt(1, 'flash/error');

        $this->assertFlashMessage('Success 1', 'custom');
        $this->assertFlashMessageAt(0, 'Success 1', 'custom');
        $this->assertFlashElementAt(0, 'flash/success', 'custom');

        $this->assertFlashMessage('Success 2', 'custom');
        $this->assertFlashMessageAt(1, 'Success 2', 'custom');
        $this->assertFlashElementAt(1, 'flash/success', 'custom');
    }

    /**
     * Tests asserting flash messages without first sending a request
     *
     * @return void
     */
    public function testAssertFlashMessageWithoutSendingRequest()
    {
        $this->expectException(AssertionFailedError::class);
        $message = 'There is no stored session data. Perhaps you need to run a request?';
        $message .= ' Additionally, ensure `$this->enableRetainFlashMessages()` has been enabled for the test.';
        $this->expectExceptionMessage($message);

        $this->assertFlashMessage('Will not work');
    }

    /**
     * tests failure messages for assertions
     *
     * @param string $assertion Assertion method
     * @param string $message Expected failure message
     * @param string $url URL to test
     * @param mixed ...$rest
     * @dataProvider assertionFailureMessagesProvider
     */
    public function testAssertionFailureMessages($assertion, $message, $url, ...$rest)
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage($message);

        Security::setSalt($this->key);

        $this->get($url);

        call_user_func_array([$this, $assertion], $rest);
    }

    /**
     * data provider for assertion failure messages
     *
     * @return array
     */
    public function assertionFailureMessagesProvider()
    {
        $templateDir = TEST_APP . 'templates' . DS;

        return [
            'assertContentType' => ['assertContentType', 'Failed asserting that \'test\' is set as the Content-Type (`text/html`).', '/posts/index', 'test'],
            'assertContentTypeVerbose' => ['assertContentType', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', 'test'],
            'assertCookie' => ['assertCookie', 'Failed asserting that \'test\' is in cookie \'remember_me\'.', '/posts/index', 'test', 'remember_me'],
            'assertCookieVerbose' => ['assertCookie', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', 'test', 'remember_me'],
            'assertCookieEncrypted' => ['assertCookieEncrypted', 'Failed asserting that \'test\' is encrypted in cookie \'secrets\'.', '/posts/secretCookie', 'test', 'secrets'],
            'assertCookieEncryptedVerbose' => ['assertCookieEncrypted', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', 'test', 'NameOfCookie'],
            'assertCookieNotSet' => ['assertCookieNotSet', 'Failed asserting that \'remember_me\' cookie is not set.', '/posts/index', 'remember_me'],
            'assertFileResponse' => ['assertFileResponse', 'Failed asserting that \'test\' file was sent.', '/posts/file', 'test'],
            'assertFileResponseVerbose' => ['assertFileResponse', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', 'test'],
            'assertHeader' => ['assertHeader', 'Failed asserting that \'test\' equals content in header \'X-Cake\' (`custom header`).', '/posts/header', 'X-Cake', 'test'],
            'assertHeaderContains' => ['assertHeaderContains', 'Failed asserting that \'test\' is in header \'X-Cake\' (`custom header`)', '/posts/header', 'X-Cake', 'test'],
            'assertHeaderNotContains' => ['assertHeaderNotContains', 'Failed asserting that \'custom header\' is not in header \'X-Cake\' (`custom header`)', '/posts/header', 'X-Cake', 'custom header'],
            'assertHeaderContainsVerbose' => ['assertHeaderContains', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', 'X-Cake', 'test'],
            'assertHeaderNotContainsVerbose' => ['assertHeaderNotContains', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', 'X-Cake', 'test'],
            'assertLayout' => ['assertLayout', 'Failed asserting that \'custom_layout\' equals layout file `' . $templateDir . 'layout' . DS . 'default.php`.', '/posts/index', 'custom_layout'],
            'assertLayoutVerbose' => ['assertLayout', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', 'custom_layout'],
            'assertRedirect' => ['assertRedirect', 'Failed asserting that \'http://localhost/\' equals content in header \'Location\' (`http://localhost/posts`).', '/posts/flashNoRender', '/'],
            'assertRedirectVerbose' => ['assertRedirect', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', '/'],
            'assertRedirectContains' => ['assertRedirectContains', 'Failed asserting that \'/posts/somewhere-else\' is in header \'Location\' (`http://localhost/posts`).', '/posts/flashNoRender', '/posts/somewhere-else'],
            'assertRedirectContainsVerbose' => ['assertRedirectContains', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', '/posts/somewhere-else'],
            'assertRedirectNotContainsVerbose' => ['assertRedirectNotContains', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', '/posts/somewhere-else'],
            'assertResponseCode' => ['assertResponseCode', 'Failed asserting that `302` matches response status code `200`.', '/posts/index', 302],
            'assertResponseContains' => ['assertResponseContains', 'Failed asserting that \'test\' is in response body.', '/posts/index', 'test'],
            'assertResponseEmpty' => ['assertResponseEmpty', 'Failed asserting that response body is empty.', '/posts/index'],
            'assertResponseEquals' => ['assertResponseEquals', 'Failed asserting that \'test\' matches response body.', '/posts/index', 'test'],
            'assertResponseEqualsVerbose' => ['assertResponseEquals', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', 'test'],
            'assertResponseError' => ['assertResponseError', 'Failed asserting that 200 is between 400 and 429.', '/posts/index'],
            'assertResponseFailure' => ['assertResponseFailure', 'Failed asserting that 200 is between 500 and 505.', '/posts/index'],
            'assertResponseNotContains' => ['assertResponseNotContains', 'Failed asserting that \'index\' is not in response body.', '/posts/index', 'index'],
            'assertResponseNotEmpty' => ['assertResponseNotEmpty', 'Failed asserting that response body is not empty.', '/posts/empty_response'],
            'assertResponseNotEquals' => ['assertResponseNotEquals', 'Failed asserting that \'posts index\' does not match response body.', '/posts/index/error', 'posts index'],
            'assertResponseNotRegExp' => ['assertResponseNotRegExp', 'Failed asserting that `/index/` PCRE pattern not found in response body.', '/posts/index/error', '/index/'],
            'assertResponseNotRegExpVerbose' => ['assertResponseNotRegExp', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', '/index/'],
            'assertResponseOk' => ['assertResponseOk', 'Failed asserting that 404 is between 200 and 204.', '/posts/missing', '/index/'],
            'assertResponseRegExp' => ['assertResponseRegExp', 'Failed asserting that `/test/` PCRE pattern found in response body.', '/posts/index/error', '/test/'],
            'assertResponseSuccess' => ['assertResponseSuccess', 'Failed asserting that 404 is between 200 and 308.', '/posts/missing'],
            'assertResponseSuccessVerbose' => ['assertResponseSuccess', 'Possibly related to Cake\Controller\Exception\MissingActionException: "Action PostsController::missing() could not be found, or is not accessible."', '/posts/missing'],

            'assertSession' => ['assertSession', 'Failed asserting that \'test\' is in session path \'Missing.path\'.', '/posts/index', 'test', 'Missing.path'],
            'assertSessionHasKey' => ['assertSessionHasKey', 'Failed asserting that \'Missing.path\' is a path present in the session.', '/posts/index', 'Missing.path'],
            'assertSessionNotHasKey' => ['assertSessionNotHasKey', 'Failed asserting that \'Flash.flash\' is not a path present in the session.', '/posts/index', 'Flash.flash'],

            'assertTemplate' => ['assertTemplate', 'Failed asserting that \'custom_template\' equals template file `' . $templateDir . 'Posts' . DS . 'index.php`.', '/posts/index', 'custom_template'],
            'assertTemplateVerbose' => ['assertTemplate', 'Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."', '/notfound', 'custom_template'],
            'assertFlashMessage' => ['assertFlashMessage', 'Failed asserting that \'missing\' is in \'flash\' message.', '/posts/index', 'missing'],
            'assertFlashMessageWithKey' => ['assertFlashMessage', 'Failed asserting that \'missing\' is in \'auth\' message.', '/posts/index', 'missing', 'auth'],
            'assertFlashMessageAt' => ['assertFlashMessageAt', 'Failed asserting that \'missing\' is in \'flash\' message #0.', '/posts/index', 0, 'missing'],
            'assertFlashMessageAtWithKey' => ['assertFlashMessageAt', 'Failed asserting that \'missing\' is in \'auth\' message #0.', '/posts/index', 0, 'missing', 'auth'],
            'assertFlashElement' => ['assertFlashElement', 'Failed asserting that \'missing\' is in \'flash\' element.', '/posts/index', 'missing'],
            'assertFlashElementWithKey' => ['assertFlashElement', 'Failed asserting that \'missing\' is in \'auth\' element.', '/posts/index', 'missing', 'auth'],
            'assertFlashElementAt' => ['assertFlashElementAt', 'Failed asserting that \'missing\' is in \'flash\' element #0.', '/posts/index', 0, 'missing'],
            'assertFlashElementAtWithKey' => ['assertFlashElementAt', 'Failed asserting that \'missing\' is in \'auth\' element #0.', '/posts/index', 0, 'missing', 'auth'],
        ];
    }

    /**
     * data provider for HTTP methods
     *
     * @return array
     */
    public function methodsProvider()
    {
        return [
            'GET' => ['get'],
            'POST' => ['post'],
            'PATCH' => ['patch'],
            'PUT' => ['put'],
            'DELETE' => ['delete'],
        ];
    }

    /**
     * Test assertCookieNotSet is creating a verbose message
     *
     * @return void
     */
    public function testAssertCookieNotSetVerbose()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."');
        $this->get('/notfound');
        $this->_response = $this->_response->withCookie(new Cookie('cookie', 'value'));
        $this->assertCookieNotSet('cookie');
    }

    /**
     * Test assertNoRedirect is creating a verbose message
     *
     * @return void
     */
    public function testAssertNoRedirectVerbose()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."');
        $this->get('/notfound');
        $this->_response = $this->_response->withHeader('Location', '/redirect');
        $this->assertNoRedirect();
    }

    /**
     * Test the header assertion generating a verbose message.
     *
     * @return void
     */
    public function testAssertHeaderVerbose()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."');
        $this->get('/notfound');
        $this->assertHeader('Etag', 'abc123');
    }

    /**
     * Test the assertResponseNotEquals generates a verbose message.
     *
     * @return void
     */
    public function testAssertResponseNotEqualsVerbose()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."');
        $this->get('/notfound');
        $this->_response = $this->_response->withStringBody('body');
        $this->assertResponseNotEquals('body');
    }

    /**
     * Test the assertResponseRegExp generates a verbose message.
     *
     * @return void
     */
    public function testAssertResponseRegExpVerbose()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Possibly related to Cake\Routing\Exception\MissingRouteException: "A route matching "/notfound" could not be found."');
        $this->get('/notfound');
        $this->_response = $this->_response->withStringBody('body');
        $this->assertResponseRegExp('/patternNotFound/');
    }

    /**
     * Test the assertion generates a verbose message for session related checks.
     *
     * @dataProvider assertionFailureSessionVerboseProvider
     * @return void
     */
    public function testAssertSessionRelatedVerboseMessages($assertMethod, ...$rest)
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Possibly related to OutOfBoundsException: "oh no!"');
        $this->get('/posts/throw_exception');
        $this->_requestSession = new Session();
        call_user_func_array([$this, $assertMethod], $rest);
    }

    /**
     * data provider for assertion verbose session related tests
     *
     * @return array
     */
    public function assertionFailureSessionVerboseProvider()
    {
        return [
            'assertFlashMessageVerbose' => ['assertFlashMessage', 'notfound'],
            'assertFlashMessageAtVerbose' => ['assertFlashMessageAt', 2, 'notfound'],
            'assertFlashElementVerbose' => ['assertFlashElement', 'notfound'],
            'assertSessionVerbose' => ['assertSession', 'notfound', 'notfound'],
        ];
    }

    /**
     * Test viewVariable not found
     *
     * @return void
     */
    public function testViewVariableNotFoundShouldReturnNull()
    {
        $this->_controller = new Controller();
        $this->assertNull($this->viewVariable('notFound'));
    }
}
