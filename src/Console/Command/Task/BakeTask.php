<?php
/**
 * Base class for Bake Tasks.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\Core\Configure;

/**
 * Base class for Bake Tasks.
 *
 */
class BakeTask extends Shell {

/**
 * Name of plugin
 *
 * @var string
 */
	public $plugin = null;

/**
 * The db connection being used for baking
 *
 * @var string
 */
	public $connection = null;

/**
 * Disable caching and enable debug for baking.
 * This forces the most current database schema to be used.
 *
 * @return void
 */
	public function startup() {
		Configure::write('debug', 2);
		Cache::disable();
		parent::startup();
	}

/**
 * Gets the path for output. Checks the plugin property
 * and returns the correct path.
 *
 * @return string Path to output.
 */
	public function getPath() {
		$path = $this->path;
		if (isset($this->plugin)) {
			$path = $this->_pluginPath($this->plugin) . $this->name . DS;
		}
		return $path;
	}

/**
 * Base execute method parses some parameters and sets some properties on the bake tasks.
 * call when overriding execute()
 *
 * @return void
 */
	public function execute() {
		foreach ($this->args as $i => $arg) {
			if (strpos($arg, '.')) {
				list($this->params['plugin'], $this->args[$i]) = pluginSplit($arg);
				break;
			}
		}
		if (isset($this->params['plugin'])) {
			$this->plugin = $this->params['plugin'];
		}
		if (isset($this->params['connection'])) {
			$this->connection = $this->params['connection'];
		}
	}

}
