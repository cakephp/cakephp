<?php
/**
 * Localization
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.I18n
 * @since         CakePHP(tm) v 1.2.0.4116
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('CakeRequest', 'Network');

/**
 * Localization
 *
 * @package       Cake.I18n
 */
class L10n {

/**
 * The language for current locale
 *
 * @var string
 */
	public $language = 'English (United States)';

/**
 * Locale search paths
 *
 * @var array
 */
	public $languagePath = array('eng');

/**
 * ISO 639-3 for current locale
 *
 * @var string
 */
	public $lang = 'eng';

/**
 * Locale
 *
 * @var string
 */
	public $locale = 'en_us';

/**
 * Default ISO 639-3 language.
 *
 * DEFAULT_LANGUAGE is defined in an application this will be set as a fall back
 *
 * @var string
 */
	public $default = null;

/**
 * Encoding used for current locale
 *
 * @var string
 */
	public $charset = 'utf-8';

/**
 * Text direction for current locale
 *
 * @var string
 */
	public $direction = 'ltr';

/**
 * Set to true if a locale is found
 *
 * @var string
 */
	public $found = false;

/**
 * Maps ISO 639-3 to I10n::_l10nCatalog
 *
 * @var array
 */
	protected $_l10nMap = array(/* Afrikaans */ 'afr' => 'af',
								/* Albanian */ 'alb' => 'sq',
								/* Arabic */ 'ara' => 'ar',
								/* Armenian - Armenia */ 'hye' => 'hy',
								/* Basque */ 'baq' => 'eu',
								/* Tibetan */ 'bod' => 'bo',
								/* Bosnian */ 'bos' => 'bs',
								/* Bulgarian */ 'bul' => 'bg',
								/* Byelorussian */ 'bel' => 'be',
								/* Catalan */ 'cat' => 'ca',
								/* Chinese */ 'chi' => 'zh',
								/* Chinese */ 'zho' => 'zh',
								/* Croatian */ 'hrv' => 'hr',
								/* Czech */ 'cze' => 'cs',
								/* Czech */ 'ces' => 'cs',
								/* Danish */ 'dan' => 'da',
								/* Dutch (Standard) */ 'dut' => 'nl',
								/* Dutch (Standard) */ 'nld' => 'nl',
								/* English */ 'eng' => 'en',
								/* Estonian */ 'est' => 'et',
								/* Faeroese */ 'fao' => 'fo',
								/* Farsi */ 'fas' => 'fa',
								/* Farsi */ 'per' => 'fa',
								/* Finnish */ 'fin' => 'fi',
								/* French (Standard) */ 'fre' => 'fr',
								/* French (Standard) */ 'fra' => 'fr',
								/* Gaelic (Scots) */ 'gla' => 'gd',
								/* Galician */ 'glg' => 'gl',
								/* German (Standard) */ 'deu' => 'de',
								/* German (Standard) */ 'ger' => 'de',
								/* Greek */ 'gre' => 'el',
								/* Greek */ 'ell' => 'el',
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
								/* Macedonian */ 'mkd' => 'mk',
								/* Malaysian */ 'may' => 'ms',
								/* Malaysian */ 'msa' => 'ms',
								/* Maltese */ 'mlt' => 'mt',
								/* Norwegian */ 'nor' => 'no',
								/* Norwegian Bokmal */ 'nob' => 'nb',
								/* Norwegian Nynorsk */ 'nno' => 'nn',
								/* Polish */ 'pol' => 'pl',
								/* Portuguese (Portugal) */ 'por' => 'pt',
								/* Rhaeto-Romanic */ 'roh' => 'rm',
								/* Romanian */ 'rum' => 'ro',
								/* Romanian */ 'ron' => 'ro',
								/* Russian */ 'rus' => 'ru',
								/* Sami (Lappish) */ 'smi' => 'sz',
								/* Serbian */ 'scc' => 'sr',
								/* Serbian */ 'srp' => 'sr',
								/* Slovak */ 'slo' => 'sk',
								/* Slovak */ 'slk' => 'sk',
								/* Slovenian */ 'slv' => 'sl',
								/* Sorbian */ 'wen' => 'sb',
								/* Spanish (Spain - Traditional) */ 'spa' => 'es',
								/* Swedish */ 'swe' => 'sv',
								/* Thai */ 'tha' => 'th',
								/* Tsonga */ 'tso' => 'ts',
								/* Tswana */ 'tsn' => 'tn',
								/* Turkish */ 'tur' => 'tr',
								/* Ukrainian */ 'ukr' => 'uk',
								/* Urdu */ 'urd' => 'ur',
								/* Venda */ 'ven' => 've',
								/* Vietnamese */ 'vie' => 'vi',
								/* Welsh */ 'cym' => 'cy',
								/* Xhosa */ 'xho' => 'xh',
								/* Yiddish */ 'yid' => 'yi',
								/* Zulu */ 'zul' => 'zu');

/**
 * HTTP_ACCEPT_LANGUAGE catalog
 *
 * holds all information related to a language
 *
 * @var array
 */
	protected $_l10nCatalog = array('af' => array('language' => 'Afrikaans', 'locale' => 'afr', 'localeFallback' => 'afr', 'charset' => 'utf-8', 'direction' => 'ltr'),
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
										'ar-ye' => array('language' => 'Arabic (Yemen)', 'locale' => 'ar_ye', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'),
										'be' => array('language' => 'Byelorussian', 'locale' => 'bel', 'localeFallback' => 'bel', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'bg' => array('language' => 'Bulgarian', 'locale' => 'bul', 'localeFallback' => 'bul', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'bo' => array('language' => 'Tibetan', 'locale' => 'bod', 'localeFallback' => 'bod', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'bo-cn' => array('language' => 'Tibetan (China)', 'locale' => 'bo_cn', 'localeFallback' => 'bod', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'bo-in' => array('language' => 'Tibetan (India)', 'locale' => 'bo_in', 'localeFallback' => 'bod', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'bs' => array('language' => 'Bosnian', 'locale' => 'bos', 'localeFallback' => 'bos', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'ca' => array('language' => 'Catalan', 'locale' => 'cat', 'localeFallback' => 'cat', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'cs' => array('language' => 'Czech', 'locale' => 'cze', 'localeFallback' => 'cze', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'da' => array('language' => 'Danish', 'locale' => 'dan', 'localeFallback' => 'dan', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'de' => array('language' => 'German (Standard)', 'locale' => 'deu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'de-at' => array('language' => 'German (Austria)', 'locale' => 'de_at', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'de-ch' => array('language' => 'German (Swiss)', 'locale' => 'de_ch', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'de-de' => array('language' => 'German (Germany)', 'locale' => 'de_de', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'de-li' => array('language' => 'German (Liechtenstein)', 'locale' => 'de_li', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'de-lu' => array('language' => 'German (Luxembourg)', 'locale' => 'de_lu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'e' => array('language' => 'Greek', 'locale' => 'gre', 'localeFallback' => 'gre', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'el' => array('language' => 'Greek', 'locale' => 'gre', 'localeFallback' => 'gre', 'charset' => 'utf-8', 'direction' => 'ltr'),
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
										'en-za' => array('language' => 'English (South Africa)', 'locale' => 'en_za', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'),
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
										'es-ve' => array('language' => 'Spanish (Venezuela)', 'locale' => 'es_ve', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'et' => array('language' => 'Estonian', 'locale' => 'est', 'localeFallback' => 'est', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'eu' => array('language' => 'Basque', 'locale' => 'baq', 'localeFallback' => 'baq', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'fa' => array('language' => 'Farsi', 'locale' => 'per', 'localeFallback' => 'per', 'charset' => 'utf-8', 'direction' => 'rtl'),
										'fi' => array('language' => 'Finnish', 'locale' => 'fin', 'localeFallback' => 'fin', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'fo' => array('language' => 'Faeroese', 'locale' => 'fao', 'localeFallback' => 'fao', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'fr' => array('language' => 'French (Standard)', 'locale' => 'fre', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'fr-be' => array('language' => 'French (Belgium)', 'locale' => 'fr_be', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'fr-ca' => array('language' => 'French (Canadian)', 'locale' => 'fr_ca', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'fr-ch' => array('language' => 'French (Swiss)', 'locale' => 'fr_ch', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'fr-fr' => array('language' => 'French (France)', 'locale' => 'fr_fr', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'fr-lu' => array('language' => 'French (Luxembourg)', 'locale' => 'fr_lu', 'localeFallback' => 'fre', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'ga' => array('language' => 'Irish', 'locale' => 'gle', 'localeFallback' => 'gle', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'gd' => array('language' => 'Gaelic (Scots)', 'locale' => 'gla', 'localeFallback' => 'gla', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'gd-ie' => array('language' => 'Gaelic (Irish)', 'locale' => 'gd_ie', 'localeFallback' => 'gla', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'gl' => array('language' => 'Galician', 'locale' => 'glg', 'localeFallback' => 'glg', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'he' => array('language' => 'Hebrew', 'locale' => 'heb', 'localeFallback' => 'heb', 'charset' => 'utf-8', 'direction' => 'rtl'),
										'hi' => array('language' => 'Hindi', 'locale' => 'hin', 'localeFallback' => 'hin', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'hr' => array('language' => 'Croatian', 'locale' => 'hrv', 'localeFallback' => 'hrv', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'hu' => array('language' => 'Hungarian', 'locale' => 'hun', 'localeFallback' => 'hun', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'hy' => array('language' => 'Armenian - Armenia', 'locale' => 'hye', 'localeFallback' => 'hye', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'id' => array('language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'in' => array('language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'is' => array('language' => 'Icelandic', 'locale' => 'ice', 'localeFallback' => 'ice', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'it' => array('language' => 'Italian', 'locale' => 'ita', 'localeFallback' => 'ita', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'it-ch' => array('language' => 'Italian (Swiss) ', 'locale' => 'it_ch', 'localeFallback' => 'ita', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'ja' => array('language' => 'Japanese', 'locale' => 'jpn', 'localeFallback' => 'jpn', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'ko' => array('language' => 'Korean', 'locale' => 'kor', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'),
										'ko-kp' => array('language' => 'Korea (North)', 'locale' => 'ko_kp', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'),
										'ko-kr' => array('language' => 'Korea (South)', 'locale' => 'ko_kr', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'),
										'koi8-r' => array('language' => 'Russian', 'locale' => 'koi8_r', 'localeFallback' => 'rus', 'charset' => 'koi8-r', 'direction' => 'ltr'),
										'lt' => array('language' => 'Lithuanian', 'locale' => 'lit', 'localeFallback' => 'lit', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'lv' => array('language' => 'Latvian', 'locale' => 'lav', 'localeFallback' => 'lav', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'mk' => array('language' => 'FYRO Macedonian', 'locale' => 'mk', 'localeFallback' => 'mac', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'mk-mk' => array('language' => 'Macedonian', 'locale' => 'mk_mk', 'localeFallback' => 'mac', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'ms' => array('language' => 'Malaysian', 'locale' => 'may', 'localeFallback' => 'may', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'mt' => array('language' => 'Maltese', 'locale' => 'mlt', 'localeFallback' => 'mlt', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'n' => array('language' => 'Dutch (Standard)', 'locale' => 'dut', 'localeFallback' => 'dut', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'nb' => array('language' => 'Norwegian Bokmal', 'locale' => 'nob', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'nl' => array('language' => 'Dutch (Standard)', 'locale' => 'dut', 'localeFallback' => 'dut', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'nl-be' => array('language' => 'Dutch (Belgium)', 'locale' => 'nl_be', 'localeFallback' => 'dut', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'nn' => array('language' => 'Norwegian Nynorsk', 'locale' => 'nno', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'no' => array('language' => 'Norwegian', 'locale' => 'nor', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'p' => array('language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'pl' => array('language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'pt' => array('language' => 'Portuguese (Portugal)', 'locale' => 'por', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'pt-br' => array('language' => 'Portuguese (Brazil)', 'locale' => 'pt_br', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'rm' => array('language' => 'Rhaeto-Romanic', 'locale' => 'roh', 'localeFallback' => 'roh', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'ro' => array('language' => 'Romanian', 'locale' => 'rum', 'localeFallback' => 'rum', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'ro-mo' => array('language' => 'Romanian (Moldavia)', 'locale' => 'ro_mo', 'localeFallback' => 'rum', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'ru' => array('language' => 'Russian', 'locale' => 'rus', 'localeFallback' => 'rus', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'ru-mo' => array('language' => 'Russian (Moldavia)', 'locale' => 'ru_mo', 'localeFallback' => 'rus', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'sb' => array('language' => 'Sorbian', 'locale' => 'wen', 'localeFallback' => 'wen', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'sk' => array('language' => 'Slovak', 'locale' => 'slo', 'localeFallback' => 'slo', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'sl' => array('language' => 'Slovenian', 'locale' => 'slv', 'localeFallback' => 'slv', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'sq' => array('language' => 'Albanian', 'locale' => 'alb', 'localeFallback' => 'alb', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'sr' => array('language' => 'Serbian', 'locale' => 'scc', 'localeFallback' => 'scc', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'sv' => array('language' => 'Swedish', 'locale' => 'swe', 'localeFallback' => 'swe', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'sv-fi' => array('language' => 'Swedish (Finland)', 'locale' => 'sv_fi', 'localeFallback' => 'swe', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'sx' => array('language' => 'Sutu', 'locale' => 'sx', 'localeFallback' => 'sx', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'sz' => array('language' => 'Sami (Lappish)', 'locale' => 'smi', 'localeFallback' => 'smi', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'th' => array('language' => 'Thai', 'locale' => 'tha', 'localeFallback' => 'tha', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'tn' => array('language' => 'Tswana', 'locale' => 'tsn', 'localeFallback' => 'tsn', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'tr' => array('language' => 'Turkish', 'locale' => 'tur', 'localeFallback' => 'tur', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'ts' => array('language' => 'Tsonga', 'locale' => 'tso', 'localeFallback' => 'tso', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'uk' => array('language' => 'Ukrainian', 'locale' => 'ukr', 'localeFallback' => 'ukr', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'ur' => array('language' => 'Urdu', 'locale' => 'urd', 'localeFallback' => 'urd', 'charset' => 'utf-8', 'direction' => 'rtl'),
										've' => array('language' => 'Venda', 'locale' => 'ven', 'localeFallback' => 'ven', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'vi' => array('language' => 'Vietnamese', 'locale' => 'vie', 'localeFallback' => 'vie', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'cy' => array('language' => 'Welsh', 'locale' => 'cym', 'localeFallback' => 'cym', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'xh' => array('language' => 'Xhosa', 'locale' => 'xho', 'localeFallback' => 'xho', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'yi' => array('language' => 'Yiddish', 'locale' => 'yid', 'localeFallback' => 'yid', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'zh' => array('language' => 'Chinese', 'locale' => 'chi', 'localeFallback' => 'chi', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'zh-cn' => array('language' => 'Chinese (PRC)', 'locale' => 'zh_cn', 'localeFallback' => 'chi', 'charset' => 'GB2312', 'direction' => 'ltr'),
										'zh-hk' => array('language' => 'Chinese (Hong Kong)', 'locale' => 'zh_hk', 'localeFallback' => 'chi', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'zh-sg' => array('language' => 'Chinese (Singapore)', 'locale' => 'zh_sg', 'localeFallback' => 'chi', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'zh-tw' => array('language' => 'Chinese (Taiwan)', 'locale' => 'zh_tw', 'localeFallback' => 'chi', 'charset' => 'utf-8', 'direction' => 'ltr'),
										'zu' => array('language' => 'Zulu', 'locale' => 'zul', 'localeFallback' => 'zul', 'charset' => 'utf-8', 'direction' => 'ltr'));

/**
 * Class constructor
 */
	public function __construct() {
		if (defined('DEFAULT_LANGUAGE')) {
			$this->default = DEFAULT_LANGUAGE;
		}
	}

/**
 * Gets the settings for $language.
 * If $language is null it attempt to get settings from L10n::_autoLanguage(); if this fails
 * the method will get the settings from L10n::_setLanguage();
 *
 * @param string $language Language (if null will use DEFAULT_LANGUAGE if defined)
 * @return mixed
 */
	public function get($language = null) {
		if ($language !== null) {
			return $this->_setLanguage($language);
		} elseif ($this->_autoLanguage() === false) {
			return $this->_setLanguage();
		}
	}

/**
 * Sets the class vars to correct values for $language.
 * If $language is null it will use the DEFAULT_LANGUAGE if defined
 *
 * @param string $language Language (if null will use DEFAULT_LANGUAGE if defined)
 * @return mixed
 */
	protected function _setLanguage($language = null) {
		$langKey = null;
		if ($language !== null && isset($this->_l10nMap[$language]) && isset($this->_l10nCatalog[$this->_l10nMap[$language]])) {
			$langKey = $this->_l10nMap[$language];
		} else if ($language !== null && isset($this->_l10nCatalog[$language])) {
			$langKey = $language;
		} else if (defined('DEFAULT_LANGUAGE')) {
			$langKey = $language = DEFAULT_LANGUAGE;
		}

		if ($langKey !== null && isset($this->_l10nCatalog[$langKey])) {
			$this->language = $this->_l10nCatalog[$langKey]['language'];
			$this->languagePath = array(
				$this->_l10nCatalog[$langKey]['locale'],
				$this->_l10nCatalog[$langKey]['localeFallback']
			);
			$this->lang = $language;
			$this->locale = $this->_l10nCatalog[$langKey]['locale'];
			$this->charset = $this->_l10nCatalog[$langKey]['charset'];
			$this->direction = $this->_l10nCatalog[$langKey]['direction'];
		} else {
			$this->lang = $language;
			$this->languagePath = array($language);
		}

		if ($this->default) {
			if (isset($this->_l10nMap[$this->default]) && isset($this->_l10nCatalog[$this->_l10nMap[$this->default]])) {
				$this->languagePath[] = $this->_l10nCatalog[$this->_l10nMap[$this->default]]['localeFallback'];
			} else if (isset($this->_l10nCatalog[$this->default])) {
				$this->languagePath[] = $this->_l10nCatalog[$this->default]['localeFallback'];
			}
		}
		$this->found = true;

		if (Configure::read('Config.language') === null) {
			Configure::write('Config.language', $this->lang);
		}

		if ($language) {
			return $language;
		}
	}

/**
 * Attempts to find the locale settings based on the HTTP_ACCEPT_LANGUAGE variable
 *
 * @return boolean Success
 */
	protected function _autoLanguage() {
		$_detectableLanguages = CakeRequest::acceptLanguage();
		foreach ($_detectableLanguages as $key => $langKey) {
			if (isset($this->_l10nCatalog[$langKey])) {
				$this->_setLanguage($langKey);
				return true;
			} else if (strpos($langKey, '-') !== false) {
				$langKey = substr($langKey, 0, 2);
				if (isset($this->_l10nCatalog[$langKey])) {
					$this->_setLanguage($langKey);
					return true;
				}
			}
		}
		return false;
	}

/**
 * Attempts to find locale for language, or language for locale
 *
 * @param mixed $mixed 2/3 char string (language/locale), array of those strings, or null
 * @return mixed string language/locale, array of those values, whole map as an array,
 *    or false when language/locale doesn't exist
 */
	public function map($mixed = null) {
		if (is_array($mixed)) {
			$result = array();
			foreach ($mixed as $_mixed) {
				if ($_result = $this->map($_mixed)) {
					$result[$_mixed] = $_result;
				}
			}
			return $result;
		} else if (is_string($mixed)) {
			if (strlen($mixed) === 2 && in_array($mixed, $this->_l10nMap)) {
				return array_search($mixed, $this->_l10nMap);
			} else if (isset($this->_l10nMap[$mixed])) {
				return $this->_l10nMap[$mixed];
			}
			return false;
		}
		return $this->_l10nMap;
	}

/**
 * Attempts to find catalog record for requested language
 *
 * @param mixed $language string requested language, array of requested languages, or null for whole catalog
 * @return mixed array catalog record for requested language, array of catalog records, whole catalog,
 *    or false when language doesn't exist
 */
	public function catalog($language = null) {
		if (is_array($language)) {
			$result = array();
			foreach ($language as $_language) {
				if ($_result = $this->catalog($_language)) {
					$result[$_language] = $_result;
				}
			}
			return $result;
		} else if (is_string($language)) {
			if (isset($this->_l10nCatalog[$language])) {
				return $this->_l10nCatalog[$language];
			} else if (isset($this->_l10nMap[$language]) && isset($this->_l10nCatalog[$this->_l10nMap[$language]])) {
				return $this->_l10nCatalog[$this->_l10nMap[$language]];
			}
			return false;
		}
		return $this->_l10nCatalog;
	}
}
