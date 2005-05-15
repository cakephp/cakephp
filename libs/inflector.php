<?PHP 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <http://sputnik.pl/cake>                                    + //
// + Copyright: (c) 2005 Michal Tatarynowicz                          + //
// +                                                                  + //
// + Author(s): (c) 2005 Michal Tatarynowicz <tatarynowicz@gmail.com> + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under the Public Domain Licence                         + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Inflector
  * I'm trying to port RoR Inflector class here.
  * Inflector pluralizes and singularizes english nouns.
  * Test with $i = new Inflector(); $i->test();
  *
  * @filesource 
  * @modifiedby $LastChangedBy$  
  * @lastmodified $Date$
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @copyright Copyright (c) 2005, Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @license Public_Domain
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
    // private

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
    function foreign_key($class_name) {
        return $this->underscore($class_name) . "_id";
    }

/**
  * Enter description here...
  *
  * @param unknown_type $verbose
  */
    function test ($verbose=false) {
        $singulars = array(
        'search', 'switch', 'fix', 'box', 'process', 'address', 'query', 'ability',
        'agency', 'half', 'safe', 'wife', 'basis', 'diagnosis', 'datum', 'medium',
        'person', 'salesperson', 'man', 'woman', 'spokesman', 'child', 'page', 'robot');
        $plurals = array(
        'searches', 'switches', 'fixes', 'boxes', 'processes', 'addresses', 'queries', 'abilities',
        'agencies', 'halves', 'saves', 'wives', 'bases', 'diagnoses', 'data', 'media',
        'people', 'salespeople', 'men', 'women', 'spokesmen', 'children', 'pages', 'robots');

        $pluralize_errors = 0;
        $singularize_errors = 0;
        $tests = 0;
        foreach (array_combine($singulars, $plurals) as $singular => $plural) {
            if ($this->pluralize($singular) != $plural) {
                debug ("Inflector test {$singular} yelded ".$this->pluralize($singular)." (expected {$plural})");
                $pluralize_errors++;
            }
            elseif ($verbose) {
                debug ("Inflector test ok: {$singular} => {$plural}",1);
            }

            if ($this->singularize($plural) != $singular) {
                debug ("Inflector test {$plural} yelded ".$this->singularize($plural)." (expected {$singular})");
                $singularize_errors++;
            }
            elseif ($verbose) {
                debug ("Inflector test ok: {$plural} => {$singular}",1);
            }
            $tests++;
        }

        $errors = $pluralize_errors + $singularize_errors;
        debug ("<b>Inflector: {$tests} tests, {$errors} errors (".($errors?'FAILED':'PASSED').')</b>');
    }
}



if (!function_exists('array_combine')) {
/**
  * Enter description here...
  *
  * @param unknown_type $a1
  * @param unknown_type $a2
  * @return unknown
  */
    function array_combine($a1, $a2) {
        $a1 = array_values($a1);
        $a2 = array_values($a2);

        if (count($a1) != count($a2)) return false; // different lenghts
        if (count($a1) <= 0) return false; // arrays are the same and both are empty

        $output = array();

        for ($i = 0; $i < count($a1); $i++) {
            $output[$a1[$i]] = $a2[$i];
        }

        return $output;
    }
}

?>
