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

use Aura\Intl\FormatterLocator;
use Aura\Intl\TranslatorLocator;
use Aura\Intl\PackageLocatorInterface;
use Cake\Cache\Cache;
use Serializable;

/**
 * 
 */
class TranslatorRegistry extends TranslatorLocator implements Serializable {

	public function serialize() {
		return serialize($this->registry);
	}

	public function unserialize($data) {
		$this->registry = unserialize($data);
	}

/**
 * Appends every loaded translator from the passed $registry into this registry,
 * Any translator that has not yet been fetch from its internal packages will
 * not be put into this registry.
 *
 * @param \Aura\Int\TranslatorLocator $registry The locator from wich to merge
 * the loaded translators.
 * @return void
 */
	public function merge(TranslatorLocator $registry) {
		$registry = $this->registry ?: [];
		$this->registry = array_merge_recursive($registry->registry, $registry);
	}

/**
 * Appends every loaded translator from the passed $registry into this registry,
 * Any translator that has not yet been fetch from its internal packages will
 * not be put into this registry.
 *
 * @param \Aura\Int\TranslatorLocator $registry The locator from wich to merge
 * the loaded translators.
 * @return void
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
