<?php
/* SVN FILE: $Id$ */

/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+0530 through U+058F
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
$config['0530_058f'][] = array('upper' => 1329, 'status' => 'C', 'lower' => array(1377)); /* ARMENIAN CAPITAL LETTER AYB */
$config['0530_058f'][] = array('upper' => 1330, 'status' => 'C', 'lower' => array(1378)); /* ARMENIAN CAPITAL LETTER BEN */
$config['0530_058f'][] = array('upper' => 1331, 'status' => 'C', 'lower' => array(1379)); /* ARMENIAN CAPITAL LETTER GIM */
$config['0530_058f'][] = array('upper' => 1332, 'status' => 'C', 'lower' => array(1380)); /* ARMENIAN CAPITAL LETTER DA */
$config['0530_058f'][] = array('upper' => 1333, 'status' => 'C', 'lower' => array(1381)); /* ARMENIAN CAPITAL LETTER ECH */
$config['0530_058f'][] = array('upper' => 1334, 'status' => 'C', 'lower' => array(1382)); /* ARMENIAN CAPITAL LETTER ZA */
$config['0530_058f'][] = array('upper' => 1335, 'status' => 'C', 'lower' => array(1383)); /* ARMENIAN CAPITAL LETTER EH */
$config['0530_058f'][] = array('upper' => 1336, 'status' => 'C', 'lower' => array(1384)); /* ARMENIAN CAPITAL LETTER ET */
$config['0530_058f'][] = array('upper' => 1337, 'status' => 'C', 'lower' => array(1385)); /* ARMENIAN CAPITAL LETTER TO */
$config['0530_058f'][] = array('upper' => 1338, 'status' => 'C', 'lower' => array(1386)); /* ARMENIAN CAPITAL LETTER ZHE */
$config['0530_058f'][] = array('upper' => 1339, 'status' => 'C', 'lower' => array(1387)); /* ARMENIAN CAPITAL LETTER INI */
$config['0530_058f'][] = array('upper' => 1340, 'status' => 'C', 'lower' => array(1388)); /* ARMENIAN CAPITAL LETTER LIWN */
$config['0530_058f'][] = array('upper' => 1341, 'status' => 'C', 'lower' => array(1389)); /* ARMENIAN CAPITAL LETTER XEH */
$config['0530_058f'][] = array('upper' => 1342, 'status' => 'C', 'lower' => array(1390)); /* ARMENIAN CAPITAL LETTER CA */
$config['0530_058f'][] = array('upper' => 1343, 'status' => 'C', 'lower' => array(1391)); /* ARMENIAN CAPITAL LETTER KEN */
$config['0530_058f'][] = array('upper' => 1344, 'status' => 'C', 'lower' => array(1392)); /* ARMENIAN CAPITAL LETTER HO */
$config['0530_058f'][] = array('upper' => 1345, 'status' => 'C', 'lower' => array(1393)); /* ARMENIAN CAPITAL LETTER JA */
$config['0530_058f'][] = array('upper' => 1346, 'status' => 'C', 'lower' => array(1394)); /* ARMENIAN CAPITAL LETTER GHAD */
$config['0530_058f'][] = array('upper' => 1347, 'status' => 'C', 'lower' => array(1395)); /* ARMENIAN CAPITAL LETTER CHEH */
$config['0530_058f'][] = array('upper' => 1348, 'status' => 'C', 'lower' => array(1396)); /* ARMENIAN CAPITAL LETTER MEN */
$config['0530_058f'][] = array('upper' => 1349, 'status' => 'C', 'lower' => array(1397)); /* ARMENIAN CAPITAL LETTER YI */
$config['0530_058f'][] = array('upper' => 1350, 'status' => 'C', 'lower' => array(1398)); /* ARMENIAN CAPITAL LETTER NOW */
$config['0530_058f'][] = array('upper' => 1351, 'status' => 'C', 'lower' => array(1399)); /* ARMENIAN CAPITAL LETTER SHA */
$config['0530_058f'][] = array('upper' => 1352, 'status' => 'C', 'lower' => array(1400)); /* ARMENIAN CAPITAL LETTER VO */
$config['0530_058f'][] = array('upper' => 1353, 'status' => 'C', 'lower' => array(1401)); /* ARMENIAN CAPITAL LETTER CHA */
$config['0530_058f'][] = array('upper' => 1354, 'status' => 'C', 'lower' => array(1402)); /* ARMENIAN CAPITAL LETTER PEH */
$config['0530_058f'][] = array('upper' => 1355, 'status' => 'C', 'lower' => array(1403)); /* ARMENIAN CAPITAL LETTER JHEH */
$config['0530_058f'][] = array('upper' => 1356, 'status' => 'C', 'lower' => array(1404)); /* ARMENIAN CAPITAL LETTER RA */
$config['0530_058f'][] = array('upper' => 1357, 'status' => 'C', 'lower' => array(1405)); /* ARMENIAN CAPITAL LETTER SEH */
$config['0530_058f'][] = array('upper' => 1358, 'status' => 'C', 'lower' => array(1406)); /* ARMENIAN CAPITAL LETTER VEW */
$config['0530_058f'][] = array('upper' => 1359, 'status' => 'C', 'lower' => array(1407)); /* ARMENIAN CAPITAL LETTER TIWN */
$config['0530_058f'][] = array('upper' => 1360, 'status' => 'C', 'lower' => array(1408)); /* ARMENIAN CAPITAL LETTER REH */
$config['0530_058f'][] = array('upper' => 1361, 'status' => 'C', 'lower' => array(1409)); /* ARMENIAN CAPITAL LETTER CO */
$config['0530_058f'][] = array('upper' => 1362, 'status' => 'C', 'lower' => array(1410)); /* ARMENIAN CAPITAL LETTER YIWN */
$config['0530_058f'][] = array('upper' => 1363, 'status' => 'C', 'lower' => array(1411)); /* ARMENIAN CAPITAL LETTER PIWR */
$config['0530_058f'][] = array('upper' => 1364, 'status' => 'C', 'lower' => array(1412)); /* ARMENIAN CAPITAL LETTER KEH */
$config['0530_058f'][] = array('upper' => 1365, 'status' => 'C', 'lower' => array(1413)); /* ARMENIAN CAPITAL LETTER OH */
$config['0530_058f'][] = array('upper' => 1366, 'status' => 'C', 'lower' => array(1414)); /* ARMENIAN CAPITAL LETTER FEH */
?>