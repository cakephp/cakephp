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
        list($base, $webroot) = static::getBase($request);
        $request = $request->withAttribute('base', $base)
            ->withAttribute('webroot', $webroot);
        if ($base) {
            $request = static::updatePath($base, $request);
        }
        return $request;
    }

    /**
     * Updates the request URI to remove the base directory.
     *
     * @param string $base The base path to remove.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to modify.
     * @return \Psr\Http\Message\ServerRequestInterface The modified request.
     */
    protected static function updatePath($base, $request)
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        if (strlen($base) > 0 && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
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
        return $request->withUri($uri->withPath($path));
    }

    /**
     * Calculate the base directory and webroot directory.
     *
     * This code is a copy/paste from Cake\Network\Request::_base()
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return array An array containing the [baseDir, webroot]
     */
    protected static function getBase($request)
    {
        $path = $request->getUri()->getPath();
        $server = $request->getServerParams();

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
