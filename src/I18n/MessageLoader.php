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

use Aura\Intl\Package;
use Cake\I18n\Loader\PoFileLoader;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;

/**
 * 
 *
 */
class MessageLoader {

	protected $_name;

	protected $_locale;

	protected $basePath;

	public function __construct($name, $locale) {
		$this->_name = $name;
		$this->_locale = $locale;

		$pluginName = Inflector::camelize($name);
		$this->_basePath = APP . 'Locale' . DS;

		if (Plugin::loaded($pluginName)) {
			$this->_basePath = Plugin::path($pluginName) . 'Locale' . DS;
		}
	}

	public function __invoke() {
		$package = new Package;
		$folder = $this->translationsFolder();

		if (!$folder || !is_file($folder . $this->_name . '.po')) {
			return $package;
		}

		$messages = (new PoFileLoader)->parse($folder . $this->_name . '.po');
		$package->setMessages($messages);
		return $package;
	}

	public function translationsFolder() {
		$locale = locale_parse($this->_locale) + ['region' => null];

		$folders = [
			implode('_', [$locale['language'], $locale['region']]),
			$locale['language']
		];

		foreach ($folders as $folder) {
			$path = $this->_basePath  . $folder . DS . 'LC_MESSAGES' . DS;
			if (is_dir($path)) {
				return $path;
			}
		}

		return false;
	}

}
