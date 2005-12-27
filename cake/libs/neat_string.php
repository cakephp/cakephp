<?php
/* SVN FILE: $Id$ */

/**
 * String handling methods.
 *
 * Random passwords, splitting strings into arrays, removing Cyrillic characters, stripping whitespace.
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
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * String handling methods.
 *
 * Random passwords, splitting strings into arrays, removing Cyrillic characters, stripping whitespace.
 *
 * @package    cake
 * @subpackage cake.cake.libs
 * @since      CakePHP v 0.2.9
 * @static 
 */
class NeatString
{
 /**
 * Returns an array with each of the non-empty characters in $string as an element.
 *
 * @param string $string
 * @return array
 */
    function toArray ($string)
    {
        return preg_split('//', $string, -1, PREG_SPLIT_NO_EMPTY);
    }

 /**
 * Returns string with Cyrillic characters translated to Roman ones.
 *
 * @param string $string
 * @return string
 */
    function toRoman ($string)
    {
        $pl = array('ą','ć','ę','ł','ń','ó','ś','ź','ż','Ą','Ć','Ę','�?','Ń','Ó','Ś','Ź','Ż');
        $ro = array('a','c','e','l','n','o','s','z','z','A','C','E','L','N','O','S','Z','Z');

        return str_replace($pl, $ro, $string);
    }

 /**
 * Returns string as lowercase with whitespace removed.
 *
 * @param string $string
 * @return string
 */
    function toCompressed ($string)
    {
        $whitespace = array("\n", "    ", "\r", "\0", "\x0B", " ");
        return strtolower(str_replace($whitespace, '', $string));
    }

 /**
 * Returns a random password.
 *
 * @param integer $length Length of generated password
 * @param string $available_chars List of characters to use in password
 * @return string Generated password
 */
    function randomPassword ($length, $available_chars = 'ABDEFHKMNPRTWXYABDEFHKMNPRTWXY23456789')
    {
        $chars = preg_split('//', $available_chars, -1, PREG_SPLIT_NO_EMPTY);
        $char_count = count($chars);

        $out = '';
        for ($ii=0; $ii<$length; $ii++)
        {
            $out .= $chars[rand(1, $char_count)-1];
        }

        return $out;
    }

}

?>