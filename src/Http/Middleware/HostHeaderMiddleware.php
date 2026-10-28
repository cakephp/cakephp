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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\InternalErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Validates request URI hosts to prevent Host header injection attacks.
 *
 * Add this middleware after the error handler and before middleware that uses
 * the request host, including assets, routing, and redirects.
 *
 * Trusted hosts are configured using delimiterless, case-insensitive regular
 * expressions. Include anchors to match the entire hostname. For example:
 *
 * ```
 * new HostHeaderMiddleware(trustedHostPatterns: [
 *     '^example\\.com$',
 *     '^[a-z0-9-]+\\.preview\\.example\\.com$',
 *     '^localhost$',
 * ]);
 * ```
 *
 * With no arguments, reads `App.trustedHostPatterns` and `App.fullBaseUrl`.
 * Nonempty patterns define the complete allowlist. Without patterns, only the
 * host in `App.fullBaseUrl` is trusted. Schemes and ports are not compared.
 */
class HostHeaderMiddleware implements MiddlewareInterface
{
    /**
     * Trusted host patterns, validated when host checks are enabled.
     */
    protected mixed $trustedHostPatterns;

    /**
     * Fallback application URL, validated when no trusted host patterns are configured.
     */
    protected mixed $fullBaseUrl;

    /**
     * Resolve host configuration from arguments or application settings.
     *
     * @param array<string>|null $trustedHostPatterns Patterns, or null to read `App.trustedHostPatterns`.
     *   An empty array overrides configured patterns and selects the full base URL fallback.
     * @param string|null $fullBaseUrl An absolute HTTP(S) URL, or null to read `App.fullBaseUrl`.
     * @param bool $disableOnDebug Whether to skip host checks when debug is enabled.
     */
    public function __construct(
        ?array $trustedHostPatterns = null,
        ?string $fullBaseUrl = null,
        protected bool $disableOnDebug = true,
    ) {
        $this->trustedHostPatterns = $trustedHostPatterns ?? Configure::read('App.trustedHostPatterns', []);
        $this->fullBaseUrl = $fullBaseUrl ?? Configure::read('App.fullBaseUrl');
    }

    /**
     * Validate the request URI host before delegating to the request handler.
     *
     * Regular expression warnings are converted into configuration exceptions.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface The handler's response.
     * @throws \Cake\Http\Exception\BadRequestException When the request host is empty or untrusted.
     * @throws \Cake\Http\Exception\InternalErrorException When host configuration is invalid.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->disableOnDebug && Configure::read('debug')) {
            return $handler->handle($request);
        }

        $patterns = $this->getTrustedHostPatterns();
        $host = $request->getUri()->getHost();
        $trusted = false;
        foreach ($patterns as $pattern) {
            // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Regex failures become configuration exceptions below.
            $result = @preg_match('{' . $pattern . '}iD', $host);
            if ($result === false) {
                throw new InternalErrorException(
                    'Unable to evaluate trusted host pattern: ' . preg_last_error_msg(),
                );
            }
            $trusted = $trusted || $result === 1;
        }

        if ($host === '' || !$trusted) {
            throw new BadRequestException('Invalid Host header. Request host is not trusted.');
        }

        return $handler->handle($request);
    }

    /**
     * Get trusted host patterns or derive an exact match from the full base URL.
     *
     * @return array<string> The trusted host patterns.
     * @throws \Cake\Http\Exception\InternalErrorException When host configuration is invalid.
     */
    protected function getTrustedHostPatterns(): array
    {
        $patterns = $this->trustedHostPatterns;
        if ($patterns !== null && $patterns !== []) {
            if (!is_array($patterns)) {
                throw new InternalErrorException('Trusted host patterns must be an array of non-empty strings.');
            }
            foreach ($patterns as $pattern) {
                if (!is_string($pattern) || $pattern === '') {
                    throw new InternalErrorException('Trusted host patterns must be an array of non-empty strings.');
                }
            }

            return $patterns;
        }

        $url = is_string($this->fullBaseUrl) ? parse_url($this->fullBaseUrl) : false;
        if (
            $url === false
            || !isset($url['host'], $url['scheme'])
            || $url['host'] === ''
            || !in_array(strtolower($url['scheme']), ['http', 'https'], true)
        ) {
            throw new InternalErrorException(
                'Configure App.trustedHostPatterns or a valid absolute HTTP(S) URL in App.fullBaseUrl.',
            );
        }

        return ['^' . preg_quote($url['host'], '{') . '\\z'];
    }
}
