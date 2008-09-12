<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 1.2.0.4116
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 */
App::import('Core', 'l10n');
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class I18n extends Object {
/**
 * Instance of the I10n class for localization
 *
 * @var object
 * @access public
 */
	var $l10n = null;
/**
 * Current domain of translation
 *
 * @var string
 * @access public
 */
	var $domain = null;
/**
 * Current language used for translations
 *
 * @var string
 * @access private;
 */
	var $__lang = null;
/**
 * Translation strings for a specific domain read from the .mo or .po files
 *
 * @var array
 * @access private
 */
	var $__domains = array();
/**
 * Set to true when I18N::__bindTextDomain() is called for the first time.
 * If a translation file is found it is set to false again
 *
 * @var boolean
 * @access private
 */
	var $__noLocale = false;
/**
 * Determine if $__domains cache should be wrote
 *
 * @var boolean
 * @access private
 */
	var $__cache = false;
/**
 * Set to true when I18N::__bindTextDomain() is called for the first time.
 * If a translation file is found it is set to false again
 *
 * @var array
 * @access private
 */
	var $__categories = array('LC_CTYPE', 'LC_NUMERIC', 'LC_TIME', 'LC_COLLATE', 'LC_MONETARY', 'LC_MESSAGES', 'LC_ALL');
/**
 * Return a static instance of the I18n class
 *
 * @return object I18n
 * @access public
 */
	function &getInstance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] =& new I18n();
			$instance[0]->l10n =& new L10n();
		}
		return $instance[0];
	}
/**
 * Used by the translation functions in basics.php
 * Can also be used like I18n::translate(); but only if the uses('i18n'); has been used to load the class.
 *
 * @param string $singular String to translate
 * @param string $plural Plural string (if any)
 * @param string $domain Domain
 * @param string $category Category
 * @param integer $count Count
 * @return string translated strings.
 * @access public
 */
	function translate($singular, $plural = null, $domain = null, $category = null, $count = null) {
		if (!$category) {
			$category = 5;
		}

		$language = Configure::read('Config.language');
		if (!empty($_SESSION['Config']['language'])) {
			$language = $_SESSION['Config']['language'];
		}
		$_this =& I18n::getInstance();

		if (($_this->__lang && $_this->__lang !== $language) || !$_this->__lang) {
			$lang = $_this->l10n->get($language);
			$_this->__lang = $lang;
		}
		$_this->category = $_this->__categories[$category];

		if (is_null($domain)) {
			$domain = 'default';
		}
		$_this->domain = $domain . '_' . $_this->l10n->locale;

		if (empty($_this->__domains)) {
			$_this->__domains = Cache::read($_this->domain, '_cake_core_');
		}

		if (!isset($_this->__domains[$_this->category][$_this->__lang][$domain])) {
			$_this->__bindTextDomain($domain);
			$_this->__cache = true;
		}

		if (!isset($count)) {
			$pli = 0;
		} elseif (!empty($_this->__domains[$_this->category][$_this->__lang][$domain]["%plural-c"]) && $_this->__noLocale === false) {
			$ph = $_this->__domains[$_this->category][$_this->__lang][$domain]["%plural-c"];
			$pli = $_this->__pluralGuess($ph, $count);
		} else {
			if ($count != 1) {
				$pli = 1;
			} else {
				$pli = 0;
			}
		}

		if (!empty($_this->__domains[$_this->category][$_this->__lang][$domain][$singular])) {
			if (($trans = $_this->__domains[$_this->category][$_this->__lang][$domain][$singular]) || ($pli) && ($trans = $_this->__domains[$_this->category][$_this->__lang][$domain][$plural])) {
				if (is_array($trans)) {
					if (isset($trans[$pli])) {
						$trans = $trans[$pli];
					}
				}
				if (strlen($trans)) {
					$singular = $trans;
					return $singular;
				}
			}
		}

		if (!empty($pli)) {
			return($plural);
		}
		return($singular);
	}
/**
 * Attempts to find the plural form of a string.
 *
 * @param string $type Type
 * @param integrer $n Number
 * @return integer plural match
 * @access private
 */
	function __pluralGuess(&$type, $n) {
		if (is_string($type)) {
			if (($type == "nplurals=1;plural=0;") || !strlen($type)) {
				$type = -1;
			} elseif ($type == "nplurals=2;plural=n!=1;") {
				$type = 1;
			} elseif ($type == "nplurals=2;plural=n>1;") {
				$type = 2;
			} elseif (strpos($type, "n%100!=11")) {

				if (strpos($type, "n!=0")) {
					$type = 3;
				}

				if (strpos($type, "n%10<=4")) {
					$type = 4;
				}

				if (strpos($type, "n%10>=2")) {
					$type = 5;
				}
			} elseif (strpos($type, "n<=4")) {
				$type = 6;
			} elseif (strpos($type, "n==2")) {
				$type = 9;
			} elseif (strpos($type, "n%10>=2")) {
				$type = 7;
			} elseif (strpos($type, "n%100==3")) {
				$type = 8;
			} elseif (strpos($type, "n%100<20")) {
				$type = 10;
			}
		}

		switch ($type) {
			case -1:
				return 0;
			case 1:
				if ($n != 1) {
					return 1;
				}
				return 0;
			case 2:
				if ($n > 1) {
					return 1;
				}
				return 0;
			case 3:
				if (($n % 10 == 1) && ($n % 100 != 11)) {
					return 0;
				}

				if ($n != 0 ) {
					return 1;
				}
				return 2;
			case 4:
				if (($n % 10 == 1) && ($n % 100 != 11)) {
					return 0;
				}

				if (($n % 10 >= 2) && ($n % 10 <= 4) && ($n % 100 < 10 || $n % 100 >= 20)) {
					return 1;
				}
				return 2;
			case 5:
				if (($n % 10 == 1) && ($n % 100 != 11)) {
					return 0;
				}

				if (($n %10 >= 2) && ($n % 100 < 10 || $n % 100 >= 20)) {
					return 1;
				}
				return 2;
			case 6:
				if ($n==1) {
					return 0;
				}

				if ($n >= 2 && $n <= 4) {
					return 1;
				}
				return 2;
			case 7:
				if ($n==1) {
					return 0;
				}

				if ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) {
					return 1;
				}
				return 2;
			case 8:
				if ($n % 100 == 1) {
					return 0;
				}

				if ($n % 100 == 2) {
					return 1;
				}

				if ($n % 100 == 3 || $n % 100 == 4) {
					return 2;
				}
				return 3;
			case 9:
				if ($n == 1) {
					return 0;
				}

				if ($n == 2) {
					return 1;
				}
				return 2;
			case 10:
				if ($n == 1) {
					return 0;
				}

				if ($n == 0 || $n % 100 > 0 && $n % 100 < 20) {
					return 1;
				}
				return 2;
		}
	}
/**
 * Binds the given domain to a file in the specified directory.
 *
 * @param string $domain Domain to bind
 * @return string Domain binded
 * @access private
 */
	function __bindTextDomain($domain) {
		$_this =& I18n::getInstance();
		$_this->__noLocale = true;
		$core = true;
		$merge = array();

		$searchPath[] = APP . 'locale';
		$paths = Configure::read('Locale.path');

		if ($paths) {
			$searchPath[] = $paths;
		}

		$plugins = Configure::listObjects('plugin');

		if (!empty($plugins)) {

		}

		foreach ($searchPath as $directory) {
			foreach ($_this->l10n->languagePath as $lang) {
				$file = $directory . DS . $lang . DS . $_this->category . DS . $domain;

				if ($core) {
					$app = $directory . DS . $lang . DS . $_this->category . DS . 'core';
					if (file_exists($fn = "$app.mo")) {
						$_this->__loadMo($fn, $domain);
						$_this->__noLocale = false;
						$merge[$_this->category][$_this->__lang][$domain] = $_this->__domains[$_this->category][$_this->__lang][$domain];
						$core = null;
					} elseif (file_exists($fn = "$app.po") && ($f = fopen($fn, "r"))) {
						$_this->__loadPo($f, $domain);
						$_this->__noLocale = false;
						$merge[$_this->category][$_this->__lang][$domain] = $_this->__domains[$_this->category][$_this->__lang][$domain];
						$core = null;
					}
				}

				if (file_exists($fn = "$file.mo")) {
					$_this->__loadMo($fn, $domain);
					$_this->__noLocale = false;
					break 2;
				} elseif (file_exists($fn = "$file.po") && ($f = fopen($fn, "r"))) {
					$_this->__loadPo($f, $domain);
					$_this->__noLocale = false;
					break 2;
				}
			}
		}

		if (empty($_this->__domains[$_this->category][$_this->__lang][$domain])) {
			$_this->__domains[$_this->category][$_this->__lang][$domain] = array();
			return($domain);
		}

		if ($head = $_this->__domains[$_this->category][$_this->__lang][$domain][""]) {
			foreach (explode("\n", $head) as $line) {
				$header = strtok($line,":");
				$line = trim(strtok("\n"));
				$_this->__domains[$_this->category][$_this->__lang][$domain]["%po-header"][strtolower($header)] = $line;
			}

			if (isset($_this->__domains[$_this->category][$_this->__lang][$domain]["%po-header"]["plural-forms"])) {
				$switch = preg_replace("/(?:[() {}\\[\\]^\\s*\\]]+)/", "", $_this->__domains[$_this->category][$_this->__lang][$domain]["%po-header"]["plural-forms"]);
				$_this->__domains[$_this->category][$_this->__lang][$domain]["%plural-c"] = $switch;
				unset($_this->__domains[$_this->category][$_this->__lang][$domain]["%po-header"]);
			}
			$_this->__domains = Set::pushDiff($_this->__domains, $merge);

			if (isset($_this->__domains[$_this->category][$_this->__lang][$domain][null])) {
				unset($_this->__domains[$_this->category][$_this->__lang][$domain][null]);
			}
		}
		return($domain);
	}
/**
 * Loads the binary .mo file for translation and sets the values for this translation in the var I18n::__domains
 *
 * @param resource $file Binary .mo file to load
 * @param string $domain Domain where to load file in
 * @access private
 */
	function __loadMo($file, $domain) {
		$_this =& I18n::getInstance();
		$data = file_get_contents($file);

		if ($data) {
			$header = substr($data, 0, 20);
			$header = unpack("L1magic/L1version/L1count/L1o_msg/L1o_trn", $header);
			extract($header);

			if ((dechex($magic) == '950412de' || dechex($magic) == 'ffffffff950412de') && $version == 0) {
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
					$_this->__domains[$_this->category][$_this->__lang][$domain][$msgid] = $msgstr;

					if (isset($msgid_plural)) {
						$_this->__domains[$_this->category][$_this->__lang][$domain][$msgid_plural] =& $_this->__domains[$_this->category][$_this->__lang][$domain][$msgid];
					}
				}
			}
		}
	}
/**
 * Loads the text .po file for translation and sets the values for this translation in the var I18n::__domains
 *
 * @param resource $file Text .po file to load
 * @param string $domain Domain to load file in
 * @return array Binded domain elements
 * @access private
 */
	function __loadPo($file, $domain) {
		$_this =& I18n::getInstance();
		$type = 0;
		$translations = array();
		$translationKey = "";
		$plural = 0;
		$header = "";

		do {
			$line = trim(fgets($file, 1024));

			if ($line == "" || $line[0] == "#") {
				continue;
			}

			if (preg_match("/msgid[[:space:]]+\"(.+)\"$/i", $line, $regs)) {
				$type = 1;
				$translationKey = stripcslashes($regs[1]);
			} elseif (preg_match("/msgid[[:space:]]+\"\"$/i", $line, $regs)) {
				$type = 2;
				$translationKey = "";
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && ($type == 1 || $type == 2 || $type == 3)) {
				$type = 3;
				$translationKey .= stripcslashes($regs[1]);
			} elseif (preg_match("/msgstr[[:space:]]+\"(.+)\"$/i", $line, $regs) && ($type == 1 || $type == 3) && $translationKey) {
				$translations[$translationKey] = stripcslashes($regs[1]);
				$type = 4;
			} elseif (preg_match("/msgstr[[:space:]]+\"\"$/i", $line, $regs) && ($type == 1 || $type == 3) && $translationKey) {
				$type = 4;
				$translations[$translationKey] = "";
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 4 && $translationKey) {
				$translations[$translationKey] .= stripcslashes($regs[1]);
			} elseif (preg_match("/msgid_plural[[:space:]]+\".*\"$/i", $line, $regs)) {
				$type = 6;
			} elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"(.+)\"$/i", $line, $regs) && ($type == 6) && $translationKey) {
				$plural = $regs[1];
				$translations[$translationKey][$plural] = stripcslashes($regs[2]);
				$type = 6;
			} elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"\"$/i", $line, $regs) && ($type == 6) && $translationKey) {
				$plural = $regs[1];
				$translations[$translationKey][$plural] = "";
				$type = 6;
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 6 && $translationKey) {
				unset($translations[$translationKey]);
				$type = 0;
				$translationKey = "";
				$plural = 0;
			} elseif (preg_match("/msgstr[[:space:]]+\"(.+)\"$/i", $line, $regs) && $type == 2 && !$translationKey) {
				$header .= stripcslashes($regs[1]);
				$type = 5;
			} elseif (preg_match("/msgstr[[:space:]]+\"\"$/i", $line, $regs) && !$translationKey) {
				$header = "";
				$type = 5;
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 5) {
				$header .= stripcslashes($regs[1]);
			} else {
				unset($translations[$translationKey]);
				$type = 0;
				$translationKey = "";
				$plural = 0;
			}
		} while (!feof($file));

		fclose($file);
		$merge[""] = $header;
		return $_this->__domains[$_this->category][$_this->__lang][$domain] = array_merge($merge ,$translations);
	}
/**
 * Object destructor
 *
 * Write cache file if changes have been made to the $__map or $__paths
 * @access private
 */
	function __destruct() {
		$_this =& I18n::getInstance();
		if ($_this->__cache) {
			Cache::write($_this->domain, array_filter($_this->__domains), '_cake_core_');
		}
	}
}
?>