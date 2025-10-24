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
 */
namespace Cake\Http;

use Cake\Core\Configure;
use Laminas\Diactoros\Uri;
use Laminas\Diactoros\UriFactory as DiactorosUriFactory;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use function Laminas\Diactoros\marshalHeadersFromSapi;

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
     * @return array
     * @phpstan-return array{uri: \Psr\Http\Message\UriInterface, base: string, webroot: string}
     */
    public static function marshalUriAndBaseFromSapi(?array $server = null): array
    {
        $server ??= $_SERVER;
        $headers = marshalHeadersFromSapi($server);

        $uri = DiactorosUriFactory::createFromSapi($server, $headers);
        ['base' => $base, 'webroot' => $webroot] = static::getBase($uri, $server);

        $uri = static::updatePath($base, $uri);

        if (!$uri->getHost()) {
            $uri = $uri->withHost('localhost');
        }

        return ['uri' => $uri, 'base' => $base, 'webroot' => $webroot];
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
        if ($path === '' || $path === '//' || $path === '/index.php') {
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
     * @return array An array containing the base and webroot paths.
     * @phpstan-return array{base: string, webroot: string}
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
