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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Aura\Intl\TranslatorLocator;
use Cake\Cache\Cache;

/**
 * Constructs and stores instances of translators that can be
 * retrieved by name and locale.
 */
class TranslatorRegistry extends TranslatorLocator {

/**
 * {@inheritDoc}
 */
	public function get($name, $locale = null) {
		if ($locale === null) {
			$locale = $this->getLocale();
		}

		if (!isset($this->registry[$name][$locale])) {
			$key = "translations.$name.$locale";
			return Cache::remember($key, function() use ($name, $locale) {
				return parent::get($name, $locale);
			}, '_cake_core_');
		}

		return $this->registry[$name][$locale];
	}

}
