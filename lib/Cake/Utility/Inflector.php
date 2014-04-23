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
 * @package       Cake.Utility
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Pluralize and singularize English words.
 *
 * Inflector pluralizes and singularizes English nouns.
 * Used by CakePHP's naming conventions throughout the framework.
 *
 * @package       Cake.Utility
 * @link          http://book.cakephp.org/2.0/en/core-utility-libraries/inflector.html
 */
class Inflector {

/**
 * Plural inflector rules
 *
 * @var array
 */
	protected static $_plural = array(
		'rules' => array(
			'/(s)tatus$/i' => '\1\2tatuses',
			'/(quiz)$/i' => '\1zes',
			'/^(ox)$/i' => '\1\2en',
			'/([m|l])ouse$/i' => '\1ice',
			'/(matr|vert|ind)(ix|ex)$/i' => '\1ices',
			'/(x|ch|ss|sh)$/i' => '\1es',
			'/([^aeiouy]|qu)y$/i' => '\1ies',
			'/(hive)$/i' => '\1s',
			'/(?:([^f])fe|([lre])f)$/i' => '\1\2ves',
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
			'brief' => 'briefs',
			'brother' => 'brothers',
			'cafe' => 'cafes',
			'child' => 'children',
			'cookie' => 'cookies',
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
			'turf' => 'turfs',
			'potato' => 'potatoes',
			'hero' => 'heroes',
			'tooth' => 'teeth',
			'goose' => 'geese',
			'foot' => 'feet'
		)
	);

/**
 * Singular inflector rules
 *
 * @var array
 */
	protected static $_singular = array(
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
			'/(shoe)s$/i' => '\1',
			'/(o)es$/i' => '\1',
			'/ouses$/' => 'ouse',
			'/([^a])uses$/' => '\1us',
			'/([m|l])ice$/i' => '\1ouse',
			'/(x|ch|ss|sh)es$/i' => '\1',
			'/(m)ovies$/i' => '\1\2ovie',
			'/(s)eries$/i' => '\1\2eries',
			'/([^aeiouy]|qu)ies$/i' => '\1y',
			'/(tive)s$/i' => '\1',
			'/(hive)s$/i' => '\1',
			'/(drive)s$/i' => '\1',
			'/([le])ves$/i' => '\1f',
			'/([^rfoa])ves$/i' => '\1fe',
			'/(^analy)ses$/i' => '\1sis',
			'/(analy|diagno|^ba|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
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
		)
	);

/**
 * Words that should not be inflected
 *
 * @var array
 */
	protected static $_uninflected = array(
		'Amoyese', 'bison', 'Borghese', 'bream', 'breeches', 'britches', 'buffalo', 'cantus',
		'carp', 'chassis', 'clippers', 'cod', 'coitus', 'Congoese', 'contretemps', 'corps',
		'debris', 'diabetes', 'djinn', 'eland', 'elk', 'equipment', 'Faroese', 'flounder',
		'Foochowese', 'gallows', 'Genevese', 'Genoese', 'Gilbertese', 'graffiti',
		'headquarters', 'herpes', 'hijinks', 'Hottentotese', 'information', 'innings',
		'jackanapes', 'Kiplingese', 'Kongoese', 'Lucchese', 'mackerel', 'Maltese', '.*?media',
		'metadata', 'mews', 'moose', 'mumps', 'Nankingese', 'news', 'nexus', 'Niasese',
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
 */
	protected static $_transliteration = array(
		'/À|Á|Â|Ã|Å|Ǻ|Ā|Ă|Ą|Ǎ|Α|Ά|А|Ả|Ạ|Ắ|Ằ|Ẳ|Ẵ|Ặ|Ấ|Ầ|Ẩ|Ẫ|Ậ/' => 'A',
		'/Æ|Ǽ/' => 'AE',
		'/Ä/' => 'Ae',
		'/Β|Б/' => 'B',
		'/Ç|Ć|Ĉ|Ċ|Č|Ц|Ћ/' => 'C',
		'/Ч/' => 'Ch',
		'/Ð|Ď|Đ|Δ|Д/' => 'D',
		'/Ђ/' => 'Dj',
		'/Џ/' => 'Dz',
		'/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě|Ε|Έ|Е|Э|Ẻ|Ẽ|Ẹ|Ế|Ề|Ể|Ễ|Ệ|Ə/' => 'E',
		'/Φ|Ф/' => 'F',
		'/Ĝ|Ğ|Ġ|Ģ|Γ|Г|Ґ/' => 'G',
		'/Ĥ|Ħ|Η|Ή|Х/' => 'H',
		'/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ|Ι|Ί|Ϊ|И|І|Ỉ|Ị/' => 'I',
		'/Ĳ/' => 'IJ',
		'/Ĵ|Й/' => 'J',
		'/Ķ|Κ|К/' => 'K',
		'/Ĺ|Ļ|Ľ|Ŀ|Ł|Λ|Л/' => 'L',
		'/Љ/' => 'Lj',
		'/Μ|М/' => 'M',
		'/Ñ|Ń|Ņ|Ň|Ν|Н/' => 'N',
		'/Њ/' => 'Nj',
		'/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ|Ο|Ό|О|Ỏ|Ọ|Ố|Ồ|Ổ|Ỗ|Ộ|Ớ|Ờ|Ở|Ỡ|Ợ/' => 'O',
		'/Œ/' => 'OE',
		'/Ö/' => 'Oe',
		'/Π|П/' => 'P',
		'/Ψ/' => 'PS',
		'/Ŕ|Ŗ|Ř|Ρ|Р/' => 'R',
		'/Ś|Ŝ|Ş|Ș|Š|Σ|С/' => 'S',
		'/ẞ/' => 'SS',
		'/Ш|Щ/' => 'Sh',
		'/Ţ|Ț|Ť|Ŧ|Τ|Т/' => 'T',
		'/Þ|Θ/' => 'TH',
		'/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ|У|Ủ|Ụ|Ứ|Ừ|Ử|Ữ|Ự/' => 'U',
		'/Ü/' => 'Ue',
		'/В/' => 'V',
		'/Ŵ|Ω|Ώ/' => 'W',
		'/Ξ|Χ/' => 'X',
		'/Ý|Ÿ|Ŷ|Υ|Ύ|Ϋ|Ы|Ỳ|Ỷ|Ỹ|Ỵ/' => 'Y',
		'/Я/' => 'Ya',
		'/Є/' => 'Ye',
		'/Ї/' => 'Yi',
		'/Ё/' => 'Yo',
		'/Ю/' => 'Yu',
		'/Ź|Ż|Ž|Ζ|З/' => 'Z',
		'/Ж/' => 'Zh',
		'/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª|α|ά|а|ả|ạ|ắ|ằ|ẳ|ẵ|ặ|ấ|ầ|ẩ|ẫ|ậ|أ/' => 'a',
		'/ع/' => 'aa',
		'/ä|æ|ǽ/' => 'ae',
		'/β|б|ب/' => 'b',
		'/ç|ć|ĉ|ċ|č|ц/ћ' => 'c',
		'/ч/' => 'ch',
		'/ð|ď|đ|δ|д|د|ض/' => 'd',
		'/ђ/' => 'dj',
		'/џ/' => 'dz',
		'/è|é|ê|ë|ē|ĕ|ė|ę|ě|ε|έ|е|э|ẻ|ẽ|ẹ|ế|ề|ể|ễ|ệ|ə/' => 'e',
		'/ƒ|φ|ф|ف/' => 'f',
		'/ĝ|ğ|ġ|ģ|γ|г|ґ|ج/' => 'g',
		'/غ/' => 'gh',
		'/ĥ|ħ|η|ή|х|ح|ه/' => 'h',
		'/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı|ι|ί|ϊ|ΐ|и|і|ỉ|ị/' => 'i',
		'/ĳ/' => 'ij',
		'/ĵ|й|ј|Ј/' => 'j',
		'/ķ|κ|к|ق|ك/' => 'k',
		'/خ/' => 'kh',
		'/ĺ|ļ|ľ|ŀ|ł|λ|л|ل/' => 'l',
		'/љ/' => 'lj',
		'/μ|м|م/' => 'm',
		'/ñ|ń|ņ|ň|ŉ|ν|н|ن/' => 'n',
		'/њ/' => 'nj',
		'/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º|ο|ό|о|ỏ|ọ|ố|ồ|ổ|ỗ|ộ|ớ|ờ|ở|ỡ|ợ|و/' => 'o',
		'/ö|œ/' => 'oe',
		'/π|п/' => 'p',
		'/ψ/' => 'ps',
		'/ŕ|ŗ|ř|ρ|р|ر/' => 'r',
		'/ś|ŝ|ş|ș|š|ſ|σ|ς|с|س|ص/' => 's',
		'/ш|щ|ش/' => 'sh',
		'/ß/' => 'ss',
		'/ţ|ț|ť|ŧ|τ|т|ت|ط/' => 't',
		'/þ|θ|ث|ذ|ظ/' => 'th',
		'/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ|у|ủ|ụ|ứ|ừ|ử|ữ|ự/' => 'u',
		'/ü/' => 'ue',
		'/в/' => 'v',
		'/ŵ|ω|ώ/' => 'w',
		'/ξ|χ/' => 'x',
		'/ý|ÿ|ŷ|υ|ύ|ΰ|ϋ|ы|ỳ|ỷ|ỹ|ỵ|ي/' => 'y',
		'/я/' => 'ya',
		'/є/' => 'ye',
		'/ї/' => 'yi',
		'/ё/' => 'yo',
		'/ю/' => 'yu',
		'/ź|ż|ž|ζ|з|ز/' => 'z',
		'/ж/' => 'zh',
	);

/**
 * Method cache array.
 *
 * @var array
 */
	protected static $_cache = array();

/**
 * The initial state of Inflector so reset() works.
 *
 * @var array
 */
	protected static $_initialState = array();

/**
 * Cache inflected values, and return if already available
 *
 * @param string $type Inflection type
 * @param string $key Original value
 * @param string $value Inflected value
 * @return string Inflected value, from cache
 */
	protected static function _cache($type, $key, $value = false) {
		$key = '_' . $key;
		$type = '_' . $type;
		if ($value !== false) {
			self::$_cache[$type][$key] = $value;
			return $value;
		}
		if (!isset(self::$_cache[$type][$key])) {
			return false;
		}
		return self::$_cache[$type][$key];
	}

/**
 * Clears Inflectors inflected value caches. And resets the inflection
 * rules to the initial values.
 *
 * @return void
 */
	public static function reset() {
		if (empty(self::$_initialState)) {
			self::$_initialState = get_class_vars('Inflector');
			return;
		}
		foreach (self::$_initialState as $key => $val) {
			if ($key !== '_initialState') {
				self::${$key} = $val;
			}
		}
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
 * @return void
 */
	public static function rules($type, $rules, $reset = false) {
		$var = '_' . $type;

		switch ($type) {
			case 'transliteration':
				if ($reset) {
					self::$_transliteration = $rules;
				} else {
					self::$_transliteration = $rules + self::$_transliteration;
				}
				break;

			default:
				foreach ($rules as $rule => $pattern) {
					if (is_array($pattern)) {
						if ($reset) {
							self::${$var}[$rule] = $pattern;
						} else {
							if ($rule === 'uninflected') {
								self::${$var}[$rule] = array_merge($pattern, self::${$var}[$rule]);
							} else {
								self::${$var}[$rule] = $pattern + self::${$var}[$rule];
							}
						}
						unset($rules[$rule], self::${$var}['cache' . ucfirst($rule)]);
						if (isset(self::${$var}['merged'][$rule])) {
							unset(self::${$var}['merged'][$rule]);
						}
						if ($type === 'plural') {
							self::$_cache['pluralize'] = self::$_cache['tableize'] = array();
						} elseif ($type === 'singular') {
							self::$_cache['singularize'] = array();
						}
					}
				}
				self::${$var}['rules'] = $rules + self::${$var}['rules'];
		}
	}

/**
 * Return $word in plural form.
 *
 * @param string $word Word in singular
 * @return string Word in plural
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/inflector.html#Inflector::pluralize
 */
	public static function pluralize($word) {
		if (isset(self::$_cache['pluralize'][$word])) {
			return self::$_cache['pluralize'][$word];
		}

		if (!isset(self::$_plural['merged']['irregular'])) {
			self::$_plural['merged']['irregular'] = self::$_plural['irregular'];
		}

		if (!isset(self::$_plural['merged']['uninflected'])) {
			self::$_plural['merged']['uninflected'] = array_merge(self::$_plural['uninflected'], self::$_uninflected);
		}

		if (!isset(self::$_plural['cacheUninflected']) || !isset(self::$_plural['cacheIrregular'])) {
			self::$_plural['cacheUninflected'] = '(?:' . implode('|', self::$_plural['merged']['uninflected']) . ')';
			self::$_plural['cacheIrregular'] = '(?:' . implode('|', array_keys(self::$_plural['merged']['irregular'])) . ')';
		}

		if (preg_match('/(.*)\\b(' . self::$_plural['cacheIrregular'] . ')$/i', $word, $regs)) {
			self::$_cache['pluralize'][$word] = $regs[1] . substr($word, 0, 1) . substr(self::$_plural['merged']['irregular'][strtolower($regs[2])], 1);
			return self::$_cache['pluralize'][$word];
		}

		if (preg_match('/^(' . self::$_plural['cacheUninflected'] . ')$/i', $word, $regs)) {
			self::$_cache['pluralize'][$word] = $word;
			return $word;
		}

		foreach (self::$_plural['rules'] as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				self::$_cache['pluralize'][$word] = preg_replace($rule, $replacement, $word);
				return self::$_cache['pluralize'][$word];
			}
		}
	}

/**
 * Return $word in singular form.
 *
 * @param string $word Word in plural
 * @return string Word in singular
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/inflector.html#Inflector::singularize
 */
	public static function singularize($word) {
		if (isset(self::$_cache['singularize'][$word])) {
			return self::$_cache['singularize'][$word];
		}

		if (!isset(self::$_singular['merged']['uninflected'])) {
			self::$_singular['merged']['uninflected'] = array_merge(
				self::$_singular['uninflected'],
				self::$_uninflected
			);
		}

		if (!isset(self::$_singular['merged']['irregular'])) {
			self::$_singular['merged']['irregular'] = array_merge(
				self::$_singular['irregular'],
				array_flip(self::$_plural['irregular'])
			);
		}

		if (!isset(self::$_singular['cacheUninflected']) || !isset(self::$_singular['cacheIrregular'])) {
			self::$_singular['cacheUninflected'] = '(?:' . implode('|', self::$_singular['merged']['uninflected']) . ')';
			self::$_singular['cacheIrregular'] = '(?:' . implode('|', array_keys(self::$_singular['merged']['irregular'])) . ')';
		}

		if (preg_match('/(.*)\\b(' . self::$_singular['cacheIrregular'] . ')$/i', $word, $regs)) {
			self::$_cache['singularize'][$word] = $regs[1] . substr($word, 0, 1) . substr(self::$_singular['merged']['irregular'][strtolower($regs[2])], 1);
			return self::$_cache['singularize'][$word];
		}

		if (preg_match('/^(' . self::$_singular['cacheUninflected'] . ')$/i', $word, $regs)) {
			self::$_cache['singularize'][$word] = $word;
			return $word;
		}

		foreach (self::$_singular['rules'] as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				self::$_cache['singularize'][$word] = preg_replace($rule, $replacement, $word);
				return self::$_cache['singularize'][$word];
			}
		}
		self::$_cache['singularize'][$word] = $word;
		return $word;
	}

/**
 * Returns the given lower_case_and_underscored_word as a CamelCased word.
 *
 * @param string $lowerCaseAndUnderscoredWord Word to camelize
 * @return string Camelized word. LikeThis.
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/inflector.html#Inflector::camelize
 */
	public static function camelize($lowerCaseAndUnderscoredWord) {
		if (!($result = self::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord))) {
			$result = str_replace(' ', '', Inflector::humanize($lowerCaseAndUnderscoredWord));
			self::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord, $result);
		}
		return $result;
	}

/**
 * Returns the given camelCasedWord as an underscored_word.
 *
 * @param string $camelCasedWord Camel-cased word to be "underscorized"
 * @return string Underscore-syntaxed version of the $camelCasedWord
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/inflector.html#Inflector::underscore
 */
	public static function underscore($camelCasedWord) {
		if (!($result = self::_cache(__FUNCTION__, $camelCasedWord))) {
			$result = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
			self::_cache(__FUNCTION__, $camelCasedWord, $result);
		}
		return $result;
	}

/**
 * Returns the given underscored_word_group as a Human Readable Word Group.
 * (Underscores are replaced by spaces and capitalized following words.)
 *
 * @param string $lowerCaseAndUnderscoredWord String to be made more readable
 * @return string Human-readable string
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/inflector.html#Inflector::humanize
 */
	public static function humanize($lowerCaseAndUnderscoredWord) {
		if (!($result = self::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord))) {
			$result = ucwords(str_replace('_', ' ', $lowerCaseAndUnderscoredWord));
			self::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord, $result);
		}
		return $result;
	}

/**
 * Returns corresponding table name for given model $className. ("people" for the model class "Person").
 *
 * @param string $className Name of class to get database table name for
 * @return string Name of the database table for given class
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/inflector.html#Inflector::tableize
 */
	public static function tableize($className) {
		if (!($result = self::_cache(__FUNCTION__, $className))) {
			$result = Inflector::pluralize(Inflector::underscore($className));
			self::_cache(__FUNCTION__, $className, $result);
		}
		return $result;
	}

/**
 * Returns Cake model class name ("Person" for the database table "people".) for given database table.
 *
 * @param string $tableName Name of database table to get class name for
 * @return string Class name
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/inflector.html#Inflector::classify
 */
	public static function classify($tableName) {
		if (!($result = self::_cache(__FUNCTION__, $tableName))) {
			$result = Inflector::camelize(Inflector::singularize($tableName));
			self::_cache(__FUNCTION__, $tableName, $result);
		}
		return $result;
	}

/**
 * Returns camelBacked version of an underscored string.
 *
 * @param string $string
 * @return string in variable form
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/inflector.html#Inflector::variable
 */
	public static function variable($string) {
		if (!($result = self::_cache(__FUNCTION__, $string))) {
			$camelized = Inflector::camelize(Inflector::underscore($string));
			$replace = strtolower(substr($camelized, 0, 1));
			$result = preg_replace('/\\w/', $replace, $camelized, 1);
			self::_cache(__FUNCTION__, $string, $result);
		}
		return $result;
	}

/**
 * Returns a string with all spaces converted to underscores (by default), accented
 * characters converted to non-accented characters, and non word characters removed.
 *
 * @param string $string the string you want to slug
 * @param string $replacement will replace keys in map
 * @return string
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/inflector.html#Inflector::slug
 */
	public static function slug($string, $replacement = '_') {
		$quotedReplacement = preg_quote($replacement, '/');

		$merge = array(
			'/[^\s\p{Zs}\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]/mu' => ' ',
			'/[\s\p{Zs}]+/mu' => $replacement,
			sprintf('/^[%s]+|[%s]+$/', $quotedReplacement, $quotedReplacement) => '',
		);

		$map = self::$_transliteration + $merge;
		return preg_replace(array_keys($map), array_values($map), $string);
	}

}

// Store the initial state
Inflector::reset();
