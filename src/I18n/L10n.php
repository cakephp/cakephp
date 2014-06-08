<?php
/**
 * Localization
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Cake\Core\Configure;
use Cake\Network\Request;

/**
 * Localization
 *
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
	public $languagePath = ['en_us', 'eng'];

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
 * Default language.
 *
 * If config value 'Config.language' is set in an application this will be set
 * as a fall back.
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
 * Request object instance
 *
 * @var \Cake\Network\Request
 */
	protected $_request = null;

/**
 * Maps ISO 639-3 to I10n::_l10nCatalog
 * The terminological codes (first one per language) should be used if possible.
 * They are the ones building the path in `/APP/Locale/[code]/`
 * The bibliographic codes are aliases.
 *
 * @var array
 */
	protected $_l10nMap = [
		/* Afrikaans */ 'afr' => 'af',
		/* Albanian */ 'sqi' => 'sq',
		/* Albanian - bibliographic */ 'alb' => 'sq',
		/* Arabic */ 'ara' => 'ar',
		/* Armenian/Armenia */ 'hye' => 'hy',
		/* Basque */ 'eus' => 'eu',
		/* Basque */ 'baq' => 'eu',
		/* Tibetan */ 'bod' => 'bo',
		/* Tibetan - bibliographic */ 'tib' => 'bo',
		/* Bosnian */ 'bos' => 'bs',
		/* Bulgarian */ 'bul' => 'bg',
		/* Byelorussian */ 'bel' => 'be',
		/* Catalan */ 'cat' => 'ca',
		/* Chinese */ 'zho' => 'zh',
		/* Chinese - bibliographic */ 'chi' => 'zh',
		/* Croatian */ 'hrv' => 'hr',
		/* Czech */ 'ces' => 'cs',
		/* Czech - bibliographic */ 'cze' => 'cs',
		/* Danish */ 'dan' => 'da',
		/* Dutch (Standard) */ 'nld' => 'nl',
		/* Dutch (Standard) - bibliographic */ 'dut' => 'nl',
		/* English */ 'eng' => 'en',
		/* Estonian */ 'est' => 'et',
		/* Faeroese */ 'fao' => 'fo',
		/* Farsi/Persian */ 'fas' => 'fa',
		/* Farsi/Persian - bibliographic */ 'per' => 'fa',
		/* Finnish */ 'fin' => 'fi',
		/* French (Standard) */ 'fra' => 'fr',
		/* French (Standard)  - bibliographic */ 'fre' => 'fr',
		/* Gaelic (Scots) */ 'gla' => 'gd',
		/* Galician */ 'glg' => 'gl',
		/* German (Standard) */ 'deu' => 'de',
		/* German (Standard) - bibliographic */ 'ger' => 'de',
		/* Greek */ 'gre' => 'el',
		/* Greek */ 'ell' => 'el',
		/* Hebrew */ 'heb' => 'he',
		/* Hindi */ 'hin' => 'hi',
		/* Hungarian */ 'hun' => 'hu',
		/* Icelandic */ 'isl' => 'is',
		/* Icelandic - bibliographic */ 'ice' => 'is',
		/* Indonesian */ 'ind' => 'id',
		/* Irish */ 'gle' => 'ga',
		/* Italian */ 'ita' => 'it',
		/* Japanese */ 'jpn' => 'ja',
		/* Kazakh */ 'kaz' => 'kk',
		/* Kalaallisut (Greenlandic) */ 'kal' => 'kl',
		/* Korean */ 'kor' => 'ko',
		/* Latvian */ 'lav' => 'lv',
		/* Limburgish */ 'lim' => 'li',
		/* Lithuanian */ 'lit' => 'lt',
		/* Macedonian */ 'mkd' => 'mk',
		/* Macedonian - bibliographic */ 'mac' => 'mk',
		/* Malaysian */ 'msa' => 'ms',
		/* Malaysian - bibliographic */ 'may' => 'ms',
		/* Maltese */ 'mlt' => 'mt',
		/* Norwegian */ 'nor' => 'no',
		/* Norwegian Bokmal */ 'nob' => 'nb',
		/* Norwegian Nynorsk */ 'nno' => 'nn',
		/* Polish */ 'pol' => 'pl',
		/* Portuguese (Portugal) */ 'por' => 'pt',
		/* Rhaeto-Romanic */ 'roh' => 'rm',
		/* Romanian */ 'ron' => 'ro',
		/* Romanian - bibliographic */ 'rum' => 'ro',
		/* Russian */ 'rus' => 'ru',
		/* Sami */ 'sme' => 'se',
		/* Serbian */ 'srp' => 'sr',
		/* Slovak */ 'slk' => 'sk',
		/* Slovak - bibliographic */ 'slo' => 'sk',
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
		/* Welsh - bibliographic */ 'wel' => 'cy',
		/* Xhosa */ 'xho' => 'xh',
		/* Yiddish */ 'yid' => 'yi',
		/* Zulu */ 'zul' => 'zu'
	];

/**
 * HTTP_ACCEPT_LANGUAGE catalog
 *
 * holds all information related to a language
 *
 * @var array
 */
	protected $_l10nCatalog = [
		'af' => ['language' => 'Afrikaans', 'locale' => 'afr', 'localeFallback' => 'afr', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'ar' => ['language' => 'Arabic', 'locale' => 'ara', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-ae' => ['language' => 'Arabic (U.A.E.)', 'locale' => 'ar_ae', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-bh' => ['language' => 'Arabic (Bahrain)', 'locale' => 'ar_bh', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-dz' => ['language' => 'Arabic (Algeria)', 'locale' => 'ar_dz', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-eg' => ['language' => 'Arabic (Egypt)', 'locale' => 'ar_eg', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-iq' => ['language' => 'Arabic (Iraq)', 'locale' => 'ar_iq', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-jo' => ['language' => 'Arabic (Jordan)', 'locale' => 'ar_jo', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-kw' => ['language' => 'Arabic (Kuwait)', 'locale' => 'ar_kw', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-lb' => ['language' => 'Arabic (Lebanon)', 'locale' => 'ar_lb', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-ly' => ['language' => 'Arabic (Libya)', 'locale' => 'ar_ly', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-ma' => ['language' => 'Arabic (Morocco)', 'locale' => 'ar_ma', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-om' => ['language' => 'Arabic (Oman)', 'locale' => 'ar_om', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-qa' => ['language' => 'Arabic (Qatar)', 'locale' => 'ar_qa', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-sa' => ['language' => 'Arabic (Saudi Arabia)', 'locale' => 'ar_sa', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-sy' => ['language' => 'Arabic (Syria)', 'locale' => 'ar_sy', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-tn' => ['language' => 'Arabic (Tunisia)', 'locale' => 'ar_tn', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'ar-ye' => ['language' => 'Arabic (Yemen)', 'locale' => 'ar_ye', 'localeFallback' => 'ara', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'be' => ['language' => 'Byelorussian', 'locale' => 'bel', 'localeFallback' => 'bel', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'bg' => ['language' => 'Bulgarian', 'locale' => 'bul', 'localeFallback' => 'bul', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'bo' => ['language' => 'Tibetan', 'locale' => 'bod', 'localeFallback' => 'bod', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'bo-cn' => ['language' => 'Tibetan (China)', 'locale' => 'bo_cn', 'localeFallback' => 'bod', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'bo-in' => ['language' => 'Tibetan (India)', 'locale' => 'bo_in', 'localeFallback' => 'bod', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'bs' => ['language' => 'Bosnian', 'locale' => 'bos', 'localeFallback' => 'bos', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'ca' => ['language' => 'Catalan', 'locale' => 'cat', 'localeFallback' => 'cat', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'cs' => ['language' => 'Czech', 'locale' => 'ces', 'localeFallback' => 'ces', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'da' => ['language' => 'Danish', 'locale' => 'dan', 'localeFallback' => 'dan', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'de' => ['language' => 'German (Standard)', 'locale' => 'deu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'de-at' => ['language' => 'German (Austria)', 'locale' => 'de_at', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'de-ch' => ['language' => 'German (Swiss)', 'locale' => 'de_ch', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'de-de' => ['language' => 'German (Germany)', 'locale' => 'de_de', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'de-li' => ['language' => 'German (Liechtenstein)', 'locale' => 'de_li', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'de-lu' => ['language' => 'German (Luxembourg)', 'locale' => 'de_lu', 'localeFallback' => 'deu', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'el' => ['language' => 'Greek', 'locale' => 'ell', 'localeFallback' => 'ell', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'en' => ['language' => 'English', 'locale' => 'eng', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'en-au' => ['language' => 'English (Australian)', 'locale' => 'en_au', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'en-bz' => ['language' => 'English (Belize)', 'locale' => 'en_bz', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'en-ca' => ['language' => 'English (Canadian)', 'locale' => 'en_ca', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'en-gb' => ['language' => 'English (British)', 'locale' => 'en_gb', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'en-ie' => ['language' => 'English (Ireland)', 'locale' => 'en_ie', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'en-jm' => ['language' => 'English (Jamaica)', 'locale' => 'en_jm', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'en-nz' => ['language' => 'English (New Zealand)', 'locale' => 'en_nz', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'en-tt' => ['language' => 'English (Trinidad)', 'locale' => 'en_tt', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'en-us' => ['language' => 'English (United States)', 'locale' => 'en_us', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'en-za' => ['language' => 'English (South Africa)', 'locale' => 'en_za', 'localeFallback' => 'eng', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es' => ['language' => 'Spanish (Spain - Traditional)', 'locale' => 'spa', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-ar' => ['language' => 'Spanish (Argentina)', 'locale' => 'es_ar', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-bo' => ['language' => 'Spanish (Bolivia)', 'locale' => 'es_bo', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-cl' => ['language' => 'Spanish (Chile)', 'locale' => 'es_cl', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-co' => ['language' => 'Spanish (Colombia)', 'locale' => 'es_co', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-cr' => ['language' => 'Spanish (Costa Rica)', 'locale' => 'es_cr', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-do' => ['language' => 'Spanish (Dominican Republic)', 'locale' => 'es_do', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-ec' => ['language' => 'Spanish (Ecuador)', 'locale' => 'es_ec', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-es' => ['language' => 'Spanish (Spain)', 'locale' => 'es_es', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-gt' => ['language' => 'Spanish (Guatemala)', 'locale' => 'es_gt', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-hn' => ['language' => 'Spanish (Honduras)', 'locale' => 'es_hn', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-mx' => ['language' => 'Spanish (Mexican)', 'locale' => 'es_mx', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-ni' => ['language' => 'Spanish (Nicaragua)', 'locale' => 'es_ni', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-pa' => ['language' => 'Spanish (Panama)', 'locale' => 'es_pa', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-pe' => ['language' => 'Spanish (Peru)', 'locale' => 'es_pe', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-pr' => ['language' => 'Spanish (Puerto Rico)', 'locale' => 'es_pr', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-py' => ['language' => 'Spanish (Paraguay)', 'locale' => 'es_py', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-sv' => ['language' => 'Spanish (El Salvador)', 'locale' => 'es_sv', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-uy' => ['language' => 'Spanish (Uruguay)', 'locale' => 'es_uy', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'es-ve' => ['language' => 'Spanish (Venezuela)', 'locale' => 'es_ve', 'localeFallback' => 'spa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'et' => ['language' => 'Estonian', 'locale' => 'est', 'localeFallback' => 'est', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'eu' => ['language' => 'Basque', 'locale' => 'eus', 'localeFallback' => 'eus', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'fa' => ['language' => 'Farsi', 'locale' => 'fas', 'localeFallback' => 'fas', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'fi' => ['language' => 'Finnish', 'locale' => 'fin', 'localeFallback' => 'fin', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'fo' => ['language' => 'Faeroese', 'locale' => 'fao', 'localeFallback' => 'fao', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'fr' => ['language' => 'French (Standard)', 'locale' => 'fra', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'fr-be' => ['language' => 'French (Belgium)', 'locale' => 'fr_be', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'fr-ca' => ['language' => 'French (Canadian)', 'locale' => 'fr_ca', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'fr-ch' => ['language' => 'French (Swiss)', 'locale' => 'fr_ch', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'fr-fr' => ['language' => 'French (France)', 'locale' => 'fr_fr', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'fr-lu' => ['language' => 'French (Luxembourg)', 'locale' => 'fr_lu', 'localeFallback' => 'fra', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'ga' => ['language' => 'Irish', 'locale' => 'gle', 'localeFallback' => 'gle', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'gd' => ['language' => 'Gaelic (Scots)', 'locale' => 'gla', 'localeFallback' => 'gla', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'gd-ie' => ['language' => 'Gaelic (Irish)', 'locale' => 'gd_ie', 'localeFallback' => 'gla', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'gl' => ['language' => 'Galician', 'locale' => 'glg', 'localeFallback' => 'glg', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'he' => ['language' => 'Hebrew', 'locale' => 'heb', 'localeFallback' => 'heb', 'charset' => 'utf-8', 'direction' => 'rtl'],
		'hi' => ['language' => 'Hindi', 'locale' => 'hin', 'localeFallback' => 'hin', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'hr' => ['language' => 'Croatian', 'locale' => 'hrv', 'localeFallback' => 'hrv', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'hu' => ['language' => 'Hungarian', 'locale' => 'hun', 'localeFallback' => 'hun', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'hy' => ['language' => 'Armenian - Armenia', 'locale' => 'hye', 'localeFallback' => 'hye', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'id' => ['language' => 'Indonesian', 'locale' => 'ind', 'localeFallback' => 'ind', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'is' => ['language' => 'Icelandic', 'locale' => 'isl', 'localeFallback' => 'isl', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'it' => ['language' => 'Italian', 'locale' => 'ita', 'localeFallback' => 'ita', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'it-ch' => ['language' => 'Italian (Swiss) ', 'locale' => 'it_ch', 'localeFallback' => 'ita', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'ja' => ['language' => 'Japanese', 'locale' => 'jpn', 'localeFallback' => 'jpn', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'kk' => ['language' => 'Kazakh', 'locale' => 'kaz', 'localeFallback' => 'kaz', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'kl' => ['language' => 'Kalaallisut (Greenlandic)', 'locale' => 'kal', 'localeFallback' => 'kal', 'charset' => 'kl', 'direction' => 'ltr'],
		'ko' => ['language' => 'Korean', 'locale' => 'kor', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'],
		'ko-kp' => ['language' => 'Korea (North)', 'locale' => 'ko_kp', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'],
		'ko-kr' => ['language' => 'Korea (South)', 'locale' => 'ko_kr', 'localeFallback' => 'kor', 'charset' => 'kr', 'direction' => 'ltr'],
		'koi8-r' => ['language' => 'Russian', 'locale' => 'koi8_r', 'localeFallback' => 'rus', 'charset' => 'koi8-r', 'direction' => 'ltr'],
		'li' => ['language' => 'Limburgish', 'locale' => 'lim', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'lt' => ['language' => 'Lithuanian', 'locale' => 'lit', 'localeFallback' => 'lit', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'lv' => ['language' => 'Latvian', 'locale' => 'lav', 'localeFallback' => 'lav', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'mk' => ['language' => 'FYRO Macedonian', 'locale' => 'mkd', 'localeFallback' => 'mkd', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'mk-mk' => ['language' => 'Macedonian', 'locale' => 'mk_mk', 'localeFallback' => 'mkd', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'ms' => ['language' => 'Malaysian', 'locale' => 'msa', 'localeFallback' => 'msa', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'mt' => ['language' => 'Maltese', 'locale' => 'mlt', 'localeFallback' => 'mlt', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'nb' => ['language' => 'Norwegian Bokmal', 'locale' => 'nob', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'nl' => ['language' => 'Dutch (Standard)', 'locale' => 'nld', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'nl-be' => ['language' => 'Dutch (Belgium)', 'locale' => 'nl_be', 'localeFallback' => 'nld', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'nn' => ['language' => 'Norwegian Nynorsk', 'locale' => 'nno', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'no' => ['language' => 'Norwegian', 'locale' => 'nor', 'localeFallback' => 'nor', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'pl' => ['language' => 'Polish', 'locale' => 'pol', 'localeFallback' => 'pol', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'pt' => ['language' => 'Portuguese (Portugal)', 'locale' => 'por', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'pt-br' => ['language' => 'Portuguese (Brazil)', 'locale' => 'pt_br', 'localeFallback' => 'por', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'rm' => ['language' => 'Rhaeto-Romanic', 'locale' => 'roh', 'localeFallback' => 'roh', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'ro' => ['language' => 'Romanian', 'locale' => 'ron', 'localeFallback' => 'ron', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'ro-mo' => ['language' => 'Romanian (Moldavia)', 'locale' => 'ro_mo', 'localeFallback' => 'ron', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'ru' => ['language' => 'Russian', 'locale' => 'rus', 'localeFallback' => 'rus', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'ru-mo' => ['language' => 'Russian (Moldavia)', 'locale' => 'ru_mo', 'localeFallback' => 'rus', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'sb' => ['language' => 'Sorbian', 'locale' => 'wen', 'localeFallback' => 'wen', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'sk' => ['language' => 'Slovak', 'locale' => 'slk', 'localeFallback' => 'slk', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'sl' => ['language' => 'Slovenian', 'locale' => 'slv', 'localeFallback' => 'slv', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'sq' => ['language' => 'Albanian', 'locale' => 'sqi', 'localeFallback' => 'sqi', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'sr' => ['language' => 'Serbian', 'locale' => 'srp', 'localeFallback' => 'srp', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'sv' => ['language' => 'Swedish', 'locale' => 'swe', 'localeFallback' => 'swe', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'sv-fi' => ['language' => 'Swedish (Finland)', 'locale' => 'sv_fi', 'localeFallback' => 'swe', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'se' => ['language' => 'Sami', 'locale' => 'sme', 'localeFallback' => 'sme', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'th' => ['language' => 'Thai', 'locale' => 'tha', 'localeFallback' => 'tha', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'tn' => ['language' => 'Tswana', 'locale' => 'tsn', 'localeFallback' => 'tsn', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'tr' => ['language' => 'Turkish', 'locale' => 'tur', 'localeFallback' => 'tur', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'ts' => ['language' => 'Tsonga', 'locale' => 'tso', 'localeFallback' => 'tso', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'uk' => ['language' => 'Ukrainian', 'locale' => 'ukr', 'localeFallback' => 'ukr', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'ur' => ['language' => 'Urdu', 'locale' => 'urd', 'localeFallback' => 'urd', 'charset' => 'utf-8', 'direction' => 'rtl'],
		've' => ['language' => 'Venda', 'locale' => 'ven', 'localeFallback' => 'ven', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'vi' => ['language' => 'Vietnamese', 'locale' => 'vie', 'localeFallback' => 'vie', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'cy' => ['language' => 'Welsh', 'locale' => 'cym', 'localeFallback' => 'cym', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'xh' => ['language' => 'Xhosa', 'locale' => 'xho', 'localeFallback' => 'xho', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'yi' => ['language' => 'Yiddish', 'locale' => 'yid', 'localeFallback' => 'yid', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'zh' => ['language' => 'Chinese', 'locale' => 'zho', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'zh-cn' => ['language' => 'Chinese (PRC)', 'locale' => 'zh_cn', 'localeFallback' => 'zho', 'charset' => 'GB2312', 'direction' => 'ltr'],
		'zh-hk' => ['language' => 'Chinese (Hong Kong)', 'locale' => 'zh_hk', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'zh-sg' => ['language' => 'Chinese (Singapore)', 'locale' => 'zh_sg', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'zh-tw' => ['language' => 'Chinese (Taiwan)', 'locale' => 'zh_tw', 'localeFallback' => 'zho', 'charset' => 'utf-8', 'direction' => 'ltr'],
		'zu' => ['language' => 'Zulu', 'locale' => 'zul', 'localeFallback' => 'zul', 'charset' => 'utf-8', 'direction' => 'ltr'],
	];

/**
 * Class constructor
 *
 * @param \Cake\Network\Request $request Request object
 */
	public function __construct(Request $request) {
		$this->_request = $request;

		$default = Configure::read('Config.language');
		if ($default) {
			$this->default = $default;
		}
	}

/**
 * Gets the settings for $language.
 * If $language is null it attempt to get settings from L10n::_autoLanguage(); if this fails
 * the method will get the settings from L10n::_setLanguage();
 *
 * @param string $language Language (if null will use config value 'Config.language' if set)
 * @return mixed
 */
	public function get($language = null) {
		if ($language !== null) {
			return $this->_setLanguage($language);
		}

		if (!$this->_autoLanguage()) {
			$this->_setLanguage();
		}
		return $this->lang;
	}

/**
 * Sets the class vars to correct values for $language.
 * If $language is null it will use the L10n::$default if defined
 *
 * @param string $language Language (if null will use L10n::$default if defined)
 * @return mixed
 */
	protected function _setLanguage($language = null) {
		$catalog = false;
		if ($language !== null) {
			$catalog = $this->catalog($language);
		}

		if (!$catalog && $this->default) {
			$language = $this->default;
			$catalog = $this->catalog($language);
		}

		if ($catalog) {
			$this->language = $catalog['language'];
			$this->languagePath = array_unique([
				$catalog['locale'],
				$catalog['localeFallback']
			]);
			$this->lang = $language;
			$this->locale = $catalog['locale'];
			$this->charset = $catalog['charset'];
			$this->direction = $catalog['direction'];
		} elseif ($language) {
			$this->lang = $language;
			$this->languagePath = [$language];
		}

		if ($this->default && $language !== $this->default) {
			$catalog = $this->catalog($this->default);
			$fallback = $catalog['localeFallback'];
			if (!in_array($fallback, $this->languagePath)) {
				$this->languagePath[] = $fallback;
			}
		}

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
 * @return bool Success
 */
	protected function _autoLanguage() {
		$_detectableLanguages = $this->_request->acceptLanguage();
		foreach ($_detectableLanguages as $langKey) {
			if (isset($this->_l10nCatalog[$langKey])) {
				$this->_setLanguage($langKey);
				return true;
			}
			if (strpos($langKey, '-') !== false) {
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
 * @param string|array $mixed 2/3 char string (language/locale), array of those strings, or null
 * @return string|array|bool string language/locale, array of those values, whole map as an array,
 *    or false when language/locale doesn't exist
 */
	public function map($mixed = null) {
		if (is_array($mixed)) {
			$result = [];
			foreach ($mixed as $_mixed) {
				if ($_result = $this->map($_mixed)) {
					$result[$_mixed] = $_result;
				}
			}
			return $result;
		}
		if (is_string($mixed)) {
			if (strlen($mixed) === 2 && in_array($mixed, $this->_l10nMap)) {
				return array_search($mixed, $this->_l10nMap);
			}
			if (isset($this->_l10nMap[$mixed])) {
				return $this->_l10nMap[$mixed];
			}
			return false;
		}
		return $this->_l10nMap;
	}

/**
 * Attempts to find catalog record for requested language
 *
 * @param string|array $language string requested language, array of requested languages, or null for whole catalog
 * @return array|bool array catalog record for requested language, array of catalog records, whole catalog,
 *    or false when language doesn't exist
 */
	public function catalog($language = null) {
		if (is_array($language)) {
			$result = [];
			foreach ($language as $_language) {
				if ($_result = $this->catalog($_language)) {
					$result[$_language] = $_result;
				}
			}
			return $result;
		}
		if (is_string($language)) {
			if (isset($this->_l10nCatalog[$language])) {
				return $this->_l10nCatalog[$language];
			}
			if (isset($this->_l10nMap[$language]) && isset($this->_l10nCatalog[$this->_l10nMap[$language]])) {
				return $this->_l10nCatalog[$this->_l10nMap[$language]];
			}
			return false;
		}
		return $this->_l10nCatalog;
	}

}
