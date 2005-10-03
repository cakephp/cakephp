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
 * @subpackage   cake.libs
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
 * @subpackage cake.libs
 * @since      CakePHP v .0.10.x.x
 */
class Inflector
{
    var $classical = array();
    
    function __construct() 
    {
    }
   
    function pluralize($text, $type = 'Noun' , $classical = false)
    {
        $this->classical = $classical;
        $this->count = count($text);
        
        return $this->_plural.$type($text);
    }
        
    function singularize($text, $type = 'Noun' , $classical = false)
    {
        $this->classical = $classical;
        $this->count = count($text);
        
        return $this->_singular.$type($text);
    }
    
    function _pluralNoun($text)
    {
        return $pluralText;
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
        return '"(?:'.$string.')"';
    }
}
?>