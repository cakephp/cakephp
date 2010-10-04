<?php
/**
 * CommandListTest file
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs
 * @since         CakePHP v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Shows a list of commands available from the console.
 *
 * @package cake.console.libs
 */
class CommandListShell extends Shell {
/**
 * Main function Prints out the list of shells.
 *
 * @return void
 */
	public function main() {
		$this->out("<info>Current Paths:</info>", 2);
		$this->out(" -app: ". $this->params['app']);
		$this->out(" -working: " . rtrim($this->params['working'], DS));
		$this->out(" -root: " . rtrim($this->params['root'], DS));
		$this->out(" -core: " . rtrim(CORE_PATH, DS));
		$this->out("");
		$this->out("<info>Changing Paths:</info>", 2);
		$this->out("Your working path should be the same as your application path");
		$this->out("to change your path use the '-app' param.");
		$this->out("Example: -app relative/path/to/myapp or -app /absolute/path/to/myapp", 2);

		$this->out("<info>Available Shells:</info>", 2);
		$shellList = array();
		foreach ($this->Dispatch->shellPaths as $path) {
			if (!is_dir($path)) {
				continue;
			}
 			$shells = App::objects('file', $path);
			if (empty($shells)) {
				continue;
			}
			if (preg_match('@plugins[\\\/]([^\\\/]*)@', $path, $matches)) {
				$type = Inflector::camelize($matches[1]);
			} elseif (preg_match('@([^\\\/]*)[\\\/]vendors[\\\/]@', $path, $matches)) {
				$type = $matches[1];
			} elseif (strpos($path, CAKE_CORE_INCLUDE_PATH . DS . 'cake') === 0) {
				$type = 'CORE';
			} else {
				$type = 'app';
			}
			foreach ($shells as $shell) {
				if ($shell !== 'shell.php') {
					$shell = str_replace('.php', '', $shell);
					$shellList[$shell][$type] = $type;
				}
			}
		}
		if ($shellList) {
			ksort($shellList);
			if (DS === '/') {
				$width = exec('tput cols') - 2;
			}
			if (empty($width)) {
				$width = 80;
			}
			$columns = max(1, floor($width / 30));
			$rows = ceil(count($shellList) / $columns);

			foreach ($shellList as $shell => $types) {
				sort($types);
				$shellList[$shell] = str_pad($shell . ' [' . implode ($types, ', ') . ']', $width / $columns);
			}
			$out = array_chunk($shellList, $rows);
			for ($i = 0; $i < $rows; $i++) {
				$row = '';
				for ($j = 0; $j < $columns; $j++) {
					if (!isset($out[$j][$i])) {
						continue;
 					}
					$row .= $out[$j][$i];
 				}
				$this->out(" " . $row);
			}
		}
		$this->out();
		$this->out("To run a command, type 'cake shell_name [args]'");
		$this->out("To get help on a specific command, type 'cake shell_name help'", 2);
	}
}
