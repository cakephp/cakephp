<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n\Middleware;

use Cake\I18n\I18n;
use Locale;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sets the runtime default locale for the request based on the
 * Accept-Language header. The default will only be set if it
 * matches the list of passed valid locales.
 */
class LocaleSelectorMiddleware implements MiddlewareInterface
{
    /**
     * List of valid locales for the request
     *
     * @var array
     */
    protected $locales = [];

    /**
     * Constructor.
     *
     * @param array $locales A list of accepted locales, or ['*'] to accept any
     *   locale header value.
     */
    public function __construct(array $locales = [])
    {
        $this->locales = $locales;
    }

    /**
     * Set locale based on request headers.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $locale = Locale::acceptFromHttp($request->getHeaderLine('Accept-Language'));
        if (!$locale) {
            return $handler->handle($request);
        }
        if ($this->locales !== ['*']) {
            $locale = Locale::lookup($this->locales, $locale, true);
        }
        if ($locale || $this->locales === ['*']) {
            I18n::setLocale($locale);
        }

        return $handler->handle($request);
    }
}
