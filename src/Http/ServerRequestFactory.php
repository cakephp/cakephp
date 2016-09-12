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
namespace Cake\Http;

use Cake\Core\Configure;
use Cake\Network\Session;
use Cake\Utility\Hash;
use Zend\Diactoros\ServerRequestFactory as BaseFactory;

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
        $request = parent::fromGlobals($server, $query, $body, $cookies, $files);
        list($base, $webroot) = static::getBase($request->getUri(), $request->getServerParams());

        $sessionConfig = (array)Configure::read('Session') + [
            'defaults' => 'php',
            'cookiePath' => $webroot
        ];
        $session = Session::create($sessionConfig);
        $request = $request->withAttribute('base', $base)
            ->withAttribute('webroot', $webroot)
            ->withAttribute('session', $session);

        if ($base) {
            $request = static::updatePath($base, $request->getUri());
        }

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

        $uri = static::marshalUriFromServer($server, $headers);
        list($base, $webroot) = static::getBase($uri, $server);

        // Look in PATH_INFO first, as this is the exact value we need prepared
        // by PHP.
        $pathInfo = Hash::get($server, 'PATH_INFO');
        if ($pathInfo) {
            $uri = $uri->withPath($pathInfo);
        } else {
            $uri = static::updatePath($base, $uri);
        }

        // TODO Find an alternate solution to this.
        $uri->base = $base;
        $uri->webroot = $webroot;
        return $uri;
    }

    /**
     * Updates the request URI to remove the base directory.
     *
     * @param string $base The base path to remove.
     * @param \Psr\Http\Message\UriInterface $uri The uri to update.
     * @return \Psr\Http\Message\ServerRequestInterface The modified Uri instance.
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
        $endsWithIndex = '/webroot/index.php';
        $endsWithLength = strlen($endsWithIndex);
        if (strlen($path) >= $endsWithLength &&
            substr($path, -$endsWithLength) === $endsWithIndex
        ) {
            $path = '/';
        }
        return $uri->withPath($path);
    }

    /**
     * Calculate the base directory and webroot directory.
     *
     * This code is a copy/paste from Cake\Network\Request::_base()
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return array An array containing the [baseDir, webroot]
     */
    protected static function getBase($uri, $server)
    {
        $path = $uri->getPath();

        $base = $webroot = $baseUrl = null;
        $config = Configure::read('App');
        extract($config);

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
