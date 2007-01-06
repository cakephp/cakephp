<?php
/* SVN FILE: $Id$ */
/**
 * Washes strings from unwanted noise.
 *
 * Helpful methods to make unsafe strings usable.
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
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Data Sanitization.
 *
 * Removal of alpahnumeric characters, SQL-safe slash-added strings, HTML-friendly strings,
 * and all of the above on arrays.
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class Sanitize{
/**
 * Removes any non-alphanumeric characters.
 *
 * @param string $string
 * @return string
 */
	function paranoid($string, $allowed = array()) {
		$allow = null;
		if (!empty($allowed)) {
			foreach($allowed as $value) {
				$allow .= "\\$value";
			}
		}

		if (is_array($string)) {
			foreach($string as $key => $clean) {
				$cleaned[$key] = preg_replace("/[^{$allow}a-zA-Z0-9]/", '', $clean);
			}
		} else {
			$cleaned = preg_replace("/[^{$allow}a-zA-Z0-9]/", '', $string);
		}
		return $cleaned;
	}
/**
 * @deprecated
 * @see Sanitize::escape()
 */
	function sql($string) {
		if (!ini_get('magic_quotes_gpc')) {
			$string = addslashes($string);
		}
		return $string;
	}
/**
 * Makes a string SQL-safe.
 *
 * @param string $string
 * @param string $connection
 * @return string
 */
	function escape($string, $connection = 'default') {
		$db = ConnectionManager::getDataSource($connection);
		return $db->value($string);
	}
/**
 * Returns given string safe for display as HTML. Renders entities.
 *
 * @param string $string
 * @param boolean $remove If true, the string is stripped of all HTML tags
 * @return string
 */
	function html($string, $remove = false) {
		if ($remove) {
			$string = strip_tags($string);
		} else {
			$patterns = array("/\&/", "/%/", "/</", "/>/", '/"/', "/'/", "/\(/", "/\)/", "/\+/", "/-/");
			$replacements = array("&amp;", "&#37;", "&lt;", "&gt;", "&quot;", "&#39;", "&#40;", "&#41;", "&#43;", "&#45;");
			$string = preg_replace($patterns, $replacements, $string);
		}
		return $string;
	}
/**
 * Strips extra whitespace from output
 *
 * @param string $str
 */
	function stripWhitespace($str) {
		$r = preg_replace('/[\n\r\t]+/', '', $str);
		return preg_replace('/\s{2,}/', ' ', $r);
	}
/**
 * Strips image tags from output
 *
 * @param string $str
 */
	function stripImages($str) {
		$str = preg_replace('/(<a[^>]*>)(<img[^>]+alt=")([^"]*)("[^>]*>)(<\/a>)/i', '$1$3$5<br />', $str);
		$str = preg_replace('/(<img[^>]+alt=")([^"]*)("[^>]*>)/i', '$2<br />', $str);
		$str = preg_replace('/<img[^>]*>/i', '', $str);
		return $str;
	}
/**
 * Strips scripts and stylesheets from output
 *
 * @param string $str
 */
	function stripScripts($str) {
		return preg_replace('/(<link[^>]+rel="[^"]*stylesheet"[^>]*>|<img[^>]*>|style="[^"]*")|<script[^>]*>.*?<\/script>|<style[^>]*>.*?<\/style>|<!--.*?-->/i', '', $str);
	}
/**
 * Strips extra whitespace, images, scripts and stylesheets from output
 *
 * @param string $str
 */
	function stripAll($str) {
		$str = $this->stripWhitespace($str);
		$str = $this->stripImages($str);
		$str = $this->stripScripts($str);
		return $str;
	}
/**
 * Strips the specified tags from output
 *
 * @param string $str
 * @param string $tag
 * @param string $tag
 * @param string ...
 */
	function stripTags() {
		$params = params(func_get_args());
		$str = $params[0];

		for($i = 1; $i < count($params); $i++) {
			$str = preg_replace('/<' . $params[$i] . '[^>]*>/i', '', $str);
			$str = preg_replace('/<\/' . $params[$i] . '[^>]*>/i', '', $str);
		}
		return $str;
	}
/**
 * @deprecated
 * @see Sanitize::clean
 */
	function cleanArray(&$toClean) {
		return $this->cleanArrayR($toClean);
	}
/**
 * @deprecated
 * @see Sanitize::clean
 */
	function cleanArrayR(&$toClean) {
		if (is_array($toClean)) {
			while(list($k, $v) = each($toClean)) {
				if (is_array($toClean[$k])) {
					$this->cleanArray($toClean[$k]);
				} else {
					$toClean[$k] = $this->cleanValue($v);
				}
			}
		} else {
			return null;
		}
	}
/**
 * @deprecated
 * @see Sanitize::clean
 */
	function cleanValue($val) {
		if ($val == "") {
			return "";
		}
		//Replace odd spaces with safe ones
		$val = str_replace(" ", " ", $val);
		$val = str_replace(chr(0xCA), "", $val);
		//Encode any HTML to entities.
		$val = $this->html($val);
		//Double-check special chars and replace carriage returns with new lines
		$val = preg_replace("/\\\$/", "$", $val);
		$val = preg_replace("/\r\n/", "\n", $val);
		$val = str_replace("!", "!", $val);
		$val = str_replace("'", "'", $val);
		//Allow unicode (?)
		$val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val);
		//Add slashes for SQL
		$val = $this->sql($val);
		//Swap user-inputted backslashes (?)
		$val = preg_replace("/\\\(?!&amp;#|\?#)/", "\\", $val);
		return $val;
	}
/**
 * Sanitizes given array or value for safe input.
 *
 * @param mixed $data
 * @param string $connection
 * @return mixed
 */
	function clean($data, $connection = 'default') {
		if (is_array($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = Sanitize::clean($val);
			}
		} else {
			if (empty($data)) {
				return $data;
			}

			//Replace odd spaces with safe ones
			$val = str_replace(chr(0xCA), '', str_replace(' ', ' ', $data));
			//Encode any HTML to entities.
			$val = Sanitize::html($val);

			//Double-check special chars and remove carriage returns
			//For increased SQL security
			$val = preg_replace("/\\\$/", "$", $val);
			$val = preg_replace("/\r/", "", $val);
			$val = str_replace("'", "'", str_replace("!", "!", $val));

			//Allow unicode (?)
			$val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val);

			// Escape for DB output
			$val = Sanitize::escape($val, $connection);

			//Swap user-inputted backslashes (?)
			$val = preg_replace("/\\\(?!&amp;#|\?#)/", "\\", $val);
			return $val;
		}
	}
/**
 * Formats column data from definition in DBO's $columns array
 *
 * @param Model $model The model containing the data to be formatted
 * @return void
 * @access public
 */
	function formatColumns(&$model) {
		foreach($model->data as $name => $values) {
			if ($name == $model->name) {
				$curModel =& $model;
			} elseif (isset($model->{$name}) && is_object($model->{$name}) && is_subclass_of($model->{$name}, 'Model')) {
				$curModel =& $model->{$name};
			} else {
				$curModel = null;
			}

			if ($curModel != null) {
				foreach($values as $column => $data) {
					$colType = $curModel->getColumnType($column);

					if ($colType != null) {
						$db =& ConnectionManager::getDataSource($curModel->useDbConfig);
						$colData = $db->columns[$colType];

						if (isset($colData['limit']) && strlen(strval($data)) > $colData['limit']) {
							$data = substr(strval($data), 0, $colData['limit']);
						}

						if (isset($colData['formatter']) || isset($colData['format'])) {

							switch(strtolower($colData['formatter'])) {
								case 'date':
									$data = date($colData['format'], strtotime($data));
								break;
								case 'sprintf':
									$data = sprintf($colData['format'], $data);
								break;
								case 'intval':
									$data = intval($data);
								break;
								case 'floatval':
									$data = floatval($data);
								break;
							}
						}
						$model->data[$name][$column]=$data;
						/*
						switch($colType) {
							case 'integer':
							case 'int':
								return  $data;
							break;
							case 'string':
							case 'text':
							case 'binary':
							case 'date':
							case 'time':
							case 'datetime':
							case 'timestamp':
							case 'date':
								return "'" . $data . "'";
							break;
						}
						*/
					}
				}
			}
		}
	}
}
?>