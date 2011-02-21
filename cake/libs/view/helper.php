<?php
/**
 * Backend for helpers.
 *
 * Internal methods for the Helpers.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Included libs
 */
App::import('Core', 'Overloadable');

/**
 * Abstract base class for all other Helpers in CakePHP.
 * Provides common methods and features.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view
 */
class Helper extends Overloadable {

/**
 * List of helpers used by this helper
 *
 * @var array
 */
	var $helpers = null;

/**
 * Base URL
 *
 * @var string
 */
	var $base = null;

/**
 * Webroot path
 *
 * @var string
 */
	var $webroot = null;

/**
 * The current theme name if any.
 *
 * @var string
 */
	var $theme = null;

/**
 * URL to current action.
 *
 * @var string
 */
	var $here = null;

/**
 * Parameter array.
 *
 * @var array
 */
	var $params = array();

/**
 * Current action.
 *
 * @var string
 */
	var $action = null;

/**
 * Plugin path
 *
 * @var string
 */
	var $plugin = null;

/**
 * POST data for models
 *
 * @var array
 */
	var $data = null;

/**
 * List of named arguments
 *
 * @var array
 */
	var $namedArgs = null;

/**
 * URL argument separator character
 *
 * @var string
 */
	var $argSeparator = null;

/**
 * Contains model validation errors of form post-backs
 *
 * @access public
 * @var array
 */
	var $validationErrors = null;

/**
 * Holds tag templates.
 *
 * @access public
 * @var array
 */
	var $tags = array();

/**
 * Holds the content to be cleaned.
 *
 * @access private
 * @var mixed
 */
	var $__tainted = null;

/**
 * Holds the cleaned content.
 *
 * @access private
 * @var mixed
 */
	var $__cleaned = null;

/**
 * Default overload methods
 *
 * @access protected
 */
	function get__($name) {}
	function set__($name, $value) {}
	function call__($method, $params) {
		trigger_error(sprintf(__('Method %1$s::%2$s does not exist', true), get_class($this), $method), E_USER_WARNING);
	}

/**
 * Parses tag templates into $this->tags.
 *
 * @param $name file name inside app/config to load.
 * @return array merged tags from config/$name.php
 * @access public
 */
	function loadConfig($name = 'tags') {
		if (file_exists(CONFIGS . $name .'.php')) {
			require(CONFIGS . $name .'.php');
			if (isset($tags)) {
				$this->tags = array_merge($this->tags, $tags);
			}
		}
		return $this->tags;
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
 * @access public
 * @link http://book.cakephp.org/view/1448/url
 */
	function url($url = null, $full = false) {
		return h(Router::url($url, $full));
	}

/**
 * Checks if a file exists when theme is used, if no file is found default location is returned
 *
 * @param string $file The file to create a webroot path to.
 * @return string Web accessible path to file.
 * @access public
 */
	function webroot($file) {
		$asset = explode('?', $file);
		$asset[1] = isset($asset[1]) ? '?' . $asset[1] : null;
		$webPath = "{$this->webroot}" . $asset[0];
		$file = $asset[0];

		if (!empty($this->theme)) {
			$file = trim($file, '/');
			$theme = $this->theme . '/';

			if (DS === '\\') {
				$file = str_replace('/', '\\', $file);
			}

			if (file_exists(Configure::read('App.www_root') . 'theme' . DS . $this->theme . DS  . $file)) {
				$webPath = "{$this->webroot}theme/" . $theme . $asset[0];
			} else {
				$viewPaths = App::path('views');

				foreach ($viewPaths as $viewPath) {
					$path = $viewPath . 'themed'. DS . $this->theme . DS  . 'webroot' . DS  . $file;

					if (file_exists($path)) {
						$webPath = "{$this->webroot}theme/" . $theme . $asset[0];
						break;
					}
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
 * @access public
 */
	function assetTimestamp($path) {
		$timestampEnabled = (
			(Configure::read('Asset.timestamp') === true && Configure::read() > 0) ||
			Configure::read('Asset.timestamp') === 'force'
		);
		if (strpos($path, '?') === false && $timestampEnabled) {
			$filepath = preg_replace('/^' . preg_quote($this->webroot, '/') . '/', '', $path);
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
				$plugin = $segments[0];
				unset($segments[0]);
				$pluginPath = App::pluginPath($plugin) . 'webroot' . DS . implode(DS, $segments);
				return $path . '?' . @filemtime($pluginPath);
			}
		}
		return $path;
	}

/**
 * Used to remove harmful tags from content.  Removes a number of well known XSS attacks
 * from content.  However, is not guaranteed to remove all possiblities.  Escaping
 * content is the best way to prevent all possible attacks.
 *
 * @param mixed $output Either an array of strings to clean or a single string to clean.
 * @return cleaned content for output
 * @access public
 */
	function clean($output) {
		$this->__reset();
		if (empty($output)) {
			return null;
		}
		if (is_array($output)) {
			foreach ($output as $key => $value) {
				$return[$key] = $this->clean($value);
			}
			return $return;
		}
		$this->__tainted = $output;
		$this->__clean();
		return $this->__cleaned;
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
 * @access public
 */
	function _parseAttributes($options, $exclude = null, $insertBefore = ' ', $insertAfter = null) {
		if (is_array($options)) {
			$options = array_merge(array('escape' => true), $options);

			if (!is_array($exclude)) {
				$exclude = array();
			}
			$keys = array_diff(array_keys($options), array_merge($exclude, array('escape')));
			$values = array_intersect_key(array_values($options), $keys);
			$escape = $options['escape'];
			$attributes = array();

			foreach ($keys as $index => $key) {
				if ($values[$index] !== false && $values[$index] !== null) {
					$attributes[] = $this->__formatAttribute($key, $values[$index], $escape);
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
 * @return string The composed attribute.
 * @access private
 */
	function __formatAttribute($key, $value, $escape = true) {
		$attribute = '';
		$attributeFormat = '%s="%s"';
		$minimizedAttributes = array('compact', 'checked', 'declare', 'readonly', 'disabled',
			'selected', 'defer', 'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize');
		if (is_array($value)) {
			$value = '';
		}

		if (in_array($key, $minimizedAttributes)) {
			if ($value === 1 || $value === true || $value === 'true' || $value === '1' || $value == $key) {
				$attribute = sprintf($attributeFormat, $key, $key);
			}
		} else {
			$attribute = sprintf($attributeFormat, $key, ($escape ? h($value) : $value));
		}
		return $attribute;
	}

/**
 * Sets this helper's model and field properties to the dot-separated value-pair in $entity.
 *
 * @param mixed $entity A field name, like "ModelName.fieldName" or "ModelName.ID.fieldName"
 * @param boolean $setScope Sets the view scope to the model specified in $tagValue
 * @return void
 * @access public
 */
	function setEntity($entity, $setScope = false) {
		$view =& ClassRegistry::getObject('view');

		if ($setScope) {
			$view->modelScope = false;
		} elseif (!empty($view->entityPath) && $view->entityPath == $entity) {
			return;
		}

		if ($entity === null) {
			$view->model = null;
			$view->association = null;
			$view->modelId = null;
			$view->modelScope = false;
			$view->entityPath = null;
			return;
		}

		$view->entityPath = $entity;
		$model = $view->model;
		$sameScope = $hasField = false;
		$parts = array_values(Set::filter(explode('.', $entity), true));

		if (empty($parts)) {
			return;
		}

		$count = count($parts);
		if ($count === 1) {
			$sameScope = true;
		} else {
			if (is_numeric($parts[0])) {
				$sameScope = true;
			}
			$reverse = array_reverse($parts);
			$field = array_shift($reverse);
			while(!empty($reverse)) {
				$subject = array_shift($reverse);
				if (is_numeric($subject)) {
					continue;
				}
				if (ClassRegistry::isKeySet($subject)) {
					$model = $subject;
					break;
				}
			}
		}

		if (ClassRegistry::isKeySet($model)) {
			$ModelObj =& ClassRegistry::getObject($model);
			for ($i = 0; $i < $count; $i++) {
				if (
					is_a($ModelObj, 'Model') && 
					($ModelObj->hasField($parts[$i]) || 
					array_key_exists($parts[$i], $ModelObj->validate))
				) {
					$hasField = $i;
					if ($hasField === 0 || ($hasField === 1 && is_numeric($parts[0]))) {
						$sameScope = true;
					}
					break;
				}
			}

			if ($sameScope === true && in_array($parts[0], array_keys($ModelObj->hasAndBelongsToMany))) {
				$sameScope = false;
			}
		}

		if (!$view->association && $parts[0] == $view->field && $view->field != $view->model) {
			array_unshift($parts, $model);
			$hasField = true;
		}
		$view->field = $view->modelId = $view->fieldSuffix = $view->association = null;

		switch (count($parts)) {
			case 1:
				if ($view->modelScope === false) {
					$view->model = $parts[0];
				} else {
					$view->field = $parts[0];
					if ($sameScope === false) {
						$view->association = $parts[0];
					}
				}
			break;
			case 2:
				if ($view->modelScope === false) {
					list($view->model, $view->field) = $parts;
				} elseif ($sameScope === true && $hasField === 0) {
					list($view->field, $view->fieldSuffix) = $parts;
				} elseif ($sameScope === true && $hasField === 1) {
					list($view->modelId, $view->field) = $parts;
				} else {
					list($view->association, $view->field) = $parts;
				}
			break;
			case 3:
				if ($sameScope === true && $hasField === 1) {
					list($view->modelId, $view->field, $view->fieldSuffix) = $parts;
				} elseif ($hasField === 2) {
					list($view->association, $view->modelId, $view->field) = $parts;
				} else {
					list($view->association, $view->field, $view->fieldSuffix) = $parts;
				}
			break;
			case 4:
				if ($parts[0] === $view->model) {
					list($view->model, $view->modelId, $view->field, $view->fieldSuffix) = $parts;
				} else {
					list($view->association, $view->modelId, $view->field, $view->fieldSuffix) = $parts;
				}
			break;
			default:
				$reverse = array_reverse($parts);

				if ($hasField) {
						$view->field = $field;
						if (!is_numeric($reverse[1]) && $reverse[1] != $model) {
							$view->field = $reverse[1];
							$view->fieldSuffix = $field;
						}
				}
				if (is_numeric($parts[0])) {
					$view->modelId = $parts[0];
				} elseif ($view->model == $parts[0] && is_numeric($parts[1])) {
					$view->modelId = $parts[1];
				}
				$view->association = $model;
			break;
		}

		if (!isset($view->model) || empty($view->model)) {
			$view->model = $view->association;
			$view->association = null;
		} elseif ($view->model === $view->association) {
			$view->association = null;
		}

		if ($setScope) {
			$view->modelScope = true;
		}
	}

/**
 * Gets the currently-used model of the rendering context.
 *
 * @return string
 * @access public
 */
	function model() {
		$view =& ClassRegistry::getObject('view');
		if (!empty($view->association)) {
			return $view->association;
		} else {
			return $view->model;
		}
	}

/**
 * Gets the ID of the currently-used model of the rendering context.
 *
 * @return mixed
 * @access public
 */
	function modelID() {
		$view =& ClassRegistry::getObject('view');
		return $view->modelId;
	}

/**
 * Gets the currently-used model field of the rendering context.
 *
 * @return string
 * @access public
 */
	function field() {
		$view =& ClassRegistry::getObject('view');
		return $view->field;
	}

/**
 * Returns false if given FORM field has no errors. Otherwise it returns the constant set in
 * the array Model->validationErrors.
 *
 * @param string $model Model name as a string
 * @param string $field Fieldname as a string
 * @param integer $modelID Unique index identifying this record within the form
 * @return boolean True on errors.
 */
	function tagIsInvalid($model = null, $field = null, $modelID = null) {
		$view =& ClassRegistry::getObject('view');
		$errors = $this->validationErrors;
		$entity = $view->entity();
		if (!empty($entity)) {
			return Set::extract($errors, join('.', $entity));
		}
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
	function domId($options = null, $id = 'id') {
		$view =& ClassRegistry::getObject('view');

		if (is_array($options) && array_key_exists($id, $options) && $options[$id] === null) {
			unset($options[$id]);
			return $options;
		} elseif (!is_array($options) && $options !== null) {
			$this->setEntity($options);
			return $this->domId();
		}

		$entity = $view->entity();
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
 * @access protected
 * @todo Refactor this method to not have as many input/output options.
 */
	function _name($options = array(), $field = null, $key = 'name') {
		$view =& ClassRegistry::getObject('view');
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
				$name = 'data[' . implode('][', $view->entity()) . ']';
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
 * @access public
 * @todo Refactor this method to not have as many input/output options.
 */
	function value($options = array(), $field = null, $key = 'value') {
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

		$view =& ClassRegistry::getObject('view');
		$result = null;

		$entity = $view->entity();
		if (!empty($this->data) && !empty($entity)) {
			$result = Set::extract($this->data, join('.', $entity));
		}

		$habtmKey = $this->field();
		if (empty($result) && isset($this->data[$habtmKey][$habtmKey]) && is_array($this->data[$habtmKey])) {
			$result = $this->data[$habtmKey][$habtmKey];
		} elseif (empty($result) && isset($this->data[$habtmKey]) && is_array($this->data[$habtmKey])) {
			if (ClassRegistry::isKeySet($habtmKey)) {
				$model =& ClassRegistry::getObject($habtmKey);
				$result = $this->__selectedArray($this->data[$habtmKey], $model->primaryKey);
			}
		}

		if (is_array($result)) {
			if (array_key_exists($view->fieldSuffix, $result)) {
				$result = $result[$view->fieldSuffix];
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
 * @access protected
 */
	function _initInputField($field, $options = array()) {
		if ($field !== null) {
			$this->setEntity($field);
		}
		$options = (array)$options;
		$options = $this->_name($options);
		$options = $this->value($options);
		$options = $this->domId($options);
		if ($this->tagIsInvalid()) {
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
 * @access public
 */
	function addClass($options = array(), $class = null, $key = 'class') {
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
	function output($str) {
		return $str;
	}

/**
 * Before render callback. beforeRender is called before the view file is rendered.
 *
 * Overridden in subclasses.
 *
 * @return void
 * @access public
 */
	function beforeRender() {
	}

/**
 * After render callback.  afterRender is called after the view file is rendered
 * but before the layout has been rendered.
 *
 * Overridden in subclasses.
 *
 * @return void
 * @access public
 */
	function afterRender() {
	}

/**
 * Before layout callback.  beforeLayout is called before the layout is rendered.
 *
 * Overridden in subclasses.
 *
 * @return void
 * @access public
 */
	function beforeLayout() {
	}

/**
 * After layout callback.  afterLayout is called after the layout has rendered.
 *
 * Overridden in subclasses.
 *
 * @return void
 * @access public
 */
	function afterLayout() {
	}

/**
 * Transforms a recordset from a hasAndBelongsToMany association to a list of selected
 * options for a multiple select element
 *
 * @param mixed $data
 * @param string $key
 * @return array
 * @access private
 */
	function __selectedArray($data, $key = 'id') {
		if (!is_array($data)) {
			$model = $data;
			if (!empty($this->data[$model][$model])) {
				return $this->data[$model][$model];
			}
			if (!empty($this->data[$model])) {
				$data = $this->data[$model];
			}
		}
		$array = array();
		if (!empty($data)) {
			foreach ($data as $var) {
				$array[$var[$key]] = $var[$key];
			}
		}
		return $array;
	}

/**
 * Resets the vars used by Helper::clean() to null
 *
 * @return void
 * @access private
 */
	function __reset() {
		$this->__tainted = null;
		$this->__cleaned = null;
	}

/**
 * Removes harmful content from output
 *
 * @return void
 * @access private
 */
	function __clean() {
		if (get_magic_quotes_gpc()) {
			$this->__cleaned = stripslashes($this->__tainted);
		} else {
			$this->__cleaned = $this->__tainted;
		}

		$this->__cleaned = str_replace(array("&amp;", "&lt;", "&gt;"), array("&amp;amp;", "&amp;lt;", "&amp;gt;"), $this->__cleaned);
		$this->__cleaned = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u', "$1;", $this->__cleaned);
		$this->__cleaned = preg_replace('#(&\#x*)([0-9A-F]+);*#iu', "$1$2;", $this->__cleaned);
		$this->__cleaned = html_entity_decode($this->__cleaned, ENT_COMPAT, "UTF-8");
		$this->__cleaned = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>#iUu', "$1>", $this->__cleaned);
		$this->__cleaned = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*)[\\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2nojavascript...', $this->__cleaned);
		$this->__cleaned = preg_replace('#([a-z]*)[\x00-\x20]*=([\'\"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2novbscript...', $this->__cleaned);
		$this->__cleaned = preg_replace('#([a-z]*)[\x00-\x20]*=*([\'\"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#iUu','$1=$2nomozbinding...', $this->__cleaned);
		$this->__cleaned = preg_replace('#([a-z]*)[\x00-\x20]*=([\'\"]*)[\x00-\x20]*data[\x00-\x20]*:#Uu', '$1=$2nodata...', $this->__cleaned);
		$this->__cleaned = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*expression[\x00-\x20]*\([^>]*>#iU', "$1>", $this->__cleaned);
		$this->__cleaned = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*behaviour[\x00-\x20]*\([^>]*>#iU', "$1>", $this->__cleaned);
		$this->__cleaned = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*>#iUu', "$1>", $this->__cleaned);
		$this->__cleaned = preg_replace('#</*\w+:\w[^>]*>#i', "", $this->__cleaned);
		do {
			$oldstring = $this->__cleaned;
			$this->__cleaned = preg_replace('#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>#i', "", $this->__cleaned);
		} while ($oldstring != $this->__cleaned);
		$this->__cleaned = str_replace(array("&amp;", "&lt;", "&gt;"), array("&amp;amp;", "&amp;lt;", "&amp;gt;"), $this->__cleaned);
	}
}
