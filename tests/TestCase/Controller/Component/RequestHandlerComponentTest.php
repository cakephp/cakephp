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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\DispatcherFactory;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\AjaxView;
use Cake\View\JsonView;
use Cake\View\XmlView;
use TestApp\Controller\RequestHandlerTestController;
use Zend\Diactoros\Stream;

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
     * @var ServerRequest
     */
    public $request;

    /**
     * Backup of $_SERVER
     *
     * @var array
     */
    protected $server = [];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->server = $_SERVER;
        static::setAppNamespace();
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
        $request = new ServerRequest('controller_posts/index');
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['_sendHeader', 'stop'])
            ->getMock();
        $this->Controller = new RequestHandlerTestController($request, $response);
        $this->RequestHandler = $this->Controller->components()->load('RequestHandler');
        $this->request = $request;

        Router::scope('/', function ($routes) {
            $routes->setExtensions('json');
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
        $_SERVER = $this->server;
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
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $collection = new ComponentRegistry($controller);
        $requestHandler = new RequestHandlerComponent($collection, $config);
        $this->assertEquals(['json' => 'MyPlugin.MyJson'], $requestHandler->getConfig('viewClassMap'));
    }

    /**
     * testInitializeCallback method
     *
     * @return void
     */
    public function testInitializeCallback()
    {
        $this->assertNull($this->RequestHandler->ext);
        $this->Controller->request = $this->Controller->request->withParam('_ext', 'rss');
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
        $this->Controller->request = $this->request->withHeader('Accept', 'application/json');

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
        $this->Controller->request = $this->request
            ->withHeader('Accept', 'application/json, application/javascript, */*; q=0.01')
            ->withHeader('X-Requested-With', 'XMLHttpRequest');
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
        $this->Controller->request = $this->request->withHeader('Accept', 'text/plain, */*; q=0.01');

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
        $this->Controller->request = $this->request->withHeader('Accept', 'application/json, application/javascript, */*; q=0.01');
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
        $this->Controller->request = $this->request->withHeader(
            'Accept',
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
        $this->Controller->request = $this->request->withHeader(
            'Accept',
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
        $this->request = $this->request->withEnv(
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

        $this->Controller->request = $this->getMockBuilder('Cake\Http\ServerRequest')
            ->setMethods(['accepts'])
            ->getMock();
        $this->Controller->request->expects($this->any())
            ->method('accepts')
            ->will($this->returnValue(['application/json']));

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertNull($this->RequestHandler->ext);

        Router::extensions($extensions, false);
    }

    /**
     * testViewClassMap
     *
     * @return void
     */
    public function testViewClassMap()
    {
        $this->RequestHandler->setConfig(['viewClassMap' => ['json' => 'CustomJson']]);
        $result = $this->RequestHandler->getConfig('viewClassMap');
        $expected = [
            'json' => 'CustomJson',
            'xml' => 'Xml',
            'ajax' => 'Ajax'
        ];
        $this->assertEquals($expected, $result);
        $this->RequestHandler->setConfig(['viewClassMap' => ['xls' => 'Excel.Excel']]);
        $result = $this->RequestHandler->getConfig('viewClassMap');
        $expected = [
            'json' => 'CustomJson',
            'xml' => 'Xml',
            'ajax' => 'Ajax',
            'xls' => 'Excel.Excel'
        ];
        $this->assertEquals($expected, $result);

        $this->RequestHandler->renderAs($this->Controller, 'json');
        $this->assertEquals('TestApp\View\CustomJsonView', $this->Controller->viewBuilder()->getClassName());
    }

    /**
     * test addInputType method
     *
     * @group deprecated
     * @return void
     */
    public function testDeprecatedAddInputType()
    {
        $this->deprecated(function () {
            $this->RequestHandler->addInputType('csv', ['str_getcsv']);
            $result = $this->RequestHandler->getConfig('inputTypeMap');
            $this->assertArrayHasKey('csv', $result);
        });
    }

    /**
     * testViewClassMap method
     *
     * @group deprecated
     * @return void
     */
    public function testViewClassMapMethod()
    {
        $this->deprecated(function () {
            $this->RequestHandler->setConfig(['viewClassMap' => ['json' => 'CustomJson']]);
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
            $this->assertEquals('TestApp\View\CustomJsonView', $this->Controller->viewBuilder()->getClassName());
        });
    }

    /**
     * Verify that isAjax is set on the request params for ajax requests
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testIsAjaxParams()
    {
        $this->Controller->request = $this->request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->Controller->beforeFilter($event);
        $this->RequestHandler->startup($event);
        $this->assertTrue($this->Controller->request->getParam('isAjax'));
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
        $this->Controller->request = $this->request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);

        $view = $this->Controller->createView();
        $this->assertInstanceOf(AjaxView::class, $view);
        $this->assertEquals('ajax', $view->getLayout());

        $this->_init();
        $this->Controller->request = $this->Controller->request->withParam('_ext', 'js');
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $this->assertNotEquals(AjaxView::class, $this->Controller->viewBuilder()->getClassName());
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
        $this->Controller->request = $this->Controller->request->withParam('_ext', 'json');
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $view = $this->Controller->createView();
        $this->assertInstanceOf(JsonView::class, $view);
        $this->assertEquals('json', $view->getLayoutPath());
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
        $this->Controller->request = $this->Controller->request->withParam('_ext', 'xml');
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $view = $this->Controller->createView();
        $this->assertInstanceOf(XmlView::class, $view);
        $this->assertEquals('xml', $view->getLayoutPath());
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
        $this->Controller->request = $this->Controller->request->withParam('_ext', 'ajax');
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $view = $this->Controller->createView();
        $this->assertInstanceOf(AjaxView::class, $view);
        $this->assertEquals('ajax', $view->getLayout());
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
        $this->Controller->request = $this->Controller->request->withParam('_ext', 'csv');
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->startup($event);
        $this->Controller->getEventManager()->on('Controller.beforeRender', function () {
            return $this->Controller->response;
        });
        $this->Controller->render();
        $this->assertEquals('RequestHandlerTest' . DS . 'csv', $this->Controller->viewBuilder()->getTemplatePath());
        $this->assertEquals('csv', $this->Controller->viewBuilder()->getLayoutPath());
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
        $this->Controller->request = new ServerRequest();
        $this->RequestHandler->beforeRender($event);
        $this->assertInternalType('array', $this->Controller->request->getData());
        $this->assertNotInternalType('object', $this->Controller->request->getData());
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
        $this->Controller->request = new ServerRequest();
        $this->RequestHandler->startup($event);
        $this->assertInternalType('array', $this->Controller->request->getData());
        $this->assertNotInternalType('object', $this->Controller->request->getData());
    }

    /**
     * Test that processing data results in an array.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupProcessDataInvalid()
    {
        $this->Controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json'
            ]
        ]);

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->assertEquals([], $this->Controller->request->getData());

        $stream = new Stream('php://memory', 'w');
        $stream->write('"invalid"');
        $this->Controller->request = $this->Controller->request->withBody($stream);
        $this->RequestHandler->startup($event);
        $this->assertEquals(['invalid'], $this->Controller->request->getData());
    }

    /**
     * Test that processing data results in an array.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupProcessData()
    {
        $this->Controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json'
            ]
        ]);

        $stream = new Stream('php://memory', 'w');
        $stream->write('{"valid":true}');
        $this->Controller->request = $this->Controller->request->withBody($stream);
        $event = new Event('Controller.startup', $this->Controller);

        $this->RequestHandler->startup($event);
        $this->assertEquals(['valid' => true], $this->Controller->request->getData());
    }

    /**
     * Test that file handles are ignored as XML data.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupIgnoreFileAsXml()
    {
        $this->Controller->request = new ServerRequest([
            'input' => '/dev/random',
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/xml'
            ]
        ]);

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->assertEquals([], $this->Controller->request->getData());
    }

    /**
     * Test that input xml is parsed
     *
     * @return void
     */
    public function testStartupConvertXmlDataWrapper()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<data>
<article id="1" title="first"></article>
</data>
XML;
        $this->Controller->request = new ServerRequest(['input' => $xml]);
        $this->Controller->request = $this->Controller->request
            ->withEnv('REQUEST_METHOD', 'POST')
            ->withEnv('CONTENT_TYPE', 'application/xml');

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $expected = [
            'data' => [
                'article' => [
                    '@id' => 1,
                    '@title' => 'first'
                ]
            ]
        ];
        $this->assertEquals($expected, $this->Controller->request->getData());
    }

    /**
     * Test that input xml is parsed
     *
     * @return void
     */
    public function testStartupConvertXmlElements()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<article>
    <id>1</id>
    <title><![CDATA[first]]></title>
</article>
XML;
        $this->Controller->request = new ServerRequest(['input' => $xml]);
        $this->Controller->request = $this->Controller->request
            ->withEnv('REQUEST_METHOD', 'POST')
            ->withEnv('CONTENT_TYPE', 'application/xml');

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $expected = [
            'article' => [
                'id' => 1,
                'title' => 'first'
            ]
        ];
        $this->assertEquals($expected, $this->Controller->request->getData());
    }

    /**
     * Test that input xml is parsed
     *
     * @return void
     */
    public function testStartupConvertXmlIgnoreEntities()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE item [
  <!ENTITY item "item">
  <!ENTITY item1 "&item;&item;&item;&item;&item;&item;">
  <!ENTITY item2 "&item1;&item1;&item1;&item1;&item1;&item1;&item1;&item1;&item1;">
  <!ENTITY item3 "&item2;&item2;&item2;&item2;&item2;&item2;&item2;&item2;&item2;">
  <!ENTITY item4 "&item3;&item3;&item3;&item3;&item3;&item3;&item3;&item3;&item3;">
  <!ENTITY item5 "&item4;&item4;&item4;&item4;&item4;&item4;&item4;&item4;&item4;">
  <!ENTITY item6 "&item5;&item5;&item5;&item5;&item5;&item5;&item5;&item5;&item5;">
  <!ENTITY item7 "&item6;&item6;&item6;&item6;&item6;&item6;&item6;&item6;&item6;">
  <!ENTITY item8 "&item7;&item7;&item7;&item7;&item7;&item7;&item7;&item7;&item7;">
]>
<item>
  <description>&item8;</description>
</item>
XML;
        $this->Controller->request = new ServerRequest(['input' => $xml]);
        $this->Controller->request = $this->Controller->request
            ->withEnv('REQUEST_METHOD', 'POST')
            ->withEnv('CONTENT_TYPE', 'application/xml');

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->assertEquals([], $this->Controller->request->getData());
    }

    /**
     * Test mapping a new type and having startup process it.
     *
     * @group deprecated
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupCustomTypeProcess()
    {
        $this->deprecated(function () {
            $this->Controller->request = new ServerRequest([
                'input' => '"A","csv","string"',
                'environment' => [
                    'REQUEST_METHOD' => 'POST',
                    'CONTENT_TYPE' => 'text/csv'
                ]
            ]);
            $this->RequestHandler->addInputType('csv', ['str_getcsv']);
            $event = new Event('Controller.startup', $this->Controller);
            $this->RequestHandler->startup($event);
            $expected = [
                'A', 'csv', 'string'
            ];
            $this->assertEquals($expected, $this->Controller->request->getData());
        });
    }

    /**
     * Test that data isn't processed when parsed data already exists.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupSkipDataProcess()
    {
        $this->Controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json'
            ]
        ]);

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->assertEquals([], $this->Controller->request->getData());

        $stream = new Stream('php://memory', 'w');
        $stream->write('{"new": "data"}');
        $this->Controller->request = $this->Controller->request
            ->withBody($stream)
            ->withParsedBody(['old' => 'news']);
        $this->RequestHandler->startup($event);
        $this->assertEquals(['old' => 'news'], $this->Controller->request->getData());
    }

    /**
     * test beforeRedirect when disabled.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testBeforeRedirectDisabled()
    {
        static::setAppNamespace();
        Router::connect('/:controller/:action');
        $this->Controller->request = $this->Controller->request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->initialize([]);
        $this->RequestHandler->setConfig('enableBeforeRedirect', false);
        $this->RequestHandler->startup($event);
        $this->assertNull($this->RequestHandler->beforeRedirect($event, '/posts/index', $this->Controller->response));
    }

    /**
     * testNonAjaxRedirect method
     *
     * @group deprecated
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testNonAjaxRedirect()
    {
        $this->deprecated(function () {
            $event = new Event('Controller.startup', $this->Controller);
            $this->RequestHandler->initialize([]);
            $this->RequestHandler->startup($event);
            $this->assertNull($this->RequestHandler->beforeRedirect($event, '/', $this->Controller->response));
        });
    }

    /**
     * test that redirects with ajax and no URL don't do anything.
     *
     * @group deprecated
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAjaxRedirectWithNoUrl()
    {
        $this->deprecated(function () {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
            $event = new Event('Controller.startup', $this->Controller);
            $this->Controller->response = $this->getMockBuilder('Cake\Http\Response')->getMock();

            $this->Controller->response->expects($this->never())
                ->method('body');

            $this->RequestHandler->initialize([]);
            $this->RequestHandler->startup($event);
            $this->assertNull($this->RequestHandler->beforeRedirect($event, null, $this->Controller->response));
        });
    }

    /**
     * testRenderAs method
     *
     * @return void
     */
    public function testRenderAs()
    {
        $this->RequestHandler->renderAs($this->Controller, 'rss');

        $this->Controller->viewBuilder()->setTemplatePath('request_handler_test\\rss');
        $this->RequestHandler->renderAs($this->Controller, 'js');
        $this->assertEquals('request_handler_test' . DS . 'js', $this->Controller->viewBuilder()->getTemplatePath());
    }

    /**
     * test that attachment headers work with renderAs
     *
     * @return void
     */
    public function testRenderAsWithAttachment()
    {
        $this->Controller->request = $this->request->withHeader('Accept', 'application/xml;q=1.0');

        $this->RequestHandler->renderAs($this->Controller, 'xml', ['attachment' => 'myfile.xml']);
        $this->assertEquals(XmlView::class, $this->Controller->viewBuilder()->getClassName());
        $this->assertEquals('application/xml', $this->Controller->response->getType());
        $this->assertEquals('UTF-8', $this->Controller->response->getCharset());
        $this->assertContains('myfile.xml', $this->Controller->response->getHeaderLine('Content-Disposition'));
    }

    /**
     * test that respondAs works as expected.
     *
     * @return void
     */
    public function testRespondAs()
    {
        $result = $this->RequestHandler->respondAs('json');
        $this->assertTrue($result);
        $this->assertEquals('application/json', $this->Controller->response->getType());

        $result = $this->RequestHandler->respondAs('text/xml');
        $this->assertTrue($result);
        $this->assertEquals('text/xml', $this->Controller->response->getType());
    }

    /**
     * test that attachment headers work with respondAs
     *
     * @return void
     */
    public function testRespondAsWithAttachment()
    {
        $result = $this->RequestHandler->respondAs('xml', ['attachment' => 'myfile.xml']);
        $this->assertTrue($result);
        $response = $this->Controller->response;
        $this->assertContains('myfile.xml', $response->getHeaderLine('Content-Disposition'));
        $this->assertContains('application/xml', $response->getType());
    }

    /**
     * test that calling renderAs() more than once continues to work.
     *
     * @link #6466
     * @return void
     */
    public function testRenderAsCalledTwice()
    {
        $this->Controller->getEventManager()->on('Controller.beforeRender', function (\Cake\Event\Event $e) {
            return $e->getSubject()->response;
        });
        $this->Controller->render();

        $this->RequestHandler->renderAs($this->Controller, 'print');
        $this->assertEquals('RequestHandlerTest' . DS . 'print', $this->Controller->viewBuilder()->getTemplatePath());
        $this->assertEquals('print', $this->Controller->viewBuilder()->getLayoutPath());

        $this->RequestHandler->renderAs($this->Controller, 'js');
        $this->assertEquals('RequestHandlerTest' . DS . 'js', $this->Controller->viewBuilder()->getTemplatePath());
        $this->assertEquals('js', $this->Controller->viewBuilder()->getLayoutPath());
    }

    /**
     * testRequestContentTypes method
     *
     * @return void
     */
    public function testRequestContentTypes()
    {
        $this->Controller->request = $this->request->withEnv('REQUEST_METHOD', 'GET');
        $this->assertNull($this->RequestHandler->requestedWith());

        $this->Controller->request = $this->request->withEnv('REQUEST_METHOD', 'POST')
            ->withEnv('CONTENT_TYPE', 'application/json');
        $this->assertEquals('json', $this->RequestHandler->requestedWith());

        $result = $this->RequestHandler->requestedWith(['json', 'xml']);
        $this->assertEquals('json', $result);

        $result = $this->RequestHandler->requestedWith(['rss', 'atom']);
        $this->assertFalse($result);

        $this->Controller->request = $this->request
            ->withEnv('REQUEST_METHOD', 'PATCH')
            ->withEnv('CONTENT_TYPE', 'application/json');
        $this->assertEquals('json', $this->RequestHandler->requestedWith());

        $this->Controller->request = $this->request
            ->withEnv('REQUEST_METHOD', 'DELETE')
            ->withEnv('CONTENT_TYPE', 'application/json');
        $this->assertEquals('json', $this->RequestHandler->requestedWith());

        $this->Controller->request = $this->request
            ->withEnv('REQUEST_METHOD', 'POST')
            ->withEnv('CONTENT_TYPE', 'application/json');
        $result = $this->RequestHandler->requestedWith(['json', 'xml']);
        $this->assertEquals('json', $result);

        $result = $this->RequestHandler->requestedWith(['rss', 'atom']);
        $this->assertFalse($result);

        $this->Controller->request = $this->request->withHeader(
            'Accept',
            'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*'
        );
        $this->assertTrue($this->RequestHandler->isXml());
        $this->assertFalse($this->RequestHandler->isAtom());
        $this->assertFalse($this->RequestHandler->isRSS());

        $this->Controller->request = $this->request->withHeader(
            'Accept',
            'application/atom+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*'
        );
        $this->assertTrue($this->RequestHandler->isAtom());
        $this->assertFalse($this->RequestHandler->isRSS());

        $this->Controller->request = $this->request->withHeader(
            'Accept',
            'application/rss+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*'
        );
        $this->assertFalse($this->RequestHandler->isAtom());
        $this->assertTrue($this->RequestHandler->isRSS());

        $this->assertFalse($this->RequestHandler->isWap());
        $this->Controller->request = $this->request->withHeader(
            'Accept',
            'text/vnd.wap.wml,text/html,text/plain,image/png,*/*'
        );
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
        $request = $this->getMockBuilder('Cake\Http\ServerRequest')
            ->setMethods(['is'])
            ->getMock();
        $request->expects($this->once())->method('is')
            ->with('mobile')
            ->will($this->returnValue(true));

        $this->Controller->request = $request;
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
        $this->Controller->request = $this->request->withHeader(
            'Accept',
            'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5'
        );
        $this->assertTrue($this->RequestHandler->accepts(['js', 'xml', 'html']));
        $this->assertFalse($this->RequestHandler->accepts(['gif', 'jpeg', 'foo']));

        $this->Controller->request = $this->request->withHeader('Accept', '*/*;q=0.5');
        $this->assertFalse($this->RequestHandler->accepts('rss'));
    }

    /**
     * test accepts and prefers methods.
     *
     * @return void
     */
    public function testPrefers()
    {
        $this->Controller->request = $this->request->withHeader(
            'Accept',
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
        $this->Controller->request = $this->request->withHeader(
            'Accept',
            'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5'
        );
        $this->assertEquals('xml', $this->RequestHandler->prefers());

        $this->Controller->request = $this->request->withHeader('Accept', '*/*;q=0.5');
        $this->assertEquals('html', $this->RequestHandler->prefers());
        $this->assertFalse($this->RequestHandler->prefers('rss'));

        $this->Controller->request = $this->request->withEnv('HTTP_ACCEPT', null);
        $this->RequestHandler->ext = 'json';
        $this->assertFalse($this->RequestHandler->prefers('xml'));
    }

    /**
     * test that AJAX requests involving redirects trigger requestAction instead.
     *
     * @group deprecated
     * @return void
     * @triggers Controller.beforeRedirect $this->Controller
     */
    public function testAjaxRedirectAsRequestAction()
    {
        $this->deprecated(function () {
            static::setAppNamespace();
            Router::connect('/:controller/:action');
            $event = new Event('Controller.beforeRedirect', $this->Controller);

            $this->RequestHandler = new RequestHandlerComponent($this->Controller->components());
            $this->Controller->request = $this->getMockBuilder('Cake\Http\ServerRequest')
                ->setMethods(['is'])
                ->getMock();
            $this->Controller->response = $this->getMockBuilder('Cake\Http\Response')
                ->setMethods(['_sendHeader', 'stop'])
                ->getMock();
            $this->Controller->request->expects($this->any())
                ->method('is')
                ->will($this->returnValue(true));

            $response = $this->RequestHandler->beforeRedirect(
                $event,
                ['controller' => 'RequestHandlerTest', 'action' => 'destination'],
                $this->Controller->response
            );
            $this->assertRegExp('/posts index/', $response->body(), 'RequestAction redirect failed.');
        });
    }

    /**
     * Test that AJAX requests involving redirects handle querystrings
     *
     * @group deprecated
     * @return void
     * @triggers Controller.beforeRedirect $this->Controller
     */
    public function testAjaxRedirectAsRequestActionWithQueryString()
    {
        $this->deprecated(function () {
            static::setAppNamespace();
            Router::connect('/:controller/:action');

            $this->RequestHandler = new RequestHandlerComponent($this->Controller->components());
            $this->Controller->request = $this->getMockBuilder('Cake\Http\ServerRequest')
                ->setMethods(['is'])
                ->getMock();
            $this->Controller->response = $this->getMockBuilder('Cake\Http\Response')
                ->setMethods(['_sendHeader', 'stop'])
                ->getMock();
            $this->Controller->request->expects($this->any())
                ->method('is')
                ->with('ajax')
                ->will($this->returnValue(true));
            $event = new Event('Controller.beforeRedirect', $this->Controller);

            $response = $this->RequestHandler->beforeRedirect(
                $event,
                '/request_action/params_pass?a=b&x=y?ish',
                $this->Controller->response
            );
            $data = json_decode($response, true);
            $this->assertEquals('/request_action/params_pass', $data['here']);

            $response = $this->RequestHandler->beforeRedirect(
                $event,
                '/request_action/query_pass?a=b&x=y?ish',
                $this->Controller->response
            );
            $data = json_decode($response, true);
            $this->assertEquals('y?ish', $data['x']);
        });
    }

    /**
     * Test that AJAX requests involving redirects handle cookie data
     *
     * @group deprecated
     * @return void
     * @triggers Controller.beforeRedirect $this->Controller
     */
    public function testAjaxRedirectAsRequestActionWithCookieData()
    {
        $this->deprecated(function () {
            static::setAppNamespace();
            Router::connect('/:controller/:action');
            $event = new Event('Controller.beforeRedirect', $this->Controller);

            $this->RequestHandler = new RequestHandlerComponent($this->Controller->components());
            $this->Controller->request = $this->getMockBuilder('Cake\Http\ServerRequest')
                ->setMethods(['is'])
                ->getMock();
            $this->Controller->response = $this->getMockBuilder('Cake\Http\Response')
                ->setMethods(['_sendHeader', 'stop'])
                ->getMock();
            $this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));

            $cookies = [
                'foo' => 'bar'
            ];
            $this->Controller->request->cookies = $cookies;

            $response = $this->RequestHandler->beforeRedirect(
                $event,
                '/request_action/cookie_pass',
                $this->Controller->response
            );
            $data = json_decode($response, true);
            $this->assertEquals($cookies, $data);
        });
    }

    /**
     * Tests that AJAX requests involving redirects don't let the status code bleed through.
     *
     * @group deprecated
     * @return void
     * @triggers Controller.beforeRedirect $this->Controller
     */
    public function testAjaxRedirectAsRequestActionStatusCode()
    {
        $this->deprecated(function () {
            static::setAppNamespace();
            Router::connect('/:controller/:action');
            $event = new Event('Controller.beforeRedirect', $this->Controller);

            $this->RequestHandler = new RequestHandlerComponent($this->Controller->components());
            $this->Controller->request = $this->getMockBuilder('Cake\Http\ServerRequest')
                ->setMethods(['is'])
                ->getMock();
            $this->Controller->response = $this->getMockBuilder('Cake\Http\Response')
                ->setMethods(['_sendHeader', 'stop'])
                ->getMock();
            $this->Controller->response->statusCode(302);
            $this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));

            $response = $this->RequestHandler->beforeRedirect(
                $event,
                ['controller' => 'RequestHandlerTest', 'action' => 'destination'],
                $this->Controller->response
            );
            $this->assertRegExp('/posts index/', $response->body(), 'RequestAction redirect failed.');
            $this->assertSame(200, $response->statusCode());
        });
    }

    /**
     * test that ajax requests involving redirects don't force no layout
     * this would cause the ajax layout to not be rendered.
     *
     * @group deprecated
     * @return void
     * @triggers Controller.beforeRedirect $this->Controller
     */
    public function testAjaxRedirectAsRequestActionStillRenderingLayout()
    {
        $this->deprecated(function () {
            static::setAppNamespace();
            Router::connect('/:controller/:action');
            $event = new Event('Controller.beforeRedirect', $this->Controller);

            $this->RequestHandler = new RequestHandlerComponent($this->Controller->components());
            $this->Controller->request = $this->getMockBuilder('Cake\Http\ServerRequest')
                ->setMethods(['is'])
                ->getMock();
            $this->Controller->response = $this->getMockBuilder('Cake\Http\Response')
                ->setMethods(['_sendHeader', 'stop'])
                ->getMock();
            $this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));

            $response = $this->RequestHandler->beforeRedirect(
                $event,
                ['controller' => 'RequestHandlerTest', 'action' => 'ajax2_layout'],
                $this->Controller->response
            );
            $this->assertRegExp('/posts index/', $response->body(), 'RequestAction redirect failed.');
            $this->assertRegExp('/Ajax!/', $response->body(), 'Layout was not rendered.');
        });
    }

    /**
     * test that the beforeRedirect callback properly converts
     * array URLs into their correct string ones, and adds base => false so
     * the correct URLs are generated.
     *
     * @group deprecated
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testBeforeRedirectCallbackWithArrayUrl()
    {
        $this->deprecated(function () {
            static::setAppNamespace();
            Router::connect('/:controller/:action/*');
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
            $event = new Event('Controller.beforeRender', $this->Controller);

            Router::setRequestInfo([
                ['plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => []],
                ['base' => '', 'here' => '/accounts/', 'webroot' => '/']
            ]);

            $RequestHandler = new RequestHandlerComponent($this->Controller->components());
            $this->Controller->request = new ServerRequest('posts/index');

            ob_start();
            $RequestHandler->beforeRedirect(
                $event,
                ['controller' => 'RequestHandlerTest', 'action' => 'param_method', 'first', 'second'],
                $this->Controller->response
            );
            $result = ob_get_clean();
            $this->assertEquals('one: first two: second', $result);
        });
    }

    /**
     * testAddInputTypeException method
     *
     * @group deprecated
     * @return void
     */
    public function testAddInputTypeException()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $this->deprecated(function () {
            $this->RequestHandler->addInputType('csv', ['I am not callable']);
        });
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testCheckNotModifiedByEtagStar()
    {
        $response = new Response();
        $response = $response->withEtag('something')
            ->withHeader('Content-Type', 'text/plain')
            ->withStringBody('keeper');
        $this->Controller->response = $response;
        $this->Controller->request = $this->request->withHeader('If-None-Match', '*');

        $event = new Event('Controller.beforeRender', $this->Controller);
        $requestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->assertFalse($requestHandler->beforeRender($event));
        $this->assertEquals(304, $this->Controller->response->getStatusCode());
        $this->assertEquals('', (string)$this->Controller->response->getBody());
        $this->assertFalse($this->Controller->response->hasHeader('Content-Type'), 'header should not be removed.');
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender
     */
    public function testCheckNotModifiedByEtagExact()
    {
        $response = new Response();
        $response = $response->withEtag('something', true)
            ->withHeader('Content-Type', 'text/plain')
            ->withStringBody('keeper');
        $this->Controller->response = $response;

        $this->Controller->request = $this->request->withHeader('If-None-Match', 'W/"something", "other"');
        $event = new Event('Controller.beforeRender', $this->Controller);

        $requestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->assertFalse($requestHandler->beforeRender($event));
        $this->assertEquals(304, $this->Controller->response->getStatusCode());
        $this->assertEquals('', (string)$this->Controller->response->getBody());
        $this->assertFalse($this->Controller->response->hasHeader('Content-Type'));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testCheckNotModifiedByEtagAndTime()
    {
        $this->Controller->request = $this->request
            ->withHeader('If-None-Match', 'W/"something", "other"')
            ->withHeader('If-Modified-Since', '2012-01-01 00:00:00');

        $response = new Response();
        $response = $response->withEtag('something', true)
            ->withHeader('Content-type', 'text/plain')
            ->withStringBody('should be removed')
            ->withModified('2012-01-01 00:00:00');
        $this->Controller->response = $response;

        $event = new Event('Controller.beforeRender', $this->Controller);
        $requestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->assertFalse($requestHandler->beforeRender($event));

        $this->assertEquals(304, $this->Controller->response->getStatusCode());
        $this->assertEquals('', (string)$this->Controller->response->getBody());
        $this->assertFalse($this->Controller->response->hasHeader('Content-type'));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testCheckNotModifiedNoInfo()
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['notModified', 'stop'])
            ->getMock();
        $response->expects($this->never())->method('notModified');
        $this->Controller->response = $response;

        $event = new Event('Controller.beforeRender', $this->Controller);
        $requestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->assertNull($requestHandler->beforeRender($event));
    }

    /**
     * Test default options in construction
     *
     * @return void
     */
    public function testConstructDefaultOptions()
    {
        $requestHandler = new RequestHandlerComponent($this->Controller->components());
        $viewClass = $requestHandler->getConfig('viewClassMap');
        $expected = [
            'json' => 'Json',
            'xml' => 'Xml',
            'ajax' => 'Ajax',
        ];
        $this->assertEquals($expected, $viewClass);

        $inputs = $requestHandler->getConfig('inputTypeMap');
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
        $viewClass = $requestHandler->getConfig('viewClassMap');
        $expected = [
            'json' => 'Json',
        ];
        $this->assertEquals($expected, $viewClass);

        $inputs = $requestHandler->getConfig('inputTypeMap');
        $this->assertArrayHasKey('json', $inputs);
        $this->assertCount(1, $inputs);
    }

    /**
     * test beforeRender() doesn't override response type set in controller action
     *
     * @return void
     */
    public function testBeforeRender()
    {
        $this->Controller->set_response_type();
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $this->assertEquals('text/plain', $this->Controller->response->getType());
    }

    /**
     * tests beforeRender automatically uses renderAs when a supported extension is found
     *
     * @return void
     */
    public function testBeforeRenderAutoRenderAs()
    {
        $this->Controller->setRequest($this->request->withParam('_ext', 'csv'));
        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));

        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $this->assertEquals('text/csv', $this->Controller->response->getType());
    }
}
