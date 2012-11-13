<?php
/**
 * L10nTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.I18n
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('L10n', 'I18n');

/**
 * L10nTest class
 *
 * @package       Cake.Test.Case.I18n
 */
class L10nTest extends CakeTestCase {

/**
 * testGet method
 *
 * @return void
 */
	public function testGet() {
		$localize = new L10n();

		// Catalog Entry
		$lang = $localize->get('en');

		$this->assertEquals('en', $lang);
		$this->assertEquals('English', $localize->language);
		$this->assertEquals(array('eng', 'eng'), $localize->languagePath);
		$this->assertEquals('eng', $localize->locale);

		// Map Entry
		$localize->get('eng');

		$this->assertEquals('English', $localize->language);
		$this->assertEquals(array('eng', 'eng'), $localize->languagePath);
		$this->assertEquals('eng', $localize->locale);

		// Catalog Entry
		$localize->get('en-ca');

		$this->assertEquals('English (Canadian)', $localize->language);
		$this->assertEquals(array('en_ca', 'eng'), $localize->languagePath);
		$this->assertEquals('en_ca', $localize->locale);

		// Default Entry
		define('DEFAULT_LANGUAGE', 'en-us');

		$lang = $localize->get('use_default');

		$this->assertEquals('en-us', $lang);
		$this->assertEquals('English (United States)', $localize->language);
		$this->assertEquals(array('en_us', 'eng'), $localize->languagePath);
		$this->assertEquals('en_us', $localize->locale);

		$localize->get('es');
		$localize->get('');
		$this->assertEquals('en-us', $localize->lang);

		// Using $this->default
		$localize = new L10n();

		$localize->get('use_default');
		$this->assertEquals('English (United States)', $localize->language);
		$this->assertEquals(array('en_us', 'eng', 'eng'), $localize->languagePath);
		$this->assertEquals('en_us', $localize->locale);
	}

/**
 * testGetAutoLanguage method
 *
 * @return void
 */
	public function testGetAutoLanguage() {
		$serverBackup = $_SERVER;
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'inexistent,en-ca';

		$localize = new L10n();
		$lang = $localize->get();

		$this->assertEquals('en-ca', $lang);
		$this->assertEquals('English (Canadian)', $localize->language);
		$this->assertEquals(array('en_ca', 'eng', 'eng'), $localize->languagePath);
		$this->assertEquals('en_ca', $localize->locale);

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es_mx';
		$lang = $localize->get();

		$this->assertEquals('es-mx', $lang);
		$this->assertEquals('Spanish (Mexican)', $localize->language);
		$this->assertEquals(array('es_mx', 'spa', 'eng'), $localize->languagePath);
		$this->assertEquals('es_mx', $localize->locale);

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en_xy,en_ca';
		$localize->get();

		$this->assertEquals('English', $localize->language);
		$this->assertEquals(array('eng', 'eng', 'eng'), $localize->languagePath);
		$this->assertEquals('eng', $localize->locale);

		$_SERVER = $serverBackup;
	}

/**
 * testMap method
 *
 * @return void
 */
	public function testMap() {
		$localize = new L10n();

		$result = $localize->map(array('afr', 'af'));
		$expected = array('afr' => 'af', 'af' => 'afr');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('sqi', 'sq'));
		$expected = array('sqi' => 'sq', 'sq' => 'sqi');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('alb', 'sq'));
		$expected = array('alb' => 'sq', 'sq' => 'sqi');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('ara', 'ar'));
		$expected = array('ara' => 'ar', 'ar' => 'ara');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('hye', 'hy'));
		$expected = array('hye' => 'hy', 'hy' => 'hye');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('eus', 'eu'));
		$expected = array('eus' => 'eu', 'eu' => 'eus');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('baq', 'eu'));
		$expected = array('baq' => 'eu', 'eu' => 'eus');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('bos', 'bs'));
		$expected = array('bos' => 'bs', 'bs' => 'bos');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('bul', 'bg'));
		$expected = array('bul' => 'bg', 'bg' => 'bul');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('bel', 'be'));
		$expected = array('bel' => 'be', 'be' => 'bel');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('cat', 'ca'));
		$expected = array('cat' => 'ca', 'ca' => 'cat');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('chi', 'zh'));
		$expected = array('chi' => 'zh', 'zh' => 'zho');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('zho', 'zh'));
		$expected = array('zho' => 'zh', 'zh' => 'zho');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('hrv', 'hr'));
		$expected = array('hrv' => 'hr', 'hr' => 'hrv');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('ces', 'cs'));
		$expected = array('ces' => 'cs', 'cs' => 'ces');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('cze', 'cs'));
		$expected = array('cze' => 'cs', 'cs' => 'ces');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('dan', 'da'));
		$expected = array('dan' => 'da', 'da' => 'dan');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('dut', 'nl'));
		$expected = array('dut' => 'nl', 'nl' => 'nld');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('nld', 'nl'));
		$expected = array('nld' => 'nl', 'nl' => 'nld');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('nld'));
		$expected = array('nld' => 'nl');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('dut'));
		$expected = array('dut' => 'nl');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('eng', 'en'));
		$expected = array('eng' => 'en', 'en' => 'eng');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('est', 'et'));
		$expected = array('est' => 'et', 'et' => 'est');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('fao', 'fo'));
		$expected = array('fao' => 'fo', 'fo' => 'fao');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('fas', 'fa'));
		$expected = array('fas' => 'fa', 'fa' => 'fas');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('per', 'fa'));
		$expected = array('per' => 'fa', 'fa' => 'fas');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('fin', 'fi'));
		$expected = array('fin' => 'fi', 'fi' => 'fin');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('fra', 'fr'));
		$expected = array('fra' => 'fr', 'fr' => 'fra');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('fre', 'fr'));
		$expected = array('fre' => 'fr', 'fr' => 'fra');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('gla', 'gd'));
		$expected = array('gla' => 'gd', 'gd' => 'gla');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('glg', 'gl'));
		$expected = array('glg' => 'gl', 'gl' => 'glg');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('deu', 'de'));
		$expected = array('deu' => 'de', 'de' => 'deu');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('ger', 'de'));
		$expected = array('ger' => 'de', 'de' => 'deu');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('ell', 'el'));
		$expected = array('ell' => 'el', 'el' => 'gre');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('gre', 'el'));
		$expected = array('gre' => 'el', 'el' => 'gre');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('heb', 'he'));
		$expected = array('heb' => 'he', 'he' => 'heb');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('hin', 'hi'));
		$expected = array('hin' => 'hi', 'hi' => 'hin');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('hun', 'hu'));
		$expected = array('hun' => 'hu', 'hu' => 'hun');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('ice', 'is'));
		$expected = array('ice' => 'is', 'is' => 'isl');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('isl', 'is'));
		$expected = array('isl' => 'is', 'is' => 'isl');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('ind', 'id'));
		$expected = array('ind' => 'id', 'id' => 'ind');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('gle', 'ga'));
		$expected = array('gle' => 'ga', 'ga' => 'gle');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('ita', 'it'));
		$expected = array('ita' => 'it', 'it' => 'ita');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('jpn', 'ja'));
		$expected = array('jpn' => 'ja', 'ja' => 'jpn');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('kor', 'ko'));
		$expected = array('kor' => 'ko', 'ko' => 'kor');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('lav', 'lv'));
		$expected = array('lav' => 'lv', 'lv' => 'lav');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('lit', 'lt'));
		$expected = array('lit' => 'lt', 'lt' => 'lit');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('mac', 'mk'));
		$expected = array('mac' => 'mk', 'mk' => 'mkd');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('mkd', 'mk'));
		$expected = array('mkd' => 'mk', 'mk' => 'mkd');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('may', 'ms'));
		$expected = array('may' => 'ms', 'ms' => 'msa');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('msa', 'ms'));
		$expected = array('msa' => 'ms', 'ms' => 'msa');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('mlt', 'mt'));
		$expected = array('mlt' => 'mt', 'mt' => 'mlt');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('nor', 'no'));
		$expected = array('nor' => 'no', 'no' => 'nor');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('nob', 'nb'));
		$expected = array('nob' => 'nb', 'nb' => 'nob');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('nno', 'nn'));
		$expected = array('nno' => 'nn', 'nn' => 'nno');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('pol', 'pl'));
		$expected = array('pol' => 'pl', 'pl' => 'pol');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('por', 'pt'));
		$expected = array('por' => 'pt', 'pt' => 'por');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('roh', 'rm'));
		$expected = array('roh' => 'rm', 'rm' => 'roh');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('ron', 'ro'));
		$expected = array('ron' => 'ro', 'ro' => 'ron');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('rum', 'ro'));
		$expected = array('rum' => 'ro', 'ro' => 'ron');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('rus', 'ru'));
		$expected = array('rus' => 'ru', 'ru' => 'rus');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('smi', 'sz'));
		$expected = array('smi' => 'sz', 'sz' => 'smi');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('srp', 'sr'));
		$expected = array('srp' => 'sr', 'sr' => 'srp');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('slk', 'sk'));
		$expected = array('slk' => 'sk', 'sk' => 'slk');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('slo', 'sk'));
		$expected = array('slo' => 'sk', 'sk' => 'slk');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('slv', 'sl'));
		$expected = array('slv' => 'sl', 'sl' => 'slv');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('wen', 'sb'));
		$expected = array('wen' => 'sb', 'sb' => 'wen');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('spa', 'es'));
		$expected = array('spa' => 'es', 'es' => 'spa');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('swe', 'sv'));
		$expected = array('swe' => 'sv', 'sv' => 'swe');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('tha', 'th'));
		$expected = array('tha' => 'th', 'th' => 'tha');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('tso', 'ts'));
		$expected = array('tso' => 'ts', 'ts' => 'tso');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('tsn', 'tn'));
		$expected = array('tsn' => 'tn', 'tn' => 'tsn');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('tur', 'tr'));
		$expected = array('tur' => 'tr', 'tr' => 'tur');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('ukr', 'uk'));
		$expected = array('ukr' => 'uk', 'uk' => 'ukr');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('urd', 'ur'));
		$expected = array('urd' => 'ur', 'ur' => 'urd');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('ven', 've'));
		$expected = array('ven' => 've', 've' => 'ven');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('vie', 'vi'));
		$expected = array('vie' => 'vi', 'vi' => 'vie');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('xho', 'xh'));
		$expected = array('xho' => 'xh', 'xh' => 'xho');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('cy', 'cym'));
		$expected = array('cym' => 'cy', 'cy' => 'cym');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('yid', 'yi'));
		$expected = array('yid' => 'yi', 'yi' => 'yid');
		$this->assertEquals($expected, $result);

		$result = $localize->map(array('zul', 'zu'));
		$expected = array('zul' => 'zu', 'zu' => 'zul');
		$this->assertEquals($expected, $result);
	}

/**
 * testCatalog method
 *
 * @return void
 */
	public function testCatalog() {
		$localize = new L10n();

		$result = $localize->catalog(array('af'));
		$expected = array(
			'af' => array('language' => 'Afrikaans', 'locale' => 'afr', 'localeFallback' => 'afr', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('ar', 'ar-ae', 'ar-bh', 'ar-dz', 'ar-eg', 'ar-iq', 'ar-jo', 'ar-kw', 'ar-lb', 'ar-ly', 'ar-ma',
			'ar-om', 'ar-qa', 'ar-sa', 'ar-sy', 'ar-tn', 'ar-ye'));
		$expected = array(
			'ar' => array('language' => 'Arabic', 'locale' => 'ara', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-ae' => array('language' => 'Arabic (U.A.E.)', 'locale' => 'ar_ae', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-bh' => array('language' => 'Arabic (Bahrain)', 'locale' => 'ar_bh', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-dz' => array('language' => 'Arabic (Algeria)', 'locale' => 'ar_dz', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-eg' => array('language' => 'Arabic (Egypt)', 'locale' => 'ar_eg', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-iq' => array('language' => 'Arabic (Iraq)', 'locale' => 'ar_iq', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-jo' => array('language' => 'Arabic (Jordan)', 'locale' => 'ar_jo', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-kw' => array('language' => 'Arabic (Kuwait)', 'locale' => 'ar_kw', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-lb' => array('language' => 'Arabic (Lebanon)', 'locale' => 'ar_lb', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-ly' => array('language' => 'Arabic (Libya)', 'locale' => 'ar_ly', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-ma' => array('language' => 'Arabic (Morocco)', 'locale' => 'ar_ma', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-om' => array('language' => 'Arabic (Oman)', 'locale' => 'ar_om', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-qa' => array('language' => 'Arabic (Qatar)', 'locale' => 'ar_qa', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-sa' => array('language' => 'Arabic (Saudi Arabia)', 'locale' => 'ar_sa', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-sy' => array('language' => 'Arabic (Syria)', 'locale' => 'ar_sy', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-tn' => array('language' => 'Arabic (Tunisia)', 'locale' => 'ar_tn', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'ar-ye' => array('language' => 'Arabic (Yemen)', 'locale' => 'ar_ye', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('be'));
		$expected = array(
			'be' => array('language' => 'Byelorussian', 'locale' => 'bel', 'localeFallback' => 'bel', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('bg'));
		$expected = array(
			'bg' => array('language' => 'Bulgarian', 'locale' => 'bul', 'localeFallback' => 'bul', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('bs'));
		$expected = array(
			'bs' => array('language' => 'Bosnian', 'locale' => 'bos', 'localeFallback' => 'bos', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('ca'));
		$expected = array(
			'ca' => array('language' => 'Catalan', 'locale' => 'cat', 'localeFallback' => 'cat', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('cs'));
		$expected = array(
			'cs' => array('language' => 'Czech', 'locale' => 'ces', 'localeFallback' => 'ces', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('da'));
		$expected = array(
			'da' => array('language' => 'Danish', 'locale' => 'dan', 'localeFallback' => 'dan', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('de', 'de-at', 'de-ch', 'de-de', 'de-li', 'de-lu'));
		$expected = array(
			'de' => array('language' => 'German (Standard)', 'locale' => 'deu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'de-at' => array('language' => 'German (Austria)', 'locale' => 'de_at', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'de-ch' => array('language' => 'German (Swiss)', 'locale' => 'de_ch', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'de-de' => array('language' => 'German (Germany)', 'locale' => 'de_de', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'de-li' => array('language' => 'German (Liechtenstein)', 'locale' => 'de_li', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'de-lu' => array('language' => 'German (Luxembourg)', 'locale' => 'de_lu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('e', 'el'));
		$expected = array(
			'e' => array('language' => 'Greek', 'locale' => 'gre', 'localeFallback' => 'gre', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'el' => array('language' => 'Greek', 'locale' => 'gre', 'localeFallback' => 'gre', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('en', 'en-au', 'en-bz', 'en-ca', 'en-gb', 'en-ie', 'en-jm', 'en-nz', 'en-tt', 'en-us', 'en-za'));
		$expected = array(
			'en' => array('language' => 'English', 'locale' => 'eng', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'en-au' => array('language' => 'English (Australian)', 'locale' => 'en_au', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'en-bz' => array('language' => 'English (Belize)', 'locale' => 'en_bz', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'en-ca' => array('language' => 'English (Canadian)', 'locale' => 'en_ca', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'en-gb' => array('language' => 'English (British)', 'locale' => 'en_gb', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'en-ie' => array('language' => 'English (Ireland)', 'locale' => 'en_ie', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'en-jm' => array('language' => 'English (Jamaica)', 'locale' => 'en_jm', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'en-nz' => array('language' => 'English (New Zealand)', 'locale' => 'en_nz', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'en-tt' => array('language' => 'English (Trinidad)', 'locale' => 'en_tt', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'en-us' => array('language' => 'English (United States)', 'locale' => 'en_us', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'en-za' => array('language' => 'English (South Africa)', 'locale' => 'en_za', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('es', 'es-ar', 'es-bo', 'es-cl', 'es-co', 'es-cr', 'es-do', 'es-ec', 'es-es', 'es-gt', 'es-hn',
			'es-mx', 'es-ni', 'es-pa', 'es-pe', 'es-pr', 'es-py', 'es-sv', 'es-uy', 'es-ve'));
		$expected = array(
			'es' => array('language' => 'Spanish (Spain - Traditional)', 'locale' => 'spa', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-ar' => array('language' => 'Spanish (Argentina)', 'locale' => 'es_ar', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-bo' => array('language' => 'Spanish (Bolivia)', 'locale' => 'es_bo', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-cl' => array('language' => 'Spanish (Chile)', 'locale' => 'es_cl', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-co' => array('language' => 'Spanish (Colombia)', 'locale' => 'es_co', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-cr' => array('language' => 'Spanish (Costa Rica)', 'locale' => 'es_cr', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-do' => array('language' => 'Spanish (Dominican Republic)', 'locale' => 'es_do', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-ec' => array('language' => 'Spanish (Ecuador)', 'locale' => 'es_ec', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-es' => array('language' => 'Spanish (Spain)', 'locale' => 'es_es', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-gt' => array('language' => 'Spanish (Guatemala)', 'locale' => 'es_gt', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-hn' => array('language' => 'Spanish (Honduras)', 'locale' => 'es_hn', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-mx' => array('language' => 'Spanish (Mexican)', 'locale' => 'es_mx', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-ni' => array('language' => 'Spanish (Nicaragua)', 'locale' => 'es_ni', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-pa' => array('language' => 'Spanish (Panama)', 'locale' => 'es_pa', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-pe' => array('language' => 'Spanish (Peru)', 'locale' => 'es_pe', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-pr' => array('language' => 'Spanish (Puerto Rico)', 'locale' => 'es_pr', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-py' => array('language' => 'Spanish (Paraguay)', 'locale' => 'es_py', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-sv' => array('language' => 'Spanish (El Salvador)', 'locale' => 'es_sv', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-uy' => array('language' => 'Spanish (Uruguay)', 'locale' => 'es_uy', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-ve' => array('language' => 'Spanish (Venezuela)', 'locale' => 'es_ve', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('et'));
		$expected = array(
			'et' => array('language' => 'Estonian', 'locale' => 'est', 'localeFallback' => 'est', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('eu'));
		$expected = array(
			'eu' => array('language' => 'Basque', 'locale' => 'eus', 'localeFallback' => 'eus', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('fa'));
		$expected = array(
			'fa' => array('language' => 'Farsi', 'locale' => 'per', 'localeFallback' => 'per', 'charset' => 'utf-8', 'direction' => 'rtl')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('fi'));
		$expected = array(
			'fi' => array('language' => 'Finnish', 'locale' => 'fin', 'localeFallback' => 'fin', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('fo'));
		$expected = array(
			'fo' => array('language' => 'Faeroese', 'locale' => 'fao', 'localeFallback' => 'fao', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('fr', 'fr-be', 'fr-ca', 'fr-ch', 'fr-fr', 'fr-lu'));
		$expected = array(
			'fr' => array('language' => 'French (Standard)', 'locale' => 'fra', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'fr-be' => array('language' => 'French (Belgium)', 'locale' => 'fr_be', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'fr-ca' => array('language' => 'French (Canadian)', 'locale' => 'fr_ca', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'fr-ch' => array('language' => 'French (Swiss)', 'locale' => 'fr_ch', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'fr-fr' => array('language' => 'French (France)', 'locale' => 'fr_fr', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'fr-lu' => array('language' => 'French (Luxembourg)', 'locale' => 'fr_lu', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('ga'));
		$expected = array(
			'ga' => array('language' => 'Irish', 'locale' => 'gle', 'localeFallback' => 'gle', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('gd', 'gd-ie'));
		$expected = array(
			'gd' => array('language' => 'Gaelic (Scots)', 'locale' => 'gla', 'localeFallback' => 'gla', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'gd-ie' => array('language' => 'Gaelic (Irish)', 'locale' => 'gd_ie', 'localeFallback' => 'gla', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('gl'));
		$expected = array(
			'gl' => array('language' => 'Galician', 'locale' => 'glg', 'localeFallback' => 'glg', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('he'));
		$expected = array(
			'he' => array('language' => 'Hebrew', 'locale' => 'heb', 'localeFallback' => 'heb', 'charset' => 'utf-8', 'direction' => 'rtl')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('hi'));
		$expected = array(
			'hi' => array('language' => 'Hindi', 'locale' => 'hin', 'localeFallback' => 'hin', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('hr'));
		$expected = array(
			'hr' => array('language' => 'Croatian', 'locale' => 'hrv', 'localeFallback' => 'hrv', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('hu'));
		$expected = array(
			'hu' => array('language' => 'Hungarian', 'locale' => 'hun', 'localeFallback' => 'hun', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('hy'));
		$expected = array(
			'hy' => array('language' => 'Armenian - Armenia', 'locale' => 'hye', 'localeFallback' => 'hye', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('id', 'in'));
		$expected = array(
			'id' => array('language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'in' => array('language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('is'));
		$expected = array(
			'is' => array('language' => 'Icelandic', 'locale' => 'isl', 'localeFallback' => 'isl', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('it', 'it-ch'));
		$expected = array(
			'it' => array('language' => 'Italian', 'locale' => 'ita', 'localeFallback' => 'ita', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'it-ch' => array('language' => 'Italian (Swiss) ', 'locale' => 'it_ch', 'localeFallback' => 'ita', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('ja'));
		$expected = array(
			'ja' => array('language' => 'Japanese', 'locale' => 'jpn', 'localeFallback' => 'jpn', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('ko', 'ko-kp', 'ko-kr'));
		$expected = array(
			'ko' => array('language' => 'Korean', 'locale' => 'kor', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'),
			'ko-kp' => array('language' => 'Korea (North)', 'locale' => 'ko_kp', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'),
			'ko-kr' => array('language' => 'Korea (South)', 'locale' => 'ko_kr', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('koi8-r', 'ru', 'ru-mo'));
		$expected = array(
			'koi8-r' => array('language' => 'Russian', 'locale' => 'koi8_r', 'localeFallback' => 'rus', 'charset' => 'koi8-r', 'direction' => 'ltr'),
			'ru' => array('language' => 'Russian', 'locale' => 'rus', 'localeFallback' => 'rus', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'ru-mo' => array('language' => 'Russian (Moldavia)', 'locale' => 'ru_mo', 'localeFallback' => 'rus', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('lt'));
		$expected = array(
			'lt' => array('language' => 'Lithuanian', 'locale' => 'lit', 'localeFallback' => 'lit', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('lv'));
		$expected = array(
			'lv' => array('language' => 'Latvian', 'locale' => 'lav', 'localeFallback' => 'lav', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('mk', 'mk-mk'));
		$expected = array(
			'mk' => array('language' => 'FYRO Macedonian', 'locale' => 'mk', 'localeFallback' => 'mkd', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'mk-mk' => array('language' => 'Macedonian', 'locale' => 'mk_mk', 'localeFallback' => 'mkd', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('ms'));
		$expected = array(
			'ms' => array('language' => 'Malaysian', 'locale' => 'msa', 'localeFallback' => 'msa', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('mt'));
		$expected = array(
			'mt' => array('language' => 'Maltese', 'locale' => 'mlt', 'localeFallback' => 'mlt', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('n', 'nl', 'nl-be'));
		$expected = array(
			'n' => array('language' => 'Dutch (Standard)', 'locale' => 'nld', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'nl' => array('language' => 'Dutch (Standard)', 'locale' => 'nld', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'nl-be' => array('language' => 'Dutch (Belgium)', 'locale' => 'nl_be', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog('nl');
		$expected = array('language' => 'Dutch (Standard)', 'locale' => 'nld', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr');
		$this->assertEquals($expected, $result);

		$result = $localize->catalog('nld');
		$expected = array('language' => 'Dutch (Standard)', 'locale' => 'nld', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr');
		$this->assertEquals($expected, $result);

		$result = $localize->catalog('dut');
		$expected = array('language' => 'Dutch (Standard)', 'locale' => 'nld', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr');
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('nb'));
		$expected = array(
			'nb' => array('language' => 'Norwegian Bokmal', 'locale' => 'nob', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('nn', 'no'));
		$expected = array(
			'nn' => array('language' => 'Norwegian Nynorsk', 'locale' => 'nno', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'no' => array('language' => 'Norwegian', 'locale' => 'nor', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('p', 'pl'));
		$expected = array(
			'p' => array('language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'pl' => array('language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('pt', 'pt-br'));
		$expected = array(
			'pt' => array('language' => 'Portuguese (Portugal)', 'locale' => 'por', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'pt-br' => array('language' => 'Portuguese (Brazil)', 'locale' => 'pt_br', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('rm'));
		$expected = array(
			'rm' => array('language' => 'Rhaeto-Romanic', 'locale' => 'roh', 'localeFallback' => 'roh', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('ro', 'ro-mo'));
		$expected = array(
			'ro' => array('language' => 'Romanian', 'locale' => 'ron', 'localeFallback' => 'ron', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'ro-mo' => array('language' => 'Romanian (Moldavia)', 'locale' => 'ro_mo', 'localeFallback' => 'ron', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('sb'));
		$expected = array(
			'sb' => array('language' => 'Sorbian', 'locale' => 'wen', 'localeFallback' => 'wen', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('sk'));
		$expected = array(
			'sk' => array('language' => 'Slovak', 'locale' => 'slk', 'localeFallback' => 'slk', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('sl'));
		$expected = array(
			'sl' => array('language' => 'Slovenian', 'locale' => 'slv', 'localeFallback' => 'slv', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('sq'));
		$expected = array(
			'sq' => array('language' => 'Albanian', 'locale' => 'sqi', 'localeFallback' => 'sqi', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('sr'));
		$expected = array(
			'sr' => array('language' => 'Serbian', 'locale' => 'srp', 'localeFallback' => 'srp', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('sv', 'sv-fi'));
		$expected = array(
			'sv' => array('language' => 'Swedish', 'locale' => 'swe', 'localeFallback' => 'swe', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'sv-fi' => array('language' => 'Swedish (Finland)', 'locale' => 'sv_fi', 'localeFallback' => 'swe', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('sx'));
		$expected = array(
			'sx' => array('language' => 'Sutu', 'locale' => 'sx', 'localeFallback' => 'sx', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('sz'));
		$expected = array(
			'sz' => array('language' => 'Sami (Lappish)', 'locale' => 'smi', 'localeFallback' => 'smi', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('th'));
		$expected = array(
			'th' => array('language' => 'Thai', 'locale' => 'tha', 'localeFallback' => 'tha', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('tn'));
		$expected = array(
			'tn' => array('language' => 'Tswana', 'locale' => 'tsn', 'localeFallback' => 'tsn', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('tr'));
		$expected = array(
			'tr' => array('language' => 'Turkish', 'locale' => 'tur', 'localeFallback' => 'tur', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('ts'));
		$expected = array(
			'ts' => array('language' => 'Tsonga', 'locale' => 'tso', 'localeFallback' => 'tso', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('uk'));
		$expected = array(
			'uk' => array('language' => 'Ukrainian', 'locale' => 'ukr', 'localeFallback' => 'ukr', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('ur'));
		$expected = array(
			'ur' => array('language' => 'Urdu', 'locale' => 'urd', 'localeFallback' => 'urd', 'charset' => 'utf-8', 'direction' => 'rtl')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('ve'));
		$expected = array(
			've' => array('language' => 'Venda', 'locale' => 'ven', 'localeFallback' => 'ven', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('vi'));
		$expected = array(
			'vi' => array('language' => 'Vietnamese', 'locale' => 'vie', 'localeFallback' => 'vie', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('cy'));
		$expected = array(
			'cy' => array('language' => 'Welsh', 'locale' => 'cym', 'localeFallback' => 'cym', 'charset' => 'utf-8',
			'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('xh'));
		$expected = array(
			'xh' => array('language' => 'Xhosa', 'locale' => 'xho', 'localeFallback' => 'xho', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('yi'));
		$expected = array(
			'yi' => array('language' => 'Yiddish', 'locale' => 'yid', 'localeFallback' => 'yid', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('zh', 'zh-cn', 'zh-hk', 'zh-sg', 'zh-tw'));
		$expected = array(
			'zh' => array('language' => 'Chinese', 'locale' => 'zho', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'zh-cn' => array('language' => 'Chinese (PRC)', 'locale' => 'zh_cn', 'localeFallback' => 'zho', 'charset' => 'GB2312', 'direction' => 'ltr'),
			'zh-hk' => array('language' => 'Chinese (Hong Kong)', 'locale' => 'zh_hk', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'zh-sg' => array('language' => 'Chinese (Singapore)', 'locale' => 'zh_sg', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'zh-tw' => array('language' => 'Chinese (Taiwan)', 'locale' => 'zh_tw', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('zu'));
		$expected = array(
			'zu' => array('language' => 'Zulu', 'locale' => 'zul', 'localeFallback' => 'zul', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('en-nz', 'es-do', 'sz', 'ar-lb', 'zh-hk', 'pt-br'));
		$expected = array(
			'en-nz' => array('language' => 'English (New Zealand)', 'locale' => 'en_nz', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-do' => array('language' => 'Spanish (Dominican Republic)', 'locale' => 'es_do', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'sz' => array('language' => 'Sami (Lappish)', 'locale' => 'smi', 'localeFallback' => 'smi', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'ar-lb' => array('language' => 'Arabic (Lebanon)', 'locale' => 'ar_lb', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'zh-hk' => array('language' => 'Chinese (Hong Kong)', 'locale' => 'zh_hk', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'pt-br' => array('language' => 'Portuguese (Brazil)', 'locale' => 'pt_br', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);

		$result = $localize->catalog(array('eng', 'deu', 'zho', 'rum', 'zul', 'yid'));
		$expected = array(
			'eng' => array('language' => 'English', 'locale' => 'eng', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'deu' => array('language' => 'German (Standard)', 'locale' => 'deu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'zho' => array('language' => 'Chinese', 'locale' => 'zho', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'rum' => array('language' => 'Romanian', 'locale' => 'ron', 'localeFallback' => 'ron', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'zul' => array('language' => 'Zulu', 'locale' => 'zul', 'localeFallback' => 'zul', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'yid' => array('language' => 'Yiddish', 'locale' => 'yid', 'localeFallback' => 'yid', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEquals($expected, $result);
	}
}
