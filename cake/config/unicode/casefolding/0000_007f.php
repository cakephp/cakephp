<?php
/* SVN FILE: $Id$ */
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+0000 through U+007F
 *
 * @see http://www.unicode.org/Public/UNIDATA/UCD.html
 * @see http://www.unicode.org/Public/UNIDATA/CaseFolding.txt
 * @see http://www.unicode.org/reports/tr21/tr21-5.html
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.config.unicode.casefolding
 * @since			CakePHP(tm) v 1.2.0.5691
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * The upper field is the decimal value of the upper case character
 *
 * The lower filed is an array of the decimal values that form the lower case version of a character.
 *
 *	The status field is:
 * C: common case folding, common mappings shared by both simple and full mappings.
 * F: full case folding, mappings that cause strings to grow in length. Multiple characters are separated by spaces.
 * S: simple case folding, mappings to single characters where different from F.
 * T: special case for uppercase I and dotted uppercase I
 *   - For non-Turkic languages, this mapping is normally not used.
 *   - For Turkic languages (tr, az), this mapping can be used instead of the normal mapping for these characters.
 *     Note that the Turkic mappings do not maintain canonical equivalence without additional processing.
 *     See the discussions of case mapping in the Unicode Standard for more information.
 */
$config['0000_007f'][] = array('upper' => 65, 'status' => 'C', 'lower' => array(97)); /* LATIN CAPITAL LETTER A */
$config['0000_007f'][] = array('upper' => 66, 'status' => 'C', 'lower' => array(98)); /* LATIN CAPITAL LETTER B */
$config['0000_007f'][] = array('upper' => 67, 'status' => 'C', 'lower' => array(99)); /* LATIN CAPITAL LETTER C */
$config['0000_007f'][] = array('upper' => 68, 'status' => 'C', 'lower' => array(100)); /* LATIN CAPITAL LETTER D */
$config['0000_007f'][] = array('upper' => 69, 'status' => 'C', 'lower' => array(101)); /* LATIN CAPITAL LETTER E */
$config['0000_007f'][] = array('upper' => 70, 'status' => 'C', 'lower' => array(102)); /* LATIN CAPITAL LETTER F */
$config['0000_007f'][] = array('upper' => 71, 'status' => 'C', 'lower' => array(103)); /* LATIN CAPITAL LETTER G */
$config['0000_007f'][] = array('upper' => 72, 'status' => 'C', 'lower' => array(104)); /* LATIN CAPITAL LETTER H */
$config['0000_007f'][] = array('upper' => 73, 'status' => 'C', 'lower' => array(105)); /* LATIN CAPITAL LETTER I */
$config['0000_007f'][] = array('upper' => 73, 'status' => 'T', 'lower' => array(305)); /* LATIN CAPITAL LETTER I */
$config['0000_007f'][] = array('upper' => 74, 'status' => 'C', 'lower' => array(106)); /* LATIN CAPITAL LETTER J */
$config['0000_007f'][] = array('upper' => 75, 'status' => 'C', 'lower' => array(107)); /* LATIN CAPITAL LETTER K */
$config['0000_007f'][] = array('upper' => 76, 'status' => 'C', 'lower' => array(108)); /* LATIN CAPITAL LETTER L */
$config['0000_007f'][] = array('upper' => 77, 'status' => 'C', 'lower' => array(109)); /* LATIN CAPITAL LETTER M */
$config['0000_007f'][] = array('upper' => 78, 'status' => 'C', 'lower' => array(110)); /* LATIN CAPITAL LETTER N */
$config['0000_007f'][] = array('upper' => 79, 'status' => 'C', 'lower' => array(111)); /* LATIN CAPITAL LETTER O */
$config['0000_007f'][] = array('upper' => 80, 'status' => 'C', 'lower' => array(112)); /* LATIN CAPITAL LETTER P */
$config['0000_007f'][] = array('upper' => 81, 'status' => 'C', 'lower' => array(113)); /* LATIN CAPITAL LETTER Q */
$config['0000_007f'][] = array('upper' => 82, 'status' => 'C', 'lower' => array(114)); /* LATIN CAPITAL LETTER R */
$config['0000_007f'][] = array('upper' => 83, 'status' => 'C', 'lower' => array(115)); /* LATIN CAPITAL LETTER S */
$config['0000_007f'][] = array('upper' => 84, 'status' => 'C', 'lower' => array(116)); /* LATIN CAPITAL LETTER T */
$config['0000_007f'][] = array('upper' => 85, 'status' => 'C', 'lower' => array(117)); /* LATIN CAPITAL LETTER U */
$config['0000_007f'][] = array('upper' => 86, 'status' => 'C', 'lower' => array(118)); /* LATIN CAPITAL LETTER V */
$config['0000_007f'][] = array('upper' => 87, 'status' => 'C', 'lower' => array(119)); /* LATIN CAPITAL LETTER W */
$config['0000_007f'][] = array('upper' => 88, 'status' => 'C', 'lower' => array(120)); /* LATIN CAPITAL LETTER X */
$config['0000_007f'][] = array('upper' => 89, 'status' => 'C', 'lower' => array(121)); /* LATIN CAPITAL LETTER Y */
$config['0000_007f'][] = array('upper' => 90, 'status' => 'C', 'lower' => array(122)); /* LATIN CAPITAL LETTER Z */
?>