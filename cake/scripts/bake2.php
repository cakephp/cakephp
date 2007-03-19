#!/usr/bin/php -q
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
	defineConstants();
	$cakeDir = CORE_PATH.DS.'cake'.DS;
	$argCount = count($argv);

	if ($argCount == 1 || isHelpParam($argv[1])) {
		showHelp();
	} else {
		$taskName = $argv[1];
		$appPath = false;
		$params = null;
		$showHelp = false;

		if ($argCount > 2) {
			if (isHelpParam($argv[2])) {
				$showHelp = true;
			} else {
				$appPath = getAppPath($argv[2]);
			}
		}

		if ($appPath != false) {
			defineAppConstants($appPath);
			$params = prepareParams($argv, 3);
		} else {
			defineAppConstantsWithDefaultValues();
			$params = prepareParams($argv, 2);
		}

		includeCoreFiles($cakeDir);

		if ($showHelp) {
			showHelpForTask($taskName);
		} else {
			executeTask($taskName, $params);
		}
	}

	function defineAppConstants($appPath) {
		$delimiter = strrpos($appPath, DS);
		$root = substr($appPath, 0, $delimiter);
		$appdir = substr($appPath, $delimiter + 1);

		define('ROOT', $root);
		define('APP_DIR', $appdir);
		define('APP_PATH', ROOT.DS.APP_DIR.DS);
		// TODO: how to handle situation with a non-standard webroot setup?
		define('WWW_ROOT', APP_PATH.'webroot'.DS);
	}

	function defineAppConstantsWithDefaultValues() {
		define('ROOT', CORE_PATH);
		define('APP_DIR', 'app');
		define('APP_PATH', ROOT.DS.APP_DIR.DS);
		define('WWW_ROOT', APP_PATH.'webroot'.DS);
	}

	function defineConstants() {
		define('PHP5', (phpversion() >= 5));
		define('DS', DIRECTORY_SEPARATOR);
		define('CAKE_CORE_INCLUDE_PATH', dirname(dirname(dirname(__FILE__))));
		define('CORE_PATH', CAKE_CORE_INCLUDE_PATH.DS);
	}

	function executeTask($taskName, $params) {
		$class = getTaskClass($taskName);

		if ($class !== null) {
			$class->execute($params);
		} else {
			echo "Task not found: " . $taskName . "\n";
		}
	}

	function getAppPath($appPathShortcut) {
		$iniFile = CORE_PATH.DS.'apps.ini';

		if (file_exists($iniFile)) {
			$appArray = readConfigFile($iniFile);

			if (array_key_exists($appPathShortcut, $appArray)) {
				return $appArray[$appPathShortcut];
			}
		}

		return false;
	}

	function getTaskClass($taskName) {
		$scriptDir = dirname(__FILE__);
		$taskPath = 'tasks'.DS.$taskName.'_task.php';
		$fileExists = true;
		require($scriptDir.DS.'tasks'.DS.'bake_task.php');

		if (file_exists(VENDORS.$taskPath)) {
			require(VENDORS.$taskPath);
		} elseif (file_exists($scriptDir.DS.$taskPath)) {
			require($scriptDir.DS.$taskPath);
		} else {
			$fileExists = false;
		}

		if ($fileExists) {
			$className = $taskName.'Task';
			return new $className;
		}

		return null;
	}

	function includeCoreFiles($cakePath) {
		require($cakePath.'basics.php');
		require($cakePath.'config'.DS.'paths.php');
	}

	function isHelpParam($param) {
		return ($param == 'help' || $param == '--help');
	}

	function prepareParams($originalParams, $elementsToRemove) {
		$params = $originalParams;

		for ($i = 0; $i < $elementsToRemove; $i++) {
			array_shift($params);
		}

		return $params;
	}

	function readConfigFile($fileName) {
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

	function showHelp() {
		echo "Usage: php bake2.php task [param1, ...]\n";
	}

	function showHelpForTask($taskName) {
		$class = getTaskClass($taskName);
		$class->help();
	}
?>