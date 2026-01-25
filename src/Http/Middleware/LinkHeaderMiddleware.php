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
 * @since         5.4.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Link\LinkInterface;

/**
 * Middleware that converts PSR-13 links to HTTP Link headers.
 *
 * This middleware reads links from the response (if using Cake\Http\Response)
 * and converts them to HTTP Link headers as defined in RFC 8288.
 *
 * ### Usage
 *
 * Add to your middleware queue in Application.php:
 *
 * ```php
 * $middlewareQueue->add(new LinkHeaderMiddleware());
 * ```
 *
 * Then in your controller, add links to the response:
 *
 * ```php
 * use Cake\Http\Link\Link;
 *
 * $this->response = $this->response->withLink(
 *     (new Link('/css/app.css'))
 *         ->withRel('preload')
 *         ->withAttribute('as', 'style')
 * );
 * ```
 *
 * The middleware will output:
 *
 * ```
 * Link: </css/app.css>; rel="preload"; as="style"
 * ```
 *
 * @link https://www.rfc-editor.org/rfc/rfc8288 Web Linking (RFC 8288)
 */
class LinkHeaderMiddleware implements MiddlewareInterface
{
    /**
     * Process the request and convert PSR-13 links to HTTP headers.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!$response instanceof Response) {
            return $response;
        }

        $links = $response->getLinks()->getLinks();
        foreach ($links as $link) {
            $response = $response->withAddedHeader('Link', $this->formatLink($link));
        }

        return $response;
    }

    /**
     * Format a PSR-13 link as an HTTP Link header value.
     *
     * @param \Psr\Link\LinkInterface $link The link to format.
     * @return string The formatted header value.
     */
    protected function formatLink(LinkInterface $link): string
    {
        $parts = ['<' . $link->getHref() . '>'];

        $rels = $link->getRels();
        if ($rels) {
            $parts[] = 'rel="' . implode(' ', $rels) . '"';
        }

        foreach ($link->getAttributes() as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $parts[] = $key;
                }
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $v) {
                    $parts[] = $key . '="' . $this->escapeValue((string)$v) . '"';
                }
                continue;
            }

            $parts[] = $key . '="' . $this->escapeValue((string)$value) . '"';
        }

        return implode('; ', $parts);
    }

    /**
     * Escape a header value for use in a quoted string.
     *
     * @param string $value The value to escape.
     * @return string The escaped value.
     */
    protected function escapeValue(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
