<?php
//////////////////////////////////////////////////////////////////////////
// + $Id $
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
  * Purpose: Log
  * Logs messages to text files.
  *
  * @filesource 
  * @author CakePHP Authors/Developers
  * @copyright Copyright (c) 2005, CakePHP Authors/Developers
  * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since CakePHP v 1.0.0.0
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */
uses('file');

class Log
{
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