<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: DBO_Postgres
  * Enter description here...
  * 
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.114
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
  * @since Cake v 1.0.0.114
  *
  */
class DBO_Postgres extends DBO {
	
/**
  * Enter description here...
  *
  * @param unknown_type $config
  * @return unknown
  */
	function connect ($config) {
		if($config) {
			$this->config = $config;
			$this->_conn = pg_pconnect("host={$config['host']} dbname={$config['database']} user={$config['login']} password={$config['password']}");
		}
		$this->connected = $this->_conn? true: false;

		if($this->connected)
			return true;
		else
			die('Could not connect to DB.');

	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function disconnect () {
		return pg_close($this->_conn);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sql
  * @return unknown
  */
	function execute ($sql) {
		return pg_query($this->_conn, $sql);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $res
  * @return unknown
  */
	function fetchRow ($res) {
		 return pg_fetch_array($res);
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function tables () {
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
		$sql = "SELECT c.relname, a.attname, t.typname FROM pg_class c, pg_attribute a, pg_type t WHERE c.relname = '{$table_name}' AND a.attnum > 0 AND a.attrelid = c.oid AND a.atttypid = t.oid";
		
		$fields = false;
		foreach ($this->all($sql) as $field) {
			$fields[] = array(
				'name' => $field['attname'],
				'type' => $field['typname']);
		}

		return $fields;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
  * @return unknown
  */
	function prepare ($data) {
		return "'".str_replace('"', '\"', str_replace('$', '$', $data))."'";
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastError () {
		return pg_last_error()? pg_last_error(): null;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastAffected () {
		return $this->_result? pg_affected_rows($this->_result): false;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastNumRows () {
		return $this->_result? pg_num_rows($this->_result): false;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $table
  * @param unknown_type $field
  * @return unknown
  */
	function lastInsertId ($table, $field='id') {
		$sql = "SELECT CURRVAL('{$table}_{$field}_seq') AS max";
		$res = $this->rawQuery($sql);
		$data = $this->fetchRow($res);
		return $data['max'];
	}
}

?>