<?php
/* SVN FILE: $Id$ */
/**
 * Case Folding Properties.
 *
 * Provides case mapping of Unicode characters for code points U+10A0 through U+10FF
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
$config['10a0_10ff'][] = array('upper' => 4256, 'status' => 'C', 'lower' => array(11520)); /* GEORGIAN CAPITAL LETTER AN */
$config['10a0_10ff'][] = array('upper' => 4257, 'status' => 'C', 'lower' => array(11521)); /* GEORGIAN CAPITAL LETTER BAN */
$config['10a0_10ff'][] = array('upper' => 4258, 'status' => 'C', 'lower' => array(11522)); /* GEORGIAN CAPITAL LETTER GAN */
$config['10a0_10ff'][] = array('upper' => 4259, 'status' => 'C', 'lower' => array(11523)); /* GEORGIAN CAPITAL LETTER DON */
$config['10a0_10ff'][] = array('upper' => 4260, 'status' => 'C', 'lower' => array(11524)); /* GEORGIAN CAPITAL LETTER EN */
$config['10a0_10ff'][] = array('upper' => 4261, 'status' => 'C', 'lower' => array(11525)); /* GEORGIAN CAPITAL LETTER VIN */
$config['10a0_10ff'][] = array('upper' => 4262, 'status' => 'C', 'lower' => array(11526)); /* GEORGIAN CAPITAL LETTER ZEN */
$config['10a0_10ff'][] = array('upper' => 4263, 'status' => 'C', 'lower' => array(11527)); /* GEORGIAN CAPITAL LETTER TAN */
$config['10a0_10ff'][] = array('upper' => 4264, 'status' => 'C', 'lower' => array(11528)); /* GEORGIAN CAPITAL LETTER IN */
$config['10a0_10ff'][] = array('upper' => 4265, 'status' => 'C', 'lower' => array(11529)); /* GEORGIAN CAPITAL LETTER KAN */
$config['10a0_10ff'][] = array('upper' => 4266, 'status' => 'C', 'lower' => array(11530)); /* GEORGIAN CAPITAL LETTER LAS */
$config['10a0_10ff'][] = array('upper' => 4267, 'status' => 'C', 'lower' => array(11531)); /* GEORGIAN CAPITAL LETTER MAN */
$config['10a0_10ff'][] = array('upper' => 4268, 'status' => 'C', 'lower' => array(11532)); /* GEORGIAN CAPITAL LETTER NAR */
$config['10a0_10ff'][] = array('upper' => 4269, 'status' => 'C', 'lower' => array(11533)); /* GEORGIAN CAPITAL LETTER ON */
$config['10a0_10ff'][] = array('upper' => 4270, 'status' => 'C', 'lower' => array(11534)); /* GEORGIAN CAPITAL LETTER PAR */
$config['10a0_10ff'][] = array('upper' => 4271, 'status' => 'C', 'lower' => array(11535)); /* GEORGIAN CAPITAL LETTER ZHAR */
$config['10a0_10ff'][] = array('upper' => 4272, 'status' => 'C', 'lower' => array(11536)); /* GEORGIAN CAPITAL LETTER RAE */
$config['10a0_10ff'][] = array('upper' => 4273, 'status' => 'C', 'lower' => array(11537)); /* GEORGIAN CAPITAL LETTER SAN */
$config['10a0_10ff'][] = array('upper' => 4274, 'status' => 'C', 'lower' => array(11538)); /* GEORGIAN CAPITAL LETTER TAR */
$config['10a0_10ff'][] = array('upper' => 4275, 'status' => 'C', 'lower' => array(11539)); /* GEORGIAN CAPITAL LETTER UN */
$config['10a0_10ff'][] = array('upper' => 4276, 'status' => 'C', 'lower' => array(11540)); /* GEORGIAN CAPITAL LETTER PHAR */
$config['10a0_10ff'][] = array('upper' => 4277, 'status' => 'C', 'lower' => array(11541)); /* GEORGIAN CAPITAL LETTER KHAR */
$config['10a0_10ff'][] = array('upper' => 4278, 'status' => 'C', 'lower' => array(11542)); /* GEORGIAN CAPITAL LETTER GHAN */
$config['10a0_10ff'][] = array('upper' => 4279, 'status' => 'C', 'lower' => array(11543)); /* GEORGIAN CAPITAL LETTER QAR */
$config['10a0_10ff'][] = array('upper' => 4280, 'status' => 'C', 'lower' => array(11544)); /* GEORGIAN CAPITAL LETTER SHIN */
$config['10a0_10ff'][] = array('upper' => 4281, 'status' => 'C', 'lower' => array(11545)); /* GEORGIAN CAPITAL LETTER CHIN */
$config['10a0_10ff'][] = array('upper' => 4282, 'status' => 'C', 'lower' => array(11546)); /* GEORGIAN CAPITAL LETTER CAN */
$config['10a0_10ff'][] = array('upper' => 4283, 'status' => 'C', 'lower' => array(11547)); /* GEORGIAN CAPITAL LETTER JIL */
$config['10a0_10ff'][] = array('upper' => 4284, 'status' => 'C', 'lower' => array(11548)); /* GEORGIAN CAPITAL LETTER CIL */
$config['10a0_10ff'][] = array('upper' => 4285, 'status' => 'C', 'lower' => array(11549)); /* GEORGIAN CAPITAL LETTER CHAR */
$config['10a0_10ff'][] = array('upper' => 4286, 'status' => 'C', 'lower' => array(11550)); /* GEORGIAN CAPITAL LETTER XAN */
$config['10a0_10ff'][] = array('upper' => 4287, 'status' => 'C', 'lower' => array(11551)); /* GEORGIAN CAPITAL LETTER JHAN */
$config['10a0_10ff'][] = array('upper' => 4288, 'status' => 'C', 'lower' => array(11552)); /* GEORGIAN CAPITAL LETTER HAE */
$config['10a0_10ff'][] = array('upper' => 4289, 'status' => 'C', 'lower' => array(11553)); /* GEORGIAN CAPITAL LETTER HE */
$config['10a0_10ff'][] = array('upper' => 4290, 'status' => 'C', 'lower' => array(11554)); /* GEORGIAN CAPITAL LETTER HIE */
$config['10a0_10ff'][] = array('upper' => 4291, 'status' => 'C', 'lower' => array(11555)); /* GEORGIAN CAPITAL LETTER WE */
$config['10a0_10ff'][] = array('upper' => 4292, 'status' => 'C', 'lower' => array(11556)); /* GEORGIAN CAPITAL LETTER HAR */
$config['10a0_10ff'][] = array('upper' => 4293, 'status' => 'C', 'lower' => array(11557)); /* GEORGIAN CAPITAL LETTER HOE */
?>