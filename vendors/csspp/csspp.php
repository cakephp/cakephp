<?php
/* This PHP Script has been created by Florian Schmitz (floele at gmail dot com) 2005
 * It is licensed under the following Creative Commons License:
 * http://creativecommons.org/licenses/by-nc/2.0/
 *
 * Feel free to send me bugs and suggestions per e-mail. An online version of this parser you
 * can find here:
 * http://cdburnerxp.se/cssparse/css_optimiser.php
 * 
 * This parser will be published with examples/explanations as soon as it is finished (ver. 1.0).
 *
 * This class represents a CSS parser which reads CSS code and saves it in an array.
 * In opposite to most other CSS parsers, it does not use regular expressions and
 * thus has full CSS2 support and a higher reliability.
 *
 * Additional to that it applies some optimisations to the CSS code which can be controlled
 * using the variables below.
 * 
 * Changelog:
 *
 * v.0.96
 *   - added Tantek-Hack protection
 *   - fixed bug in compress_numbers()
 *
 * v.0.95:
 *   - improved speed and cleaned up code
 *   - added hex code -> color name conversion
 *   - fixed bug: uppercase color names not recognised
 *   - fixed bug: line-height:1.0 not optimised (line-height:1)
 *   - removed value seperation on "," which was added in order to work with the CSS3 background-property. This will be done in another way.
 *   - fixed bug: optimise2 was dependent of optimise
 */

class csspp {
// Data is saved here
var $css = array();
var $charset = '';
var $import = array();
var $input_css = '';
var $output_css = '';
var $template = array();

// Some handlers and other stuff
var $whitespace = array(' ',"\n","\t","\r","\x0B");
var $newlines = array("\n","\r","\x0B");
var $hex = array('a','b','c','d','e','f','\\',':');
var $units = array('in','cm','mm','pt','pc','px','em','%','ex');
var $shorthands = array(
'border-color' => array('border-top-color','border-right-color','border-bottom-color','border-left-color'),
'border-style' => array('border-top-style','border-right-style','border-bottom-style','border-left-style'),
'border-width' => array('border-top-width','border-right-width','border-bottom-width','border-left-width'),
'margin' => array('margin-top','margin-right','margin-bottom','margin-left'),
'padding' => array('padding-top','padding-right','padding-bottom','padding-left'),
'-moz-border-radius' => 0);
var $version = '0.96';
var $status = 'wfs';

/* wfs = wait for selector
 * is = in selector
 * wfp = wait for property
 * ip = in property
 * iv = in value
 * instr = in string (-> ",',( => ignore } and ; etc.)
 * ic = in comment (ignore everything)
 * at = in @-block
 */

// Settings
var $sort_s = FALSE;
var $sort_d = FALSE;
var $optimise = TRUE;
var $optimise2 = FALSE;
var $remove_bslash = FALSE;
var $compress_colors = TRUE;
var $lowercase_s = FALSE;
var $case_p = 'lower';
var $import_from_url = FALSE;
var $save_ie_hacks = TRUE;


// Temporary location for data
var $current_at = '';
var $current_selector = '';
var $current_property = '';
var $current_value = '';
var $cur_sub_value = '';
var $cur_sub_value_arr = array();
var $str_char = '';
var $str_from = '';
var $comment_from = '';

/* Load standard template */
function csspp()
{
	$this->template[0] = '<span class="at">'; //string before @rule
	$this->template[1] = '</span> <span class="format">{</span>'."\n"; //bracket after @-rule
	$this->template[2] = '<span class="selector">'; //string before selector
	$this->template[3] = '</span> <span class="format">{</span>'."\n"; //bracket after selector
	$this->template[4] = '<span class="property">'; //string before property
	$this->template[5] = '</span><span class="value">'; //string after property+before value
	$this->template[6] = '</span>'.'<span class="format">;</span>'."\n"; //string after value
	$this->template[7] = '<span class="format">}</span>'; //closing bracket - selector
	$this->template[8] = "\n\n"; //after closing bracket (conditional)
	$this->template[9] = "\n".'<span class="format">}</span>'."\n\n"; //closing bracket @-rule
	$this->template[10] = ''; //indent in @-rule
	$this->template[11] = '</span> <span class="format">{</span>'."\n"; //indent in @-rule before selector bracket
	$this->template[12] = ''; // after @-rule
}

function compress ($css)
{
	$this->parse($css);
	$this->print_code($this->css);
//	return strip_tags($this->output_css);
	return str_replace($this->newlines, '', strip_tags($this->output_css));
}


/* Extract URL from @import value and/or add missing http:// to URL */
function parseurl($string,$opt = 1)
{
	if(substr($string,0,4) == 'url(' && $opt == 1)
	{
		$string = substr($string,4);
	
		$string = substr($string,0,(strlen($string)-1)-strpos(strrev($string),')'));
	}
	if(($string{0} == '"' || $string{0} == '\'') && $opt == 1)
	{
		$string = substr($string,0,(strlen($string)-1)-strpos(strrev($string),$string{0}));
		$string = substr($string,1);
	}
	if(substr($string,0,7) != 'http://')
	{
		$string = 'http://'.$string;
	}
	return $string;	
}

/* Compresses shorthand values. Example: margin:1px 1px 1px 1px -> margin:1px */
function shorthand($value)
{
	$important = '';
	if(csspp::is_important($value))
	{
		$values = csspp::is_important($value,1);
		$important = ' !important';
	}
	else $values = $value;
	
	$values = explode(' ',$values);
	switch(count($values))
	{
		case 4:
		if($values[0] == $values[1] && $values[0] == $values[2] && $values[0] == $values[3])
		{
			return $values[0].$important;
		}
		elseif($values[1] == $values[3] && $values[0] == $values[2])
		{
			return $values[0].' '.$values[1].$important;
		}
		elseif($values[1] == $values[3])
		{
			return $values[0].' '.$values[1].' '.$values[2].$important;
		}
		else return $value;
		break;
		
		case 3:
		if($values[0] == $values[1] && $values[0] == $values[2])
		{
			return $values[0].$important;
		}
		elseif($values[0] == $values[2])
		{
			return $values[0].' '.$values[1].$important;
		}
		else return $value;
		break;
		
		case 2:
		if($values[0] == $values[1])
		{
			return $values[0].$important;
		}
		else return $value;
		break;
		
		default:
		return $value;
		break;
	}
	
}

/* Color compression function. Converts all rgb() values to #-values and uses the short-form if possible. Also replaces 4 color names by #-values. */
function cut_color($color)
{
	if(strtolower(substr($color,0,4)) == 'rgb(')
	{
		$color_tmp = substr($color,4,strlen($color)-5);
		$color_tmp = explode(',',$color_tmp);
		for ( $i = 0; $i < 3; $i++ )
		{
			$color_tmp[$i] = trim ($color_tmp[$i]);
			if(substr($color_tmp[$i],-1) == '%')
			{
				$color_tmp[$i] = round((255*$color_tmp[$i])/100);
			}
			if($color_tmp[$i]>255) $color_tmp[$i] = 255;
		}
		$color = '#';
		for ( $i=0; $i < 3; $i++ )
		{
			if($color_tmp[$i]<16) $color .= '0'.dechex($color_tmp[$i]);
				else $color .= dechex($color_tmp[$i]);
		}
	}
	if(strlen($color) == 7)
	{
		if($color{0} == '#' && $color{1} == $color{2} && $color{3} == $color{4} && $color{5} == $color{6})
		{
			$color = '#'.$color{1}.$color{3}.$color{5};
		}
	}
	switch(strtolower($color))
	{
		/* color name -> hex code */
		case 'black': return '#000';
		case 'fuchsia': return '#F0F';
		case 'white': return '#FFF';
		case 'yellow': return '#FF0';
				
		/* hex code -> color name */
		case '#800000': return 'maroon';
		case '#ffa500': return 'orange';
		case '#808000': return 'olive';
		case '#800080': return 'purple';
		case '#008000': return 'green';
		case '#000080': return 'navy';
		case '#008080': return 'teal';
		case '#c0c0c0': return 'silver';
		case '#808080': return 'gray';
		case '#f00': return 'red';
		case '#ff0000': return 'red';	
	}
	return $color;
}

/* Get compression ratio. */
function get_ratio()
{
	if(empty($this->output_css)) $this->print_code($this->css);
	return $ratio = round(((strlen($this->input_css))-(strlen(strip_tags(html_entity_decode($this->output_css)))))/(strlen($this->input_css)),3)*100;
}

/* Get difference between the old and new code in bytes */
function get_diff()
{
	if(empty($this->output_css)) $this->print_code($this->css);
	$diff = (strlen(html_entity_decode(strip_tags($this->output_css))))-(strlen($this->input_css));
	if($diff > 0) return '+'.$diff;
	elseif($diff == 0) return '+-'.$diff;
	else return $diff;
}

/* Get the size of either input or output CSS in KB */
function size($loc = 'output')
{
	if($loc == 'output' && empty($this->output_css)) $this->print_code($this->css);
	if($loc == 'input') return (strlen($this->input_css)/1000);
	else return (strlen(html_entity_decode(strip_tags($this->output_css)))/1000);
}

/* Load a new template */
function load_template($content,$from_file=TRUE)
{
	if($from_file)
	{
		$content = strip_tags(file_get_contents($content),'<span>');
	}
	$content = str_replace("\r",'',$content); // Unify newlines (because the output also only uses \n)
	$template = explode('|',$content);

	for ( $i=0, $size = count($this->template); $i < $size; $i++ ) {
		$this->template[$i] = @$template[$i];
	}
}

/* Start parsing from URL */
function parse_from_url($url) {
	$content = @file_get_contents($url);
	$this->parse($content);
}

/* Parse CSS in $string. The code is saved as array in $this->css */
function parse($string) {
$this->input_css = $string;
for ( $i=0, $size = strlen($string); $i < $size; $i++ ) {
	switch($this->status)
	{
		case 'wfs':
		if($string{$i} == '/' && @$string{$i+1} == '*')
		{
			$this->status = 'ic';
			$this->comment_from = 'wfs';
		}
		elseif($string{$i} == '@')
		{
			if(strtolower(substr($string,$i+1,4)) == 'page' || strtolower(substr($string,$i+1,9)) == 'font-face')
			{
				$this->current_selector = '@';
				$this->status = 'is';
			}
			elseif(strtolower(substr($string,$i+1,7)) == 'charset')
			{
				$this->current_selector = '@charset';
				$i += 7;
				$this->status = 'iv';
			}
			elseif(strtolower(substr($string,$i+1,6)) == 'import')
			{
				$this->current_selector = '@import';
				$i += 6;
				$this->status = 'iv';
			}
			else
			{
				$this->current_at = '@';
				$this->status = 'at';
			}
		}
		elseif($string{$i} == '}')
		{
			$this->current_at = '';
		}
		elseif(!in_array($string{$i},$this->whitespace))
		{
			$this->import_from_url = FALSE;
			//print_r($this->import);
			$this->status = 'is';
			$this->current_selector = $string{$i};
		}
		break;
		
		case 'at';
		if($string{$i} == '/' && @$string{$i+1} == '*')
		{
			$this->status = 'ic';
			$this->comment_from = 'at';
		}
		elseif($string{$i} != '{')
		{
			if(($string{$i-1} != ' ' && $string{$i} == ' ') || $string{$i} != ' ')
			{
				$this->current_at .= $string{$i};
			}
		}
		elseif($string{$i} == '{')
		{
			$this->status = 'wfs';
		}
		break;
		
		case 'is';
		if($string{$i} == '/' && @$string{$i+1} == '*')
		{
			$this->status = 'ic';
			$this->comment_from = 'is';
		}
		elseif(($string{$i} == '"' || $string{$i} == "'") && !csspp::escaped($string,$i))
		{
			$this->current_selector .= $string{$i};
			$this->status = 'instr';
			$this->str_char = $string{$i};
			$this->str_from = 'is';
		}
		elseif($string{$i} != '{')
		{
			if($string{$i-1} == ',' && !in_array($string{$i},$this->whitespace) || $string{$i-1} != ',')
			{
				if( ( !in_array($string{$i-1},$this->whitespace) && in_array($string{$i},$this->whitespace) ) || !in_array($string{$i},$this->whitespace))
				{
					$this->current_selector .= $string{$i};
				}
			}
		}
		elseif($string{$i} == '{' && !csspp::escaped($string,$i))
		{
			$this->status = 'wfp';
		}
		elseif($string{$i} == '{' && csspp::escaped($string,$i))
		{
			$this->current_selector .= $string{$i};
		}
		break;

		case 'wfp':
		if($string{$i} == '/' && @$string{$i+1} == '*')
		{
			$this->status = 'ic';
			$this->comment_from = 'wfp';
		}
		elseif($string{$i} == '}')
		{
			$this->status = 'wfs';
			$this->current_selector = '';
		}
		elseif(!in_array($string{$i},$this->whitespace))
		{
			$this->current_property .= $string{$i};
			$this->status = 'ip';
		}
		break;
		
		case 'ip':
		if(!in_array($string{$i},$this->whitespace) && $string{$i} != ':')
		{
			if($string{$i} != '\\' || $this->remove_bslash == FALSE)
			{
				$this->current_property .= $string{$i};
			}
			elseif($this->remove_bslash == TRUE)
			{
				if(($string{$i} == '\\' && in_array(@$string{$i+1},$this->hex)) || csspp::escaped($string,$i))
				{
					$this->current_property .= $string{$i};
				}
			}
		}
		if($string{$i} == ':')
		{
			$this->status = 'iv';
		}				
		break;
		
		case 'iv':
		if($string{$i} == '/' && @$string{$i+1} == '*')
		{
			$this->status = 'ic';
			$this->comment_from = 'iv';
		}
		elseif(($string{$i} == '"' || $string{$i} == "'" || $string{$i} == '(') && !csspp::escaped($string,$i))
		{
			if($this->current_selector != '@charset')
			{
				$this->cur_sub_value .= $string{$i};
			}
			if($string{$i} == '(') $this->str_char = ')'; else $this->str_char = $string{$i};
			$this->status = 'instr';
			$this->str_from = 'iv';
		}
		elseif($string{$i} != ';' && $string{$i} != '}')
		{
			$c = FALSE;
			if((($string{$i-1} != ' ') && $string{$i} == ' ') || $string{$i} != ' ')
			{
				$c = TRUE;
				$this->cur_sub_value .= $string{$i};
			}
			if(in_array($string{$i},$this->whitespace) && $c)
			{
				if(trim($this->cur_sub_value) != '')
				{
					if($this->compress_colors)
					{
						$this->cur_sub_value = csspp::cut_color($this->cur_sub_value);
					}
					$this->cur_sub_value = csspp::compress_numbers($this->cur_sub_value);
					$this->cur_sub_value_arr[] = trim($this->cur_sub_value);
				}
				$this->cur_sub_value = '';
			}
		}
		elseif($string{$i} == ';')
		{
			if($this->current_selector == '@charset')
			{
				$this->status = 'wfs';
				$this->charset = $this->cur_sub_value;
				$this->cur_sub_value = '';
				$this->current_selector = '';
			}
			elseif($this->current_selector == '@import')
			{
				$this->cur_sub_value_arr[] = trim($this->cur_sub_value);
				$this->status = 'wfs';
				$this->import[] = implode(' ',$this->cur_sub_value_arr);
				$this->cur_sub_value_arr = array();
				$this->cur_sub_value = '';
				$this->current_selector = '';
			}
			else
			{
				$this->status = 'wfp';
			}
		}	
		if($string{$i} == '}' || $string{$i} == ';' && !empty($this->current_selector))
		{
			if($this->current_at == '')
			{
				$this->current_at = 'standard';
			}
			// case settings
			if($this->lowercase_s)
			{
				$this->current_selector = strtolower($this->current_selector);
			}
			$this->current_property = strtolower($this->current_property);
			
			if(trim($this->cur_sub_value) != '')
			{
				if($this->compress_colors)
				{
					 $this->cur_sub_value = csspp::cut_color($this->cur_sub_value);
				}
				$this->cur_sub_value = csspp::compress_numbers($this->cur_sub_value);
				$this->cur_sub_value_arr[] = $this->cur_sub_value;
				$this->cur_sub_value = '';
			}
				
			$this->current_value = implode(' ',$this->cur_sub_value_arr);
		
			$this->current_selector = trim($this->current_selector);
			
			// optimise shorthand properties
			if(isset($this->shorthands[$this->current_property]))
			{
				$this->current_value = csspp::shorthand($this->current_value);
			}

			
			$this->current_at = trim(str_replace(' ,' , ',' , $this->current_at));
			if(isset($this->css[$this->current_at][$this->current_selector]) && ($this->save_ie_hacks || csspp::has_subkey($this->current_property,$this->css[$this->current_at][$this->current_selector])))
			{
				$this->css[$this->current_at][$this->current_selector] = $this->merge_css($this->css[$this->current_at][$this->current_selector],$this->current_property,$this->current_value);
			}
			else
			{
				$this->css[$this->current_at][$this->current_selector][][$this->current_property] = trim($this->current_value);
			}
			
			// Further Optimisation
			if(isset($this->shorthands[$this->current_property]) && $this->optimise2)
			{
				$temp = $this->dissolve_shorthands($this->current_property,$this->current_value);

				foreach($temp as $key => $value)
				{
					if(!csspp::has_subkey($key,$this->css[$this->current_at][$this->current_selector]))
					{
						$this->css[$this->current_at][$this->current_selector][][$key] = $value;
					}
					else
					{
						$this->css[$this->current_at][$this->current_selector] = $this->merge_css($this->css[$this->current_at][$this->current_selector],$key,$value,0);
					}
					$this->css[$this->current_at][$this->current_selector] = csspp::rm_subkey($this->current_property,$this->css[$this->current_at][$this->current_selector]);
				}
			}
						
			$this->current_property = '';
			$this->cur_sub_value_arr = array();
			$this->current_value = '';
		}
		if($string{$i} == '}')
		{
			$this->status = 'wfs';
			$this->current_selector = '';
		}
		break;
		
		case 'instr':
		if($string{$i} == $this->str_char && !csspp::escaped($string,$i))
		{
				if($this->str_from == 'iv')
				{
					$this->status = 'iv';
				}
				elseif($this->str_from == 'is')
				{
					$this->status = 'is';
				}
			if($this->current_selector == '@charset')
			{
				break;
			}
		}
		if($this->str_from == 'iv')
		{
			$this->cur_sub_value .= $string{$i};
		}
		elseif($this->str_from == 'is')
		{
			$this->current_selector .= $string{$i};
		}
		break;
		
		case 'ic':
		if($string{$i} == '/' && $string{$i-1} == '*' && !csspp::escaped($string,$i))
		{
			$this->status = $this->comment_from;
		}
		break;
	}
}
  
if($this->optimise)
{
	foreach($this->css as $key => $value)
	{
		for ($i=0;$i<count($this->css);$i++)
		{
			$this->css[$key] = csspp::merge_selectors($this->css[$key]);
		}
	}
}

if($this->optimise2)
{
	foreach($this->css as $key => $value)
	{
		foreach($value as $key1 => $value1)
		{
			$this->css[$key][$key1] = csspp::merge_shorthands($this->css[$key][$key1]);
		}
	}
}
  
if(empty($this->css)) return FALSE; else return TRUE;
}

/* Checks if a character is escaped (and returns TRUE if it is) */ 
function escaped($string,$pos) 
{
	if($string{$pos-1} != '\\')
	{
		return FALSE;
	}
	elseif(csspp::escaped($string,$pos-1))
	{
		return FALSE;
	}
	else
	{
		return TRUE;
	}
}

/* Checks if $array has the key $find (array[x][$find]). If gv=1, the value of the key is returned. */
function has_subkey($find,$array,$gv=0) {
	foreach($array as $key => $value)
	{
		if(isset($array[$key][$find]))
		{
			return ($gv == 0) ? TRUE : $array[$key][$find];
		}
	}
	return FALSE;
}
/* Removes the key $find in $array (array[x][$find]). */
function rm_subkey($find,$array) {
	$css = $array;
	foreach($array as $key => $value)
	{
		if(isset($css[$key][$find]))
		{
			unset($css[$key]);
		}
	}
	return $css;
}

/* Merges CSS Properties */ 
function merge_css($css,$property,$new_val,$ie=1)
{
	$return = $css;
	if($this->save_ie_hacks && $ie == 1)
	{
		$overwrite = FALSE;
		foreach($css as $key => $value)
		{
			if(isset($css[$key][$property]) && (!csspp::is_important($css[$key][$property]) && !($property == 'voice-family' && $css[$key][$property] == '"\"}\""') ) )
			{
				$overwrite = TRUE;
				$return[$key][$property] = trim($new_val);
			}
		}
		if(!$overwrite) $return[][$property] = trim($new_val);
	}
	else
	{
		foreach($css as $key => $value)
		{
			if(isset($css[$key][$property]))
			{
				if(csspp::is_important($css[$key][$property]) && strtolower(substr($new_val,-10,10)) == '!important')
				{
					unset($return[$key]);
					$return[][$property] = trim($new_val);
				}
				elseif(!csspp::is_important($css[$key][$property]))
				{
					unset($return[$key]);
					$return[][$property] = trim($new_val);
				}
			}
		}
	}
	return $return;
}

/* This function checks if the properties $needle also exist in other selectors $haystack and returs them as $keys */
function in_array_prop($needle, $haystack)
{
	$keys = array();
	foreach($haystack as $key => $value)
	{
		$i = 0;
		foreach($needle as $key1 => $value1)
		{
			if(in_array($needle[$key1],$haystack[$key]))
			$i++;
		}
		if($i == count($needle) && $i == count($haystack[$key])) $keys[] = $key;
	}
	if(empty($keys)) return FALSE; else return $keys;
}

/* Merges selectors with same properties. Example: a{color:red} b{color:red} -> a,b{color:red} */
function merge_selectors($array)
{
	foreach($array as $key => $value)
	{
		if(isset($array[$key]))
		{
			$newsel = '';
			$temp = $array;
			unset($temp[$key]);
			
			$result = csspp::in_array_prop($array[$key],$temp);
			if($result !== FALSE)
			{
				$newsel = $key;
				unset($array[$key]);
				foreach($result as $key1 => $value1)
				{
					unset($array[$value1]);
					$newsel .= ','.$value1;
				}
			$array[$newsel] = $value;
			}
		}
	}
	return $array;
}

/* Explodes a string as explode() does, however, not if $sep is escaped or within a string */
function explode_ws($sep,$string)
{
	$status = 'st';
	$to = '';
	
	$output = array();
	$num = 0;
	for($i = 0, $len = strlen($string);$i < $len; $i++)
	{
		switch($status)
		{
			case 'st':
			if($string{$i} == $sep && !csspp::escaped($string,$i))
			{
				++$num;
			}
			elseif($string{$i} == '"' || $string{$i} == '\'' && !csspp::escaped($string,$i))
			{
				$status = 'str';
				$to = $string{$i};
				(isset($output[$num])) ? $output[$num] .= $string{$i} : $output[$num] = $string{$i};
			}
			else
			{
				(isset($output[$num])) ? $output[$num] .= $string{$i} : $output[$num] = $string{$i};
			}
			break;
			
			case 'str':
			if($string{$i} == $to && !csspp::escaped($string,$i))
			{
				$status = 'st';
			}
			(isset($output[$num])) ? $output[$num] .= $string{$i} : $output[$num] = $string{$i};
			break;
		}
	}
	return (count($output) < 2) ? $output[0] : $output;
}

/* Checks if $value is !important. If gv=1, the property without !important is returned */
function is_important($value,$gv=0)
{
	if(strtolower(substr(str_replace($this->whitespace,'',$value),-10,10)) == '!important')
	{
		if($gv == 1)
		{
			$value = trim($value);
			$value = substr($value,0,-9);
			$value = trim($value);
			$value = substr($value,0,-1);
			$value = trim($value);
			return $value;
		}
		else return TRUE;
	}
	else return FALSE;
}

/* Returns the formatted CSS Code and saves it into $this->output_css */
function print_code($arr)
{
	$output = '';
	
	if (!empty ($this->charset))
	{
		$output .= $this->template[0].'@charset '.$this->template[5].'"'.$this->charset.'"'.$this->template[6].$this->template[12];
	}
	
	if (!empty ($this->import))
	{
		for ($i = 0, $size = count($this->import); $i < $size; $i ++) {
			$output .= $this->template[0].'@import '.$this->template[5].$this->import[$i].$this->template[6].$this->template[12];
		}
	}
	
	asort($arr);
	foreach($arr as $key => $value)
	{
		$ar = $arr[$key];
		if ($key != 'standard')
		{
			$output .= $this->template[0].htmlentities($key).$this->template[1];
		}
		for ($i = 0, $size = count($ar); $i < $size; $i ++)
		{
			if ($key != 'standard') $output .= $this->template[10];
			
			$num = array_keys($ar);
			
			if ($this->sort_s == TRUE) sort($num);
			
			$output .= ($num[$i]{0} != '@') ? $this->template[2].htmlentities($num[$i]) : $this->template[0].htmlentities($num[$i]);
			
			$output .= ($key != 'standard') ? $this->template[11] : $this->template[3];
			
			foreach($ar[$num[$i]] as $key1 => $value1) {
				if ($key != 'standard') $output .= $this->template[10];
				if($this->case_p == 'upper') $ar[$num[$i]][$key1] = array_change_key_case($ar[$num[$i]][$key1],CASE_UPPER);
				
				$num2 = array_keys($ar[$num[$i]][$key1]);
				if ($this->sort_d == TRUE) sort($num2);
				$output .= $this->template[4].htmlentities($num2[0]).':'.$this->template[5].htmlentities($ar[$num[$i]][$key1][$num2[0]]).$this->template[6];
			}
			if ($key != 'standard') $output .= $this->template[10];
			$output .= $this->template[7];
			if ( ($i == (count($ar) - 1) && $key != 'standard') || $key == 'standard') $output .= $this->template[8];
		}
		if ($key != 'standard') $output .= $this->template[9];
	}
	$output = trim($output);
	$this->output_css = $output;
	return $output;
}

/////////////////////////////////////////////////////////////////////////
//////////
//////////     Additional Optimisations
//////////
/////////////////////////////////////////////////////////////////////////

/* Compresses numbers (ie. 1.0 -> 1 or 1.100 -> 1.1 */
function compress_numbers($subvalue)
{
	if(strlen($subvalue) > 0 && is_numeric($subvalue{0}))
	{
		$temp = explode('/',$subvalue);
		for ( $l = 0, $size_3 = count($temp); $l < $size_3; $l++ )
		{
			if(strlen($temp[$l]) > 0 && floatval($temp[$l]) == 0 && is_numeric($temp[$l]{0}))
			{
				$temp[$l] = 0;
			}
			elseif(strlen($temp[$l]) > 0 && is_numeric($temp[$l]{0}))
			{
				$unit_found = FALSE;
				for( $m = 0, $size_4 = count($this->units); $m < $size_4; $m++ )
				{
					if(strpos(strtolower($temp[$l]),$this->units[$m]) !== FALSE)
					{
						$temp[$l] = floatval($temp[$l]).$this->units[$m];
						$unit_found = TRUE;
						break;
					}
				}
				if(!$unit_found) $temp[$l] = floatval($temp[$l]);
			}
		}
		return (count($temp) > 1) ? $temp[0].'/'.$temp[1] : $temp[0];
	}
	else return $subvalue;
}

/* Dissolves properties like padding:10px 10px 10px to padding-top:10px;padding-bottom:10px;... */
function dissolve_shorthands($property,$value)
{
	if(is_array($this->shorthands[$property])):
	
	$important = '';
	if(csspp::is_important($value))
	{
		$value = csspp::is_important($value,1);
		$important = ' !important';
	}
	$values = explode(' ',$value);


	$return = array();
	if(count($values) == 4)
	{
		for($i=0;$i<4;$i++)
		{
			$return[$this->shorthands[$property][$i]] = $values[$i].$important;
		}
	}
	elseif(count($values) == 3)
	{
		$return[$this->shorthands[$property][0]] = $values[0].$important;
		$return[$this->shorthands[$property][1]] = $values[1].$important;
		$return[$this->shorthands[$property][3]] = $values[1].$important;
		$return[$this->shorthands[$property][2]] = $values[2].$important;
	}
	elseif(count($values) == 2)
	{
		for($i=0;$i<4;$i++)
		{
			$return[$this->shorthands[$property][$i]] = (($i%2 != 0)) ? $values[1].$important : $values[0].$important;
		}
	}
	else
	{
		for($i=0;$i<4;$i++)
		{
			$return[$this->shorthands[$property][$i]] = $values[0].$important;
		}	
	}
	
	return $return;
	
	else:
	$return[$property] = $value;
	return $return;
	endif;
}

/* Merges Shorthand properties again, the opposite of dissolve_shorthands() */
function merge_shorthands($array)
{
	$return = $array;
	
	foreach($this->shorthands as $key => $value)
	{
		if(csspp::has_subkey($value[0],$array) && csspp::has_subkey($value[1],$array)
		&& csspp::has_subkey($value[2],$array) && csspp::has_subkey($value[3],$array))
		{
			$return[][$key] = '';
			end($return);
			$num = key($return);
			
			$important = '';
			for($i=0;$i<4;$i++)
			{
				$val = csspp::has_subkey($value[$i],$array,1);
				if(csspp::is_important($val))
				{
					$important = '!important';
					$return[$num][$key] .= substr(str_replace($this->whitespace,'',$val),0,strlen(str_replace($this->whitespace,'',$val))-10).' ';
				}
				else
				{
					$return[$num][$key] .= $val.' ';
				}
				$return = csspp::rm_subkey($value[$i],$return);
			}
			$return[$num][$key] = csspp::shorthand(trim($return[$num][$key].$important));		
		}
	}
	return $return;
}

}
?>