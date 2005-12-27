<?php
/* SVN FILE: $Id$ */

/**
 * Convenience class for handling directories.
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * Included libraries.
  *
  */
if(!class_exists('Object', FALSE))
{
    uses('object');
}
/**
 * Folder structure browser, lists folders and files.
 *
 * Long description for class
 *
 * @package    cake
 * @subpackage cake.cake.libs
 * @since      CakePHP v 0.2.9
 */
class Folder extends Object {

/**
  * Path to Folder.
  *
  * @var string
  */
   var $path = null;

/**
  * Sortedness.
  *
  * @var boolean
  */
   var $sort = false;

/**
  * Constructor.
  *
  * @param string $path
  * @param boolean $path
  */
   function Folder ($path = false , $create = false, $mode = false)
   {
      if (empty($path))
      {
         $path = getcwd();
      }

      if ( !file_exists( $path ) && $create==true )
      {
          $this->mkdirr($path, $mode);
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
  * @param boolean $noDotFiles
  * @return array
  */
   function ls($sort=true , $noDotFiles = false)
   {
      $dir = opendir($this->path);

      if ($dir)
      {
         $dirs = $files = array();
         while (false !== ($n = readdir($dir)))
         {
            if ( (!preg_match('#^\.+$#', $n) && $noDotFiles == false) || ( $noDotFiles == true && !preg_match('#^\.(.*)$#', $n) ) )
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

         closedir($dir);

         return array($dirs,$files);
      }
      else
      {
         return false;
      }
   }


/**
  * Returns an array of all matching files in current directory.
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
  * Returns an array of all matching files in and below current directory.
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
  * @static
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
  * @static
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
  * @static
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
  * @static
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
  * @static
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
  * @static
  */
   function addPathElement ($path, $element)
   {
      return Folder::slashTerm($path).$element;
   }

/**
 * Returns true if the File is in a given CakePath.
 *
 * @return boolean
 */
   function inCakePath ( $path = '' )
   {
      $dir = substr( Folder::slashTerm(ROOT) , 0 , -1 );

      $newdir = Folder::slashTerm($dir.$path);

      return $this->inPath( $newdir );
   }

/**
 * Returns true if the File is in given path.
 *
 * @return boolean
 */
   function inPath ( $path = '' )
   {
      $dir = substr( Folder::slashTerm($path) , 0 , -1 );

      $return = preg_match('/^'.preg_quote(Folder::slashTerm($dir),'/').'(.*)/' , Folder::slashTerm($this->pwd()) );

      if ( $return == 1 )
      {
         return true;
      }
      else
      {
         return false;
      }
   }

/**
 * Create a directory structure recursively.
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.0
 * @param       string   $pathname    The directory structure to create
 * @return      bool     Returns TRUE on success, FALSE on failure
 */
   function mkdirr($pathname, $mode = null)
   {
      // Check if directory already exists
      if (is_dir($pathname) || empty($pathname))
      {
         return true;
      }

      // Ensure a file does not already exist with the same name
      if (is_file($pathname))
      {
         trigger_error('mkdirr() File exists', E_USER_WARNING);
         return false;
      }

      // Crawl up the directory tree
      $next_pathname = substr($pathname, 0, strrpos($pathname, DIRECTORY_SEPARATOR));
      if ($this->mkdirr($next_pathname, $mode))
      {
         if (!file_exists($pathname))
         {
            umask(0);
            return mkdir($pathname, $mode);
         }
      }

      return false;
   }

/**
 * Returns the size in bytes of this Folder.
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.0
 * @param       string   $directory    Path to directory
 */
   function dirsize()
   {
      // Init
      $size = 0;

      $directory = Folder::slashTerm($this->path);

      // Creating the stack array
      $stack = array($directory);

      // Iterate stack
      for ($i = 0, $j = count($stack); $i < $j; ++$i)
      {

         // Add to total size
         if (is_file($stack[$i]))
         {
            $size += filesize($stack[$i]);

         }

         // Add to stack
         elseif (is_dir($stack[$i]))
         {
            // Read directory
            $dir = dir($stack[$i]);
            while (false !== ($entry = $dir->read()))
            {
               // No pointers
               if ($entry == '.' || $entry == '..')
               {
                  continue;
               }

               // Add to stack
               $add = $stack[$i] . $entry;
               if (is_dir($stack[$i] . $entry))
               {
                  $add = Folder::slashTerm($add);
               }
               $stack[] = $add;

            }

            // Clean up
            $dir->close();
         }

         // Recount stack
         $j = count($stack);
      }

      return $size;
   }
}

?>