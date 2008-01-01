<?php
/* SVN FILE: $Id$ */
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+10400 through U+1044F
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
$config['10400_1044f'][] = array('upper' => 66560, 'status' => 'C', 'lower' => array(66600)); /* DESERET CAPITAL LETTER LONG I */
$config['10400_1044f'][] = array('upper' => 66561, 'status' => 'C', 'lower' => array(66601)); /* DESERET CAPITAL LETTER LONG E */
$config['10400_1044f'][] = array('upper' => 66562, 'status' => 'C', 'lower' => array(66602)); /* DESERET CAPITAL LETTER LONG A */
$config['10400_1044f'][] = array('upper' => 66563, 'status' => 'C', 'lower' => array(66603)); /* DESERET CAPITAL LETTER LONG AH */
$config['10400_1044f'][] = array('upper' => 66564, 'status' => 'C', 'lower' => array(66604)); /* DESERET CAPITAL LETTER LONG O */
$config['10400_1044f'][] = array('upper' => 66565, 'status' => 'C', 'lower' => array(66605)); /* DESERET CAPITAL LETTER LONG OO */
$config['10400_1044f'][] = array('upper' => 66566, 'status' => 'C', 'lower' => array(66606)); /* DESERET CAPITAL LETTER SHORT I */
$config['10400_1044f'][] = array('upper' => 66567, 'status' => 'C', 'lower' => array(66607)); /* DESERET CAPITAL LETTER SHORT E */
$config['10400_1044f'][] = array('upper' => 66568, 'status' => 'C', 'lower' => array(66608)); /* DESERET CAPITAL LETTER SHORT A */
$config['10400_1044f'][] = array('upper' => 66569, 'status' => 'C', 'lower' => array(66609)); /* DESERET CAPITAL LETTER SHORT AH */
$config['10400_1044f'][] = array('upper' => 66570, 'status' => 'C', 'lower' => array(66610)); /* DESERET CAPITAL LETTER SHORT O */
$config['10400_1044f'][] = array('upper' => 66571, 'status' => 'C', 'lower' => array(66611)); /* DESERET CAPITAL LETTER SHORT OO */
$config['10400_1044f'][] = array('upper' => 66572, 'status' => 'C', 'lower' => array(66612)); /* DESERET CAPITAL LETTER AY */
$config['10400_1044f'][] = array('upper' => 66573, 'status' => 'C', 'lower' => array(66613)); /* DESERET CAPITAL LETTER OW */
$config['10400_1044f'][] = array('upper' => 66574, 'status' => 'C', 'lower' => array(66614)); /* DESERET CAPITAL LETTER WU */
$config['10400_1044f'][] = array('upper' => 66575, 'status' => 'C', 'lower' => array(66615)); /* DESERET CAPITAL LETTER YEE */
$config['10400_1044f'][] = array('upper' => 66576, 'status' => 'C', 'lower' => array(66616)); /* DESERET CAPITAL LETTER H */
$config['10400_1044f'][] = array('upper' => 66577, 'status' => 'C', 'lower' => array(66617)); /* DESERET CAPITAL LETTER PEE */
$config['10400_1044f'][] = array('upper' => 66578, 'status' => 'C', 'lower' => array(66618)); /* DESERET CAPITAL LETTER BEE */
$config['10400_1044f'][] = array('upper' => 66579, 'status' => 'C', 'lower' => array(66619)); /* DESERET CAPITAL LETTER TEE */
$config['10400_1044f'][] = array('upper' => 66580, 'status' => 'C', 'lower' => array(66620)); /* DESERET CAPITAL LETTER DEE */
$config['10400_1044f'][] = array('upper' => 66581, 'status' => 'C', 'lower' => array(66621)); /* DESERET CAPITAL LETTER CHEE */
$config['10400_1044f'][] = array('upper' => 66582, 'status' => 'C', 'lower' => array(66622)); /* DESERET CAPITAL LETTER JEE */
$config['10400_1044f'][] = array('upper' => 66583, 'status' => 'C', 'lower' => array(66623)); /* DESERET CAPITAL LETTER KAY */
$config['10400_1044f'][] = array('upper' => 66584, 'status' => 'C', 'lower' => array(66624)); /* DESERET CAPITAL LETTER GAY */
$config['10400_1044f'][] = array('upper' => 66585, 'status' => 'C', 'lower' => array(66625)); /* DESERET CAPITAL LETTER EF */
$config['10400_1044f'][] = array('upper' => 66586, 'status' => 'C', 'lower' => array(66626)); /* DESERET CAPITAL LETTER VEE */
$config['10400_1044f'][] = array('upper' => 66587, 'status' => 'C', 'lower' => array(66627)); /* DESERET CAPITAL LETTER ETH */
$config['10400_1044f'][] = array('upper' => 66588, 'status' => 'C', 'lower' => array(66628)); /* DESERET CAPITAL LETTER THEE */
$config['10400_1044f'][] = array('upper' => 66589, 'status' => 'C', 'lower' => array(66629)); /* DESERET CAPITAL LETTER ES */
$config['10400_1044f'][] = array('upper' => 66590, 'status' => 'C', 'lower' => array(66630)); /* DESERET CAPITAL LETTER ZEE */
$config['10400_1044f'][] = array('upper' => 66591, 'status' => 'C', 'lower' => array(66631)); /* DESERET CAPITAL LETTER ESH */
$config['10400_1044f'][] = array('upper' => 66592, 'status' => 'C', 'lower' => array(66632)); /* DESERET CAPITAL LETTER ZHEE */
$config['10400_1044f'][] = array('upper' => 66593, 'status' => 'C', 'lower' => array(66633)); /* DESERET CAPITAL LETTER ER */
$config['10400_1044f'][] = array('upper' => 66594, 'status' => 'C', 'lower' => array(66634)); /* DESERET CAPITAL LETTER EL */
$config['10400_1044f'][] = array('upper' => 66595, 'status' => 'C', 'lower' => array(66635)); /* DESERET CAPITAL LETTER EM */
$config['10400_1044f'][] = array('upper' => 66596, 'status' => 'C', 'lower' => array(66636)); /* DESERET CAPITAL LETTER EN */
$config['10400_1044f'][] = array('upper' => 66597, 'status' => 'C', 'lower' => array(66637)); /* DESERET CAPITAL LETTER ENG */
$config['10400_1044f'][] = array('upper' => 66598, 'status' => 'C', 'lower' => array(66638)); /* DESERET CAPITAL LETTER OI */
$config['10400_1044f'][] = array('upper' => 66599, 'status' => 'C', 'lower' => array(66639)); /* DESERET CAPITAL LETTER EW */
?>