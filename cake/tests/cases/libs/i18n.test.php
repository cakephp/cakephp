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
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'i18n');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class I18nTest extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		Configure::write('Locale.path', TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'locale');
	}
/**
 * testDefaultStrings method
 *
 * @access public
 * @return void
 */
	function testDefaultStrings() {
		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 1', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('1 = 1', $plurals));
		$this->assertTrue(in_array('2 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('3 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('4 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('5 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('6 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('7 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('8 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('9 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('10 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('11 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('12 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('13 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('14 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('15 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('16 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('17 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('18 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('19 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('20 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('21 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('22 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('23 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('24 = 0 or > 1', $plurals));
		$this->assertTrue(in_array('25 = 0 or > 1', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 1 (from core)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('1 = 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('2 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('3 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('4 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('5 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('6 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('7 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('8 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('9 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('10 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('11 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('12 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('13 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('14 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('15 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('16 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('17 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('18 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('19 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('20 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('21 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('22 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('23 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('24 = 0 or > 1 (from core)', $corePlurals));
		$this->assertTrue(in_array('25 = 0 or > 1 (from core)', $corePlurals));
	}
/**
 * testPoRulesZero method
 *
 * @access public
 * @return void
 */
	function testPoRulesZero() {
		Configure::write('Config.language', 'rule_0_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 0 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('1 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('2 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('3 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('4 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('5 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('6 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('7 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('8 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('9 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('10 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('11 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('12 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('13 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('14 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('15 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('16 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('17 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('18 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('19 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('20 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('21 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('22 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('23 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('24 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('25 ends with any # (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 0 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 ends with any # (from core translated)', $corePlurals));
	}
/**
 * testMoRulesZero method
 *
 * @access public
 * @return void
 */
	function testMoRulesZero() {
		Configure::write('Config.language', 'rule_0_mo');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 0 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('1 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('2 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('3 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('4 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('5 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('6 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('7 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('8 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('9 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('10 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('11 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('12 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('13 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('14 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('15 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('16 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('17 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('18 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('19 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('20 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('21 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('22 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('23 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('24 ends with any # (translated)', $plurals));
		$this->assertTrue(in_array('25 ends with any # (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 0 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 ends with any # (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 ends with any # (from core translated)', $corePlurals));
	}
/**
 * testPoRulesOne method
 *
 * @access public
 * @return void
 */
	function testPoRulesOne() {
		Configure::write('Config.language', 'rule_1_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 1 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('1 = 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('3 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('4 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('5 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('6 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('7 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('8 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('9 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('10 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('11 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('12 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('13 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('14 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('15 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('16 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('17 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('18 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('19 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('20 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('21 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('22 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('23 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('24 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('25 = 0 or > 1 (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 1 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 = 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 = 0 or > 1 (from core translated)', $corePlurals));
	}
/**
 * testMoRulesOne method
 *
 * @access public
 * @return void
 */
	function testMoRulesOne() {
		Configure::write('Config.language', 'rule_1_mo');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 1 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('1 = 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('3 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('4 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('5 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('6 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('7 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('8 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('9 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('10 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('11 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('12 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('13 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('14 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('15 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('16 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('17 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('18 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('19 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('20 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('21 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('22 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('23 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('24 = 0 or > 1 (translated)', $plurals));
		$this->assertTrue(in_array('25 = 0 or > 1 (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 1 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 = 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 = 0 or > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 = 0 or > 1 (from core translated)', $corePlurals));
	}
/**
 * testPoRulesTwo method
 *
 * @access public
 * @return void
 */
	function testPoRulesTwo() {
		Configure::write('Config.language', 'rule_2_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 2 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 = 0 or 1 (translated)', $plurals));
		$this->assertTrue(in_array('1 = 0 or 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('3 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('4 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('5 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('6 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('7 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('8 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('9 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('10 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('11 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('12 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('13 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('14 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('15 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('16 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('17 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('18 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('19 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('20 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('21 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('22 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('23 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('24 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('25 > 1 (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 2 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 = 0 or 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 = 0 or 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 > 1 (from core translated)', $corePlurals));
	}
/**
 * testMoRulesTwo method
 *
 * @access public
 * @return void
 */
	function testMoRulesTwo() {
		Configure::write('Config.language', 'rule_2_mo');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 2 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 = 0 or 1 (translated)', $plurals));
		$this->assertTrue(in_array('1 = 0 or 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('3 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('4 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('5 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('6 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('7 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('8 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('9 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('10 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('11 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('12 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('13 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('14 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('15 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('16 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('17 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('18 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('19 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('20 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('21 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('22 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('23 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('24 > 1 (translated)', $plurals));
		$this->assertTrue(in_array('25 > 1 (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 2 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 = 0 or 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 = 0 or 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 > 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 > 1 (from core translated)', $corePlurals));
	}
/**
 * testPoRulesThree method
 *
 * @access public
 * @return void
 */
	function testPoRulesThree() {
		Configure::write('Config.language', 'rule_3_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 3 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 = 0 (translated)', $plurals));
		$this->assertTrue(in_array('1 ends 1 but not 11 (translated)', $plurals));
		$this->assertTrue(in_array('2 everything else (translated)', $plurals));
		$this->assertTrue(in_array('3 everything else (translated)', $plurals));
		$this->assertTrue(in_array('4 everything else (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 ends 1 but not 11 (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 3 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 = 0 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends 1 but not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 ends 1 but not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testMoRulesThree method
 *
 * @access public
 * @return void
 */
	function testMoRulesThree() {
		Configure::write('Config.language', 'rule_3_mo');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 3 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 = 0 (translated)', $plurals));
		$this->assertTrue(in_array('1 ends 1 but not 11 (translated)', $plurals));
		$this->assertTrue(in_array('2 everything else (translated)', $plurals));
		$this->assertTrue(in_array('3 everything else (translated)', $plurals));
		$this->assertTrue(in_array('4 everything else (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 ends 1 but not 11 (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 3 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 = 0 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends 1 but not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 ends 1 but not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testPoRulesFour method
 *
 * @access public
 * @return void
 */
	function testPoRulesFour() {
		Configure::write('Config.language', 'rule_4_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 4 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 = 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 = 2 (translated)', $plurals));
		$this->assertTrue(in_array('3 everything else (translated)', $plurals));
		$this->assertTrue(in_array('4 everything else (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 4 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 = 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 = 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testMoRulesFour method
 *
 * @access public
 * @return void
 */
	function testMoRulesFour() {
		Configure::write('Config.language', 'rule_4_mo');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 4 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 = 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 = 2 (translated)', $plurals));
		$this->assertTrue(in_array('3 everything else (translated)', $plurals));
		$this->assertTrue(in_array('4 everything else (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 4 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 = 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 = 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testPoRulesFive method
 *
 * @access public
 * @return void
 */
	function testPoRulesFive() {
		Configure::write('Config.language', 'rule_5_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 5 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('0 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('1 = 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('3 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('4 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('5 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('6 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('7 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('8 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('9 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('10 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('11 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('12 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('13 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('14 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('15 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('16 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('17 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('18 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('19 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 5 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('0 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 = 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testMoRulesFive method
 *
 * @access public
 * @return void
 */
	function testMoRulesFive() {
		Configure::write('Config.language', 'rule_5_mo');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 5 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('0 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('1 = 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('3 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('4 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('5 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('6 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('7 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('8 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('9 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('10 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('11 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('12 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('13 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('14 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('15 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('16 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('17 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('18 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('19 = 0 or ends in 01-19 (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 5 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('0 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 = 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 = 0 or ends in 01-19 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testPoRulesSix method
 *
 * @access public
 * @return void
 */
	function testPoRulesSix() {
		Configure::write('Config.language', 'rule_6_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 6 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('1 ends in 1, not 11 (translated)', $plurals));
		$this->assertTrue(in_array('2 everything else (translated)', $plurals));
		$this->assertTrue(in_array('3 everything else (translated)', $plurals));
		$this->assertTrue(in_array('4 everything else (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('11 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('12 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('13 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('14 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('15 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('16 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('17 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('18 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('19 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('20 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('21 ends in 1, not 11 (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 6 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends in 1, not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 ends in 1, not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testMoRulesSix method
 *
 * @access public
 * @return void
 */
	function testMoRulesSix() {
		Configure::write('Config.language', 'rule_6_mo');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 6 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('1 ends in 1, not 11 (translated)', $plurals));
		$this->assertTrue(in_array('2 everything else (translated)', $plurals));
		$this->assertTrue(in_array('3 everything else (translated)', $plurals));
		$this->assertTrue(in_array('4 everything else (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('11 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('12 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('13 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('14 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('15 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('16 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('17 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('18 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('19 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('20 ends in 0 or ends in 10-20 (translated)', $plurals));
		$this->assertTrue(in_array('21 ends in 1, not 11 (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 6 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends in 1, not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 ends in 0 or ends in 10-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 ends in 1, not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testPoRulesSeven method
 *
 * @access public
 * @return void
 */
	function testPoRulesSeven() {
		Configure::write('Config.language', 'rule_7_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 7 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 ends in 1, not 11 (translated)', $plurals));
		$this->assertTrue(in_array('2 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('3 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('4 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 ends in 1, not 11 (translated)', $plurals));
		$this->assertTrue(in_array('22 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('23 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('24 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 7 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends in 1, not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 ends in 1, not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testMoRulesSeven method
 *
 * @access public
 * @return void
 */
	function testMoRulesSeven() {
		Configure::write('Config.language', 'rule_7_mo');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 7 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 ends in 1, not 11 (translated)', $plurals));
		$this->assertTrue(in_array('2 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('3 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('4 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 ends in 1, not 11 (translated)', $plurals));
		$this->assertTrue(in_array('22 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('23 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('24 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 7 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends in 1, not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 ends in 1, not 11 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testPoRulesEight method
 *
 * @access public
 * @return void
 */
	function testPoRulesEight() {
		Configure::write('Config.language', 'rule_8_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 8 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 is 2-4 (translated)', $plurals));
		$this->assertTrue(in_array('3 is 2-4 (translated)', $plurals));
		$this->assertTrue(in_array('4 is 2-4 (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 8 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 is 2-4 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 is 2-4 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 is 2-4 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testMoRulesEight method
 *
 * @access public
 * @return void
 */
	function testMoRulesEight() {
		Configure::write('Config.language', 'rule_8_mo');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 8 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 is 2-4 (translated)', $plurals));
		$this->assertTrue(in_array('3 is 2-4 (translated)', $plurals));
		$this->assertTrue(in_array('4 is 2-4 (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 8 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 is 2-4 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 is 2-4 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 is 2-4 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testPoRulesNine method
 *
 * @access public
 * @return void
 */
	function testPoRulesNine() {
		Configure::write('Config.language', 'rule_9_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 9 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('3 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('4 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('23 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('24 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 9 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testMoRulesNine method
 *
 * @access public
 * @return void
 */
	function testMoRulesNine() {
		Configure::write('Config.language', 'rule_9_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 9 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('3 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('4 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('23 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('24 ends in 2-4, not 12-14 (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 9 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 ends in 2-4, not 12-14 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testPoRulesTen method
 *
 * @access public
 * @return void
 */
	function testPoRulesTen() {
		Configure::write('Config.language', 'rule_10_po');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 10 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 ends in 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 ends in 2 (translated)', $plurals));
		$this->assertTrue(in_array('3 ends in 03-04 (translated)', $plurals));
		$this->assertTrue(in_array('4 ends in 03-04 (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 10 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends in 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 ends in 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 ends in 03-04 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 ends in 03-04 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testMoRulesTen method
 *
 * @access public
 * @return void
 */
	function testMoRulesTen() {
		Configure::write('Config.language', 'rule_10_mo');

		$singular = $this->__Singular();
		$this->assertEqual('Plural Rule 10 (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 ends in 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 ends in 2 (translated)', $plurals));
		$this->assertTrue(in_array('3 ends in 03-04 (translated)', $plurals));
		$this->assertTrue(in_array('4 ends in 03-04 (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 10 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends in 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 ends in 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 ends in 03-04 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 ends in 03-04 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testPoRulesEleven method
 *
 * @access public
 * @return void
 */
	function testPoRulesEleven() {

	}
/**
 * testMoRulesEleven method
 *
 * @access public
 * @return void
 */
	function testMoRulesEleven() {

	}
/**
 * testPoRulesTwelve method
 *
 * @access public
 * @return void
 */
	function testPoRulesTwelve() {

	}
/**
 * testMoRulesTwelve method
 *
 * @access public
 * @return void
 */
	function testMoRulesTwelve() {

	}
/**
 * testPoRulesThirteen method
 *
 * @access public
 * @return void
 */
	function testPoRulesThirteen() {

	}
/**
 * testMoRulesThirteen method
 *
 * @access public
 * @return void
 */
	function testMoRulesThirteen() {

	}
/**
 * testPoRulesFourteen method
 *
 * @access public
 * @return void
 */
	function testPoRulesFourteen() {

	}
/**
 * testMoRulesFourteen method
 *
 * @access public
 * @return void
 */
	function testMoRulesFourteen() {

	}
/**
 * testSetLanguageWithSession method
 *
 * @access public
 * @return void
 */
	function testSetLanguageWithSession () {
		$_SESSION['Config']['language'] = 'po';
		$singular = $this->__Singular();
		$this->assertEqual('Po (translated)', $singular);

		$plurals = $this->__Plural();
		$this->assertTrue(in_array('0 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (po translated)', $plurals));
		$this->assertTrue(in_array('2 is 2-4 (po translated)', $plurals));
		$this->assertTrue(in_array('3 is 2-4 (po translated)', $plurals));
		$this->assertTrue(in_array('4 is 2-4 (po translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('11 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('12 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (po translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (po translated)', $plurals));
		unset($_SESSION['Config']['language']);
	}
/**
 * testNoCoreTranslation method
 *
 * @access public
 * @return void
 */
	function testNoCoreTranslation () {
		Configure::write('Config.language', 'po');
		$singular = $this->__Singular();
		$this->assertEqual('Po (translated)', $singular);

		$coreSingular = $this->__singularFromCore();
		$this->assertNotEqual('Po (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertFalse(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertFalse(in_array('2 is 2-4 (from core translated)', $corePlurals));
		$this->assertFalse(in_array('3 is 2-4 (from core translated)', $corePlurals));
		$this->assertFalse(in_array('4 is 2-4 (from core translated)', $corePlurals));
		$this->assertFalse(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('11 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('12 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertFalse(in_array('25 everything else (from core translated)', $corePlurals));
	}
/**
 * testPoMultipleLineTranslation method
 *
 * @access public
 * @return void
 */
	function testPoMultipleLineTranslation () {
		Configure::write('Config.language', 'po');
		$string = <<<EOD
This is a multiline translation
broken up over multiple lines.
This is the third line.
This is the forth line.
EOD;
		$result = __($string, true);
		$expected = <<<EOD
This is a multiline translation
broken up over multiple lines.
This is the third line.
This is the forth line. (translated)
EOD;
		$this->assertEqual($result, $expected);
	}
/**
 * testPoNoTranslationNeeded method
 *
 * @access public
 * @return void
 */
	function testPoNoTranslationNeeded () {
		Configure::write('Config.language', 'po');
		$result = __('No Translation needed', true);
		$this->assertEqual($result, 'No Translation needed');
	}
/**
 * testPoQuotedString method
 *
 * @access public
 * @return void
 */
	function testPoQuotedString () {
		$expected = 'this is a "quoted string" (translated)';
		$this->assertEqual(__('this is a "quoted string"', true), $expected);
	}
/**
 * Singular method
 *
 * @access private
 * @return void
 */
	function __Singular() {
		$singular = __('Plural Rule 1', true);
		return $singular;
	}
/**
 * Plural method
 *
 * @access private
 * @return void
 */
	function __Plural() {
		$plurals = array();
		for ($number = 0; $number <= 25; $number++) {
			$plurals[] =  sprintf(__n('%d = 1', '%d = 0 or > 1', $number, true), $number );
		}
		return $plurals;
	}
/**
 * singularFromCore method
 *
 * @access private
 * @return void
 */
	function __singularFromCore() {
		$singular = __('Plural Rule 1 (from core)', true);
		return $singular;
	}
/**
 * pluralFromCore method
 *
 * @access private
 * @return void
 */
	function __pluralFromCore() {
		for ($number = 0; $number <= 25; $number++) {
			$plurals[] =  sprintf(__n('%d = 1 (from core)', '%d = 0 or > 1 (from core)', $number, true), $number );
		}
		return $plurals;
	}
}
?>