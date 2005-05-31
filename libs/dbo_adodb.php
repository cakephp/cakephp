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
  * Purpose: DBO_AdoDB
  * AdoDB layer for DBO
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
  * Include AdoDB files.
  *
  */
require_once(VENDORS.'adodb/adodb.inc.php');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class DBO_AdoDB extends DBO {

/**
  * Connects to the database using options in the given configuration array.
  *
  * @param array $config Configuration array for connecting
  */
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
	
/**
  * Disconnects from database.
  *
  * @return unknown
  */
	function disconnect () {
		return $this->_adodb->close();
	}

/**
  * Executes given SQL statement.
  *
  * @param string $sql
  * @return unknown
  */
	function execute ($sql) {
		return $this->_adodb->execute($sql);
	}

/**
  * Return a row from given resultset.
  *
  * @param unknown_type $res Resultset
  * @return unknown
  */
	function fetchRow ($res) {
		return $res->FetchRow();
	}

/**
  * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
  *
  * @return unknown
  */
	function tables() {
		$tables = $this->_adodb->MetaTables('TABLES');

		if (!sizeof($tables)>0) {
			trigger_error(ERROR_NO_TABLE_LIST, E_USER_NOTICE);
			exit;
		}
		return $tables;
	}

/**
  * Returns an array of the fields in given table name.
  *
  * @param string $table_name Name of database table to inspect
  * @return array Fields in table. Keys are name and type
  */
	function fields ($table_name) {
		$data = $this->_adodb->MetaColumns($table_name);
		$fields = false;

		foreach ($data as $item)
			$fields[] = array('name'=>$item->name, 'type'=>$item->type);

		return $fields;
	}

/**
  * To be implemented
  *
  * @param unknown_type $data
  */
	function prepare ($data)		{ die('Please implement DBO::prepare() first.'); }

/**
  * Returns last SQL error message.
  *
  * @return unknown
  */
	function lastError () {
		return $this->_adodb->ErrorMsg();
	}

/**
  * Returns number of affected rows
  *
  * @return int
  */
	function lastAffected ()		{
		return $this->_adodb->Affected_Rows(); 
	}

/**
  * Returns number of rows in resultset of the last database operation.
  *
  * @return int Number of rows in resultset
  */
	function lastNumRows () {
		 return $this->_result? $this->_result->RecordCount(): false;
	}

/**
  * To be implemented
  *
  */
	function lastInsertId ()		{ die('Please implement DBO::lastInsertId() first.'); }
}

?>