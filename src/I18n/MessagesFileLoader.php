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
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;

/**
 *
 *
 */
class MessagesFileLoader {

	protected $_name;

	protected $_locale;

	protected $_extension;

	public function __construct($name, $locale, $extension = 'po') {
		$this->_name = $name;
		$this->_locale = $locale;
		$this->_extension = $extension;
	}

	public function __invoke() {
		$package = new Package;
		$folder = $this->translationsFolder();
		$ext = $this->_extension;

		if (!$folder || !is_file($folder . $this->_name . ".$ext")) {
			return $package;
		}

		$name = ucfirst($ext);
		$class = App::classname($name, 'I18n\Parser', 'FileParser');

		if (!$class) {
			throw new \RuntimeException(sprintf('Could not find class %s'), "{$name}FileParser");
		}

		$messages = (new $class)->parse($folder . $this->_name . ".$ext");
		$package->setMessages($messages);
		return $package;
	}

	public function translationsFolder() {
		$locale = locale_parse($this->_locale) + ['region' => null];

		$folders = [
			implode('_', [$locale['language'], $locale['region']]),
			$locale['language']
		];

		$pluginName = Inflector::camelize($this->_name);
		$basePath = APP . 'Locale' . DS;

		if (Plugin::loaded($pluginName)) {
			$basePath = Plugin::path($pluginName) . 'src' . DS . 'Locale' . DS;
		}

		foreach ($folders as $folder) {
			$path = $basePath . $folder . DS . 'LC_MESSAGES' . DS;
			if (is_dir($path)) {
				return $path;
			}
		}

		return false;
	}

}
