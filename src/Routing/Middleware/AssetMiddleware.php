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
namespace Cake\Routing\Middleware;

use Cake\Core\Plugin;
use Cake\Http\Response;
use Cake\Utility\Inflector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SplFileInfo;
use Zend\Diactoros\Stream;

/**
 * Handles serving plugin assets in development mode.
 *
 * This should not be used in production environments as it
 * has sub-optimal performance when compared to serving files
 * with a real webserver.
 */
class AssetMiddleware implements MiddlewareInterface
{
    /**
     * The amount of time to cache the asset.
     *
     * @var string
     */
    protected $cacheTime = '+1 day';

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
    }

    /**
     * Serve assets if the path matches one.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $url = $request->getUri()->getPath();
        if (strpos($url, '..') !== false || strpos($url, '.') === false) {
            return $handler->handle($request);
        }

        if (strpos($url, '/.') !== false) {
            return $handler->handle($request);
        }

        $assetFile = $this->_getAssetFile($url);
        if ($assetFile === null || !file_exists($assetFile)) {
            return $handler->handle($request);
        }

        $file = new SplFileInfo($assetFile);
        $modifiedTime = $file->getMTime();
        if ($this->isNotModified($request, $file)) {
            return (new Response())
                ->withStringBody('')
                ->withStatus(304)
                ->withHeader(
                    'Last-Modified',
                    date(DATE_RFC850, $modifiedTime)
                );
        }

        return $this->deliverAsset($request, $file);
    }

    /**
     * Check the not modified header.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to check.
     * @param \SplFileInfo $file The file object to compare.
     * @return bool
     */
    protected function isNotModified(ServerRequestInterface $request, SplFileInfo $file): bool
    {
        $modifiedSince = $request->getHeaderLine('If-Modified-Since');
        if (!$modifiedSince) {
            return false;
        }

        return strtotime($modifiedSince) === $file->getMTime();
    }

    /**
     * Builds asset file path based off url
     *
     * @param string $url Asset URL
     * @return string|null Absolute path for asset file, null on failure
     */
    protected function _getAssetFile(string $url): ?string
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
     * @param \SplFileInfo $file The file wrapper for the file.
     * @return \Cake\Http\Response The response with the file & headers.
     */
    protected function deliverAsset(ServerRequestInterface $request, SplFileInfo $file): Response
    {
        $stream = new Stream(fopen($file->getPathname(), 'rb'));

        $response = new Response(['stream' => $stream]);

        $contentType = $response->getMimeType($file->getExtension()) ?: 'application/octet-stream';
        $modified = $file->getMTime();
        $expire = strtotime($this->cacheTime);
        $maxAge = $expire - time();

        return $response
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Cache-Control', 'public,max-age=' . $maxAge)
            ->withHeader('Date', gmdate('D, j M Y G:i:s \G\M\T', time()))
            ->withHeader('Last-Modified', gmdate('D, j M Y G:i:s \G\M\T', $modified))
            ->withHeader('Expires', gmdate('D, j M Y G:i:s \G\M\T', $expire));
    }
}
