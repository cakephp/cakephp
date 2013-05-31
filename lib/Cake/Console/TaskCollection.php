<?php
/**
 * Task collection is used as a registry for loaded tasks and handles loading
 * and constructing task class objects.
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
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ObjectCollection', 'Utility');

/**
 * Collection object for Tasks. Provides features
 * for lazily loading tasks, and firing callbacks on loaded tasks.
 *
 * @package       Cake.Console
 */
class TaskCollection extends ObjectCollection {

/**
 * Shell to use to set params to tasks.
 *
 * @var Shell
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
 * @param Shell $Shell
 */
	public function __construct(Shell $Shell) {
		$this->_Shell = $Shell;
	}

/**
 * Loads/constructs a task. Will return the instance in the collection
 * if it already exists.
 *
 * @param string $task Task name to load
 * @param array $settings Settings for the task.
 * @return Task A task object, Either the existing loaded task or a new one.
 * @throws MissingTaskException when the task could not be found
 */
	public function load($task, $settings = array()) {
		list($plugin, $name) = pluginSplit($task, true);

		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}

		$taskClass = $name . 'Task';
		App::uses($taskClass, $plugin . 'Console/Command/Task');

		$exists = class_exists($taskClass);
		if (!$exists) {
			throw new MissingTaskException(array(
				'class' => $taskClass
			));
		}

		$this->_loaded[$name] = new $taskClass(
			$this->_Shell->stdout, $this->_Shell->stderr, $this->_Shell->stdin
		);
		return $this->_loaded[$name];
	}

}
