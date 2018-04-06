<?php
namespace Cake\Test\TestCase;

use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

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

        $path = dirname(dirname(__DIR__));
        $app = $this->getMockForAbstractClass('Cake\Http\BaseApplication', [$path]);
        $result = $app($request, $response, $next);
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $result);
        $this->assertEquals('Hello Jane', '' . $result->getBody());
    }

    /**
     * Ensure that Router::$initialized is toggled even if the routes
     * file fails. This prevents the routes file from being re-parsed
     * during the error handling process.
     *
     * @return void
     */
    public function testRouteHookInitializesRouterOnError()
    {
        $app = $this->getMockForAbstractClass(
            'Cake\Http\BaseApplication',
            [TEST_APP . 'invalid_routes' . DS]
        );
        $builder = Router::createRouteBuilder('/');
        try {
            $app->routes($builder);

            $this->fail('invalid_routes/routes.php file should raise an error.');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(Router::$initialized, 'Should be toggled to prevent duplicate route errors');
            $this->assertContains('route class', $e->getMessage());
        }
    }
}
