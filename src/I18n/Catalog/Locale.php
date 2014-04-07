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
namespace Cake\I18n\Catalog;

use Cake\I18n\CatalogEngine;
use Cake\Utility\String;

class Locale extends CatalogEngine {

/**
 * Escape string
 *
 * @var string
 */
	protected $_escape = null;

/**
 * [read description]
 * @param string $domain [description]
 * @param array $locales [description]
 * @param string $category [description]
 * @return array|boolean [description]
 */
	public function read($domain, array $locales, $category) {
		$paths = $this->_searchPaths($domain);

		foreach ($paths as $path) {
			foreach ($locales as $locale) {
				$filename = $path . $locale . DS . $category;
				$entries = $this->_parse($filename);
				if ($entries !== false) {
					return $entries;
				}
			}
		}

		return false;
	}

/**
 * Parses a locale definition file following the POSIX standard
 *
 * @param string $filename Locale definition filename
 * @return mixed Array of definitions on success or false on failure
 */
	protected function _parse($filename) {
		if (!file_exists($filename) || !$file = fopen($filename, 'r')) {
			return false;
		}

		$definitions = array();
		$comment = '#';
		$escape = '\\';
		$currentToken = false;
		$value = '';

		while ($line = fgets($file)) {
			$line = trim($line);
			if (empty($line) || $line[0] === $comment) {
				continue;
			}
			$parts = preg_split("/[[:space:]]+/", $line);
			if ($parts[0] === 'comment_char') {
				$comment = $parts[1];
				continue;
			}
			if ($parts[0] === 'escape_char') {
				$escape = $parts[1];
				continue;
			}
			$count = count($parts);
			if ($count === 2) {
				$currentToken = $parts[0];
				$value = $parts[1];
			} elseif ($count === 1) {
				$value = is_array($value) ? $parts[0] : $value . $parts[0];
			} else {
				continue;
			}

			$len = strlen($value) - 1;
			if ($value[$len] === $escape) {
				$value = substr($value, 0, $len);
				continue;
			}

			$mustEscape = array($escape . ',', $escape . ';', $escape . '<', $escape . '>', $escape . $escape);
			$replacements = array_map('crc32', $mustEscape);
			$value = str_replace($mustEscape, $replacements, $value);
			$value = explode(';', $value);
			$this->_escape = $escape;
			foreach ($value as $i => $val) {
				$val = trim($val, '"');
				$val = preg_replace_callback('/(?:<)?(.[^>]*)(?:>)?/', array(&$this, '_parseLiteralValue'), $val);
				$val = str_replace($replacements, $mustEscape, $val);
				$value[$i] = $val;
			}
			if (count($value) === 1) {
				$definitions[$currentToken] = array_pop($value);
			} else {
				$definitions[$currentToken] = $value;
			}
		}

		return $definitions;
	}

/**
 * Auxiliary function to parse a symbol from a locale definition file
 *
 * @param string $string Symbol to be parsed
 * @return string parsed symbol
 */
	protected function _parseLiteralValue($string) {
		$string = $string[1];
		if (substr($string, 0, 2) === $this->_escape . 'x') {
			$delimiter = $this->_escape . 'x';
			return implode('', array_map('chr', array_map('hexdec', array_filter(explode($delimiter, $string)))));
		}
		if (substr($string, 0, 2) === $this->_escape . 'd') {
			$delimiter = $this->_escape . 'd';
			return implode('', array_map('chr', array_filter(explode($delimiter, $string))));
		}
		if ($string[0] === $this->_escape && isset($string[1]) && is_numeric($string[1])) {
			$delimiter = $this->_escape;
			return implode('', array_map('chr', array_filter(explode($delimiter, $string))));
		}
		if (substr($string, 0, 3) === 'U00') {
			$delimiter = 'U00';
			return implode('', array_map('chr', array_map('hexdec', array_filter(explode($delimiter, $string)))));
		}
		if (preg_match('/U([0-9a-fA-F]{4})/', $string, $match)) {
			return String::ascii(array(hexdec($match[1])));
		}
		return $string;
	}

}
