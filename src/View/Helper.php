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
 * @since         0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Core\Plugin;
use Cake\Event\EventListener;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Abstract base class for all other Helpers in CakePHP.
 * Provides common methods and features.
 *
 * ## Callback methods
 *
 * Helpers support a number of callback methods. These callbacks allow you to hook into
 * the various view lifecycle events and either modify existing view content or perform
 * other application specific logic. The events are not implemented by this base class, as
 * implementing a callback method subscribes a helper to the related event. The callback methods
 * are as follows:
 *
 * - `beforeRender(Event $event, $viewFile)` - beforeRender is called before the view file is rendered.
 * - `afterRender(Event $event, $viewFile)` - afterRender is called after the view file is rendered
 *   but before the layout has been rendered.
 * - beforeLayout(Event $event, $layoutFile)` - beforeLayout is called before the layout is rendered.
 * - `afterLayout(Event $event, $layoutFile)` - afterLayout is called after the layout has rendered.
 * - `beforeRenderFile(Event $event, $viewFile)` - Called before any view fragment is rendered.
 * - `afterRenderFile(Event $event, $viewFile, $content)` - Called after any view fragment is rendered.
 *   If a listener returns a non-null value, the output of the rendered file will be set to that.
 *
 */
class Helper implements EventListener {

	use InstanceConfigTrait;

/**
 * List of helpers used by this helper
 *
 * @var array
 */
	public $helpers = array();

/**
 * Default config for this helper.
 *
 * @var array
 */
	protected $_defaultConfig = [];

/**
 * A helper lookup table used to lazy load helper objects.
 *
 * @var array
 */
	protected $_helperMap = array();

/**
 * The current theme name if any.
 *
 * @var string
 */
	public $theme = null;

/**
 * Request object
 *
 * @var \Cake\Network\Request
 */
	public $request = null;

/**
 * Plugin path
 *
 * @var string
 */
	public $plugin = null;

/**
 * Holds the fields array('field_name' => array('type' => 'string', 'length' => 100),
 * primaryKey and validates array('field_name')
 *
 * @var array
 */
	public $fieldset = array();

/**
 * Holds tag templates.
 *
 * @var array
 */
	public $tags = array();

/**
 * The View instance this helper is attached to
 *
 * @var View
 */
	protected $_View;

/**
 * Minimized attributes
 *
 * @var array
 */
	protected $_minimizedAttributes = array(
		'compact', 'checked', 'declare', 'readonly', 'disabled', 'selected',
		'defer', 'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize',
		'autoplay', 'controls', 'loop', 'muted', 'required', 'novalidate', 'formnovalidate'
	);

/**
 * Format to attribute
 *
 * @var string
 */
	protected $_attributeFormat = '%s="%s"';

/**
 * Format to attribute
 *
 * @var string
 */
	protected $_minimizedAttributeFormat = '%s="%s"';

/**
 * Default Constructor
 *
 * @param View $View The View this helper is being attached to.
 * @param array $config Configuration settings for the helper.
 */
	public function __construct(View $View, array $config = array()) {
		$this->_View = $View;
		$this->request = $View->request;

		$this->config($config);

		if (!empty($this->helpers)) {
			$this->_helperMap = $View->helpers()->normalizeArray($this->helpers);
		}
	}

/**
 * Provide non fatal errors on missing method calls.
 *
 * @param string $method Method to invoke
 * @param array $params Array of params for the method.
 * @return void
 */
	public function __call($method, $params) {
		trigger_error(sprintf('Method %1$s::%2$s does not exist', get_class($this), $method), E_USER_WARNING);
	}

/**
 * Lazy loads helpers.
 *
 * @param string $name Name of the property being accessed.
 * @return mixed Helper or property found at $name
 * @deprecated Accessing request properties through this method is deprecated and will be removed in 3.0.
 */
	public function __get($name) {
		if (isset($this->_helperMap[$name]) && !isset($this->{$name})) {
			$settings = array_merge((array)$this->_helperMap[$name]['config'], array('enabled' => false));
			$this->{$name} = $this->_View->addHelper($this->_helperMap[$name]['class'], $this->_config);
		}
		if (isset($this->{$name})) {
			return $this->{$name};
		}
	}

/**
 * Finds URL for specified action.
 *
 * Returns a URL pointing at the provided parameters.
 *
 * @param string|array $url Either a relative string url like `/products/view/23` or
 *    an array of URL parameters. Using an array for URLs will allow you to leverage
 *    the reverse routing features of CakePHP.
 * @param bool $full If true, the full base URL will be prepended to the result
 * @return string Full translated URL with base path.
 * @link http://book.cakephp.org/2.0/en/views/helpers.html
 */
	public function url($url = null, $full = false) {
		return h(Router::url($url, $full));
	}

/**
 * Checks if a file exists when theme is used, if no file is found default location is returned
 *
 * @param string $file The file to create a webroot path to.
 * @return string Web accessible path to file.
 */
	public function webroot($file) {
		$asset = explode('?', $file);
		$asset[1] = isset($asset[1]) ? '?' . $asset[1] : null;
		$webPath = "{$this->request->webroot}" . $asset[0];
		$file = $asset[0];

		if (!empty($this->theme)) {
			$file = trim($file, '/');
			$theme = $this->theme . '/';

			if (DS === '\\') {
				$file = str_replace('/', '\\', $file);
			}

			if (file_exists(Configure::read('App.www_root') . 'theme/' . $this->theme . DS . $file)) {
				$webPath = "{$this->request->webroot}theme/" . $theme . $asset[0];
			} else {
				$themePath = App::themePath($this->theme);
				$path = $themePath . 'webroot/' . $file;
				if (file_exists($path)) {
					$webPath = "{$this->request->webroot}theme/" . $theme . $asset[0];
				}
			}
		}
		if (strpos($webPath, '//') !== false) {
			return str_replace('//', '/', $webPath . $asset[1]);
		}
		return $webPath . $asset[1];
	}

/**
 * Generate URL for given asset file. Depending on options passed provides full URL with domain name.
 * Also calls Helper::assetTimestamp() to add timestamp to local files
 *
 * @param string|array Path string or URL array
 * @param array $options Options array. Possible keys:
 *   `fullBase` Return full URL with domain name
 *   `pathPrefix` Path prefix for relative URLs
 *   `ext` Asset extension to append
 *   `plugin` False value will prevent parsing path as a plugin
 * @return string Generated URL
 */
	public function assetUrl($path, array $options = array()) {
		if (is_array($path)) {
			return $this->url($path, !empty($options['fullBase']));
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
		if (
			!empty($options['ext']) &&
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
	protected function _encodeUrl($url) {
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
	public function assetTimestamp($path) {
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
			if ($segments[0] === 'theme') {
				$theme = $segments[1];
				unset($segments[0], $segments[1]);
				$themePath = App::themePath($theme) . 'webroot' . DS . implode(DS, $segments);
				//@codingStandardsIgnoreStart
				return $path . '?' . @filemtime($themePath);
				//@codingStandardsIgnoreEnd
			} else {
				$plugin = Inflector::camelize($segments[0]);
				if (Plugin::loaded($plugin)) {
					unset($segments[0]);
					$pluginPath = Plugin::path($plugin) . 'webroot' . DS . implode(DS, $segments);
					//@codingStandardsIgnoreStart
					return $path . '?' . @filemtime($pluginPath);
					//@codingStandardsIgnoreEnd
				}
			}
		}
		return $path;
	}

/**
 * Returns a string to be used as onclick handler for confirm dialogs.
 *
 * @param string $message Message to be displayed
 * @param string $okCode Code to be executed after user chose 'OK'
 * @param string $cancelCode Code to be executed after user chose 'Cancel'
 * @param array $options Array of options
 * @return string onclick JS code
 */
	protected function _confirm($message, $okCode, $cancelCode = '', $options = array()) {
		$message = json_encode($message);
		$confirm = "if (confirm({$message})) { {$okCode} } {$cancelCode}";
		if (isset($options['escape']) && $options['escape'] === false) {
			$confirm = h($confirm);
		}
		return $confirm;
	}

/**
 * Adds the given class to the element options
 *
 * @param array $options Array options/attributes to add a class to
 * @param string $class The class name being added.
 * @param string $key the key to use for class.
 * @return array Array of options with $key set.
 */
	public function addClass(array $options = array(), $class = null, $key = 'class') {
		if (isset($options[$key]) && trim($options[$key])) {
			$options[$key] .= ' ' . $class;
		} else {
			$options[$key] = $class;
		}
		return $options;
	}

/**
 * Get the View callbacks this helper is interested in.
 *
 * By defining one of the callback methods a helper is assumed
 * to be interested in the related event.
 *
 * Override this method if you need to add non-conventional event listeners.
 * Or if you want helpers to listen to non-standard events.
 *
 * @return array
 */
	public function implementedEvents() {
		$eventMap = [
			'View.beforeRenderFile' => 'beforeRenderFile',
			'View.afterRenderFile' => 'afterRenderFile',
			'View.beforeRender' => 'beforeRender',
			'View.afterRender' => 'afterRender',
			'View.beforeLayout' => 'beforeLayout',
			'View.afterLayout' => 'afterLayout'
		];
		$events = [];
		foreach ($eventMap as $event => $method) {
			if (method_exists($this, $method)) {
				$events[$event] = $method;
			}
		}
		return $events;
	}

}
