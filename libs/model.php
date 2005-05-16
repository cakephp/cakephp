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
  * Purpose: Model
  * DBO-backed object data model, loosly based on RoR (www.rubyonrails.com).
  * Automatically selects db table name based on pluralized lowercase object class name
  * (i.e. class 'User' => table 'users'; class 'Man' => table 'men')
  * The table is required to have at least 'id auto_increment', 'created datetime', 
  * and 'modified datetime' fields
  *
  * To do:
  *   - schema-related cross-table ($has_one, $has_many, $belongs_to)
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
uses('object', 'validators', 'inflector');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Model extends Object {
    
/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $parent = false;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $use_table = false;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $id = false;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $data = array();

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $table = false;
	// private
/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
	var $_table_info = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
	var $_oneToMany = array();

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
	var $_oneToOne = array();

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
	var $_hasMany = array();

/**
  * Enter description here...
  *
  * append entries for validation as ('field_name' => '/^perl_compat_regexp$/') that has to match with preg_match()
  * validate with Model::validate()
  * @var unknown_type
  */
	var $validate = array();

/**
  * Enter description here...
  *
  * @param unknown_type $id
  */
	function __construct ($id=false) {
		global $DB;

		$this->db = &$DB;

		if ($id) 
			$this->id = $id;

		$table_name = $this->use_table? $this->use_table: Inflector::tableize(get_class($this));
		$this->use_table ($table_name);
		parent::__construct();

		$this->create_links();
	}

/**
  * Enter description here...
  *
  */
	function create_links () {
		if (!empty($this->hasMany))
			$this->_hasMany = explode(',', $this->hasMany);
		
		foreach ($this->_hasMany as $model_name) {
			// todo fix strip the model name
			$model_name = Inflector::singularize($model_name);
			$this->$model_name = new $model_name();
		}

		$this->relink();
	}

/**
  * Enter description here...
  *
  */
	function relink () {
		foreach ($this->_hasMany as $model) {
			$name = Inflector::singularize($model);
			$this->$name->clear_links();
			$this->$name->link_many_to_one(get_class($this), $this->id);
		}
	}

/**
  * Enter description here...
  *
  * @param unknown_type $model_name
  * @param unknown_type $value
  */
	function link_many_to_one ($model_name, $value=null) {
		$table_name = Inflector::tableize($model_name);
		$field_name = Inflector::singularize($table_name).'_id';
		$this->_one_to_many[] = array($table_name, $field_name, $value);
	}

/**
  * Enter description here...
  *
  */
	function clear_links () {
		$this->_one_to_many = array();
	}

/**
  * Enter description here...
  *
  * @param unknown_type $table_name
  */
	function use_table ($table_name) {
		if (!in_array($table_name, $this->db->tables())) {
			trigger_error (sprintf(ERROR_NO_MODEL_TABLE, get_class($this), $table_name), E_USER_ERROR);
			die();
		}
		else {
			$this->table = $table_name;
			$this->load_info();
		}
	}


/**
  * Enter description here...
  *
  * @param unknown_type $one
  * @param unknown_type $two
  * @return unknown
  */
	function set ($one, $two=null) {
		$data = is_array($one)? $one: array($one=>$two);

		foreach ($data as $n => $v) {
			if (!$this->has_field($n)) {
				DEBUG? 
					trigger_error(sprintf(ERROR_NO_FIELD_IN_MODEL_DB, $n, $this->table), E_USER_ERROR):
					trigger_error('Application error occured, trying to set a field name that doesn\'t exist.', E_USER_WARNING);
			}

			$n == 'id'? $this->id = $v: $this->data[$n] = $v;
		}

		return $data;
    }

/**
  * Enter description here...
  *
  * @param unknown_type $id
  */
	function set_id ($id) {
		$this->id = $id;
		$this->relink();
	}

/**
  * Enter description here...
  *
  */
	function load_info () {
		if (empty($this->_table_info))
			$this->_table_info = new neatArray($this->db->fields($this->table));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
	function has_field ($name) {
		return $this->_table_info->findIn('name', $name);
	}

/**
  * reads a list of fields from the db
  *
  * @param string $fields
  * @param array $fields
  * @return array of values
  */
	function read ($fields=null) {
		return $this->id? $this->find("id = '{$this->id}'", $fields): false;
	}

/**
  * reads a field from a record
  *
  * @param string $name
  * @return field contents
  */
	function field ($name) {
		if (isset($this->data[$name]))
			return $this->data[$name];
		else {
			if ($this->id && $data = $this->read($name)) {
				return isset($data[$name])? $data[$name]: false;
			}
			else {
				return false;
			}
		}
	}

/**
  * saves model data to the db
  *
  * @param array $data
  * @return success
  */
	function save ($data=null) {
		if ($data) $this->set($data);

		if (!$this->validates())
			return false;

		$fields = $values = array();
		foreach ($this->data as $n=>$v) {
			$fields[] = $n;
			$values[] = $this->db->prepare($v);
		}

		if (empty($this->id) && $this->has_field('created')) {
			$fields[] = 'created';
			$values[] = date("'Y-m-d H:i:s'");
		}
		if ($this->has_field('modified')) {
			$fields[] = 'modified';
			$values[] = 'NOW()';
		}

		if(count($fields)){
			if($this->id){
				$sql = array();
				foreach (array_combine($fields, $values) as $field=>$value) {
					$sql[] = $field.'='.$value;
				}
				if($this->db->query("UPDATE {$this->table} SET ".join(',', $sql)." WHERE id = '{$this->id}'") && $this->db->lastAffected()){
					$this->data = false;
					return true;
				}
				else {
					return $this->db->hasAny($this->table, "id = '{$this->id}'");
				}
			}
			else {
				$fields = join(',', $fields);
				$values = join(',', $values);

				if($this->db->query("INSERT INTO {$this->table} ({$fields}) VALUES ({$values})")) {
					$this->id = $this->db->lastInsertId($this->table, 'id');
					return true;
				}
				else {
					return false;
				}
			}
		}
		else {
			return false;
		}

	}

/**
  * deletes a record
  *
  * @param mixed $id
  * @return success
  */
	function remove ($id=null) {
		return $this->del($id);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $id
  * @return unknown
  */
	function del ($id=null) {
		if ($id) $this->id = $id;
		if ($this->id && $this->db->query("DELETE FROM {$this->table} WHERE id = '{$this->id}'")) {
			$this->id = false;
			return true;
		}
		else
			return false;
	}

/**
  * checks for existance of a record with set id
  *
  * @return true if such record exists
  */
	function exists () {
		return $this->id? $this->db->hasAny($this->table, "id = '{$this->id}'"): false;
	}

/**
  * reads a single row 
  *
  * @param string $conditions
  * @param string $fields
  * @return array of fields
  */
	function find ($conditions = null, $fields = null) {
		$data = $this->findAll($conditions, $fields, null, 1);
		return empty($data[0])? false: $data[0];
	}

/**
  * returns specified fields from db records matching conditions
  *
  * @param string $conditions
  * @param string $fields
  * @param string $order
  * @param int $limit
  * @param int $page
  * @return array of records
  */
	function findAll ($conditions = null, $fields = null, $order = null, $limit=50, $page=1) {
		if (is_array($fields))
			$f = $fields;
		elseif ($fields)
			$f = array($fields);
		else
			$f = array('*');

		$condtions = $this->db->prepare($conditions);
			
		$joins = $whers = array();

		foreach ($this->_oneToMany as $rule) {
			list($table, $field, $value) = $rule;
			$joins[] = "LEFT JOIN {$table} ON {$this->table}.{$field} = {$table}.id";
			$whers[] = "{$this->table}.{$field} = '{$value}'";
		}

		$joins = count($joins)? join(' ', $joins): null;
		$whers = count($whers)? '('.join(' AND ', $whers).')': null;
		$conditions .= ($conditions && $whers? ' AND ': null).$whers;

		$offset_str = $page > 1? " OFFSET ".$page*$limit: "";
		$limit_str = $limit? " LIMIT {$limit}": "";

		$data = $this->db->all(
			"SELECT "
			.join(', ', $f)
			." FROM {$this->table} {$joins}"
			.($conditions? " WHERE {$conditions}":null)
			.($order? " ORDER BY {$order}": null)
			.$limit_str
			.$offset_str);

		return $data;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sql
  * @param unknown_type $debug
  * @return unknown
  */
	function findBySql ($sql, $debug=0) {
		return $this->db->all($sql, $debug);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $conditions
  * @param unknown_type $fields
  * @return unknown
  */
	function findAllThreaded ($conditions=null, $fields=null) {
		return $this->_doThread($this->findAll($conditions, $fields), null);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $conditions
  * @return unknown
  */
	function findCount ($conditions) {
		list($data) = $this->findAll($conditions, 'COUNT(id) AS count');
		return $data['count'];
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
  * @param unknown_type $root
  * @return unknown
  */
	function _doThread ($data, $root) {
		$out = array();
		
		for ($ii=0; $ii<sizeof($data); $ii++) {
			if ($data[$ii]['parent_id'] == $root) {
				$tmp = $data[$ii];
				$tmp['children'] = isset($data[$ii]['id'])? $this->_do_thread($data, $data[$ii]['id']): null;
				$out[] = $tmp;
			}
		}
		
		return $out;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $conditions
  * @param unknown_type $field
  * @param unknown_type $value
  * @return unknown
  */
	function findNeighbours ($conditions, $field, $value) {
		list($prev) = $this->findAll($conditions." AND {$field} < '{$value}'", $field, "{$field} DESC", 1);
		list($next) = $this->findAll($conditions." AND {$field} > '{$value}'", $field, "{$field} ASC", 1);
		
		return array('prev'=>$prev['id'], 'next'=>$next['id']);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $sql
  * @return unknown
  */
	function query ($sql) {
		return $this->db->query($sql);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
  * @return unknown
  */
	function validates ($data=null) {
		$errors = count($this->invalidFields($data));
		
		return $errors == 0;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
  * @return unknown
  */
	function invalidFields ($data=null) {
		return $this->_invalidFields($data);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
  * @return unknown
  */
	function _invalidFields ($data=null) {
		if (!isset($this->validate))
			return true;

		$data = ($data? $data: (isset($this->data)? $this->data: array()));
		$errors = array();

		foreach ($this->validate as $field_name=>$validator) {
			if (isset($data[$field_name])) {
				if (!preg_match($validator, $data[$field_name]))
					$errors[$field_name] = 1;
			}
		}

		return $errors;
	}

}

?>