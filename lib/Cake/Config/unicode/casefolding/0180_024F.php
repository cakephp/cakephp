<?php
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+0180 through U+024F
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
$config['0180_024F'][] = array('upper' => 385, 'status' => 'C', 'lower' => array(595)); /* LATIN CAPITAL LETTER B WITH HOOK */
$config['0180_024F'][] = array('upper' => 386, 'status' => 'C', 'lower' => array(387)); /* LATIN CAPITAL LETTER B WITH TOPBAR */
$config['0180_024F'][] = array('upper' => 388, 'status' => 'C', 'lower' => array(389)); /* LATIN CAPITAL LETTER TONE SIX */
$config['0180_024F'][] = array('upper' => 390, 'status' => 'C', 'lower' => array(596)); /* LATIN CAPITAL LETTER OPEN O */
$config['0180_024F'][] = array('upper' => 391, 'status' => 'C', 'lower' => array(392)); /* LATIN CAPITAL LETTER C WITH HOOK */
$config['0180_024F'][] = array('upper' => 393, 'status' => 'C', 'lower' => array(598)); /* LATIN CAPITAL LETTER AFRICAN D */
$config['0180_024F'][] = array('upper' => 394, 'status' => 'C', 'lower' => array(599)); /* LATIN CAPITAL LETTER D WITH HOOK */
$config['0180_024F'][] = array('upper' => 395, 'status' => 'C', 'lower' => array(396)); /* LATIN CAPITAL LETTER D WITH TOPBAR */
$config['0180_024F'][] = array('upper' => 398, 'status' => 'C', 'lower' => array(477)); /* LATIN CAPITAL LETTER REVERSED E */
$config['0180_024F'][] = array('upper' => 399, 'status' => 'C', 'lower' => array(601)); /* LATIN CAPITAL LETTER SCHWA */
$config['0180_024F'][] = array('upper' => 400, 'status' => 'C', 'lower' => array(603)); /* LATIN CAPITAL LETTER OPEN E */
$config['0180_024F'][] = array('upper' => 401, 'status' => 'C', 'lower' => array(402)); /* LATIN CAPITAL LETTER F WITH HOOK */
$config['0180_024F'][] = array('upper' => 403, 'status' => 'C', 'lower' => array(608)); /* LATIN CAPITAL LETTER G WITH HOOK */
$config['0180_024F'][] = array('upper' => 404, 'status' => 'C', 'lower' => array(611)); /* LATIN CAPITAL LETTER GAMMA */
$config['0180_024F'][] = array('upper' => 406, 'status' => 'C', 'lower' => array(617)); /* LATIN CAPITAL LETTER IOTA */
$config['0180_024F'][] = array('upper' => 407, 'status' => 'C', 'lower' => array(616)); /* LATIN CAPITAL LETTER I WITH STROKE */
$config['0180_024F'][] = array('upper' => 408, 'status' => 'C', 'lower' => array(409)); /* LATIN CAPITAL LETTER K WITH HOOK */
$config['0180_024F'][] = array('upper' => 412, 'status' => 'C', 'lower' => array(623)); /* LATIN CAPITAL LETTER TURNED M */
$config['0180_024F'][] = array('upper' => 413, 'status' => 'C', 'lower' => array(626)); /* LATIN CAPITAL LETTER N WITH LEFT HOOK */
$config['0180_024F'][] = array('upper' => 415, 'status' => 'C', 'lower' => array(629)); /* LATIN CAPITAL LETTER O WITH MIDDLE TILDE */
$config['0180_024F'][] = array('upper' => 416, 'status' => 'C', 'lower' => array(417)); /* LATIN CAPITAL LETTER O WITH HORN */
$config['0180_024F'][] = array('upper' => 418, 'status' => 'C', 'lower' => array(419)); /* LATIN CAPITAL LETTER OI */
$config['0180_024F'][] = array('upper' => 420, 'status' => 'C', 'lower' => array(421)); /* LATIN CAPITAL LETTER P WITH HOOK */
$config['0180_024F'][] = array('upper' => 422, 'status' => 'C', 'lower' => array(640)); /* LATIN LETTER YR */
$config['0180_024F'][] = array('upper' => 423, 'status' => 'C', 'lower' => array(424)); /* LATIN CAPITAL LETTER TONE TWO */
$config['0180_024F'][] = array('upper' => 425, 'status' => 'C', 'lower' => array(643)); /* LATIN CAPITAL LETTER ESH */
$config['0180_024F'][] = array('upper' => 428, 'status' => 'C', 'lower' => array(429)); /* LATIN CAPITAL LETTER T WITH HOOK */
$config['0180_024F'][] = array('upper' => 430, 'status' => 'C', 'lower' => array(648)); /* LATIN CAPITAL LETTER T WITH RETROFLEX HOOK */
$config['0180_024F'][] = array('upper' => 431, 'status' => 'C', 'lower' => array(432)); /* LATIN CAPITAL LETTER U WITH HORN */
$config['0180_024F'][] = array('upper' => 433, 'status' => 'C', 'lower' => array(650)); /* LATIN CAPITAL LETTER UPSILON */
$config['0180_024F'][] = array('upper' => 434, 'status' => 'C', 'lower' => array(651)); /* LATIN CAPITAL LETTER V WITH HOOK */
$config['0180_024F'][] = array('upper' => 435, 'status' => 'C', 'lower' => array(436)); /* LATIN CAPITAL LETTER Y WITH HOOK */
$config['0180_024F'][] = array('upper' => 437, 'status' => 'C', 'lower' => array(438)); /* LATIN CAPITAL LETTER Z WITH STROKE */
$config['0180_024F'][] = array('upper' => 439, 'status' => 'C', 'lower' => array(658)); /* LATIN CAPITAL LETTER EZH */
$config['0180_024F'][] = array('upper' => 440, 'status' => 'C', 'lower' => array(441)); /* LATIN CAPITAL LETTER EZH REVERSED */
$config['0180_024F'][] = array('upper' => 444, 'status' => 'C', 'lower' => array(445)); /* LATIN CAPITAL LETTER TONE FIVE */
$config['0180_024F'][] = array('upper' => 452, 'status' => 'C', 'lower' => array(454)); /* LATIN CAPITAL LETTER DZ WITH CARON */
$config['0180_024F'][] = array('upper' => 453, 'status' => 'C', 'lower' => array(454)); /* LATIN CAPITAL LETTER D WITH SMALL LETTER Z WITH CARON */
$config['0180_024F'][] = array('upper' => 455, 'status' => 'C', 'lower' => array(457)); /* LATIN CAPITAL LETTER LJ */
$config['0180_024F'][] = array('upper' => 456, 'status' => 'C', 'lower' => array(457)); /* LATIN CAPITAL LETTER L WITH SMALL LETTER J */
$config['0180_024F'][] = array('upper' => 458, 'status' => 'C', 'lower' => array(460)); /* LATIN CAPITAL LETTER NJ */
$config['0180_024F'][] = array('upper' => 459, 'status' => 'C', 'lower' => array(460)); /* LATIN CAPITAL LETTER N WITH SMALL LETTER J */
$config['0180_024F'][] = array('upper' => 461, 'status' => 'C', 'lower' => array(462)); /* LATIN CAPITAL LETTER A WITH CARON */
$config['0180_024F'][] = array('upper' => 463, 'status' => 'C', 'lower' => array(464)); /* LATIN CAPITAL LETTER I WITH CARON */
$config['0180_024F'][] = array('upper' => 465, 'status' => 'C', 'lower' => array(466)); /* LATIN CAPITAL LETTER O WITH CARON */
$config['0180_024F'][] = array('upper' => 467, 'status' => 'C', 'lower' => array(468)); /* LATIN CAPITAL LETTER U WITH CARON */
$config['0180_024F'][] = array('upper' => 469, 'status' => 'C', 'lower' => array(470)); /* LATIN CAPITAL LETTER U WITH DIAERESIS AND MACRON */
$config['0180_024F'][] = array('upper' => 471, 'status' => 'C', 'lower' => array(472)); /* LATIN CAPITAL LETTER U WITH DIAERESIS AND ACUTE */
$config['0180_024F'][] = array('upper' => 473, 'status' => 'C', 'lower' => array(474)); /* LATIN CAPITAL LETTER U WITH DIAERESIS AND CARON */
$config['0180_024F'][] = array('upper' => 475, 'status' => 'C', 'lower' => array(476)); /* LATIN CAPITAL LETTER U WITH DIAERESIS AND GRAVE */
$config['0180_024F'][] = array('upper' => 478, 'status' => 'C', 'lower' => array(479)); /* LATIN CAPITAL LETTER A WITH DIAERESIS AND MACRON */
$config['0180_024F'][] = array('upper' => 480, 'status' => 'C', 'lower' => array(481)); /* LATIN CAPITAL LETTER A WITH DOT ABOVE AND MACRON */
$config['0180_024F'][] = array('upper' => 482, 'status' => 'C', 'lower' => array(483)); /* LATIN CAPITAL LETTER AE WITH MACRON */
$config['0180_024F'][] = array('upper' => 484, 'status' => 'C', 'lower' => array(485)); /* LATIN CAPITAL LETTER G WITH STROKE */
$config['0180_024F'][] = array('upper' => 486, 'status' => 'C', 'lower' => array(487)); /* LATIN CAPITAL LETTER G WITH CARON */
$config['0180_024F'][] = array('upper' => 488, 'status' => 'C', 'lower' => array(489)); /* LATIN CAPITAL LETTER K WITH CARON */
$config['0180_024F'][] = array('upper' => 490, 'status' => 'C', 'lower' => array(491)); /* LATIN CAPITAL LETTER O WITH OGONEK */
$config['0180_024F'][] = array('upper' => 492, 'status' => 'C', 'lower' => array(493)); /* LATIN CAPITAL LETTER O WITH OGONEK AND MACRON */
$config['0180_024F'][] = array('upper' => 494, 'status' => 'C', 'lower' => array(495)); /* LATIN CAPITAL LETTER EZH WITH CARON */
$config['0180_024F'][] = array('upper' => 496, 'status' => 'F', 'lower' => array(106, 780)); /* LATIN SMALL LETTER J WITH CARON */
$config['0180_024F'][] = array('upper' => 497, 'status' => 'C', 'lower' => array(499)); /* LATIN CAPITAL LETTER DZ */
$config['0180_024F'][] = array('upper' => 498, 'status' => 'C', 'lower' => array(499)); /* LATIN CAPITAL LETTER D WITH SMALL LETTER Z */
$config['0180_024F'][] = array('upper' => 500, 'status' => 'C', 'lower' => array(501)); /* LATIN CAPITAL LETTER G WITH ACUTE */
$config['0180_024F'][] = array('upper' => 502, 'status' => 'C', 'lower' => array(405)); /* LATIN CAPITAL LETTER HWAIR */
$config['0180_024F'][] = array('upper' => 503, 'status' => 'C', 'lower' => array(447)); /* LATIN CAPITAL LETTER WYNN */
$config['0180_024F'][] = array('upper' => 504, 'status' => 'C', 'lower' => array(505)); /* LATIN CAPITAL LETTER N WITH GRAVE */
$config['0180_024F'][] = array('upper' => 506, 'status' => 'C', 'lower' => array(507)); /* LATIN CAPITAL LETTER A WITH RING ABOVE AND ACUTE */
$config['0180_024F'][] = array('upper' => 508, 'status' => 'C', 'lower' => array(509)); /* LATIN CAPITAL LETTER AE WITH ACUTE */
$config['0180_024F'][] = array('upper' => 510, 'status' => 'C', 'lower' => array(511)); /* LATIN CAPITAL LETTER O WITH STROKE AND ACUTE */
$config['0180_024F'][] = array('upper' => 512, 'status' => 'C', 'lower' => array(513)); /* LATIN CAPITAL LETTER A WITH DOUBLE GRAVE */
$config['0180_024F'][] = array('upper' => 514, 'status' => 'C', 'lower' => array(515)); /* LATIN CAPITAL LETTER A WITH INVERTED BREVE */
$config['0180_024F'][] = array('upper' => 516, 'status' => 'C', 'lower' => array(517)); /* LATIN CAPITAL LETTER E WITH DOUBLE GRAVE */
$config['0180_024F'][] = array('upper' => 518, 'status' => 'C', 'lower' => array(519)); /* LATIN CAPITAL LETTER E WITH INVERTED BREVE */
$config['0180_024F'][] = array('upper' => 520, 'status' => 'C', 'lower' => array(521)); /* LATIN CAPITAL LETTER I WITH DOUBLE GRAVE */
$config['0180_024F'][] = array('upper' => 522, 'status' => 'C', 'lower' => array(523)); /* LATIN CAPITAL LETTER I WITH INVERTED BREVE */
$config['0180_024F'][] = array('upper' => 524, 'status' => 'C', 'lower' => array(525)); /* LATIN CAPITAL LETTER O WITH DOUBLE GRAVE */
$config['0180_024F'][] = array('upper' => 526, 'status' => 'C', 'lower' => array(527)); /* LATIN CAPITAL LETTER O WITH INVERTED BREVE */
$config['0180_024F'][] = array('upper' => 528, 'status' => 'C', 'lower' => array(529)); /* LATIN CAPITAL LETTER R WITH DOUBLE GRAVE */
$config['0180_024F'][] = array('upper' => 530, 'status' => 'C', 'lower' => array(531)); /* LATIN CAPITAL LETTER R WITH INVERTED BREVE */
$config['0180_024F'][] = array('upper' => 532, 'status' => 'C', 'lower' => array(533)); /* LATIN CAPITAL LETTER U WITH DOUBLE GRAVE */
$config['0180_024F'][] = array('upper' => 534, 'status' => 'C', 'lower' => array(535)); /* LATIN CAPITAL LETTER U WITH INVERTED BREVE */
$config['0180_024F'][] = array('upper' => 536, 'status' => 'C', 'lower' => array(537)); /* LATIN CAPITAL LETTER S WITH COMMA BELOW */
$config['0180_024F'][] = array('upper' => 538, 'status' => 'C', 'lower' => array(539)); /* LATIN CAPITAL LETTER T WITH COMMA BELOW */
$config['0180_024F'][] = array('upper' => 540, 'status' => 'C', 'lower' => array(541)); /* LATIN CAPITAL LETTER YOGH */
$config['0180_024F'][] = array('upper' => 542, 'status' => 'C', 'lower' => array(543)); /* LATIN CAPITAL LETTER H WITH CARON */
$config['0180_024F'][] = array('upper' => 544, 'status' => 'C', 'lower' => array(414)); /* LATIN CAPITAL LETTER N WITH LONG RIGHT LEG */
$config['0180_024F'][] = array('upper' => 546, 'status' => 'C', 'lower' => array(547)); /* LATIN CAPITAL LETTER OU */
$config['0180_024F'][] = array('upper' => 548, 'status' => 'C', 'lower' => array(549)); /* LATIN CAPITAL LETTER Z WITH HOOK */
$config['0180_024F'][] = array('upper' => 550, 'status' => 'C', 'lower' => array(551)); /* LATIN CAPITAL LETTER A WITH DOT ABOVE */
$config['0180_024F'][] = array('upper' => 552, 'status' => 'C', 'lower' => array(553)); /* LATIN CAPITAL LETTER E WITH CEDILLA */
$config['0180_024F'][] = array('upper' => 554, 'status' => 'C', 'lower' => array(555)); /* LATIN CAPITAL LETTER O WITH DIAERESIS AND MACRON */
$config['0180_024F'][] = array('upper' => 556, 'status' => 'C', 'lower' => array(557)); /* LATIN CAPITAL LETTER O WITH TILDE AND MACRON */
$config['0180_024F'][] = array('upper' => 558, 'status' => 'C', 'lower' => array(559)); /* LATIN CAPITAL LETTER O WITH DOT ABOVE */
$config['0180_024F'][] = array('upper' => 560, 'status' => 'C', 'lower' => array(561)); /* LATIN CAPITAL LETTER O WITH DOT ABOVE AND MACRON */
$config['0180_024F'][] = array('upper' => 562, 'status' => 'C', 'lower' => array(563)); /* LATIN CAPITAL LETTER Y WITH MACRON */
$config['0180_024F'][] = array('upper' => 570, 'status' => 'C', 'lower' => array(11365)); /* LATIN CAPITAL LETTER A WITH STROKE */
$config['0180_024F'][] = array('upper' => 571, 'status' => 'C', 'lower' => array(572)); /* LATIN CAPITAL LETTER C WITH STROKE */
$config['0180_024F'][] = array('upper' => 573, 'status' => 'C', 'lower' => array(410)); /* LATIN CAPITAL LETTER L WITH BAR */
$config['0180_024F'][] = array('upper' => 574, 'status' => 'C', 'lower' => array(11366)); /* LATIN CAPITAL LETTER T WITH DIAGONAL STROKE */
$config['0180_024F'][] = array('upper' => 577, 'status' => 'C', 'lower' => array(578)); /* LATIN CAPITAL LETTER GLOTTAL STOP */
$config['0180_024F'][] = array('upper' => 579, 'status' => 'C', 'lower' => array(384)); /* LATIN CAPITAL LETTER B WITH STROKE */
$config['0180_024F'][] = array('upper' => 580, 'status' => 'C', 'lower' => array(649)); /* LATIN CAPITAL LETTER U BAR */
$config['0180_024F'][] = array('upper' => 581, 'status' => 'C', 'lower' => array(652)); /* LATIN CAPITAL LETTER TURNED V */
$config['0180_024F'][] = array('upper' => 582, 'status' => 'C', 'lower' => array(583)); /* LATIN CAPITAL LETTER E WITH STROKE */
$config['0180_024F'][] = array('upper' => 584, 'status' => 'C', 'lower' => array(585)); /* LATIN CAPITAL LETTER J WITH STROKE */
$config['0180_024F'][] = array('upper' => 586, 'status' => 'C', 'lower' => array(587)); /* LATIN CAPITAL LETTER SMALL Q WITH HOOK TAIL */
$config['0180_024F'][] = array('upper' => 588, 'status' => 'C', 'lower' => array(589)); /* LATIN CAPITAL LETTER R WITH STROKE */
$config['0180_024F'][] = array('upper' => 590, 'status' => 'C', 'lower' => array(591)); /* LATIN CAPITAL LETTER Y WITH STROKE */
