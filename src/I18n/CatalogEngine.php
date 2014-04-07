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
 * [read description]
 * @param string $domain [description]
 * @param array $locales [description]
 * @param string $category [description]
 * @return array|boolean [description]
 */
	public function read($domain, array $locales, $category) {
		return false;
	}

/**
 * [write description]
 * @param string $domain [description]
 * @param string $locale [description]
 * @param string $category [description]
 * @param array $data [description]
 * @return boolean [description]
 */
	public function write($domain, $locale, $category, array $data) {
		return false;
	}

}
