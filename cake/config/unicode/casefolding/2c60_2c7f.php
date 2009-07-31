<?php
/* SVN FILE: $Id$ */

/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+2C60 through U+2C7F
 *
 * @see http://www.unicode.org/Public/UNIDATA/UCD.html
 * @see http://www.unicode.org/Public/UNIDATA/CaseFolding.txt
 * @see http://www.unicode.org/reports/tr21/tr21-5.html
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.config.unicode.casefolding
 * @since         CakePHP(tm) v 1.2.0.5691
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
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
$config['2c60_2c7f'][] = array('upper' => 11360, 'status' => 'C', 'lower' => array(11361)); /* LATIN CAPITAL LETTER L WITH DOUBLE BAR */
$config['2c60_2c7f'][] = array('upper' => 11362, 'status' => 'C', 'lower' => array(619)); /* LATIN CAPITAL LETTER L WITH MIDDLE TILDE */
$config['2c60_2c7f'][] = array('upper' => 11363, 'status' => 'C', 'lower' => array(7549)); /* LATIN CAPITAL LETTER P WITH STROKE */
$config['2c60_2c7f'][] = array('upper' => 11364, 'status' => 'C', 'lower' => array(637)); /* LATIN CAPITAL LETTER R WITH TAIL */
$config['2c60_2c7f'][] = array('upper' => 11367, 'status' => 'C', 'lower' => array(11368)); /* LATIN CAPITAL LETTER H WITH DESCENDER */
$config['2c60_2c7f'][] = array('upper' => 11369, 'status' => 'C', 'lower' => array(11370)); /* LATIN CAPITAL LETTER K WITH DESCENDER */
$config['2c60_2c7f'][] = array('upper' => 11371, 'status' => 'C', 'lower' => array(11372)); /* LATIN CAPITAL LETTER Z WITH DESCENDER */
$config['2c60_2c7f'][] = array('upper' => 11381, 'status' => 'C', 'lower' => array(11382)); /* LATIN CAPITAL LETTER HALF H */
?>