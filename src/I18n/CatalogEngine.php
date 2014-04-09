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

use Cake\Core\App;
use Cake\Core\InstanceConfigTrait;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;

/**
 * Abstract class for all catalog engine classes.
 */
abstract class CatalogEngine {

	use InstanceConfigTrait;

/**
 * Default config for this class
 *
 * @var array
 */
	protected $_defaultConfig = [];

/**
 * Paths to search for catalog files.
 *
 * @param string $domain Domain name
 * @return array List of paths
 */
	protected function _searchPaths($domain) {
		$searchPaths = App::path('Locale');
		$plugins = Plugin::loaded();

		if (!empty($plugins)) {
			foreach ($plugins as $plugin) {
				$pluginDomain = Inflector::underscore($plugin);
				if ($pluginDomain === $domain) {
					$searchPaths[] = Plugin::path($plugin) . 'Locale/';
					$searchPaths = array_reverse($searchPaths);
					break;
				}
			}
		}

		return $searchPaths;
	}

/**
 * Read translations from catalog
 *
 * @param string $domain Domain name
 * @param array $locales Locales to get translations for
 * @param string $category Category name
 * @return array|boolean List of translation on success or false on failure
 */
	public function read($domain, array $locales, $category) {
		return false;
	}

/**
 * Write translations to catalog
 *
 * @param string $domain Domain name
 * @param array $locales Locale to write translations for
 * @param string $category Category name
 * @param array $data Translations to write
 * @return boolean True on success else false
 */
	public function write($domain, $locale, $category, array $data) {
		return false;
	}

}
