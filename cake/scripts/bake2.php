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
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2005, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.scripts.bake
 * @since			CakePHP v 1.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
	defineConstants();
	$cakeDir = CORE_PATH.DS.'cake'.DS;
	
	if (count($argv) >= 2) {
		$appPath = getAppPath($cakeDir, $argv[1]);
		$taskName = '';
		$params = array();
		
		if ($appPath != false) {
			defineAppConstants($appPath);
			$taskName = $argv[2];
			$params = prepareParams($argv, 3);
		} else {
			defineAppConstantsWithDefaultValues();
			$taskName = $argv[1];
			$params = prepareParams($argv, 2);
		}
		
		includeCoreFiles($cakeDir);
		executeTask($taskName, $params);
	} else {
		showHelp();
	}
		
	function defineAppConstants($appPath) {
		$delimiter = strrpos($appPath, DS);
		$root = substr($appPath, 0, $delimiter);
		$appdir = substr($appPath, $delimiter + 1);

		define('ROOT', $root);
		define('APP_DIR', $appdir);
	}
	
	function defineAppConstantsWithDefaultValues() {
		define('ROOT', CORE_PATH);
		define('APP_DIR', 'app');
	}
	
	function defineConstants() {
		define('DS', DIRECTORY_SEPARATOR);
		define('CORE_PATH', dirname(dirname(dirname(__FILE__))));
	}
	
	function executeTask($taskName, $params) {
		$scriptDir = dirname(__FILE__);
		require($scriptDir.DS.'tasks'.DS.'task.php');
		require($scriptDir.DS.'tasks'.DS.$taskName.'_task.php');
		
		$className = $taskName.'Task';
		$class = new $className;
		$class->execute($params);
	}
	
	function getAppPath($cakeDir, $appPathShortcut) {
		$iniFile = $cakeDir.'config'.DS.'apps.ini';
		
		if (file_exists($iniFile)) {
			$appArray = readConfigFile($iniFile);
		
			if (array_key_exists($appPathShortcut, $appArray)) {
				return $appArray[$appPathShortcut];
			}
		}
		
		return false;
	}
	
	function includeCoreFiles($cakePath) {
		require($cakePath.'basics.php');
		require($cakePath.'config'.DS.'paths.php');
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
?>