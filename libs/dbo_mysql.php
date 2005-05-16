<?PHP
/*
 * Name: DBO/MySQL
 * Author: Michal Tatarynowicz (tatarynowicz@gmail.com)
 * Licence: Public Domain
*/

uses('object', 'dbo');

class DBO_MySQL extends DBO {
	
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

	function disconnect () {
		return mysql_close();
	}

	function execute ($sql) {
		return mysql_query($sql);
	}

	function fetchRow ($res) {
		return mysql_fetch_array($res);
	}

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

	function fields ($table_name) {
		$data = $this->all("DESC {$table_name}");
		$fields = false;

		foreach ($data as $item)
			$fields[] = array('name'=>$item['Field'], 'type'=>$item['Type']);

		return $fields;
	}

	function prepare ($data) {
		return "'".str_replace("'", "\\'", $data)."'";
	}

	function lastError () {
		return mysql_errno()? mysql_errno().': '.mysql_error(): null;
	}

	function lastAffected () {
		return $this->_result? mysql_affected_rows(): false;
	}

	function lastNumRows () {
		return $this->_result? @mysql_num_rows($this->_result): false;
	}

	function lastInsertId() {
		Return mysql_insert_id();
	}

}

?>