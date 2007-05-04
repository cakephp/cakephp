<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007,	Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.scripts.bake
 * @since			CakePHP(tm) v 1.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class Bake2Script extends CakeScript {
	
	var $task = null;
	
	function initialize() {
		pr($this->args);
		if(isset($this->args[0])) {
			$this->task = $this->args[0];
		}
	}

	function main() {
		if($this->task == null) {
			$this->err('No Task specified');
			exit();
		}
		
		$task = $this->_loadTask($this->task);
		if ($task !== null) {
			$task->execute($this->args);
		} else {
			$this->err("Task not found: " . $this->task . "\n");
		}
	}

	function _loadTask($taskName = null) {
		$loaded = false;
		foreach($this->Dispatch->scriptPaths as $path) {
			$this->taskPath = $path . 'tasks' . DS . $taskName.'_task.php';
			if (file_exists($this->taskPath)) {
				$loaded = true;
				break;
			}
		}
		
		if ($loaded) {
			require SCRIPTS . 'tasks' . DS . 'bake_task.php';
			require $this->taskPath;
		
			$this->taskClass = $taskName.'Task';
			if(class_exists($this->taskClass)) {
				return new $this->taskClass($this);
			}
		}
		return null;
	}

	function _readConfigFile($fileName) {
		$fileLineArray = file($fileName);

		foreach($fileLineArray as $fileLine) {
			$dataLine = trim($fileLine);

			$delimiter = strpos($dataLine, '=');

			if ($delimiter > 0) {
				$key = strtolower(trim(substr($dataLine, 0, $delimiter)));
				$value = trim(substr($dataLine, $delimiter + 1));
				$iniSetting[$key] = $value;
			}
		}

		return $iniSetting;
	}

	function help() {
		echo "Usage: php bake2.php task [param1, ...]\n";
	}
}
?>