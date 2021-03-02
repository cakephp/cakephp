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
namespace Cake\Routing\Middleware;

use Cake\Core\Plugin;
use Cake\Filesystem\File;
use Cake\Utility\Inflector;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles serving plugin assets in development mode.
 *
 * This should not be used in production environments as it
 * has sub-optimal performance when compared to serving files
 * with a real webserver.
 */
class AssetMiddleware
{
    /**
     * The amount of time to cache the asset.
     *
     * @var string
     */
    protected $cacheTime = '+1 day';

    /**
     * A extension to content type mapping for plain text types.
     *
     * Because finfo doesn't give useful information for plain text types,
     * we have to handle that here.
     *
     * @var array
     */
    protected $typeMap = [
        'css' => 'text/css',
        'json' => 'application/json',
        'js' => 'application/javascript',
        'ico' => 'image/x-icon',
        'eot' => 'application/vnd.ms-fontobject',
        'svg' => 'image/svg+xml',
        'html' => 'text/html',
        'rss' => 'application/rss+xml',
        'xml' => 'application/xml',
    ];

    /**
     * Constructor.
     *
     * @param array $options The options to use
     */
    public function __construct(array $options = [])
    {
        if (!empty($options['cacheTime'])) {
            $this->cacheTime = $options['cacheTime'];
        }
        if (!empty($options['types'])) {
            $this->typeMap = array_merge($this->typeMap, $options['types']);
        }
    }

    /**
     * Serve assets if the path matches one.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke($request, $response, $next)
    {
        $url = $request->getUri()->getPath();
        if (strpos($url, '..') !== false || strpos($url, '.') === false) {
            return $next($request, $response);
        }

        if (strpos($url, '/.') !== false) {
            return $next($request, $response);
        }

        $assetFile = $this->_getAssetFile($url);
        if ($assetFile === null || !file_exists($assetFile)) {
            return $next($request, $response);
        }

        $file = new File($assetFile);
        $modifiedTime = $file->lastChange();
        if ($this->isNotModified($request, $file)) {
            $headers = $response->getHeaders();
            $headers['Last-Modified'] = date(DATE_RFC850, $modifiedTime);

            return new Response('php://memory', 304, $headers);
        }

        return $this->deliverAsset($request, $response, $file);
    }

    /**
     * Check the not modified header.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to check.
     * @param \Cake\Filesystem\File $file The file object to compare.
     * @return bool
     */
    protected function isNotModified($request, $file)
    {
        $modifiedSince = $request->getHeaderLine('If-Modified-Since');
        if (!$modifiedSince) {
            return false;
        }

        return strtotime($modifiedSince) === $file->lastChange();
    }

    /**
     * Builds asset file path based off url
     *
     * @param string $url Asset URL
     * @return string|null Absolute path for asset file, null on failure
     */
    protected function _getAssetFile($url)
    {
        $parts = explode('/', ltrim($url, '/'));
        $pluginPart = [];
        for ($i = 0; $i < 2; $i++) {
            if (!isset($parts[$i])) {
                break;
            }
            $pluginPart[] = Inflector::camelize($parts[$i]);
            $plugin = implode('/', $pluginPart);
            if ($plugin && Plugin::isLoaded($plugin)) {
                $parts = array_slice($parts, $i + 1);
                $fileFragment = implode(DIRECTORY_SEPARATOR, $parts);
                $pluginWebroot = Plugin::path($plugin) . 'webroot' . DIRECTORY_SEPARATOR;

                return $pluginWebroot . $fileFragment;
            }
        }

        return null;
    }

    /**
     * Sends an asset file to the client
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request object to use.
     * @param \Psr\Http\Message\ResponseInterface $response The response object to use.
     * @param \Cake\Filesystem\File $file The file wrapper for the file.
     * @return \Psr\Http\Message\ResponseInterface The response with the file & headers.
     */
    protected function deliverAsset(ServerRequestInterface $request, ResponseInterface $response, $file)
    {
        $contentType = $this->getType($file);
        $modified = $file->lastChange();
        $expire = strtotime($this->cacheTime);
        $maxAge = $expire - time();

        $stream = new Stream(fopen($file->path, 'rb'));

        return $response->withBody($stream)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Cache-Control', 'public,max-age=' . $maxAge)
            ->withHeader('Date', gmdate('D, d M Y H:i:s \G\M\T', time()))
            ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', $modified))
            ->withHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', $expire));
    }

    /**
     * Return the type from a File object
     *
     * @param File $file The file from which you get the type
     * @return string
     */
    protected function getType($file)
    {
        $extension = $file->ext();
        if (isset($this->typeMap[$extension])) {
            return $this->typeMap[$extension];
        }

        return $file->mime() ?: 'application/octet-stream';
    }
}
