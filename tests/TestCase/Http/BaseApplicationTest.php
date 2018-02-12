<?php
namespace Cake\Test\TestCase;

use Cake\Http\BaseApplication;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
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
}
