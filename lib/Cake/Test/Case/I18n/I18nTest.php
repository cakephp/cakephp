<?php
/**
 * I18nTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.I18n
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('I18n', 'I18n');
App::uses('CakeSession', 'Model/Datasource');

/**
 * I18nTest class
 *
 * @package       Cake.Test.Case.I18n
 */
class I18nTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Cache::delete('object_map', '_cake_core_');
		App::build(array(
			'Locale' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Locale' . DS),
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load(array('TestPlugin'));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		Cache::delete('object_map', '_cake_core_');
		App::build();
		CakePlugin::unload();
	}

/**
 * testTranslationCaching method
 *
 * @return void
 */
	public function testTranslationCaching() {
		Configure::write('Config.language', 'cache_test_po');

		// reset internally stored entries
		I18n::clear();

		Cache::clear(false, '_cake_core_');
		$lang = Configure::read('Config.language');

		Cache::config('_cake_core_', Cache::config('default'));

		// make some calls to translate using different domains
		$this->assertEquals('Dom 1 Foo', I18n::translate('dom1.foo', false, 'dom1'));
		$this->assertEquals('Dom 1 Bar', I18n::translate('dom1.bar', false, 'dom1'));
		$domains = I18n::domains();
		$this->assertEquals('Dom 1 Foo', $domains['dom1']['cache_test_po']['LC_MESSAGES']['dom1.foo']['']);

		// reset internally stored entries
		I18n::clear();

		// now only dom1 should be in cache
		$cachedDom1 = Cache::read('dom1_' . $lang, '_cake_core_');
		$this->assertEquals('Dom 1 Foo', $cachedDom1['LC_MESSAGES']['dom1.foo']['']);
		$this->assertEquals('Dom 1 Bar', $cachedDom1['LC_MESSAGES']['dom1.bar']['']);
		// dom2 not in cache
		$this->assertFalse(Cache::read('dom2_' . $lang, '_cake_core_'));

		// translate a item of dom2 (adds dom2 to cache)
		$this->assertEquals('Dom 2 Foo', I18n::translate('dom2.foo', false, 'dom2'));

		// verify dom2 was cached through manual read from cache
		$cachedDom2 = Cache::read('dom2_' . $lang, '_cake_core_');
		$this->assertEquals('Dom 2 Foo', $cachedDom2['LC_MESSAGES']['dom2.foo']['']);
		$this->assertEquals('Dom 2 Bar', $cachedDom2['LC_MESSAGES']['dom2.bar']['']);

		// modify cache entry manually to verify that dom1 entries now will be read from cache
		$cachedDom1['LC_MESSAGES']['dom1.foo'][''] = 'FOO';
		Cache::write('dom1_' . $lang, $cachedDom1, '_cake_core_');
		$this->assertEquals('FOO', I18n::translate('dom1.foo', false, 'dom1'));
	}

/**
 * testDefaultStrings method
 *
 * @return void
 */
	public function testDefaultStrings() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 1', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 1 (from core)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesZero() {
		Configure::write('Config.language', 'rule_0_po');
		$this->assertRulesZero();
	}

/**
 * testMoRulesZero method
 *
 * @return void
 */
	public function testMoRulesZero() {
		Configure::write('Config.language', 'rule_0_mo');
		$this->assertRulesZero();
	}

/**
 * Assertions for rules zero.
 *
 * @return void
 */
	public function assertRulesZero() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 0 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 0 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesOne() {
		Configure::write('Config.language', 'rule_1_po');
		$this->assertRulesOne();
	}

/**
 * testMoRulesOne method
 *
 * @return void
 */
	public function testMoRulesOne() {
		Configure::write('Config.language', 'rule_1_mo');
		$this->assertRulesOne();
	}

/**
 * Assertions for plural rule one
 *
 * @return void
 */
	public function assertRulesOne() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 1 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 1 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * testMoRulesTwo method
 *
 * @return void
 */
	public function testMoRulesTwo() {
		Configure::write('Config.language', 'rule_2_mo');
		$this->assertRulesTwo();
	}

/**
 * testPoRulesTwo method
 *
 * @return void
 */
	public function testPoRulesTwo() {
		Configure::write('Config.language', 'rule_2_po');
		$this->assertRulesTwo();
	}

/**
 * Assertions for rules Two
 *
 * @return void
 */
	public function assertRulesTwo() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 2 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 2 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesThree() {
		Configure::write('Config.language', 'rule_3_po');
		$this->assertRulesThree();
	}

/**
 * testMoRulesThree method
 *
 * @return void
 */
	public function testMoRulesThree() {
		Configure::write('Config.language', 'rule_3_mo');
		$this->assertRulesThree();
	}

/**
 * Assert rules for plural three.
 *
 * @return void
 */
	public function assertRulesThree() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 3 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 3 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesFour() {
		Configure::write('Config.language', 'rule_4_po');
		$this->assertRulesFour();
	}

/**
 * testMoRulesFour method
 *
 * @return void
 */
	public function testMoRulesFour() {
		Configure::write('Config.language', 'rule_4_mo');
		$this->assertRulesFour();
	}

/**
 * Run the assertions for Rule 4 plurals.
 *
 * @return void
 */
	public function assertRulesFour() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 4 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 4 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesFive() {
		Configure::write('Config.language', 'rule_5_po');
		$this->assertRulesFive();
	}

/**
 * testMoRulesFive method
 *
 * @return void
 */
	public function testMoRulesFive() {
		Configure::write('Config.language', 'rule_5_mo');
		$this->assertRulesFive();
	}

/**
 * Run the assertions for rule 5 plurals
 *
 * @return void
 */
	public function assertRulesFive() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 5 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 5 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesSix() {
		Configure::write('Config.language', 'rule_6_po');
		$this->assertRulesSix();
	}

/**
 * testMoRulesSix method
 *
 * @return void
 */
	public function testMoRulesSix() {
		Configure::write('Config.language', 'rule_6_mo');
		$this->assertRulesSix();
	}

/**
 * Assertions for the sixth plural rules.
 *
 * @return void
 */
	public function assertRulesSix() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 6 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 6 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesSeven() {
		Configure::write('Config.language', 'rule_7_po');
		$this->assertRulesSeven();
	}

/**
 * testMoRulesSeven method
 *
 * @return void
 */
	public function testMoRulesSeven() {
		Configure::write('Config.language', 'rule_7_mo');
		$this->assertRulesSeven();
	}

/**
 * Run assertions for seventh plural rules
 *
 * @return void
 */
	public function assertRulesSeven() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 7 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 7 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesEight() {
		Configure::write('Config.language', 'rule_8_po');
		$this->assertRulesEight();
	}

/**
 * testMoRulesEight method
 *
 * @return void
 */
	public function testMoRulesEight() {
		Configure::write('Config.language', 'rule_8_mo');
		$this->assertRulesEight();
	}

/**
 * Run assertions for the eighth plural rule.
 *
 * @return void
 */
	public function assertRulesEight() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 8 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 8 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesNine() {
		Configure::write('Config.language', 'rule_9_po');
		$this->assertRulesNine();
	}

/**
 * testMoRulesNine method
 *
 * @return void
 */
	public function testMoRulesNine() {
		Configure::write('Config.language', 'rule_9_mo');
		$this->assertRulesNine();
	}

/**
 * Assert plural rules nine
 *
 * @return void
 */
	public function assertRulesNine() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 9 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 9 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesTen() {
		Configure::write('Config.language', 'rule_10_po');
		$this->assertRulesTen();
	}

/**
 * testMoRulesTen method
 *
 * @return void
 */
	public function testMoRulesTen() {
		Configure::write('Config.language', 'rule_10_mo');
		$this->assertRulesTen();
	}

/**
 * Assertions for plural rules 10
 *
 * @return void
 */
	public function assertRulesTen() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 10 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 10 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesEleven() {
		Configure::write('Config.language', 'rule_11_po');
		$this->assertRulesEleven();
	}

/**
 * testMoRulesEleven method
 *
 * @return void
 */
	public function testMoRulesEleven() {
		Configure::write('Config.language', 'rule_11_mo');
		$this->assertRulesEleven();
	}

/**
 * Assertions for plural rules eleven
 *
 * @return void
 */
	public function assertRulesEleven() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 11 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 11 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPoRulesTwelve() {
		Configure::write('Config.language', 'rule_12_po');
		$this->assertRulesTwelve();
	}

/**
 * testMoRulesTwelve method
 *
 * @return void
 */
	public function testMoRulesTwelve() {
		Configure::write('Config.language', 'rule_12_mo');
		$this->assertRulesTwelve();
	}

/**
 * Assertions for plural rules twelve
 *
 * @return void
 */
	public function assertRulesTwelve() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 12 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 12 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * testMoRulesThirteen method
 *
 * @return void
 */
	public function testmoRulesThirteen() {
		Configure::write('Config.language', 'rule_13_mo');
		$this->assertRulesThirteen();
	}

/**
 * testPoRulesThirteen method
 *
 * @return void
 */
	public function testPoRulesThirteen() {
		Configure::write('Config.language', 'rule_13_po');
		$this->assertRulesThirteen();
	}

/**
 * Assertions for plural rules thirteen
 *
 * @return void
 */
	public function assertRulesThirteen() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 13 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 13 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * testMoRulesFourteen method
 *
 * @return void
 */
	public function testMoRulesFourteen() {
		Configure::write('Config.language', 'rule_14_mo');
		$this->assertRulesFourteen();
	}

/**
 * testPoRulesFourteen method
 *
 * @return void
 */
	public function testPoRulesFourteen() {
		Configure::write('Config.language', 'rule_14_po');
		$this->assertRulesFourteen();
	}

/**
 * Assertions for plural rules fourteen
 *
 * @return void
 */
	public function assertRulesFourteen() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 14 (translated)', $singular);

		$plurals = $this->_plural();
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

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 14 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * testMoRulesFifteen method
 *
 * @return void
 */
	public function testMoRulesFifteen() {
		Configure::write('Config.language', 'rule_15_mo');
		$this->assertRulesFifteen();
	}

/**
 * testPoRulesFifteen method
 *
 * @return void
 */
	public function testPoRulesFifteen() {
		Configure::write('Config.language', 'rule_15_po');
		$this->assertRulesFifteen();
	}

/**
 * Assertions for plural rules fifteen
 *
 * @return void
 */
	public function assertRulesFifteen() {
		$singular = $this->_singular();
		$this->assertEquals('Plural Rule 15 (translated)', $singular);

		$plurals = $this->_plural(111);
		$this->assertTrue(in_array('0 is 0 (translated)', $plurals));
		$this->assertTrue(in_array('1 is 1 (translated)', $plurals));
		$this->assertTrue(in_array('2 is 2 (translated)', $plurals));
		$this->assertTrue(in_array('3 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('4 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('5 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('6 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('7 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('8 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('9 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('10 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('11 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('12 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('13 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('14 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('15 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('16 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('17 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('18 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('19 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('20 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('31 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('42 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('53 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('64 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('75 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('86 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('97 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('98 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('99 ends with 11-99 (translated)', $plurals));
		$this->assertTrue(in_array('100 everything else (translated)', $plurals));
		$this->assertTrue(in_array('101 everything else (translated)', $plurals));
		$this->assertTrue(in_array('102 everything else (translated)', $plurals));
		$this->assertTrue(in_array('103 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('104 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('105 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('106 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('107 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('108 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('109 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('110 ends with 03-10 (translated)', $plurals));
		$this->assertTrue(in_array('111 ends with 11-99 (translated)', $plurals));

		$coreSingular = $this->_singularFromCore();
		$this->assertEquals('Plural Rule 15 (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore(111);
		$this->assertTrue(in_array('0 is 0 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('1 is 1 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('2 is 2 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('3 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('4 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('5 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('6 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('7 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('8 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('9 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('10 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('11 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('12 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('13 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('14 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('15 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('16 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('17 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('18 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('19 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('20 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('31 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('42 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('53 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('64 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('75 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('86 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('97 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('98 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('99 ends with 11-99 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('100 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('101 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('102 everything else (from core translated)', $corePlurals));
		$this->assertTrue(in_array('103 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('104 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('105 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('106 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('107 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('108 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('109 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('110 ends with 03-10 (from core translated)', $corePlurals));
		$this->assertTrue(in_array('111 ends with 11-99 (from core translated)', $corePlurals));
	}

/**
 * testSetLanguageWithSession method
 *
 * @return void
 */
	public function testSetLanguageWithSession() {
		CakeSession::write('Config.language', 'po');
		$singular = $this->_singular();
		$this->assertEquals('Po (translated)', $singular);

		$plurals = $this->_plural();
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
		CakeSession::delete('Config.language');
	}

/**
 * testNoCoreTranslation method
 *
 * @return void
 */
	public function testNoCoreTranslation() {
		Configure::write('Config.language', 'po');
		$singular = $this->_singular();
		$this->assertEquals('Po (translated)', $singular);

		$coreSingular = $this->_singularFromCore();
		$this->assertNotEquals('Po (from core translated)', $coreSingular);

		$corePlurals = $this->_pluralFromCore();
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
 * @return void
 */
	public function testPluginTranslation() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));

		Configure::write('Config.language', 'po');
		$singular = $this->_domainSingular();
		$this->assertEquals('Plural Rule 1 (from plugin)', $singular);

		$plurals = $this->_domainPlural();
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
 * Test that Configure::read('I18n.preferApp') will prefer app.
 *
 * @return void
 */
	public function testPluginTranslationPreferApp() {
		// Reset internally stored entries
		I18n::clear();
		Cache::clear(false, '_cake_core_');

		Configure::write('I18n.preferApp', true);

		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));

		Configure::write('Config.language', 'po');
		$singular = $this->_domainSingular();
		$this->assertEquals('Plural Rule 1', $singular);

		$plurals = $this->_domainPlural();
		$this->assertTrue(in_array('0 = 0 or > 1', $plurals));
	}

/**
 * testPoMultipleLineTranslation method
 *
 * @return void
 */
	public function testPoMultipleLineTranslation() {
		Configure::write('Config.language', 'po');

		$string = "This is a multiline translation\n";
		$string .= "broken up over multiple lines.\n";
		$string .= "This is the third line.\n";
		$string .= "This is the forth line.";
		$result = __($string);

		$expected = "This is a multiline translation\n";
		$expected .= "broken up over multiple lines.\n";
		$expected .= "This is the third line.\n";
		$expected .= "This is the forth line. (translated)";
		$this->assertEquals($expected, $result);

		// Windows Newline is \r\n
		$string = "This is a multiline translation\r\n";
		$string .= "broken up over multiple lines.\r\n";
		$string .= "This is the third line.\r\n";
		$string .= "This is the forth line.";
		$result = __($string);
		$this->assertEquals($expected, $result);

		$singular = "valid\nsecond line";
		$plural = "valids\nsecond line";

		$result = __n($singular, $plural, 1);
		$expected = "v\nsecond line";
		$this->assertEquals($expected, $result);

		$result = __n($singular, $plural, 2);
		$expected = "vs\nsecond line";
		$this->assertEquals($expected, $result);

		$string = "This is a multiline translation\n";
		$string .= "broken up over multiple lines.\n";
		$string .= "This is the third line.\n";
		$string .= "This is the forth line.";

		$singular = "%d = 1\n" . $string;
		$plural = "%d = 0 or > 1\n" . $string;

		$result = __n($singular, $plural, 1);
		$expected = "%d is 1\n" . $string;
		$this->assertEquals($expected, $result);

		$result = __n($singular, $plural, 2);
		$expected = "%d is 2-4\n" . $string;
		$this->assertEquals($expected, $result);

		// Windows Newline is \r\n
		$string = "This is a multiline translation\r\n";
		$string .= "broken up over multiple lines.\r\n";
		$string .= "This is the third line.\r\n";
		$string .= "This is the forth line.";

		$singular = "%d = 1\r\n" . $string;
		$plural = "%d = 0 or > 1\r\n" . $string;

		$result = __n($singular, $plural, 1);
		$expected = "%d is 1\n" . str_replace("\r\n", "\n", $string);
		$this->assertEquals($expected, $result);

		$result = __n($singular, $plural, 2);
		$expected = "%d is 2-4\n" . str_replace("\r\n", "\n", $string);
		$this->assertEquals($expected, $result);
	}

/**
 * testPoNoTranslationNeeded method
 *
 * @return void
 */
	public function testPoNoTranslationNeeded() {
		Configure::write('Config.language', 'po');
		$result = __('No Translation needed');
		$this->assertEquals('No Translation needed', $result);
	}

/**
 * testPoQuotedString method
 *
 * @return void
 */
	public function testPoQuotedString() {
		Configure::write('Config.language', 'po');
		$expected = 'this is a "quoted string" (translated)';
		$this->assertEquals($expected, __('this is a "quoted string"'));
	}

/**
 * testFloatValue method
 *
 * @return void
 */
	public function testFloatValue() {
		Configure::write('Config.language', 'rule_9_po');

		$result = __n('%d = 1', '%d = 0 or > 1', (float)1);
		$expected = '%d is 1 (translated)';
		$this->assertEquals($expected, $result);

		$result = __n('%d = 1', '%d = 0 or > 1', (float)2);
		$expected = "%d ends in 2-4, not 12-14 (translated)";
		$this->assertEquals($expected, $result);

		$result = __n('%d = 1', '%d = 0 or > 1', (float)5);
		$expected = "%d everything else (translated)";
		$this->assertEquals($expected, $result);
	}

/**
 * testCategory method
 *
 * @return void
 */
	public function testCategory() {
		Configure::write('Config.language', 'po');
		// Test with default (I18n constant) category.
		$category = $this->_category();
		$this->assertEquals('Monetary Po (translated)', $category);
		// Test with category number represenation.
		$category = $this->_category(3);
		$this->assertEquals('Monetary Po (translated)', $category);
	}

/**
 * testPluginCategory method
 *
 * @return void
 */
	public function testPluginCategory() {
		Configure::write('Config.language', 'po');

		$singular = $this->_domainCategorySingular();
		$this->assertEquals('Monetary Plural Rule 1 (from plugin)', $singular);

		$plurals = $this->_domainCategoryPlural();
		$this->assertTrue(in_array('Monetary 0 = 0 or > 1 (from plugin)', $plurals));
		$this->assertTrue(in_array('Monetary 1 = 1 (from plugin)', $plurals));
	}

/**
 * testCategoryThenSingular method
 *
 * @return void
 */
	public function testCategoryThenSingular() {
		Configure::write('Config.language', 'po');
		$category = $this->_category();
		$this->assertEquals('Monetary Po (translated)', $category);

		$singular = $this->_singular();
		$this->assertEquals('Po (translated)', $singular);
	}

/**
 * testTimeDefinition method
 *
 * @return void
 */
	public function testTimeDefinition() {
		Configure::write('Config.language', 'po');
		$result = __c('d_fmt', 5);
		$expected = '%m/%d/%Y';
		$this->assertEquals($expected, $result);

		$result = __c('am_pm', 5);
		$expected = array('AM', 'PM');
		$this->assertEquals($expected, $result);

		$result = __c('abmon', 5);
		$expected = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		$this->assertEquals($expected, $result);
	}

/**
 * testTimeDefinitionJapanese method
 *
 * @return void
 */
	public function testTimeDefinitionJapanese() {
		Configure::write('Config.language', 'ja_jp');
		$result = __c('d_fmt', 5);

		$expected = "%Y年%m月%d日";

		$this->assertEquals($expected, $result);

		$result = __c('am_pm', 5);
		$expected = array("午前", "午後");
		$this->assertEquals($expected, $result);

		$result = __c('abmon', 5);
		$expected = array(" 1月", " 2月", " 3月", " 4月", " 5月", " 6月", " 7月", " 8月", " 9月", "10月", "11月", "12月");
		$this->assertEquals($expected, $result);
	}

/**
 * testTranslateLanguageParam method
 *
 * @return void
 */
	public function testTranslateLanguageParam() {
		Configure::write('Config.language', 'rule_0_po');

		$result = I18n::translate('Plural Rule 1', null, null, I18n::LC_MESSAGES);
		$expected = 'Plural Rule 0 (translated)';
		$this->assertEquals($expected, $result);

		$result = I18n::translate('Plural Rule 1', null, null, I18n::LC_MESSAGES, null, 'rule_1_po');
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEquals($expected, $result);
	}

/**
 * Test that the '' domain causes exceptions.
 *
 * @expectedException CakeException
 * @return void
 */
	public function testTranslateEmptyDomain() {
		I18n::translate('Plural Rule 1', null, '');
	}

/**
 * testLoadLocaleDefinition method
 *
 * @return void
 */
	public function testLoadLocaleDefinition() {
		$path = current(App::path('locales'));
		$result = I18n::loadLocaleDefinition($path . 'nld' . DS . 'LC_TIME');
		$expected = array('zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag');
		$this->assertSame($expected, $result['day']);
	}

/**
 * Test basic context support
 *
 * @return void
 */
	public function testContext() {
		Configure::write('Config.language', 'nld');

		$this->assertSame("brief", __x('mail', 'letter'));
		$this->assertSame("letter", __x('character', 'letter'));
		$this->assertSame("bal", __x('spherical object', 'ball'));
		$this->assertSame("danspartij", __x('social gathering', 'ball'));
		$this->assertSame("balans", __('balance'));
		$this->assertSame("saldo", __x('money', 'balance'));
	}

/**
 * Test basic context support using mo files.
 *
 * @return void
 */
	public function testContextMoFile() {
		Configure::write('Config.language', 'nld_mo');

		$this->assertSame("brief", __x('mail', 'letter'));
		$this->assertSame("letter", __x('character', 'letter'));
		$this->assertSame("bal", __x('spherical object', 'ball'));
		$this->assertSame("danspartij", __x('social gathering', 'ball'));
		$this->assertSame("balans", __('balance'));
		$this->assertSame("saldo", __x('money', 'balance'));

		// MO file is sorted by msgid, 'zoo' should be last
		$this->assertSame("dierentuin", __('zoo'));
	}

/**
 * Singular method
 *
 * @return void
 */
	protected function _domainCategorySingular($domain = 'test_plugin', $category = 3) {
		$singular = __dc($domain, 'Plural Rule 1', $category);
		return $singular;
	}

/**
 * Plural method
 *
 * @return void
 */
	protected function _domainCategoryPlural($domain = 'test_plugin', $category = 3) {
		$plurals = array();
		for ($number = 0; $number <= 25; $number++) {
			$plurals[] = sprintf(__dcn($domain, '%d = 1', '%d = 0 or > 1', (float)$number, $category), (float)$number);
		}
		return $plurals;
	}

/**
 * Singular method
 *
 * @return void
 */
	protected function _domainSingular($domain = 'test_plugin') {
		$singular = __d($domain, 'Plural Rule 1');
		return $singular;
	}

/**
 * Plural method
 *
 * @return void
 */
	protected function _domainPlural($domain = 'test_plugin') {
		$plurals = array();
		for ($number = 0; $number <= 25; $number++) {
			$plurals[] = sprintf(__dn($domain, '%d = 1', '%d = 0 or > 1', (float)$number), (float)$number);
		}
		return $plurals;
	}

/**
 * category method
 *
 * @return void
 */
	protected function _category($category = I18n::LC_MONETARY) {
		$singular = __c('Plural Rule 1', $category);
		return $singular;
	}

/**
 * Singular method
 *
 * @return void
 */
	protected function _singular() {
		$singular = __('Plural Rule 1');
		return $singular;
	}

/**
 * Plural method
 *
 * @param int $upTo For numbers upto (default to 25)
 * @return void
 */
	protected function _plural($upTo = 25) {
		$plurals = array();
		for ($number = 0; $number <= $upTo; $number++) {
			$plurals[] = sprintf(__n('%d = 1', '%d = 0 or > 1', (float)$number), (float)$number);
		}
		return $plurals;
	}

/**
 * singularFromCore method
 *
 * @return void
 */
	protected function _singularFromCore() {
		$singular = __('Plural Rule 1 (from core)');
		return $singular;
	}

/**
 * pluralFromCore method
 *
 * @param int $upTo For numbers upto (default to 25)
 * @return void
 */
	protected function _pluralFromCore($upTo = 25) {
		$plurals = array();
		for ($number = 0; $number <= $upTo; $number++) {
			$plurals[] = sprintf(__n('%d = 1 (from core)', '%d = 0 or > 1 (from core)', (float)$number), (float)$number);
		}
		return $plurals;
	}
}
