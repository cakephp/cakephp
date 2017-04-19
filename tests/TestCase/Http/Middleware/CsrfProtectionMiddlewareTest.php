<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\Http\Response;

/**
 * Test for CsrfProtection
 */
class CsrfProtectionMiddlewareTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test setting the cookie value
     *
     * @return void
     */
    public function testSettingCookie()
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'webroot' => '/dir/',
        ]);
        $response = new Response();

        $callback = function($request, $response) {
            return $response;
        };
        $middleware = new CsrfProtectionMiddleware();
        $response = $middleware($request, $response, $callback);
        $cookie = $response->cookie('csrfToken');

        $this->assertNotEmpty($cookie, 'Should set a token.');
        $this->assertRegExp('/^[a-f0-9]+$/', $cookie['value'], 'Should look like a hash.');
        $this->assertEquals(0, $cookie['expire'], 'session duration.');
        $this->assertEquals('/dir/', $cookie['path'], 'session path.');
        $this->assertEquals($cookie['value'], $request->params['_csrfToken']);
    }
}
