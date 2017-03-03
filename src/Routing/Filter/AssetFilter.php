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
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing\Filter;

use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\DispatcherFilter;
use Cake\Utility\Inflector;

/**
 * Filters a request and tests whether it is a file in the webroot folder or not and
 * serves the file to the client if appropriate.
 */
class AssetFilter extends DispatcherFilter
{

    /**
     * Default priority for all methods in this filter
     * This filter should run before the request gets parsed by router
     *
     * @var int
     */
    protected $_priority = 9;

    /**
     * The amount of time to cache the asset.
     *
     * @var string
     */
    protected $_cacheTime = '+1 day';

    /**
     *
     * Constructor.
     *
     * @param array $config Array of config.
     */
    public function __construct($config = [])
    {
        if (!empty($config['cacheTime'])) {
            $this->_cacheTime = $config['cacheTime'];
        }
        parent::__construct($config);
    }

    /**
     * Checks if a requested asset exists and sends it to the browser
     *
     * @param \Cake\Event\Event $event Event containing the request and response object
     * @return \Cake\Http\Response|null If the client is requesting a recognized asset, null otherwise
     * @throws \Cake\Network\Exception\NotFoundException When asset not found
     */
    public function beforeDispatch(Event $event)
    {
        /* @var \Cake\Http\ServerRequest $request */
        $request = $event->getData('request');

        $url = urldecode($request->url);
        if (strpos($url, '..') !== false || strpos($url, '.') === false) {
            return null;
        }

        $assetFile = $this->_getAssetFile($url);
        if ($assetFile === null || !file_exists($assetFile)) {
            return null;
        }
        /* @var \Cake\Http\Response $response */
        $response = $event->getData('response');
        $event->stopPropagation();

        $response->modified(filemtime($assetFile));
        if ($response->checkNotModified($request)) {
            return $response;
        }

        $pathSegments = explode('.', $url);
        $ext = array_pop($pathSegments);

        return $this->_deliverAsset($request, $response, $assetFile, $ext);
    }

    /**
     * Builds asset file path based off url
     *
     * @param string $url Asset URL
     * @return string Absolute path for asset file
     */
    protected function _getAssetFile($url)
    {
        $parts = explode('/', $url);
        $pluginPart = [];
        for ($i = 0; $i < 2; $i++) {
            if (!isset($parts[$i])) {
                break;
            }
            $pluginPart[] = Inflector::camelize($parts[$i]);
            $plugin = implode('/', $pluginPart);
            if ($plugin && Plugin::loaded($plugin)) {
                $parts = array_slice($parts, $i + 1);
                $fileFragment = implode(DIRECTORY_SEPARATOR, $parts);
                $pluginWebroot = Plugin::path($plugin) . 'webroot' . DIRECTORY_SEPARATOR;

                return $pluginWebroot . $fileFragment;
            }
        }
    }

    /**
     * Sends an asset file to the client
     *
     * @param \Cake\Http\ServerRequest $request The request object to use.
     * @param \Cake\Http\Response $response The response object to use.
     * @param string $assetFile Path to the asset file in the file system
     * @param string $ext The extension of the file to determine its mime type
     * @return \Cake\Http\Response The updated response.
     */
    protected function _deliverAsset(ServerRequest $request, Response $response, $assetFile, $ext)
    {
        $compressionEnabled = $response->compress();
        if ($response->type($ext) === $ext) {
            $contentType = 'application/octet-stream';
            $agent = $request->env('HTTP_USER_AGENT');
            if (preg_match('%Opera(/| )([0-9].[0-9]{1,2})%', $agent) || preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent)) {
                $contentType = 'application/octetstream';
            }
            $response->type($contentType);
        }
        if (!$compressionEnabled) {
            $response->header('Content-Length', filesize($assetFile));
        }
        $response->cache(filemtime($assetFile), $this->_cacheTime);
        $response->file($assetFile);

        return $response;
    }
}
