<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 * 
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, Cake Software Foundation, Inc. 
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @copyright    Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs
 * @since        CakePHP v .0.10.x.x
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Short description for class
 *
 * Inflector pluralizes and singularizes English words.
 *
 * @package    cake
 * @subpackage cake.cake.libs
 * @since      CakePHP v .0.10.x.x
 */
class Inflector 
{
    var $classical = array();
    
    var $pre = '';
    
    var $word = '';
    
    var $post = '';
    
    
   function &getInstance() {
       
       static $instance = array();
       if (!$instance)
       {
           $instance[0] =& new Inflector; 
       }
       return $instance[0];
   }
    
    function pluralize($text, $type = 'Noun' , $classical = false)
    {
        $inflec =& Inflector::getInstance();
        $inflec->classical = $classical;
        $inflec->count = strlen($text);
        
        if ($inflec->count == 1)
        {
            return $text;
        }
        if(empty($text))
        {
            return;
        }
        
        $inflec->_pre($text);
        
        if (empty($inflec->word))
        {
            return $text;
        }
        
        $type = '_plural'.$type;
        $inflected = $inflec->_postProcess($inflec->word,$inflec->$type());
        return $inflected;
    }
        
    function singularize($text, $type = 'Noun' , $classical = false)
    {
        $inflec =& Inflector::getInstance();
        $inflec->classical = $classical;
        $inflec->count = count($text);
        
        if ($inflec->count == 1)
        {
            return $text;
        }
        if(empty($text))
        {
            return;
        }
        
        return $inflec->_singular.$type($text);
    }
    
    function _pluralNoun()
    {
        $inflec =& Inflector::getInstance();
        
        require_once(CAKE.'config'.DS.'nouns.php');
        
        $regexPluralUninflected = $inflec->_enclose(join( '|', array_values(array_merge($pluralUninflected,$pluralUninflecteds))));
        
        $regexPluralUninflectedHerd = $inflec->_enclose(join( '|', array_values($pluralUninflectedHerd)));
        
        $pluralIrregular = array_merge($pluralIrregular,$pluralIrregulars);
        $regexPluralIrregular = $inflec->_enclose(join( '|', array_keys($pluralIrregular)));
        
        if (preg_match('/^('.$regexPluralUninflected.')$/i', $inflec->word, $regs))
        {
            return $inflec->word;
        }
        
        if (empty($inflec->classical))
        {
            preg_match('/^('.$regexPluralUninflectedHerd.')$/i', $inflec->word, $regs);
            return $inflec->word;
        }
        
        if (preg_match('/(.*)\\b('.$regexPluralIrregular.')$/i', $inflec->word, $regs))
        {
            return $regs[1] . $pluralIrregular[strtolower($regs[2])];
        }

        return $inflec->word.'s';
    }
    
    function _pluralVerb($text)
    {
        return $pluralText;
    }
    
    function _pluralAdjective($text)
    {
        return $pluralText;
    }
    
    function _pluralSpecialNoun($text)
    {
        return $pluralText;
    }
    
    function _pluralSpecialVerb($text)
    {
        return $pluralText;
    }
    
    function _pluralGeneralVerb($text)
    {
        return $pluralText;
    }
    
    function _pluralSpecialAdjective($text)
    {
        return $pluralText;
    }

    function _singularNoun($text)
    {
        return $text;
    }
    
    function _singularVerb($text)
    {
        return $singularText;
    }
    
    function _singularAdjective($text)
    {
        return $singularText;
    }
    
    function _singularSpecialNoun($text)
    {
        return $singularText;
    }
    
    function _singularSpecialVerb($text)
    {
        return $singularText;
    }
    
    function _singularGeneralVerb($text)
    {
        return $singularText;
    }
    
    function _singularSpecialAdjective($text)
    {
        return $singularText;
    }
    
    function _enclose($string)
    {
        return '(?:'.$string.')';
    }
    
    function _pre($text)
    {
        $inflec =& Inflector::getInstance();
        if (preg_match('/\\A(\\s*)(.+?)(\\s*)\\Z/', $text, $regs))
        {
            if (!empty($regs[1]))
            {
                $inflec->pre = $regs[1];
            }
            
            if (!empty($regs[2]))
            {
                $inflec->word = $regs[2];
            }
            
            if (!empty($regs[3]))
            {
                $inflec->post = $regs[3];;
            }
        }
    }
    
    function _postProcess($orig, $inflected)
    {
        $inflec =& Inflector::getInstance();
        $inflected = preg_replace('/([^|]+)\\|(.+)/', $inflec->classical ? '${2}' : '${1}', $inflected);
        
        return $inflected;
    }

}

echo Inflector::pluralize('rhinoceros');
?>