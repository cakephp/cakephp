<?PHP 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Inflector
  * I'm trying to port RoR Inflector class here.
  * Inflector pluralizes and singularizes english nouns.
  * Test with $i = new Inflector(); $i->test();
  *
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
  */

/**
  * Enter description here...
  *
  */
uses('object');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Inflector extends Object {
	
/**
  * Enter description here...
  *
  */
	function __construct () {
		parent::__construct();
	}
	
/**
  * Enter description here...
  *
  * @param unknown_type $word
  * @return unknown
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
  * Enter description here...
  *
  * @param unknown_type $word
  * @return unknown
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
  * Enter description here...
  *
  * @param unknown_type $lower_case_and_underscored_word
  * @return unknown
  */
	function camelize($lower_case_and_underscored_word) {
		return str_replace(" ","",ucwords(str_replace("_"," ",$lower_case_and_underscored_word)));
	}    

/**
  * Enter description here...
  *
  * @param unknown_type $camel_cased_word
  * @return unknown
  */
	function underscore($camel_cased_word) {
		$camel_cased_word = preg_replace('/([A-Z]+)([A-Z])/','\1_\2',$camel_cased_word);
		return strtolower(preg_replace('/([a-z])([A-Z])/','\1_\2',$camel_cased_word));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $lower_case_and_underscored_word
  * @return unknown
  */
	function humanize($lower_case_and_underscored_word) {
		return ucwords(str_replace("_"," ",$lower_case_and_underscored_word));
	}    

/**
  * Enter description here...
  *
  * @param unknown_type $class_name
  * @return unknown
  */
	function tableize($class_name) {
		return Inflector::pluralize(Inflector::underscore($class_name));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $table_name
  * @return unknown
  */
	function classify($table_name) {
		return $this->camelize($this->singularize($table_name));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $class_name
  * @return unknown
  */
	function foreignKey($class_name) {
		return $this->underscore($class_name) . "_id";
	} 
}

?>
