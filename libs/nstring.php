<?php

// $Id$

class Nstring // static
{
	function toArray ($string)
	{
		return preg_split('//', $string, -1, PREG_SPLIT_NO_EMPTY);
	}
	
	function toRoman ($string)
	{
		$pl = array('ą','ć','ę','ł','ń','ó','ś','ź','ż','Ą','Ć','Ę','Ł','Ń','Ó','Ś','Ź','Ż');
		$ro = array('a','c','e','l','n','o','s','z','z','A','C','E','L','N','O','S','Z','Z');

		return str_replace($pl, $ro, $string);
	}

	function toCompressed ($string)
	{
		$whitespace = array("\n", "\t", "\r", "\0", "\x0B", " ");
		return strtolower(str_replace($whitespace, '', $string));
	}

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