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
  * Enter description here...
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
  * Enter description here...
  *
  * @param unknown_type $config
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
  * Enter description here...
  *
  * @return unknown
  */
	function disconnect () {
		return $this->_adodb->close();
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sql
  * @return unknown
  */
	function execute ($sql) {
		return $this->_adodb->execute($sql);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $res
  * @return unknown
  */
	function fetchRow ($res) {
		return $res->FetchRow();
	}

/**
  * Enter description here...
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
  * Enter description here...
  *
  * @param unknown_type $table_name
  * @return unknown
  */
	function fields ($table_name) {
		$data = $this->_adodb->MetaColumns($table_name);
		$fields = false;

		foreach ($data as $item)
			$fields[] = array('name'=>$item->name, 'type'=>$item->type);

		return $fields;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
  */
	function prepare ($data)		{ die('Please implement DBO::prepare() first.'); }

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastError () {
		return $this->_adodb->ErrorMsg();
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastAffected ()		{
		return $this->_adodb->Affected_Rows(); 
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function lastNumRows () {
		 return $this->_result? $this->_result->RecordCount(): false;
	}

/**
  * Enter description here...
  *
  */
	function lastInsertId ()		{ die('Please implement DBO::lastInsertId() first.'); }
}

?>