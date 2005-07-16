<?php 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Folder
  * Folder structure browser, lists folders and files.
  *
  * @filesource 
  * @author CakePHP Authors/Developers
  * @copyright Copyright (c) 2005, CakePHP Authors/Developers
  * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since CakePHP v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

uses('object');

/**
  * Folder structure browser, lists folders and files.
  *
  * @package cake
  * @subpackage cake.libs
  * @since CakePHP v 0.2.9
  */
class Folder extends Object {
    
/**
  * Enter description here...
  *
  * @var string
  */
   var $path = null;

/**
  * Enter description here...
  *
  * @var boolean
  */
   var $sort = false;

/**
  * Constructor.
  *
  * @param string $path
  */
   function __construct ($path=false) 
   {
		if (empty($path)) 
		{
			$path = getcwd();
		}
      	$this->cd($path);
   }   

/**
  * Return current path.
  *
  * @return string Current path
  */
   function pwd () 
   {
      return $this->path;
   }

/**
  * Change directory to $desired_path.
  *
  * @param string $desired_path Path to the directory to change to
  * @return string The new path. Returns false on failure
  */
   function cd ($desired_path) 
   {
      $desired_path = realpath($desired_path);
      $new_path = Folder::isAbsolute($desired_path)? 
         $desired_path: 
         Folder::addPathElement($this->path, $desired_path);

      return is_dir($new_path)? $this->path = $new_path: false;
   }


/**
  * Returns an array of the contents of the current directory, or false on failure. 
  * The returned array holds two arrays: one of dirs and one of files.
  *
  * @param boolean $sort
  * @return array
  */
   function ls($sort=true) 
   {
      $dir = opendir($this->path);

      if ($dir) 
      {
         $dirs = $files = array();
         while (false !== ($n = readdir($dir))) 
         {
            if (!preg_match('#^\.+$#', $n)) 
            {
               if (is_dir($this->addPathElement($this->path, $n)))
               {
                  $dirs[] = $n;
               }
               else
               { 
                  $files[] = $n;
               }
            }
         }

         if ($sort || $this->sort) 
         {
            sort($dirs);
            sort($files);
         }

         return array($dirs,$files);
      }
      else 
      {
         return false;
      }
   }


/**
  * Returns an array of all matching files in current directory
  *
  * @param string $pattern Preg_match pattern (Defaults to: .*)
  * @return array
  */
   function find ($regexp_pattern='.*') 
   {
      $data = $this->ls();
      
      if (!is_array($data))
      {
         return array();
      }
      
      list($dirs, $files) = $data;

      $found = array();
      foreach ($files as $file) 
      {
         if (preg_match("/^{$regexp_pattern}$/i", $file)) 
         {
            $found[] = $file;
         }
      }

      return $found;
   }


/**
  * Returns an array of all matching files in and below current directory
  *
  * @param string $pattern Preg_match pattern (Defaults to: .*)
  * @return array Files matching $pattern
  */
   function findRecursive ($pattern='.*') 
   {
      $starts_on = $this->path;
      $out = $this->_findRecursive($pattern);
      $this->cd($starts_on);
      return $out;
   }

/**
  * Private helper function for findRecursive.
  *
  * @param string $pattern
  * @return array Files matching pattern
  * @access private
  */
   function _findRecursive ($pattern) 
   {
      list($dirs, $files) = $this->ls();

      $found = array();
      foreach ($files as $file) 
      {
         if (preg_match("/^{$pattern}$/i", $file)) 
         {
            $found[] = $this->addPathElement($this->path, $file);
         }
      }

      $start = $this->path;
      foreach ($dirs as $dir) 
      {
         $this->cd($this->addPathElement($start, $dir));
         $found = array_merge($found, $this->findRecursive($pattern));
      }

      return $found;
   }

/**
  * Returns true if given $path is a Windows path.
  *
  * @param string $path Path to check
  * @return boolean 
  */
   function isWindowsPath ($path) 
   {
      return preg_match('#^[A-Z]:\\\#i', $path)? true: false;
   }

/**
  * Returns true if given $path is an absolute path.
  *
  * @param string $path Path to check
  * @return boolean 
  */
   function isAbsolute ($path) 
   {
      return preg_match('#^\/#', $path) || preg_match('#^[A-Z]:\\\#i', $path);
   }

/**
  * Returns true if given $path ends in a slash (i.e. is slash-terminated).
  *
  * @param string $path Path to check
  * @return boolean
  */
   function isSlashTerm ($path) 
   {
      return preg_match('#[\\\/]$#', $path)? true: false;
   }

/**
  * Returns a correct set of slashes for given $path. (\\ for Windows paths and / for other paths.)
  *
  * @param string $path Path to check
  * @return string Set of slashes ("\\" or "/")
  */
   function correctSlashFor ($path) 
   {
      return Folder::isWindowsPath($path)? '\\': '/';
   }

/**
  * Returns $path with added terminating slash (corrected for Windows or other OS).
  *
  * @param string $path Path to check
  * @return string
  */
   function slashTerm ($path) 
   {
      return $path . (Folder::isSlashTerm($path)? null: Folder::correctSlashFor($path));
   }

/**
  * Returns $path with $element added, with correct slash in-between.
  *
  * @param string $path
  * @param string $element
  * @return string
  */
   function addPathElement ($path, $element) 
   {
      return Folder::slashTerm($path).$element;
   }
}

?>