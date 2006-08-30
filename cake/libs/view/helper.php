<?php
/* SVN FILE: $Id$ */

/**
 * Backend for helpers.
 *
 * Internal methods for the Helpers.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.view
 * @since			CakePHP v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Backend for helpers.
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view
 */
class Helper extends Object {

/**
 * Holds tag templates.
 *
 * @access public
 * @var array
 */
	var $tags = array();
/**
 * Parses tag templates into $this->tags.
 *
 * @return void
 */
	function loadConfig() {
		$config = fileExistsInPath(CAKE . 'config' . DS . 'tags.ini.php');
		$cakeConfig = $this->readConfigFile($config);

		if (file_exists(APP . 'config' . DS . 'tags.ini.php')) {
			$appConfig = $this->readConfigFile(APP . 'config' . DS . 'tags.ini.php');
			$cakeConfig = am($cakeConfig, $appConfig);
		}
		return $cakeConfig;
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
		$base = strip_plugin($this->base, $this->plugin);
		$extension = null;

		if (is_array($url) && !empty($url)) {
			if (!isset($url['action'])) {
				$url['action'] = $this->params['action'];
			}
			if (!isset($url['controller'])) {
				$url['controller'] = $this->params['controller'];
			}
			if (!isset($url['plugin'])) {
				$url['plugin'] = $this->plugin;
			}
			if (isset($url['ext'])) {
				$extension = '.' . $url['ext'];
			}
			if (defined('CAKE_ADMIN') && !isset($url[CAKE_ADMIN]) && isset($this->params['admin'])) {
				$url[CAKE_ADMIN] = $this->params['admin'];
			}

			$named = $args = array();
			$keys = array_keys($url);
			$count = count($keys);
			for ($i = 0; $i < $count; $i++) {
				if (is_numeric($keys[$i])) {
					$args[] = $url[$keys[$i]];
				} else {
					if (!in_array($keys[$i], array('action', 'controller', 'plugin', 'ext'))) {
						$named[] = array($keys[$i], $url[$keys[$i]]);
					}
				}
			}

			$combined = '';
			if ($this->namedArgs) {
				if ($this->namedArgs === true) {
					$sep = $this->argSeparator;
				} elseif (is_array($this->namedArgs)) {
					$sep = '/';
				}

				$count = count($named);
				for ($i = 0; $i < $count; $i++) {
					$named[$i] = join($this->argSeparator, $named[$i]);
				}
				if (defined('CAKE_ADMIN') && isset($named[CAKE_ADMIN])) {
					unset($named[CAKE_ADMIN]);
				}
				$combined = join('/', $named);
			}

			$urlOut = array_filter(array($url['plugin'], $url['controller'], $url['action'], join('/', array_filter($args)), $combined));
			if (defined('CAKE_ADMIN') && isset($url[CAKE_ADMIN]) && $url[CAKE_ADMIN]) {
				array_unshift($urlOut, CAKE_ADMIN);
			}
			$output = $base . '/' . join('/', $urlOut);
		} else {
			if (((strpos($url, '://')) || (strpos($url, 'javascript:') === 0) || (strpos($url, 'mailto:') === 0)) || $url == '#') {
				return $this->output($url);
			}

			if (empty($url)) {
				return $this->here;
			} elseif($url{0} == '/') {
				$output = $base . $url;
			} else {
				$output = $base . '/' . strtolower($this->params['controller']) . '/' . $url;
			}
		}
		if ($full) {
			$output = FULL_BASE_URL . $output;
		}
		return $this->output($output . $extension);
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
		return list($this->view->model, $this->view->field) = explode("/", $tagValue);
	}
/**
 * Enter description here...
 *
 * @return string
 */
	function model() {
		return $this->view->model;
	}
/**
 * Enter description here...
 *
 * @return string
 */
	function field() {
		return $this->view->field;
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
		if (!isset($options[$id])) {
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
		if (is_string($options)) {
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
		if (isset($this->params['data'][$this->model()][$this->field()])) {
			$result = h($this->params['data'][$this->model()][$this->field()]);
		} elseif(isset($this->data[$this->model()][$this->field()])) {
			$result = h($this->data[$this->model()][$this->field()]);
		}

		if ($options !== 0) {
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
 * Returns an array of settings in given INI file.
 *
 * @param string $fileName
 * @return array
 */
	function readConfigFile($fileName) {
		$fileLineArray = file($fileName);

		foreach($fileLineArray as $fileLine) {
			$dataLine = trim($fileLine);
			$firstChar = substr($dataLine, 0, 1);

			if ($firstChar != ';' && $dataLine != '') {
				if ($firstChar == '[' && substr($dataLine, -1, 1) == ']') {
					// [section block] we might use this later do not know for sure
					// this could be used to add a key with the section block name
					// but it adds another array level
				} else {
					$delimiter = strpos($dataLine, '=');

					if ($delimiter > 0) {
						$key = strtolower(trim(substr($dataLine, 0, $delimiter)));
						$value = trim(substr($dataLine, $delimiter + 1));

						if (substr($value, 0, 1) == '"' && substr($value, -1) == '"') {
							$value = substr($value, 1, -1);
						}

						$iniSetting[$key] = stripcslashes($value);
					} else {
						$iniSetting[strtolower(trim($dataLine))] = '';
					}
				}
			} else {
			}
		}

		return $iniSetting;
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
}

?>