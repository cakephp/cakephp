<?php
/* SVN FILE: $Id$ */

/**
 * Convenience class for reading, writing and appending to files.
 * 
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, CakePHP Authors/Developers
 *
 * Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com>
 *            Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The MIT License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.libs
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Enter description here...
 *
 */
uses('object');

/**
 * Convenience class for reading, writing and appending to files.
 *
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      CakePHP v 0.2.9
 */
class File extends Object
{
/**
 * Folder of the File
 *
 * @var Folder
 */
   var $folder = null;

/**
 * Filename
 *
 * @var string
 */
   var $name = null;

/**
 * Constructor
 *
 * @param string $path
 * @param boolean $create Create file if it does not exist
 * @return File
 */
   function File ($path , $create = false )
   {
      $this->folder = new Folder( dirname( realpath($path) ) , $create );

      $this->name = basename( realpath($path) );

      if ( !$this->exists() )
      {
         if ( $create === true )
         {
            if ( !$this->create() )
            {
               return false;
            }
         }
         else
         {
            return false;
         }
      }
   }

/**
 * Return the contents of this File as a string.
 *
 * @return string Contents
 */
   function read ()
   {
      return file_get_contents( $this->getFullPath() );
   }

/**
 * Append given data string to this File.
 *
 * @param string $data Data to write
 * @return boolean Success
 */
   function append ($data)
   {
      return $this->write($data, 'a');
   }

/**
 * Write given data to this File. 
 *
 * @param string $data	Data to write to this File.
 * @param string $mode	Mode of writing. {@link http://php.net/fwrite See fwrite()}.
 * @return boolean Success
 */
   function write ($data, $mode = 'w')
   {
      $file = $this->getFullPath();
      if (!($handle = fopen( $file , $mode)))
      {
         print ("[File] Could not open $file with mode $mode!");
         return false;
      }

      if (!fwrite($handle, $data))
      return false;

      if (!fclose($handle))
      return false;

      return true;
   }

/**
 * Get md5 Checksum of file with previous check of Filesize
 *
 * @param string $force	Data to write to this File.
 * @return string md5 Checksum {@link http://php.net/md5_file See md5_file()}
 */
   function getMd5 ($force = false)
   {
      $md5 = '';
      if ( $force == true || $this->getSize(false) < MAX_MD5SIZE )
      {
         $md5 = md5_file( $this->getFullPath() );
      }

      return $md5;
   }

/**
 * Get the Filesize
 *
 * @param boolean $humanReadeble	Data to write to this File.
 * @return string|int filesize as int or as humand readable string
 */
   function getSize ()
   {
      $size = filesize( $this->getFullPath() );
      return $size;
   }

/**
 * Get the Fileextension
 *
 * @return string The Fileextension
 */
   function getExt ()
   {
      $ext = '';

      $parts = explode('.', $this->getName() );

      if ( count($parts) > 1 )
      {
         $ext = array_pop( $parts );
      }
      else
      {
         $ext = '';
      }

      return $ext;
   }

/**
 * Get the Filename
 *
 * @return string The Filename
 */
   function getName ()
   {
      return $this->name;
   }

/**
 * get the File owner
 *
 * @return int the Fileowner
 */
   function getOwner ()
   {
      return fileowner( $this->getFullPath() );
   }

/**
 * get the File owner
 *
 * @return int the Filegroup
 */
   function getGroup ()
   {
      return filegroup( $this->getFullPath() );
   }

/**
 * creates the File
 *
 * @return boolean
 */
   function create ()
   {
      $dir = $this->folder->pwd();
      if ( file_exists( $dir ) && is_dir($dir) && is_writable($dir) && !$this->exists() )
      {
         if ( !touch( $this->getFullPath() ) )
         {
            print ("[File] Could not create $this->getName()!");
            return false;
         }
         else
         {
            return true;
         }
      }
      else
      {
         print ("[File] Could not create $this->getName()!");
         return false;
      }
   }

/**
 * deletes the File
 *
 * @return boolean
 */
   function exists ()
   {
      return file_exists( $this->getFullPath() );
   }

/**
 * deletes the File
 *
 * @return boolean
 */
   function delete ()
   {
      return unlink( $this->getFullPath() );
   }

/**
 * check if the File writable
 *
 * @return boolean
 */
   function writable ()
   {
      return is_writable( $this->getFullPath() );
   }

/**
 * check if the File executable
 *
 * @return boolean
 */
   function executable ()
   {
      return is_executable( $this->getFullPath() );
   }

/**
 * check if the File readable
 *
 * @return boolean
 */
   function readable ()
   {
      return is_readable( $this->getFullPath() );
   }

/**
 * get last access time
 *
 * @return int timestamp
 */
   function lastAccess ()
   {
      return fileatime( $this->getFullPath() );
   }

/**
 * get last access time
 *
 * @return int timestamp
 */
   function lastChange ()
   {
      return filemtime( $this->getFullPath() );
   }

/**
 * get the current folder
 *
 * @return Folder
 */
   function getFolder ()
   {
      return $this->folder;
   }

/**
 * get the chmod of the File
 *
 * @return string
 */
   function getChmod (  )
   {
      return substr(sprintf('%o', fileperms($this->getFullPath())), -4);
   }

/**
 * get the chmod of the File
 *
 * @return string
 */
   function getFullPath (  )
   {
      return Folder::slashTerm($this->folder->pwd()).$this->getName();
   }
}

?>
