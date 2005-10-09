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
 * @subpackage   cake.cake.libs
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * Enter description here...
  */
uses('file');
    
/**
 * Logs messages to text files
 *
 * Long description for class
 *
 * @package    cake
 * @subpackage cake.cake.libs
 * @since      CakePHP v 0.2.9
 */
class Log
{
/**
 * Enter description here...
 *
 * @param unknown_type $type
 * @param unknown_type $msg
 * @return unknown
 */
   function write($type, $msg)
   {
      $filename = LOGS.$type.'.log';
      $output = date('y-m-d H:i:s').' '.ucfirst($type).': '.$msg."\n";

      $log = new File($filename);
      return $log->append($output);
   }
}

/**
  * Error constant. Used for differentiating error logging and debugging.
  * Currently PHP supports LOG_DEBUG
  */
define ('LOG_ERROR', 2);

/**
  * Shortcut.
  */
function LogError ($message)
{
   $bad = array("\n", "\r", "\t");
   $good = ' ';
   Log::write('error', str_replace($bad, $good, $message));
}

?>