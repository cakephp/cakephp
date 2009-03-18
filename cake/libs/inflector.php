<?php
/* SVN FILE: $Id$ */
/**
 * Pluralize and singularize English words.
 *
 * Used by Cake's naming conventions throughout the framework.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 0.2.9
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 *
 */
if (!class_exists('Object')) {
	uses('object');
}
if (!class_exists('Set')) {
	require LIBS . 'set.php';
}
/**
 * Pluralize and singularize English words.
 *
 * Inflector pluralizes and singularizes English nouns.
 * Used by Cake's naming conventions throughout the framework.
 * Test with $i = new Inflector(); $i->test();
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 * @link          http://book.cakephp.org/view/491/Inflector
 */
class Inflector extends Object {

	var $plural = array(
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
			'/us$/' => 'uses',
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

	var $singular = array(
		'rules' => array(
			'/(s)tatuses$/i' => '\1\2tatus',
			'/^(.*)(menu)s$/i' => '\1\2',
			'/(quiz)zes$/i' => '\\1',
			'/(matr)ices$/i' => '\1ix',
			'/(vert|ind)ices$/i' => '\1ex',
			'/^(ox)en/i' => '\1',
			'/(alias)(es)*$/i' => '\1',
			'/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|viri?)i$/i' => '\1us',
			'/([ftw]ax)es/' => '\1',
			'/(cris|ax|test)es$/i' => '\1is',
			'/(shoe)s$/i' => '\1',
			'/(o)es$/i' => '\1',
			'/ouses$/' => 'ouse',
			'/uses$/' => 'us',
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
			'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
			'/([ti])a$/i' => '\1um',
			'/(p)eople$/i' => '\1\2erson',
			'/(m)en$/i' => '\1an',
			'/(c)hildren$/i' => '\1\2hild',
			'/(n)ews$/i' => '\1\2ews',
			'/^(.*us)$/' => '\\1',
			'/s$/i' => ''
		),
		'uninflected' => array(
			'.*[nrlm]ese', '.*deer', '.*fish', '.*measles', '.*ois', '.*pox', '.*sheep', '.*ss'
    ),
		'irregular' => array()
	);

  	var $uninflected = array(
  	  'Amoyese', 'bison', 'Borghese', 'bream', 'breeches', 'britches', 'buffalo', 'cantus',
	    'carp', 'chassis', 'clippers', 'cod', 'coitus', 'Congoese', 'contretemps', 'corps',
	    'debris', 'diabetes', 'djinn', 'eland', 'elk', 'equipment', 'Faroese', 'flounder',
	    'Foochowese', 'gallows', 'Genevese', 'Genoese', 'Gilbertese', 'graffiti',
	    'headquarters', 'herpes', 'hijinks', 'Hottentotese', 'information', 'innings',
	    'jackanapes', 'Kiplingese', 'Kongoese', 'Lucchese', 'mackerel', 'Maltese', 'media',
	    'mews', 'moose', 'mumps', 'Nankingese', 'news', 'nexus', 'Niasese',
	    'Pekingese', 'Piedmontese', 'pincers', 'Pistoiese', 'pliers', 'Portuguese',
	    'proceedings', 'rabies', 'rice', 'rhinoceros', 'salmon', 'Sarawakese', 'scissors',
	    'sea[- ]bass', 'series', 'Shavese', 'shears', 'siemens', 'species', 'swine', 'testes',
	    'trousers', 'trout','tuna', 'Vermontese', 'Wenchowese', 'whiting', 'wildebeest',
	    'Yengeese'
	);


/**
 * Cached array identity map of pluralized words.
 *
 * @var array
 * @access private
 **/
	var $pluralized = array();
/**
 * List of pluralization rules in the form of pattern => replacement.
 *
 * @var array
 * @access public
 * @link http://book.cakephp.org/view/47/Custom-Inflections
 **/
	var $rules = array();
/**
 * Cached array identity map of singularized words.
 *
 * @var array
 * @access private
 **/
	var $singularized = array();
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
 * undocumented function
 *
 * @param string $type
 * @param string $rules
 * @return void
 * @author Joel Perras
 */
	function rules($type, $rules = array()) {
		$_this =& Inflector::getInstance();

    foreach ($rules as $rule => $pattern) {
        if (is_array($pattern)) {
          $_this->{$type}[$rule] = array_merge($pattern, $_this->{$type}[$rule]);
          unset($rules[$rule], $_this->{$type}['regex' . ucfirst($rule)]);
        }
    }
    $_this->{$type}['rules'] = array_merge($rules, $_this->{$type}['rules']);
	}
/**
 * Return $word in plural form.
 *
 * @param string $word Word in singular
 * @return string Word in plural
 * @access public
 * @static
 * @link http://book.cakephp.org/view/572/Class-methods
 */
	function pluralize($word) {
		$_this =& Inflector::getInstance();

		if (isset($_this->pluralized[$word])) {
			return $_this->pluralized[$word];
		}
		extract($_this->plural);
    	$uninflected = array_merge($uninflected, $_this->uninflected);

    	if (!isset($_this->plural['regexUninflected']) || !isset($_this->plural['regexIrregular'])) {
			$_this->plural['regexUninflected'] = '(?:' . join( '|', $uninflected) . ')';
			$_this->plural['regexIrregular'] = '(?:' . join( '|', array_keys($irregular)) . ')';
		}

		if (preg_match('/(.+?)\\b(' . $_this->plural['regexIrregular'] . ')$/i', $word, $regs)) {
			$_this->pluralized[$word] = $regs[1] . substr($word, 0, 1) . substr($irregular[strtolower($regs[2])], 1);
			return $_this->pluralized[$word];
		}

		if (preg_match('/^(' . $_this->plural['regexUninflected'] . ')$/i', $word, $regs)) {
			$_this->pluralized[$word] = $word;
			return $word;
		}

		foreach ($rules as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				$_this->pluralized[$word] = preg_replace($rule, $replacement, $word);
				return $_this->pluralized[$word];
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
 * @link http://book.cakephp.org/view/572/Class-methods
 */
	function singularize($word) {
		$_this =& Inflector::getInstance();

		if (isset($_this->singularized[$word])) {
			return $_this->singularized[$word];
		}

		extract($_this->singular);
    	$uninflected = array_merge($uninflected, $_this->uninflected);
    	$irregular = array_flip($_this->plural['irregular']);

		if (!isset($_this->singular['regexUninflected']) || !isset($_this->singular['regexIrregular'])) {
			$_this->singular['regexUninflected'] = '(?:' . join( '|', $uninflected) . ')';
        	$_this->singular['regexIrregular'] = '(?:' . join( '|', array_keys($irregular)) . ')';
		}

		if (preg_match('/(.+?)\\b(' . $_this->singular['regexUninflected'] . ')$/i', $word, $regs)) {
			$_this->singularized[$word] = $regs[1] . substr($word, 0, 1) . substr($irregular[strtolower($regs[2])], 1);
			return $_this->singularized[$word];
		}

		if (preg_match('/^(' . $_this->singular['regexUninflected'] . ')$/i', $word, $regs)) {
			$_this->singularized[$word] = $word;
			return $word;
		}

		foreach ($rules as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				$_this->singularized[$word] = preg_replace($rule, $replacement, $word);
				return $_this->singularized[$word];
			}
		}
		$_this->singularized[$word] = $word;
		return $word;
	}
/**
 * Returns the given lower_case_and_underscored_word as a CamelCased word.
 *
 * @param string $lower_case_and_underscored_word Word to camelize
 * @return string Camelized word. LikeThis.
 * @access public
 * @static
 * @link http://book.cakephp.org/view/572/Class-methods
 */
	function camelize($lowerCaseAndUnderscoredWord) {
		return str_replace(" ", "", ucwords(str_replace("_", " ", $lowerCaseAndUnderscoredWord)));
	}
/**
 * Returns the given camelCasedWord as an underscored_word.
 *
 * @param string $camelCasedWord Camel-cased word to be "underscorized"
 * @return string Underscore-syntaxed version of the $camelCasedWord
 * @access public
 * @static
 * @link http://book.cakephp.org/view/572/Class-methods
 */
	function underscore($camelCasedWord) {
		return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
	}
/**
 * Returns the given underscored_word_group as a Human Readable Word Group.
 * (Underscores are replaced by spaces and capitalized following words.)
 *
 * @param string $lower_case_and_underscored_word String to be made more readable
 * @return string Human-readable string
 * @access public
 * @static
 * @link http://book.cakephp.org/view/572/Class-methods
 */
	function humanize($lowerCaseAndUnderscoredWord) {
		return ucwords(str_replace("_", " ", $lowerCaseAndUnderscoredWord));
	}
/**
 * Returns corresponding table name for given model $className. ("people" for the model class "Person").
 *
 * @param string $className Name of class to get database table name for
 * @return string Name of the database table for given class
 * @access public
 * @static
 * @link http://book.cakephp.org/view/572/Class-methods
 */
	function tableize($className) {
		return Inflector::pluralize(Inflector::underscore($className));
	}
/**
 * Returns Cake model class name ("Person" for the database table "people".) for given database table.
 *
 * @param string $tableName Name of database table to get class name for
 * @return string Class name
 * @access public
 * @static
 * @link http://book.cakephp.org/view/572/Class-methods
 */
	function classify($tableName) {
		return Inflector::camelize(Inflector::singularize($tableName));
	}
/**
 * Returns camelBacked version of an underscored string.
 *
 * @param string $string
 * @return string in variable form
 * @access public
 * @static
 * @link http://book.cakephp.org/view/572/Class-methods
 */
	function variable($string) {
		$string = Inflector::camelize(Inflector::underscore($string));
		$replace = strtolower(substr($string, 0, 1));
		return preg_replace('/\\w/', $replace, $string, 1);
	}
/**
 * Returns a string with all spaces converted to underscores (by default), accented
 * characters converted to non-accented characters, and non word characters removed.
 *
 * @param string $string
 * @param string $replacement
 * @return string
 * @access public
 * @static
 * @link http://book.cakephp.org/view/572/Class-methods
 */
	function slug($string, $replacement = '_') {
		if (!class_exists('String')) {
			require LIBS . 'string.php';
		}
		$map = array(
			'/à|á|å|â/' => 'a',
			'/è|é|ê|ẽ|ë/' => 'e',
			'/ì|í|î/' => 'i',
			'/ò|ó|ô|ø/' => 'o',
			'/ù|ú|ů|û/' => 'u',
			'/ç/' => 'c',
			'/ñ/' => 'n',
			'/ä|æ/' => 'ae',
			'/ö/' => 'oe',
			'/ü/' => 'ue',
			'/Ä/' => 'Ae',
			'/Ü/' => 'Ue',
			'/Ö/' => 'Oe',
			'/ß/' => 'ss',
			'/[^\w\s]/' => ' ',
			'/\\s+/' => $replacement,
			String::insert('/^[:replacement]+|[:replacement]+$/', array('replacement' => preg_quote($replacement, '/'))) => '',
		);
		return preg_replace(array_keys($map), array_values($map), $string);
	}
}
?>
