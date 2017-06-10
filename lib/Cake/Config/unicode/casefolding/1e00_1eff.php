<?php
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+1E00 through U+1EFF
 *
 * @see http://www.unicode.org/Public/UNIDATA/UCD.html
 * @see http://www.unicode.org/Public/UNIDATA/CaseFolding.txt
 * @see http://www.unicode.org/reports/tr21/tr21-5.html
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Config.unicode.casefolding
 * @since         CakePHP(tm) v 1.2.0.5691
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
$config['1e00_1eff'][] = array('upper' => 7680, 'status' => 'C', 'lower' => array(7681)); /* LATIN CAPITAL LETTER A WITH RING BELOW */
$config['1e00_1eff'][] = array('upper' => 7682, 'status' => 'C', 'lower' => array(7683)); /* LATIN CAPITAL LETTER B WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7684, 'status' => 'C', 'lower' => array(7685)); /* LATIN CAPITAL LETTER B WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7686, 'status' => 'C', 'lower' => array(7687)); /* LATIN CAPITAL LETTER B WITH LINE BELOW */
$config['1e00_1eff'][] = array('upper' => 7688, 'status' => 'C', 'lower' => array(7689)); /* LATIN CAPITAL LETTER C WITH CEDILLA AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7690, 'status' => 'C', 'lower' => array(7691)); /* LATIN CAPITAL LETTER D WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7692, 'status' => 'C', 'lower' => array(7693)); /* LATIN CAPITAL LETTER D WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7694, 'status' => 'C', 'lower' => array(7695)); /* LATIN CAPITAL LETTER D WITH LINE BELOW */
$config['1e00_1eff'][] = array('upper' => 7696, 'status' => 'C', 'lower' => array(7697)); /* LATIN CAPITAL LETTER D WITH CEDILLA */
$config['1e00_1eff'][] = array('upper' => 7698, 'status' => 'C', 'lower' => array(7699)); /* LATIN CAPITAL LETTER D WITH CIRCUMFLEX BELOW */
$config['1e00_1eff'][] = array('upper' => 7700, 'status' => 'C', 'lower' => array(7701)); /* LATIN CAPITAL LETTER E WITH MACRON AND GRAVE */
$config['1e00_1eff'][] = array('upper' => 7702, 'status' => 'C', 'lower' => array(7703)); /* LATIN CAPITAL LETTER E WITH MACRON AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7704, 'status' => 'C', 'lower' => array(7705)); /* LATIN CAPITAL LETTER E WITH CIRCUMFLEX BELOW */
$config['1e00_1eff'][] = array('upper' => 7706, 'status' => 'C', 'lower' => array(7707)); /* LATIN CAPITAL LETTER E WITH TILDE BELOW */
$config['1e00_1eff'][] = array('upper' => 7708, 'status' => 'C', 'lower' => array(7709)); /* LATIN CAPITAL LETTER E WITH CEDILLA AND BREVE */
$config['1e00_1eff'][] = array('upper' => 7710, 'status' => 'C', 'lower' => array(7711)); /* LATIN CAPITAL LETTER F WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7712, 'status' => 'C', 'lower' => array(7713)); /* LATIN CAPITAL LETTER G WITH MACRON */
$config['1e00_1eff'][] = array('upper' => 7714, 'status' => 'C', 'lower' => array(7715)); /* LATIN CAPITAL LETTER H WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7716, 'status' => 'C', 'lower' => array(7717)); /* LATIN CAPITAL LETTER H WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7718, 'status' => 'C', 'lower' => array(7719)); /* LATIN CAPITAL LETTER H WITH DIAERESIS */
$config['1e00_1eff'][] = array('upper' => 7720, 'status' => 'C', 'lower' => array(7721)); /* LATIN CAPITAL LETTER H WITH CEDILLA */
$config['1e00_1eff'][] = array('upper' => 7722, 'status' => 'C', 'lower' => array(7723)); /* LATIN CAPITAL LETTER H WITH BREVE BELOW */
$config['1e00_1eff'][] = array('upper' => 7724, 'status' => 'C', 'lower' => array(7725)); /* LATIN CAPITAL LETTER I WITH TILDE BELOW */
$config['1e00_1eff'][] = array('upper' => 7726, 'status' => 'C', 'lower' => array(7727)); /* LATIN CAPITAL LETTER I WITH DIAERESIS AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7728, 'status' => 'C', 'lower' => array(7729)); /* LATIN CAPITAL LETTER K WITH ACUTE */
$config['1e00_1eff'][] = array('upper' => 7730, 'status' => 'C', 'lower' => array(7731)); /* LATIN CAPITAL LETTER K WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7732, 'status' => 'C', 'lower' => array(7733)); /* LATIN CAPITAL LETTER K WITH LINE BELOW */
$config['1e00_1eff'][] = array('upper' => 7734, 'status' => 'C', 'lower' => array(7735)); /* LATIN CAPITAL LETTER L WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7736, 'status' => 'C', 'lower' => array(7737)); /* LATIN CAPITAL LETTER L WITH DOT BELOW AND MACRON */
$config['1e00_1eff'][] = array('upper' => 7738, 'status' => 'C', 'lower' => array(7739)); /* LATIN CAPITAL LETTER L WITH LINE BELOW */
$config['1e00_1eff'][] = array('upper' => 7740, 'status' => 'C', 'lower' => array(7741)); /* LATIN CAPITAL LETTER L WITH CIRCUMFLEX BELOW */
$config['1e00_1eff'][] = array('upper' => 7742, 'status' => 'C', 'lower' => array(7743)); /* LATIN CAPITAL LETTER M WITH ACUTE */
$config['1e00_1eff'][] = array('upper' => 7744, 'status' => 'C', 'lower' => array(7745)); /* LATIN CAPITAL LETTER M WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7746, 'status' => 'C', 'lower' => array(7747)); /* LATIN CAPITAL LETTER M WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7748, 'status' => 'C', 'lower' => array(7749)); /* LATIN CAPITAL LETTER N WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7750, 'status' => 'C', 'lower' => array(7751)); /* LATIN CAPITAL LETTER N WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7752, 'status' => 'C', 'lower' => array(7753)); /* LATIN CAPITAL LETTER N WITH LINE BELOW */
$config['1e00_1eff'][] = array('upper' => 7754, 'status' => 'C', 'lower' => array(7755)); /* LATIN CAPITAL LETTER N WITH CIRCUMFLEX BELOW */
$config['1e00_1eff'][] = array('upper' => 7756, 'status' => 'C', 'lower' => array(7757)); /* LATIN CAPITAL LETTER O WITH TILDE AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7758, 'status' => 'C', 'lower' => array(7759)); /* LATIN CAPITAL LETTER O WITH TILDE AND DIAERESIS */
$config['1e00_1eff'][] = array('upper' => 7760, 'status' => 'C', 'lower' => array(7761)); /* LATIN CAPITAL LETTER O WITH MACRON AND GRAVE */
$config['1e00_1eff'][] = array('upper' => 7762, 'status' => 'C', 'lower' => array(7763)); /* LATIN CAPITAL LETTER O WITH MACRON AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7764, 'status' => 'C', 'lower' => array(7765)); /* LATIN CAPITAL LETTER P WITH ACUTE */
$config['1e00_1eff'][] = array('upper' => 7766, 'status' => 'C', 'lower' => array(7767)); /* LATIN CAPITAL LETTER P WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7768, 'status' => 'C', 'lower' => array(7769)); /* LATIN CAPITAL LETTER R WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7770, 'status' => 'C', 'lower' => array(7771)); /* LATIN CAPITAL LETTER R WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7772, 'status' => 'C', 'lower' => array(7773)); /* LATIN CAPITAL LETTER R WITH DOT BELOW AND MACRON */
$config['1e00_1eff'][] = array('upper' => 7774, 'status' => 'C', 'lower' => array(7775)); /* LATIN CAPITAL LETTER R WITH LINE BELOW */
$config['1e00_1eff'][] = array('upper' => 7776, 'status' => 'C', 'lower' => array(7777)); /* LATIN CAPITAL LETTER S WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7778, 'status' => 'C', 'lower' => array(7779)); /* LATIN CAPITAL LETTER S WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7780, 'status' => 'C', 'lower' => array(7781)); /* LATIN CAPITAL LETTER S WITH ACUTE AND DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7782, 'status' => 'C', 'lower' => array(7783)); /* LATIN CAPITAL LETTER S WITH CARON AND DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7784, 'status' => 'C', 'lower' => array(7785)); /* LATIN CAPITAL LETTER S WITH DOT BELOW AND DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7786, 'status' => 'C', 'lower' => array(7787)); /* LATIN CAPITAL LETTER T WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7788, 'status' => 'C', 'lower' => array(7789)); /* LATIN CAPITAL LETTER T WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7790, 'status' => 'C', 'lower' => array(7791)); /* LATIN CAPITAL LETTER T WITH LINE BELOW */
$config['1e00_1eff'][] = array('upper' => 7792, 'status' => 'C', 'lower' => array(7793)); /* LATIN CAPITAL LETTER T WITH CIRCUMFLEX BELOW */
$config['1e00_1eff'][] = array('upper' => 7794, 'status' => 'C', 'lower' => array(7795)); /* LATIN CAPITAL LETTER U WITH DIAERESIS BELOW */
$config['1e00_1eff'][] = array('upper' => 7796, 'status' => 'C', 'lower' => array(7797)); /* LATIN CAPITAL LETTER U WITH TILDE BELOW */
$config['1e00_1eff'][] = array('upper' => 7798, 'status' => 'C', 'lower' => array(7799)); /* LATIN CAPITAL LETTER U WITH CIRCUMFLEX BELOW */
$config['1e00_1eff'][] = array('upper' => 7800, 'status' => 'C', 'lower' => array(7801)); /* LATIN CAPITAL LETTER U WITH TILDE AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7802, 'status' => 'C', 'lower' => array(7803)); /* LATIN CAPITAL LETTER U WITH MACRON AND DIAERESIS */
$config['1e00_1eff'][] = array('upper' => 7804, 'status' => 'C', 'lower' => array(7805)); /* LATIN CAPITAL LETTER V WITH TILDE */
$config['1e00_1eff'][] = array('upper' => 7806, 'status' => 'C', 'lower' => array(7807)); /* LATIN CAPITAL LETTER V WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7808, 'status' => 'C', 'lower' => array(7809)); /* LATIN CAPITAL LETTER W WITH GRAVE */
$config['1e00_1eff'][] = array('upper' => 7810, 'status' => 'C', 'lower' => array(7811)); /* LATIN CAPITAL LETTER W WITH ACUTE */
$config['1e00_1eff'][] = array('upper' => 7812, 'status' => 'C', 'lower' => array(7813)); /* LATIN CAPITAL LETTER W WITH DIAERESIS */
$config['1e00_1eff'][] = array('upper' => 7814, 'status' => 'C', 'lower' => array(7815)); /* LATIN CAPITAL LETTER W WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7816, 'status' => 'C', 'lower' => array(7817)); /* LATIN CAPITAL LETTER W WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7818, 'status' => 'C', 'lower' => array(7819)); /* LATIN CAPITAL LETTER X WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7820, 'status' => 'C', 'lower' => array(7821)); /* LATIN CAPITAL LETTER X WITH DIAERESIS */
$config['1e00_1eff'][] = array('upper' => 7822, 'status' => 'C', 'lower' => array(7823)); /* LATIN CAPITAL LETTER Y WITH DOT ABOVE */
$config['1e00_1eff'][] = array('upper' => 7824, 'status' => 'C', 'lower' => array(7825)); /* LATIN CAPITAL LETTER Z WITH CIRCUMFLEX */
$config['1e00_1eff'][] = array('upper' => 7826, 'status' => 'C', 'lower' => array(7827)); /* LATIN CAPITAL LETTER Z WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7828, 'status' => 'C', 'lower' => array(7829)); /* LATIN CAPITAL LETTER Z WITH LINE BELOW */

//$config['1e00_1eff'][] = array('upper' => 7830, 'status' => 'F', 'lower' => array(104, 817)); /* LATIN SMALL LETTER H WITH LINE BELOW */
//$config['1e00_1eff'][] = array('upper' => 7831, 'status' => 'F', 'lower' => array(116, 776)); /* LATIN SMALL LETTER T WITH DIAERESIS */
//$config['1e00_1eff'][] = array('upper' => 7832, 'status' => 'F', 'lower' => array(119, 778)); /* LATIN SMALL LETTER W WITH RING ABOVE */
//$config['1e00_1eff'][] = array('upper' => 7833, 'status' => 'F', 'lower' => array(121, 778)); /* LATIN SMALL LETTER Y WITH RING ABOVE */
//$config['1e00_1eff'][] = array('upper' => 7834, 'status' => 'F', 'lower' => array(97, 702)); /* LATIN SMALL LETTER A WITH RIGHT HALF RING */
//$config['1e00_1eff'][] = array('upper' => 7835, 'status' => 'C', 'lower' => array(7777)); /* LATIN SMALL LETTER LONG S WITH DOT ABOVE */

$config['1e00_1eff'][] = array('upper' => 7840, 'status' => 'C', 'lower' => array(7841)); /* LATIN CAPITAL LETTER A WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7842, 'status' => 'C', 'lower' => array(7843)); /* LATIN CAPITAL LETTER A WITH HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7844, 'status' => 'C', 'lower' => array(7845)); /* LATIN CAPITAL LETTER A WITH CIRCUMFLEX AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7846, 'status' => 'C', 'lower' => array(7847)); /* LATIN CAPITAL LETTER A WITH CIRCUMFLEX AND GRAVE */
$config['1e00_1eff'][] = array('upper' => 7848, 'status' => 'C', 'lower' => array(7849)); /* LATIN CAPITAL LETTER A WITH CIRCUMFLEX AND HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7850, 'status' => 'C', 'lower' => array(7851)); /* LATIN CAPITAL LETTER A WITH CIRCUMFLEX AND TILDE */
$config['1e00_1eff'][] = array('upper' => 7852, 'status' => 'C', 'lower' => array(7853)); /* LATIN CAPITAL LETTER A WITH CIRCUMFLEX AND DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7854, 'status' => 'C', 'lower' => array(7855)); /* LATIN CAPITAL LETTER A WITH BREVE AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7856, 'status' => 'C', 'lower' => array(7857)); /* LATIN CAPITAL LETTER A WITH BREVE AND GRAVE */
$config['1e00_1eff'][] = array('upper' => 7858, 'status' => 'C', 'lower' => array(7859)); /* LATIN CAPITAL LETTER A WITH BREVE AND HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7860, 'status' => 'C', 'lower' => array(7861)); /* LATIN CAPITAL LETTER A WITH BREVE AND TILDE */
$config['1e00_1eff'][] = array('upper' => 7862, 'status' => 'C', 'lower' => array(7863)); /* LATIN CAPITAL LETTER A WITH BREVE AND DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7864, 'status' => 'C', 'lower' => array(7865)); /* LATIN CAPITAL LETTER E WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7866, 'status' => 'C', 'lower' => array(7867)); /* LATIN CAPITAL LETTER E WITH HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7868, 'status' => 'C', 'lower' => array(7869)); /* LATIN CAPITAL LETTER E WITH TILDE */
$config['1e00_1eff'][] = array('upper' => 7870, 'status' => 'C', 'lower' => array(7871)); /* LATIN CAPITAL LETTER E WITH CIRCUMFLEX AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7872, 'status' => 'C', 'lower' => array(7873)); /* LATIN CAPITAL LETTER E WITH CIRCUMFLEX AND GRAVE */
$config['1e00_1eff'][] = array('upper' => 7874, 'status' => 'C', 'lower' => array(7875)); /* LATIN CAPITAL LETTER E WITH CIRCUMFLEX AND HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7876, 'status' => 'C', 'lower' => array(7877)); /* LATIN CAPITAL LETTER E WITH CIRCUMFLEX AND TILDE */
$config['1e00_1eff'][] = array('upper' => 7878, 'status' => 'C', 'lower' => array(7879)); /* LATIN CAPITAL LETTER E WITH CIRCUMFLEX AND DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7880, 'status' => 'C', 'lower' => array(7881)); /* LATIN CAPITAL LETTER I WITH HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7882, 'status' => 'C', 'lower' => array(7883)); /* LATIN CAPITAL LETTER I WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7884, 'status' => 'C', 'lower' => array(7885)); /* LATIN CAPITAL LETTER O WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7886, 'status' => 'C', 'lower' => array(7887)); /* LATIN CAPITAL LETTER O WITH HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7888, 'status' => 'C', 'lower' => array(7889)); /* LATIN CAPITAL LETTER O WITH CIRCUMFLEX AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7890, 'status' => 'C', 'lower' => array(7891)); /* LATIN CAPITAL LETTER O WITH CIRCUMFLEX AND GRAVE */
$config['1e00_1eff'][] = array('upper' => 7892, 'status' => 'C', 'lower' => array(7893)); /* LATIN CAPITAL LETTER O WITH CIRCUMFLEX AND HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7894, 'status' => 'C', 'lower' => array(7895)); /* LATIN CAPITAL LETTER O WITH CIRCUMFLEX AND TILDE */
$config['1e00_1eff'][] = array('upper' => 7896, 'status' => 'C', 'lower' => array(7897)); /* LATIN CAPITAL LETTER O WITH CIRCUMFLEX AND DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7898, 'status' => 'C', 'lower' => array(7899)); /* LATIN CAPITAL LETTER O WITH HORN AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7900, 'status' => 'C', 'lower' => array(7901)); /* LATIN CAPITAL LETTER O WITH HORN AND GRAVE */
$config['1e00_1eff'][] = array('upper' => 7902, 'status' => 'C', 'lower' => array(7903)); /* LATIN CAPITAL LETTER O WITH HORN AND HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7904, 'status' => 'C', 'lower' => array(7905)); /* LATIN CAPITAL LETTER O WITH HORN AND TILDE */
$config['1e00_1eff'][] = array('upper' => 7906, 'status' => 'C', 'lower' => array(7907)); /* LATIN CAPITAL LETTER O WITH HORN AND DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7908, 'status' => 'C', 'lower' => array(7909)); /* LATIN CAPITAL LETTER U WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7910, 'status' => 'C', 'lower' => array(7911)); /* LATIN CAPITAL LETTER U WITH HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7912, 'status' => 'C', 'lower' => array(7913)); /* LATIN CAPITAL LETTER U WITH HORN AND ACUTE */
$config['1e00_1eff'][] = array('upper' => 7914, 'status' => 'C', 'lower' => array(7915)); /* LATIN CAPITAL LETTER U WITH HORN AND GRAVE */
$config['1e00_1eff'][] = array('upper' => 7916, 'status' => 'C', 'lower' => array(7917)); /* LATIN CAPITAL LETTER U WITH HORN AND HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7918, 'status' => 'C', 'lower' => array(7919)); /* LATIN CAPITAL LETTER U WITH HORN AND TILDE */
$config['1e00_1eff'][] = array('upper' => 7920, 'status' => 'C', 'lower' => array(7921)); /* LATIN CAPITAL LETTER U WITH HORN AND DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7922, 'status' => 'C', 'lower' => array(7923)); /* LATIN CAPITAL LETTER Y WITH GRAVE */
$config['1e00_1eff'][] = array('upper' => 7924, 'status' => 'C', 'lower' => array(7925)); /* LATIN CAPITAL LETTER Y WITH DOT BELOW */
$config['1e00_1eff'][] = array('upper' => 7926, 'status' => 'C', 'lower' => array(7927)); /* LATIN CAPITAL LETTER Y WITH HOOK ABOVE */
$config['1e00_1eff'][] = array('upper' => 7928, 'status' => 'C', 'lower' => array(7929)); /* LATIN CAPITAL LETTER Y WITH TILDE */
