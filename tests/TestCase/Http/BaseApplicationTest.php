<?php
namespace Cake\Test\TestCase;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestPlugin\Plugin as TestPlugin;

/**
 * Base application test.
 */
class BaseApplicationTest extends TestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        static::setAppNamespace();
        $this->path = dirname(dirname(__DIR__));
    }

    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload();
    }

    /**
     * Integration test for a simple controller.
     *
     * @return void
     */
    public function testInvoke()
    {
        $next = function ($req, $res) {
            return $res;
        };
        $response = new Response();
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/cakes']);
        $request = $request->withAttribute('params', [
            'controller' => 'Cakes',
            'action' => 'index',
            'plugin' => null,
            'pass' => []
        ]);

        $app = $this->getMockForAbstractClass('Cake\Http\BaseApplication', [$this->path]);
        $result = $app($request, $response, $next);
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $result);
        $this->assertEquals('Hello Jane', '' . $result->getBody());
    }

    public function testAddPluginUnknownClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be found');
        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $app->addPlugin('SomethingBad');
    }

    public function testAddPluginBadClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not implement');
        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $app->addPlugin(__CLASS__);
    }

    public function testAddPluginValid()
    {
        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $app->addPlugin(TestPlugin::class);

        $this->assertCount(1, $app->getPlugins());
        $this->assertTrue($app->getPlugins()->has('TestPlugin'));
    }

    public function testPluginEvents()
    {
        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            [$this->path]
        );
        $start = $app->getEventManager();
        $this->assertCount(0, $start->listeners('TestPlugin.load'));

        $app->addPlugin(TestPlugin::class);
        $this->assertNull($app->pluginEvents());

        $after = $app->getEventManager();
        $this->assertSame($after, $start);
        $this->assertCount(1, $after->listeners('TestPlugin.load'));
    }

    public function testPluginMiddleware()
    {
        $start = new MiddlewareQueue();
        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            [$this->path]
        );
        $app->addPlugin(TestPlugin::class);

        $after = $app->pluginMiddleware($start);
        $this->assertSame($start, $after);
        $this->assertCount(1, $after);
    }

    public function testPluginRoutes()
    {
        $collection = new RouteCollection();
        $routes = new RouteBuilder($collection, '/');
        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            [$this->path]
        );
        $app->addPlugin(TestPlugin::class);

        $result = $app->pluginRoutes($routes);
        $this->assertSame($routes, $result);
        $url = [
            'plugin' => 'TestPlugin',
            'controller' => 'TestPlugin',
            'action' => 'index',
            '_method' => 'GET'
        ];
        $this->assertNotEmpty($collection->match($url, []));
    }

    public function testPluginBootstrap()
    {
        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            [$this->path]
        );
        $app->addPlugin(TestPlugin::class);

        $this->assertFalse(Configure::check('PluginTest.test_plugin.bootstrap'));
        $this->assertNull($app->pluginBootstrap());
        $this->assertTrue(Configure::check('PluginTest.test_plugin.bootstrap'));
    }
}
