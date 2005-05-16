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
  * Purpose: DBO/ADO
  * 
  * Description: 
  * A MySQL functions wrapper. Provides ability to get results as arrays
  * and query logging (with execution time).
  *
  * Example usage:
  *
  * <code>
	require('dbo_mysql.php'); // or 'dbo_postgres.php'

	// create and connect the object
	$db = new DBO_MySQL(array( // or 'DBO_Postgres'
		'host'=>'localhost',
		'login'=>'username',
		'password'=>'password',
		'database'=>'database'));

	// read the whole query result array (of rows)
	$all_rows = $db->all("SELECT a,b,c FROM table");

	// read the first row with debugging on
	$first_row_only = $db->one("SELECT a,b,c FROM table WHERE a=1", TRUE);

	// emulate the usual way of reading query results
	if ($db->query("SELECT a,b,c FROM table")) {
		while ( $row = $db->farr() ) {
			print $row['a'].$row['b'].$row['c'];
		}
	}
	
	// show a log of all queries, sorted by execution time
	$db->showLog(TRUE);
  * </code>
  *
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
  */

/**
  * Enter description here...
  *
  */
uses('object');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class DBO extends Object {
	
/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $connected=FALSE;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $debug=FALSE;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $error=NULL;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $affected=NULL;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $numRows=NULL;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $took=NULL;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
    var $_conn=NULL;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
    var $_result=NULL;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
    var $_queriesCnt=0;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
    var $_queriesTime=NULL;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
	var $_queriesLog=array();

	// specific for each database, implemented in db drivers
	function connect ($config)		{ die('Please implement DBO::connect() first.'); }
	function disconnect ()			{ die('Please implement DBO::disconnect() first.'); }
	function execute ($sql)			{ die('Please implement DBO::execute() first.'); }
	function fetchRow ($result)	{ die('Please implement DBO::fetchRow() first.'); }
	function tables()					{ die('Please implement DBO::tables() first.'); }
	function fields ($table_name) { die('Please implement DBO::fields() first.'); }
	function prepare ($data)		{ die('Please implement DBO::prepare() first.'); }
	function lastError ()			{ die('Please implement DBO::lastError() first.'); }
	function lastAffected ()		{ die('Please implement DBO::lastAffected() first.'); }
	function lastNumRows ($result){ die('Please implement DBO::lastNumRows() first.'); }
	function lastInsertId ()		{ die('Please implement DBO::lastInsertId() first.'); }

/**
  * Enter description here...
  *
  * @param unknown_type $config
  * @param unknown_type $DEBUG
  * @return unknown
  */
	function __construct ($config=NULL) {
		$this->debug = DEBUG > 1;
		parent::__construct();
		return $this->connect($config);
	}

/**
  * Enter description here...
  *
  */
    function __destructor() {
        $this->close();
    }

/**
  * Enter description here...
  *
  * @param unknown_type $db_name
  * @return unknown
  */
	function useDb ($db_name) {
		return $this->query("USE {$db_name}");
	}

/**
  * Enter description here...
  *
  */
	function close () {
		if ($this->debug) $this->showLog();
		$this->disconnect();
		$this->_conn = NULL;
		$this->connected = false;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sql
  * @return unknown
  */
	function rawQuery ($sql) {
		$this->took = $this->error = $this->numRows = false;
		return $this->execute($sql);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sql
  * @return unknown
  */
	function query($sql) {
	  $t = getMicrotime();
	  $this->_result = $this->execute($sql);
	  $this->affected = $this->lastAffected();
	  $this->took = round((getMicrotime()-$t)*1000, 0);
	  $this->error = $this->lastError();
	  $this->numRows = $this->lastNumRows($this->_result);
	  $this->logQuery($sql);
	  if ($this->debug) $this->showQuery($sql);

	  return $this->error? false: $this->_result;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $results
  * @param unknown_type $type
  * @return unknown
  */
	function farr ($res=false) {
		return $this->fetchRow($res? $res: $this->_result);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sql
  * @return unknown
  */
	function one ($sql) {
		return $this->query($sql)? $this->farr(): false;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sql
  * @return unknown
  */
	function all ($sql) {
		if($this->query($sql)) {
			$out=array();
			while($item = $this->farr()) $out[] = $item;
			return $out;
		}
		else {
			return false;
		}
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @param unknown_type $sql
  * @return unknown
  */
	function field ($name, $sql) {
		$data = $this->one($sql);
		return empty($data[$name])? false: $data[$name];
	}

/**
  * Enter description here...
  *
  * @param unknown_type $table
  * @param unknown_type $sql
  * @return unknown
  */
	function hasAny($table, $sql) {
		$out = $this->one("SELECT COUNT(*) AS count FROM {$table}".($sql? " WHERE {$sql}":""));
		return is_array($out)? $out['count']: false;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function isConnected() {
		return $this->connected;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sorted
  */
	function showLog($sorted=false) {
		$log = $sorted?
			sortByKey($this->_queriesLog, 'took', 'desc', SORT_NUMERIC):
			$this->_queriesLog;

		print("<table border=1>\n<tr><th colspan=7>{$this->_queriesCnt} queries took {$this->_queriesTime} ms</th></tr>\n");
		print("<tr><td>Nr</td><td>Query</td><td>Error</td><td>Affected</td><td>Num. rows</td><td>Took (ms)</td></tr>\n");

		foreach($log AS $k=>$i) {
			print("<tr><td>".($k+1)."</td><td>{$i['query']}</td><td>{$i['error']}</td><td align='right'>{$i['affected']}</td><td align='right'>{$i['numRows']}</td><td align='right'>{$i['took']}</td></tr>\n");
		}

		print("</table>\n");
	}

/**
  * Enter description here...
  *
  * @param unknown_type $q
  */
	function logQuery($sql) {
		$this->_queriesCnt++;
		$this->_queriesTime += $this->took;
		$this->_queriesLog[] = array(
			'query'=>$sql,
			'error'=>$this->error,
			'affected'=>$this->affected,
			'numRows'=>$this->numRows,
			'took'=>$this->took
			);

		if ($this->error)
			false; // shouldn't we be logging errors somehow?
	}

/**
  * Enter description here...
  *
  * @param unknown_type $q
  */
	function showQuery($sql) {
		$error = $this->error;

		if ($this->debug || $error) {
			print("<p style=\"text-align:left\"><b>Query:</b> {$sql} <small>[Aff:{$this->affected} Num:{$this->numRows} Took:{$this->took}ms]</small>");
			if($error) {
				print("<br /><span style=\"color:Red;text-align:left\"><b>ERROR:</b> {$this->error}</span>");
			}
			print('</p>');
		}
	}
}

?>