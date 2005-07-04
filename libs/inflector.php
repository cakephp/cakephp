<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Inflector
  * I'm trying to port RoR Inflector class here.
  * Inflector pluralizes and singularizes English nouns.
  * Test with $i = new Inflector(); $i->test();
  *
  * @filesource 
  * @author CakePHP Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since CakePHP v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

/**
  * This is a port of Ruby on Rails' Inflector class.
  * Inflector pluralizes and singularizes English nouns.
  * Test with $i = new Inflector(); $i->test();
  *
  * @package cake
  * @subpackage cake.libs
  * @since CakePHP v 0.2.9
  */
class Inflector extends Object 
{
	
/**
  * Constructor.
  *
  */
	function __construct () {
		parent::__construct();
	}
	
/**
  * Return $word in plural form.
  *
  * @param string $word Word in singular
  * @return string Word in plural
  */
	function pluralize ($word) {
		$plural_rules = array(
			'/(x|ch|ss|sh)$/'			=> '\1es',       # search, switch, fix, box, process, address
			'/series$/'					=> '\1series',
			'/([^aeiouy]|qu)ies$/'	=> '\1y',
			'/([^aeiouy]|qu)y$/'		=> '\1ies',      # query, ability, agency
			'/(?:([^f])fe|([lr])f)$/' => '\1\2ves', # half, safe, wife
			'/sis$/'						=> 'ses',        # basis, diagnosis
			'/([ti])um$/'				=> '\1a',        # datum, medium
			'/person$/'					=> 'people',     # person, salesperson
			'/man$/'						=> 'men',        # man, woman, spokesman
			'/child$/'					=> 'children',   # child
			'/s$/'						=> 's',          # no change (compatibility)
			'/$/'							=> 's'
		);

		foreach ($plural_rules as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				return preg_replace($rule, $replacement, $word);
			}
		}
		
		return false;
	}

/**
  * Return $word in singular form.
  *
  * @param string $word Word in plural
  * @return string Word in singular
  */
	function singularize ($word) {
		$singular_rules = array(
			'/(x|ch|ss)es$/'		   => '\1',
			'/movies$/'				   => 'movie',
			'/series$/'				   => 'series',
			'/([^aeiouy]|qu)ies$/'  => '\1y',
			'/([lr])ves$/'			   => '\1f',
			'/([^f])ves$/'			   => '\1fe',
			'/(analy|ba|diagno|parenthe|progno|synop|the)ses$/' => '\1sis',
			'/([ti])a$/'				=> '\1um',
			'/people$/'					=> 'person',
			'/men$/'						=> 'man',
			'/status$/'					=> 'status',
			'/children$/'				=> 'child',
			'/news$/'					=> 'news',
			'/s$/'						=> ''
		);

		foreach ($singular_rules as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				return preg_replace($rule, $replacement, $word);
			}
		}
		
		return false;
	}
	
/**
  * Returns given $lower_case_and_underscored_word as a camelCased word.
  *
  * @param string $lower_case_and_underscored_word Word to camelize
  * @return string Camelized word. likeThis.
  */
	function camelize($lower_case_and_underscored_word) {
		return str_replace(" ","",ucwords(str_replace("_"," ",$lower_case_and_underscored_word)));
	}    

/**
  * Returns an underscore-syntaxed ($like_this_dear_reader) version of the $camel_cased_word.
  *
  * @param string $camel_cased_word Camel-cased word to be "underscorized"
  * @return string Underscore-syntaxed version of the $camel_cased_word
  */
	function underscore($camel_cased_word) {
		$camel_cased_word = preg_replace('/([A-Z]+)([A-Z])/','\1_\2',$camel_cased_word);
		return strtolower(preg_replace('/([a-z])([A-Z])/','\1_\2',$camel_cased_word));
	}

/**
  * Returns a human-readable string from $lower_case_and_underscored_word,
  * by replacing underscores with a space, and by upper-casing the initial characters.
  *
  * @param string $lower_case_and_underscored_word String to be made more readable
  * @return string Human-readable string
  */
	function humanize($lower_case_and_underscored_word) {
		return ucwords(str_replace("_"," ",$lower_case_and_underscored_word));
	}    

/**
  * Returns corresponding table name for given $class_name.
  *
  * @param string $class_name Name of class to get database table name for
  * @return string Name of the database table for given class
  */
	function tableize($class_name) {
		return Inflector::pluralize(Inflector::underscore($class_name));
	}

/**
  * Returns Cake class name ("Post" for the database table "posts".) for given database table.
  *
  * @param string $table_name Name of database table to get class name for
  * @return string
  */
	function classify($table_name)
	{
		return Inflector::camelize(Inflector::singularize($table_name));
	}

/**
  * Returns $class_name in underscored form, with "_id" tacked on at the end. 
  * This is for use in dealing with the database.
  *
  * @param string $class_name
  * @return string
  */
	function foreignKey($class_name)
	{
		return Inflector::underscore($class_name) . "_id";
	} 
	
	function toControllerFilename($name)
	{
		return CONTROLLERS.Inflector::underscore($name).'.php';
	}
	
	function toHelperFilename($name)
	{
		return HELPERS.Inflector::underscore($name).'.php';
	}
	
	function toFullName($name, $correct)
	{
		if (strstr($name, '_') && (strtolower($name) == $name))
		{
			return Inflector::camelize($name);
		}
	
		if (preg_match("/^(.*)({$correct})$/i", $name, $reg))
		{
			if ($reg[2] == $correct)
			{
				return $name;
			}
			else 
			{
				return ucfirst($reg[1].$correct);
			}
		}
		else 
		{
			return ucfirst($name.$correct);
		}
	}
	
	function toLibraryFilename ($name)
	{
		return LIBS.Inflector::underscore($name).'.php';
	}
}

?>
