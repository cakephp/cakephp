<?php
/**
 * Pluralize and singularize English words.
 *
 * Used by Cake's naming conventions throughout the framework.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Pluralize and singularize English words.
 *
 * Inflector pluralizes and singularizes English nouns.
 * Used by Cake's naming conventions throughout the framework.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 * @link          http://book.cakephp.org/view/1478/Inflector
 */
class Inflector {

/**
 * Plural inflector rules
 *
 * @var array
 * @access protected
 */
	var $_plural = array(
		'rules' => array(
			'/(s)tatus$/i' => '\1\2tatuses',
			'/(quiz)$/i' => '\1zes',
			'/^(ox)$/i' => '\1\2en',
			'/([m|l])ouse$/i' => '\1ice',
			'/(matr|vert|ind)(ix|ex)$/i'  => '\1ices',
			'/(x|ch|ss|sh)$/i' => '\1es',
			'/([^aeiouy]|qu)y$/i' => '\1ies',
			'/(hive)$/i' => '\1s',
			'/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
			'/sis$/i' => 'ses',
			'/([ti])um$/i' => '\1a',
			'/(p)erson$/i' => '\1eople',
			'/(m)an$/i' => '\1en',
			'/(c)hild$/i' => '\1hildren',
			'/(buffal|tomat)o$/i' => '\1\2oes',
			'/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|vir)us$/i' => '\1i',
			'/us$/i' => 'uses',
			'/(alias)$/i' => '\1es',
			'/(ax|cris|test)is$/i' => '\1es',
			'/s$/' => 's',
			'/^$/' => '',
			'/$/' => 's',
		),
		'uninflected' => array(
			'.*[nrlm]ese', '.*deer', '.*fish', '.*measles', '.*ois', '.*pox', '.*sheep', 'people'
		),
		'irregular' => array(
			'atlas' => 'atlases',
			'beef' => 'beefs',
			'brother' => 'brothers',
			'cafe' => 'cafes',
			'child' => 'children',
			'corpus' => 'corpuses',
			'cow' => 'cows',
			'ganglion' => 'ganglions',
			'genie' => 'genies',
			'genus' => 'genera',
			'graffito' => 'graffiti',
			'hoof' => 'hoofs',
			'loaf' => 'loaves',
			'man' => 'men',
			'money' => 'monies',
			'mongoose' => 'mongooses',
			'move' => 'moves',
			'mythos' => 'mythoi',
			'niche' => 'niches',
			'numen' => 'numina',
			'occiput' => 'occiputs',
			'octopus' => 'octopuses',
			'opus' => 'opuses',
			'ox' => 'oxen',
			'penis' => 'penises',
			'person' => 'people',
			'sex' => 'sexes',
			'soliloquy' => 'soliloquies',
			'testis' => 'testes',
			'trilby' => 'trilbys',
			'turf' => 'turfs'
		)
	);

/**
 * Singular inflector rules
 *
 * @var array
 * @access protected
 */
	var $_singular = array(
		'rules' => array(
			'/(s)tatuses$/i' => '\1\2tatus',
			'/^(.*)(menu)s$/i' => '\1\2',
			'/(quiz)zes$/i' => '\\1',
			'/(matr)ices$/i' => '\1ix',
			'/(vert|ind)ices$/i' => '\1ex',
			'/^(ox)en/i' => '\1',
			'/(alias)(es)*$/i' => '\1',
			'/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|viri?)i$/i' => '\1us',
			'/([ftw]ax)es/i' => '\1',
			'/(cris|ax|test)es$/i' => '\1is',
			'/(shoe|slave)s$/i' => '\1',
			'/(o)es$/i' => '\1',
			'/ouses$/' => 'ouse',
			'/([^a])uses$/' => '\1us',
			'/([m|l])ice$/i' => '\1ouse',
			'/(x|ch|ss|sh)es$/i' => '\1',
			'/(m)ovies$/i' => '\1\2ovie',
			'/(s)eries$/i' => '\1\2eries',
			'/([^aeiouy]|qu)ies$/i' => '\1y',
			'/([lr])ves$/i' => '\1f',
			'/(tive)s$/i' => '\1',
			'/(hive)s$/i' => '\1',
			'/(drive)s$/i' => '\1',
			'/([^fo])ves$/i' => '\1fe',
			'/(^analy)ses$/i' => '\1sis',
			'/(analy|ba|diagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
			'/([ti])a$/i' => '\1um',
			'/(p)eople$/i' => '\1\2erson',
			'/(m)en$/i' => '\1an',
			'/(c)hildren$/i' => '\1\2hild',
			'/(n)ews$/i' => '\1\2ews',
			'/eaus$/' => 'eau',
			'/^(.*us)$/' => '\\1',
			'/s$/i' => ''
		),
		'uninflected' => array(
			'.*[nrlm]ese', '.*deer', '.*fish', '.*measles', '.*ois', '.*pox', '.*sheep', '.*ss'
		),
		'irregular' => array(
			'foes' => 'foe',
			'waves' => 'wave',
			'curves' => 'curve'
		)
	);

/**
 * Words that should not be inflected
 *
 * @var array
 * @access protected
 */
	var $_uninflected = array(
		'Amoyese', 'bison', 'Borghese', 'bream', 'breeches', 'britches', 'buffalo', 'cantus',
		'carp', 'chassis', 'clippers', 'cod', 'coitus', 'Congoese', 'contretemps', 'corps',
		'debris', 'diabetes', 'djinn', 'eland', 'elk', 'equipment', 'Faroese', 'flounder',
		'Foochowese', 'gallows', 'Genevese', 'Genoese', 'Gilbertese', 'graffiti',
		'headquarters', 'herpes', 'hijinks', 'Hottentotese', 'information', 'innings',
		'jackanapes', 'Kiplingese', 'Kongoese', 'Lucchese', 'mackerel', 'Maltese', '.*?media',
		'mews', 'moose', 'mumps', 'Nankingese', 'news', 'nexus', 'Niasese',
		'Pekingese', 'Piedmontese', 'pincers', 'Pistoiese', 'pliers', 'Portuguese',
		'proceedings', 'rabies', 'rice', 'rhinoceros', 'salmon', 'Sarawakese', 'scissors',
		'sea[- ]bass', 'series', 'Shavese', 'shears', 'siemens', 'species', 'swine', 'testes',
		'trousers', 'trout', 'tuna', 'Vermontese', 'Wenchowese', 'whiting', 'wildebeest',
		'Yengeese'
	);

/**
 * Default map of accented and special characters to ASCII characters
 *
 * @var array
 * @access protected
 */
	var $_transliteration = array(
		'/ä|æ|ǽ/' => 'ae',
		'/ö|œ/' => 'oe',
		'/ü/' => 'ue',
		'/Ä/' => 'Ae',
		'/Ü/' => 'Ue',
		'/Ö/' => 'Oe',
		'/À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ/' => 'A',
		'/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª/' => 'a',
		'/Ç|Ć|Ĉ|Ċ|Č/' => 'C',
		'/ç|ć|ĉ|ċ|č/' => 'c',
		'/Ð|Ď|Đ/' => 'D',
		'/ð|ď|đ/' => 'd',
		'/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě/' => 'E',
		'/è|é|ê|ë|ē|ĕ|ė|ę|ě/' => 'e',
		'/Ĝ|Ğ|Ġ|Ģ/' => 'G',
		'/ĝ|ğ|ġ|ģ/' => 'g',
		'/Ĥ|Ħ/' => 'H',
		'/ĥ|ħ/' => 'h',
		'/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ/' => 'I',
		'/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı/' => 'i',
		'/Ĵ/' => 'J',
		'/ĵ/' => 'j',
		'/Ķ/' => 'K',
		'/ķ/' => 'k',
		'/Ĺ|Ļ|Ľ|Ŀ|Ł/' => 'L',
		'/ĺ|ļ|ľ|ŀ|ł/' => 'l',
		'/Ñ|Ń|Ņ|Ň/' => 'N',
		'/ñ|ń|ņ|ň|ŉ/' => 'n',
		'/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ/' => 'O',
		'/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º/' => 'o',
		'/Ŕ|Ŗ|Ř/' => 'R',
		'/ŕ|ŗ|ř/' => 'r',
		'/Ś|Ŝ|Ş|Š/' => 'S',
		'/ś|ŝ|ş|š|ſ/' => 's',
		'/Ţ|Ť|Ŧ/' => 'T',
		'/ţ|ť|ŧ/' => 't',
		'/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ/' => 'U',
		'/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ/' => 'u',
		'/Ý|Ÿ|Ŷ/' => 'Y',
		'/ý|ÿ|ŷ/' => 'y',
		'/Ŵ/' => 'W',
		'/ŵ/' => 'w',
		'/Ź|Ż|Ž/' => 'Z',
		'/ź|ż|ž/' => 'z',
		'/Æ|Ǽ/' => 'AE',
		'/ß/'=> 'ss',
		'/Ĳ/' => 'IJ',
		'/ĳ/' => 'ij',
		'/Œ/' => 'OE',
		'/ƒ/' => 'f'
	);

/**
 * Cached array identity map of pluralized words.
 *
 * @var array
 * @access protected
 */
	var $_pluralized = array();

/**
 * Cached array identity map of singularized words.
 *
 * @var array
 * @access protected
 */
	var $_singularized = array();

/**
 * Cached Underscore Inflections
 *
 * @var array
 * @access protected
 */
	var $_underscore = array();

/**
 * Cached Camelize Inflections
 *
 * @var array
 * @access protected
 */
	var $_camelize = array();

/**
 * Classify cached inflecctions
 *
 * @var array
 * @access protected
 */
	var $_classify = array();

/**
 * Tablize cached inflections
 *
 * @var array
 * @access protected
 */
	var $_tableize = array();

/**
 * Humanize cached inflections
 *
 * @var array
 * @access protected
 */
	var $_humanize = array();

/**
 * Gets a reference to the Inflector object instance
 *
 * @return object
 * @access public
 */
	function &getInstance() {
		static $instance = array();

		if (!$instance) {
			$instance[0] =& new Inflector();
		}
		return $instance[0];
	}

/**
 * Cache inflected values, and return if already available
 *
 * @param string $type Inflection type
 * @param string $key Original value
 * @param string $value Inflected value
 * @return string Inflected value, from cache
 * @access protected
 */
	function _cache($type, $key, $value = false) {
		$key = '_' . $key;
		$type = '_' . $type;
		if ($value !== false) {
			$this->{$type}[$key] = $value;
			return $value;
		}

		if (!isset($this->{$type}[$key])) {
			return false;
		}
		return $this->{$type}[$key];
	}

/**
 * Adds custom inflection $rules, of either 'plural', 'singular' or 'transliteration' $type.
 *
 * ### Usage:
 *
 * {{{
 * Inflector::rules('plural', array('/^(inflect)or$/i' => '\1ables'));
 * Inflector::rules('plural', array(
 *     'rules' => array('/^(inflect)ors$/i' => '\1ables'),
 *     'uninflected' => array('dontinflectme'),
 *     'irregular' => array('red' => 'redlings')
 * ));
 * Inflector::rules('transliteration', array('/å/' => 'aa'));
 * }}}
 *
 * @param string $type The type of inflection, either 'plural', 'singular' or 'transliteration'
 * @param array $rules Array of rules to be added.
 * @param boolean $reset If true, will unset default inflections for all
 *        new rules that are being defined in $rules.
 * @access public
 * @return void
 * @static
 */
	function rules($type, $rules, $reset = false) {
		$_this =& Inflector::getInstance();
		$var = '_'.$type;

		switch ($type) {
			case 'transliteration':
				if ($reset) {
					$_this->_transliteration = $rules;
				} else {
					$_this->_transliteration = $rules + $_this->_transliteration;
				}
			break;

			default:
				foreach ($rules as $rule => $pattern) {
					if (is_array($pattern)) {
						if ($reset) {
							$_this->{$var}[$rule] = $pattern;
						} else {
							if ($rule === 'uninflected') {
								$_this->{$var}[$rule] = array_merge($pattern, $_this->{$var}[$rule]);
							} else {
								$_this->{$var}[$rule] = $pattern + $_this->{$var}[$rule];
							}
						}
						unset($rules[$rule], $_this->{$var}['cache' . ucfirst($rule)]);
						if (isset($_this->{$var}['merged'][$rule])) {
							unset($_this->{$var}['merged'][$rule]);
						}
						if ($type === 'plural') {
							$_this->_pluralized = $_this->_tableize = array();
						} elseif ($type === 'singular') {
							$_this->_singularized = array();
						}
					}
				}
				$_this->{$var}['rules'] = $rules + $_this->{$var}['rules'];
			break;
		}
	}

/**
 * Return $word in plural form.
 *
 * @param string $word Word in singular
 * @return string Word in plural
 * @access public
 * @static
 * @link http://book.cakephp.org/view/1479/Class-methods
 */
	function pluralize($word) {
		$_this =& Inflector::getInstance();

		if (isset($_this->_pluralized[$word])) {
			return $_this->_pluralized[$word];
		}

		if (!isset($_this->_plural['merged']['irregular'])) {
			$_this->_plural['merged']['irregular'] = $_this->_plural['irregular'];
		}

		if (!isset($_this->plural['merged']['uninflected'])) {
			$_this->_plural['merged']['uninflected'] = array_merge($_this->_plural['uninflected'], $_this->_uninflected);
		}

		if (!isset($_this->_plural['cacheUninflected']) || !isset($_this->_plural['cacheIrregular'])) {
			$_this->_plural['cacheUninflected'] = '(?:' . implode('|', $_this->_plural['merged']['uninflected']) . ')';
			$_this->_plural['cacheIrregular'] = '(?:' . implode('|', array_keys($_this->_plural['merged']['irregular'])) . ')';
		}

		if (preg_match('/(.*)\\b(' . $_this->_plural['cacheIrregular'] . ')$/i', $word, $regs)) {
			$_this->_pluralized[$word] = $regs[1] . substr($word, 0, 1) . substr($_this->_plural['merged']['irregular'][strtolower($regs[2])], 1);
			return $_this->_pluralized[$word];
		}

		if (preg_match('/^(' . $_this->_plural['cacheUninflected'] . ')$/i', $word, $regs)) {
			$_this->_pluralized[$word] = $word;
			return $word;
		}

		foreach ($_this->_plural['rules'] as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				$_this->_pluralized[$word] = preg_replace($rule, $replacement, $word);
				return $_this->_pluralized[$word];
			}
		}
	}

/**
 * Return $word in singular form.
 *
 * @param string $word Word in plural
 * @return string Word in singular
 * @access public
 * @static
 * @link http://book.cakephp.org/view/1479/Class-methods
 */
	function singularize($word) {
		$_this =& Inflector::getInstance();

		if (isset($_this->_singularized[$word])) {
			return $_this->_singularized[$word];
		}

		if (!isset($_this->_singular['merged']['uninflected'])) {
			$_this->_singular['merged']['uninflected'] = array_merge($_this->_singular['uninflected'], $_this->_uninflected);
		}

		if (!isset($_this->_singular['merged']['irregular'])) {
			$_this->_singular['merged']['irregular'] = array_merge($_this->_singular['irregular'], array_flip($_this->_plural['irregular']));
		}

		if (!isset($_this->_singular['cacheUninflected']) || !isset($_this->_singular['cacheIrregular'])) {
			$_this->_singular['cacheUninflected'] = '(?:' . join( '|', $_this->_singular['merged']['uninflected']) . ')';
			$_this->_singular['cacheIrregular'] = '(?:' . join( '|', array_keys($_this->_singular['merged']['irregular'])) . ')';
		}

		if (preg_match('/(.*)\\b(' . $_this->_singular['cacheIrregular'] . ')$/i', $word, $regs)) {
			$_this->_singularized[$word] = $regs[1] . substr($word, 0, 1) . substr($_this->_singular['merged']['irregular'][strtolower($regs[2])], 1);
			return $_this->_singularized[$word];
		}

		if (preg_match('/^(' . $_this->_singular['cacheUninflected'] . ')$/i', $word, $regs)) {
			$_this->_singularized[$word] = $word;
			return $word;
		}

		foreach ($_this->_singular['rules'] as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				$_this->_singularized[$word] = preg_replace($rule, $replacement, $word);
				return $_this->_singularized[$word];
			}
		}
		$_this->_singularized[$word] = $word;
		return $word;
	}

/**
 * Returns the given lower_case_and_underscored_word as a CamelCased word.
 *
 * @param string $lower_case_and_underscored_word Word to camelize
 * @return string Camelized word. LikeThis.
 * @access public
 * @static
 * @link http://book.cakephp.org/view/1479/Class-methods
 */
	function camelize($lowerCaseAndUnderscoredWord) {
		$_this =& Inflector::getInstance();
		if (!($result = $_this->_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord))) {
			$result = str_replace(' ', '', Inflector::humanize($lowerCaseAndUnderscoredWord));
			$_this->_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord, $result);
		}
		return $result;
	}

/**
 * Returns the given camelCasedWord as an underscored_word.
 *
 * @param string $camelCasedWord Camel-cased word to be "underscorized"
 * @return string Underscore-syntaxed version of the $camelCasedWord
 * @access public
 * @static
 * @link http://book.cakephp.org/view/1479/Class-methods
 */
	function underscore($camelCasedWord) {
		$_this =& Inflector::getInstance();
		if (!($result = $_this->_cache(__FUNCTION__, $camelCasedWord))) {
			$result = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
			$_this->_cache(__FUNCTION__, $camelCasedWord, $result);
		}
		return $result;
	}

/**
 * Returns the given underscored_word_group as a Human Readable Word Group.
 * (Underscores are replaced by spaces and capitalized following words.)
 *
 * @param string $lower_case_and_underscored_word String to be made more readable
 * @return string Human-readable string
 * @access public
 * @static
 * @link http://book.cakephp.org/view/1479/Class-methods
 */
	function humanize($lowerCaseAndUnderscoredWord) {
		$_this =& Inflector::getInstance();
		if (!($result = $_this->_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord))) {
			$result = ucwords(str_replace('_', ' ', $lowerCaseAndUnderscoredWord));
			$_this->_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord, $result);
		}
		return $result;
	}

/**
 * Returns corresponding table name for given model $className. ("people" for the model class "Person").
 *
 * @param string $className Name of class to get database table name for
 * @return string Name of the database table for given class
 * @access public
 * @static
 * @link http://book.cakephp.org/view/1479/Class-methods
 */
	function tableize($className) {
		$_this =& Inflector::getInstance();
		if (!($result = $_this->_cache(__FUNCTION__, $className))) {
			$result = Inflector::pluralize(Inflector::underscore($className));
			$_this->_cache(__FUNCTION__, $className, $result);
		}
		return $result;
	}

/**
 * Returns Cake model class name ("Person" for the database table "people".) for given database table.
 *
 * @param string $tableName Name of database table to get class name for
 * @return string Class name
 * @access public
 * @static
 * @link http://book.cakephp.org/view/1479/Class-methods
 */
	function classify($tableName) {
		$_this =& Inflector::getInstance();
		if (!($result = $_this->_cache(__FUNCTION__, $tableName))) {
			$result = Inflector::camelize(Inflector::singularize($tableName));
			$_this->_cache(__FUNCTION__, $tableName, $result);
		}
		return $result;
	}

/**
 * Returns camelBacked version of an underscored string.
 *
 * @param string $string
 * @return string in variable form
 * @access public
 * @static
 * @link http://book.cakephp.org/view/1479/Class-methods
 */
	function variable($string) {
		$_this =& Inflector::getInstance();
		if (!($result = $_this->_cache(__FUNCTION__, $string))) {
			$string2 = Inflector::camelize(Inflector::underscore($string));
			$replace = strtolower(substr($string2, 0, 1));
			$result = preg_replace('/\\w/', $replace, $string2, 1);
			$_this->_cache(__FUNCTION__, $string, $result);
		}
		return $result;
	}

/**
 * Returns a string with all spaces converted to underscores (by default), accented
 * characters converted to non-accented characters, and non word characters removed.
 *
 * @param string $string the string you want to slug
 * @param string $replacement will replace keys in map
 * @param array $map extra elements to map to the replacement
 * @deprecated $map param will be removed in future versions. Use Inflector::rules() instead
 * @return string
 * @access public
 * @static
 * @link http://book.cakephp.org/view/1479/Class-methods
 */
	function slug($string, $replacement = '_', $map = array()) {
		$_this =& Inflector::getInstance();

		if (is_array($replacement)) {
			$map = $replacement;
			$replacement = '_';
		}
		$quotedReplacement = preg_quote($replacement, '/');

		$merge = array(
			'/[^\s\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]/mu' => ' ',
			'/\\s+/' => $replacement,
			sprintf('/^[%s]+|[%s]+$/', $quotedReplacement, $quotedReplacement) => '',
		);

		$map = $map + $_this->_transliteration + $merge;
		return preg_replace(array_keys($map), array_values($map), $string);
	}
}
