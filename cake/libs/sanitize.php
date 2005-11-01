<?php
/* SVN FILE: $Id$ */

/**
 * Washes strings from unwanted noise.
 * 
 * Helpful methods to make unsafe strings usable.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, CakePHP Authors/Developers
 *
 * Author(s): Larry E. Masters aka PhpNut <nut@phpnut.com>
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
 * @since        CakePHP v 0.10.0.1076
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Data Sanitization.
 *
 * Removal of alpahnumeric characters, SQL-safe slash-added strings, HTML-friendly strings,
 * and all of the above on arrays.
 *
 * @package    cake
 * @subpackage cake.cake.libs
 * @since      CakePHP v 0.10.0.1076
 *
 */
class Sanitize
{

/**
 * Removes any non-alphanumeric characters.
 *
 * @param string $string
 * @return string
 */
	function paranoid($string)
	{
		return preg_replace( "/[^a-zA-Z0-9]/", "", $string );
	}

/**
 * Makes a string SQL-safe by adding slashes (if needed).
 *
 * @param string $string
 * @return string
 */
   function sql($string)
   {
      if (!ini_get('magic_quotes_gpc'))
      {
         $string = addslashes($string);
      }
      
      return $string;
   }
	
/**
 * Returns given string safe for display as HTML. Renders entities and converts newlines to <br/>.
 *
 * @param string $string
 * @param boolean $remove If true, the string is stripped of all HTML tags
 * @return string
 */
   function html($string, $remove = false)
   {
      if ($remove)
      {
         $string = strip_tags($string);
      }
      else
      {
         $patterns   =  array("/\&/", "/%/", "/</", "/>/", '/"/', "/'/", "/\(/", "/\)/", "/\+/", "/-/", "/\n/");
			$replacements 	= array("&amp;", "&#37;", "&lt;", "&gt;", "&quot;", "&#39;", "&#40;", "&#41;", "&#43;", "&#45;", "<br/>");
			$string = preg_replace($patterns, $replacements, $string);
      }

      return $string;
   }
	
/**
 * Recursively sanitizes given array of data for safe input.
 *
 * @param mixed $toClean
 * @return mixed
 */
   function cleanArray(&$toClean) 
   {
      return $this->cleanArrayR($toClean);
   }

/**
 * Private method used for recursion (see cleanArray()).
 *
 * @param array $toClean
 * @return array
 * @see cleanArray
 */
   function cleanArrayR(&$toClean) 
   {
      if (is_array($toClean)) 
      {
         while(list($k, $v) = each($toClean))
         {
            if ( is_array($toClean[$k]) ) 
            {
               $this->cleanArray($toClean[$k]);
            } 
            else 
            {
               $toClean[$k] = $this->cleanValue($v);
            }
         }
      }
      else 
      {
         return null;
      }
   }
	
/**
 * Do we really need to sanitize array keys? If so, we can use this code...

   function cleanKey($key)
   {
      if ($key == "") 
      {
         return "";
      }
      
      //URL decode and convert chars to HTML entities
      $key = htmlspecialchars(urldecode($key));
      //Remove ..
      $key = preg_replace( "/\.\./", "", $key );
      //Remove __FILE__, etc.
      $key = preg_replace( "/\_\_(.+?)\_\_/", "", $key );
      //Trim word chars, '.', '-', '_'
      $key = preg_replace( "/^([\w\.\-\_]+)$/", "$1", $key );
      
      return $key;
   }
 */
	
/**
 * Method used by cleanArray() to sanitize array nodes.
 *
 * @param string $val
 * @return string
 */
   function cleanValue($val) 
   {
      if ($val == "")
      {
         return "";
      }

      //Replace odd spaces with safe ones
      $val = str_replace(" ", " ", $val);
      $val = str_replace(chr(0xCA), "", $val);

      //Encode any HTML to entities (including \n --> <br/>)
      $val = $this->html($val);

      //Double-check special chars and remove carriage returns
      //For increased SQL security
      $val = preg_replace( "/\\\$/"	,"$"	,$val);
      $val = preg_replace( "/\r/"		,""		,$val);
      $val = str_replace ( "!"		,"!"	,$val);
      $val = str_replace ( "'"		, "'"	,$val);

      //Allow unicode (?)
      $val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val );

      //Add slashes for SQL
      $val = $this->sql($val);

      //Swap user-inputted backslashes (?)
      $val = preg_replace( "/\\\(?!&amp;#|\?#)/", "\\", $val );

      return $val;
   }
}
?>