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

class Gettext extends CatalogEngine {

	public function read($domain, array $locales, $category) {
		$paths = $this->_searchPaths($domain);

		foreach ($paths as $path) {
			foreach ($locales as $locale) {
				$filename = $path . $locale . DS . $category . DS . $domain;
				$entries = $this->parse($filename);
				if ($entries !== false) {
					$entries = $this->_header($entries);
					return $entries;
				}
			}
		}

		return false;
	}

	protected function _header($entries) {
		if (!isset($entries[""])) {
			return $entries;
		}

		$headers = [];
		foreach (explode("\n", $entries[""]) as $line) {
			$header = strtok($line, ':');
			$line = trim(strtok("\n"));
			$headers[strtolower($header)] = $line;
		}
		$entries['%po-header'] = $headers;

		if (isset($entries[null])) {
			unset($entries[null]);
		}

		return $entries;
	}

	public function parse($filename) {
		foreach (['mo', 'po'] as $type) {
			$return = $this->{'_parse' . ucfirst($type)}($filename . '.' . $type);
			if ($return !== false) {
				return $return;
			}
		}
		return false;
	}

	protected function _parseMo($filename) {
		if (!file_exists($filename)) {
			return false;
		}

		$data = file_get_contents($filename);
		if (empty($data)) {
			return false;
		}

		// @codingStandardsIgnoreStart
		$translations = [];
		$header = substr($data, 0, 20);
		$header = unpack('L1magic/L1version/L1count/L1o_msg/L1o_trn', $header);
		extract($header);

		if ((dechex($magic) === '950412de' || dechex($magic) === 'ffffffff950412de') && !$version) {
			for ($n = 0; $n < $count; $n++) {
				$r = unpack("L1len/L1offs", substr($data, $o_msg + $n * 8, 8));
				$msgid = substr($data, $r["offs"], $r["len"]);
				unset($msgid_plural);

				if (strpos($msgid, "\000")) {
					list($msgid, $msgid_plural) = explode("\000", $msgid);
				}
				$r = unpack("L1len/L1offs", substr($data, $o_trn + $n * 8, 8));
				$msgstr = substr($data, $r["offs"], $r["len"]);

				if (strpos($msgstr, "\000")) {
					$msgstr = explode("\000", $msgstr);
				}
				$translations[$msgid] = $msgstr;

				if (isset($msgid_plural)) {
					$translations[$msgid_plural] =& $translations[$msgid];
				}
			}
		}
		// @codingStandardsIgnoreEnd

		return $translations;
	}

	protected function _parsePo($filename) {
		if (!file_exists($filename)) {
			return false;
		}

		$file = fopen($filename, 'r');
		if (!$file) {
			return false;
		}

		$type = 0;
		$translations = [];
		$translationKey = '';
		$plural = 0;
		$header = '';

		do {
			$line = trim(fgets($file));
			if ($line === '' || $line[0] === '#') {
				continue;
			}
			if (preg_match("/msgid[[:space:]]+\"(.+)\"$/i", $line, $regs)) {
				$type = 1;
				$translationKey = stripcslashes($regs[1]);
			} elseif (preg_match("/msgid[[:space:]]+\"\"$/i", $line, $regs)) {
				$type = 2;
				$translationKey = '';
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && ($type == 1 || $type == 2 || $type == 3)) {
				$type = 3;
				$translationKey .= stripcslashes($regs[1]);
			} elseif (preg_match("/msgstr[[:space:]]+\"(.+)\"$/i", $line, $regs) && ($type == 1 || $type == 3) && $translationKey) {
				$translations[$translationKey] = stripcslashes($regs[1]);
				$type = 4;
			} elseif (preg_match("/msgstr[[:space:]]+\"\"$/i", $line, $regs) && ($type == 1 || $type == 3) && $translationKey) {
				$type = 4;
				$translations[$translationKey] = '';
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 4 && $translationKey) {
				$translations[$translationKey] .= stripcslashes($regs[1]);
			} elseif (preg_match("/msgid_plural[[:space:]]+\".*\"$/i", $line, $regs)) {
				$type = 6;
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 6 && $translationKey) {
				$type = 6;
			} elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"(.+)\"$/i", $line, $regs) && ($type == 6 || $type == 7) && $translationKey) {
				$plural = $regs[1];
				$translations[$translationKey][$plural] = stripcslashes($regs[2]);
				$type = 7;
			} elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"\"$/i", $line, $regs) && ($type == 6 || $type == 7) && $translationKey) {
				$plural = $regs[1];
				$translations[$translationKey][$plural] = '';
				$type = 7;
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 7 && $translationKey) {
				$translations[$translationKey][$plural] .= stripcslashes($regs[1]);
			} elseif (preg_match("/msgstr[[:space:]]+\"(.+)\"$/i", $line, $regs) && $type == 2 && !$translationKey) {
				$header .= stripcslashes($regs[1]);
				$type = 5;
			} elseif (preg_match("/msgstr[[:space:]]+\"\"$/i", $line, $regs) && !$translationKey) {
				$header = '';
				$type = 5;
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 5) {
				$header .= stripcslashes($regs[1]);
			} else {
				unset($translations[$translationKey]);
				$type = 0;
				$translationKey = '';
				$plural = 0;
			}
		} while (!feof($file));
		fclose($file);

		$merge[''] = $header;
		return array_merge($merge, $translations);
	}

}
