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
 * Convenience class for reading, writing and appending to files.
 *
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      CakePHP v 0.2.9
 */
class File
{
/**
 * Path to file
 *
 * @var string
 */
   var $path = null;
   
/**
 * Constructor
 *
 * @param string $path
 * @return File
 */
   function File ($path)
   {
      $this->path = $path;
   }
   
/**
 * Return the contents of this File as a string.
 *
 * @return string Contents
 */
   function read ()
   {
      return file_get_contents($this->path);
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
      if (!($handle = fopen($this->path, $mode)))
      {
         print ("[File] Could not open {$this->path} with mode $mode!");
         return false;
      }
         
      if (!fwrite($handle, $data))
         return false;
         
      if (!fclose($handle))
         return false;
      
      return true;
   }
}

?>