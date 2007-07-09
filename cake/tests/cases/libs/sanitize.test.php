<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5428
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('sanitize');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class SanitizeTest extends UnitTestCase {

	function testEscapeAlphaNumeric() {
		$resultAlpha = Sanitize::escape('abc', 'default');
		$this->assertEqual($resultAlpha, 'abc');

		$resultNumeric = Sanitize::escape('123', 'default');
		$this->assertEqual($resultNumeric, '123');

		$resultNumeric = Sanitize::escape(1234, 'default');
		$this->assertEqual($resultNumeric, 1234);

		$resultNumeric = Sanitize::escape(1234.23, 'default');
		$this->assertEqual($resultNumeric, 1234.23);

		$resultNumeric = Sanitize::escape('#1234.23', 'default');
		$this->assertEqual($resultNumeric, '#1234.23');
	}
}
?>