<?PHP
/*
 * Name: DBO/PostgreSQL
 * Author: Michal Tatarynowicz (tatarynowicz@gmail.com)
 * Licence: Public Domain
*/

uses('object', 'dbo');

class DBO_Postgres extends DBO {
	
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

	function disconnect () {
		return pg_close($this->_conn);
	}

	function execute ($sql) {
		return pg_query($this->_conn, $sql);
	}

	function fetchRow ($res) {
		 return pg_fetch_array($res);
	}

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

	function prepare ($data) {
		return "'".str_replace('"', '\"', str_replace('$', '$', $data))."'";
	}

	function lastError () {
		return pg_last_error()? pg_last_error(): null;
	}

	function lastAffected () {
		return $this->_result? pg_affected_rows($this->_result): false;
	}

	function lastNumRows () {
		return $this->_result? pg_num_rows($this->_result): false;
	}

	function lastInsertId ($table, $field='id') {
		$sql = "SELECT CURRVAL('{$table}_{$field}_seq') AS max";
		$res = $this->rawQuery($sql);
		$data = $this->fetchRow($res);
		return $data['max'];
	}
}

?>