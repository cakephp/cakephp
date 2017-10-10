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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\Helper;

/**
 * UrlHelper class for generating URLs.
 */
class UrlHelper extends Helper
{

    /**
     * Returns a URL based on provided parameters.
     *
     * ### Options:
     *
     * - `escape`: If false, the URL will be returned unescaped, do only use if it is manually
     *    escaped afterwards before being displayed.
     * - `fullBase`: If true, the full base URL will be prepended to the result
     *
     * @param string|array|null $url Either a relative string URL like `/products/view/23` or
     *    an array of URL parameters. Using an array for URLs will allow you to leverage
     *    the reverse routing features of CakePHP.
     * @param array|bool $options Array of options; bool `full` for BC reasons.
     * @return string Full translated URL with base path.
     */
    public function build($url = null, $options = false)
    {
        $defaults = [
            'fullBase' => false,
            'escape' => true,
        ];
        if (!is_array($options)) {
            $options = ['fullBase' => $options];
        }
        $options += $defaults;

        $url = Router::url($url, $options['fullBase']);
        if ($options['escape']) {
            $url = h($url);
        }

        return $url;
    }

    /**
     * Generates URL for given image file.
     *
     * Depending on options passed provides full URL with domain name. Also calls
     * `Helper::assetTimestamp()` to add timestamp to local files.
     *
     * @param string|array $path Path string or URL array
     * @param array $options Options array. Possible keys:
     *   `fullBase` Return full URL with domain name
     *   `pathPrefix` Path prefix for relative URLs
     *   `plugin` False value will prevent parsing path as a plugin
     * @return string Generated URL
     */
    public function image($path, array $options = [])
    {
        $pathPrefix = Configure::read('App.imageBaseUrl');

        return $this->assetUrl($path, $options + compact('pathPrefix'));
    }

    /**
     * Generates URL for given CSS file.
     *
     * Depending on options passed provides full URL with domain name. Also calls
     * `Helper::assetTimestamp()` to add timestamp to local files.
     *
     * @param string|array $path Path string or URL array
     * @param array $options Options array. Possible keys:
     *   `fullBase` Return full URL with domain name
     *   `pathPrefix` Path prefix for relative URLs
     *   `ext` Asset extension to append
     *   `plugin` False value will prevent parsing path as a plugin
     * @return string Generated URL
     */
    public function css($path, array $options = [])
    {
        $pathPrefix = Configure::read('App.cssBaseUrl');
        $ext = '.css';

        return $this->assetUrl($path, $options + compact('pathPrefix', 'ext'));
    }

    /**
     * Generates URL for given javascript file.
     *
     * Depending on options passed provides full URL with domain name. Also calls
     * `Helper::assetTimestamp()` to add timestamp to local files.
     *
     * @param string|array $path Path string or URL array
     * @param array $options Options array. Possible keys:
     *   `fullBase` Return full URL with domain name
     *   `pathPrefix` Path prefix for relative URLs
     *   `ext` Asset extension to append
     *   `plugin` False value will prevent parsing path as a plugin
     * @return string Generated URL
     */
    public function script($path, array $options = [])
    {
        $pathPrefix = Configure::read('App.jsBaseUrl');
        $ext = '.js';

        return $this->assetUrl($path, $options + compact('pathPrefix', 'ext'));
    }

    /**
     * Generates URL for given asset file.
     *
     * Depending on options passed provides full URL with domain name. Also calls
     * `Helper::assetTimestamp()` to add timestamp to local files.
     *
     * @param string|array $path Path string or URL array
     * @param array $options Options array. Possible keys:
     *   `fullBase` Return full URL with domain name
     *   `pathPrefix` Path prefix for relative URLs
     *   `ext` Asset extension to append
     *   `plugin` False value will prevent parsing path as a plugin
     * @return string Generated URL
     */
    public function assetUrl($path, array $options = [])
    {
        if (is_array($path)) {
            return $this->build($path, !empty($options['fullBase']));
        }
        if (strpos($path, '://') !== false || preg_match('/^[a-z]+:/i', $path)) {
            return ltrim($this->build($path), '/');
        }
        if (!array_key_exists('plugin', $options) || $options['plugin'] !== false) {
            list($plugin, $path) = $this->_View->pluginSplit($path, false);
        }
        if (!empty($options['pathPrefix']) && $path[0] !== '/') {
            $path = $options['pathPrefix'] . $path;
        }
        if (!empty($options['ext']) &&
            strpos($path, '?') === false &&
            substr($path, -strlen($options['ext'])) !== $options['ext']
        ) {
            $path .= $options['ext'];
        }
        if (preg_match('|^([a-z0-9]+:)?//|', $path)) {
            return $path;
        }
        if (isset($plugin)) {
            $path = Inflector::underscore($plugin) . '/' . $path;
        }
        $path = $this->_encodeUrl($this->assetTimestamp($this->webroot($path)));

        if (!empty($options['fullBase'])) {
            $path = rtrim(Router::fullBaseUrl(), '/') . '/' . ltrim($path, '/');
        }

        return $path;
    }

    /**
     * Encodes a URL for use in HTML attributes.
     *
     * @param string $url The URL to encode.
     * @return string The URL encoded for both URL & HTML contexts.
     */
    protected function _encodeUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $parts = array_map('rawurldecode', explode('/', $path));
        $parts = array_map('rawurlencode', $parts);
        $encoded = implode('/', $parts);

        return h(str_replace($path, $encoded, $url));
    }

    /**
     * Adds a timestamp to a file based resource based on the value of `Asset.timestamp` in
     * Configure. If Asset.timestamp is true and debug is true, or Asset.timestamp === 'force'
     * a timestamp will be added.
     *
     * @param string $path The file path to timestamp, the path must be inside WWW_ROOT
     * @return string Path with a timestamp added, or not.
     */
    public function assetTimestamp($path)
    {
        $stamp = Configure::read('Asset.timestamp');
        $timestampEnabled = $stamp === 'force' || ($stamp === true && Configure::read('debug'));
        if ($timestampEnabled && strpos($path, '?') === false) {
            $filepath = preg_replace(
                '/^' . preg_quote($this->request->getAttribute('webroot'), '/') . '/',
                '',
                urldecode($path)
            );
            $webrootPath = WWW_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $filepath);
            if (file_exists($webrootPath)) {
                return $path . '?' . filemtime($webrootPath);
            }
            $segments = explode('/', ltrim($filepath, '/'));
            $plugin = Inflector::camelize($segments[0]);
            if (Plugin::loaded($plugin)) {
                unset($segments[0]);
                $pluginPath = Plugin::path($plugin) . 'webroot' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
                if (file_exists($pluginPath)) {
                    return $path . '?' . filemtime($pluginPath);
                }
            }
        }

        return $path;
    }

    /**
     * Checks if a file exists when theme is used, if no file is found default location is returned
     *
     * @param string $file The file to create a webroot path to.
     * @return string Web accessible path to file.
     */
    public function webroot($file)
    {
        $asset = explode('?', $file);
        $asset[1] = isset($asset[1]) ? '?' . $asset[1] : null;
        $webPath = $this->request->getAttribute('webroot') . $asset[0];
        $file = $asset[0];

        if (!empty($this->theme)) {
            $file = trim($file, '/');
            $theme = $this->_inflectThemeName($this->theme) . '/';

            if (DIRECTORY_SEPARATOR === '\\') {
                $file = str_replace('/', '\\', $file);
            }

            if (file_exists(Configure::read('App.wwwRoot') . $theme . $file)) {
                $webPath = $this->request->getAttribute('webroot') . $theme . $asset[0];
            } else {
                $themePath = Plugin::path($this->theme);
                $path = $themePath . 'webroot/' . $file;
                if (file_exists($path)) {
                    $webPath = $this->request->getAttribute('webroot') . $theme . $asset[0];
                }
            }
        }
        if (strpos($webPath, '//') !== false) {
            return str_replace('//', '/', $webPath . $asset[1]);
        }

        return $webPath . $asset[1];
    }

    /**
     * Inflect the theme name to its underscored version.
     *
     * @param string $name Name of the theme which should be inflected.
     * @return string Inflected name of the theme
     */
    protected function _inflectThemeName($name)
    {
        return Inflector::underscore($name);
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }
}
