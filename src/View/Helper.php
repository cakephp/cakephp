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
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Object;
use Cake\Core\Plugin;
use Cake\Event\EventListener;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Abstract base class for all other Helpers in CakePHP.
 * Provides common methods and features.
 *
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
class Helper extends Object implements EventListener {

/**
 * Settings for this helper.
 *
 * @var array
 */
	public $settings = array();

/**
 * List of helpers used by this helper
 *
 * @var array
 */
	public $helpers = array();

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
 * @var Cake\Network\Request
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
 * A list of strings that should be treated as suffixes, or
 * sub inputs for a parent input. This is used for date/time
 * inputs primarily.
 *
 * @var array
 */
	protected $_fieldSuffixes = array(
		'year', 'month', 'day', 'hour', 'min', 'second', 'meridian'
	);

/**
 * The name of the current model entities are in scope of.
 *
 * @see Helper::setEntity()
 * @var string
 */
	protected $_modelScope;

/**
 * The name of the current model association entities are in scope of.
 *
 * @see Helper::setEntity()
 * @var string
 */
	protected $_association;

/**
 * The dot separated list of elements the current field entity is for.
 *
 * @see Helper::setEntity()
 * @var string
 */
	protected $_entityPath;

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
 * @param array $settings Configuration settings for the helper.
 */
	public function __construct(View $View, $settings = array()) {
		$this->_View = $View;
		$this->request = $View->request;
		if ($settings) {
			$this->settings = Hash::merge($this->settings, $settings);
		}
		if (!empty($this->helpers)) {
			$this->_helperMap = $View->Helpers->normalizeArray($this->helpers);
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
			$settings = array_merge((array)$this->_helperMap[$name]['settings'], array('enabled' => false));
			$this->{$name} = $this->_View->loadHelper($this->_helperMap[$name]['class'], $settings);
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
 * @param boolean $full If true, the full base URL will be prepended to the result
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
	public function assetUrl($path, $options = array()) {
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
 * Configure. If Asset.timestamp is true and debug > 0, or Asset.timestamp === 'force'
 * a timestamp will be added.
 *
 * @param string $path The file path to timestamp, the path must be inside WWW_ROOT
 * @return string Path with a timestamp added, or not.
 */
	public function assetTimestamp($path) {
		$stamp = Configure::read('Asset.timestamp');
		$timestampEnabled = $stamp === 'force' || ($stamp === true && Configure::read('debug') > 0);
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
 * Returns a space-delimited string with items of the $options array. If a key
 * of $options array happens to be one of those listed in `Helper::$_minimizedAttributes`
 *
 * And its value is one of:
 *
 * - '1' (string)
 * - 1 (integer)
 * - true (boolean)
 * - 'true' (string)
 *
 * Then the value will be reset to be identical with key's name.
 * If the value is not one of these 3, the parameter is not output.
 *
 * 'escape' is a special option in that it controls the conversion of
 *  attributes to their html-entity encoded equivalents. Set to false to disable html-encoding.
 *
 * If value for any option key is set to `null` or `false`, that option will be excluded from output.
 *
 * @param array $options Array of options.
 * @param array $exclude Array of options to be excluded, the options here will not be part of the return.
 * @param string $insertBefore String to be inserted before options.
 * @param string $insertAfter String to be inserted after options.
 * @return string Composed attributes.
 * @deprecated This method will be moved to HtmlHelper in 3.0
 */
	protected function _parseAttributes($options, $exclude = null, $insertBefore = ' ', $insertAfter = null) {
		if (!is_string($options)) {
			$options = (array)$options + array('escape' => true);

			if (!is_array($exclude)) {
				$exclude = array();
			}

			$exclude = array('escape' => true) + array_flip($exclude);
			$escape = $options['escape'];
			$attributes = array();

			foreach ($options as $key => $value) {
				if (!isset($exclude[$key]) && $value !== false && $value !== null) {
					$attributes[] = $this->_formatAttribute($key, $value, $escape);
				}
			}
			$out = implode(' ', $attributes);
		} else {
			$out = $options;
		}
		return $out ? $insertBefore . $out . $insertAfter : '';
	}

/**
 * Formats an individual attribute, and returns the string value of the composed attribute.
 * Works with minimized attributes that have the same value as their name such as 'disabled' and 'checked'
 *
 * @param string $key The name of the attribute to create
 * @param string $value The value of the attribute to create.
 * @param boolean $escape Define if the value must be escaped
 * @return string The composed attribute.
 * @deprecated This method will be moved to HtmlHelper in 3.0
 */
	protected function _formatAttribute($key, $value, $escape = true) {
		if (is_array($value)) {
			$value = implode(' ', $value);
		}
		if (is_numeric($key)) {
			return sprintf($this->_minimizedAttributeFormat, $value, $value);
		}
		$truthy = array(1, '1', true, 'true', $key);
		$isMinimized = in_array($key, $this->_minimizedAttributes);
		if ($isMinimized && in_array($value, $truthy, true)) {
			return sprintf($this->_minimizedAttributeFormat, $key, $key);
		}
		if ($isMinimized) {
			return '';
		}
		return sprintf($this->_attributeFormat, $key, ($escape ? h($value) : $value));
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
 * Sets this helper's model and field properties to the dot-separated value-pair in $entity.
 *
 * @param string $entity A field name, like "ModelName.fieldName" or "ModelName.ID.fieldName"
 * @param boolean $setScope Sets the view scope to the model specified in $tagValue
 * @return void
 */
	public function setEntity($entity, $setScope = false) {
		if ($entity === null) {
			$this->_modelScope = false;
		}
		if ($setScope === true) {
			$this->_modelScope = $entity;
		}
		$parts = array_values(Hash::filter(explode('.', $entity)));
		if (empty($parts)) {
			return;
		}
		$count = count($parts);
		$lastPart = isset($parts[$count - 1]) ? $parts[$count - 1] : null;

		// Either 'body' or 'date.month' type inputs.
		if (
			($count === 1 && $this->_modelScope && !$setScope) ||
			(
				$count === 2 &&
				in_array($lastPart, $this->_fieldSuffixes) &&
				$this->_modelScope &&
				$parts[0] !== $this->_modelScope
			)
		) {
			$entity = $this->_modelScope . '.' . $entity;
		}

		// 0.name, 0.created.month style inputs. Excludes inputs with the modelScope in them.
		if (
			$count >= 2 &&
			is_numeric($parts[0]) &&
			!is_numeric($parts[1]) &&
			$this->_modelScope &&
			strpos($entity, $this->_modelScope) === false
		) {
			$entity = $this->_modelScope . '.' . $entity;
		}

		$this->_association = null;

		$isHabtm = (
			isset($this->fieldset[$this->_modelScope]['fields'][$parts[0]]['type']) &&
			$this->fieldset[$this->_modelScope]['fields'][$parts[0]]['type'] === 'multiple'
		);

		// habtm models are special
		if ($count === 1 && $isHabtm) {
			$this->_association = $parts[0];
			$entity = $parts[0] . '.' . $parts[0];
		} else {
			// check for associated model.
			$reversed = array_reverse($parts);
			foreach ($reversed as $i => $part) {
				if ($i > 0 && preg_match('/^[A-Z]/', $part)) {
					$this->_association = $part;
					break;
				}
			}
		}
		$this->_entityPath = $entity;
	}

/**
 * Returns the entity reference of the current context as an array of identity parts
 *
 * @return array An array containing the identity elements of an entity
 */
	public function entity() {
		return explode('.', $this->_entityPath);
	}

/**
 * Gets the currently-used model of the rendering context.
 *
 * @return string
 */
	public function model() {
		if ($this->_association) {
			return $this->_association;
		}
		return $this->_modelScope;
	}

/**
 * Gets the currently-used model field of the rendering context.
 * Strips off field suffixes such as year, month, day, hour, min, meridian
 * when the current entity is longer than 2 elements.
 *
 * @return string
 */
	public function field() {
		$entity = $this->entity();
		$count = count($entity);
		$last = $entity[$count - 1];
		if ($count > 2 && in_array($last, $this->_fieldSuffixes)) {
			$last = isset($entity[$count - 2]) ? $entity[$count - 2] : null;
		}
		return $last;
	}

/**
 * Gets the input field name for the current tag. Creates input name attributes
 * using CakePHP's `Model[field]` formatting.
 *
 * @param array|string $options If an array, should be an array of attributes that $key needs to be added to.
 *   If a string or null, will be used as the View entity.
 * @param string $field
 * @param string $key The name of the attribute to be set, defaults to 'name'
 * @return mixed If an array was given for $options, an array with $key set will be returned.
 *   If a string was supplied a string will be returned.
 */
	protected function _name($options = array(), $field = null, $key = 'name') {
		if ($options === null) {
			$options = array();
		} elseif (is_string($options)) {
			$field = $options;
			$options = 0;
		}

		if (!empty($field)) {
			$this->setEntity($field);
		}

		if (is_array($options) && array_key_exists($key, $options)) {
			return $options;
		}

		switch ($field) {
			case '_method':
				$name = $field;
				break;
			default:
				$entity = $this->entity();
				$first = array_shift($entity);
				$name = $first . ($entity ? '[' . implode('][', $entity) . ']' : '');
			break;
		}

		if (is_array($options)) {
			$options[$key] = $name;
			return $options;
		}
		return $name;
	}

/**
 * Gets the data for the current tag
 *
 * @param array|string $options If an array, should be an array of attributes that $key needs to be added to.
 *   If a string or null, will be used as the View entity.
 * @param string $field
 * @param string $key The name of the attribute to be set, defaults to 'value'
 * @return mixed If an array was given for $options, an array with $key set will be returned.
 *   If a string was supplied a string will be returned.
 */
	public function value($options = array(), $field = null, $key = 'value') {
		if ($options === null) {
			$options = array();
		} elseif (is_string($options)) {
			$field = $options;
			$options = 0;
		}

		if (is_array($options) && isset($options[$key])) {
			return $options;
		}

		if (!empty($field)) {
			$this->setEntity($field);
		}
		$result = null;
		$data = $this->request->data;

		$entity = $this->entity();
		if (!empty($data) && is_array($data) && !empty($entity)) {
			$result = Hash::get($data, implode('.', $entity));
		}

		$habtmKey = $this->field();
		if (empty($result) && isset($data[$habtmKey][$habtmKey]) && is_array($data[$habtmKey])) {
			$result = $data[$habtmKey][$habtmKey];
		}

		if (is_array($options)) {
			if ($result === null && isset($options['default'])) {
				$result = $options['default'];
			}
			unset($options['default']);
		}

		if (is_array($options)) {
			$options[$key] = $result;
			return $options;
		}
		return $result;
	}

/**
 * Sets the defaults for an input tag. Will set the
 * name, value, and id attributes for an array of html attributes.
 *
 * @param string $field The field name to initialize.
 * @param array $options Array of options to use while initializing an input field.
 * @return array Array options for the form input.
 */
	protected function _initInputField($field, $options = array()) {
		if ($field !== null) {
			$this->setEntity($field);
		}
		$options = (array)$options;
		$options = $this->_name($options);
		$options = $this->value($options);
		$options = $this->domId($options);
		return $options;
	}

/**
 * Adds the given class to the element options
 *
 * @param array $options Array options/attributes to add a class to
 * @param string $class The class name being added.
 * @param string $key the key to use for class.
 * @return array Array of options with $key set.
 */
	public function addClass($options = array(), $class = null, $key = 'class') {
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

/**
 * Transforms a recordset from a hasAndBelongsToMany association to a list of selected
 * options for a multiple select element
 *
 * @param string|array $data
 * @param string $key
 * @return array
 */
	protected function _selectedArray($data, $key = 'id') {
		if (!is_array($data)) {
			$model = $data;
			if (!empty($this->request->data[$model][$model])) {
				return $this->request->data[$model][$model];
			}
			if (!empty($this->request->data[$model])) {
				$data = $this->request->data[$model];
			}
		}
		$array = array();
		if (!empty($data)) {
			foreach ($data as $row) {
				if (isset($row[$key])) {
					$array[$row[$key]] = $row[$key];
				}
			}
		}
		return empty($array) ? null : $array;
	}

}
