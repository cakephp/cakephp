<?php
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+2C00 through U+2C5F
 *
 * @see http://www.unicode.org/Public/UNIDATA/UCD.html
 * @see http://www.unicode.org/Public/UNIDATA/CaseFolding.txt
 * @see http://www.unicode.org/reports/tr21/tr21-5.html
 *
 * PHP 5
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
$config['2c00_2c5f'][] = array('upper' => 11264, 'status' => 'C', 'lower' => array(11312)); /* GLAGOLITIC CAPITAL LETTER AZU */
$config['2c00_2c5f'][] = array('upper' => 11265, 'status' => 'C', 'lower' => array(11313)); /* GLAGOLITIC CAPITAL LETTER BUKY */
$config['2c00_2c5f'][] = array('upper' => 11266, 'status' => 'C', 'lower' => array(11314)); /* GLAGOLITIC CAPITAL LETTER VEDE */
$config['2c00_2c5f'][] = array('upper' => 11267, 'status' => 'C', 'lower' => array(11315)); /* GLAGOLITIC CAPITAL LETTER GLAGOLI */
$config['2c00_2c5f'][] = array('upper' => 11268, 'status' => 'C', 'lower' => array(11316)); /* GLAGOLITIC CAPITAL LETTER DOBRO */
$config['2c00_2c5f'][] = array('upper' => 11269, 'status' => 'C', 'lower' => array(11317)); /* GLAGOLITIC CAPITAL LETTER YESTU */
$config['2c00_2c5f'][] = array('upper' => 11270, 'status' => 'C', 'lower' => array(11318)); /* GLAGOLITIC CAPITAL LETTER ZHIVETE */
$config['2c00_2c5f'][] = array('upper' => 11271, 'status' => 'C', 'lower' => array(11319)); /* GLAGOLITIC CAPITAL LETTER DZELO */
$config['2c00_2c5f'][] = array('upper' => 11272, 'status' => 'C', 'lower' => array(11320)); /* GLAGOLITIC CAPITAL LETTER ZEMLJA */
$config['2c00_2c5f'][] = array('upper' => 11273, 'status' => 'C', 'lower' => array(11321)); /* GLAGOLITIC CAPITAL LETTER IZHE */
$config['2c00_2c5f'][] = array('upper' => 11274, 'status' => 'C', 'lower' => array(11322)); /* GLAGOLITIC CAPITAL LETTER INITIAL IZHE */
$config['2c00_2c5f'][] = array('upper' => 11275, 'status' => 'C', 'lower' => array(11323)); /* GLAGOLITIC CAPITAL LETTER I */
$config['2c00_2c5f'][] = array('upper' => 11276, 'status' => 'C', 'lower' => array(11324)); /* GLAGOLITIC CAPITAL LETTER DJERVI */
$config['2c00_2c5f'][] = array('upper' => 11277, 'status' => 'C', 'lower' => array(11325)); /* GLAGOLITIC CAPITAL LETTER KAKO */
$config['2c00_2c5f'][] = array('upper' => 11278, 'status' => 'C', 'lower' => array(11326)); /* GLAGOLITIC CAPITAL LETTER LJUDIJE */
$config['2c00_2c5f'][] = array('upper' => 11279, 'status' => 'C', 'lower' => array(11327)); /* GLAGOLITIC CAPITAL LETTER MYSLITE */
$config['2c00_2c5f'][] = array('upper' => 11280, 'status' => 'C', 'lower' => array(11328)); /* GLAGOLITIC CAPITAL LETTER NASHI */
$config['2c00_2c5f'][] = array('upper' => 11281, 'status' => 'C', 'lower' => array(11329)); /* GLAGOLITIC CAPITAL LETTER ONU */
$config['2c00_2c5f'][] = array('upper' => 11282, 'status' => 'C', 'lower' => array(11330)); /* GLAGOLITIC CAPITAL LETTER POKOJI */
$config['2c00_2c5f'][] = array('upper' => 11283, 'status' => 'C', 'lower' => array(11331)); /* GLAGOLITIC CAPITAL LETTER RITSI */
$config['2c00_2c5f'][] = array('upper' => 11284, 'status' => 'C', 'lower' => array(11332)); /* GLAGOLITIC CAPITAL LETTER SLOVO */
$config['2c00_2c5f'][] = array('upper' => 11285, 'status' => 'C', 'lower' => array(11333)); /* GLAGOLITIC CAPITAL LETTER TVRIDO */
$config['2c00_2c5f'][] = array('upper' => 11286, 'status' => 'C', 'lower' => array(11334)); /* GLAGOLITIC CAPITAL LETTER UKU */
$config['2c00_2c5f'][] = array('upper' => 11287, 'status' => 'C', 'lower' => array(11335)); /* GLAGOLITIC CAPITAL LETTER FRITU */
$config['2c00_2c5f'][] = array('upper' => 11288, 'status' => 'C', 'lower' => array(11336)); /* GLAGOLITIC CAPITAL LETTER HERU */
$config['2c00_2c5f'][] = array('upper' => 11289, 'status' => 'C', 'lower' => array(11337)); /* GLAGOLITIC CAPITAL LETTER OTU */
$config['2c00_2c5f'][] = array('upper' => 11290, 'status' => 'C', 'lower' => array(11338)); /* GLAGOLITIC CAPITAL LETTER PE */
$config['2c00_2c5f'][] = array('upper' => 11291, 'status' => 'C', 'lower' => array(11339)); /* GLAGOLITIC CAPITAL LETTER SHTA */
$config['2c00_2c5f'][] = array('upper' => 11292, 'status' => 'C', 'lower' => array(11340)); /* GLAGOLITIC CAPITAL LETTER TSI */
$config['2c00_2c5f'][] = array('upper' => 11293, 'status' => 'C', 'lower' => array(11341)); /* GLAGOLITIC CAPITAL LETTER CHRIVI */
$config['2c00_2c5f'][] = array('upper' => 11294, 'status' => 'C', 'lower' => array(11342)); /* GLAGOLITIC CAPITAL LETTER SHA */
$config['2c00_2c5f'][] = array('upper' => 11295, 'status' => 'C', 'lower' => array(11343)); /* GLAGOLITIC CAPITAL LETTER YERU */
$config['2c00_2c5f'][] = array('upper' => 11296, 'status' => 'C', 'lower' => array(11344)); /* GLAGOLITIC CAPITAL LETTER YERI */
$config['2c00_2c5f'][] = array('upper' => 11297, 'status' => 'C', 'lower' => array(11345)); /* GLAGOLITIC CAPITAL LETTER YATI */
$config['2c00_2c5f'][] = array('upper' => 11298, 'status' => 'C', 'lower' => array(11346)); /* GLAGOLITIC CAPITAL LETTER SPIDERY HA */
$config['2c00_2c5f'][] = array('upper' => 11299, 'status' => 'C', 'lower' => array(11347)); /* GLAGOLITIC CAPITAL LETTER YU */
$config['2c00_2c5f'][] = array('upper' => 11300, 'status' => 'C', 'lower' => array(11348)); /* GLAGOLITIC CAPITAL LETTER SMALL YUS */
$config['2c00_2c5f'][] = array('upper' => 11301, 'status' => 'C', 'lower' => array(11349)); /* GLAGOLITIC CAPITAL LETTER SMALL YUS WITH TAIL */
$config['2c00_2c5f'][] = array('upper' => 11302, 'status' => 'C', 'lower' => array(11350)); /* GLAGOLITIC CAPITAL LETTER YO */
$config['2c00_2c5f'][] = array('upper' => 11303, 'status' => 'C', 'lower' => array(11351)); /* GLAGOLITIC CAPITAL LETTER IOTATED SMALL YUS */
$config['2c00_2c5f'][] = array('upper' => 11304, 'status' => 'C', 'lower' => array(11352)); /* GLAGOLITIC CAPITAL LETTER BIG YUS */
$config['2c00_2c5f'][] = array('upper' => 11305, 'status' => 'C', 'lower' => array(11353)); /* GLAGOLITIC CAPITAL LETTER IOTATED BIG YUS */
$config['2c00_2c5f'][] = array('upper' => 11306, 'status' => 'C', 'lower' => array(11354)); /* GLAGOLITIC CAPITAL LETTER FITA */
$config['2c00_2c5f'][] = array('upper' => 11307, 'status' => 'C', 'lower' => array(11355)); /* GLAGOLITIC CAPITAL LETTER IZHITSA */
$config['2c00_2c5f'][] = array('upper' => 11308, 'status' => 'C', 'lower' => array(11356)); /* GLAGOLITIC CAPITAL LETTER SHTAPIC */
$config['2c00_2c5f'][] = array('upper' => 11309, 'status' => 'C', 'lower' => array(11357)); /* GLAGOLITIC CAPITAL LETTER TROKUTASTI A */
$config['2c00_2c5f'][] = array('upper' => 11310, 'status' => 'C', 'lower' => array(11358)); /* GLAGOLITIC CAPITAL LETTER LATINATE MYSLITE */
