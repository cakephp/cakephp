<?php
/**
 * I18nTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'i18n');

/**
 * I18nTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class I18nTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		Cache::delete('object_map', '_cake_core_');
		App::build(array(
			'locales' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'locale' . DS),
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		), true);
		App::objects('plugin', null, false);
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		Cache::delete('object_map', '_cake_core_');
		App::build();
		App::objects('plugin', null, false);
	}

/**
 * testDefaultStrings method
 *
 * @access public
 * @return void
 */
	function testDefaultStrings() {
		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 1', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 0 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 0 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 1 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 1 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 2 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 2 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 3 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 3 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 4 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 4 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 5 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 5 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 6 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 6 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 7 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 7 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 8 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 8 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 9 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 9 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 10 (translated)', $singular);

		$plurals = $this->__plural();
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

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 10 (translated)', $singular);

		$plurals = $this->__plural();
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
		Configure::write('Config.language', 'rule_11_po');

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 11 (translated)', $singular);

		$plurals = $this->__plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 is 2 (translated)', $plurals));
		$this->assertTrue(in_array('3 is 3-6 (translated)', $plurals));
		$this->assertTrue(in_array('4 is 3-6 (translated)', $plurals));
		$this->assertTrue(in_array('5 is 3-6 (translated)', $plurals));
		$this->assertTrue(in_array('6 is 3-6 (translated)', $plurals));
		$this->assertTrue(in_array('7 is 7-10 (translated)', $plurals));
		$this->assertTrue(in_array('8 is 7-10 (translated)', $plurals));
		$this->assertTrue(in_array('9 is 7-10 (translated)', $plurals));
		$this->assertTrue(in_array('10 is 7-10 (translated)', $plurals));
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
		$this->assertEqual('Plural Rule 11 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 is 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 is 3-6 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 is 3-6 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 is 3-6 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 is 3-6 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 is 7-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 is 7-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 is 7-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 is 7-10 (from core translated)', $corePlurals));
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
 * testMoRulesEleven method
 *
 * @access public
 * @return void
 */
	function testMoRulesEleven() {
		Configure::write('Config.language', 'rule_11_mo');

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 11 (translated)', $singular);

		$plurals = $this->__plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 is 2 (translated)', $plurals));
		$this->assertTrue(in_array('3 is 3-6 (translated)', $plurals));
		$this->assertTrue(in_array('4 is 3-6 (translated)', $plurals));
		$this->assertTrue(in_array('5 is 3-6 (translated)', $plurals));
		$this->assertTrue(in_array('6 is 3-6 (translated)', $plurals));
		$this->assertTrue(in_array('7 is 7-10 (translated)', $plurals));
		$this->assertTrue(in_array('8 is 7-10 (translated)', $plurals));
		$this->assertTrue(in_array('9 is 7-10 (translated)', $plurals));
		$this->assertTrue(in_array('10 is 7-10 (translated)', $plurals));
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
		$this->assertEqual('Plural Rule 11 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 is 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 is 3-6 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 is 3-6 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 is 3-6 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 is 3-6 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 is 7-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 is 7-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 is 7-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 is 7-10 (from core translated)', $corePlurals));
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
 * testPoRulesTwelve method
 *
 * @access public
 * @return void
 */
	function testPoRulesTwelve() {
		Configure::write('Config.language', 'rule_12_po');

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 12 (translated)', $singular);

		$plurals = $this->__plural();
		$this->assertTrue(in_array('0 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 is 2 (translated)', $plurals));
		$this->assertTrue(in_array('3 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('4 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('5 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('6 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('7 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('8 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('9 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('10 is 0 or 3-10 (translated)', $plurals));
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
		$this->assertEqual('Plural Rule 12 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 is 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 is 0 or 3-10 (from core translated)', $corePlurals));
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
 * testMoRulesTwelve method
 *
 * @access public
 * @return void
 */
	function testMoRulesTwelve() {
		Configure::write('Config.language', 'rule_12_mo');

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 12 (translated)', $singular);

		$plurals = $this->__plural();
		$this->assertTrue(in_array('0 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 is 2 (translated)', $plurals));
		$this->assertTrue(in_array('3 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('4 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('5 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('6 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('7 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('8 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('9 is 0 or 3-10 (translated)', $plurals));
		$this->assertTrue(in_array('10 is 0 or 3-10 (translated)', $plurals));
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
		$this->assertEqual('Plural Rule 12 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 is 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 is 0 or 3-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 is 0 or 3-10 (from core translated)', $corePlurals));
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
 * testPoRulesThirteen method
 *
 * @access public
 * @return void
 */
	function testPoRulesThirteen() {
		Configure::write('Config.language', 'rule_13_po');

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 13 (translated)', $singular);

		$plurals = $this->__plural();
		$this->assertTrue(in_array('0 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('3 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('4 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('5 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('6 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('7 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('8 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('9 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('10 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('11 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('12 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('13 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('14 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('15 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('16 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('17 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('18 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('19 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('20 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 13 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}

/**
 * testMoRulesThirteen method
 *
 * @access public
 * @return void
 */
	function testMoRulesThirteen() {
		Configure::write('Config.language', 'rule_13_mo');

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 13 (translated)', $singular);

		$plurals = $this->__plural();
		$this->assertTrue(in_array('0 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('3 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('4 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('5 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('6 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('7 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('8 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('9 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('10 is 0 or ends in 01-10 (translated)', $plurals));
		$this->assertTrue(in_array('11 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('12 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('13 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('14 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('15 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('16 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('17 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('18 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('19 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('20 ends in 11-20 (translated)', $plurals));
		$this->assertTrue(in_array('21 everything else (translated)', $plurals));
		$this->assertTrue(in_array('22 everything else (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 13 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 is 0 or ends in 01-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 ends in 11-20 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}

/**
 * testPoRulesFourteen method
 *
 * @access public
 * @return void
 */
	function testPoRulesFourteen() {
		Configure::write('Config.language', 'rule_14_po');

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 14 (translated)', $singular);

		$plurals = $this->__plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 ends in 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 ends in 2 (translated)', $plurals));
		$this->assertTrue(in_array('3 everything else (translated)', $plurals));
		$this->assertTrue(in_array('4 everything else (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 ends in 1 (translated)', $plurals));
		$this->assertTrue(in_array('12 ends in 2 (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 ends in 1 (translated)', $plurals));
		$this->assertTrue(in_array('22 ends in 2 (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 14 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends in 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 ends in 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 ends in 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 ends in 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 ends in 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 ends in 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}

/**
 * testMoRulesFourteen method
 *
 * @access public
 * @return void
 */
	function testMoRulesFourteen() {
		Configure::write('Config.language', 'rule_14_mo');

		$singular = $this->__singular();
		$this->assertEqual('Plural Rule 14 (translated)', $singular);

		$plurals = $this->__plural();
		$this->assertTrue(in_array('0 everything else (translated)', $plurals));
		$this->assertTrue(in_array('1 ends in 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 ends in 2 (translated)', $plurals));
		$this->assertTrue(in_array('3 everything else (translated)', $plurals));
		$this->assertTrue(in_array('4 everything else (translated)', $plurals));
		$this->assertTrue(in_array('5 everything else (translated)', $plurals));
		$this->assertTrue(in_array('6 everything else (translated)', $plurals));
		$this->assertTrue(in_array('7 everything else (translated)', $plurals));
		$this->assertTrue(in_array('8 everything else (translated)', $plurals));
		$this->assertTrue(in_array('9 everything else (translated)', $plurals));
		$this->assertTrue(in_array('10 everything else (translated)', $plurals));
		$this->assertTrue(in_array('11 ends in 1 (translated)', $plurals));
		$this->assertTrue(in_array('12 ends in 2 (translated)', $plurals));
		$this->assertTrue(in_array('13 everything else (translated)', $plurals));
		$this->assertTrue(in_array('14 everything else (translated)', $plurals));
		$this->assertTrue(in_array('15 everything else (translated)', $plurals));
		$this->assertTrue(in_array('16 everything else (translated)', $plurals));
		$this->assertTrue(in_array('17 everything else (translated)', $plurals));
		$this->assertTrue(in_array('18 everything else (translated)', $plurals));
		$this->assertTrue(in_array('19 everything else (translated)', $plurals));
		$this->assertTrue(in_array('20 everything else (translated)', $plurals));
		$this->assertTrue(in_array('21 ends in 1 (translated)', $plurals));
		$this->assertTrue(in_array('22 ends in 2 (translated)', $plurals));
		$this->assertTrue(in_array('23 everything else (translated)', $plurals));
		$this->assertTrue(in_array('24 everything else (translated)', $plurals));
		$this->assertTrue(in_array('25 everything else (translated)', $plurals));

		$coreSingular = $this->__singularFromCore();
		$this->assertEqual('Plural Rule 14 (from core translated)', $coreSingular);

		$corePlurals = $this->__pluralFromCore();
		$this->assertTrue(in_array('0 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 ends in 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 ends in 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 ends in 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 ends in 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('21 ends in 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('22 ends in 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('23 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('24 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('25 everything else (from core translated)', $corePlurals));
	}

/**
 * testSetLanguageWithSession method
 *
 * @access public
 * @return void
 */
	function testSetLanguageWithSession () {
		$_SESSION['Config']['language'] = 'po';
		$singular = $this->__singular();
		$this->assertEqual('Po (translated)', $singular);

		$plurals = $this->__plural();
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
		$singular = $this->__singular();
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
 * testPluginTranslation method
 *
 * @access public
 * @return void
 */
	function testPluginTranslation() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));

		Configure::write('Config.language', 'po');
		$singular = $this->__domainSingular();
		$this->assertEqual('Plural Rule 1 (from plugin)', $singular);

		$plurals = $this->__domainPlural();
		$this->assertTrue(in_array('0 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('1 = 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('2 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('3 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('4 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('5 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('6 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('7 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('8 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('9 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('10 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('11 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('12 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('13 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('14 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('15 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('16 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('17 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('18 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('19 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('20 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('21 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('22 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('23 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('24 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('25 = 0 or > 1 (from plugin)', $plurals));
	}

/**
 * testPoMultipleLineTranslation method
 *
 * @access public
 * @return void
 */
	function testPoMultipleLineTranslation () {
		Configure::write('Config.language', 'po');

		$string = "This is a multiline translation\n";
		$string .= "broken up over multiple lines.\n";
		$string .= "This is the third line.\n";
		$string .= "This is the forth line.";
		$result = __($string, true);

		$expected = "This is a multiline translation\n";
		$expected .= "broken up over multiple lines.\n";
		$expected .= "This is the third line.\n";
		$expected .= "This is the forth line. (translated)";
		$this->assertEqual($result, $expected);

		// Windows Newline is \r\n
		$string = "This is a multiline translation\r\n";
		$string .= "broken up over multiple lines.\r\n";
		$string .= "This is the third line.\r\n";
		$string .= "This is the forth line.";
		$result = __($string, true);
		$this->assertEqual($result, $expected);

		$singular = "valid\nsecond line";
		$plural = "valids\nsecond line";

		$result = __n($singular, $plural, 1, true);
		$expected = "v\nsecond line";
		$this->assertEqual($result, $expected);

		$result = __n($singular, $plural, 2, true);
		$expected = "vs\nsecond line";
		$this->assertEqual($result, $expected);

		$string = "This is a multiline translation\n";
		$string .= "broken up over multiple lines.\n";
		$string .= "This is the third line.\n";
		$string .= "This is the forth line.";

		$singular = "%d = 1\n" . $string;
		$plural = "%d = 0 or > 1\n" . $string;

		$result = __n($singular, $plural, 1, true);
		$expected = "%d is 1\n" . $string;
		$this->assertEqual($result, $expected);

		$result = __n($singular, $plural, 2, true);
		$expected = "%d is 2-4\n" . $string;
		$this->assertEqual($result, $expected);

		// Windows Newline is \r\n
		$string = "This is a multiline translation\r\n";
		$string .= "broken up over multiple lines.\r\n";
		$string .= "This is the third line.\r\n";
		$string .= "This is the forth line.";

		$singular = "%d = 1\r\n" . $string;
		$plural = "%d = 0 or > 1\r\n" . $string;

		$result = __n($singular, $plural, 1, true);
		$expected = "%d is 1\n" . str_replace("\r\n", "\n", $string);
		$this->assertEqual($result, $expected);

		$result = __n($singular, $plural, 2, true);
		$expected = "%d is 2-4\n" . str_replace("\r\n", "\n", $string);
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
 * testFloatValue method
 *
 * @access public
 * @return void
 */
	function testFloatValue() {
		Configure::write('Config.language', 'rule_9_po');

		$result = __n('%d = 1', '%d = 0 or > 1', (float)1, true);
		$expected = '%d is 1 (translated)';
		$this->assertEqual($result, $expected);

		$result = __n('%d = 1', '%d = 0 or > 1', (float)2, true);
		$expected = "%d ends in 2-4, not 12-14 (translated)";
		$this->assertEqual($result, $expected);

		$result = __n('%d = 1', '%d = 0 or > 1', (float)5, true);
		$expected = "%d everything else (translated)";
		$this->assertEqual($result, $expected);
	}

/**
 * testCategory method
 *
 * @access public
 * @return void
 */
	function testCategory() {
		Configure::write('Config.language', 'po');
		$category = $this->__category();
		$this->assertEqual('Monetary Po (translated)', $category);
	}

/**
 * testPluginCategory method
 *
 * @access public
 * @return void
 */
	function testPluginCategory() {
		Configure::write('Config.language', 'po');

		$singular = $this->__domainCategorySingular();
		$this->assertEqual('Monetary Plural Rule 1 (from plugin)', $singular);

		$plurals = $this->__domainCategoryPlural();
		$this->assertTrue(in_array('Monetary 0 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('Monetary 1 = 1 (from plugin)', $plurals));
	}

/**
 * testCategoryThenSingular method
 *
 * @access public
 * @return void
 */
	function testCategoryThenSingular() {
		Configure::write('Config.language', 'po');
		$category = $this->__category();
		$this->assertEqual('Monetary Po (translated)', $category);

		$singular = $this->__singular();
		$this->assertEqual('Po (translated)', $singular);
	}

	function testTimeDefinition() {
		Configure::write('Config.language', 'po');
		$result = __c('d_fmt', 5, true);
		$expected = '%m/%d/%Y';
		$this->assertEqual($result, $expected);

		$result = __c('am_pm', 5, true);
		$expected = array('AM', 'PM');
		$this->assertEqual($result, $expected);

		$result = __c('abmon', 5, true);
		$expected = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		$this->assertEqual($result, $expected);
	}

	function testTimeDefinitionJapanese(){
		Configure::write('Config.language', 'ja_jp');
		$result = __c('d_fmt', 5, true);
		
		$expected = "%Y年%m月%d日";
		
		$this->assertEqual($result, $expected);

		$result = __c('am_pm', 5, true);
		$expected = array("午前", "午後");
		$this->assertEqual($result, $expected);

		$result = __c('abmon', 5, true);
		$expected = array(" 1月", " 2月", " 3月", " 4月", " 5月", " 6月", " 7月", " 8月", " 9月", "10月", "11月", "12月");
		$this->assertEqual($result, $expected);
	}

/**
 * Singular method
 *
 * @access private
 * @return void
 */
	function __domainCategorySingular($domain = 'test_plugin', $category = 3) {
		$singular = __dc($domain, 'Plural Rule 1', $category, true);
		return $singular;
	}

/**
 * Plural method
 *
 * @access private
 * @return void
 */
	function __domainCategoryPlural($domain = 'test_plugin', $category = 3) {
		$plurals = array();
		for ($number = 0; $number <= 25; $number++) {
			$plurals[] =  sprintf(__dcn($domain, '%d = 1', '%d = 0 or > 1', (float)$number, $category, true), (float)$number);
		}
		return $plurals;
	}

/**
 * Singular method
 *
 * @access private
 * @return void
 */
	function __domainSingular($domain = 'test_plugin') {
		$singular = __d($domain, 'Plural Rule 1', true);
		return $singular;
	}

/**
 * Plural method
 *
 * @access private
 * @return void
 */
	function __domainPlural($domain = 'test_plugin') {
		$plurals = array();
		for ($number = 0; $number <= 25; $number++) {
			$plurals[] =  sprintf(__dn($domain, '%d = 1', '%d = 0 or > 1', (float)$number, true), (float)$number );
		}
		return $plurals;
	}

/**
 * category method
 *
 * @access private
 * @return void
 */
	function __category($category = 3) {
		$singular = __c('Plural Rule 1', $category, true);
		return $singular;
	}

/**
 * Singular method
 *
 * @access private
 * @return void
 */
	function __singular() {
		$singular = __('Plural Rule 1', true);
		return $singular;
	}

/**
 * Plural method
 *
 * @access private
 * @return void
 */
	function __plural() {
		$plurals = array();
		for ($number = 0; $number <= 25; $number++) {
			$plurals[] =  sprintf(__n('%d = 1', '%d = 0 or > 1', (float)$number, true), (float)$number );
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
		$plurals = array();
		for ($number = 0; $number <= 25; $number++) {
			$plurals[] =  sprintf(__n('%d = 1 (from core)', '%d = 0 or > 1 (from core)', (float)$number, true), (float)$number );
		}
		return $plurals;
	}
}
