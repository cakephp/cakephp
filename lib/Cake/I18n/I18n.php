<?php
/**
 * Internationalization
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
 * @package       Cake.I18n
 * @since         CakePHP(tm) v 1.2.0.4116
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Included libraries.
 */
App::uses('CakePlugin', 'Core');
App::uses('L10n', 'I18n');
App::uses('Multibyte', 'I18n');

if (function_exists('mb_internal_encoding')) {
	$encoding = Configure::read('App.encoding');
	if (!empty($encoding)) {
		mb_internal_encoding($encoding);
	}
}

/**
 * I18n handles translation of Text and time format strings.
 *
 * @package       Cake.I18n
 */
class I18n {

/**
 * Instance of the I10n class for localization
 *
 * @var I10n
 */
	public $l10n = null;

/**
 * Current domain of translation
 *
 * @var string
 */
	public $domain = null;

/**
 * Current category of translation
 *
 * @var string
 */
	public $category = 'LC_MESSAGES';

/**
 * Current language used for translations
 *
 * @var string
 */
	protected $_lang = null;

/**
 * Translation strings for a specific domain read from the .mo or .po files
 *
 * @var array
 */
	protected $_domains = array();

/**
 * Set to true when I18N::_bindTextDomain() is called for the first time.
 * If a translation file is found it is set to false again
 *
 * @var boolean
 */
	protected $_noLocale = false;

/**
 * Set to true when I18N::_bindTextDomain() is called for the first time.
 * If a translation file is found it is set to false again
 *
 * @var array
 */
	protected $_categories = array(
		'LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME', 'LC_MESSAGES'
	);

/**
 * Return a static instance of the I18n class
 *
 * @return I18n
 */
	public static function &getInstance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] = new I18n();
			$instance[0]->l10n = new L10n();
		}
		return $instance[0];
	}

/**
 * Used by the translation functions in basics.php
 * Returns a translated string based on current language and translation files stored in locale folder
 *
 * @param string $singular String to translate
 * @param string $plural Plural string (if any)
 * @param string $domain Domain The domain of the translation.  Domains are often used by plugin translations
 * @param string $category Category The integer value of the category to use.
 * @param integer $count Count Count is used with $plural to choose the correct plural form.
 * @return string translated string.
 */
	public static function translate($singular, $plural = null, $domain = null, $category = 6, $count = null) {
		$_this = I18n::getInstance();

		if (strpos($singular, "\r\n") !== false) {
			$singular = str_replace("\r\n", "\n", $singular);
		}
		if ($plural !== null && strpos($plural, "\r\n") !== false) {
			$plural = str_replace("\r\n", "\n", $plural);
		}

		if (is_numeric($category)) {
			$_this->category = $_this->_categories[$category];
		}
		$language = Configure::read('Config.language');

		if (!empty($_SESSION['Config']['language'])) {
			$language = $_SESSION['Config']['language'];
		}

		if (($_this->_lang && $_this->_lang !== $language) || !$_this->_lang) {
			$lang = $_this->l10n->get($language);
			$_this->_lang = $lang;
		}

		if (is_null($domain)) {
			$domain = 'default';
		}

		$_this->domain = $domain . '_' . $_this->l10n->lang;

		if (!isset($_this->_domains[$domain][$_this->_lang])) {
			$_this->_domains[$domain][$_this->_lang] = Cache::read($_this->domain, '_cake_core_');
		}

		if (!isset($_this->_domains[$domain][$_this->_lang][$_this->category])) {
			$_this->_bindTextDomain($domain);
			Cache::write($_this->domain, $_this->_domains[$domain][$_this->_lang], '_cake_core_');
		}

		if ($_this->category == 'LC_TIME') {
			return $_this->_translateTime($singular,$domain);
		}

		if (!isset($count)) {
			$plurals = 0;
		} elseif (!empty($_this->_domains[$domain][$_this->_lang][$_this->category]["%plural-c"]) && $_this->_noLocale === false) {
			$header = $_this->_domains[$domain][$_this->_lang][$_this->category]["%plural-c"];
			$plurals = $_this->_pluralGuess($header, $count);
		} else {
			if ($count != 1) {
				$plurals = 1;
			} else {
				$plurals = 0;
			}
		}

		if (!empty($_this->_domains[$domain][$_this->_lang][$_this->category][$singular])) {
			if (($trans = $_this->_domains[$domain][$_this->_lang][$_this->category][$singular]) || ($plurals) && ($trans = $_this->_domains[$domain][$_this->_lang][$_this->category][$plural])) {
				if (is_array($trans)) {
					if (isset($trans[$plurals])) {
						$trans = $trans[$plurals];
					}
				}
				if (strlen($trans)) {
					return $trans;
				}
			}
		}

		if (!empty($plurals)) {
			return $plural;
		}
		return $singular;
	}

/**
 * Clears the domains internal data array.  Useful for testing i18n.
 *
 * @return void
 */
	public static function clear() {
		$self = I18n::getInstance();
		$self->_domains = array();
	}

/**
 * Get the loaded domains cache.
 *
 * @return array
 */
	public static function domains() {
		$self = I18n::getInstance();
		return $self->_domains;
	}

/**
 * Attempts to find the plural form of a string.
 *
 * @param string $header Type
 * @param integer $n Number
 * @return integer plural match
 */
	protected function _pluralGuess($header, $n) {
		if (!is_string($header) || $header === "nplurals=1;plural=0;" || !isset($header[0])) {
			return 0;
		}

		if ($header === "nplurals=2;plural=n!=1;") {
			return $n != 1 ? 1 : 0;
		} elseif ($header === "nplurals=2;plural=n>1;") {
			return $n > 1 ? 1 : 0;
		}

		if (strpos($header, "plurals=3")) {
			if (strpos($header, "100!=11")) {
				if (strpos($header, "10<=4")) {
					return $n % 10 == 1 && $n % 100 != 11 ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);
				} elseif (strpos($header, "100<10")) {
					return $n % 10 == 1 && $n % 100 != 11 ? 0 : ($n % 10 >= 2 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);
				}
				return $n % 10 == 1 && $n % 100 != 11 ? 0 : ($n != 0 ? 1 : 2);
			} elseif (strpos($header, "n==2")) {
				return $n == 1 ? 0 : ($n == 2 ? 1 : 2);
			} elseif (strpos($header, "n==0")) {
				return $n == 1 ? 0 : ($n == 0 || ($n % 100 > 0 && $n % 100 < 20) ? 1 : 2);
			} elseif (strpos($header, "n>=2")) {
				return $n == 1 ? 0 : ($n >= 2 && $n <= 4 ? 1 : 2);
			} elseif (strpos($header, "10>=2")) {
				return $n == 1 ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);
			}
			return $n % 10 == 1 ? 0 : ($n % 10 == 2 ? 1 : 2);
		} elseif (strpos($header, "plurals=4")) {
			if (strpos($header, "100==2")) {
				return $n % 100 == 1 ? 0 : ($n % 100 == 2 ? 1 : ($n % 100 == 3 || $n % 100 == 4 ? 2 : 3));
			} elseif (strpos($header, "n>=3")) {
				return $n == 1 ? 0 : ($n == 2 ? 1 : ($n == 0 || ($n >= 3 && $n <= 10) ? 2 : 3));
			} elseif (strpos($header, "100>=1")) {
				return $n == 1 ? 0 : ($n == 0 || ($n % 100 >= 1 && $n % 100 <= 10) ? 1 : ($n % 100 >= 11 && $n % 100 <= 20 ? 2 : 3));
			}
		} elseif (strpos($header, "plurals=5")) {
			return $n == 1 ? 0 : ($n == 2 ? 1 : ($n >= 3 && $n <= 6 ? 2 : ($n >= 7 && $n <= 10 ? 3 : 4)));
		}
	}

/**
 * Binds the given domain to a file in the specified directory.
 *
 * @param string $domain Domain to bind
 * @return string Domain binded
 */
	protected function _bindTextDomain($domain) {
		$this->_noLocale = true;
		$core = true;
		$merge = array();
		$searchPaths = App::path('locales');
		$plugins = CakePlugin::loaded();

		if (!empty($plugins)) {
			foreach ($plugins as $plugin) {
				$pluginDomain = Inflector::underscore($plugin);
				if ($pluginDomain === $domain) {
					$searchPaths[] = CakePlugin::path($plugin) . 'Locale' . DS;
					$searchPaths = array_reverse($searchPaths);
					break;
				}
			}
		}


		foreach ($searchPaths as $directory) {

			foreach ($this->l10n->languagePath as $lang) {
				$file = $directory . $lang . DS . $this->category . DS . $domain;
				$localeDef = $directory . $lang . DS . $this->category;

				if ($core) {
					$app = $directory . $lang . DS . $this->category . DS . 'core';

					if (file_exists($fn = "$app.mo")) {
						$this->_loadMo($fn, $domain);
						$this->_noLocale = false;
						$merge[$domain][$this->_lang][$this->category] = $this->_domains[$domain][$this->_lang][$this->category];
						$core = null;
					} elseif (file_exists($fn = "$app.po") && ($f = fopen($fn, "r"))) {
						$this->_loadPo($f, $domain);
						$this->_noLocale = false;
						$merge[$domain][$this->_lang][$this->category] = $this->_domains[$domain][$this->_lang][$this->category];
						$core = null;
					}
				}

				if (file_exists($fn = "$file.mo")) {
					$this->_loadMo($fn, $domain);
					$this->_noLocale = false;
					break 2;
				} elseif (file_exists($fn = "$file.po") && ($f = fopen($fn, "r"))) {
					$this->_loadPo($f, $domain);
					$this->_noLocale = false;
					break 2;
				} elseif (is_file($localeDef) && ($f = fopen($localeDef, "r"))) {
					$this->_loadLocaleDefinition($f, $domain);
					$this->_noLocale = false;
					return $domain;
				}
			}
		}

		if (empty($this->_domains[$domain][$this->_lang][$this->category])) {
			$this->_domains[$domain][$this->_lang][$this->category] = array();
			return $domain;
		}

		if (isset($this->_domains[$domain][$this->_lang][$this->category][""])) {
			$head = $this->_domains[$domain][$this->_lang][$this->category][""];

			foreach (explode("\n", $head) as $line) {
				$header = strtok($line,":");
				$line = trim(strtok("\n"));
				$this->_domains[$domain][$this->_lang][$this->category]["%po-header"][strtolower($header)] = $line;
			}

			if (isset($this->_domains[$domain][$this->_lang][$this->category]["%po-header"]["plural-forms"])) {
				$switch = preg_replace("/(?:[() {}\\[\\]^\\s*\\]]+)/", "", $this->_domains[$domain][$this->_lang][$this->category]["%po-header"]["plural-forms"]);
				$this->_domains[$domain][$this->_lang][$this->category]["%plural-c"] = $switch;
				unset($this->_domains[$domain][$this->_lang][$this->category]["%po-header"]);
			}
			$this->_domains = Set::pushDiff($this->_domains, $merge);

			if (isset($this->_domains[$domain][$this->_lang][$this->category][null])) {
				unset($this->_domains[$domain][$this->_lang][$this->category][null]);
			}
		}
		return $domain;
	}

/**
 * Loads the binary .mo file for translation and sets the values for this translation in the var I18n::_domains
 *
 * @param string $file Binary .mo file to load
 * @param string $domain Domain where to load file in
 * @return void
 */
	protected function _loadMo($file, $domain) {
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
					$this->_domains[$domain][$this->_lang][$this->category][$msgid] = $msgstr;

					if (isset($msgid_plural)) {
						$this->_domains[$domain][$this->_lang][$this->category][$msgid_plural] =& $this->_domains[$domain][$this->_lang][$this->category][$msgid];
					}
				}
			}
		}
	}

/**
 * Loads the text .po file for translation and sets the values for this translation in the var I18n::_domains
 *
 * @param resource $file Text .po file to load
 * @param string $domain Domain to load file in
 * @return array Binded domain elements
 */
	protected function _loadPo($file, $domain) {
		$type = 0;
		$translations = array();
		$translationKey = "";
		$plural = 0;
		$header = "";

		do {
			$line = trim(fgets($file));
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
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 6 && $translationKey) {
				$type = 6;
			} elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"(.+)\"$/i", $line, $regs) && ($type == 6 || $type == 7) && $translationKey) {
				$plural = $regs[1];
				$translations[$translationKey][$plural] = stripcslashes($regs[2]);
				$type = 7;
			} elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"\"$/i", $line, $regs) && ($type == 6 || $type == 7) && $translationKey) {
				$plural = $regs[1];
				$translations[$translationKey][$plural] = "";
				$type = 7;
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 7 && $translationKey) {
				$translations[$translationKey][$plural] .= stripcslashes($regs[1]);
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
		return $this->_domains[$domain][$this->_lang][$this->category] = array_merge($merge ,$translations);
	}

/**
 * Parses a locale definition file following the POSIX standard
 *
 * @param resource $file file handler
 * @param string $domain Domain where locale definitions will be stored
 * @return void
 */
	protected function _loadLocaleDefinition($file, $domain = null) {
		$comment = '#';
		$escape = '\\';
		$currentToken = false;
		$value = '';
		while ($line = fgets($file)) {
			$line = trim($line);
			if (empty($line) || $line[0] === $comment) {
				continue;
			}
			$parts = preg_split("/[[:space:]]+/",$line);
			if ($parts[0] === 'comment_char') {
				$comment = $parts[1];
				continue;
			}
			if ($parts[0] === 'escape_char') {
				$escape = $parts[1];
				continue;
			}
			$count = count($parts);
			if ($count == 2) {
				$currentToken = $parts[0];
				$value = $parts[1];
			} elseif ($count == 1) {
				$value .= $parts[0];
			} else {
				continue;
			}

			$len = strlen($value) - 1;
			if ($value[$len] === $escape) {
				$value = substr($value, 0, $len);
				continue;
			}

			$mustEscape = array($escape . ',' , $escape . ';', $escape . '<', $escape . '>', $escape . $escape);
			$replacements = array_map('crc32', $mustEscape);
			$value = str_replace($mustEscape, $replacements, $value);
			$value = explode(';', $value);
			$this->__escape = $escape;
			foreach ($value as $i => $val) {
				$val = trim($val, '"');
				$val = preg_replace_callback('/(?:<)?(.[^>]*)(?:>)?/', array(&$this, '_parseLiteralValue'), $val);
				$val = str_replace($replacements, $mustEscape, $val);
				$value[$i] = $val;
			}
			if (count($value) == 1) {
				$this->_domains[$domain][$this->_lang][$this->category][$currentToken] = array_pop($value);
			} else {
				$this->_domains[$domain][$this->_lang][$this->category][$currentToken] = $value;
			}
		}
	}

/**
 * Auxiliary function to parse a symbol from a locale definition file
 *
 * @param string $string Symbol to be parsed
 * @return string parsed symbol
 */
	protected function _parseLiteralValue($string) {
		$string = $string[1];
		if (substr($string, 0, 2) === $this->__escape . 'x') {
			$delimiter = $this->__escape . 'x';
			return join('', array_map('chr', array_map('hexdec',array_filter(explode($delimiter, $string)))));
		}
		if (substr($string, 0, 2) === $this->__escape . 'd') {
			$delimiter = $this->__escape . 'd';
			return join('', array_map('chr', array_filter(explode($delimiter, $string))));
		}
		if ($string[0] === $this->__escape && isset($string[1]) && is_numeric($string[1])) {
			$delimiter = $this->__escape;
			return join('', array_map('chr', array_filter(explode($delimiter, $string))));
		}
		if (substr($string, 0, 3) === 'U00') {
			$delimiter = 'U00';
			return join('', array_map('chr', array_map('hexdec', array_filter(explode($delimiter, $string)))));
		}
		if (preg_match('/U([0-9a-fA-F]{4})/', $string, $match)) {
			return Multibyte::ascii(array(hexdec($match[1])));
		}
		return $string;
	}

/**
 * Returns a Time format definition from corresponding domain
 *
 * @param string $format Format to be translated
 * @param string $domain Domain where format is stored
 * @return mixed translated format string if only value or array of translated strings for corresponding format.
 */
	protected function _translateTime($format, $domain) {
		if (!empty($this->_domains[$domain][$this->_lang]['LC_TIME'][$format])) {
			if (($trans = $this->_domains[$domain][$this->_lang][$this->category][$format])) {
				return $trans;
			}
		}
		return $format;
	}
}
