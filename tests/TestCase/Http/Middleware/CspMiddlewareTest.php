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
 * @since         3.9.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Http\Middleware\CspMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use ParagonIE\CSPBuilder\CSPBuilder;

/**
 * Content Security Policy Middleware Test
 */
class CspMiddlewareTest extends TestCase
{
    /**
     * testInvoke
     *
     * @return void
     */
    public function testProcess()
    {
        $request = new ServerRequest();

        $middleware = new CspMiddleware([
            'script-src' => [
                'allow' => [
                    'https://www.google-analytics.com',
                ],
                'self' => true,
                'unsafe-inline' => false,
                'unsafe-eval' => false,
            ],
        ]);

        $next = function ($request, $response) {
            $expected = [
                'script-src \'self\' https://www.google-analytics.com; ',
            ];
            $headers = $response->getHeaders();
            $this->assertNotEmpty($headers['Content-Security-Policy']);
            $this->assertEquals($expected, $headers['Content-Security-Policy']);
        };

        $response = new Response();
        $middleware($request, $response, $next);
    }

    /**
     * testPassingACSPBuilderInstance
     *
     * @return void
     */
    public function testPassingACSPBuilderInstance()
    {
        $request = new ServerRequest();

        $config = [
            'script-src' => [
                'allow' => [
                    'https://www.google-analytics.com',
                ],
                'self' => true,
                'unsafe-inline' => false,
                'unsafe-eval' => false,
            ],
        ];

        $cspBuilder = new CSPBuilder($config);
        $middleware = new CspMiddleware($cspBuilder);

        $next = function ($request, $response) {
            $headers = $response->getHeaders();
            $expected = [
                'script-src \'self\' https://www.google-analytics.com; ',
            ];

            $this->assertNotEmpty($headers['Content-Security-Policy']);
            $this->assertEquals($expected, $headers['Content-Security-Policy']);
        };

        $response = new Response();
        $middleware($request, $response, $next);
    }
}
