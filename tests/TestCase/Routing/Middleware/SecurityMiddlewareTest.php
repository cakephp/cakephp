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
namespace Cake\Test\TestCase\Routing\Middleware;

use Cake\Http\ServerRequestFactory;
use Cake\Routing\Middleware\SecurityMiddleware;
use Cake\TestSuite\TestCase;
use Zend\Diactoros\Response;

/**
 * Test for SecurityMiddleware
 */
class SecurityMiddlewareTest extends TestCase {

    /**
     * Test adding the security headers
     *
     * @return void
     */
    public function testAddingSecurityHeaders()
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/',
        ]);
        $response = new Response();
        $next = function ($req, $res) {
            return $res;
        };

        $middleware = new SecurityMiddleware();
        $middleware
            ->setCrossDomainPolicy()
            ->setReferrerPolicy()
            ->setXFrameOptions()
            ->setXssProtection()
            ->noOpen()
            ->noSniff();

        $expected = [
            'x-permitted-cross-domain-policies' => [
                0 => '1; mode=block'
            ],
            'referrer-policy' => [
                0 => 'same-origin'
            ],
            'x-frame-options' => [
                0 => 'sameorigin'
            ],
            'x-download-options' => [
                0 => 'noopen'
            ],
            'x-content-type-options' => [
                0 => 'nosniff'
            ]

        ];

        $result = $middleware($request, $response, $next);
        $this->assertEquals($expected, $result->getHeaders());
    }
}
