<?php
/* SVN FILE: $Id$ */
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+FB00 through U+FB4F
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
$config['fb00_fb4f'][] = array('upper' => 64256, 'status' => 'F', 'lower' => array(102, 102)); /* LATIN SMALL LIGATURE FF */
$config['fb00_fb4f'][] = array('upper' => 64257, 'status' => 'F', 'lower' => array(102, 105)); /* LATIN SMALL LIGATURE FI */
$config['fb00_fb4f'][] = array('upper' => 64258, 'status' => 'F', 'lower' => array(102, 108)); /* LATIN SMALL LIGATURE FL */
$config['fb00_fb4f'][] = array('upper' => 64259, 'status' => 'F', 'lower' => array(102, 102, 105)); /* LATIN SMALL LIGATURE FFI */
$config['fb00_fb4f'][] = array('upper' => 64260, 'status' => 'F', 'lower' => array(102, 102, 108)); /* LATIN SMALL LIGATURE FFL */
$config['fb00_fb4f'][] = array('upper' => 64261, 'status' => 'F', 'lower' => array(115, 116)); /* LATIN SMALL LIGATURE LONG S T */
$config['fb00_fb4f'][] = array('upper' => 64262, 'status' => 'F', 'lower' => array(115, 116)); /* LATIN SMALL LIGATURE ST */
$config['fb00_fb4f'][] = array('upper' => 64275, 'status' => 'F', 'lower' => array(1396, 1398)); /* ARMENIAN SMALL LIGATURE MEN NOW */
$config['fb00_fb4f'][] = array('upper' => 64276, 'status' => 'F', 'lower' => array(1396, 1381)); /* ARMENIAN SMALL LIGATURE MEN ECH */
$config['fb00_fb4f'][] = array('upper' => 64277, 'status' => 'F', 'lower' => array(1396, 1387)); /* ARMENIAN SMALL LIGATURE MEN INI */
$config['fb00_fb4f'][] = array('upper' => 64278, 'status' => 'F', 'lower' => array(1406, 1398)); /* ARMENIAN SMALL LIGATURE VEW NOW */
$config['fb00_fb4f'][] = array('upper' => 64279, 'status' => 'F', 'lower' => array(1396, 1389)); /* ARMENIAN SMALL LIGATURE MEN XEH */
?>