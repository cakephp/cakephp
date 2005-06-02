<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/*
 * Name: DBO/Pear
 * Pear::DB layer for DBO
  * 
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

/**
  * Enter description here...
  *
  */

uses('dbo');
/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class DBO_Pear extends DBO {

/**
  * Enter description here...
  *
  * @param unknown_type $config
  * @return unknown
  */
	function connect ($config) {
		die('Please implement DBO::connect() first.');
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function disconnect () {
		die('Please implement DBO::disconnect() first.');
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sql
  * @return unknown
  */
	function execute ($sql) {
		return $this->_pear->query($sql);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $res
  * @return unknown
  */
	function fetchRow ($result) {
		return $result->fetchRow(DB_FETCHMODE_ASSOC);
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function tables () {  // POSTGRESQL ONLY! PEAR:DB DOESN'T SUPPORT LISTING TABLES
		$sql = "SELECT a.relname AS name
         FROM pg_class a, pg_user b
         WHERE ( relkind = 'r') and relname !~ '^pg_' AND relname !~ '^sql_'
         AND relname !~ '^xin[vx][0-9]+' AND b.usesysid = a.relowner
         AND NOT (EXISTS (SELECT viewname FROM pg_views WHERE viewname=a.relname));";

		$result = $this->all($sql);

		if (!$result) {
			trigger_error(ERROR_NO_TABLE_LIST, E_USER_ERROR);
			exit;
		}
		else {
			$tables = array();
			foreach ($result as $item) $tables[] = $item['name'];
			return $tables;
		}
	}

/**
  * Enter description here...
  *
  * @param unknown_type $table_name
  * @return unknown
  */
	function fields ($table_name) {
		$data = $this->_pear->tableInfo($table_name);
		$fields = false;

		foreach ($data as $item)
			$fields[] = array('name'=>$item['name'], 'type'=>$item['type']);

		return $fields;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
  * @return unknown
  */
	function prepare ($data) {
		return $this->_pear->quoteSmart($data);
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastError () {
		return PEAR::isError($this->_result)? $this->_result->getMessage(): null;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastAffected () {
		return $this->_pear->affectedRows();
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastNumRows ($result) {
		if (method_exists($result, 'numRows'))
			return $result->numRows();
		else
			return false;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastInsertId ($table) {
		return $this->field('id', "SELECT MAX(id) FROM {$table}");
	}


}

?>