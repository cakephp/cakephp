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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Routing\DispatcherFactory;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use TestApp\Controller\RequestHandlerTestController;

/**
 * RequestHandlerComponentTest class
 */
class RequestHandlerComponentTest extends TestCase
{

    /**
     * Controller property
     *
     * @var RequestHandlerTestController
     */
    public $Controller;

    /**
     * RequestHandler property
     *
     * @var RequestHandlerComponent
     */
    public $RequestHandler;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        DispatcherFactory::add('Routing');
        DispatcherFactory::add('ControllerFactory');
        $this->_init();
    }

    /**
     * init method
     *
     * @return void
     */
    protected function _init()
    {
        $request = new Request('controller_posts/index');
        $response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $this->Controller = new RequestHandlerTestController($request, $response);
        $this->RequestHandler = $this->Controller->components()->load('RequestHandler');
        $this->request = $request;

        Router::scope('/', function ($routes) {
            $routes->extensions('json');
            $routes->fallbacks('InflectedRoute');
        });
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        DispatcherFactory::clear();
        Router::reload();
        Router::$initialized = false;
        unset($this->RequestHandler, $this->Controller);
    }

    /**
     * Test that the constructor sets the config.
     *
     * @return void
     */
    public function testConstructorConfig()
    {
        $config = [
            'viewClassMap' => ['json' => 'MyPlugin.MyJson']
        ];
        $controller = $this->getMock('Cake\Controller\Controller', ['redirect']);
        $collection = new ComponentRegistry($controller);
        $requestHandler = new RequestHandlerComponent($collection, $config);
        $this->assertEquals(['json' => 'MyPlugin.MyJson'], $requestHandler->config('viewClassMap'));
    }

    /**
     * testInitializeCallback method
     *
     * @return void
     */
    public function testInitializeCallback()
    {
        $this->assertNull($this->RequestHandler->ext);
        $this->Controller->request->params['_ext'] = 'rss';
        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('rss', $this->RequestHandler->ext);
    }

    /**
     * test that a mapped Accept-type header will set $this->ext correctly.
     *
     * @return void
     */
    public function testInitializeContentTypeSettingExt()
    {
        Router::reload();
        Router::$initialized = true;
        $this->request->env('HTTP_ACCEPT', 'application/json');

        $this->RequestHandler->ext = null;
        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('json', $this->RequestHandler->ext);
    }

    /**
     * Test that RequestHandler sets $this->ext when jQuery sends its wonky-ish headers.
     *
     * @return void
     */
    public function testInitializeContentTypeWithjQueryAccept()
    {
        Router::reload();
        Router::$initialized = true;
        $this->request->env('HTTP_ACCEPT', 'application/json, application/javascript, */*; q=0.01');
        $this->request->env('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->RequestHandler->ext = null;
        Router::extensions('json', false);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('json', $this->RequestHandler->ext);
    }

    /**
     * Test that RequestHandler does not set extension to csv for text/plain mimetype
     *
     * @return void
     */
    public function testInitializeContentTypeWithjQueryTextPlainAccept()
    {
        Router::reload();
        Router::$initialized = true;
        $this->request->env('HTTP_ACCEPT', 'text/plain, */*; q=0.01');

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertNull($this->RequestHandler->ext);
    }

    /**
     * Test that RequestHandler sets $this->ext when jQuery sends its wonky-ish headers
     * and the application is configured to handle multiple extensions
     *
     * @return void
     */
    public function testInitializeContentTypeWithjQueryAcceptAndMultiplesExtensions()
    {
        Router::reload();
        Router::$initialized = true;
        $this->request->env('HTTP_ACCEPT', 'application/json, application/javascript, */*; q=0.01');
        $this->RequestHandler->ext = null;
        Router::extensions(['rss', 'json'], false);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('json', $this->RequestHandler->ext);
    }

    /**
     * Test that RequestHandler does not set $this->ext when multiple accepts are sent.
     *
     * @return void
     */
    public function testInitializeNoContentTypeWithSingleAccept()
    {
        Router::reload();
        Router::$initialized = true;
        $_SERVER['HTTP_ACCEPT'] = 'application/json, text/html, */*; q=0.01';
        $this->assertNull($this->RequestHandler->ext);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertNull($this->RequestHandler->ext);
    }

    /**
     * Test that ext is set to the first listed extension with multiple accepted
     * content types.
     * Having multiple types accepted with same weight, means the client lets the
     * server choose the returned content type.
     *
     * @return void
     */
    public function testInitializeNoContentTypeWithMultipleAcceptedTypes()
    {
        $this->request->env(
            'HTTP_ACCEPT',
            'application/json, application/javascript, application/xml, */*; q=0.01'
        );
        $this->RequestHandler->ext = null;
        Router::extensions(['xml', 'json'], false);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('xml', $this->RequestHandler->ext);

        $this->RequestHandler->ext = null;
        Router::extensions(['json', 'xml'], false);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('json', $this->RequestHandler->ext);
    }

    /**
     * Test that ext is set to type with highest weight
     *
     * @return void
     */
    public function testInitializeContentTypeWithMultipleAcceptedTypes()
    {
        Router::reload();
        Router::$initialized = true;
        $this->request->env(
            'HTTP_ACCEPT',
            'text/csv;q=1.0, application/json;q=0.8, application/xml;q=0.7'
        );
        $this->RequestHandler->ext = null;

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('json', $this->RequestHandler->ext);
    }

    /**
     * Test that ext is not set with confusing android accepts headers.
     *
     * @return void
     */
    public function testInitializeAmbiguousAndroidAccepts()
    {
        Router::reload();
        Router::$initialized = true;
        $this->request->env(
            'HTTP_ACCEPT',
            'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5'
        );
        $this->RequestHandler->ext = null;

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertNull($this->RequestHandler->ext);
    }

    /**
     * Test that the headers sent by firefox are not treated as XML requests.
     *
     * @return void
     */
    public function testInititalizeFirefoxHeaderNotXml()
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;image/png,image/jpeg,image/*;q=0.9,*/*;q=0.8';
        Router::extensions(['xml', 'json'], false);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertNull($this->RequestHandler->ext);
    }

    /**
     * Test that a type mismatch doesn't incorrectly set the ext
     *
     * @return void
     */
    public function testInitializeContentTypeAndExtensionMismatch()
    {
        $this->assertNull($this->RequestHandler->ext);
        $extensions = Router::extensions();
        Router::extensions('xml', false);

        $this->Controller->request = $this->getMock('Cake\Network\Request', ['accepts']);
        $this->Controller->request->expects($this->any())
            ->method('accepts')
            ->will($this->returnValue(['application/json']));

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertNull($this->RequestHandler->ext);

        call_user_func_array(['Cake\Routing\Router', 'extensions'], [$extensions, false]);
    }

    /**
     * testViewClassMap method
     *
     * @return void
     */
    public function testViewClassMap()
    {
        $restore = error_reporting(E_ALL & ~E_USER_DEPRECATED);

        $this->RequestHandler->config(['viewClassMap' => ['json' => 'CustomJson']]);
        $this->RequestHandler->initialize([]);
        $result = $this->RequestHandler->viewClassMap();
        $expected = [
            'json' => 'CustomJson',
            'xml' => 'Xml',
            'ajax' => 'Ajax'
        ];
        $this->assertEquals($expected, $result);

        $result = $this->RequestHandler->viewClassMap('xls', 'Excel.Excel');
        $expected = [
            'json' => 'CustomJson',
            'xml' => 'Xml',
            'ajax' => 'Ajax',
            'xls' => 'Excel.Excel'
        ];
        $this->assertEquals($expected, $result);

        $this->RequestHandler->renderAs($this->Controller, 'json');
        $this->assertEquals('TestApp\View\CustomJsonView', $this->Controller->viewClass);
        error_reporting($restore);
    }

    /**
     * Verify that isAjax is set on the request params for ajax requests
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testIsAjaxParams()
    {
        $this->request->env('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->Controller->beforeFilter($event);
        $this->RequestHandler->startup($event);
        $this->assertEquals(true, $this->Controller->request->params['isAjax']);
    }

    /**
     * testAutoAjaxLayout method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAutoAjaxLayout()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->request->env('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $this->assertEquals($this->Controller->viewClass, 'Cake\View\AjaxView');
        $view = $this->Controller->createView();
        $this->assertEquals('ajax', $view->layout);

        $this->_init();
        $this->Controller->request->params['_ext'] = 'js';
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $this->assertNotEquals($this->Controller->viewClass, 'Cake\View\AjaxView');
    }

    /**
     * test custom JsonView class is loaded and correct.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testJsonViewLoaded()
    {
        Router::extensions(['json', 'xml', 'ajax'], false);
        $this->Controller->request->params['_ext'] = 'json';
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $this->assertEquals('Cake\View\JsonView', $this->Controller->viewClass);
        $view = $this->Controller->createView();
        $this->assertEquals('json', $view->layoutPath);
        $this->assertEquals('json', $view->subDir);
    }

    /**
     * test custom XmlView class is loaded and correct.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testXmlViewLoaded()
    {
        Router::extensions(['json', 'xml', 'ajax'], false);
        $this->Controller->request->params['_ext'] = 'xml';
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $this->assertEquals('Cake\View\XmlView', $this->Controller->viewClass);
        $view = $this->Controller->createView();
        $this->assertEquals('xml', $view->layoutPath);
        $this->assertEquals('xml', $view->subDir);
    }

    /**
     * test custom AjaxView class is loaded and correct.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAjaxViewLoaded()
    {
        Router::extensions(['json', 'xml', 'ajax'], false);
        $this->Controller->request->params['_ext'] = 'ajax';
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $this->assertEquals('Cake\View\AjaxView', $this->Controller->viewClass);
        $view = $this->Controller->createView();
        $this->assertEquals('ajax', $view->layout);
    }

    /**
     * test configured extension but no view class set.
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testNoViewClassExtension()
    {
        Router::extensions(['json', 'xml', 'ajax', 'csv'], false);
        $this->Controller->request->params['_ext'] = 'csv';
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $this->Controller->eventManager()->on('Controller.beforeRender', function () {
            return $this->Controller->response;
        });
        $this->Controller->render();
        $this->assertEquals('RequestHandlerTest' . DS . 'csv', $this->Controller->viewBuilder()->templatePath());
        $this->assertEquals('csv', $this->Controller->viewBuilder()->layoutPath());
    }

    /**
     * testStartupCallback method
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testStartupCallback()
    {
        $event = new Event('Controller.beforeRender', $this->Controller);
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['CONTENT_TYPE'] = 'application/xml';
        $this->Controller->request = $this->getMock('Cake\Network\Request', ['_readInput']);
        $this->RequestHandler->beforeRender($event);
        $this->assertTrue(is_array($this->Controller->request->data));
        $this->assertFalse(is_object($this->Controller->request->data));
    }

    /**
     * testStartupCallback with charset.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupCallbackCharset()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['CONTENT_TYPE'] = 'application/xml; charset=UTF-8';
        $this->Controller->request = $this->getMock('Cake\Network\Request', ['_readInput']);
        $this->RequestHandler->startup($event);
        $this->assertTrue(is_array($this->Controller->request->data));
        $this->assertFalse(is_object($this->Controller->request->data));
    }

    /**
     * Test that processing data results in an array.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupProcessData()
    {
        $this->Controller->request = $this->getMock('Cake\Network\Request', ['_readInput']);
        $this->Controller->request->expects($this->at(0))
            ->method('_readInput')
            ->will($this->returnValue(''));
        $this->Controller->request->expects($this->at(1))
            ->method('_readInput')
            ->will($this->returnValue('"invalid"'));
        $this->Controller->request->expects($this->at(2))
            ->method('_readInput')
            ->will($this->returnValue('{"valid":true}'));

        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->Controller->request->env('CONTENT_TYPE', 'application/json');

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->assertEquals([], $this->Controller->request->data);

        $this->RequestHandler->startup($event);
        $this->assertEquals(['invalid'], $this->Controller->request->data);

        $this->RequestHandler->startup($event);
        $this->assertEquals(['valid' => true], $this->Controller->request->data);
    }

    /**
     * Test that file handles are ignored as XML data.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupIgnoreFileAsXml()
    {
        $this->Controller->request = $this->getMock('Cake\Network\Request', ['_readInput']);
        $this->Controller->request->expects($this->any())
            ->method('_readInput')
            ->will($this->returnValue('/dev/random'));

        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->Controller->request->env('CONTENT_TYPE', 'application/xml');

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->assertEquals([], $this->Controller->request->data);
    }

    /**
     * Test mapping a new type and having startup process it.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupCustomTypeProcess()
    {
        $restore = error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $this->Controller->request = $this->getMock('Cake\Network\Request', ['_readInput']);
        $this->Controller->request->expects($this->once())
            ->method('_readInput')
            ->will($this->returnValue('"A","csv","string"'));
        $this->RequestHandler->addInputType('csv', ['str_getcsv']);
        $this->Controller->request->env('REQUEST_METHOD', 'POST');
        $this->Controller->request->env('CONTENT_TYPE', 'text/csv');
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $expected = [
            'A', 'csv', 'string'
        ];
        $this->assertEquals($expected, $this->Controller->request->data);
        error_reporting($restore);
    }

    /**
     * testNonAjaxRedirect method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testNonAjaxRedirect()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $this->assertNull($this->RequestHandler->beforeRedirect($event, '/', $this->Controller->response));
    }

    /**
     * test that redirects with ajax and no URL don't do anything.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAjaxRedirectWithNoUrl()
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->response = $this->getMock('Cake\Network\Response');

        $this->Controller->response->expects($this->never())
            ->method('body');

        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $this->assertNull($this->RequestHandler->beforeRedirect($event, null, $this->Controller->response));
    }

    /**
     * testRenderAs method
     *
     * @return void
     */
    public function testRenderAs()
    {
        $this->assertFalse(in_array('Rss', $this->Controller->helpers));
        $this->RequestHandler->renderAs($this->Controller, 'rss');
        $this->assertTrue(in_array('Rss', $this->Controller->helpers));

        $this->Controller->viewBuilder()->templatePath('request_handler_test\\rss');
        $this->RequestHandler->renderAs($this->Controller, 'js');
        $this->assertEquals('request_handler_test' . DS . 'js', $this->Controller->viewBuilder()->templatePath());
    }

    /**
     * test that attachment headers work with renderAs
     *
     * @return void
     */
    public function testRenderAsWithAttachment()
    {
        $this->RequestHandler->request = $this->getMock('Cake\Network\Request', ['parseAccept']);
        $this->RequestHandler->request->expects($this->any())
            ->method('parseAccept')
            ->will($this->returnValue(['1.0' => ['application/xml']]));

        $this->RequestHandler->response = $this->getMock('Cake\Network\Response', ['type', 'download', 'charset']);
        $this->RequestHandler->response->expects($this->at(0))
            ->method('type')
            ->with('application/xml');
        $this->RequestHandler->response->expects($this->at(1))
            ->method('charset')
            ->with('UTF-8');
        $this->RequestHandler->response->expects($this->at(2))
            ->method('download')
            ->with('myfile.xml');

        $this->RequestHandler->renderAs($this->Controller, 'xml', ['attachment' => 'myfile.xml']);

        $this->assertEquals('Cake\View\XmlView', $this->Controller->viewClass);
    }

    /**
     * test that respondAs works as expected.
     *
     * @return void
     */
    public function testRespondAs()
    {
        $this->RequestHandler->response = $this->getMock('Cake\Network\Response', ['type']);
        $this->RequestHandler->response->expects($this->at(0))->method('type')
            ->with('application/json');
        $this->RequestHandler->response->expects($this->at(1))->method('type')
            ->with('text/xml');

        $result = $this->RequestHandler->respondAs('json');
        $this->assertTrue($result);
        $result = $this->RequestHandler->respondAs('text/xml');
        $this->assertTrue($result);
    }

    /**
     * test that attachment headers work with respondAs
     *
     * @return void
     */
    public function testRespondAsWithAttachment()
    {
        $this->RequestHandler = $this->getMock(
            'Cake\Controller\Component\RequestHandlerComponent',
            ['_header'],
            [$this->Controller->components()]
        );
        $this->RequestHandler->response = $this->getMock('Cake\Network\Response', ['type', 'download']);
        $this->RequestHandler->request = $this->getMock('Cake\Network\Request', ['parseAccept']);

        $this->RequestHandler->request->expects($this->once())
            ->method('parseAccept')
            ->will($this->returnValue(['1.0' => ['application/xml']]));

        $this->RequestHandler->response->expects($this->once())->method('download')
            ->with('myfile.xml');
        $this->RequestHandler->response->expects($this->once())->method('type')
            ->with('application/xml');

        $result = $this->RequestHandler->respondAs('xml', ['attachment' => 'myfile.xml']);
        $this->assertTrue($result);
    }

    /**
     * test that calling renderAs() more than once continues to work.
     *
     * @link #6466
     * @return void
     */
    public function testRenderAsCalledTwice()
    {
        $this->Controller->eventManager()->on('Controller.beforeRender', function (\Cake\Event\Event $e) {
            return $e->subject()->response;
        });
        $this->Controller->render();

        $this->RequestHandler->renderAs($this->Controller, 'print');
        $this->assertEquals('RequestHandlerTest' . DS . 'print', $this->Controller->viewBuilder()->templatePath());
        $this->assertEquals('print', $this->Controller->viewBuilder()->layoutPath());

        $this->RequestHandler->renderAs($this->Controller, 'js');
        $this->assertEquals('RequestHandlerTest' . DS . 'js', $this->Controller->viewBuilder()->templatePath());
        $this->assertEquals('js', $this->Controller->viewBuilder()->layoutPath());
    }

    /**
     * testRequestContentTypes method
     *
     * @return void
     */
    public function testRequestContentTypes()
    {
        $this->request->env('REQUEST_METHOD', 'GET');
        $this->assertNull($this->RequestHandler->requestedWith());

        $this->request->env('REQUEST_METHOD', 'POST');
        $this->request->env('CONTENT_TYPE', 'application/json');
        $this->assertEquals('json', $this->RequestHandler->requestedWith());

        $result = $this->RequestHandler->requestedWith(['json', 'xml']);
        $this->assertEquals('json', $result);

        $result = $this->RequestHandler->requestedWith(['rss', 'atom']);
        $this->assertFalse($result);

        $this->request->env('REQUEST_METHOD', 'PATCH');
        $this->assertEquals('json', $this->RequestHandler->requestedWith());

        $this->request->env('REQUEST_METHOD', 'DELETE');
        $this->assertEquals('json', $this->RequestHandler->requestedWith());

        $this->request->env('REQUEST_METHOD', 'POST');
        $this->request->env('CONTENT_TYPE', '');
        $this->request->env('HTTP_CONTENT_TYPE', 'application/json');

        $result = $this->RequestHandler->requestedWith(['json', 'xml']);
        $this->assertEquals('json', $result);

        $result = $this->RequestHandler->requestedWith(['rss', 'atom']);
        $this->assertFalse($result);

        $this->request->env('HTTP_ACCEPT', 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*');
        $this->assertTrue($this->RequestHandler->isXml());
        $this->assertFalse($this->RequestHandler->isAtom());
        $this->assertFalse($this->RequestHandler->isRSS());

        $this->request->env('HTTP_ACCEPT', 'application/atom+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*');
        $this->assertTrue($this->RequestHandler->isAtom());
        $this->assertFalse($this->RequestHandler->isRSS());

        $this->request->env('HTTP_ACCEPT', 'application/rss+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*');
        $this->assertFalse($this->RequestHandler->isAtom());
        $this->assertTrue($this->RequestHandler->isRSS());

        $this->assertFalse($this->RequestHandler->isWap());
        $this->request->env('HTTP_ACCEPT', 'text/vnd.wap.wml,text/html,text/plain,image/png,*/*');
        $this->assertTrue($this->RequestHandler->isWap());
    }

    /**
     * testResponseContentType method
     *
     * @return void
     */
    public function testResponseContentType()
    {
        $this->assertEquals('html', $this->RequestHandler->responseType());
        $this->assertTrue($this->RequestHandler->respondAs('atom'));
        $this->assertEquals('atom', $this->RequestHandler->responseType());
    }

    /**
     * testMobileDeviceDetection method
     *
     * @return void
     */
    public function testMobileDeviceDetection()
    {
        $request = $this->getMock('Cake\Network\Request', ['is']);
        $request->expects($this->once())->method('is')
            ->with('mobile')
            ->will($this->returnValue(true));

        $this->RequestHandler->request = $request;
        $this->assertTrue($this->RequestHandler->isMobile());
    }

    /**
     * test that map alias converts aliases to content types.
     *
     * @return void
     */
    public function testMapAlias()
    {
        $result = $this->RequestHandler->mapAlias('xml');
        $this->assertEquals('application/xml', $result);

        $result = $this->RequestHandler->mapAlias('text/html');
        $this->assertNull($result);

        $result = $this->RequestHandler->mapAlias('wap');
        $this->assertEquals('text/vnd.wap.wml', $result);

        $result = $this->RequestHandler->mapAlias(['xml', 'js', 'json']);
        $expected = ['application/xml', 'application/javascript', 'application/json'];
        $this->assertEquals($expected, $result);
    }

    /**
     * test accepts() on the component
     *
     * @return void
     */
    public function testAccepts()
    {
        $this->request->env('HTTP_ACCEPT', 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5');
        $this->assertTrue($this->RequestHandler->accepts(['js', 'xml', 'html']));
        $this->assertFalse($this->RequestHandler->accepts(['gif', 'jpeg', 'foo']));

        $this->request->env('HTTP_ACCEPT', '*/*;q=0.5');
        $this->assertFalse($this->RequestHandler->accepts('rss'));
    }

    /**
     * test accepts and prefers methods.
     *
     * @return void
     */
    public function testPrefers()
    {
        $this->request->env(
            'HTTP_ACCEPT',
            'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*'
        );
        $this->assertNotEquals('rss', $this->RequestHandler->prefers());
        $this->RequestHandler->ext = 'rss';
        $this->assertEquals('rss', $this->RequestHandler->prefers());
        $this->assertFalse($this->RequestHandler->prefers('xml'));
        $this->assertEquals('xml', $this->RequestHandler->prefers(['js', 'xml', 'xhtml']));
        $this->assertFalse($this->RequestHandler->prefers(['red', 'blue']));
        $this->assertEquals('xhtml', $this->RequestHandler->prefers(['js', 'json', 'xhtml']));
        $this->assertTrue($this->RequestHandler->prefers(['rss']), 'Should return true if input matches ext.');
        $this->assertFalse($this->RequestHandler->prefers(['html']), 'No match with ext, return false.');

        $this->_init();
        $this->request->env(
            'HTTP_ACCEPT',
            'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5'
        );
        $this->assertEquals('xml', $this->RequestHandler->prefers());

        $this->request->env('HTTP_ACCEPT', '*/*;q=0.5');
        $this->assertEquals('html', $this->RequestHandler->prefers());
        $this->assertFalse($this->RequestHandler->prefers('rss'));
    }

    /**
     * test that AJAX requests involving redirects trigger requestAction instead.
     *
     * @return void
     * @triggers Controller.beforeRedirect $this->Controller
     */
    public function testAjaxRedirectAsRequestAction()
    {
        Configure::write('App.namespace', 'TestApp');
        Router::connect('/:controller/:action');
        $event = new Event('Controller.beforeRedirect', $this->Controller);

        $this->Controller->RequestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->Controller->request = $this->getMock('Cake\Network\Request', ['is']);
        $this->Controller->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $this->Controller->RequestHandler->request = $this->Controller->request;
        $this->Controller->RequestHandler->response = $this->Controller->response;
        $this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));

        $response = $this->Controller->RequestHandler->beforeRedirect(
            $event,
            ['controller' => 'RequestHandlerTest', 'action' => 'destination'],
            $this->Controller->response
        );
        $this->assertRegExp('/posts index/', $response->body(), 'RequestAction redirect failed.');
    }

    /**
     * Test that AJAX requests involving redirects handle querystrings
     *
     * @return void
     * @triggers Controller.beforeRedirect $this->Controller
     */
    public function testAjaxRedirectAsRequestActionWithQueryString()
    {
        Configure::write('App.namespace', 'TestApp');
        Router::connect('/:controller/:action');
        $event = new Event('Controller.beforeRedirect', $this->Controller);

        $this->Controller->RequestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->Controller->request = $this->getMock('Cake\Network\Request', ['is']);
        $this->Controller->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $this->Controller->RequestHandler->request = $this->Controller->request;
        $this->Controller->RequestHandler->response = $this->Controller->response;
        $this->Controller->request->expects($this->any())
            ->method('is')
            ->with('ajax')
            ->will($this->returnValue(true));

        $response = $this->Controller->RequestHandler->beforeRedirect(
            $event,
            '/request_action/params_pass?a=b&x=y?ish',
            $this->Controller->response
        );
        $data = json_decode($response, true);
        $this->assertEquals('/request_action/params_pass', $data['here']);

        $response = $this->Controller->RequestHandler->beforeRedirect(
            $event,
            '/request_action/query_pass?a=b&x=y?ish',
            $this->Controller->response
        );
        $data = json_decode($response, true);
        $this->assertEquals('y?ish', $data['x']);
    }

    /**
     * Test that AJAX requests involving redirects handle cookie data
     *
     * @return void
     * @triggers Controller.beforeRedirect $this->Controller
     */
    public function testAjaxRedirectAsRequestActionWithCookieData()
    {
        Configure::write('App.namespace', 'TestApp');
        Router::connect('/:controller/:action');
        $event = new Event('Controller.beforeRedirect', $this->Controller);

        $this->Controller->RequestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->Controller->request = $this->getMock('Cake\Network\Request', ['is']);
        $this->Controller->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $this->Controller->RequestHandler->request = $this->Controller->request;
        $this->Controller->RequestHandler->response = $this->Controller->response;
        $this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));

        $cookies = [
            'foo' => 'bar'
        ];
        $this->Controller->request->cookies = $cookies;

        $response = $this->Controller->RequestHandler->beforeRedirect(
            $event,
            '/request_action/cookie_pass',
            $this->Controller->response
        );
        $data = json_decode($response, true);
        $this->assertEquals($cookies, $data);
    }

    /**
     * Tests that AJAX requests involving redirects don't let the status code bleed through.
     *
     * @return void
     * @triggers Controller.beforeRedirect $this->Controller
     */
    public function testAjaxRedirectAsRequestActionStatusCode()
    {
        Configure::write('App.namespace', 'TestApp');
        Router::connect('/:controller/:action');
        $event = new Event('Controller.beforeRedirect', $this->Controller);

        $this->Controller->RequestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->Controller->request = $this->getMock('Cake\Network\Request', ['is']);
        $this->Controller->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $this->Controller->response->statusCode(302);
        $this->Controller->RequestHandler->request = $this->Controller->request;
        $this->Controller->RequestHandler->response = $this->Controller->response;
        $this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));

        $response = $this->Controller->RequestHandler->beforeRedirect(
            $event,
            ['controller' => 'RequestHandlerTest', 'action' => 'destination'],
            $this->Controller->response
        );
        $this->assertRegExp('/posts index/', $response->body(), 'RequestAction redirect failed.');
        $this->assertSame(200, $response->statusCode());
    }

    /**
     * test that ajax requests involving redirects don't force no layout
     * this would cause the ajax layout to not be rendered.
     *
     * @return void
     * @triggers Controller.beforeRedirect $this->Controller
     */
    public function testAjaxRedirectAsRequestActionStillRenderingLayout()
    {
        Configure::write('App.namespace', 'TestApp');
        Router::connect('/:controller/:action');
        $event = new Event('Controller.beforeRedirect', $this->Controller);

        $this->Controller->RequestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->Controller->request = $this->getMock('Cake\Network\Request', ['is']);
        $this->Controller->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $this->Controller->RequestHandler->request = $this->Controller->request;
        $this->Controller->RequestHandler->response = $this->Controller->response;
        $this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));

        $response = $this->Controller->RequestHandler->beforeRedirect(
            $event,
            ['controller' => 'RequestHandlerTest', 'action' => 'ajax2_layout'],
            $this->Controller->response
        );
        $this->assertRegExp('/posts index/', $response->body(), 'RequestAction redirect failed.');
        $this->assertRegExp('/Ajax!/', $response->body(), 'Layout was not rendered.');
    }

    /**
     * test that the beforeRedirect callback properly converts
     * array URLs into their correct string ones, and adds base => false so
     * the correct URLs are generated.
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testBeforeRedirectCallbackWithArrayUrl()
    {
        Configure::write('App.namespace', 'TestApp');
        Router::connect('/:controller/:action/*');
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $event = new Event('Controller.beforeRender', $this->Controller);

        Router::setRequestInfo([
            ['plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => []],
            ['base' => '', 'here' => '/accounts/', 'webroot' => '/']
        ]);

        $RequestHandler = new RequestHandlerComponent($this->Controller->components());
        $RequestHandler->request = new Request('posts/index');
        $RequestHandler->response = $this->Controller->response;

        ob_start();
        $RequestHandler->beforeRedirect(
            $event,
            ['controller' => 'RequestHandlerTest', 'action' => 'param_method', 'first', 'second'],
            $this->Controller->response
        );
        $result = ob_get_clean();
        $this->assertEquals('one: first two: second', $result);
    }

    /**
     * testAddInputTypeException method
     *
     * @expectedException \Cake\Core\Exception\Exception
     * @return void
     */
    public function testAddInputTypeException()
    {
        $restore = error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $this->RequestHandler->addInputType('csv', ['I am not callable']);
        error_reporting($restore);
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testCheckNotModifiedByEtagStar()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '*';
        $event = new Event('Controller.beforeRender', $this->Controller);
        $RequestHandler = new RequestHandlerComponent($this->Controller->components());
        $RequestHandler->response = $this->getMock('Cake\Network\Response', ['notModified', 'stop']);
        $RequestHandler->response->etag('something');
        $RequestHandler->response->expects($this->once())->method('notModified');
        $this->assertFalse($RequestHandler->beforeRender($event));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender
     */
    public function testCheckNotModifiedByEtagExact()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
        $event = new Event('Controller.beforeRender');
        $RequestHandler = new RequestHandlerComponent($this->Controller->components());
        $RequestHandler->response = $this->getMock('Cake\Network\Response', ['notModified', 'stop']);
        $RequestHandler->response->etag('something', true);
        $RequestHandler->response->expects($this->once())->method('notModified');
        $this->assertFalse($RequestHandler->beforeRender($event));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testCheckNotModifiedByEtagAndTime()
    {
        $_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
        $event = new Event('Controller.beforeRender', $this->Controller);
        $RequestHandler = new RequestHandlerComponent($this->Controller->components());
        $RequestHandler->response = $this->getMock('Cake\Network\Response', ['notModified', 'stop']);
        $RequestHandler->response->etag('something', true);
        $RequestHandler->response->modified('2012-01-01 00:00:00');
        $RequestHandler->response->expects($this->once())->method('notModified');
        $this->assertFalse($RequestHandler->beforeRender($event));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testCheckNotModifiedNoInfo()
    {
        $event = new Event('Controller.beforeRender', $this->Controller);
        $RequestHandler = new RequestHandlerComponent($this->Controller->components());
        $RequestHandler->response = $this->getMock('Cake\Network\Response', ['notModified', 'stop']);
        $RequestHandler->response->expects($this->never())->method('notModified');
        $this->assertNull($RequestHandler->beforeRender($event));
    }

    /**
     * Test default options in construction
     *
     * @return void
     */
    public function testConstructDefaultOptions()
    {
        $requestHandler = new RequestHandlerComponent($this->Controller->components());
        $viewClass = $requestHandler->config('viewClassMap');
        $expected = [
            'json' => 'Json',
            'xml' => 'Xml',
            'ajax' => 'Ajax',
        ];
        $this->assertEquals($expected, $viewClass);

        $inputs = $requestHandler->config('inputTypeMap');
        $this->assertArrayHasKey('json', $inputs);
        $this->assertArrayHasKey('xml', $inputs);
    }

    /**
     * Test options in constructor replace defaults
     *
     * @return void
     */
    public function testConstructReplaceOptions()
    {
        $requestHandler = new RequestHandlerComponent(
            $this->Controller->components(),
            [
                'viewClassMap' => ['json' => 'Json'],
                'inputTypeMap' => ['json' => ['json_decode', true]]
            ]
        );
        $viewClass = $requestHandler->config('viewClassMap');
        $expected = [
            'json' => 'Json',
        ];
        $this->assertEquals($expected, $viewClass);

        $inputs = $requestHandler->config('inputTypeMap');
        $this->assertArrayHasKey('json', $inputs);
        $this->assertCount(1, $inputs);
    }
}
