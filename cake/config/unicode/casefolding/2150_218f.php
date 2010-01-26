<?php
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+2150 through U+218F
 *
 * @see http://www.unicode.org/Public/UNIDATA/UCD.html
 * @see http://www.unicode.org/Public/UNIDATA/CaseFolding.txt
 * @see http://www.unicode.org/reports/tr21/tr21-5.html
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.config.unicode.casefolding
 * @since         CakePHP(tm) v 1.2.0.5691
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
$config['2150_218f'][] = array('upper' => 8544, 'status' => 'C', 'lower' => array(8560)); /* ROMAN NUMERAL ONE */
$config['2150_218f'][] = array('upper' => 8545, 'status' => 'C', 'lower' => array(8561)); /* ROMAN NUMERAL TWO */
$config['2150_218f'][] = array('upper' => 8546, 'status' => 'C', 'lower' => array(8562)); /* ROMAN NUMERAL THREE */
$config['2150_218f'][] = array('upper' => 8547, 'status' => 'C', 'lower' => array(8563)); /* ROMAN NUMERAL FOUR */
$config['2150_218f'][] = array('upper' => 8548, 'status' => 'C', 'lower' => array(8564)); /* ROMAN NUMERAL FIVE */
$config['2150_218f'][] = array('upper' => 8549, 'status' => 'C', 'lower' => array(8565)); /* ROMAN NUMERAL SIX */
$config['2150_218f'][] = array('upper' => 8550, 'status' => 'C', 'lower' => array(8566)); /* ROMAN NUMERAL SEVEN */
$config['2150_218f'][] = array('upper' => 8551, 'status' => 'C', 'lower' => array(8567)); /* ROMAN NUMERAL EIGHT */
$config['2150_218f'][] = array('upper' => 8552, 'status' => 'C', 'lower' => array(8568)); /* ROMAN NUMERAL NINE */
$config['2150_218f'][] = array('upper' => 8553, 'status' => 'C', 'lower' => array(8569)); /* ROMAN NUMERAL TEN */
$config['2150_218f'][] = array('upper' => 8554, 'status' => 'C', 'lower' => array(8570)); /* ROMAN NUMERAL ELEVEN */
$config['2150_218f'][] = array('upper' => 8555, 'status' => 'C', 'lower' => array(8571)); /* ROMAN NUMERAL TWELVE */
$config['2150_218f'][] = array('upper' => 8556, 'status' => 'C', 'lower' => array(8572)); /* ROMAN NUMERAL FIFTY */
$config['2150_218f'][] = array('upper' => 8557, 'status' => 'C', 'lower' => array(8573)); /* ROMAN NUMERAL ONE HUNDRED */
$config['2150_218f'][] = array('upper' => 8558, 'status' => 'C', 'lower' => array(8574)); /* ROMAN NUMERAL FIVE HUNDRED */
$config['2150_218f'][] = array('upper' => 8559, 'status' => 'C', 'lower' => array(8575)); /* ROMAN NUMERAL ONE THOUSAND */
$config['2150_218f'][] = array('upper' => 8579, 'status' => 'C', 'lower' => array(8580)); /* ROMAN NUMERAL REVERSED ONE HUNDRED */
?>