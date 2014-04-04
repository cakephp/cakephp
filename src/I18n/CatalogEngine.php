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

/**
 * Abstract class for all catalog engine classes.
 */
abstract class CatalogEngine {

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
