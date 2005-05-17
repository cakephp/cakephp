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

/**
  * Purpose: DBO_MySQL
  * MySQL layer for DBO
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

uses('object', 'dbo');
/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class DBO_MySQL extends DBO {
	
/**
  * Enter description here...
  *
  * @param unknown_type $config
  * @return unknown
  */
	function connect ($config) {
		if($config) {
			$this->config = $config;
			$this->_conn = mysql_pconnect($config['host'],$config['login'],$config['password']);
		}
		$this->connected = $this->_conn? true: false;

		if($this->connected)
			Return mysql_select_db($config['database'], $this->_conn);
		else
			die('Could not connect to DB.');
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function disconnect () {
		return mysql_close();
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sql
  * @return unknown
  */
	function execute ($sql) {
		return mysql_query($sql);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $res
  * @return unknown
  */
	function fetchRow ($res) {
		return mysql_fetch_array($res);
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function tables () {
		$result = mysql_list_tables($this->config['database']);

		if (!$result) {
			trigger_error(ERROR_NO_TABLE_LIST, E_USER_NOTICE);
			exit;
		}
		else {
			$tables = array();
			while ($line = mysql_fetch_array($result)) {
				$tables[] = $line[0];
			}
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
		$data = $this->all("DESC {$table_name}");
		$fields = false;

		foreach ($data as $item)
			$fields[] = array('name'=>$item['Field'], 'type'=>$item['Type']);

		return $fields;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
  * @return unknown
  */
	function prepare ($data) {
		return "'".mysql_real_escape_string($data)."'";
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastError () {
		return mysql_errno()? mysql_errno().': '.mysql_error(): null;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastAffected () {
		return $this->_result? mysql_affected_rows(): false;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastNumRows () {
		return $this->_result? @mysql_num_rows($this->_result): false;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastInsertId() {
		Return mysql_insert_id();
	}

}

?>