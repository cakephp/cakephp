<?php
//////////////////////////////////////////////////////////////////////////
// + $Id:$
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
 * Purpose: Dispatcher
 * Dispatches the request, creating aproppriate models and controllers.
 * 
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 * @version $Revision:$
 * @modifiedby $LastChangedBy:$
 * @lastmodified $Date:$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Dispatches the request, creating appropriate models and controllers.
 *
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 */
class File
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $path = null;
	
/**
 * Enter description here...
 *
 * @param unknown_type $path
 * @return File
 */
	function File ($path)
	{
		$this->path = $path;
	}
	
/**
 * Enter description here...
 *
 * @return unknown
 */
	function read ()
	{
		return file_get_contents($this->path);
	}
	
/**
 * Enter description here...
 *
 * @param unknown_type $data
 * @return unknown
 */
	function append ($data)
	{
		return $this->write($data, 'a');
	}
	
/**
 * Enter description here...
 *
 * @param unknown_type $data
 * @param unknown_type $mode
 * @return unknown
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