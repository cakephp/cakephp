<?php
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+0370 through U+03FF
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
$config['0370_03ff'][] = array('upper' => 902, 'status' => 'C', 'lower' => array(940)); /* GREEK CAPITAL LETTER ALPHA WITH TONOS */
$config['0370_03ff'][] = array('upper' => 904, 'status' => 'C', 'lower' => array(941)); /* GREEK CAPITAL LETTER EPSILON WITH TONOS */
$config['0370_03ff'][] = array('upper' => 905, 'status' => 'C', 'lower' => array(942)); /* GREEK CAPITAL LETTER ETA WITH TONOS */
$config['0370_03ff'][] = array('upper' => 906, 'status' => 'C', 'lower' => array(943)); /* GREEK CAPITAL LETTER IOTA WITH TONOS */
$config['0370_03ff'][] = array('upper' => 908, 'status' => 'C', 'lower' => array(972)); /* GREEK CAPITAL LETTER OMICRON WITH TONOS */
$config['0370_03ff'][] = array('upper' => 910, 'status' => 'C', 'lower' => array(973)); /* GREEK CAPITAL LETTER UPSILON WITH TONOS */
$config['0370_03ff'][] = array('upper' => 911, 'status' => 'C', 'lower' => array(974)); /* GREEK CAPITAL LETTER OMEGA WITH TONOS */
//$config['0370_03ff'][] = array('upper' => 912, 'status' => 'F', 'lower' => array(953, 776, 769)); /* GREEK SMALL LETTER IOTA WITH DIALYTIKA AND TONOS */
$config['0370_03ff'][] = array('upper' => 913, 'status' => 'C', 'lower' => array(945)); /* GREEK CAPITAL LETTER ALPHA */
$config['0370_03ff'][] = array('upper' => 914, 'status' => 'C', 'lower' => array(946)); /* GREEK CAPITAL LETTER BETA */
$config['0370_03ff'][] = array('upper' => 915, 'status' => 'C', 'lower' => array(947)); /* GREEK CAPITAL LETTER GAMMA */
$config['0370_03ff'][] = array('upper' => 916, 'status' => 'C', 'lower' => array(948)); /* GREEK CAPITAL LETTER DELTA */
$config['0370_03ff'][] = array('upper' => 917, 'status' => 'C', 'lower' => array(949)); /* GREEK CAPITAL LETTER EPSILON */
$config['0370_03ff'][] = array('upper' => 918, 'status' => 'C', 'lower' => array(950)); /* GREEK CAPITAL LETTER ZETA */
$config['0370_03ff'][] = array('upper' => 919, 'status' => 'C', 'lower' => array(951)); /* GREEK CAPITAL LETTER ETA */
$config['0370_03ff'][] = array('upper' => 920, 'status' => 'C', 'lower' => array(952)); /* GREEK CAPITAL LETTER THETA */
$config['0370_03ff'][] = array('upper' => 921, 'status' => 'C', 'lower' => array(953)); /* GREEK CAPITAL LETTER IOTA */
$config['0370_03ff'][] = array('upper' => 922, 'status' => 'C', 'lower' => array(954)); /* GREEK CAPITAL LETTER KAPPA */
$config['0370_03ff'][] = array('upper' => 923, 'status' => 'C', 'lower' => array(955)); /* GREEK CAPITAL LETTER LAMDA */
$config['0370_03ff'][] = array('upper' => 924, 'status' => 'C', 'lower' => array(956)); /* GREEK CAPITAL LETTER MU */
$config['0370_03ff'][] = array('upper' => 925, 'status' => 'C', 'lower' => array(957)); /* GREEK CAPITAL LETTER NU */
$config['0370_03ff'][] = array('upper' => 926, 'status' => 'C', 'lower' => array(958)); /* GREEK CAPITAL LETTER XI */
$config['0370_03ff'][] = array('upper' => 927, 'status' => 'C', 'lower' => array(959)); /* GREEK CAPITAL LETTER OMICRON */
$config['0370_03ff'][] = array('upper' => 928, 'status' => 'C', 'lower' => array(960)); /* GREEK CAPITAL LETTER PI */
$config['0370_03ff'][] = array('upper' => 929, 'status' => 'C', 'lower' => array(961)); /* GREEK CAPITAL LETTER RHO */
$config['0370_03ff'][] = array('upper' => 931, 'status' => 'C', 'lower' => array(963)); /* GREEK CAPITAL LETTER SIGMA */
$config['0370_03ff'][] = array('upper' => 932, 'status' => 'C', 'lower' => array(964)); /* GREEK CAPITAL LETTER TAU */
$config['0370_03ff'][] = array('upper' => 933, 'status' => 'C', 'lower' => array(965)); /* GREEK CAPITAL LETTER UPSILON */
$config['0370_03ff'][] = array('upper' => 934, 'status' => 'C', 'lower' => array(966)); /* GREEK CAPITAL LETTER PHI */
$config['0370_03ff'][] = array('upper' => 935, 'status' => 'C', 'lower' => array(967)); /* GREEK CAPITAL LETTER CHI */
$config['0370_03ff'][] = array('upper' => 936, 'status' => 'C', 'lower' => array(968)); /* GREEK CAPITAL LETTER PSI */
$config['0370_03ff'][] = array('upper' => 937, 'status' => 'C', 'lower' => array(969)); /* GREEK CAPITAL LETTER OMEGA */
$config['0370_03ff'][] = array('upper' => 938, 'status' => 'C', 'lower' => array(970)); /* GREEK CAPITAL LETTER IOTA WITH DIALYTIKA */
$config['0370_03ff'][] = array('upper' => 939, 'status' => 'C', 'lower' => array(971)); /* GREEK CAPITAL LETTER UPSILON WITH DIALYTIKA */
$config['0370_03ff'][] = array('upper' => 944, 'status' => 'F', 'lower' => array(965, 776, 769)); /* GREEK SMALL LETTER UPSILON WITH DIALYTIKA AND TONOS */
$config['0370_03ff'][] = array('upper' => 962, 'status' => 'C', 'lower' => array(963)); /* GREEK SMALL LETTER FINAL SIGMA */
$config['0370_03ff'][] = array('upper' => 976, 'status' => 'C', 'lower' => array(946)); /* GREEK BETA SYMBOL */
$config['0370_03ff'][] = array('upper' => 977, 'status' => 'C', 'lower' => array(952)); /* GREEK THETA SYMBOL */
$config['0370_03ff'][] = array('upper' => 981, 'status' => 'C', 'lower' => array(966)); /* GREEK PHI SYMBOL */
$config['0370_03ff'][] = array('upper' => 982, 'status' => 'C', 'lower' => array(960)); /* GREEK PI SYMBOL */
$config['0370_03ff'][] = array('upper' => 984, 'status' => 'C', 'lower' => array(985)); /* GREEK LETTER ARCHAIC KOPPA */
$config['0370_03ff'][] = array('upper' => 986, 'status' => 'C', 'lower' => array(987)); /* GREEK LETTER STIGMA */
$config['0370_03ff'][] = array('upper' => 988, 'status' => 'C', 'lower' => array(989)); /* GREEK LETTER DIGAMMA */
$config['0370_03ff'][] = array('upper' => 990, 'status' => 'C', 'lower' => array(991)); /* GREEK LETTER KOPPA */
$config['0370_03ff'][] = array('upper' => 992, 'status' => 'C', 'lower' => array(993)); /* GREEK LETTER SAMPI */
$config['0370_03ff'][] = array('upper' => 994, 'status' => 'C', 'lower' => array(995)); /* COPTIC CAPITAL LETTER SHEI */
$config['0370_03ff'][] = array('upper' => 996, 'status' => 'C', 'lower' => array(997)); /* COPTIC CAPITAL LETTER FEI */
$config['0370_03ff'][] = array('upper' => 998, 'status' => 'C', 'lower' => array(999)); /* COPTIC CAPITAL LETTER KHEI */
$config['0370_03ff'][] = array('upper' => 1000, 'status' => 'C', 'lower' => array(1001)); /* COPTIC CAPITAL LETTER HORI */
$config['0370_03ff'][] = array('upper' => 1002, 'status' => 'C', 'lower' => array(1003)); /* COPTIC CAPITAL LETTER GANGIA */
$config['0370_03ff'][] = array('upper' => 1004, 'status' => 'C', 'lower' => array(1005)); /* COPTIC CAPITAL LETTER SHIMA */
$config['0370_03ff'][] = array('upper' => 1006, 'status' => 'C', 'lower' => array(1007)); /* COPTIC CAPITAL LETTER DEI */
$config['0370_03ff'][] = array('upper' => 1008, 'status' => 'C', 'lower' => array(954)); /* GREEK KAPPA SYMBOL */
$config['0370_03ff'][] = array('upper' => 1009, 'status' => 'C', 'lower' => array(961)); /* GREEK RHO SYMBOL */
$config['0370_03ff'][] = array('upper' => 1012, 'status' => 'C', 'lower' => array(952)); /* GREEK CAPITAL THETA SYMBOL */
$config['0370_03ff'][] = array('upper' => 1013, 'status' => 'C', 'lower' => array(949)); /* GREEK LUNATE EPSILON SYMBOL */
$config['0370_03ff'][] = array('upper' => 1015, 'status' => 'C', 'lower' => array(1016)); /* GREEK CAPITAL LETTER SHO */
$config['0370_03ff'][] = array('upper' => 1017, 'status' => 'C', 'lower' => array(1010)); /* GREEK CAPITAL LUNATE SIGMA SYMBOL */
$config['0370_03ff'][] = array('upper' => 1018, 'status' => 'C', 'lower' => array(1019)); /* GREEK CAPITAL LETTER SAN */
$config['0370_03ff'][] = array('upper' => 1021, 'status' => 'C', 'lower' => array(891)); /* GREEK CAPITAL REVERSED LUNATE SIGMA SYMBOL */
$config['0370_03ff'][] = array('upper' => 1022, 'status' => 'C', 'lower' => array(892)); /* GREEK CAPITAL DOTTED LUNATE SIGMA SYMBOL */
$config['0370_03ff'][] = array('upper' => 1023, 'status' => 'C', 'lower' => array(893)); /* GREEK CAPITAL REVERSED DOTTED LUNATE SIGMA SYMBOL */
?>