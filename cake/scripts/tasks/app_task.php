<?php
/* SVN FILE: $Id$ */
/**
 * The AppTask creates the application skeleton.
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
class AppTask extends BakeTask {
	
	function execute($params) {
		if (count($params) == 2) {
			$skel = SCRIPTS.'templates'.DS.'skel';
			$this->copydirr($skel, $params[1], 0755);
			$this->makeTmpWritable($params[1].DS.'tmp');
			$this->addAppAlias($params[0], $params[1]);
		} else {
			$this->help();
		}
	}
	
	function help() {
		echo "The app task creates an application skeleton for you.\n";
		echo "Usage: bake2 app alias app-path \n";
	}
	
	function addAppAlias($alias, $path) {
		$filename = CORE_PATH . 'apps.ini';
		
		if (!$handle = fopen($filename, 'a')) {
      		echo "Cannot open file ($filename) \n";
      		exit;
		}

		if (fwrite($handle, $alias . ' = ' . $path."\n") === FALSE) {
    		echo "Cannot write to file ($filename) \n";
    		exit;
		}
		fclose($handle);
	}
	
	function copydirr($fromDir, $toDir, $chmod = 0755) {
		$errors = array();

		if (!is_dir($toDir)) {
			uses('folder');
			$folder = new Folder();
			$folder->mkdirr($toDir, 0755);
		}

		if (!is_writable($toDir)) {
			$errors[] = 'target '.$toDir.' is not writable';
		}

		if (!is_dir($fromDir)) {
			$errors[] = 'source '.$fromDir.' is not a directory';
		}

		if (!empty($errors)) {
			foreach($errors as $err) {
				echo 'Error: '.$err."\n";
			}
			
			return false;
		}
		
		$exceptions = array('.', '..', '.svn');
		$handle = opendir($fromDir);

		while (false!==($item = readdir($handle))) {
			if (!in_array($item,$exceptions)) {
				$from = str_replace('//','/',$fromDir.'/'.$item);
				$to = str_replace('//','/',$toDir.'/'.$item);
				if (is_file($from)) {
					if (@copy($from, $to)) {
						chmod($to, $chmod);
						touch($to, filemtime($from));
					} else {
						$errors[] = 'cannot copy file from '.$from.' to '.$to;
					}
				}

				if (is_dir($from)) {
					if (@mkdir($to)) {
						chmod($to,$chmod);
					} else {
						$errors[] = 'cannot create directory '.$to;
					}
					$this->copydirr($from,$to,$chmod);
				}
			}
		}
		closedir($handle);

		if (!empty($errors)) {
			foreach($errors as $err) {
				echo 'Error: '.$err . "\n";
			}
			return false;
		}
		return true;
	}
	
	function makeTmpWritable($path) {
		if(chmodr($path, 0777) === false) {
			echo 'Could not set permissions on '. $path.DS."*\n";
			echo "You must manually check that these directories can be wrote to by the server \n";
		}
	}
}
?>