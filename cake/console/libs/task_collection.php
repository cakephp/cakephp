<?php
/**
 * Task collection is used as a registry for loaded tasks and handles loading
 * and constructing task class objects.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.console.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'ObjectCollection');

class TaskCollection extends ObjectCollection {
/**
 * Shell to use to set params to tasks.
 *
 * @var array
 */
	protected $_Shell;

/**
 * The directory inside each shell path that contains tasks.
 *
 * @var string
 */
	public $taskPathPrefix = 'tasks/';

/**
 * Constructor
 *
 * @param array $paths Array of paths to search for tasks on .
 * @return void
 */
	public function __construct(Shell $Shell) {
		$this->_Shell = $Shell;
	}

/**
 * Loads/constructs a task.  Will return the instance in the collection
 * if it already exists.
 * 
 * @param string $task Task name to load
 * @param array $settings Settings for the task.
 * @param boolean $enable Whether or not this task should be enabled by default
 * @return Task A task object, Either the existing loaded task or a new one.
 * @throws MissingTaskFileException, MissingTaskClassException when the task could not be found
 */
	public function load($task, $settings = array(), $enable = true) {
		list($plugin, $name) = pluginSplit($task, true);

		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}
		$taskFile = Inflector::underscore($name);
		$taskClass = $name . 'Task';
		if (!class_exists($taskClass)) {
			if (!App::import('Shell', $plugin . $this->taskPathPrefix . $name)) {
				throw new MissingTaskFileException($taskFile . '.php');
			}
			if (!class_exists($taskClass)) {
				throw new MissingTaskClassException($taskClass);
			}
		}

		$this->_loaded[$name] = new $taskClass(
			$this->_Shell->stdout, $this->_Shell->stderr, $this->_Shell->stdin
		);
		if ($enable === true) {
			$this->_enabled[] = $name;
		}
		return $this->_loaded[$name];
	}

}
