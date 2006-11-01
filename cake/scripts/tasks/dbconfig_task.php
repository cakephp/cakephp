<?php
/* SVN FILE: $Id$ */
/**
 * The DbconfigTask creates the database configuration file.
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
define('DB_CONFIG_FILE', CONFIGS.'database.php');
define('DB_CONFIG_FILE_DEFAULT', CONFIGS.'database.php.default'); 
 
class DbconfigTask extends BakeTask {
	
	var $database;
	var $user;
	var $password = '';
	var $configName = 'default';
	var $databaseDriver = 'mysql';
	var $persistent = 'false';
	var $host = 'localhost';
	var $port = '';
	var $prefix = '';
	
	function execute($params) {
		
		$paramCount = count($params);
		
		if ($paramCount >= 2) {
			$this->database = array_shift($params);
			$this->user = array_shift($params);
			
			if ($paramCount > 2) {
				$this->handleParams($params);
			}
			
			if (file_exists(DB_CONFIG_FILE)) {
				$this->insertOrUpdateConfiguration();
			} elseif (file_exists(DB_CONFIG_FILE_DEFAULT)) {
				rename(DB_CONFIG_FILE_DEFAULT, DB_CONFIG_FILE);
				$this->insertOrUpdateConfiguration();
			} else {
				$this->createFile(DB_CONFIG_FILE, $this->getFileContent());
			}
		} else {
			$this->help();
		}
	}

	function help() {
		echo "The dbconfig task creates the database configuration file for you.\n";
		echo "Usage: bake2 dbconfig [app-alias] database user [password] [-c=configName] [-d=databaseDriver] \n";
		echo "       [-persistent] [-h=hostname[:port]] [-p=prefix]\n";
	}
	
	function beginsWith($str, $sub) {
    	return (substr($str, 0, strlen($sub)) === $sub);
 	}
 	
 	function createFile($path, $content) {
		
		if ($f = fopen($path, 'w')) {
			fwrite($f, $content);
			fclose($f);
			
			return true;
		}
		
		return false;
	}
	
	function getConfiguration() {
		$out = "\tvar \${$this->configName} = array(\n";
		$out .= "\t\t'driver' => '{$this->databaseDriver}',\n";
		$out .= "\t\t'host' => '{$this->host}',\n";
		
		if ($this->port != '') {
			$out .= "\t\t'port' => {$this->port}, \n";
		}
		
		$out .= "\t\t'login' => '{$this->user}',\n";
		$out .= "\t\t'password' => '{$this->password}',\n";
		$out .= "\t\t'database' => '{$this->database}', \n";
		$out .= "\t\t'persistent' => {$this->persistent}, \n";
		$out .= "\t\t'prefix' => '{$this->prefix}' \n";
		$out .= "\t);\n";
		
		return $out;
	}
	
	function getFileContent() {
		$out = "<?php\n";
		$out .= "class DATABASE_CONFIG {\n\n";
		$out .= $this->getConfiguration();
		$out .= "}\n";
		$out .= "?>";
		
		return $out;
	}
	
	function insertOrUpdateConfiguration() {
		$data = file_get_contents(DB_CONFIG_FILE);
				
		if (strpos($data, 'var $'.$this->configName) === false) {
			$data = str_replace('}', "\n".$this->getConfiguration()."}", $data);
		} else {
			$data = preg_replace('/\tvar \$'.$this->configName.' (\S|\s)*\);/s', $this->getConfiguration(), $data);
		}
		
		$this->createFile(DB_CONFIG_FILE, $data);
	}
	
	function handleParams($params) {
		if (!$this->beginsWith($params[0], '-')) {
			$this->password = array_shift($params);
		}
		
		while (count($params) > 0) {
			$param = array_shift($params);
			$firstThreeChars = substr($param, 0, 3);
			$paramValue = substr($param, 3);
			
			switch ($firstThreeChars) {
				case '-c=':
					$this->configName = $paramValue;
					break;
				case '-d=':
					$this->databaseDriver = $paramValue;
					break;
				case '-pe':
					$this->persistent = 'true';
					break;
				case '-h=':
					if (strpos($paramValue, ':') === false) {
						$this->host = $paramValue;
					} else {
						$hostData = explode(':', $paramValue);
						$this->host = $hostData[0];
						$this->port = $hostData[1];
					}
					
					break;
				case '-p=':
					$this->prefix = $paramValue;
					break;
				default:
					break;
			}
		}
	}
}
?>