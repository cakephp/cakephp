<?PHP 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Folder
  * Folder structure browser, lists folders and files.
  *
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
  */

/**
  * Enter description here...
  *
  */
uses('object');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Folder extends Object {
    
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $path = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $sort = false;

/**
  * Enter description here...
  *
  * @param unknown_type $path
  */
	function __construct ($path=false) {
		if (empty($path)) $path = getcwd();
		$this->cd($path);
   }	

/**
  * Enter description here...
  *
  * @return unknown
  */
	function pwd () {
		return $this->path;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $desired_path
  * @return unknown
  */
	function cd ($desired_path) {
		$desired_path = realpath($desired_path);
		$new_path = Folder::isAbsolute($desired_path)? 
			$desired_path: 
			Folder::addPathElement($this->path, $desired_path);

		return is_dir($new_path)? $this->path = $new_path: false;
	}


/**
  * Enter description here...
  *
  * @param unknown_type $sort
  * @return unknown
  */
	function ls($sort=true) {
		if ($dir = opendir($this->path)) {
			$dirs = $files = array();
			while (false !== ($n = readdir($dir))) {
				if (!preg_match('#^\.+$#', $n)) {
					if (is_dir($this->addPathElement($this->path, $n)))
						$dirs[] = $n;
					else 
						$files[] = $n;
				}
			}

			if ($sort || $this->sort) {
				sort($dirs);
				sort($files);
			}

			return array($dirs,$files);
		}
		else {
			return false;
		}
	}

/**
  * Enter description here...
  *
  * @param unknown_type $pattern
  * @return unknown
  */
	function findRecursive ($pattern='.*') {
		$starts_on = $this->path;
		$out = $this->_findRecursive($pattern);
		$this->cd($starts_on);
		return $out;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $pattern
  * @return unknown
  */
	function _findRecursive ($pattern) {
		list($dirs, $files) = $this->ls();

		$found = array();
		foreach ($files as $file) {
			if (preg_match("/^{$pattern}$/i", $file)) {
				$found[] = $this->addPathElement($this->path, $file);
			}
		}

		$start = $this->path;
		foreach ($dirs as $dir) {
			$this->cd($this->addPathElement($start, $dir));
			$found = array_merge($found, $this->findRecursive($pattern));
		}

		return $found;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @return unknown
  */
	function isWindowsPath ($path) {
		return preg_match('#^[A-Z]:\\\#i', $path)? true: false;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @return unknown
  */
	function isAbsolute ($path) {
		return preg_match('#^\/#', $path) || preg_match('#^[A-Z]:\\\#i', $path);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @return unknown
  */
	function isSlashTerm ($path) {
		return preg_match('#[\\\/]$#', $path)? true: false;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @return unknown
  */
	function correctSlashFor ($path) {
		return Folder::isWindowsPath($path)? '\\': '/';
	}

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @return unknown
  */
	function slashTerm ($path) {
		return $path . (Folder::isSlashTerm($path)? null: Folder::correctSlashFor($path));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @param unknown_type $element
  * @return unknown
  */
	function addPathElement ($path, $element) {
		return Folder::slashTerm($path).$element;
	}
}

?>