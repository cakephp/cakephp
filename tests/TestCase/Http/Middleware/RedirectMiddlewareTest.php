<?php
declare(strict_types=1);

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
 * @since         4.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Http\ServerRequest;
use Cake\Routing\Exception\RedirectException;
use Cake\Routing\Middleware\RedirectMiddleware;
use Cake\TestSuite\TestCase;
use TestApp\Http\TestRequestHandler;

class RedirectMiddlewareTest extends TestCase
{
    /**
     * Provides the request handler
     *
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    protected function _getRequestHandler()
    {
        return new TestRequestHandler(function ($request) {
            throw new RedirectException('/foo/bar?baz=1');
        });
    }

    /**
     * testInvoke
     *
     * @return void
     */
    public function testProcess()
    {
        $request = new ServerRequest();

        $middleware = new RedirectMiddleware();

        $response = $middleware->process($request, $this->_getRequestHandler());
        $headers = $response->getHeaders();
        $expected = [
            'location' => [
                '/foo/bar?baz=1'
            ],
        ];

        $this->assertEquals($expected, $headers);
    }
}
