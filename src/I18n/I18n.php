<?php
/**
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

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * I18n handles translation of Text and time format strings.
 *
 */
class I18n {

/**
 * Constructor, use I18n::getInstance() to get the i18n translation object.
 *
 * @param \Cake\Network\Request $request Request object
 */
	public function __construct(Request $request) {
		$this->_request = $request;

		$this->l10n = new L10n($this->_request);
	}

/**
 * Used by the translation functions in basics.php
 * Returns a translated string based on current language and translation files stored in locale folder
 *
 * @param string $singular String to translate
 * @param string $plural Plural string (if any)
 * @param string $domain Domain The domain of the translation. Domains are often used by plugin translations.
 *    If null, the default domain will be used.
 * @param int $category Category The integer value of the category to use.
 * @param int $count Count Count is used with $plural to choose the correct plural form.
 * @param string $language Language to translate string to.
 *    If null it checks for language in session followed by Config.language configuration variable.
 * @return string translated string.
 * @throws \Cake\Error\Exception When '' is provided as a domain.
 */
	public static function translate($singular, $plural = null, $domain = null, $category = self::LC_MESSAGES, $count = null, $language = null) {
		
	}

/**
 * Clears the domains internal data array. Useful for testing i18n.
 *
 * @return void
 */
	public static function clear() {
		$self = I18n::getInstance();
		$self->_domains = array();
	}

/**
 * Get the loaded domains cache.
 *
 * @return array
 */
	public static function domains() {
		$self = I18n::getInstance();
		return $self->_domains;
	}

}
