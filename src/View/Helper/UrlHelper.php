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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\Helper;

/**
 * UrlHelper class for generating urls.
 */
class UrlHelper extends Helper
{

    /**
     * Returns a URL based on provided parameters.
     *
     * @param string|array $url Either a relative string url like `/products/view/23` or
     *    an array of URL parameters. Using an array for URLs will allow you to leverage
     *    the reverse routing features of CakePHP.
     * @param bool $full If true, the full base URL will be prepended to the result
     * @return string Full translated URL with base path.
     */
    public function build($url = null, $full = false)
    {
        return h(Router::url($url, $full));
    }

    /**
     * Generate URL for given asset file. Depending on options passed provides full URL with domain name.
     * Also calls Helper::assetTimestamp() to add timestamp to local files
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
        if (strpos($path, '://') !== false) {
            return $path;
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
                '/^' . preg_quote($this->request->webroot, '/') . '/',
                '',
                urldecode($path)
            );
            $webrootPath = WWW_ROOT . str_replace('/', DS, $filepath);
            if (file_exists($webrootPath)) {
                //@codingStandardsIgnoreStart
                return $path . '?' . @filemtime($webrootPath);
                //@codingStandardsIgnoreEnd
            }
            $segments = explode('/', ltrim($filepath, '/'));
            $plugin = Inflector::camelize($segments[0]);
            if (Plugin::loaded($plugin)) {
                unset($segments[0]);
                $pluginPath = Plugin::path($plugin) . 'webroot' . DS . implode(DS, $segments);
                //@codingStandardsIgnoreStart
                return $path . '?' . @filemtime($pluginPath);
                //@codingStandardsIgnoreEnd
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
        $webPath = $this->request->webroot . $asset[0];
        $file = $asset[0];

        if (!empty($this->theme)) {
            $file = trim($file, '/');
            $theme = $this->_inflectThemeName($this->theme) . '/';

            if (DS === '\\') {
                $file = str_replace('/', '\\', $file);
            }

            if (file_exists(Configure::read('App.wwwRoot') . $theme . $file)) {
                $webPath = $this->request->webroot . $theme . $asset[0];
            } else {
                $themePath = Plugin::path($this->theme);
                $path = $themePath . 'webroot/' . $file;
                if (file_exists($path)) {
                    $webPath = $this->request->webroot . $theme . $asset[0];
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
