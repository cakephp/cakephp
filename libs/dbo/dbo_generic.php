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
  * Generic layer for DBO.
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

class DBO_generic extends DBO 
{

	function connect ($config) 
	{
	}

	function disconnect () 
	{
	}

	function execute ($sql) 
	{
	}

	function fetchRow () 
	{
	}

	function tablesList () 
	{
	}

	function fields ($table_name)
	{
	}

	function prepareValue ($data)
	{
	}

	function lastError () 
	{
	}

	function lastAffected ()
	{
	}

	function lastNumRows () 
	{
	}

	function lastInsertId () 
	{
	}
	
	function selectLimit ($limit, $offset=null)
	{
	}

}

?>