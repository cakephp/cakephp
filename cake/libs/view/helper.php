<?php
/* SVN FILE: $Id$ */

/**
 * Backend for helpers.
 *
 * Internal methods for the Helpers.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.view
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Included libs
 */
uses('overloadable');

/**
 * Backend for helpers.
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view
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
 * Theme name
 *
 * @var string
 */
	var $themeWeb = null;
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
 * Default overload methods
 *
 * @access protected
 */
	function get__($name) {}
	function set__($name, $value) {}
	function call__($method, $params) {
		trigger_error(sprintf(__('Method %1$s::%2$s does not exist', true), get_class($this), $method), E_USER_ERROR);
	}

/**
 * Parses tag templates into $this->tags.
 *
 * @return void
 */
	function loadConfig() {
		if (file_exists(APP . 'config' . DS . 'tags.php')) {
			require(APP . 'config' . DS . 'tags.php');
			$this->tags = am($this->tags, $tags);
		}
		return $this->tags;
	}
/**
 * Finds URL for specified action.
 *
 * Returns an URL pointing to a combination of controller and action. Param
 * $url can be:
 *	+ Empty - the method will find adress to actuall controller/action.
 *	+ '/' - the method will find base URL of application.
 *	+ A combination of controller/action - the method will find url for it.
 *
 * @param  mixed  $url    Cake-relative URL, like "/products/edit/92" or "/presidents/elect/4"
 *                        or an array specifying any of the following: 'controller', 'action',
 *                        and/or 'plugin', in addition to named arguments (keyed array elements),
 *                        and standard URL arguments (indexed array elements)
 * @param boolean $full   If true, the full base URL will be prepended to the result
 * @return string  Full translated URL with base path.
 */
	function url($url = null, $full = false) {
		return Router::url($url, $full);
	}
/**
 * Returns a space-delimited string with items of the $options array. If a
 * key of $options array happens to be one of:
 *	+ 'compact'
 *	+ 'checked'
 *	+ 'declare'
 *	+ 'readonly'
 *	+ 'disabled'
 *	+ 'selected'
 *	+ 'defer'
 *	+ 'ismap'
 *	+ 'nohref'
 *	+ 'noshade'
 *	+ 'nowrap'
 *	+ 'multiple'
 *	+ 'noresize'
 *
 * And its value is one of:
 *	+ 1
 *	+ true
 *	+ 'true'
 *
 * Then the value will be reset to be identical with key's name.
 * If the value is not one of these 3, the parameter is not output.
 *
 * @param  array  $options Array of options.
 * @param  array  $exclude Array of options to be excluded.
 * @param  string $insertBefore String to be inserted before options.
 * @param  string $insertAfter  String to be inserted ater options.
 * @return string
 */
	function _parseAttributes($options, $exclude = null, $insertBefore = ' ', $insertAfter = null) {
		$minimizedAttributes = array('compact', 'checked', 'declare', 'readonly', 'disabled', 'selected', 'defer', 'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize');
		if (!is_array($exclude)) {
			$exclude = array();
		}

		if (is_array($options)) {
			$out = array();

			foreach($options as $key => $value) {
				if (!in_array($key, $exclude)) {
					if (in_array($key, $minimizedAttributes) && ($value === 1 || $value === true || $value === 'true' || in_array($value, $minimizedAttributes))) {
						$value = $key;
					} elseif(in_array($key, $minimizedAttributes)) {
						continue;
					}
					$out[] = "{$key}=\"{$value}\"";
				}
			}
			$out = join(' ', $out);
			return $out ? $insertBefore . $out . $insertAfter : null;
		} else {
			return $options ? $insertBefore . $options . $insertAfter : null;
		}
	}
/**
 * @deprecated Name changed to '_parseAttributes'. Version 0.9.2.
 * @see HtmlHelper::_parseAttributes()
 * @param  array  $options Array of options.
 * @param  array  $exclude Array of options to be excluded.
 * @param  string $insertBefore String to be inserted before options.
 * @param  string $insertAfter  String to be inserted ater options.
 * @return string
 */
	function parseHtmlOptions($options, $exclude = null, $insertBefore = ' ', $insertAfter = null) {
		if (!is_array($exclude)) {
			$exclude = array();
		}

		if (is_array($options)) {
			$out = array();

			foreach($options as $k => $v) {
				if (!in_array($k, $exclude)) {
					$out[] = "{$k}=\"{$v}\"";
				}
			}
			$out = join(' ', $out);
			return $out ? $insertBefore . $out . $insertAfter : null;
		} else {
			return $options ? $insertBefore . $options . $insertAfter : null;
		}
	}
/**
 * Sets this helper's model and field properties to the slash-separated value-pair in $tagValue.
 *
 * @param string $tagValue A field name, like "Modelname/fieldname"
 */
	function setFormTag($tagValue) {
		$view =& ClassRegistry::getObject('view');
		$parts = explode("/", $tagValue);

		if (count($parts) == 1) {
			$view->field = $parts[0];
		} elseif (count($parts) == 2 && is_numeric($parts[0])) {
			$view->modelId = $parts[0];
			$view->field = $parts[1];
		} elseif (count($parts) == 2) {
			$view->model = $parts[0];
			$view->field = $parts[1];
		} elseif (count($parts) == 3) {
			$view->model   = $parts[0];
			$view->modelId = $parts[1];
			$view->field   = $parts[2];
		}
	}
/**
 * Gets the currently-used model of the rendering context.
 *
 * @return string
 */
	function model() {
		$view =& ClassRegistry::getObject('view');
		return $view->model;
	}
/**
 * Gets the currently-used model field of the rendering context.
 *
 * @return string
 */
	function field() {
		$view =& ClassRegistry::getObject('view');
		return $view->field;
	}
/**
 * Returns false if given FORM field has no errors. Otherwise it returns the constant set in the array Model->validationErrors.
 *
 * @param string $model Model name as string
 * @param string $field		Fieldname as string
 * @return boolean True on errors.
 */
	function tagIsInvalid($model = null, $field = null) {
		if ($model == null) {
			$model = $this->model();
		}
		if ($field == null) {
			$field = $this->field();
		}
		return empty($this->validationErrors[$model][$field]) ? 0 : $this->validationErrors[$model][$field];
	}
/**
 * Generates a DOM ID for the selected element, if one is not set.
 *
 * @param array $options
 * @param string $id
 * @return array
 */
	function domId($options = array(), $id = 'id') {
		if (is_array($options) && !isset($options[$id])) {
			$options[$id] = $this->model() . Inflector::camelize($this->field());
		}
		return $options;
	}
/**
 * Gets the data for the current tag
 *
 * @param array $options
 * @param string $key
 * @return array
 */
	function __value($options = array(), $field = null, $key = 'value') {
		if ($options === null) {
			$options = array();
		} elseif (is_string($options)) {
			$field = $options;
			$options = 0;
		}

		if ($field != null) {
			$this->setFormTag($field);
		}

		if (is_array($options) && isset($options[$key])) {
			return $options;
		}

		$result = null;
		if (isset($this->data[$this->model()][$this->field()])) {
			$result = h($this->data[$this->model()][$this->field()]);
		} elseif (isset($this->data[$this->field()]) && is_array($this->data[$this->field()])) {
			if (ClassRegistry::isKeySet($this->field())) {
				$key =& ClassRegistry::getObject($this->field());
				$result = $this->__selectedArray($this->data[$this->field()], $key->primaryKey);
			}
		}

		if (is_array($options)) {
			$options[$key] = $result;
			return $options;
		} else {
			return $result;
		}
	}
/**
 * Adds the given class to the element options
 *
 * @param array $options
 * @param string $class
 * @param string $key
 * @return array
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
 * @param  string  $str	String to be output.
 * @return string
 */
	function output($str) {
		return $str;
	}
/**
 * Assigns values to tag templates.
 *
 * Finds a tag template by $keyName, and replaces $values's keys with
 * $values's keys.
 *
 * @param  string $keyName Name of the key in the tag array.
 * @param  array  $values  Values to be inserted into tag.
 * @return string Tag with inserted values.
 */
	function assign($keyName, $values) {
		return str_replace('%%' . array_keys($values) . '%%', array_values($values), $this->tags[$keyName]);
	}
/**
 * Before render callback.  Overridden in subclasses.
 *
 * @return void
 */
	function beforeRender() {
	}
/**
 * After render callback.  Overridden in subclasses.
 *
 * @return void
 */
	function afterRender() {
	}
/**
 * Before layout callback.  Overridden in subclasses.
 *
 * @return void
 */
	function beforeLayout() {
	}
/**
 * After layout callback.  Overridden in subclasses.
 *
 * @return void
 */
	function afterLayout() {
	}
/**
 * Enter description here...
 *
 * @param mixed $data
 * @param string $key
 * @return array
 * @access private
 */
	function __selectedArray($data, $key = 'id') {
		if(!is_array($data)) {
			$model = $data;
			if(!empty($this->data[$model][$model])) {
				return $this->data[$model][$model];
			}
			if(!empty($this->data[$model])) {
				$data = $this->data[$model];
			}
		}
		$array = array();
		if(!empty($data)) {
			foreach($data as $var) {
				$array[$var[$key]] = $var[$key];
			}
		}
		return $array;
	}
}
?>