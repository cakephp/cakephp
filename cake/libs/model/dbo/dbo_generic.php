<?php
/* SVN FILE: $Id$ */

/**
 * Generic layer for DBO.
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
 * @subpackage   cake.libs.dbo
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Abstract DBO class file.
 * 
 * All implementations override this class.
 *
 * @package    cake
 * @subpackage cake.libs.dbo
 * @since      CakePHP v 0.2.9
 */
class DBO_generic extends DBO 
{

/**
 * Abstract method defined in subclasses. 
 *
 */
   function connect ($config) 
   {
   }

/**
 * Abstract method defined in subclasses. 
 *
 */
   function disconnect () 
   {
   }

/**
 * Abstract method defined in subclasses. 
 *
 */
   function execute ($sql) 
   {
   }

/**
 * Abstract method defined in subclasses. 
 *
  *
 */
   function fetchRow () 
   {
   }

/**
 * Abstract method defined in subclasses. 
 *
 *
 */
   function tablesList () 
   {
   }

/**
 * Abstract method defined in subclasses. 
 *
 */
   function fields ($tableName)
   {
   }

/**
* Abstract method defined in subclasses. 
 *
 */
   function prepareValue ($data)
   {
   }

/**
 * Abstract method defined in subclasses. 
 *
 */
   function lastError () 
   {
   }

/**
 * Abstract method defined in subclasses. 
 *
 */
   function lastAffected ()
   {
   }

/**
 * Abstract method defined in subclasses. 
 *
 */
   function lastNumRows () 
   {
   }

/**
 * Abstract method defined in subclasses. 
 *
 */
   function lastInsertId () 
   {
   }
   
/**
 * Abstract method defined in subclasses. 
 *
 */
   function selectLimit ($limit, $offset=null)
   {
   }

}

?>