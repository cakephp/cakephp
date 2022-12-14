<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Http\Middleware\CspMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use ParagonIE\CSPBuilder\CSPBuilder;
use Psr\Http\Server\RequestHandlerInterface;
use TestApp\Http\TestRequestHandler;

/**
 * Content Security Policy Middleware Test
 */
class CspMiddlewareTest extends TestCase
{
    /**
     * Provides the request handler
     */
    protected function _getRequestHandler(): RequestHandlerInterface
    {
        return new TestRequestHandler(function ($request) {
            return new Response();
        });
    }

    /**
     * test process adding headers
     */
    public function testProcessAddHeaders(): void
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

        $response = $middleware->process($request, $this->_getRequestHandler());
        $policy = $response->getHeaderLine('Content-Security-Policy');

        $expected = 'script-src \'self\' https://www.google-analytics.com';
        $this->assertStringContainsString($expected, $policy);
        $this->assertStringNotContainsString('nonce-', $policy);
    }

    /**
     * test process adding request attributes for nonces
     */
    public function testProcessAddNonceAttributes(): void
    {
        $request = new ServerRequest();

        $policy = [
            'script-src' => [
                'self' => true,
                'unsafe-inline' => false,
                'unsafe-eval' => false,
            ],
            'style-src' => [
                'self' => true,
                'unsafe-inline' => false,
                'unsafe-eval' => false,
            ],
        ];
        $middleware = new CspMiddleware($policy, [
            'scriptNonce' => true,
            'styleNonce' => true,
        ]);

        $handler = new TestRequestHandler(function ($request) {
            $this->assertNotEmpty($request->getAttribute('cspScriptNonce'));
            $this->assertNotEmpty($request->getAttribute('cspStyleNonce'));

            return new Response();
        });

        $response = $middleware->process($request, $handler);
        $policy = $response->getHeaderLine('Content-Security-Policy');
        $expected = [
            "script-src 'self' 'nonce-",
            "style-src 'self' 'nonce-",
        ];

        $this->assertNotEmpty($policy);
        foreach ($expected as $match) {
            $this->assertStringContainsString($match, $policy);
        }
    }

    /**
     * testPassingACSPBuilderInstance
     */
    public function testPassingACSPBuilderInstance(): void
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

        $response = $middleware->process($request, $this->_getRequestHandler());
        $policy = $response->getHeaderLine('Content-Security-Policy');
        $expected = 'script-src \'self\' https://www.google-analytics.com';

        $this->assertStringContainsString($expected, $policy);
    }
}
