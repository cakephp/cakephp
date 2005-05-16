<?PHP
/*
 * Name: DBO/ADO
 * Author: Michal Tatarynowicz (tatarynowicz@gmail.com),  Giovanni Degani
 * Licence: Public Domain
*/

require_once(VENDORS.'adodb/adodb.inc.php');

class DBO_AdoDB extends DBO {

	function connect ($config) {
		if($this->config = $config) {
			if(isset($this->config['driver'])) {
				$this->_adodb = NewADOConnection($this->config['driver']);

				$adodb =& $this->_adodb;
				$this->connected = $adodb->Connect($this->config['host'],$this->config['login'],$this->config['password'],$this->config['database']);
			}
		}

		if(!$this->connected)
			die('Could not connect to DB.');
	}
	
	function disconnect () {
		return $this->_adodb->close();
	}

	function execute ($sql) {
		return $this->_adodb->execute($sql);
	}

	function fetchRow ($res) {
		return $res->FetchRow();
	}

	function tables() {
		$tables = $this->_adodb->MetaTables('TABLES');

		if (!sizeof($tables)>0) {
			trigger_error(ERROR_NO_TABLE_LIST, E_USER_NOTICE);
			exit;
		}
		return $tables;
	}

	function fields ($table_name) {
		$data = $this->_adodb->MetaColumns($table_name);
		$fields = false;

		foreach ($data as $item)
			$fields[] = array('name'=>$item->name, 'type'=>$item->type);

		return $fields;
	}

	function prepare ($data)		{ die('Please implement DBO::prepare() first.'); }

	function lastError () {
		return $this->_adodb->ErrorMsg();
	}

	function lastAffected ()		{
		return $this->_adodb->Affected_Rows(); 
	}

	function lastNumRows () {
		 return $this->_result? $this->_result->RecordCount(): false;
	}

	function lastInsertId ()		{ die('Please implement DBO::lastInsertId() first.'); }
/*


	function connect($config) {
		if($config) {
			$this->config = $config;
			if(isset($this->config['driver'])) {
				$this->_adodb = NewADOConnection($this->config['driver']);
				$adodb =& $this->_adodb;
				$this->connected = $adodb->Connect($this->config['host'],$this->config['login'],$this->config['password'],$this->config['database']);
			}
		}

		if(!$this->connected)
			die('Could not connect to DB.');
	}

	function close() {
		$adodb =& $this->_adodb;
		$adodb->close;
		showLog();
		$this->_conn = NULL;
		$this->connected = NULL;
	}

	function query($q,$DEBUG=FALSE,$log=TRUE) {
		$adodb =& $this->_adodb;
		$t = getMicrotime();

		if($log){
			$this->_result =& $adodb->Execute($q);
			$result =& $this->_result;
			$this->took = round((getmicrotime()-$t)*1000, 0);
			if(!$this->_result && $adodb->ErrorMsg())
				$this->error = $adodb->ErrorMsg();
			else
				$this->error = NULL;

			$this->insert_id = $adodb->Insert_ID();
			
			$this->affected = $adodb->Affected_Rows();
			
			$this->num_rows = $result->RecordCount();
			$this->_logQuery($q);

			if($this->debug || $DEBUG) $this->_showQuery($q);

			Return $this->error? FALSE: $this->_result;
		}
		else {
			$this->_result = $adodb->Execute($q);
			Return $this->_result;
		}
	}
	
	function farr() {
		$result =& $this->_result;
		return $result->FetchRow();
	}
	
	//SAME AS ABOVE? 
	function one($q,$DEBUG=FALSE) {
		$result =& $this->_result;
		Return $this->query($q,$DEBUG)? $result->FetchRow(): FALSE;
	}
	
	function all($q,$DEBUG=FALSE) {
		if($this->query($q,$DEBUG)) {
			$result = $this->_result;
			return $result->GetRows();
		} else {
			Return FALSE;
		}
	}
	
	function field($name, $q, $DEBUG=FALSE) {
		$data = $this->one($q, $DEBUG);
		return empty($data[$name])? false: $data[$name];
	}

	function tables() {
		$adodb =& $this->_adodb;
		$tables = $adodb->MetaTables('TABLES');

		if (!sizeof($tables)>0) {
			trigger_error(ERROR_NO_TABLE_LIST, E_USER_NOTICE);
			exit;
		}
		return $tables;
	}

	function fields ($table_name) {
		return $this->all("DESC {$table_name}");
	}


	function hasAny($table, $sql) {
		$out = $this->one("SELECT COUNT(*) AS count FROM {$table}".($sql? " WHERE {$sql}":""));
		return is_array($out)? $out['count']: FALSE;
	}

	function isConnected() {
		Return $this->connected;
	}
	function lastInsertId() {
		Return $this->insert_id;
	}
	function lastAffected() {
		Return $this->affected;
	}
	function lastNumRows() {
		Return $this->num_rows;
	}
	function lastError() {
		return $this->error;
	}

	function showLog($sorted=FALSE) {
		$log = $sorted?
			sortByKey($this->_queries_log, 'took', 'desc', SORT_NUMERIC):
			$this->_queries_log;

		print("<table border=1>\n<tr><th colspan=7>{$this->_queries_cnt} queries took {$this->_queries_time} ms</th></tr>\n");
		print("<tr><td>Nr</td><td>Query</td><td>Error</td><td>Affected</td><td>Num. rows</td><td>Took (ms)</td></tr>\n");

		foreach($log AS $k=>$i) {
			print("<tr><td>".($k+1)."</td><td>{$i['query']}</td><td>{$i['error']}</td><td align='right'>{$i['affected']}</td><td align='right'>{$i['num_rows']}</td><td align='right'>{$i['took']}</td></tr>\n");
		}

		print("</table>\n");
	}
	
	function _logQuery($q) {
		$this->_queries_cnt++;
		$this->_queries_time += $this->took;
		$this->_queries_log[] = array(
			'query'=>$q,
			'error'=>$this->error,
			'affected'=>$this->affected,
			'num_rows'=>$this->num_rows,
			'took'=>$this->took
			);


		if ($this->error && function_exists('logError'))
			logError("Query: {$q} RETURNS ERROR {$this->error}");
	}
	
	function _showQuery($q) {
		$error = $this->error;

		if ($this->debug || $error) {
			print("<p style=\"text-align:left\"><b>Query:</b> {$q} <small>[Aff:{$this->affected} Num:{$this->num_rows} Took:{$this->took}ms]</small>");
			if($error) {
				print("<br /><span style=\"color:Red;text-align:left\"><b>ERROR:</b> {$this->error}</span>");
			}
			print('</p>');
		}
	}
*/
}

?>