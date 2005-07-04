<?php
//////////////////////////////////////////////////////////////////////////
// + $Id:$
// +------------------------------------------------------------------+ //
// + CakePHP : Rapid Development Framework <http://www.cakephp.org/>  + //
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
  * Purpose:
  *
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision:$
  * @modifiedby $LastChangedBy:$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */


/**
 * Enter description here...
 *
 * @static 
 */
class NeatString{
   
/**
 * Enter description here...
 *
 * @param unknown_type $string
 * @return unknown
 */
	function toArray ($string)
	{
		return preg_split('//', $string, -1, PREG_SPLIT_NO_EMPTY);
	}
	
/**
 * Enter description here...
 *
 * @param unknown_type $string
 * @return unknown
 */
	function toRoman ($string)
	{
		$pl = array('ą','ć','ę','ł','ń','ó','ś','ź','ż','Ą','Ć','Ę','�?','Ń','Ó','Ś','Ź','Ż');
		$ro = array('a','c','e','l','n','o','s','z','z','A','C','E','L','N','O','S','Z','Z');

		return str_replace($pl, $ro, $string);
	}

/**
 * Enter description here...
 *
 * @param unknown_type $string
 * @return unknown
 */
	function toCompressed ($string)
	{
		$whitespace = array("\n", "\t", "\r", "\0", "\x0B", " ");
		return strtolower(str_replace($whitespace, '', $string));
	}

/**
 * Enter description here...
 *
 * @param unknown_type $length
 * @param unknown_type $available_chars
 * @return unknown
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