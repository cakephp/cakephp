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

use Cake\Http\Middleware\SecurityHeadersMiddleware;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Response;

/**
 * Test for SecurityMiddleware
 */
class SecurityHeadersMiddlewareTest extends TestCase
{
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

        $middleware = new SecurityHeadersMiddleware();
        $middleware
            ->setCrossDomainPolicy()
            ->setReferrerPolicy()
            ->setXFrameOptions()
            ->setXssProtection()
            ->noOpen()
            ->noSniff();

        $expected = [
            'x-permitted-cross-domain-policies' => ['all'],
            'x-xss-protection' => ['1; mode=block'],
            'referrer-policy' => ['same-origin'],
            'x-frame-options' => ['sameorigin'],
            'x-download-options' => ['noopen'],
            'x-content-type-options' => ['nosniff'],
        ];

        $result = $middleware($request, $response, $next);
        $this->assertEquals($expected, $result->getHeaders());
    }

    /**
     * Testing that the URL is required when option is `allow-from`
     *
     * @return void
     */
    public function testInvalidArgumentExceptionForsetXFrameOptionsUrl()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The 2nd arg $url can not be empty when `allow-from` is used');
        $middleware = new SecurityHeadersMiddleware();
        $middleware->setXFrameOptions('allow-from');
    }

    /**
     * Testing the protected checkValues() method that is used by most of the
     * methods in the test to avoid passing an invalid argument.
     *
     * @return void
     */
    public function testCheckValues()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid arg `INVALID-VALUE!`, use one of these: all, none, master-only, by-content-type, by-ftp-filename');
        $middleware = new SecurityHeadersMiddleware();
        $middleware->setCrossDomainPolicy('INVALID-VALUE!');
    }
}
