<?php
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+FF00 through U+FFEF
 *
 * @see http://www.unicode.org/Public/UNIDATA/UCD.html
 * @see http://www.unicode.org/Public/UNIDATA/CaseFolding.txt
 * @see http://www.unicode.org/reports/tr21/tr21-5.html
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Config.unicode.casefolding
 * @since         CakePHP(tm) v 1.2.0.5691
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
$config['ff00_ffef'][] = array('upper' => 65313, 'status' => 'C', 'lower' => array(65345)); /* FULLWIDTH LATIN CAPITAL LETTER A */
$config['ff00_ffef'][] = array('upper' => 65314, 'status' => 'C', 'lower' => array(65346)); /* FULLWIDTH LATIN CAPITAL LETTER B */
$config['ff00_ffef'][] = array('upper' => 65315, 'status' => 'C', 'lower' => array(65347)); /* FULLWIDTH LATIN CAPITAL LETTER C */
$config['ff00_ffef'][] = array('upper' => 65316, 'status' => 'C', 'lower' => array(65348)); /* FULLWIDTH LATIN CAPITAL LETTER D */
$config['ff00_ffef'][] = array('upper' => 65317, 'status' => 'C', 'lower' => array(65349)); /* FULLWIDTH LATIN CAPITAL LETTER E */
$config['ff00_ffef'][] = array('upper' => 65318, 'status' => 'C', 'lower' => array(65350)); /* FULLWIDTH LATIN CAPITAL LETTER F */
$config['ff00_ffef'][] = array('upper' => 65319, 'status' => 'C', 'lower' => array(65351)); /* FULLWIDTH LATIN CAPITAL LETTER G */
$config['ff00_ffef'][] = array('upper' => 65320, 'status' => 'C', 'lower' => array(65352)); /* FULLWIDTH LATIN CAPITAL LETTER H */
$config['ff00_ffef'][] = array('upper' => 65321, 'status' => 'C', 'lower' => array(65353)); /* FULLWIDTH LATIN CAPITAL LETTER I */
$config['ff00_ffef'][] = array('upper' => 65322, 'status' => 'C', 'lower' => array(65354)); /* FULLWIDTH LATIN CAPITAL LETTER J */
$config['ff00_ffef'][] = array('upper' => 65323, 'status' => 'C', 'lower' => array(65355)); /* FULLWIDTH LATIN CAPITAL LETTER K */
$config['ff00_ffef'][] = array('upper' => 65324, 'status' => 'C', 'lower' => array(65356)); /* FULLWIDTH LATIN CAPITAL LETTER L */
$config['ff00_ffef'][] = array('upper' => 65325, 'status' => 'C', 'lower' => array(65357)); /* FULLWIDTH LATIN CAPITAL LETTER M */
$config['ff00_ffef'][] = array('upper' => 65326, 'status' => 'C', 'lower' => array(65358)); /* FULLWIDTH LATIN CAPITAL LETTER N */
$config['ff00_ffef'][] = array('upper' => 65327, 'status' => 'C', 'lower' => array(65359)); /* FULLWIDTH LATIN CAPITAL LETTER O */
$config['ff00_ffef'][] = array('upper' => 65328, 'status' => 'C', 'lower' => array(65360)); /* FULLWIDTH LATIN CAPITAL LETTER P */
$config['ff00_ffef'][] = array('upper' => 65329, 'status' => 'C', 'lower' => array(65361)); /* FULLWIDTH LATIN CAPITAL LETTER Q */
$config['ff00_ffef'][] = array('upper' => 65330, 'status' => 'C', 'lower' => array(65362)); /* FULLWIDTH LATIN CAPITAL LETTER R */
$config['ff00_ffef'][] = array('upper' => 65331, 'status' => 'C', 'lower' => array(65363)); /* FULLWIDTH LATIN CAPITAL LETTER S */
$config['ff00_ffef'][] = array('upper' => 65332, 'status' => 'C', 'lower' => array(65364)); /* FULLWIDTH LATIN CAPITAL LETTER T */
$config['ff00_ffef'][] = array('upper' => 65333, 'status' => 'C', 'lower' => array(65365)); /* FULLWIDTH LATIN CAPITAL LETTER U */
$config['ff00_ffef'][] = array('upper' => 65334, 'status' => 'C', 'lower' => array(65366)); /* FULLWIDTH LATIN CAPITAL LETTER V */
$config['ff00_ffef'][] = array('upper' => 65335, 'status' => 'C', 'lower' => array(65367)); /* FULLWIDTH LATIN CAPITAL LETTER W */
$config['ff00_ffef'][] = array('upper' => 65336, 'status' => 'C', 'lower' => array(65368)); /* FULLWIDTH LATIN CAPITAL LETTER X */
$config['ff00_ffef'][] = array('upper' => 65337, 'status' => 'C', 'lower' => array(65369)); /* FULLWIDTH LATIN CAPITAL LETTER Y */
$config['ff00_ffef'][] = array('upper' => 65338, 'status' => 'C', 'lower' => array(65370)); /* FULLWIDTH LATIN CAPITAL LETTER Z */
