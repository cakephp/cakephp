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
  * @subpackage cake.libs.dbo
  * @since CakePHP v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */


/**
  * AdoDB layer for DBO.
  *
  * @package cake
  * @subpackage cake.libs.dbo
  * @since CakePHP v 0.2.9
  */
class DBO_generic extends DBO 
{

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $config
	 */
	function connect ($config) 
	{
	}

	/**
	 * Enter description here...
	 *
	 */
	function disconnect () 
	{
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $sql
	 */
	function execute ($sql) 
	{
	}

	/**
	 * Enter description here...
	 *
	 */
	function fetchRow () 
	{
	}

	/**
	 * Enter description here...
	 *
	 */
	function tablesList () 
	{
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $table_name
	 */
	function fields ($table_name)
	{
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $data
	 */
	function prepareValue ($data)
	{
	}

	/**
	 * Enter description here...
	 *
	 */
	function lastError () 
	{
	}

	/**
	 * Enter description here...
	 *
	 */
	function lastAffected ()
	{
	}

	/**
	 * Enter description here...
	 *
	 */
	function lastNumRows () 
	{
	}

	/**
	 * Enter description here...
	 *
	 */
	function lastInsertId () 
	{
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $limit
	 * @param unknown_type $offset
	 */
	function selectLimit ($limit, $offset=null)
	{
	}

}

?>