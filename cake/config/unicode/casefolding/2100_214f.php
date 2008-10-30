<?php
/* SVN FILE: $Id$ */
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+2100 through U+214F
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
$config['2100_214f'][] = array('upper' => 8486, 'status' => 'C', 'lower' => array(969)); /* OHM SIGN */
$config['2100_214f'][] = array('upper' => 8490, 'status' => 'C', 'lower' => array(107)); /* KELVIN SIGN */
$config['2100_214f'][] = array('upper' => 8491, 'status' => 'C', 'lower' => array(229)); /* ANGSTROM SIGN */
$config['2100_214f'][] = array('upper' => 8498, 'status' => 'C', 'lower' => array(8526)); /* TURNED CAPITAL F */
?>