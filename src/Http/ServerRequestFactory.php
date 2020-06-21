<?php
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
namespace Cake\Http;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Laminas\Diactoros\ServerRequestFactory as BaseFactory;

/**
 * Factory for making ServerRequest instances.
 *
 * This subclass adds in CakePHP specific behavior to populate
 * the basePath and webroot attributes. Furthermore the Uri's path
 * is corrected to only contain the 'virtual' path for the request.
 */
abstract class ServerRequestFactory extends BaseFactory
{
    /**
     * {@inheritDoc}
     */
    public static function fromGlobals(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ) {
        $server = static::normalizeServer($server ?: $_SERVER);
        $uri = static::createUri($server);
        $sessionConfig = (array)Configure::read('Session') + [
            'defaults' => 'php',
            'cookiePath' => $uri->webroot,
        ];
        $session = Session::create($sessionConfig);
        $request = new ServerRequest([
            'environment' => $server,
            'uri' => $uri,
            'files' => $files ?: $_FILES,
            'cookies' => $cookies ?: $_COOKIE,
            'query' => $query ?: $_GET,
            'post' => $body ?: $_POST,
            'webroot' => $uri->webroot,
            'base' => $uri->base,
            'session' => $session,
            'mergeFilesAsObjects' => Configure::read('App.uploadedFilesAsObjects', false),
        ]);

        return $request;
    }

    /**
     * Create a new Uri instance from the provided server data.
     *
     * @param array $server Array of server data to build the Uri from.
     *   $_SERVER will be added into the $server parameter.
     * @return \Psr\Http\Message\UriInterface New instance.
     */
    public static function createUri(array $server = [])
    {
        $server += $_SERVER;
        $server = static::normalizeServer($server);
        $headers = static::marshalHeaders($server);

        return static::marshalUriFromServer($server, $headers);
    }

    /**
     * Build a UriInterface object.
     *
     * Add in some CakePHP specific logic/properties that help
     * preserve backwards compatibility.
     *
     * @param array $server The server parameters.
     * @param array $headers The normalized headers
     * @return \Psr\Http\Message\UriInterface a constructed Uri
     */
    public static function marshalUriFromServer(array $server, array $headers)
    {
        $uri = parent::marshalUriFromServer($server, $headers);
        list($base, $webroot) = static::getBase($uri, $server);

        // Look in PATH_INFO first, as this is the exact value we need prepared
        // by PHP.
        $pathInfo = Hash::get($server, 'PATH_INFO');
        if ($pathInfo) {
            $uri = $uri->withPath($pathInfo);
        } else {
            $uri = static::updatePath($base, $uri);
        }

        if (!$uri->getHost()) {
            $uri = $uri->withHost('localhost');
        }

        // Splat on some extra attributes to save
        // some method calls.
        $uri->base = $base;
        $uri->webroot = $webroot;

        return $uri;
    }

    /**
     * Updates the request URI to remove the base directory.
     *
     * @param string $base The base path to remove.
     * @param \Psr\Http\Message\UriInterface $uri The uri to update.
     * @return \Psr\Http\Message\UriInterface The modified Uri instance.
     */
    protected static function updatePath($base, $uri)
    {
        $path = $uri->getPath();
        if (strlen($base) > 0 && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        if ($path === '/index.php' && $uri->getQuery()) {
            $path = $uri->getQuery();
        }
        if (empty($path) || $path === '/' || $path === '//' || $path === '/index.php') {
            $path = '/';
        }
        $endsWithIndex = '/' . (Configure::read('App.webroot') ?: 'webroot') . '/index.php';
        $endsWithLength = strlen($endsWithIndex);
        if (
            strlen($path) >= $endsWithLength &&
            substr($path, -$endsWithLength) === $endsWithIndex
        ) {
            $path = '/';
        }

        return $uri->withPath($path);
    }

    /**
     * Calculate the base directory and webroot directory.
     *
     * @param \Psr\Http\Message\UriInterface $uri The Uri instance.
     * @param array $server The SERVER data to use.
     * @return array An array containing the [baseDir, webroot]
     */
    protected static function getBase($uri, $server)
    {
        $config = (array)Configure::read('App') + [
            'base' => null,
            'webroot' => null,
            'baseUrl' => null,
        ];
        $base = $config['base'];
        $baseUrl = $config['baseUrl'];
        $webroot = $config['webroot'];

        if ($base !== false && $base !== null) {
            return [$base, $base . '/'];
        }

        if (!$baseUrl) {
            $base = dirname(Hash::get($server, 'PHP_SELF'));
            // Clean up additional / which cause following code to fail..
            $base = preg_replace('#/+#', '/', $base);

            $indexPos = strpos($base, '/' . $webroot . '/index.php');
            if ($indexPos !== false) {
                $base = substr($base, 0, $indexPos) . '/' . $webroot;
            }
            if ($webroot === basename($base)) {
                $base = dirname($base);
            }

            if ($base === DIRECTORY_SEPARATOR || $base === '.') {
                $base = '';
            }
            $base = implode('/', array_map('rawurlencode', explode('/', $base)));

            return [$base, $base . '/'];
        }

        $file = '/' . basename($baseUrl);
        $base = dirname($baseUrl);

        if ($base === DIRECTORY_SEPARATOR || $base === '.') {
            $base = '';
        }
        $webrootDir = $base . '/';

        $docRoot = Hash::get($server, 'DOCUMENT_ROOT');
        $docRootContainsWebroot = strpos($docRoot, $webroot);

        if (!empty($base) || !$docRootContainsWebroot) {
            if (strpos($webrootDir, '/' . $webroot . '/') === false) {
                $webrootDir .= $webroot . '/';
            }
        }

        return [$base . $file, $webrootDir];
    }
}
