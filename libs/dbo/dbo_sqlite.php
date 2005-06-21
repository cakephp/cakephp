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
 * Include DBO.
 */
uses('dbo');

/**
 * SQLite layer for DBO.
 *
 * @package cake
 * @subpackage cake.libs
 * @since Cake v 0.9.0
 */
class DBO_SQLite extends DBO 
{

	/**
	 * Connects to the database using config['file'] as a filename.
	 *
	 * @param array $config Configuration array for connecting
	 * @return mixed
	 */
	function connect($config) 
	{
		if ($config) 
		{
			$this->config = $config;
			$this->_conn = sqlite_open($config['file']);
		}
		$this->connected = $this->_conn? true: false;

		if($this->connected)
		{
			return $this->_conn;
		}
		else
		{
			die('Could not connect to DB.');
		}
	}

	/**
	 * Disconnects from database.
	 *
	 * @return boolean True if the database could be disconnected, else false
	 */
	function disconnect() 
	{
		return sqlite_close($this->_conn);
	}

	/**
	 * Executes given SQL statement.
	 *
	 * @param string $sql SQL statement
	 * @return resource Result resource identifier
	 */
	function execute($sql) 
	{
		return sqlite_query($this->_conn, $sql);
	}

	/**
	 * Returns a row from given resultset as an array .
	 *
	 * @return array The fetched row as an array
	 */
	function fetchRow() 
	{
		return sqlite_fetch_array($this->_result);
	}

	/**
	 * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
	 *
	 * @return array Array of tablenames in the database
	 */
	function tablesList() 
	{
		$result = sqlite_query($this->_conn, "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");

		if (!$result) 
		{
			trigger_error(ERROR_NO_TABLE_LIST, E_USER_NOTICE);
			exit;
		}
		else 
		{
			$tables = array();
			while ($line = sqlite_fetch_array($result)) 
			{
				$tables[] = $line[0];
			}
			return $tables;
		}
	}
	
	/**
	 * Returns an array of the fields in given table name.
	 *
	 * @param string $table_name Name of database table to inspect
	 * @return array Fields in table. Keys are name and type
	 */
	function fields($table_name)
	{
		$fields = false;
		$cols = sqlite_fetch_column_types($table_name, $this->_conn, SQLITE_ASSOC);

		foreach ($cols as $column => $type)
		{
			$fields[] = array('name'=>$column, 'type'=>$type);
		}

		return $fields;
	}

	/**
	 * Returns a quoted and escaped string of $data for use in an SQL statement.
	 *
	 * @param string $data String to be prepared for use in an SQL statement
	 * @return string Quoted and escaped
	 */
	function prepareValue($data)
	{
		return "'".sqlite_escape_string($data)."'";
	}

	/**
	 * Returns a formatted error message from previous database operation.
	 *
	 * @return string Error message
	 */
	function lastError() 
	{
		return sqlite_last_error($this->_conn)? sqlite_last_error($this->_conn).': '.sqlite_error_string(sqlite_last_error($this->_conn)): null;
	}

	/**
	 * Returns number of affected rows in previous database operation. If no previous operation exists, this returns false.
	 *
	 * @return int Number of affected rows
	 */
	function lastAffected()
	{
		return $this->_result? sqlite_changes($this->_conn): false;
	}

	/**
	 * Returns number of rows in previous resultset. If no previous resultset exists, 
	 * this returns false.
	 *
	 * @return int Number of rows in resultset
	 */
	function lastNumRows() 
	{
		return $this->_result? sqlite_num_rows($this->_result): false;
	}

	/**
	 * Returns the ID generated from the previous INSERT operation.
	 *
	 * @return int 
	 */
	function lastInsertId() 
	{
		return sqlite_last_insert_rowid($this->_conn);
	}

	/**
	 * Returns a limit statement in the correct format for the particular database.
	 *
	 * @param int $limit Limit of results returned
	 * @param int $offset Offset from which to start results
	 * @return string SQL limit/offset statement
	 */
	function selectLimit($limit, $offset=null)
	{
		// :TODO: Please verify this with SQLite, untested.
		return " LIMIT {$limit}".($offset? " OFFSET {$offset}": null);
	}

}

?>
