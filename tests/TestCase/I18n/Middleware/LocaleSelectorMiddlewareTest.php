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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n\Middleware;

use Cake\I18n\I18n;
use Cake\I18n\Middleware\LocaleSelectorMiddleware;
use Cake\TestSuite\TestCase;
use Locale;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Test for LocaleSelectorMiddleware
 */
class LocaleSelectorMiddlewareTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->locale = Locale::getDefault();
        $this->next = function ($req, $res) {
            return $res;
        };
    }

    /**
     * Resets the default locale
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Locale::setDefault($this->locale);
    }

    /**
     * The default locale should not change when there are no accepted
     * locales.
     *
     * @return void
     */
    public function testInvokeNoAcceptedLocales()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        $middleware = new LocaleSelectorMiddleware([]);
        $middleware($request, $response, $this->next);
        $this->assertSame($this->locale, I18n::locale());

        $request = ServerRequestFactory::fromGlobals(['HTTP_ACCEPT_LANGUAGE' => 'garbage']);
        $response = new Response();
        $middleware = new LocaleSelectorMiddleware([]);
        $middleware($request, $response, $this->next);
        $this->assertSame($this->locale, I18n::locale());
    }

    /**
     * The default locale should not change when the request locale is not accepted
     *
     * @return void
     */
    public function testInvokeLocaleNotAccepted()
    {
        $request = ServerRequestFactory::fromGlobals(['HTTP_ACCEPT_LANGUAGE' => 'en-GB,en;q=0.8,es;q=0.6,da;q=0.4']);
        $response = new Response();
        $middleware = new LocaleSelectorMiddleware(['en_CA', 'en_US', 'es']);
        $middleware($request, $response, $this->next);
        $this->assertSame($this->locale, I18n::locale(), 'en-GB is not accepted');
    }

    /**
     * The default locale should change when the request locale is accepted
     *
     * @return void
     */
    public function testInvokeLocaleAccepted()
    {
        $request = ServerRequestFactory::fromGlobals(['HTTP_ACCEPT_LANGUAGE' => 'es,es-ES;q=0.8,da;q=0.4']);
        $response = new Response();
        $middleware = new LocaleSelectorMiddleware(['en_CA', 'es']);
        $middleware($request, $response, $this->next);
        $this->assertSame('es', I18n::locale(), 'es is accepted');
    }

    /**
     * The default locale should change when the '*' is accepted
     *
     * @return void
     */
    public function testInvokeLocaleAcceptAll()
    {
        $response = new Response();
        $middleware = new LocaleSelectorMiddleware(['*']);

        $request = ServerRequestFactory::fromGlobals(['HTTP_ACCEPT_LANGUAGE' => 'es,es-ES;q=0.8,da;q=0.4']);
        $middleware($request, $response, $this->next);
        $this->assertSame('es', I18n::locale(), 'es is accepted');

        $request = ServerRequestFactory::fromGlobals(['HTTP_ACCEPT_LANGUAGE' => 'en;q=0.4,es;q=0.6,da;q=0.8']);
        $middleware($request, $response, $this->next);
        $this->assertSame('da', I18n::locale(), 'da is accepted');
    }
}
