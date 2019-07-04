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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n\Middleware;

use Cake\I18n\I18n;
use Cake\I18n\Middleware\LocaleSelectorMiddleware;
use Cake\TestSuite\TestCase;
use Locale;
use TestApp\Http\TestRequestHandler;
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
    public function setUp(): void
    {
        parent::setUp();
        $this->locale = Locale::getDefault();
    }

    /**
     * Resets the default locale
     *
     * @return void
     */
    public function tearDown(): void
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
        $middleware = new LocaleSelectorMiddleware([]);
        $middleware->process($request, new TestRequestHandler());
        $this->assertSame($this->locale, I18n::getLocale());

        $request = ServerRequestFactory::fromGlobals(['HTTP_ACCEPT_LANGUAGE' => 'garbage']);
        $middleware = new LocaleSelectorMiddleware([]);
        $middleware->process($request, new TestRequestHandler());
        $this->assertSame($this->locale, I18n::getLocale());
    }

    /**
     * The default locale should not change when the request locale is not accepted
     *
     * @return void
     */
    public function testInvokeLocaleNotAccepted()
    {
        $request = ServerRequestFactory::fromGlobals(['HTTP_ACCEPT_LANGUAGE' => 'en-GB,en;q=0.8,es;q=0.6,da;q=0.4']);
        $middleware = new LocaleSelectorMiddleware(['en_CA', 'en_US', 'es']);
        $middleware->process($request, new TestRequestHandler());
        $this->assertSame($this->locale, I18n::getLocale(), 'en-GB is not accepted');
    }

    /**
     * The default locale should change when the request locale is accepted
     *
     * @return void
     */
    public function testInvokeLocaleAccepted()
    {
        $request = ServerRequestFactory::fromGlobals(['HTTP_ACCEPT_LANGUAGE' => 'es,es-ES;q=0.8,da;q=0.4']);
        $middleware = new LocaleSelectorMiddleware(['en_CA', 'es']);
        $middleware->process($request, new TestRequestHandler());
        $this->assertSame('es', I18n::getLocale(), 'es is accepted');
    }

    /**
     * The default locale should change when the request locale has an accepted fallback option
     *
     * @return void
     */
    public function testInvokeLocaleAcceptedFallback()
    {
        $request = ServerRequestFactory::fromGlobals(['HTTP_ACCEPT_LANGUAGE' => 'es-ES;q=0.8,da;q=0.4']);
        $middleware = new LocaleSelectorMiddleware(['en_CA', 'es']);
        $middleware->process($request, new TestRequestHandler());
        $this->assertSame('es', I18n::getLocale(), 'es is accepted');
    }

    /**
     * The default locale should change when the '*' is accepted
     *
     * @return void
     */
    public function testInvokeLocaleAcceptAll()
    {
        $middleware = new LocaleSelectorMiddleware(['*']);

        $request = ServerRequestFactory::fromGlobals(['HTTP_ACCEPT_LANGUAGE' => 'es,es-ES;q=0.8,da;q=0.4']);
        $middleware->process($request, new TestRequestHandler());
        $this->assertSame('es', I18n::getLocale(), 'es is accepted');

        $request = ServerRequestFactory::fromGlobals(['HTTP_ACCEPT_LANGUAGE' => 'en;q=0.4,es;q=0.6,da;q=0.8']);
        $middleware->process($request, new TestRequestHandler());
        $this->assertSame('da', I18n::getLocale(), 'da is accepted');
    }
}
