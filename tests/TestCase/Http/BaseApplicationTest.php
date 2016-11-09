<?php
namespace Cake\Test\TestCase;

use Cake\Core\Configure;
use Cake\Http\BaseApplication;
use Cake\Http\ServerRequestFactory;
use Cake\Network\Response;
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
        Configure::write('App.namespace', 'TestApp');
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
}
