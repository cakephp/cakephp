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
	var $default = null;
/**
 * Enter description here...
 *
 * @var string
 * @access public
 */
	var $charset = 'utf-8';
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
	var $__l10nCatalog = array('af' => array('language' => 'Afrikaans', 'locale' => 'afr', 'localeFallback' => 'afr', 'charset' => ''),
										'ar' => array('language' => 'Arabic', 'locale' => 'ara', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-ae' => array('language' => 'Arabic (U.A.E.)', 'locale' => 'ar_ae', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-bh' => array('language' => 'Arabic (Bahrain)', 'locale' => 'ar_bh', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-dz' => array('language' => 'Arabic (Algeria)', 'locale' => 'ar_dz', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-eg' => array('language' => 'Arabic (Egypt)', 'locale' => 'ar_eg', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-iq' => array('language' => 'Arabic (Iraq)', 'locale' => 'ar_iq', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-jo' => array('language' => 'Arabic (Jordan)', 'locale' => 'ar_jo', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-kw' => array('language' => 'Arabic (Kuwait)', 'locale' => 'ar_kw', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-lb' => array('language' => 'Arabic (Lebanon)', 'locale' => 'ar_lb', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-ly' => array('language' => 'Arabic (Libya)', 'locale' => 'ar_ly', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-ma' => array('language' => 'Arabic (Morocco)', 'locale' => 'ar_ma', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-om' => array('language' => 'Arabic (Oman)', 'locale' => 'ar_om', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-qa' => array('language' => 'Arabic (Qatar)', 'locale' => 'ar_qa', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-sa' => array('language' => 'Arabic (Saudi Arabia)', 'locale' => 'ar_sa', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-sy' => array('language' => 'Arabic (Syria)', 'locale' => 'ar_sy', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-tn' => array('language' => 'Arabic (Tunisia)', 'locale' => 'ar_tn', 'localeFallback' => 'ara', 'charset' => ''),
										'ar-ye' => array('language' => 'Arabic (Yemen)', 'locale' => 'ar_ye', 'localeFallback' => 'ara', 'charset' => ''),
										'be' => array('language' => 'Byelorussian', 'locale' => 'bel', 'localeFallback' => 'bel', 'charset' => ''),
										'bg' => array('language' => 'Bulgarian', 'locale' => 'bul', 'localeFallback' => 'bul', 'charset' => ''),
										'ca' => array('language' => 'Catalan', 'locale' => 'cat', 'localeFallback' => 'cat', 'charset' => ''),
										'cs' => array('language' => 'Czech', 'locale' => 'cze', 'localeFallback' => 'cze', 'charset' => ''),
										'da' => array('language' => 'Danish', 'locale' => 'dan', 'localeFallback' => 'dan', 'charset' => ''),
										'de' => array('language' => 'German  (Standard)', 'locale' => 'deu', 'localeFallback' => 'deu', 'charset' => ''),
										'de-at' => array('language' => 'German  (Austria)', 'locale' => 'de_at', 'localeFallback' => 'deu', 'charset' => ''),
										'de-ch' => array('language' => 'German  (Swiss)', 'locale' => 'de_ch', 'localeFallback' => 'deu', 'charset' => ''),
										'de-de' => array('language' => 'German  (Germany)', 'locale' => 'de_de', 'localeFallback' => 'deu', 'charset' => ''),
										'de-li' => array('language' => 'German  (Liechtenstein)', 'locale' => 'de_li', 'localeFallback' => 'deu', 'charset' => ''),
										'de-lu' => array('language' => 'German  (Luxembourg)', 'locale' => 'de_lu', 'localeFallback' => 'deu', 'charset' => ''),
										'e' => array('language' => 'Greek', 'locale' => 'gre', 'localeFallback' => 'gre', 'charset' => ''),
										'el' => array('language' => 'Greek', 'locale' => 'gre', 'localeFallback' => 'gre', 'charset' => ''),
										'en' => array('language' => 'English', 'locale' => 'eng', 'localeFallback' => 'eng', 'charset' => ''),
										'en-au' => array('language' => 'English (Australian)', 'locale' => 'en_au', 'localeFallback' => 'eng', 'charset' => ''),
										'en-bz' => array('language' => 'English (Belize)', 'locale' => 'en_bz', 'localeFallback' => 'eng', 'charset' => ''),
										'en-ca' => array('language' => 'English (Canadian)', 'locale' => 'en_ca', 'localeFallback' => 'eng', 'charset' => ''),
										'en-gb' => array('language' => 'English (British)', 'locale' => 'en_gb', 'localeFallback' => 'eng', 'charset' => ''),
										'en-ie' => array('language' => 'English (Ireland)', 'locale' => 'en_ie', 'localeFallback' => 'eng', 'charset' => ''),
										'en-jm' => array('language' => 'English (Jamaica)', 'locale' => 'en_jm', 'localeFallback' => 'eng', 'charset' => ''),
										'en-nz' => array('language' => 'English (New Zealand)', 'locale' => 'en_nz', 'localeFallback' => 'eng', 'charset' => ''),
										'en-tt' => array('language' => 'English (Trinidad)', 'locale' => 'en_tt', 'localeFallback' => 'eng', 'charset' => ''),
										'en-us' => array('language' => 'English (United States)', 'locale' => 'en_us', 'localeFallback' => 'eng', 'charset' => ''),
										'en-za' => array('language' => 'English (South Africa)', 'locale' => 'en_za', 'localeFallback' => 'eng', 'charset' => ''),
										'es' => array('language' => 'Spanish (Spain - Traditional)', 'locale' => 'spa', 'localeFallback' => 'spa', 'charset' => ''),
										'es-ar' => array('language' => 'Spanish (Argentina)', 'locale' => 'es_ar', 'localeFallback' => 'spa', 'charset' => ''),
										'es-bo' => array('language' => 'Spanish (Bolivia)', 'locale' => 'es_bo', 'localeFallback' => 'spa', 'charset' => ''),
										'es-cl' => array('language' => 'Spanish (Chile)', 'locale' => 'es_cl', 'localeFallback' => 'spa', 'charset' => ''),
										'es-co' => array('language' => 'Spanish (Colombia)', 'locale' => 'es_co', 'localeFallback' => 'spa', 'charset' => ''),
										'es-cr' => array('language' => 'Spanish (Costa Rica)', 'locale' => 'es_cr', 'localeFallback' => 'spa', 'charset' => ''),
										'es-do' => array('language' => 'Spanish (Dominican Republic)', 'locale' => 'es_do', 'localeFallback' => 'spa', 'charset' => ''),
										'es-ec' => array('language' => 'Spanish (Ecuador)', 'locale' => 'es_ec', 'localeFallback' => 'spa', 'charset' => ''),
										'es-es' => array('language' => 'Spanish (Spain)', 'locale' => 'es_es', 'localeFallback' => 'spa', 'charset' => ''),
										'es-gt' => array('language' => 'Spanish (Guatemala)', 'locale' => 'es_gt', 'localeFallback' => 'spa', 'charset' => ''),
										'es-hn' => array('language' => 'Spanish (Honduras)', 'locale' => 'es_hn', 'localeFallback' => 'spa', 'charset' => ''),
										'es-mx' => array('language' => 'Spanish (Mexican)', 'locale' => 'es_mx', 'localeFallback' => 'spa', 'charset' => ''),
										'es-ni' => array('language' => 'Spanish (Nicaragua)', 'locale' => 'es_ni', 'localeFallback' => 'spa', 'charset' => ''),
										'es-pa' => array('language' => 'Spanish (Panama)', 'locale' => 'es_pa', 'localeFallback' => 'spa', 'charset' => ''),
										'es-pe' => array('language' => 'Spanish (Peru)', 'locale' => 'es_pe', 'localeFallback' => 'spa', 'charset' => ''),
										'es-pr' => array('language' => 'Spanish (Puerto Rico)', 'locale' => 'es_pr', 'localeFallback' => 'spa', 'charset' => ''),
										'es-py' => array('language' => 'Spanish (Paraguay)', 'locale' => 'es_py', 'localeFallback' => 'spa', 'charset' => ''),
										'es-sv' => array('language' => 'Spanish (El Salvador)', 'locale' => 'es_sv', 'localeFallback' => 'spa', 'charset' => ''),
										'es-uy' => array('language' => 'Spanish (Uruguay)', 'locale' => 'es_uy', 'localeFallback' => 'spa', 'charset' => ''),
										'es-ve' => array('language' => 'Spanish (Venezuela)', 'locale' => 'es_ve', 'localeFallback' => 'spa', 'charset' => ''),
										'et' => array('language' => 'Estonian', 'locale' => 'est', 'localeFallback' => 'est', 'charset' => ''),
										'eu' => array('language' => 'Basque', 'locale' => 'baq', 'localeFallback' => 'baq', 'charset' => ''),
										'fa' => array('language' => 'Faeroese', 'locale' => 'per', 'localeFallback' => 'per', 'charset' => ''),
										'fi' => array('language' => 'Finnish', 'locale' => 'fin', 'localeFallback' => 'fin', 'charset' => ''),
										'fo' => array('language' => 'Faeroese', 'locale' => 'fao', 'localeFallback' => 'fao', 'charset' => ''),
										'fr' => array('language' => 'French (Standard)', 'locale' => 'fre', 'localeFallback' => 'fre', 'charset' => ''),
										'fr-be' => array('language' => 'French (Belgium)', 'locale' => 'fr_be', 'localeFallback' => 'fre', 'charset' => ''),
										'fr-ca' => array('language' => 'French (Canadian)', 'locale' => 'fr_ca', 'localeFallback' => 'fre', 'charset' => ''),
										'fr-ch' => array('language' => 'French (Swiss)', 'locale' => 'fr_ch', 'localeFallback' => 'fre', 'charset' => ''),
										'fr-fr' => array('language' => 'French (France)', 'locale' => 'fr_fr', 'localeFallback' => 'fre', 'charset' => ''),
										'fr-lu' => array('language' => 'French (Luxembourg)', 'locale' => 'fr_lu', 'localeFallback' => 'fre', 'charset' => ''),
										'ga' => array('language' => 'Irish', 'locale' => 'gle', 'localeFallback' => 'gle', 'charset' => ''),
										'gd' => array('language' => 'Gaelic (Scots)', 'locale' => 'gla', 'localeFallback' => 'gla', 'charset' => ''),
										'gd-ie' => array('language' => 'Gaelic (Irish)', 'locale' => 'gd_ie', 'localeFallback' => 'gla', 'charset' => ''),
										'gl' => array('language' => 'Galician', 'locale' => 'glg', 'localeFallback' => 'glg', 'charset' => ''),
										'he' => array('language' => 'Hebrew', 'locale' => 'heb', 'localeFallback' => 'heb', 'charset' => ''),
										'hi' => array('language' => 'Hindi', 'locale' => 'hin', 'localeFallback' => 'hin', 'charset' => ''),
										'hr' => array('language' => 'Croatian', 'locale' => 'scr', 'localeFallback' => 'scr', 'charset' => ''),
										'hu' => array('language' => 'Hungarian', 'locale' => 'hun', 'localeFallback' => 'hun', 'charset' => ''),
										'id' => array('language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind', 'charset' => ''),
										'in' => array('language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind', 'charset' => ''),
										'is' => array('language' => 'Icelandic', 'locale' => 'ice', 'localeFallback' => 'ice', 'charset' => ''),
										'it' => array('language' => 'Italian', 'locale' => 'ita', 'localeFallback' => 'ita', 'charset' => ''),
										'it-ch' => array('language' => 'Italian (Swiss) ', 'locale' => 'it_ch', 'localeFallback' => 'ita', 'charset' => ''),
										'ja' => array('language' => 'Japanese', 'locale' => 'jpn', 'localeFallback' => 'jpn', 'charset' => ''),
										'ko' => array('language' => 'Korean', 'locale' => 'kor', 'localeFallback' => 'kor', 'charset' => ''),
										'ko-kp' => array('language' => 'Korea (North)', 'locale' => 'ko_kp', 'localeFallback' => 'kor', 'charset' => ''),
										'ko-kr' => array('language' => 'Korea (South)', 'locale' => 'ko_kr', 'localeFallback' => 'kor', 'charset' => ''),
										'koi8-r' => array('language' => 'Russian', 'locale' => 'koi8_r', 'localeFallback' => 'rus', 'charset' => ''),
										'lt' => array('language' => 'Lithuanian', 'locale' => 'lit', 'localeFallback' => 'lit', 'charset' => ''),
										'lv' => array('language' => 'Latvian)', 'locale' => 'lav', 'localeFallback' => 'lav', 'charset' => ''),
										'mk' => array('language' => 'FYRO Macedonian', 'locale' => 'mk', 'localeFallback' => 'mac', 'charset' => ''),
										'mk-mk' => array('language' => 'Macedonian', 'locale' => 'mk_mk', 'localeFallback' => 'mac', 'charset' => ''),
										'ms' => array('language' => 'Malaysian', 'locale' => 'may', 'localeFallback' => 'may', 'charset' => ''),
										'mt' => array('language' => 'Maltese', 'locale' => 'mlt', 'localeFallback' => 'mlt', 'charset' => ''),
										'n' => array('language' => 'Dutch (Standard)', 'locale' => 'dut', 'localeFallback' => 'dut', 'charset' => ''),
										'nb' => array('language' => 'Norwegian Bokmal', 'locale' => 'nob', 'localeFallback' => 'nor', 'charset' => ''),
										'nl' => array('language' => 'Dutch (Standard)', 'locale' => 'dut', 'localeFallback' => 'dut', 'charset' => ''),
										'nl-be' => array('language' => 'Dutch (Belgium)', 'locale' => 'nl_be', 'localeFallback' => 'dut', 'charset' => ''),
										'nn' => array('language' => 'Norwegian Nynorsk', 'locale' => 'nno', 'localeFallback' => 'nor', 'charset' => ''),
										'no' => array('language' => 'Norwegian', 'locale' => 'nor', 'localeFallback' => 'nor', 'charset' => ''),
										'p' => array('language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol', 'charset' => ''),
										'pl' => array('language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol', 'charset' => ''),
										'pt' => array('language' => 'Portuguese (Portugal)', 'locale' => 'por', 'localeFallback' => 'por', 'charset' => ''),
										'pt-br' => array('language' => 'Portuguese (Brazil)', 'locale' => 'pt_br', 'localeFallback' => 'por', 'charset' => ''),
										'rm' => array('language' => 'Rhaeto-Romanic', 'locale' => 'roh', 'localeFallback' => 'roh', 'charset' => ''),
										'ro' => array('language' => 'Romanian', 'locale' => 'rum', 'localeFallback' => 'rum', 'charset' => ''),
										'ro' => array('language' => 'Russian', 'locale' => 'rus', 'localeFallback' => 'rus', 'charset' => ''),
										'ro-mo' => array('language' => 'Romanian (Moldavia)', 'locale' => 'ro_mo', 'localeFallback' => 'rum', 'charset' => ''),
										'ru-mo' => array('language' => 'Russian (Moldavia)', 'locale' => 'ru_mo', 'localeFallback' => 'rus', 'charset' => ''),
										'sb' => array('language' => 'Slovenian', 'locale' => 'wen', 'localeFallback' => 'wen', 'charset' => ''),
										'sk' => array('language' => 'Slovack', 'locale' => 'slo', 'localeFallback' => 'slo', 'charset' => ''),
										'sl' => array('language' => 'Slovenian', 'locale' => 'slv', 'localeFallback' => 'slv', 'charset' => ''),
										'sq' => array('language' => 'Albanian', 'locale' => 'alb', 'localeFallback' => 'alb', 'charset' => ''),
										'sr' => array('language' => 'Serbian', 'locale' => 'scc', 'localeFallback' => 'scc', 'charset' => ''),
										'sv' => array('language' => 'Swedish', 'locale' => 'swe', 'localeFallback' => 'swe', 'charset' => ''),
										'sv-fi' => array('language' => 'Swedish (Findland)', 'locale' => 'sv_fi', 'localeFallback' => 'swe', 'charset' => ''),
										'sx' => array('language' => 'Sutu', 'locale' => 'sx', 'localeFallback' => 'sx', 'charset' => ''),
										'sz' => array('language' => 'Sami (Lappish)', 'locale' => 'smi', 'localeFallback' => 'smi', 'charset' => ''),
										'th' => array('language' => 'Thai', 'locale' => 'tha', 'localeFallback' => 'tha', 'charset' => ''),
										'tn' => array('language' => 'Tswana', 'locale' => 'tsn', 'localeFallback' => 'tsn', 'charset' => ''),
										'tr' => array('language' => 'Turkish', 'locale' => 'tur', 'localeFallback' => 'tur', 'charset' => ''),
										'ts' => array('language' => 'Tsonga', 'locale' => 'tso', 'localeFallback' => 'tso', 'charset' => ''),
										'uk' => array('language' => 'Ukrainian', 'locale' => 'ukr', 'localeFallback' => 'ukr', 'charset' => ''),
										'ur' => array('language' => 'Urdu', 'locale' => 'urd', 'localeFallback' => 'urd', 'charset' => ''),
										've' => array('language' => 'Venda', 'locale' => 'ven', 'localeFallback' => 'ven', 'charset' => ''),
										'vi' => array('language' => 'Vietnamese', 'locale' => 'vie', 'localeFallback' => 'vie', 'charset' => ''),
										'xh' => array('language' => 'Xhosa', 'locale' => 'xho', 'localeFallback' => 'xho', 'charset' => ''),
										'yi' => array('language' => 'Yiddish', 'locale' => 'yid', 'localeFallback' => 'yid', 'charset' => ''),
										'zh' => array('language' => 'Chinese', 'locale' => 'chi', 'localeFallback' => 'chi', 'charset' => ''),
										'zh-cn' => array('language' => 'Chinese (PRC)', 'locale' => 'zh_cn', 'localeFallback' => 'chi', 'charset' => ''),
										'zh-hk' => array('language' => 'Chinese (Hong Kong)', 'locale' => 'zh_hk', 'localeFallback' => 'chi', 'charset' => ''),
										'zh-sg' => array('language' => 'Chinese (Singapore)', 'locale' => 'zh_sg', 'localeFallback' => 'chi', 'charset' => ''),
										'zh-tw' => array('language' => 'Chinese (Taiwan)', 'locale' => 'zh_tw', 'localeFallback' => 'chi', 'charset' => ''),
										'zu' => array('language' => 'Zulu', 'locale' => 'zul', 'localeFallback' => 'zul', 'charset' => ''));
/**
 * Enter description here...
 *
 */
	function __construct() {
		if (defined('DEFAULT_LANGUAGE')) {
			$this->default = DEFAULT_LANGUAGE;
		}
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
			$this->languagePath = array(0 => $this->__l10nCatalog[$this->__l10nMap[$language]]['locale'],
													1 => $this->__l10nCatalog[$this->__l10nMap[$language]]['localeFallback']);
			$this->lang = $language;
			$this->locale = $this->__l10nCatalog[$this->__l10nMap[$language]]['locale'];
			$this->charset = $this->__l10nCatalog[$this->__l10nMap[$language]]['charset'];
		} elseif (defined('DEFAULT_LANGUAGE')) {
			$this->language = $this->__l10nCatalog[$this->__l10nMap[DEFAULT_LANGUAGE]]['language'];
			$this->languagePath = array(0 => $this->__l10nCatalog[$this->__l10nMap[DEFAULT_LANGUAGE]]['locale'],
													1 => $this->__l10nCatalog[$this->__l10nMap[DEFAULT_LANGUAGE]]['localeFallback']);
			$this->lang = DEFAULT_LANGUAGE;
			$this->locale = $this->__l10nCatalog[$this->__l10nMap[DEFAULT_LANGUAGE]]['locale'];
			$this->charset = $this->__l10nCatalog[$this->__l10nMap[DEFAULT_LANGUAGE]]['charset'];
		}
		if($this->default) {
			$this->languagePath = array(2 => $this->__l10nCatalog[$this->default]['localeFallback']);
		}
	}
/**
 * Enter description here...
 * @access private
 */
	function __autoLanguage() {
		$_detectableLanguages = split ('[,;]', env('HTTP_ACCEPT_LANGUAGE'));
		foreach ($_detectableLanguages as $key => $langKey) {
			if (isset($this->__l10nCatalog[$langKey])) {

				$this->language = $this->__l10nCatalog[$langKey]['language'];
				$this->languagePath = array(0 => $this->__l10nCatalog[$langKey]['locale'],
														1 => $this->__l10nCatalog[$langKey]['localeFallback']);
				$this->lang = $langKey;
				$this->locale = $this->__l10nCatalog[$langKey]['locale'];
				$this->charset = $this->__l10nCatalog[$langKey]['charset'];

				if($this->default) {
					$this->languagePath = array(2 => $this->__l10nCatalog[$this->default]['localeFallback']);
				}
				break;
			}
		}
	}
}
?>