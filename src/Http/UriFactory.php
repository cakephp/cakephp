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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 *
 * Parts of this file are derived from laminas-diactoros
 * @copyright Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC. (https://getlaminas.org/)
 * @license   https://github.com/laminas/laminas-diactoros/blob/3.9.x/LICENSE.md New BSD License
 */
namespace Cake\Http;

use Cake\Core\Configure;
use InvalidArgumentException;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * Factory class for creating uri instances.
 */
class UriFactory implements UriFactoryInterface
{
    /**
     * Create a new URI.
     *
     * @param string $uri The URI to parse.
     * @throws \InvalidArgumentException If the given URI cannot be parsed.
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }

    /**
     * Get a new Uri instance and base info from the provided server data.
     *
     * @param array|null $server Array of server data to build the Uri from.
     *   $_SERVER will be used if $server parameter is null.
     * @return array{uri: \Psr\Http\Message\UriInterface, base: string, webroot: string}
     */
    public static function marshalUriAndBaseFromSapi(?array $server = null): array
    {
        $server ??= $_SERVER;
        $uri = static::createFromSapi($server);
        ['base' => $base, 'webroot' => $webroot] = static::getBase($uri, $server);

        $uri = static::updatePath($base, $uri);

        if (!$uri->getHost()) {
            $uri = $uri->withHost('localhost');
        }

        return ['uri' => $uri, 'base' => $base, 'webroot' => $webroot];
    }

    /**
     * Create a Uri instance from SAPI parameters.
     *
     * @param array $server The SERVER data to use.
     * @return \Psr\Http\Message\UriInterface
     */
    protected static function createFromSapi(array $server): UriInterface
    {
        $https = $server['HTTPS'] ?? $server['https'] ?? false;
        if (!is_bool($https) && !is_string($https)) {
            throw new InvalidArgumentException(sprintf(
                'SAPI HTTPS value must be a string or boolean; received `%s`.',
                get_debug_type($https),
            ));
        }
        $isHttps = is_bool($https) ? $https : strtolower($https) === 'on';
        $uri = new Uri()->withScheme($isHttps ? 'https' : 'http');

        [$host, $port] = static::marshalHostAndPort($server);
        if ($host !== '') {
            $uri = $uri->withHost($host);
            if ($port !== null) {
                $uri = $uri->withPort($port);
            }
        }

        $path = explode('?', static::marshalRequestPath($server), 2)[0];
        $fragment = '';
        if (str_contains($path, '#')) {
            [$path, $fragment] = explode('#', $path, 2);
        }

        $query = '';
        if (isset($server['QUERY_STRING']) && is_scalar($server['QUERY_STRING'])) {
            $query = ltrim((string)$server['QUERY_STRING'], '?');
        }

        return $uri
            ->withPath($path)
            ->withFragment($fragment)
            ->withQuery($query);
    }

    /**
     * Get the host and port from the Host header or SERVER_NAME/SERVER_PORT.
     *
     * @param array $server The SERVER data to use.
     * @return array{0: string, 1: int|null}
     */
    protected static function marshalHostAndPort(array $server): array
    {
        // Apache prefixes variables added by rewrite rules with `REDIRECT_`.
        $host = $server['HTTP_HOST'] ?? $server['REDIRECT_HTTP_HOST'] ?? null;
        // Ignore malformed host headers. Whitespace is invalid in hostnames and a comma
        // indicates multiple Host headers were sent, which is not legal.
        if (is_string($host) && $host !== '' && !preg_match('/[\t ,]/', $host)) {
            $port = null;
            if (preg_match('/:(\d+)$/', $host, $matches)) {
                $host = substr($host, 0, -(strlen($matches[1]) + 1));
                $port = (int)$matches[1];
            }

            return [$host, $port];
        }

        if (!isset($server['SERVER_NAME'])) {
            return ['', null];
        }

        $host = (string)$server['SERVER_NAME'];
        $port = isset($server['SERVER_PORT']) ? (int)$server['SERVER_PORT'] : null;
        if (!isset($server['SERVER_ADDR']) || !preg_match('/^\[[0-9a-fA-F:]+\]$/', $host)) {
            return [$host, $port];
        }

        // Some clients send an IPv6 address in SERVER_NAME where the last
        // segment could be misinterpreted as the port.
        $host = '[' . $server['SERVER_ADDR'] . ']';
        $port ??= 80;
        $separator = strrpos($host, ':');
        if ($separator !== false && $port . ']' === substr($host, $separator + 1)) {
            $port = null;
        }

        return [$host, $port];
    }

    /**
     * Detect the request path from the SAPI parameters.
     *
     * Uses the IIS7 UrlRewrite environment, `REQUEST_URI` or `ORIG_PATH_INFO`.
     *
     * @param array $server The SERVER data to use.
     * @return string
     */
    protected static function marshalRequestPath(array $server): string
    {
        // IIS7 with URL Rewrite: use the unencoded url to avoid the double slash problem.
        $unencodedUrl = $server['UNENCODED_URL'] ?? '';
        if (($server['IIS_WasUrlRewritten'] ?? null) === '1' && is_string($unencodedUrl) && $unencodedUrl !== '') {
            return $unencodedUrl;
        }

        $requestUri = $server['REQUEST_URI'] ?? null;
        if (is_string($requestUri)) {
            return (string)preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
        }

        $origPathInfo = $server['ORIG_PATH_INFO'] ?? '';
        if (!is_string($origPathInfo) || $origPathInfo === '') {
            return '/';
        }

        return $origPathInfo;
    }

    /**
     * Updates the request URI to remove the base directory.
     *
     * @param string $base The base path to remove.
     * @param \Psr\Http\Message\UriInterface $uri The uri to update.
     * @return \Psr\Http\Message\UriInterface
     */
    protected static function updatePath(string $base, UriInterface $uri): UriInterface
    {
        $path = $uri->getPath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        // App.baseUrl is meant to be set only when URL rewriting is not used.
        if (!Configure::read('App.baseUrl')) {
            if ($path === '' || $path === '//') {
                $path = '/';
            }

            return $uri->withPath($path);
        }

        if ($path === '/index.php' && $uri->getQuery()) {
            $path = $uri->getQuery();
        }
        if (in_array($path, ['', '//', '/index.php'], true)) {
            $path = '/';
        }

        // Check for $webroot/index.php at the start and end of the path.
        $search = '';
        if (str_starts_with($path, '/')) {
            $search .= '/';
        }
        $search .= (Configure::read('App.webroot') ?: 'webroot') . '/index.php';
        if (str_starts_with($path, $search)) {
            $path = substr($path, strlen($search));
        } elseif (str_ends_with($path, $search)) {
            $path = '/';
        }
        if (!$path) {
            $path = '/';
        }

        return $uri->withPath($path);
    }

    /**
     * Calculate the base directory and webroot directory.
     *
     * @param \Psr\Http\Message\UriInterface $uri The Uri instance.
     * @param array $server The SERVER data to use.
     * @return array{base: string, webroot: string} An array containing the base and webroot paths.
     */
    protected static function getBase(UriInterface $uri, array $server): array
    {
        $config = (array)Configure::read('App') + [
            'base' => null,
            'webroot' => null,
            'baseUrl' => null,
        ];
        $base = $config['base'];
        $baseUrl = $config['baseUrl'];
        $webroot = (string)$config['webroot'];

        if ($base !== false && $base !== null) {
            return ['base' => $base, 'webroot' => $base . '/'];
        }

        if (!$baseUrl) {
            $phpSelf = $server['PHP_SELF'] ?? null;
            if ($phpSelf === null) {
                return ['base' => '', 'webroot' => '/'];
            }

            $base = dirname($server['PHP_SELF'] ?? DIRECTORY_SEPARATOR);
            // Clean up additional / which cause following code to fail..
            $base = (string)preg_replace('#/+#', '/', $base);

            $indexPos = strpos($base, '/index.php');
            if ($indexPos !== false) {
                $base = substr($base, 0, $indexPos);
            }
            if ($webroot === basename($base)) {
                $base = dirname($base);
            }

            if ($base === DIRECTORY_SEPARATOR || $base === '.') {
                $base = '';
            }
            $base = implode('/', array_map('rawurlencode', explode('/', $base)));

            return ['base' => $base, 'webroot' => $base . '/'];
        }

        $file = '/' . basename($baseUrl);
        $base = dirname($baseUrl);

        if ($base === DIRECTORY_SEPARATOR || $base === '.') {
            $base = '';
        }
        $webrootDir = $base . '/';

        $docRoot = $server['DOCUMENT_ROOT'] ?? '';
        if (
            ($base || !str_contains($docRoot, $webroot))
            && !str_contains($webrootDir, '/' . $webroot . '/')
        ) {
            $webrootDir .= $webroot . '/';
        }

        return ['base' => $base . $file, 'webroot' => $webrootDir];
    }
}
