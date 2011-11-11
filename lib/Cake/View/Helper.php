<?php
/**
 * Backend for helpers.
 *
 * Internal methods for the Helpers.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Router', 'Routing');

/**
 * Abstract base class for all other Helpers in CakePHP.
 * Provides common methods and features.
 *
 * @package       Cake.View
 */
class Helper extends Object {

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
 * @var CakeRequest
 */
	public $request = null;

/**
 * Plugin path
 *
 * @var string
 */
	public $plugin = null;

/**
 * Holds the fields array('field_name' => array('type'=> 'string', 'length'=> 100),
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
 * Holds the content to be cleaned.
 *
 * @var mixed
 */
	protected $_tainted = null;

/**
 * Holds the cleaned content.
 *
 * @var mixed
 */
	protected $_cleaned = null;

/**
 * The View instance this helper is attached to
 *
 * @var View
 */
	protected $_View;

/**
 * A list of strings that should be treated as suffixes, or
 * sub inputs for a parent input.  This is used for date/time
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
 * Default Constructor
 *
 * @param View $View The View this helper is being attached to.
 * @param array $settings Configuration settings for the helper.
 */
	public function __construct(View $View, $settings = array()) {
		$this->_View = $View;
		$this->request = $View->request;
		if (!empty($this->helpers)) {
			$this->_helperMap = ObjectCollection::normalizeObjectArray($this->helpers);
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
		trigger_error(__d('cake_dev', 'Method %1$s::%2$s does not exist', get_class($this), $method), E_USER_WARNING);
	}

/**
 * Lazy loads helpers. Provides access to deprecated request properties as well.
 *
 * @param string $name Name of the property being accessed.
 * @return mixed Helper or property found at $name
 */
	public function __get($name) {
		if (isset($this->_helperMap[$name]) && !isset($this->{$name})) {
			$settings = array_merge((array)$this->_helperMap[$name]['settings'], array('enabled' => false));
			$this->{$name} = $this->_View->loadHelper($this->_helperMap[$name]['class'], $settings);
		}
		if (isset($this->{$name})) {
			return $this->{$name};
		}
		switch ($name) {
			case 'base':
			case 'here':
			case 'webroot':
			case 'data':
				return $this->request->{$name};
			case 'action':
				return isset($this->request->params['action']) ? $this->request->params['action'] : '';
			case 'params':
				return $this->request;
		}
	}

/**
 * Provides backwards compatiblity access for setting values to the request object.
 *
 * @param string $name Name of the property being accessed.
 * @param mixed $value
 * @return mixed Return the $value
 */
	public function __set($name, $value) {
		switch ($name) {
			case 'base':
			case 'here':
			case 'webroot':
			case 'data':
				return $this->request->{$name} = $value;
			case 'action':
				return $this->request->params['action'] = $value;
		}
		return $this->{$name} = $value;
	}

/**
 * Finds URL for specified action.
 *
 * Returns a URL pointing at the provided parameters.
 *
 * @param mixed $url Either a relative string url like `/products/view/23` or
 *    an array of url parameters.  Using an array for urls will allow you to leverage
 *    the reverse routing features of CakePHP.
 * @param boolean $full If true, the full base URL will be prepended to the result
 * @return string  Full translated URL with base path.
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

			if (file_exists(Configure::read('App.www_root') . 'theme' . DS . $this->theme . DS  . $file)) {
				$webPath = "{$this->request->webroot}theme/" . $theme . $asset[0];
			} else {
				$themePath = App::themePath($this->theme);
				$path = $themePath . 'webroot' . DS  . $file;
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
 * Adds a timestamp to a file based resource based on the value of `Asset.timestamp` in
 * Configure.  If Asset.timestamp is true and debug > 0, or Asset.timestamp == 'force'
 * a timestamp will be added.
 *
 * @param string $path The file path to timestamp, the path must be inside WWW_ROOT
 * @return string Path with a timestamp added, or not.
 */
	public function assetTimestamp($path) {
		$stamp = Configure::read('Asset.timestamp');
		$timestampEnabled = $stamp === 'force' || ($stamp === true && Configure::read('debug') > 0);
		if ($timestampEnabled && strpos($path, '?') === false) {
			$filepath = preg_replace('/^' . preg_quote($this->request->webroot, '/') . '/', '', $path);
			$webrootPath = WWW_ROOT . str_replace('/', DS, $filepath);
			if (file_exists($webrootPath)) {
				return $path . '?' . @filemtime($webrootPath);
			}
			$segments = explode('/', ltrim($filepath, '/'));
			if ($segments[0] === 'theme') {
				$theme = $segments[1];
				unset($segments[0], $segments[1]);
				$themePath = App::themePath($theme) . 'webroot' . DS . implode(DS, $segments);
				return $path . '?' . @filemtime($themePath);
			} else {
				$plugin = Inflector::camelize($segments[0]);
				if (CakePlugin::loaded($plugin)) {
					unset($segments[0]);
					$pluginPath = CakePlugin::path($plugin) . 'webroot' . DS . implode(DS, $segments);
					return $path . '?' . @filemtime($pluginPath);
				}
			}
		}
		return $path;
	}

/**
 * Used to remove harmful tags from content.  Removes a number of well known XSS attacks
 * from content.  However, is not guaranteed to remove all possibilities.  Escaping
 * content is the best way to prevent all possible attacks.
 *
 * @param mixed $output Either an array of strings to clean or a single string to clean.
 * @return string|array cleaned content for output
 */
	public function clean($output) {
		$this->_reset();
		if (empty($output)) {
			return null;
		}
		if (is_array($output)) {
			foreach ($output as $key => $value) {
				$return[$key] = $this->clean($value);
			}
			return $return;
		}
		$this->_tainted = $output;
		$this->_clean();
		return $this->_cleaned;
	}

/**
 * Returns a space-delimited string with items of the $options array. If a
 * key of $options array happens to be one of:
 *
 * - 'compact'
 * - 'checked'
 * - 'declare'
 * - 'readonly'
 * - 'disabled'
 * - 'selected'
 * - 'defer'
 * - 'ismap'
 * - 'nohref'
 * - 'noshade'
 * - 'nowrap'
 * - 'multiple'
 * - 'noresize'
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
 *  attributes to their html-entity encoded equivalents.  Set to false to disable html-encoding.
 *
 * If value for any option key is set to `null` or `false`, that option will be excluded from output.
 *
 * @param array $options Array of options.
 * @param array $exclude Array of options to be excluded, the options here will not be part of the return.
 * @param string $insertBefore String to be inserted before options.
 * @param string $insertAfter String to be inserted after options.
 * @return string Composed attributes.
 * @deprecated This method has been moved to HtmlHelper
 */
	protected function _parseAttributes($options, $exclude = null, $insertBefore = ' ', $insertAfter = null) {
		if (!is_string($options)) {
			$options = (array) $options + array('escape' => true);

			if (!is_array($exclude)) {
				$exclude = array();
			}

			$exclude =  array('escape' => true) + array_flip($exclude);
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
 * @deprecated This method has been moved to HtmlHelper
 */
	protected function _formatAttribute($key, $value, $escape = true) {
		$attribute = '';
		if (is_array($value)) {
			$value = '';
		}

		if (is_numeric($key)) {
			$attribute = sprintf($this->_minimizedAttributeFormat, $value, $value);
		} elseif (in_array($key, $this->_minimizedAttributes)) {
			if ($value === 1 || $value === true || $value === 'true' || $value === '1' || $value == $key) {
				$attribute = sprintf($this->_minimizedAttributeFormat, $key, $key);
			}
		} else {
			$attribute = sprintf($this->_attributeFormat, $key, ($escape ? h($value) : $value));
		}
		return $attribute;
	}

/**
 * Sets this helper's model and field properties to the dot-separated value-pair in $entity.
 *
 * @param mixed $entity A field name, like "ModelName.fieldName" or "ModelName.ID.fieldName"
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
		$parts = array_values(Set::filter(explode('.', $entity), true));
		if (empty($parts)) {
			return;
		}
		$count = count($parts);
		$lastPart = isset($parts[$count - 1]) ? $parts[$count - 1] : null;

		// Either 'body' or 'date.month' type inputs.
		if (
			($count === 1 && $this->_modelScope && $setScope == false) ||
			(
				$count === 2 &&
				in_array($lastPart, $this->_fieldSuffixes) &&
				$this->_modelScope &&
				$parts[0] !== $this->_modelScope
			)
		) {
			$entity = $this->_modelScope . '.' . $entity;
		}

		// 0.name, 0.created.month style inputs.  Excludes inputs with the modelScope in them.
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
			$this->fieldset[$this->_modelScope]['fields'][$parts[0]]['type'] === 'multiple' &&
			$count == 1
		);

		// habtm models are special
		if ($count == 1 && $isHabtm) {
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
		return;
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
 * Strips off fieldsuffixes such as year, month, day, hour, min, meridian
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
 * Generates a DOM ID for the selected element, if one is not set.
 * Uses the current View::entity() settings to generate a CamelCased id attribute.
 *
 * @param mixed $options Either an array of html attributes to add $id into, or a string
 *   with a view entity path to get a domId for.
 * @param string $id The name of the 'id' attribute.
 * @return mixed If $options was an array, an array will be returned with $id set.  If a string
 *   was supplied, a string will be returned.
 * @todo Refactor this method to not have as many input/output options.
 */
	public function domId($options = null, $id = 'id') {
		if (is_array($options) && array_key_exists($id, $options) && $options[$id] === null) {
			unset($options[$id]);
			return $options;
		} elseif (!is_array($options) && $options !== null) {
			$this->setEntity($options);
			return $this->domId();
		}

		$entity = $this->entity();
		$model = array_shift($entity);
		$dom = $model . join('', array_map(array('Inflector', 'camelize'), $entity));

		if (is_array($options) && !array_key_exists($id, $options)) {
			$options[$id] = $dom;
		} elseif ($options === null) {
			return $dom;
		}
		return $options;
	}

/**
 * Gets the input field name for the current tag. Creates input name attributes
 * using CakePHP's data[Model][field] formatting.
 *
 * @param mixed $options If an array, should be an array of attributes that $key needs to be added to.
 *   If a string or null, will be used as the View entity.
 * @param string $field
 * @param string $key The name of the attribute to be set, defaults to 'name'
 * @return mixed If an array was given for $options, an array with $key set will be returned.
 *   If a string was supplied a string will be returned.
 * @todo Refactor this method to not have as many input/output options.
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
				$name = 'data[' . implode('][', $this->entity()) . ']';
			break;
		}

		if (is_array($options)) {
			$options[$key] = $name;
			return $options;
		} else {
			return $name;
		}
	}

/**
 * Gets the data for the current tag
 *
 * @param mixed $options If an array, should be an array of attributes that $key needs to be added to.
 *   If a string or null, will be used as the View entity.
 * @param string $field
 * @param string $key The name of the attribute to be set, defaults to 'value'
 * @return mixed If an array was given for $options, an array with $key set will be returned.
 *   If a string was supplied a string will be returned.
 * @todo Refactor this method to not have as many input/output options.
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
		if (!empty($data) && !empty($entity)) {
			$result = Set::extract(implode('.', $entity), $data);
		}

		$habtmKey = $this->field();
		if (empty($result) && isset($data[$habtmKey][$habtmKey]) && is_array($data[$habtmKey])) {
			$result = $data[$habtmKey][$habtmKey];
		} elseif (empty($result) && isset($data[$habtmKey]) && is_array($data[$habtmKey])) {
			if (ClassRegistry::isKeySet($habtmKey)) {
				$model = ClassRegistry::getObject($habtmKey);
				$result = $this->_selectedArray($data[$habtmKey], $model->primaryKey);
			}
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
		} else {
			return $result;
		}
	}

/**
 * Sets the defaults for an input tag.  Will set the
 * name, value, and id attributes for an array of html attributes. Will also
 * add a 'form-error' class if the field contains validation errors.
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
		if ($this->tagIsInvalid() !== false) {
			$options = $this->addClass($options, 'form-error');
		}
		return $options;
	}

/**
 * Adds the given class to the element options
 *
 * @param array $options Array options/attributes to add a class to
 * @param string $class The classname being added.
 * @param string $key the key to use for class.
 * @return array Array of options with $key set.
 */
	public function addClass($options = array(), $class = null, $key = 'class') {
		if (isset($options[$key]) && trim($options[$key]) != '') {
			$options[$key] .= ' ' . $class;
		} else {
			$options[$key] = $class;
		}
		return $options;
	}

/**
 * Returns a string generated by a helper method
 *
 * This method can be overridden in subclasses to do generalized output post-processing
 *
 * @param string $str String to be output.
 * @return string
 * @deprecated This method will be removed in future versions.
 */
	public function output($str) {
		return $str;
	}

/**
 * Before render callback. beforeRender is called before the view file is rendered.
 *
 * Overridden in subclasses.
 *
 * @param string $viewFile The view file that is going to be rendered
 * @return void
 */
	public function beforeRender($viewFile) {
	}

/**
 * After render callback.  afterRender is called after the view file is rendered
 * but before the layout has been rendered.
 *
 * Overridden in subclasses.
 *
 * @param string $viewFile The view file that was rendered.
 * @return void
 */
	public function afterRender($viewFile) {
	}

/**
 * Before layout callback.  beforeLayout is called before the layout is rendered.
 *
 * Overridden in subclasses.
 *
 * @param string $layoutFile The layout about to be rendered.
 * @return void
 */
	public function beforeLayout($layoutFile) {
	}

/**
 * After layout callback.  afterLayout is called after the layout has rendered.
 *
 * Overridden in subclasses.
 *
 * @param string $layoutFile The layout file that was rendered.
 * @return void
 */
	public function afterLayout($layoutFile) {
	}

/**
 * Transforms a recordset from a hasAndBelongsToMany association to a list of selected
 * options for a multiple select element
 *
 * @param mixed $data
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

/**
 * Resets the vars used by Helper::clean() to null
 *
 * @return void
 */
	protected function _reset() {
		$this->_tainted = null;
		$this->_cleaned = null;
	}

/**
 * Removes harmful content from output
 *
 * @return void
 */
	protected function _clean() {
		if (get_magic_quotes_gpc()) {
			$this->_cleaned = stripslashes($this->_tainted);
		} else {
			$this->_cleaned = $this->_tainted;
		}

		$this->_cleaned = str_replace(array("&amp;", "&lt;", "&gt;"), array("&amp;amp;", "&amp;lt;", "&amp;gt;"), $this->_cleaned);
		$this->_cleaned = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u', "$1;", $this->_cleaned);
		$this->_cleaned = preg_replace('#(&\#x*)([0-9A-F]+);*#iu', "$1$2;", $this->_cleaned);
		$this->_cleaned = html_entity_decode($this->_cleaned, ENT_COMPAT, "UTF-8");
		$this->_cleaned = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>#iUu', "$1>", $this->_cleaned);
		$this->_cleaned = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*)[\\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2nojavascript...', $this->_cleaned);
		$this->_cleaned = preg_replace('#([a-z]*)[\x00-\x20]*=([\'\"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2novbscript...', $this->_cleaned);
		$this->_cleaned = preg_replace('#([a-z]*)[\x00-\x20]*=*([\'\"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#iUu','$1=$2nomozbinding...', $this->_cleaned);
		$this->_cleaned = preg_replace('#([a-z]*)[\x00-\x20]*=([\'\"]*)[\x00-\x20]*data[\x00-\x20]*:#Uu', '$1=$2nodata...', $this->_cleaned);
		$this->_cleaned = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*expression[\x00-\x20]*\([^>]*>#iU', "$1>", $this->_cleaned);
		$this->_cleaned = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*behaviour[\x00-\x20]*\([^>]*>#iU', "$1>", $this->_cleaned);
		$this->_cleaned = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*>#iUu', "$1>", $this->_cleaned);
		$this->_cleaned = preg_replace('#</*\w+:\w[^>]*>#i', "", $this->_cleaned);
		do {
			$oldstring = $this->_cleaned;
			$this->_cleaned = preg_replace('#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>#i', "", $this->_cleaned);
		} while ($oldstring != $this->_cleaned);
		$this->_cleaned = str_replace(array("&amp;", "&lt;", "&gt;"), array("&amp;amp;", "&amp;lt;", "&amp;gt;"), $this->_cleaned);
	}
}
