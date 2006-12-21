<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 1.2.0.4116
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class L10n extends Object {
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $language = 'English (United States)';
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $languagePath = 'eng';
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $lang = 'en-us';
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $locale = 'en_us';
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $winLocale = 'eng';
/**
 * Enter description here...
 *
 * @var array
 * @access private
 */
	var $__l10nMap = array(/* Afrikaans */ 'afr' => 'af',
								/* Albanian */ 'alb' => 'sq',
								/* Arabic */ 'ara' => 'ar',
								/* Basque */ 'baq' => 'eu',
								/* Bulgarian */ 'bul' => 'bg',
								/* Byelorussian */ 'bel' => 'be',
								/* Catalan */ 'cat' => 'ca',
								/* Chinese */ 'chi' => 'zh',
								/* Chinese */ 'zho' => 'zh',
								/* Croatian */ 'hrv' => 'hr',
								/* Croatian */ 'scr' => 'hr',
								/* Czech */ 'ces' => 'cs',
								/* Czech */ 'cze' => 'cs',
								/* Danish */ 'dan' => 'da',
								/* Dutch (Standard) */ 'dut' => 'nl',
								/* Dutch (Standard) */ 'nld' => 'nl',
								/* English */ 'eng' => 'en',
								/* Estonian */ 'est' => 'et',
								/* Faeroese */ 'fao' => 'fo',
								/* Farsi */ 'fas' => 'fa',
								/* Farsi */ 'per' => 'fa',
								/* Finnish */ 'fin' => 'fi',
								/* French (Standard) */ 'fra' => 'fr',
								/* French (Standard) */ 'fre' => 'fr',
								/* Gaelic (Scots) */ 'gla' => 'gd',
								/* Galician */ 'glg' => 'gl',
								/* German  (Standard) */ 'deu' => 'de',
								/* German  (Standard) */ 'ger' => 'de',
								/* Greek */ 'ell' => 'el',
								/* Greek */ 'gre' => 'el',
								/* Hebrew */ 'heb' => 'he',
								/* Hindi */ 'hin' => 'hi',
								/* Hungarian */ 'hun' => 'hu',
								/* Icelandic */ 'ice' => 'is',
								/* Icelandic */ 'isl' => 'is',
								/* Indonesian */ 'ind' => 'id',
								/* Irish */ 'gle' => 'ga',
								/* Italian */ 'ita' => 'it',
								/* Japanese */ 'jpn' => 'ja',
								/* Korean */ 'kor' => 'ko',
								/* Latvian */ 'lav' => 'lv',
								/* Lithuanian */ 'lit' => 'lt',
								/* Macedonian */ 'mac' => 'mk',
								/* Macedonian  */ 'mkd' => 'mk',
								/* Malaysian */ 'may' => 'ms',
								/* Malaysian */ 'msa' => 'ms',
								/* Maltese */ 'mlt' => 'mt',
								/* Norwegian */ 'nor' => 'no',
								/* Norwegian Bokmal */ 'nob' => 'nb',
								/* Norwegian Nynorsk */ 'nno' => 'nn',
								/* Polish */ 'pol' => 'pl',
								/* Portuguese (Portugal) */ 'por' => 'pt',
								/* Rhaeto-Romanic */ 'roh' => 'rm',
								/* Romanian */ 'ron' => 'ro',
								/* Romanian */ 'rum' => 'ro',
								/* Russian */ 'rus' => 'ru',
								/* Sami (Lappish) */ 'smi' => 'sz',
								/* Scots Gaelic */ 'gla' => 'gd',
								/* Serbian */ 'scc' => 'sr',
								/* Serbian */ 'srp' => 'sr',
								/* Slovack */ 'slk' => 'sk',
								/* Slovack */ 'slo' => 'sk',
								/* Slovenian */ 'slv' => 'sl',
								/* Sorbian */ 'wen' => 'sb',
								/* Spanish (Spain - Traditional) */ 'spa' => 'es',
								/* Swedish */ 'swe' => 'sv',
								/* Thai */  'tha' => 'th',
								/* Tsonga */  'tso' => 'ts',
								/* Tswana */ 'tsn' => 'tn',
								/* Turkish */ 'tur' => 'tr',
								/* Ukrainian */ 'ukr' => 'uk',
								/* Urdu */ 'urd' => 'ur',
								/* Venda */ 'ven' => 've',
								/* Vietnamese */ 'vie' => 'vi',
								/* Xhosa */ 'xho' => 'xh',
								/* Yiddish */ 'yid' => 'yi',
								/* Zulu */  'zul' => 'zu');
/**
 * Enter description here...
 *
 * @var unknown_type
 * @access private
 */
	var $__l10nCatalog = array('af' => array('language' => 'Afrikaans', 'locale' => 'afr', 'localeFallback' => 'afr'),
										'ar' => array('language' => 'Arabic', 'locale' => 'ara', 'localeFallback' => 'ara'),
										'ar-ae' => array('language' => 'Arabic (U.A.E.)', 'locale' => 'ar_ae', 'localeFallback' => 'ara'),
										'ar-bh' => array('language' => 'Arabic (Bahrain)', 'locale' => 'ar_bh', 'localeFallback' => 'ara'),
										'ar-dz' => array('language' => 'Arabic (Algeria)', 'locale' => 'ar_dz', 'localeFallback' => 'ara'),
										'ar-eg' => array('language' => 'Arabic (Egypt)', 'locale' => 'ar_eg', 'localeFallback' => 'ara'),
										'ar-iq' => array('language' => 'Arabic (Iraq)', 'locale' => 'ar_iq', 'localeFallback' => 'ara'),
										'ar-jo' => array('language' => 'Arabic (Jordan)', 'locale' => 'ar_jo', 'localeFallback' => 'ara'),
										'ar-kw' => array('language' => 'Arabic (Kuwait)', 'locale' => 'ar_kw', 'localeFallback' => 'ara'),
										'ar-lb' => array('language' => 'Arabic (Lebanon)', 'locale' => 'ar_lb', 'localeFallback' => 'ara'),
										'ar-ly' => array('language' => 'Arabic (Libya)', 'locale' => 'ar_ly', 'localeFallback' => 'ara'),
										'ar-ma' => array('language' => 'Arabic (Morocco)', 'locale' => 'ar_ma', 'localeFallback' => 'ara'),
										'ar-om' => array('language' => 'Arabic (Oman)', 'locale' => 'ar_om', 'localeFallback' => 'ara'),
										'ar-qa' => array('language' => 'Arabic (Qatar)', 'locale' => 'ar_qa', 'localeFallback' => 'ara'),
										'ar-sa' => array('language' => 'Arabic (Saudi Arabia)', 'locale' => 'ar_sa', 'localeFallback' => 'ara'),
										'ar-sy' => array('language' => 'Arabic (Syria)', 'locale' => 'ar_sy', 'localeFallback' => 'ara'),
										'ar-tn' => array('language' => 'Arabic (Tunisia)', 'locale' => 'ar_tn', 'localeFallback' => 'ara'),
										'ar-ye' => array('language' => 'Arabic (Yemen)', 'locale' => 'ar_ye', 'localeFallback' => 'ara'),
										'be' => array('language' => 'Byelorussian', 'locale' => 'bel', 'localeFallback' => 'bel'),
										'bg' => array('language' => 'Bulgarian', 'locale' => 'bul', 'localeFallback' => 'bul'),
										'ca' => array('language' => 'Catalan', 'locale' => 'cat', 'localeFallback' => 'cat'),
										'cs' => array('language' => 'Czech', 'locale' => 'cze', 'localeFallback' => 'cze'),
										'da' => array('language' => 'Danish', 'locale' => 'dan', 'localeFallback' => 'dan'),
										'de' => array('language' => 'German  (Standard)', 'locale' => 'deu', 'localeFallback' => 'deu'),
										'de-at' => array('language' => 'German  (Austria)', 'locale' => 'de_at', 'localeFallback' => 'deu'),
										'de-ch' => array('language' => 'German  (Swiss)', 'locale' => 'de_ch', 'localeFallback' => 'deu'),
										'de-de' => array('language' => 'German  (Germany)', 'locale' => 'de_de', 'localeFallback' => 'deu'),
										'de-li' => array('language' => 'German  (Liechtenstein)', 'locale' => 'de_li', 'localeFallback' => 'deu'),
										'de-lu' => array('language' => 'German  (Luxembourg)', 'locale' => 'de_lu', 'localeFallback' => 'deu'),
										'e' => array('language' => 'Greek', 'locale' => 'gre', 'localeFallback' => 'gre'),
										'el' => array('language' => 'Greek', 'locale' => 'gre', 'localeFallback' => 'gre'),
										'en' => array('language' => 'English', 'locale' => 'eng', 'localeFallback' => 'eng'),
										'en-au' => array('language' => 'English (Australian)', 'locale' => 'en_au', 'localeFallback' => 'eng'),
										'en-bz' => array('language' => 'English (Belize)', 'locale' => 'en_bz', 'localeFallback' => 'eng'),
										'en-ca' => array('language' => 'English (Canadian)', 'locale' => 'en_ca', 'localeFallback' => 'eng'),
										'en-gb' => array('language' => 'English (British)', 'locale' => 'en_gb', 'localeFallback' => 'eng'),
										'en-ie' => array('language' => 'English (Ireland)', 'locale' => 'en_ie', 'localeFallback' => 'eng'),
										'en-jm' => array('language' => 'English (Jamaica)', 'locale' => 'en_jm', 'localeFallback' => 'eng'),
										'en-nz' => array('language' => 'English (New Zealand)', 'locale' => 'en_nz', 'localeFallback' => 'eng'),
										'en-tt' => array('language' => 'English (Trinidad)', 'locale' => 'en_tt', 'localeFallback' => 'eng'),
										'en-us' => array('language' => 'English (United States)', 'locale' => 'en_us', 'localeFallback' => 'eng'),
										'en-za' => array('language' => 'English (South Africa)', 'locale' => 'en_za', 'localeFallback' => 'eng'),
										'es' => array('language' => 'Spanish (Spain - Traditional)', 'locale' => 'spa', 'localeFallback' => 'spa'),
										'es-ar' => array('language' => 'Spanish (Argentina)', 'locale' => 'es_ar', 'localeFallback' => 'spa'),
										'es-bo' => array('language' => 'Spanish (Bolivia)', 'locale' => 'es_bo', 'localeFallback' => 'spa'),
										'es-cl' => array('language' => 'Spanish (Chile)', 'locale' => 'es_cl', 'localeFallback' => 'spa'),
										'es-co' => array('language' => 'Spanish (Colombia)', 'locale' => 'es_co', 'localeFallback' => 'spa'),
										'es-cr' => array('language' => 'Spanish (Costa Rica)', 'locale' => 'es_cr', 'localeFallback' => 'spa'),
										'es-do' => array('language' => 'Spanish (Dominican Republic)', 'locale' => 'es_do', 'localeFallback' => 'spa'),
										'es-ec' => array('language' => 'Spanish (Ecuador)', 'locale' => 'es_ec', 'localeFallback' => 'spa'),
										'es-es' => array('language' => 'Spanish (Spain)', 'locale' => 'es_es', 'localeFallback' => 'spa'),
										'es-gt' => array('language' => 'Spanish (Guatemala)', 'locale' => 'es_gt', 'localeFallback' => 'spa'),
										'es-hn' => array('language' => 'Spanish (Honduras)', 'locale' => 'es_hn', 'localeFallback' => 'spa'),
										'es-mx' => array('language' => 'Spanish (Mexican)', 'locale' => 'es_mx', 'localeFallback' => 'spa'),
										'es-ni' => array('language' => 'Spanish (Nicaragua)', 'locale' => 'es_ni', 'localeFallback' => 'spa'),
										'es-pa' => array('language' => 'Spanish (Panama)', 'locale' => 'es_pa', 'localeFallback' => 'spa'),
										'es-pe' => array('language' => 'Spanish (Peru)', 'locale' => 'es_pe', 'localeFallback' => 'spa'),
										'es-pr' => array('language' => 'Spanish (Puerto Rico)', 'locale' => 'es_pr', 'localeFallback' => 'spa'),
										'es-py' => array('language' => 'Spanish (Paraguay)', 'locale' => 'es_py', 'localeFallback' => 'spa'),
										'es-sv' => array('language' => 'Spanish (El Salvador)', 'locale' => 'es_sv', 'localeFallback' => 'spa'),
										'es-uy' => array('language' => 'Spanish (Uruguay)', 'locale' => 'es_uy', 'localeFallback' => 'spa'),
										'es-ve' => array('language' => 'Spanish (Venezuela)', 'locale' => 'es_ve', 'localeFallback' => 'spa'),
										'et' => array('language' => 'Estonian', 'locale' => 'est', 'localeFallback' => 'est'),
										'eu' => array('language' => 'Basque', 'locale' => 'baq', 'localeFallback' => 'baq'),
										'fa' => array('language' => 'Faeroese', 'locale' => 'per', 'localeFallback' => 'per'),
										'fi' => array('language' => 'Finnish', 'locale' => 'fin', 'localeFallback' => 'fin'),
										'fo' => array('language' => 'Faeroese', 'locale' => 'fao', 'localeFallback' => 'fao'),
										'fr' => array('language' => 'French (Standard)', 'locale' => 'fre', 'localeFallback' => 'fre'),
										'fr-be' => array('language' => 'French (Belgium)', 'locale' => 'fr_be', 'localeFallback' => 'fre'),
										'fr-ca' => array('language' => 'French (Canadian)', 'locale' => 'fr_ca', 'localeFallback' => 'fre'),
										'fr-ch' => array('language' => 'French (Swiss)', 'locale' => 'fr_ch', 'localeFallback' => 'fre'),
										'fr-fr' => array('language' => 'French (France)', 'locale' => 'fr_fr', 'localeFallback' => 'fre'),
										'fr-lu' => array('language' => 'French (Luxembourg)', 'locale' => 'fr_lu', 'localeFallback' => 'fre'),
										'ga' => array('language' => 'Irish', 'locale' => 'gle', 'localeFallback' => 'gle'),
										'gd' => array('language' => 'Gaelic (Scots)', 'locale' => 'gla', 'localeFallback' => 'gla'),
										'gd-ie' => array('language' => 'Gaelic (Irish)', 'locale' => 'gd_ie', 'localeFallback' => 'gla'),
										'gl' => array('language' => 'Galician', 'locale' => 'glg', 'localeFallback' => 'glg'),
										'he' => array('language' => 'Hebrew', 'locale' => 'heb', 'localeFallback' => 'heb'),
										'hi' => array('language' => 'Hindi', 'locale' => 'hin', 'localeFallback' => 'hin'),
										'hr' => array('language' => 'Croatian', 'locale' => 'scr', 'localeFallback' => 'scr'),
										'hu' => array('language' => 'Hungarian', 'locale' => 'hun', 'localeFallback' => 'hun'),
										'id' => array('language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind'),
										'in' => array('language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind'),
										'is' => array('language' => 'Icelandic', 'locale' => 'ice', 'localeFallback' => 'ice'),
										'it' => array('language' => 'Italian', 'locale' => 'ita', 'localeFallback' => 'ita'),
										'it-ch' => array('language' => 'Italian (Swiss) ', 'locale' => 'it_ch', 'localeFallback' => 'ita'),
										'ja' => array('language' => 'Japanese', 'locale' => 'jpn', 'localeFallback' => 'jpn'),
										'ko' => array('language' => 'Korean', 'locale' => 'kor', 'localeFallback' => 'kor'),
										'ko-kp' => array('language' => 'Korea (North)', 'locale' => 'ko_kp', 'localeFallback' => 'kor'),
										'ko-kr' => array('language' => 'Korea (South)', 'locale' => 'ko_kr', 'localeFallback' => 'kor'),
										'koi8-r' => array('language' => 'Russian', 'locale' => 'koi8_r', 'localeFallback' => 'rus'),
										'lt' => array('language' => 'Lithuanian', 'locale' => 'lit', 'localeFallback' => 'lit'),
										'lv' => array('language' => 'Latvian)', 'locale' => 'lav', 'localeFallback' => 'lav'),
										'mk' => array('language' => 'FYRO Macedonian', 'locale' => 'mk', 'localeFallback' => 'mac'),
										'mk-mk' => array('language' => 'Macedonian', 'locale' => 'mk_mk', 'localeFallback' => 'mac'),
										'ms' => array('language' => 'Malaysian', 'locale' => 'may', 'localeFallback' => 'may'),
										'mt' => array('language' => 'Maltese', 'locale' => 'mlt', 'localeFallback' => 'mlt'),
										'n' => array('language' => 'Dutch (Standard)', 'locale' => 'dut', 'localeFallback' => 'dut'),
										'nb' => array('language' => 'Norwegian Bokmal', 'locale' => 'nob', 'localeFallback' => 'nor'),
										'nl' => array('language' => 'Dutch (Standard)', 'locale' => 'dut', 'localeFallback' => 'dut'),
										'nl-be' => array('language' => 'Dutch (Belgium)', 'locale' => 'nl_be', 'localeFallback' => 'dut'),
										'nn' => array('language' => 'Norwegian Nynorsk', 'locale' => 'nno', 'localeFallback' => 'nor'),
										'no' => array('language' => 'Norwegian', 'locale' => 'nor', 'localeFallback' => 'nor'),
										'p' => array('language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol'),
										'pl' => array('language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol'),
										'pt' => array('language' => 'Portuguese (Portugal)', 'locale' => 'por', 'localeFallback' => 'por'),
										'pt-br' => array('language' => 'Portuguese (Brazil)', 'locale' => 'pt_br', 'localeFallback' => 'por'),
										'rm' => array('language' => 'Rhaeto-Romanic', 'locale' => 'roh', 'localeFallback' => 'roh'),
										'ro' => array('language' => 'Romanian', 'locale' => 'rum', 'localeFallback' => 'rum'),
										'ro' => array('language' => 'Russian', 'locale' => 'rus', 'localeFallback' => 'rus'),
										'ro-mo' => array('language' => 'Romanian (Moldavia)', 'locale' => 'ro_mo', 'localeFallback' => 'rum'),
										'ru-mo' => array('language' => 'Russian (Moldavia)', 'locale' => 'ru_mo', 'localeFallback' => 'rus'),
										'sb' => array('language' => 'Slovenian', 'locale' => 'wen', 'localeFallback' => 'wen'),
										'sk' => array('language' => 'Slovack', 'locale' => 'slo', 'localeFallback' => 'slo'),
										'sl' => array('language' => 'Slovenian', 'locale' => 'slv', 'localeFallback' => 'slv'),
										'sq' => array('language' => 'Albanian', 'locale' => 'alb', 'localeFallback' => 'alb'),
										'sr' => array('language' => 'Serbian', 'locale' => 'scc', 'localeFallback' => 'scc'),
										'sv' => array('language' => 'Swedish', 'locale' => 'swe', 'localeFallback' => 'swe'),
										'sv-fi' => array('language' => 'Swedish (Findland)', 'locale' => 'sv_fi', 'localeFallback' => 'swe'),
										'sx' => array('language' => 'Sutu', 'locale' => 'sx', 'localeFallback' => 'sx'),
										'sz' => array('language' => 'Sami (Lappish)', 'locale' => 'smi', 'localeFallback' => 'smi'),
										'th' => array('language' => 'Thai', 'locale' => 'tha', 'localeFallback' => 'tha'),
										'tn' => array('language' => 'Tswana', 'locale' => 'tsn', 'localeFallback' => 'tsn'),
										'tr' => array('language' => 'Turkish', 'locale' => 'tur', 'localeFallback' => 'tur'),
										'ts' => array('language' => 'Tsonga', 'locale' => 'tso', 'localeFallback' => 'tso'),
										'uk' => array('language' => 'Ukrainian', 'locale' => 'ukr', 'localeFallback' => 'ukr'),
										'ur' => array('language' => 'Urdu', 'locale' => 'urd', 'localeFallback' => 'urd'),
										've' => array('language' => 'Venda', 'locale' => 'ven', 'localeFallback' => 'ven'),
										'vi' => array('language' => 'Vietnamese', 'locale' => 'vie', 'localeFallback' => 'vie'),
										'xh' => array('language' => 'Xhosa', 'locale' => 'xho', 'localeFallback' => 'xho'),
										'yi' => array('language' => 'Yiddish', 'locale' => 'yid', 'localeFallback' => 'yid'),
										'zh' => array('language' => 'Chinese', 'locale' => 'chi', 'localeFallback' => 'chi'),
										'zh-cn' => array('language' => 'Chinese (PRC)', 'locale' => 'zh_cn', 'localeFallback' => 'chi'),
										'zh-hk' => array('language' => 'Chinese (Hong Kong)', 'locale' => 'zh_hk', 'localeFallback' => 'chi'),
										'zh-sg' => array('language' => 'Chinese (Singapore)', 'locale' => 'zh_sg', 'localeFallback' => 'chi'),
										'zh-tw' => array('language' => 'Chinese (Taiwan)', 'locale' => 'zh_tw', 'localeFallback' => 'chi'),
										'zu' => array('language' => 'Zulu', 'locale' => 'zul', 'localeFallback' => 'zul'));
/**
 * Enter description here...
 *
 */
	function __construct() {
		parent::__construct();
	}
/**
 * Enter description here...
 *
 * @param string $language
 * @return unknown
 * @access private
 */
	function get($language = null) {
		if (!is_null($language)) {
			return $this->__setLanguage($language);
		}
		return $this->__autoLanguage();
	}
/**
 * Enter description here...
 *
 * @param string $language
 * @access private
 */
	function __setLanguage($language = null) {
		if ((!is_null($language)) && (isset($this->__l10nCatalog[$this->__l10nMap[$language]]))) {
			$this->language = $this->__l10nCatalog[$this->__l10nMap[$language]]['language'];
			$this->languagePath = array(0 => $this->__l10nCatalog[$language]['locale'],
													1 => $this->__l10nCatalog[$language]['localeFallback'],
													2 => $this->__l10nCatalog[DEFAULT_LANGUAGE]['localeFallback']);
			$this->lang = $language;
			$this->locale = $this->__l10nCatalog[$this->__l10nMap[$language]]['locale'];
		} elseif (defined('DEFAULT_LANGUAGE')) {
			$this->language = $this->__l10nCatalog[$this->__l10nMap[DEFAULT_LANGUAGE]]['language'];
			$this->languagePath = array(0 => $this->__l10nCatalog[$this->__l10nMap[DEFAULT_LANGUAGE]]['locale'],
													1 => $this->__l10nCatalog[$this->__l10nMap[DEFAULT_LANGUAGE]]['localeFallback']);
			$this->lang = DEFAULT_LANGUAGE;
			$this->locale = $this->__l10nCatalog[$this->__l10nMap[DEFAULT_LANGUAGE]]['locale'];
		}
	}
/**
 * Enter description here...
 * @access private
 */
	function __autoLanguage() {
		$_detectableLanguages = split ('[,;]', env('HTTP_ACCEPT_LANGUAGE'));
		foreach ($_detectableLanguages as $langKey => $key) {
			if (isset($this->__l10nCatalog[$langKey])) {
				$this->language = $this->__l10nCatalog[$langKey]['language'];
				$this->languagePath = array(0 => $this->__l10nCatalog[$langKey]['locale'],
														1 => $this->__l10nCatalog[$langKey]['localeFallback'],
														2 => $this->__l10nCatalog[DEFAULT_LANGUAGE]['localeFallback']);
				$this->lang = $langKey;
				$this->locale = $this->__l10nCatalog[$langKey]['locale'];
				break;
			}
		}
	}
}
?>