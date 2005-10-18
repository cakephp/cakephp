<?php
/* SVN FILE: $Id$ */

/**
 * Pluralize and singularize English words.
 * 
 * Used by Cake's naming conventions throughout the framework.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, CakePHP Authors/Developers
 *
 * Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com>
 *            Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The MIT License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.cake.libs
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Pluralize and singularize English words.
 *
 * Inflector pluralizes and singularizes English nouns. 
 * Used by Cake's naming conventions throughout the framework.
 * Test with $i = new Inflector(); $i->test();
 *
 * @package    cake
 * @subpackage cake.cake.libs
 * @since      CakePHP v 0.2.9
 */
class Inflector extends Object 
{
   
/**
  * Constructor.
  *
  */
   function __construct () 
   {
      parent::__construct();
   }
   
/**
  * Return $word in plural form.
  *
  * @param string $word Word in singular
  * @return string Word in plural
  */
   function pluralize ($word) 
   {
      $plural_rules = array(
      	'/^(ox)$/'              => '\1\2en',	    # ox
      	'/([m|l])ouse$/'          => '\1ice',	    # mouse, louse
      	'/(matr|vert|ind)ix|ex$/' =>  '\1ices',     # matrix, vertex, index
        '/(x|ch|ss|sh)$/'         =>  '\1es',       # search, switch, fix, box, process, address
        //'/([^aeiouy]|qu)ies$/'    =>  '\1y',
        '/([^aeiouy]|qu)y$/'      =>  '\1ies',      # query, ability, agency
        '/(hive)$/'               =>  '\1s',        # archive, hive
        '/(?:([^f])fe|([lr])f)$/' =>  '\1\2ves',    # half, safe, wife
        '/sis$/'                  =>  'ses',        # basis, diagnosis
        '/([ti])um$/'             =>  '\1a',        # datum, medium
        '/(p)erson$/'             =>  '\1eople',    # person, salesperson
        '/(m)an$/'                =>  '\1en',       # man, woman, spokesman
        '/(c)hild$/'              =>  '\1hildren',  # child
      	'/(buffal|tomat)o$/'      =>  '\1\2oes',    # buffalo, tomato
      	'/(bu)s$/'                =>  '\1\2ses',    # bus
        '/(alias)/'               =>  '\1es',       # alias
      	'/(octop|vir)us$/'        =>  '\1i',        # octopus, virus - virus has no defined plural (according to Latin/dictionary.com), but viri is better than viruses/viruss
      	'/(ax|cri|test)is$/'      =>  '\1es',       # axis, crisis 
        '/s$/'                    =>  's',          # no change (compatibility)
        '/$/'                     => 's'
      );
      
      foreach ($plural_rules as $rule => $replacement) 
      {
         if (preg_match($rule, $word)) 
         {
            return preg_replace($rule, $replacement, $word);
         }
      }
      
      return $word;//false;
   }

/**
  * Return $word in singular form.
  *
  * @param string $word Word in plural
  * @return string Word in singular
  */
   function singularize ($word)
   {
      $singular_rules = array(
        '/(matr)ices$/'         =>'\1ix',
      	'/(vert|ind)ices$/'     => '\1ex',
      	'/^(ox)en/'             => '\1',
      	'/(alias)es$/'          => '\1',
      	'/([octop|vir])i$/'     => '\1us',
      	'/(cris|ax|test)es$/'   => '\1is',
      	'/(shoe)s$/'            => '\1',
      	'/(o)es$/'              => '\1',
      	'/(bus)es$/'            => '\1',
      	'/([m|l])ice$/'         => '\1ouse',
        '/(x|ch|ss|sh)es$/'     => '\1',
        '/(m)ovies$/'           => '\1\2ovie',
        '/(s)eries$/'           => '\1\2eries',
        '/([^aeiouy]|qu)ies$/'  => '\1y',
        '/([lr])ves$/'          => '\1f',
        '/(tive)s$/'            => '\1',
        '/(hive)s$/'            => '\1',
        '/([^f])ves$/'          => '\1fe',
        '/(^analy)ses$/'        => '\1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/' => '\1\2sis',
        '/([ti])a$/'            => '\1um',
        '/(p)eople$/'           => '\1\2erson',
        '/(m)en$/'              => '\1an',
        '/(s)tatuses$/'           => '\1\2tatus',
        '/(c)hildren$/'         => '\1\2hild',
        '/(n)ews$/'             => '\1\2ews',
        '/s$/'                  => ''
      );

      foreach ($singular_rules as $rule => $replacement) 
      {
         if (preg_match($rule, $word)) 
         {
            return preg_replace($rule, $replacement, $word);
         }
      }
      // should not return false is not matched
      return $word;//false;
   }
   
/**
  * Returns given $lower_case_and_underscored_word as a camelCased word.
  *
  * @param string $lower_case_and_underscored_word Word to camelize
  * @return string Camelized word. likeThis.
  */
   function camelize($lower_case_and_underscored_word) 
   {
      return str_replace(" ","",ucwords(str_replace("_"," ",$lower_case_and_underscored_word)));
   }    

/**
  * Returns an underscore-syntaxed ($like_this_dear_reader) version of the $camel_cased_word.
  *
  * @param string $camel_cased_word Camel-cased word to be "underscorized"
  * @return string Underscore-syntaxed version of the $camel_cased_word
  */
   function underscore($camel_cased_word) 
   {
      $camel_cased_word = preg_replace('/([A-Z]+)([A-Z])/','\1_\2', $camel_cased_word);
      return strtolower(preg_replace('/([a-z])([A-Z])/','\1_\2', $camel_cased_word));
   }

/**
  * Returns a human-readable string from $lower_case_and_underscored_word,
  * by replacing underscores with a space, and by upper-casing the initial characters.
  *
  * @param string $lower_case_and_underscored_word String to be made more readable
  * @return string Human-readable string
  */
   function humanize($lower_case_and_underscored_word) 
   {
      return ucwords(str_replace("_"," ",$lower_case_and_underscored_word));
   }    

/**
  * Returns corresponding table name for given $class_name. ("posts" for the model class "Post").
  *
  * @param string $class_name Name of class to get database table name for
  * @return string Name of the database table for given class
  */
   function tableize($class_name) 
   {
      return Inflector::pluralize(Inflector::underscore($class_name));
   }

/**
  * Returns Cake model class name ("Post" for the database table "posts".) for given database table.
  *
  * @param string $tableName Name of database table to get class name for
  * @return string
  */
   function classify($tableName)
   {
      return Inflector::camelize(Inflector::singularize($tableName));
   }

/**
  * Returns $class_name in underscored form, with "_id" tacked on at the end. 
  * This is for use in dealing with foreign keys in the database.
  *
  * @param string $class_name
  * @return string
  */
   function foreignKey($class_name)
   {
      return Inflector::underscore($class_name) . "_id";
   } 
   
/**
 * Returns filename for given Cake controller name.
 *
 * @param string $name
 * @return string
 */
   function toControllerFilename($name)
   {
      return CONTROLLERS.Inflector::underscore($name).'.php';
   }
   
/**
 * Returns filename for given Cake helper name.
 *
 * @param string $name
 * @return string
    */
   function toHelperFilename($name)
   {
      return HELPERS.Inflector::underscore($name).'.php';
   }
   
/**
 * Returns given name as camelized.
 *
 * @param string $name
 * @param string $correct 
 * @return string
 * @todo Explain this method
 */
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
   
/**
 * Returns filename for given Cake library name.
 *
 * @param string $name
 * @return string
 */
   function toLibraryFilename ($name)
   {
      return LIBS.Inflector::underscore($name).'.php';
   }
}

?>
