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
  * Purpose: DBO_SQLite
  * SQLite layer for DBO
  * 
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.9.0
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
  * SQLite layer for DBO.
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.9.0
  *
  */
class DBO_SQLite extends DBO {
	
/**
  * We are connecting to the database, and using config['host'] as a filename.
  *
  * @param array $config
  * @return mixed
  */
	function connect ($config) {
		if($config) {
			$this->config = $config;
			$this->_conn = sqlite_open($config['host']);
		}
		$this->connected = $this->_conn ? true: false;

		if($this->connected==false)
			die('Could not connect to DB.');
		else
			return $this->_conn;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function disconnect () {
		return sqlite_close($this->_conn);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sql
  * @return unknown
  */
	function execute ($sql) {
		return sqlite_query($this->_conn, $sql);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $res
  * @return unknown
  */
	function fetchRow ($res) {
		return sqlite_fetch_array($res);
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function tables () {
		$result = sqlite_query($this->_conn, "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");
$this->_conn
		if (!$result) {
			trigger_error(ERROR_NO_TABLE_LIST, E_USER_NOTICE);
			exit;
		}
		else {
			$tables = array();
			while ($line = sqlite_fetch_array($result)) {
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
	function fields ($table_name)
	{
		$fields = false;
		$cols = sqlite_fetch_column_types($table_name, $this->_conn, SQLITE_ASSOC);

		foreach ($cols as $column => $type)
			$fields[] = array('name'=>$column, 'type'=>$type);

		return $fields;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
  * @return unknown
  */
	function prepare ($data) {
		return "'".sqlite_escape_string($data)."'";
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastError () {
		return sqlite_last_error($this->_conn)? sqlite_last_error($this->_conn).': '.sqlite_error_string(sqlite_last_error($this->_conn)): null;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastAffected () {
		return $this->_result? sqlite_changes($this->_conn): false;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastNumRows () {
		return $this->_result? sqlite_num_rows($this->_result): false;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastInsertId() {
		Return sqlite_last_insert_rowid($this->_conn);
	}

}

?>
