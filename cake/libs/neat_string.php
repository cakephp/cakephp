<?php
/* SVN FILE: $Id$ */
/**
 * String handling methods.
 *
 * Random passwords, splitting strings into arrays, removing Cyrillic characters, stripping whitespace.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *			1785 E. Sahara Avenue, Suite 490-204
 *			Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * String handling methods.
 *
 * Random passwords, splitting strings into arrays, removing Cyrillic characters, stripping whitespace.
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class NeatString{
/**
 * Returns an array with each of the non-empty characters in $string as an element.
 *
 * @param string $string String to split
 * @return array An array where each element is a non empty character
 * @access public
 * @static
 */
	function toArray($string) {
		$split = preg_split('//', $string, -1, PREG_SPLIT_NO_EMPTY);
		return $split;
	}
/**
 * Returns string with Cyrillic characters translated to Roman ones.
 *
 * @param string $string String to translate
 * @return string String with cyrillic chracters translated
 * @access public
 * @static
 */
	function toRoman($string) {
		$pl = array('ą','ć','ę','ł','ń','ó','ś','ź','ż','Ą','Ć','Ę','�?','Ń','Ó','Ś','Ź','Ż');
		$ro = array('a','c','e','l','n','o','s','z','z','A','C','E','L','N','O','S','Z','Z');
		$replace = str_replace($pl, $ro, $string);
		return $replace;
	}
/**
 * Returns string as lowercase with whitespace removed.
 *
 * @param string $string String to convert
 * @return string Converted string
 * @access public
 * @static
 */
	function toCompressed($string) {
		$whitespace = array("\n", "	", "\r", "\0", "\x0B", " ");
		$replace = strtolower(str_replace($whitespace, '', $string));
		return $replace;
	}
/**
 * Returns a random password.
 *
 * @param integer $length Length of generated password
 * @param string $available_chars List of characters to use in password
 * @return string Generated password
 * @access public
 * @static
 */
	function randomPassword($length, $available_chars = 'ABDEFHKMNPRTWXYABDEFHKMNPRTWXY23456789') {
		$chars = preg_split('//', $available_chars, -1, PREG_SPLIT_NO_EMPTY);
		$char_count = count($chars);
		$out = '';
		for($ii = 0; $ii < $length; $ii++) {
			$out .= $chars[rand(1, $char_count)-1];
		}
		return $out;
	}
}
?>