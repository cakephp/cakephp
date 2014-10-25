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
		$this->_process();
	}

/**
 * Process plugins
 *
 * @return void
 */
	protected function _process() {
		$plugins = Plugin::loaded();
		foreach ($plugins as $plugin) {
			$path = Plugin::path($plugin) . 'webroot';
			if (!is_dir($path)) {
				$this->out();
				$this->out(
					sprintf('Skipping plugin %s. It does not have webroot folder.', $plugin),
					2,
					Shell::VERBOSE
				);
				continue;
			}

			$this->out();
			$this->out('For plugin: ' . $plugin);
			$this->hr();

			$link = Inflector::underscore($plugin);
			$dir = WWW_ROOT;

			if (strpos('/', $link) !== false) {
				$parts = explode('/', $link);
				$link = array_pop($parts);
				$dir = WWW_ROOT . implode(DS, $parts) . DS;
				if (!is_dir($dir) && !$this->_createDirectory($dir)) {
					continue;
				}
			}

			if (file_exists($dir . $link)) {
				$this->out($link . ' already exists', 1, Shell::VERBOSE);
				continue;
			}

			if ($this->_createSymlink($path, $dir . $link)) {
				continue;
			}

			$this->_copyDirectory($path, $dir . $link);
		}

		$this->out();
		$this->out('Done');
	}

/**
 * Create direcotry
 *
 * @param string $dir Directory name
 * @return boolean
 */
	protected function _createDirectory($dir) {
		$old = umask(0);
		// @codingStandardsIgnoreStart
		$result = @mkdir($dir, 0755, true);
		// @codingStandardsIgnoreEnd
		umask($old);

		if ($result) {
			$this->out('Created directory ' . $dir);
			return true;
		}

		$this->err('Failed creating directory ' . $dir);
		return false;
	}

/**
 * Create symmlink
 *
 * @param string $target Target directory
 * @param string $link Link name
 * @return boolean
 */
	protected function _createSymlink($target, $link) {
		// @codingStandardsIgnoreStart
		$result = @symlink($target, $link);
		// @codingStandardsIgnoreEnd

		if ($result) {
			$this->out('Created symlink ' . $link);
			return true;
		}

		return false;
	}

/**
 * Copy directory
 *
 * @param string $source Source directory
 * @param string $destination Destination directory
 * @return boolean
 */
	protected function _copyDirectory($source, $destination) {
		$folder = new Folder($source);
		if ($folder->copy(['to' => $destination])) {
			$this->out('Copied assets to directory ' . $destination);
			return true;
		}

		$this->err('Error copying assets to directory ' . $destination);
		return false;
	}

}
