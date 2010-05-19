<?php
/**
 * L10nTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'l10n');

/**
 * L10nTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class L10nTest extends CakeTestCase {

/**
 * testGet method
 *
 * @access public
 * @return void
 */
	function testGet() {
		$l10n =& new L10n();

		// Catalog Entry
		$l10n->get('en');

		$this->assertEqual($l10n->language, 'English');
		$this->assertEqual($l10n->languagePath, array('eng', 'eng'));
		$this->assertEqual($l10n->locale, 'eng');

		// Map Entry
		$l10n->get('eng');

		$this->assertEqual($l10n->language, 'English');
		$this->assertEqual($l10n->languagePath, array('eng', 'eng'));
		$this->assertEqual($l10n->locale, 'eng');

		// Catalog Entry
		$l10n->get('en-ca');

		$this->assertEqual($l10n->language, 'English (Canadian)');
		$this->assertEqual($l10n->languagePath, array('en_ca', 'eng'));
		$this->assertEqual($l10n->locale, 'en_ca');

		// Default Entry
		define('DEFAULT_LANGUAGE', 'en-us');

		$l10n->get('use_default');

		$this->assertEqual($l10n->language, 'English (United States)');
		$this->assertEqual($l10n->languagePath, array('en_us', 'eng'));
		$this->assertEqual($l10n->locale, 'en_us');

		$l10n->get('es');
		$l10n->get('');
		$this->assertEqual($l10n->lang, 'en-us');


		// Using $this->default
		$l10n = new L10n();

		$l10n->get('use_default');
		$this->assertEqual($l10n->language, 'English (United States)');
		$this->assertEqual($l10n->languagePath, array('en_us', 'eng', 'eng'));
		$this->assertEqual($l10n->locale, 'en_us');
	}

/**
 * testGetAutoLanguage method
 *
 * @access public
 * @return void
 */
	function testGetAutoLanguage() {
		$__SERVER = $_SERVER;
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'inexistent,en-ca';

		$l10n =& new L10n();
		$l10n->get();

		$this->assertEqual($l10n->language, 'English (Canadian)');
		$this->assertEqual($l10n->languagePath, array('en_ca', 'eng', 'eng'));
		$this->assertEqual($l10n->locale, 'en_ca');

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es_mx';
		$l10n->get();

		$this->assertEqual($l10n->language, 'Spanish (Mexican)');
		$this->assertEqual($l10n->languagePath, array('es_mx', 'spa', 'eng'));
		$this->assertEqual($l10n->locale, 'es_mx');

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en_xy,en_ca';
		$l10n->get();

		$this->assertEqual($l10n->language, 'English');
		$this->assertEqual($l10n->languagePath, array('eng', 'eng', 'eng'));
		$this->assertEqual($l10n->locale, 'eng');

		$_SERVER = $__SERVER;
	}

/**
 * testMap method
 *
 * @access public
 * @return void
 */
	function testMap() {
		$l10n =& new L10n();

		$result = $l10n->map(array('afr', 'af'));
		$expected = array('afr' => 'af', 'af' => 'afr');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('alb', 'sq'));
		$expected = array('alb' => 'sq', 'sq' => 'alb');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('ara', 'ar'));
		$expected = array('ara' => 'ar', 'ar' => 'ara');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('hye', 'hy'));
		$expected = array('hye' => 'hy', 'hy' => 'hye');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('baq', 'eu'));
		$expected = array('baq' => 'eu', 'eu' => 'baq');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('baq', 'eu'));
		$expected = array('baq' => 'eu', 'eu' => 'baq');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('bos', 'bs'));
		$expected = array('bos' => 'bs', 'bs' => 'bos');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('bul', 'bg'));
		$expected = array('bul' => 'bg', 'bg' => 'bul');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('bel', 'be'));
		$expected = array('bel' => 'be', 'be' => 'bel');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('cat', 'ca'));
		$expected = array('cat' => 'ca', 'ca' => 'cat');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('chi', 'zh'));
		$expected = array('chi' => 'zh', 'zh' => 'chi');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('zho', 'zh'));
		$expected = array('zho' => 'zh', 'zh' => 'chi');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('hrv', 'hr'));
		$expected = array('hrv' => 'hr', 'hr' => 'hrv');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('ces', 'cs'));
		$expected = array('ces' => 'cs', 'cs' => 'cze');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('cze', 'cs'));
		$expected = array('cze' => 'cs', 'cs' => 'cze');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('dan', 'da'));
		$expected = array('dan' => 'da', 'da' => 'dan');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('dut', 'nl'));
		$expected = array('dut' => 'nl', 'nl' => 'dut');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('nld', 'nl'));
		$expected = array('nld' => 'nl', 'nl' => 'dut');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('eng', 'en'));
		$expected = array('eng' => 'en', 'en' => 'eng');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('est', 'et'));
		$expected = array('est' => 'et', 'et' => 'est');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('fao', 'fo'));
		$expected = array('fao' => 'fo', 'fo' => 'fao');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('fas', 'fa'));
		$expected = array('fas' => 'fa', 'fa' => 'fas');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('per', 'fa'));
		$expected = array('per' => 'fa', 'fa' => 'fas');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('fin', 'fi'));
		$expected = array('fin' => 'fi', 'fi' => 'fin');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('fra', 'fr'));
		$expected = array('fra' => 'fr', 'fr' => 'fre');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('fre', 'fr'));
		$expected = array('fre' => 'fr', 'fr' => 'fre');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('gla', 'gd'));
		$expected = array('gla' => 'gd', 'gd' => 'gla');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('glg', 'gl'));
		$expected = array('glg' => 'gl', 'gl' => 'glg');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('deu', 'de'));
		$expected = array('deu' => 'de', 'de' => 'deu');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('ger', 'de'));
		$expected = array('ger' => 'de', 'de' => 'deu');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('ell', 'el'));
		$expected = array('ell' => 'el', 'el' => 'gre');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('gre', 'el'));
		$expected = array('gre' => 'el', 'el' => 'gre');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('heb', 'he'));
		$expected = array('heb' => 'he', 'he' => 'heb');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('hin', 'hi'));
		$expected = array('hin' => 'hi', 'hi' => 'hin');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('hun', 'hu'));
		$expected = array('hun' => 'hu', 'hu' => 'hun');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('ice', 'is'));
		$expected = array('ice' => 'is', 'is' => 'ice');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('isl', 'is'));
		$expected = array('isl' => 'is', 'is' => 'ice');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('ind', 'id'));
		$expected = array('ind' => 'id', 'id' => 'ind');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('gle', 'ga'));
		$expected = array('gle' => 'ga', 'ga' => 'gle');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('ita', 'it'));
		$expected = array('ita' => 'it', 'it' => 'ita');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('jpn', 'ja'));
		$expected = array('jpn' => 'ja', 'ja' => 'jpn');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('kor', 'ko'));
		$expected = array('kor' => 'ko', 'ko' => 'kor');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('lav', 'lv'));
		$expected = array('lav' => 'lv', 'lv' => 'lav');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('lit', 'lt'));
		$expected = array('lit' => 'lt', 'lt' => 'lit');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('mac', 'mk'));
		$expected = array('mac' => 'mk', 'mk' => 'mac');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('mkd', 'mk'));
		$expected = array('mkd' => 'mk', 'mk' => 'mac');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('may', 'ms'));
		$expected = array('may' => 'ms', 'ms' => 'may');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('msa', 'ms'));
		$expected = array('msa' => 'ms', 'ms' => 'may');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('mlt', 'mt'));
		$expected = array('mlt' => 'mt', 'mt' => 'mlt');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('nor', 'no'));
		$expected = array('nor' => 'no', 'no' => 'nor');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('nob', 'nb'));
		$expected = array('nob' => 'nb', 'nb' => 'nob');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('nno', 'nn'));
		$expected = array('nno' => 'nn', 'nn' => 'nno');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('pol', 'pl'));
		$expected = array('pol' => 'pl', 'pl' => 'pol');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('por', 'pt'));
		$expected = array('por' => 'pt', 'pt' => 'por');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('roh', 'rm'));
		$expected = array('roh' => 'rm', 'rm' => 'roh');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('ron', 'ro'));
		$expected = array('ron' => 'ro', 'ro' => 'rum');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('rum', 'ro'));
		$expected = array('rum' => 'ro', 'ro' => 'rum');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('rus', 'ru'));
		$expected = array('rus' => 'ru', 'ru' => 'rus');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('smi', 'sz'));
		$expected = array('smi' => 'sz', 'sz' => 'smi');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('scc', 'sr'));
		$expected = array('scc' => 'sr', 'sr' => 'scc');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('srp', 'sr'));
		$expected = array('srp' => 'sr', 'sr' => 'scc');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('slk', 'sk'));
		$expected = array('slk' => 'sk', 'sk' => 'slo');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('slo', 'sk'));
		$expected = array('slo' => 'sk', 'sk' => 'slo');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('slv', 'sl'));
		$expected = array('slv' => 'sl', 'sl' => 'slv');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('wen', 'sb'));
		$expected = array('wen' => 'sb', 'sb' => 'wen');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('spa', 'es'));
		$expected = array('spa' => 'es', 'es' => 'spa');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('swe', 'sv'));
		$expected = array('swe' => 'sv', 'sv' => 'swe');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('tha', 'th'));
		$expected = array('tha' => 'th', 'th' => 'tha');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('tso', 'ts'));
		$expected = array('tso' => 'ts', 'ts' => 'tso');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('tsn', 'tn'));
		$expected = array('tsn' => 'tn', 'tn' => 'tsn');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('tur', 'tr'));
		$expected = array('tur' => 'tr', 'tr' => 'tur');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('ukr', 'uk'));
		$expected = array('ukr' => 'uk', 'uk' => 'ukr');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('urd', 'ur'));
		$expected = array('urd' => 'ur', 'ur' => 'urd');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('ven', 've'));
		$expected = array('ven' => 've', 've' => 'ven');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('vie', 'vi'));
		$expected = array('vie' => 'vi', 'vi' => 'vie');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('xho', 'xh'));
		$expected = array('xho' => 'xh', 'xh' => 'xho');
		$this->assertEqual($result, $expected);
	
		$result = $l10n->map(array('cy', 'cym'));
		$expected = array('cym' => 'cy', 'cy' => 'cym');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('yid', 'yi'));
		$expected = array('yid' => 'yi', 'yi' => 'yid');
		$this->assertEqual($result, $expected);

		$result = $l10n->map(array('zul', 'zu'));
		$expected = array('zul' => 'zu', 'zu' => 'zul');
		$this->assertEqual($result, $expected);
	}

/**
 * testCatalog method
 *
 * @access public
 * @return void
 */
	function testCatalog() {
		$l10n =& new L10n();

		$result = $l10n->catalog(array('af'));
		$expected = array(
			'af' => array('language' => 'Afrikaans', 'locale' => 'afr', 'localeFallback' => 'afr', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('ar', 'ar-ae', 'ar-bh', 'ar-dz', 'ar-eg', 'ar-iq', 'ar-jo', 'ar-kw', 'ar-lb', 'ar-ly', 'ar-ma',
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
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('be'));
		$expected = array(
			'be' => array('language' => 'Byelorussian', 'locale' => 'bel', 'localeFallback' => 'bel', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('bg'));
		$expected = array(
			'bg' => array('language' => 'Bulgarian', 'locale' => 'bul', 'localeFallback' => 'bul', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('bs'));
		$expected = array(
			'bs' => array('language' => 'Bosnian', 'locale' => 'bos', 'localeFallback' => 'bos', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('ca'));
		$expected = array(
			'ca' => array('language' => 'Catalan', 'locale' => 'cat', 'localeFallback' => 'cat', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('cs'));
		$expected = array(
			'cs' => array('language' => 'Czech', 'locale' => 'cze', 'localeFallback' => 'cze', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('da'));
		$expected = array(
			'da' => array('language' => 'Danish', 'locale' => 'dan', 'localeFallback' => 'dan', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('de', 'de-at', 'de-ch', 'de-de', 'de-li', 'de-lu'));
		$expected = array(
			'de' => array('language' => 'German (Standard)', 'locale' => 'deu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'de-at' => array('language' => 'German (Austria)', 'locale' => 'de_at', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'de-ch' => array('language' => 'German (Swiss)', 'locale' => 'de_ch', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'de-de' => array('language' => 'German (Germany)', 'locale' => 'de_de', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'de-li' => array('language' => 'German (Liechtenstein)', 'locale' => 'de_li', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'de-lu' => array('language' => 'German (Luxembourg)', 'locale' => 'de_lu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('e', 'el'));
		$expected = array(
			'e' => array('language' => 'Greek', 'locale' => 'gre', 'localeFallback' => 'gre', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'el' => array('language' => 'Greek', 'locale' => 'gre', 'localeFallback' => 'gre', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('en', 'en-au', 'en-bz', 'en-ca', 'en-gb', 'en-ie', 'en-jm', 'en-nz', 'en-tt', 'en-us', 'en-za'));
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
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('es', 'es-ar', 'es-bo', 'es-cl', 'es-co', 'es-cr', 'es-do', 'es-ec', 'es-es', 'es-gt', 'es-hn',
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
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('et'));
		$expected = array(
			'et' => array('language' => 'Estonian', 'locale' => 'est', 'localeFallback' => 'est', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('eu'));
		$expected = array(
			'eu' => array('language' => 'Basque', 'locale' => 'baq', 'localeFallback' => 'baq', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('fa'));
		$expected = array(
			'fa' => array('language' => 'Farsi', 'locale' => 'per', 'localeFallback' => 'per', 'charset' => 'utf-8', 'direction' => 'rtl')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('fi'));
		$expected = array(
			'fi' => array('language' => 'Finnish', 'locale' => 'fin', 'localeFallback' => 'fin', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('fo'));
		$expected = array(
			'fo' => array('language' => 'Faeroese', 'locale' => 'fao', 'localeFallback' => 'fao', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('fr', 'fr-be', 'fr-ca', 'fr-ch', 'fr-fr', 'fr-lu'));
		$expected = array(
			'fr' => array('language' => 'French (Standard)', 'locale' => 'fre', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'fr-be' => array('language' => 'French (Belgium)', 'locale' => 'fr_be', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'fr-ca' => array('language' => 'French (Canadian)', 'locale' => 'fr_ca', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'fr-ch' => array('language' => 'French (Swiss)', 'locale' => 'fr_ch', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'fr-fr' => array('language' => 'French (France)', 'locale' => 'fr_fr', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'fr-lu' => array('language' => 'French (Luxembourg)', 'locale' => 'fr_lu', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('ga'));
		$expected = array(
			'ga' => array('language' => 'Irish', 'locale' => 'gle', 'localeFallback' => 'gle', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('gd', 'gd-ie'));
		$expected = array(
			'gd' => array('language' => 'Gaelic (Scots)', 'locale' => 'gla', 'localeFallback' => 'gla', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'gd-ie' => array('language' => 'Gaelic (Irish)', 'locale' => 'gd_ie', 'localeFallback' => 'gla', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('gl'));
		$expected = array(
			'gl' => array('language' => 'Galician', 'locale' => 'glg', 'localeFallback' => 'glg', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('he'));
		$expected = array(
			'he' => array('language' => 'Hebrew', 'locale' => 'heb', 'localeFallback' => 'heb', 'charset' => 'utf-8', 'direction' => 'rtl')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('hi'));
		$expected = array(
			'hi' => array('language' => 'Hindi', 'locale' => 'hin', 'localeFallback' => 'hin', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('hr'));
		$expected = array(
			'hr' => array('language' => 'Croatian', 'locale' => 'hrv', 'localeFallback' => 'hrv', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('hu'));
		$expected = array(
			'hu' => array('language' => 'Hungarian', 'locale' => 'hun', 'localeFallback' => 'hun', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('hy'));
		$expected = array(
			'hy' => array('language' => 'Armenian - Armenia', 'locale' => 'hye', 'localeFallback' => 'hye', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('id', 'in'));
		$expected = array(
			'id' => array('language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'in' => array('language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('is'));
		$expected = array(
			'is' => array('language' => 'Icelandic', 'locale' => 'ice', 'localeFallback' => 'ice', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('it', 'it-ch'));
		$expected = array(
			'it' => array('language' => 'Italian', 'locale' => 'ita', 'localeFallback' => 'ita', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'it-ch' => array('language' => 'Italian (Swiss) ', 'locale' => 'it_ch', 'localeFallback' => 'ita', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('ja'));
		$expected = array(
			'ja' => array('language' => 'Japanese', 'locale' => 'jpn', 'localeFallback' => 'jpn', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('ko', 'ko-kp', 'ko-kr'));
		$expected = array(
			'ko' => array('language' => 'Korean', 'locale' => 'kor', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'),
			'ko-kp' => array('language' => 'Korea (North)', 'locale' => 'ko_kp', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'),
			'ko-kr' => array('language' => 'Korea (South)', 'locale' => 'ko_kr', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('koi8-r', 'ru', 'ru-mo'));
		$expected = array(
			'koi8-r' => array('language' => 'Russian', 'locale' => 'koi8_r', 'localeFallback' => 'rus', 'charset' => 'koi8-r', 'direction' => 'ltr'),
			'ru' => array('language' => 'Russian', 'locale' => 'rus', 'localeFallback' => 'rus', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'ru-mo' => array('language' => 'Russian (Moldavia)', 'locale' => 'ru_mo', 'localeFallback' => 'rus', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('lt'));
		$expected = array(
			'lt' => array('language' => 'Lithuanian', 'locale' => 'lit', 'localeFallback' => 'lit', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('lv'));
		$expected = array(
			'lv' => array('language' => 'Latvian', 'locale' => 'lav', 'localeFallback' => 'lav', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('mk', 'mk-mk'));
		$expected = array(
			'mk' => array('language' => 'FYRO Macedonian', 'locale' => 'mk', 'localeFallback' => 'mac', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'mk-mk' => array('language' => 'Macedonian', 'locale' => 'mk_mk', 'localeFallback' => 'mac', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('ms'));
		$expected = array(
			'ms' => array('language' => 'Malaysian', 'locale' => 'may', 'localeFallback' => 'may', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('mt'));
		$expected = array(
			'mt' => array('language' => 'Maltese', 'locale' => 'mlt', 'localeFallback' => 'mlt', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('n', 'nl', 'nl-be'));
		$expected = array(
			'n' => array('language' => 'Dutch (Standard)', 'locale' => 'dut', 'localeFallback' => 'dut', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'nl' => array('language' => 'Dutch (Standard)', 'locale' => 'dut', 'localeFallback' => 'dut', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'nl-be' => array('language' => 'Dutch (Belgium)', 'locale' => 'nl_be', 'localeFallback' => 'dut', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('nb'));
		$expected = array(
			'nb' => array('language' => 'Norwegian Bokmal', 'locale' => 'nob', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('nn', 'no'));
		$expected = array(
			'nn' => array('language' => 'Norwegian Nynorsk', 'locale' => 'nno', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'no' => array('language' => 'Norwegian', 'locale' => 'nor', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('p', 'pl'));
		$expected = array(
			'p' => array('language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'pl' => array('language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('pt', 'pt-br'));
		$expected = array(
			'pt' => array('language' => 'Portuguese (Portugal)', 'locale' => 'por', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'pt-br' => array('language' => 'Portuguese (Brazil)', 'locale' => 'pt_br', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('rm'));
		$expected = array(
			'rm' => array('language' => 'Rhaeto-Romanic', 'locale' => 'roh', 'localeFallback' => 'roh', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('ro', 'ro-mo'));
		$expected = array(
			'ro' => array('language' => 'Romanian', 'locale' => 'rum', 'localeFallback' => 'rum', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'ro-mo' => array('language' => 'Romanian (Moldavia)', 'locale' => 'ro_mo', 'localeFallback' => 'rum', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('sb'));
		$expected = array(
			'sb' => array('language' => 'Sorbian', 'locale' => 'wen', 'localeFallback' => 'wen', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('sk'));
		$expected = array(
			'sk' => array('language' => 'Slovak', 'locale' => 'slo', 'localeFallback' => 'slo', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('sl'));
		$expected = array(
			'sl' => array('language' => 'Slovenian', 'locale' => 'slv', 'localeFallback' => 'slv', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('sq'));
		$expected = array(
			'sq' => array('language' => 'Albanian', 'locale' => 'alb', 'localeFallback' => 'alb', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('sr'));
		$expected = array(
			'sr' => array('language' => 'Serbian', 'locale' => 'scc', 'localeFallback' => 'scc', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('sv', 'sv-fi'));
		$expected = array(
			'sv' => array('language' => 'Swedish', 'locale' => 'swe', 'localeFallback' => 'swe', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'sv-fi' => array('language' => 'Swedish (Finland)', 'locale' => 'sv_fi', 'localeFallback' => 'swe', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('sx'));
		$expected = array(
			'sx' => array('language' => 'Sutu', 'locale' => 'sx', 'localeFallback' => 'sx', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('sz'));
		$expected = array(
			'sz' => array('language' => 'Sami (Lappish)', 'locale' => 'smi', 'localeFallback' => 'smi', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('th'));
		$expected = array(
			'th' => array('language' => 'Thai', 'locale' => 'tha', 'localeFallback' => 'tha', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('tn'));
		$expected = array(
			'tn' => array('language' => 'Tswana', 'locale' => 'tsn', 'localeFallback' => 'tsn', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('tr'));
		$expected = array(
			'tr' => array('language' => 'Turkish', 'locale' => 'tur', 'localeFallback' => 'tur', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('ts'));
		$expected = array(
			'ts' => array('language' => 'Tsonga', 'locale' => 'tso', 'localeFallback' => 'tso', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('uk'));
		$expected = array(
			'uk' => array('language' => 'Ukrainian', 'locale' => 'ukr', 'localeFallback' => 'ukr', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('ur'));
		$expected = array(
			'ur' => array('language' => 'Urdu', 'locale' => 'urd', 'localeFallback' => 'urd', 'charset' => 'utf-8', 'direction' => 'rtl')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('ve'));
		$expected = array(
			've' => array('language' => 'Venda', 'locale' => 'ven', 'localeFallback' => 'ven', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('vi'));
		$expected = array(
			'vi' => array('language' => 'Vietnamese', 'locale' => 'vie', 'localeFallback' => 'vie', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('cy'));
		$expected = array(
			'cy' => array('language' => 'Welsh', 'locale' => 'cym', 'localeFallback' => 'cym', 'charset' => 'utf-8',
'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('xh'));
		$expected = array(
			'xh' => array('language' => 'Xhosa', 'locale' => 'xho', 'localeFallback' => 'xho', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('yi'));
		$expected = array(
			'yi' => array('language' => 'Yiddish', 'locale' => 'yid', 'localeFallback' => 'yid', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('zh', 'zh-cn', 'zh-hk', 'zh-sg', 'zh-tw'));
		$expected = array(
			'zh' => array('language' => 'Chinese', 'locale' => 'chi', 'localeFallback' => 'chi', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'zh-cn' => array('language' => 'Chinese (PRC)', 'locale' => 'zh_cn', 'localeFallback' => 'chi', 'charset' => 'GB2312', 'direction' => 'ltr'),
			'zh-hk' => array('language' => 'Chinese (Hong Kong)', 'locale' => 'zh_hk', 'localeFallback' => 'chi', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'zh-sg' => array('language' => 'Chinese (Singapore)', 'locale' => 'zh_sg', 'localeFallback' => 'chi', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'zh-tw' => array('language' => 'Chinese (Taiwan)', 'locale' => 'zh_tw', 'localeFallback' => 'chi', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('zu'));
		$expected = array(
			'zu' => array('language' => 'Zulu', 'locale' => 'zul', 'localeFallback' => 'zul', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);

		$result = $l10n->catalog(array('en-nz', 'es-do', 'sz', 'ar-lb', 'zh-hk', 'pt-br'));
		$expected = array(
			'en-nz' => array('language' => 'English (New Zealand)', 'locale' => 'en_nz', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'es-do' => array('language' => 'Spanish (Dominican Republic)', 'locale' => 'es_do', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'sz' => array('language' => 'Sami (Lappish)', 'locale' => 'smi', 'localeFallback' => 'smi', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'ar-lb' => array('language' => 'Arabic (Lebanon)', 'locale' => 'ar_lb', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
			'zh-hk' => array('language' => 'Chinese (Hong Kong)', 'locale' => 'zh_hk', 'localeFallback' => 'chi', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'pt-br' => array('language' => 'Portuguese (Brazil)', 'locale' => 'pt_br', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);
		
		$result = $l10n->catalog(array('eng', 'deu', 'zho', 'rum', 'zul', 'yid'));
		$expected = array(
			'eng' => array('language' => 'English', 'locale' => 'eng', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'deu' => array('language' => 'German (Standard)', 'locale' => 'deu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'zho' => array('language' => 'Chinese', 'locale' => 'chi', 'localeFallback' => 'chi', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'rum' => array('language' => 'Romanian', 'locale' => 'rum', 'localeFallback' => 'rum', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'zul' => array('language' => 'Zulu', 'locale' => 'zul', 'localeFallback' => 'zul', 'charset' => 'utf-8', 'direction' => 'ltr'),
			'yid' => array('language' => 'Yiddish', 'locale' => 'yid', 'localeFallback' => 'yid', 'charset' => 'utf-8', 'direction' => 'ltr')
		);
		$this->assertEqual($result, $expected);
	}
}
