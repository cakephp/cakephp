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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Core\StaticConfigTrait;
use Cake\Error\Exception;
use Cake\I18n\CatalogEngineRegistry;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Utility\String;

/**
 * I18n handles translation of Text and time format strings.
 *
 */
class I18n {

	use StaticConfigTrait;

/**
 * Instance of the L10n class for localization
 *
 * @var L10n
 */
	public $l10n = null;

/**
 * Default domain of translation
 *
 * @var string
 */
	public static $defaultDomain = 'default';

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
 * @var bool
 */
	protected $_noLocale = false;

/**
 * Translation categories
 *
 * @var array
 */
	protected $_categories = array(
		'LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME', 'LC_MESSAGES'
	);

/**
 * Constants for the translation categories.
 *
 * The constants may be used in translation fetching
 * instead of hardcoded integers.
 * Example:
 * {{{
 *	I18n::translate('CakePHP is awesome.', null, null, I18n::LC_MESSAGES)
 * }}}
 *
 * To keep the code more readable, I18n constants are preferred over
 * hardcoded integers.
 */
/**
 * Constant for LC_ALL.
 *
 * @var int
 */
	const LC_ALL = 0;

/**
 * Constant for LC_COLLATE.
 *
 * @var int
 */
	const LC_COLLATE = 1;

/**
 * Constant for LC_CTYPE.
 *
 * @var int
 */
	const LC_CTYPE = 2;

/**
 * Constant for LC_MONETARY.
 *
 * @var int
 */
	const LC_MONETARY = 3;

/**
 * Constant for LC_NUMERIC.
 *
 * @var int
 */
	const LC_NUMERIC = 4;

/**
 * Constant for LC_TIME.
 *
 * @var int
 */
	const LC_TIME = 5;

/**
 * Constant for LC_MESSAGES.
 *
 * @var int
 */
	const LC_MESSAGES = 6;

/**
 * Request object instance
 *
 * @var \Cake\Network\Request
 */
	protected $_request = null;

/**
 * Catalog registry used for creating and using catalog adapters.
 *
 * @var \Cake\I18n\CatalogRegistry
 */
	protected static $_registry;

/**
 * Constructor, use I18n::getInstance() to get the i18n translation object.
 *
 * @param \Cake\Network\Request $request Request object
 */
	public function __construct(Request $request) {
		$this->_request = $request;

		$this->l10n = new L10n($this->_request);
	}

/**
 * Return a static instance of the I18n class
 *
 * @return I18n
 */
	public static function getInstance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] = new I18n(Request::createFromGlobals());
		}
		return $instance[0];
	}

/**
 * Finds and builds the instance of the required engine class.
 *
 * @param string $name Name of the config array that needs an engine instance built
 * @return void
 * @throws \Cake\Error\Exception When a catalog engine cannot be created.
 */
	protected static function _buildEngine($name) {
		if (empty(static::$_registry)) {
			static::$_registry = new CatalogEngineRegistry();
		}
		if (empty(static::$_config[$name]['className'])) {
			throw new Exception(sprintf('The "%s" catalog configuration does not exist.', $name));
		}

		$config = static::$_config[$name];
		static::$_registry->load($name, $config);
	}

/**
 * Fetch the engine attached to a specific configuration name.
 *
 * If the catalog engine & configuration are missing an error will be
 * triggered.
 *
 * @param string $config The configuration name you want an engine for.
 * @return \Cake\I18n\CatalogEngine
 */
	public static function engine($config) {
		if (isset(static::$_registry->{$config})) {
			return static::$_registry->{$config};
		}

		static::_buildEngine($config);
		return static::$_registry->{$config};
	}

/**
 * Used by the translation functions in basics.php
 * Returns a translated string based on current language and translation files stored in locale folder
 *
 * @param string $singular String to translate
 * @param string $plural Plural string (if any)
 * @param string $domain Domain The domain of the translation. Domains are often used by plugin translations.
 *    If null, the default domain will be used.
 * @param int $category Category The integer value of the category to use.
 * @param int $count Count is used with $plural to choose the correct plural form.
 * @param string $language Language to translate string to.
 *    If null it checks for language in session followed by Config.language configuration variable.
 * @return string translated string.
 * @throws \Cake\Error\Exception When '' is provided as a domain.
 */
	public static function translate($singular, $plural = null, $domain = null,
		$category = self::LC_MESSAGES, $count = null, $language = null
	) {
		$_this = I18n::getInstance();

		if (strpos($singular, "\r\n") !== false) {
			$singular = str_replace("\r\n", "\n", $singular);
		}
		if ($plural !== null && strpos($plural, "\r\n") !== false) {
			$plural = str_replace("\r\n", "\n", $plural);
		}

		$_this->category = $_this->_categories[$category];

		if (empty($language)) {
			if (empty($language)) {
				$language = Configure::read('Config.language');
			}
		}

		if (($_this->_lang && $_this->_lang !== $language) || !$_this->_lang) {
			$lang = $_this->l10n->get($language);
			$_this->_lang = $lang;
		}

		if ($domain === null) {
			$domain = static::$defaultDomain;
		}
		if ($domain === '') {
			throw new Exception('You cannot use "" as a domain.');
		}

		$_this->domain = $domain . '_' . $_this->l10n->lang;

		if (!isset($_this->_domains[$domain][$_this->_lang])) {
			$_this->_domains[$domain][$_this->_lang] = (array)Cache::read($_this->domain, '_cake_core_');
		}

		if (!isset($_this->_domains[$domain][$_this->_lang][$_this->category])) {
			$merge[$domain][$_this->_lang][$_this->category] = $_this->_translations(
				$domain,
				$_this->l10n->languagePath,
				$_this->category
			);
			$_this->_domains = Hash::mergeDiff($_this->_domains, $merge);

			Cache::write($_this->domain, $_this->_domains[$domain][$_this->_lang], '_cake_core_');
		}

		if ($_this->category === 'LC_TIME') {
			return $_this->_translateTime($singular, $domain);
		}

		return $_this->_translateString($singular, $plural, $domain, $_this->category, $count, $_this->_lang);
	}

/**
 * Translate a string
 *
 * @param string $singular String to translate
 * @param string $plural Plural string
 * @param string $domain Domain name
 * @param string $category Category name LC_MESSAGES, LC_ALL etc
 * @param int $count Count is used with $plural to choose the correct plural form
 * @param string $locale Locale to translate string to
 * @return string Translated string
 */
	protected function _translateString($singular, $plural, $domain, $category, $count, $locale) {
		$_this = I18n::getInstance();

		if ($count === null) {
			$plurals = 0;
		} elseif (!empty($_this->_domains[$domain][$locale][$category]['%plural-c']) &&
			$_this->_noLocale === false
		) {
			$header = $_this->_domains[$domain][$locale][$category]['%plural-c'];
			$plurals = $_this->_pluralGuess($header, $count);
		} else {
			if ($count != 1) {
				$plurals = 1;
			} else {
				$plurals = 0;
			}
		}

		if (!empty($_this->_domains[$domain][$locale][$category][$singular])) {
			if (($trans = $_this->_domains[$domain][$locale][$category][$singular]) || ($plurals) && ($trans = $_this->_domains[$domain][$locale][$category][$plural])) {
				if (is_array($trans)) {
					if (isset($trans[$plurals])) {
						$trans = $trans[$plurals];
					} else {
						trigger_error(
							sprintf(
								'Missing plural form translation for "%s" in "%s" domain, "%s" locale. ' .
								' Check your po file for correct plurals and valid Plural-Forms header.',
								$singular,
								$domain,
								$locale
							),
							E_USER_WARNING
						);
						$trans = $trans[0];
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
 * Get translations using one of the configured catalog engines.
 *
 * @param string $domain Domain name
 * @param array $locales Locales
 * @param string $category Category name
 * @return array Translations
 */
	protected function _translations($domain, array $locales, $category) {
		$this->_noLocale = true;

		$config = 'default';
		if (isset(static::$_config[$domain])) {
			$config = $domain;
		}

		$translations = static::engine($config)->read($domain, $locales, $category);

		if ($translations !== false) {
			$this->_noLocale = false;

			if (isset($translations['%po-header']['plural-forms'])) {
				$switch = preg_replace(
					'/(?:[() {}\\[\\]^\\s*\\]]+)/',
					'',
					$translations['%po-header']['plural-forms']
				);
				$translations['%plural-c'] = $switch;
			}
			unset($translations['%po-header']);
		} else {
			$translations = [];
		}

		return $translations;
	}

/**
 * Clears the domains internal data array. Useful for testing i18n.
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
 * @param int $n Number
 * @return int plural match
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
