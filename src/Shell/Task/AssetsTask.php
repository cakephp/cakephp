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
namespace Cake\Shell\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Utility\Inflector;

/**
 * Task for symlinking / copying plugin assets to app's webroot.
 *
 */
class AssetsTask extends Shell {

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function main() {
		$this->_symlink();
	}

/**
 * Symlink folder
 *
 * @return void
 */
	protected function _symlink() {
		$this->out();
		$this->out();
		$this->out('Symlinking...');
		$this->hr();
		$plugins = Plugin::loaded();
		foreach ($plugins as $plugin) {
			$this->out('For plugin: ' . $plugin);
			$this->out();

			$link = Inflector::underscore($plugin);
			$dir = WWW_ROOT;
			if (file_exists($dir . $link)) {
				$this->out($link . ' already exists');
				$this->out();
				continue;
			}

			if (strpos('/', $link) !== false) {
				$parts = explode('/', $link);
				$link = array_pop($parts);
				$dir = WWW_ROOT . implode(DS, $parts) . DS;
				if (!is_dir($dir)) {
					$this->out('Creating directory: ' . $dir);
					$this->out();
					$old = umask(0);
					mkdir($dir, 0755, true);
					umask($old);
				}
			}

			$path = Plugin::path($plugin) . 'webroot';
			$this->out('Creating symlink: ' . $dir);
			$this->out();
			// @codingStandardsIgnoreStart
			$result = @symlink($path, $dir . $link);
			// @codingStandardsIgnoreEnd

			if (!$result) {
				$this->err('Symlink creation failed');
				$this->out('Copying to directory:' . $dir);
				$this->out();
				$folder = new Folder($path);
				$folder->copy(['to' => $dir . $link]);
			}
		}

		$this->out();
		$this->out('Done.');
	}

}
