<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <http://sputnik.pl/cake>                                    + //
// + Copyright: (c) 2005 Michal Tatarynowicz                          + //
// +                                                                  + //
// + Author(s): (c) 2005 Michal Tatarynowicz <tatarynowicz@gmail.com> + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under the Public Domain Licence                         + //
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
  *	require('dbo.php');
  *
  *	// create and connect the object
  *	$db = new DBO(array(
  *		'host'=>'localhost',
  *		'login'=>'username',
  *		'password'=>'password',
  *		'database'=>'database'
  *		'type'=>'mysql'));
  *
  *	// read the whole query result array (of rows)
  *	$all_rows = $db->all("SELECT a,b,c FROM table");
  *
  *	// read the first row with debugging on
  * 	$first_row_only = $db->one("SELECT a,b,c FROM table WHERE a=1", TRUE);
  *
  *	// emulate the usual MySQL way of reading query results
  *	if ($db->q("SELECT a,b,c FROM table")) {
  *		while ( $row = $db->farr() ) {
  * 			print $row['a'].$row['b'].$row['c'];
  *		}
  *	}
  *	
  *	// show a log of all queries, sorted by execution time
  *	$db->show_log(TRUE);
  *	</code>
  * 
  * @filesource 
  * @modifiedby $LastChangedBy$  
  * @lastmodified $Date$
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @copyright Copyright (c) 2005, Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @license Public_Domain
  *
  */

/**
  * Enter description here...
  *
  */
require_once('adodb/adodb.inc.php');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class DBO {

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
    var $error=NULL;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
    var $insert_id=NULL;

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
    var $_queries_cnt=0;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
    var $_queries_time=NULL;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
    var $_queries_log=array();

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
    var $_adodb=NULL;

/**
  * Enter description here...
  *
  * @param unknown_type $config
  * @param unknown_type $DEBUG
  * @return DBO
  */
    function DBO($config=NULL,$DEBUG=FALSE) {
        $this->__constructor($config,$DEBUG);
    }

/**
  * Enter description here...
  *
  * @param unknown_type $config
  * @param unknown_type $DEBUG
  */
    function __constructor($config=NULL,$DEBUG=FALSE) {
        $this->debug = $DEBUG;
        if ($DEBUG > 1) register_shutdown_function( array( &$this, "show_log" ) );
        $this->connect($config);
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
  * @param unknown_type $config
  */
    function connect($config) {
        if($config) {
            $this->config = $config;
            if(isset($this->config['type'])) {
                $this->_adodb = NewADOConnection($this->config['type']);
                $adodb =& $this->_adodb;
                $this->connected = $adodb->Connect($this->config['host'],$this->config['login'],$this->config['password'],$this->config['database']);
            }
        }

        if(!$this->connected)
        die('Could not connect to DB.');
    }

/**
  * Enter description here...
  *
  */
    function close() {
        $adodb =& $this->_adodb;
        $adodb->close;
        showLog();
        $this->_conn = NULL;
        $this->connected = NULL;
    }

/**
  * Enter description here...
  *
  * @param unknown_type $q
  * @param unknown_type $DEBUG
  * @param unknown_type $log
  * @return unknown
  */
    function q($q,$DEBUG=FALSE,$log=TRUE) {
        Return $this->query($q,$DEBUG,$log);
    }

/**
  * Enter description here...
  *
  * @param unknown_type $q
  * @param unknown_type $DEBUG
  * @param unknown_type $log
  * @return unknown
  */
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
            $this->_log_query($q);

            if($this->debug || $DEBUG) $this->_show_query($q);

            Return $this->error? FALSE: $this->_result;
        }
        else {
            $this->_result = $adodb->Execute($q);
            Return $this->_result;
        }
    }

/**
  * Enter description here...
  *
  * @return unknown
  */
    function farr() {
        $result =& $this->_result;
        return $result->FetchRow();
    }

    //SAME AS ABOVE?
/**
  * Enter description here...
  *
  * @param unknown_type $q
  * @param unknown_type $DEBUG
  * @return unknown
  */
    function one($q,$DEBUG=FALSE) {
        $result =& $this->_result;
        Return $this->query($q,$DEBUG)? $result->FetchRow(): FALSE;
    }

/**
  * Enter description here...
  *
  * @param unknown_type $q
  * @param unknown_type $DEBUG
  * @return unknown
  */
    function all($q,$DEBUG=FALSE) {
        if($this->query($q,$DEBUG)) {
            $result = $this->_result;
            return $result->GetRows();
        } else {
            Return FALSE;
        }
    }

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @param unknown_type $q
  * @param unknown_type $DEBUG
  * @return unknown
  */
    function field($name, $q, $DEBUG=FALSE) {
        $data = $this->one($q, $DEBUG);
        return empty($data[$name])? false: $data[$name];
    }

/**
  * Enter description here...
  *
  * @return unknown
  */
    function tables() {
        $adodb =& $this->_adodb;
        $tables = $adodb->MetaTables('TABLES');

        if (!sizeof($tables)>0) {
            trigger_error(ERROR_NO_TABLE_LIST, E_USER_NOTICE);
            exit;
        }
        return $tables;
    }

/**
  * Enter description here...
  *
  * @param unknown_type $table
  * @param unknown_type $sql
  * @return unknown
  */
    function has_any($table, $sql) {
        $out = $this->one("SELECT COUNT(*) AS count FROM {$table} WHERE {$sql}");
        return is_array($out)? $out['count']: FALSE;
    }

/**
  * Enter description here...
  *
  * @return unknown
  */
    function got_connected() {
        Return $this->connected;
    }

/**
  * Enter description here...
  *
  * @return unknown
  */
    function got_insert_id() {
        Return $this->insert_id;
    }

/**
  * Enter description here...
  *
  * @return unknown
  */
    function got_affected() {
        Return $this->affected;
    }

/**
  * Enter description here...
  *
  * @return unknown
  */
    function got_num_rows() {
        Return $this->num_rows;
    }

/**
  * Enter description here...
  *
  * @return unknown
  */
    function got_error() {
        return $this->error;
    }

/**
  * Enter description here...
  *
  * @param unknown_type $sorted
  */
    function show_log($sorted=FALSE) {
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

/**
  * Enter description here...
  *
  * @param unknown_type $q
  */
    function _log_query($q) {
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

/**
  * Enter description here...
  *
  * @param unknown_type $q
  */
    function _show_query($q) {
        $error = $this->error;

        if ($this->debug || $error) {
            print("<p style=\"text-align:left\"><b>Query:</b> {$q} <small>[Aff:{$this->affected} Num:{$this->num_rows} Took:{$this->took}ms]</small>");
            if($error) {
                print("<br /><span style=\"color:Red;text-align:left\"><b>ERROR:</b> {$this->error}</span>");
            }
            print('</p>');
        }
    }

}

if (!function_exists('getMicrotime')) {
/**
  * Enter description here...
  *
  * @return unknown
  */
    function getMicrotime() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}
if (!function_exists('sortByKey')) {
/**
  * Enter description here...
  *
  * @param unknown_type $array
  * @param unknown_type $sortby
  * @param unknown_type $order
  * @param unknown_type $type
  * @return unknown
  */
    function sortByKey(&$array, $sortby, $order='asc', $type=SORT_NUMERIC) {

        if( is_array($array) ) {

            foreach( $array AS $key => $val )
            $sa[$key] = $val[$sortby];

            if( $order == 'asc' )
            asort($sa, $type);
            else
            arsort($sa, $type);

            foreach( $sa as $key=>$val )
            $out[] = $array[$key];

            Return $out;

        }
        else
        Return null;
    }
}

?>