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
 * @since         3.6.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use Cake\Http\Middleware\CspMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * Content Security Policy Middleware Test
 */
class CspMiddlewareTest extends TestCase
{

    /**
     * @inheritDoc
     */
    public function setUp() {
        parent::setUp();

        $this->skipIf(version_compare(PHP_VERSION, '7.0.0', 'lt'));
    }

    /**
     * testInvoke
     *
     * @return void
     */
    public function testInvoke() {
        $request = new ServerRequest();
        $response = new Response();
        $callable = function($request, $response) {
            return $response;
        };

        $middleware = new CspMiddleware([
            'script-src' => [
                'allow' => [
                    'https://www.google-analytics.com'
                ],
                'self' => true,
                'unsafe-inline' => false,
                'unsafe-eval' => false
            ]
        ]);

        $response = $middleware($request, $response, $callable);
        $headers = $response->getHeaders();
        $expected = [
            'script-src \'self\' https://www.google-analytics.com; '
        ];

        $this->assertNotEmpty($headers['Content-Security-Policy']);
        $this->assertEquals($expected, $headers['Content-Security-Policy']);
    }
}
