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
 * Copyright (c) 2006, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * Included libraries.
  *
  */
if(!class_exists('Object'))
{
    uses('object');
}
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
        $pluralRules = array('/(s)tatus$/i'             => '\1\2tatuses',
                             '/^(ox)$/i'                => '\1\2en',      # ox
                             '/([m|l])ouse$/i'          => '\1ice',       # mouse, louse
                             '/(matr|vert|ind)ix|ex$/i' =>  '\1ices',     # matrix, vertex, index
                             '/(x|ch|ss|sh)$/i'         =>  '\1es',       # search, switch, fix, box, process, address
                             '/([^aeiouy]|qu)y$/i'      =>  '\1ies',      # query, ability, agency
                             '/(hive)$/i'               =>  '\1s',        # archive, hive
                             '/(?:([^f])fe|([lr])f)$/i' =>  '\1\2ves',    # half, safe, wife
                             '/sis$/i'                  =>  'ses',        # basis, diagnosis
                             '/([ti])um$/i'             =>  '\1a',        # datum, medium
                             '/(p)erson$/i'             =>  '\1eople',    # person, salesperson
                             '/(m)an$/i'                =>  '\1en',       # man, woman, spokesman
                             '/(c)hild$/i'              =>  '\1hildren',  # child
                             '/(buffal|tomat)o$/i'      =>  '\1\2oes',    # buffalo, tomato
                             '/(bu)s$/i'                =>  '\1\2ses',    # bus
                             '/(alias)/i'               =>  '\1es',       # alias
                             '/(octop|vir)us$/i'        =>  '\1i',        # octopus, virus - virus has no defined plural (according to Latin/dictionary.com), but viri is better than viruses/viruss
                             '/(ax|cri|test)is$/i'      =>  '\1es',       # axis, crisis
                             '/s$/'                     =>  's',          # no change (compatibility)
                             '/$/'                      => 's');

        $uninflected = array('.*[nrlm]ese', '.*deer', '.*fish', '.*measles', '.*ois', '.*pox', '.*rice', '.*sheep', 'Amoyese',
                             'bison', 'Borghese', 'bream', 'breeches', 'britches', 'buffalo', 'cantus', 'carp', 'chassis', 'clippers',
                             'cod', 'coitus', 'Congoese', 'contretemps', 'corps', 'debris', 'diabetes', 'djinn', 'eland', 'elk',
                             'equipment', 'Faroese', 'flounder', 'Foochowese', 'gallows', 'Genevese', 'Genoese', 'Gilbertese', 'graffiti',
                             'headquarters', 'herpes', 'hijinks', 'Hottentotese', 'information', 'innings', 'jackanapes', 'Kiplingese',
                             'Kongoese', 'Lucchese', 'mackerel', 'Maltese', 'mews', 'moose', 'mumps', 'Nankingese', 'news',
                             'nexus', 'Niasese', 'Pekingese', 'Piedmontese', 'pincers', 'Pistoiese', 'pliers', 'Portuguese', 'proceedings',
                             'rabies', 'rhinoceros', 'salmon', 'Sarawakese', 'scissors', 'sea[- ]bass', 'series', 'Shavese', 'shears',
                             'siemens', 'species', 'swine', 'testes', 'trousers', 'trout', 'tuna', 'Vermontese', 'Wenchowese',
                             'whiting', 'wildebeest', 'Yengeese',);

        $irregular = array('atlas'     => 'atlases',
                           'beef'      => 'beefs',
                           'brother'   => 'brothers',
                           'child'     => 'children',
                           'corpus'    => 'corpuses',
                           'cow'       => 'cows',
                           'ganglion'  => 'ganglions',
                           'genie'     => 'genies',
                           'genus'     => 'genera',
                           'graffito'  => 'graffiti',
                           'hoof'      => 'hoofs',
                           'loaf'      => 'loaves',
                           'man'       => 'men',
                           'money'     => 'monies',
                           'mongoose'  => 'mongooses',
                           'move'      => 'moves',
                           'mythos'    => 'mythoi',
                           'numen'     => 'numina',
                           'occiput'   => 'occiputs',
                           'octopus'   => 'octopuses',
                           'opus'      => 'opuses',
                           'ox'        => 'oxen',
                           'penis'     => 'penises',
                           'person'    => 'people',
                           'sex'       => 'sexes',
                           'soliloquy' => 'soliloquies',
                           'testis'    => 'testes',
                           'trilby'    => 'trilbys',
                           'turf'      => 'turfs',);

        $regexUninflected = __enclose(join( '|', $uninflected));
        $regexIrregular = __enclose(join( '|', array_keys($irregular)));

        if (preg_match('/^('.$regexUninflected.')$/i', $word, $regs))
        {
            return $word;
        }

        if (preg_match('/(.*)\\b('.$regexIrregular.')$/i', $word, $regs))
        {
            return $regs[1] . $irregular[strtolower($regs[2])];
        }

        foreach ($pluralRules as $rule => $replacement)
        {
            if (preg_match($rule, $word))
            {
                return preg_replace($rule, $replacement, $word);
            }
        }
        return $word;
    }

/**
 * Return $word in singular form.
 *
 * @param string $word Word in plural
 * @return string Word in singular
 */
    function singularize ($word)
    {
        $singularRules = array('/(s)tatuses$/i'         => '\1\2tatus',
                               '/(matr)ices$/i'         =>'\1ix',
                               '/(vert|ind)ices$/i'     => '\1ex',
                               '/^(ox)en/i'             => '\1',
                               '/(alias)es$/i'          => '\1',
                               '/([octop|vir])i$/i'     => '\1us',
                               '/(cris|ax|test)es$/i'   => '\1is',
                               '/(shoe)s$/i'            => '\1',
                               '/(o)es$/i'              => '\1',
                               '/(bus)es$/i'            => '\1',
                               '/([m|l])ice$/i'         => '\1ouse',
                               '/(x|ch|ss|sh)es$/i'     => '\1',
                               '/(m)ovies$/i'           => '\1\2ovie',
                               '/(s)eries$/i'           => '\1\2eries',
                               '/([^aeiouy]|qu)ies$/i'  => '\1y',
                               '/([lr])ves$/i'          => '\1f',
                               '/(tive)s$/i'            => '\1',
                               '/(hive)s$/i'            => '\1',
                               '/(drive)s$/i'           => '\1',
                               '/([^f])ves$/i'          => '\1fe',
                               '/(^analy)ses$/i'        => '\1sis',
                               '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
                               '/([ti])a$/i'            => '\1um',
                               '/(p)eople$/i'           => '\1\2erson',
                               '/(m)en$/i'              => '\1an',
                               '/(c)hildren$/i'         => '\1\2hild',
                               '/(n)ews$/i'             => '\1\2ews',
                               '/s$/i'                  => '');

        $uninflected = array('.*[nrlm]ese', '.*deer', '.*fish', '.*measles', '.*ois', '.*pox', '.*rice', '.*sheep', 'Amoyese',
                             'bison', 'Borghese', 'bream', 'breeches', 'britches', 'buffalo', 'cantus', 'carp', 'chassis', 'clippers',
                             'cod', 'coitus', 'Congoese', 'contretemps', 'corps', 'debris', 'diabetes', 'djinn', 'eland', 'elk',
                             'equipment', 'Faroese', 'flounder', 'Foochowese', 'gallows', 'Genevese', 'Genoese', 'Gilbertese', 'graffiti',
                             'headquarters', 'herpes', 'hijinks', 'Hottentotese', 'information', 'innings', 'jackanapes', 'Kiplingese',
                             'Kongoese', 'Lucchese', 'mackerel', 'Maltese', 'mews', 'moose', 'mumps', 'Nankingese', 'news',
                             'nexus', 'Niasese', 'Pekingese', 'Piedmontese', 'pincers', 'Pistoiese', 'pliers', 'Portuguese', 'proceedings',
                             'rabies', 'rhinoceros', 'salmon', 'Sarawakese', 'scissors', 'sea[- ]bass', 'series', 'Shavese', 'shears',
                             'siemens', 'species', 'swine', 'testes', 'trousers', 'trout', 'tuna', 'Vermontese', 'Wenchowese',
                             'whiting', 'wildebeest', 'Yengeese',);

        $irregular = array('atlas'       => 'atlases',
                           'beefs'       => 'beef',
                           'brothers'    => 'brother',
                           'children'    => 'child',
                           'corpuses'    => 'corpus',
                           'cows'        => 'cow',
                           'ganglions'   => 'ganglion',
                           'genies'      => 'genie',
                           'genera'      => 'genus',
                           'graffiti'    => 'graffito',
                           'hoofs'       => 'hoof',
                           'loaves'      => 'loaf',
                           'men'         => 'man',
                           'monies'      => 'money',
                           'mongooses'   => 'mongoose',
                           'moves'       => 'move',
                           'mythoi'      => 'mythos',
                           'numina'      => 'numen',
                           'occiputs'    => 'occiput',
                           'octopuses'   => 'octopus',
                           'opuses'      => 'opus',
                           'oxen'        => 'ox',
                           'penises'     => 'penis',
                           'people'      => 'person',
                           'sexes'       => 'sex',
                           'soliloquies' => 'soliloquy',
                           'testes'      => 'testis',
                           'trilbys'     => 'trilby',
                           'turfs'       => 'turf',);

        $regexUninflected = __enclose(join( '|', $uninflected));
        $regexIrregular = __enclose(join( '|', array_keys($irregular)));

        if (preg_match('/^('.$regexUninflected.')$/i', $word, $regs))
        {
            return $word;
        }

        if (preg_match('/(.*)\\b('.$regexIrregular.')$/i', $word, $regs))
        {
            return $regs[1] . $irregular[strtolower($regs[2])];
        }

        foreach ($singularRules as $rule => $replacement)
        {
            if (preg_match($rule, $word))
            {
                return preg_replace($rule, $replacement, $word);
            }
        }
        return $word;
    }

/**
  * Returns given $lower_case_and_underscored_word as a camelCased word.
  *
  * @param string $lower_case_and_underscored_word Word to camelize
  * @return string Camelized word. likeThis.
  */
   function camelize($lowerCaseAndUnderscoredWord)
   {
      return str_replace(" ","",ucwords(str_replace("_"," ",$lowerCaseAndUnderscoredWord)));
   }

/**
  * Returns an underscore-syntaxed ($like_this_dear_reader) version of the $camel_cased_word.
  *
  * @param string $camel_cased_word Camel-cased word to be "underscorized"
  * @return string Underscore-syntaxed version of the $camel_cased_word
  */
   function underscore($camelCasedWord)
   {
      return strtolower (preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
   }

/**
  * Returns a human-readable string from $lower_case_and_underscored_word,
  * by replacing underscores with a space, and by upper-casing the initial characters.
  *
  * @param string $lower_case_and_underscored_word String to be made more readable
  * @return string Human-readable string
  */
   function humanize($lowerCaseAndUnderscoredWord)
   {
      return ucwords(str_replace("_"," ",$lowerCaseAndUnderscoredWord));
   }

/**
  * Returns corresponding table name for given $class_name. ("posts" for the model class "Post").
  *
  * @param string $class_name Name of class to get database table name for
  * @return string Name of the database table for given class
  */
   function tableize($className)
   {
      return Inflector::pluralize(Inflector::underscore($className));
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
   function foreignKey($className)
   {
      return Inflector::underscore($className) . "_id";
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

function __enclose($string)
{
    return '(?:'.$string.')';
}

?>