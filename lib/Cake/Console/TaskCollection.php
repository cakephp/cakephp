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
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Core\App;
use Cake\Error;

/**
 * Collection object for Tasks. Provides features
 * for lazily loading tasks.
 */
class TaskCollection {

/**
 * Map of loaded tasks.
 *
 * @var array
 */
	protected $_loaded = [];

/**
 * Shell to use to set params to tasks.
 *
 * @var Shell
 */
	protected $_Shell;

/**
 * Constructor
 *
 * @param Shell $Shell
 */
	public function __construct(Shell $Shell) {
		$this->_Shell = $Shell;
	}

/**
 * Loads/constructs a task. Will return the instance in the registry if it already exists.
 *
 * You can alias your task as an existing task by setting the 'className' key, i.e.,
 * {{{
 * public $tasks = array(
 * 'DbConfig' => array(
 * 'className' => 'Bakeplus.DbConfigure'
 * );
 * );
 * }}}
 * All calls to the `DbConfig` task would use `DbConfigure` found in the `Bakeplus` plugin instead.
 *
 * @param string $task Task name to load
 * @param array $settings Settings for the task.
 * @return Task A task object, Either the existing loaded task or a new one.
 * @throws Cake\Error\MissingTaskException when the task could not be found
 */
	public function load($task, $settings = array()) {
		if (is_array($settings) && isset($settings['className'])) {
			$alias = $task;
			$task = $settings['className'];
		}
		list($plugin, $name) = pluginSplit($task, true);
		if (!isset($alias)) {
			$alias = $name;
		}

		if (isset($this->_loaded[$alias])) {
			return $this->_loaded[$alias];
		}

		$taskClass = App::classname($task, 'Console/Command/Task', 'Task');
		if (!$taskClass) {
			throw new Error\MissingTaskException(array(
				'class' => $name
			));
		}

		$this->_loaded[$alias] = new $taskClass(
			$this->_Shell->stdout, $this->_Shell->stderr, $this->_Shell->stdin
		);
		return $this->_loaded[$alias];
	}

/**
 * Get the loaded helpers list, or get the helper instance at a given name.
 *
 * @param null|string $name The helper name to get or null.
 * @return array|Helper Either a list of helper names, or a loaded helper.
 */
	public function loaded($name = null) {
		if (!empty($name)) {
			return isset($this->_loaded[$name]);
		}
		return array_keys($this->_loaded);
	}

/**
 * Provide public read access to the loaded objects
 *
 * @param string $name Name of property to read
 * @return mixed
 */
	public function __get($name) {
		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}
		return null;
	}

/**
 * Provide isset access to _loaded
 *
 * @param string $name Name of object being checked.
 * @return boolean
 */
	public function __isset($name) {
		return isset($this->_loaded[$name]);
	}

}
