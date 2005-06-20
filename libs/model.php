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
  * Purpose: Model
  * DBO-backed object data model, loosely based on RoR (www.rubyonrails.com).
  * Automatically selects a database table name based on a pluralized lowercase object class name
  * (i.e. class 'User' => table 'users'; class 'Man' => table 'men')
  * The table is required to have at least 'id auto_increment', 'created datetime', 
  * and 'modified datetime' fields
  *
  * To do:
  *   - schema-related cross-table ($has_one, $has_many, $belongs_to)
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
  */
uses('object', 'validators', 'inflector');

/**
  * DBO-backed object data model, loosely based on RoR (www.rubyonrails.com).
  * Automatically selects a database table name based on a pluralized lowercase object class name
  * (i.e. class 'User' => table 'users'; class 'Man' => table 'men')
  * The table is required to have at least 'id auto_increment', 'created datetime', 
  * and 'modified datetime' fields.
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Model extends Object 
{
    
/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $parent = false;

/**
  * Custom database table name
  *
  * @var string
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
  * Container for the data that this model gets from persistent storage (the database).
  *
  * @var array
  * @access public
  */
	var $data = array();

/**
  * Table name for this Model.
  *
  * @var string
  * @access public
  */
	var $table = false;
	// private
/**
  * Table metadata
  *
  * @var array
  * @access private
  */
	var $_table_info = null;

/**
  * Array of other Models this Model references in a one-to-many relationship. 
  *
  * @var array
  * @access private
  */
	var $_oneToMany = array();

/**
  * Array of other Models this Model references in a one-to-one relationship. 
  *
  * @var array
  * @access private
  */
	var $_oneToOne = array();

/**
  * Array of other Models this Model references in a has-many relationship. 
  *
  * @var array
  * @access private
  */
	var $_hasMany = array();

/**
  * Enter description here...
  *
  * append entries for validation as ('field_name' => '/^perl_compat_regexp$/') that has to match with preg_match()
  * validate with Model::validate()
  * @var array
  */
	var $validate = array();

/**
  * Append entries for validation as ('field_name' => '/^perl_compat_regexp$/') that has to match with preg_match()
  * validate with Model::validate()
  * @var array
  */
	var $validationErrors = null;

/**
  * Constructor. Binds the Model's database table to the object.
  *
  * @param unknown_type $id
  * @param string $table Database table to use.
  * @param unknown_type $db Database connection object.
  */
	function __construct ($id=false, $table=null, $db=null) 
	{
		global $DB;

		$this->db = $db? $db: $DB;

		if ($id) 
			$this->id = $id;

		$table_name = $table? $table: ($this->use_table? $this->use_table: Inflector::tableize(get_class($this)));
		$this->useTable ($table_name);
		parent::__construct();
		$this->createLinks();
	}

/**
  * Creates has-many relationships, and then call relink.
  *
  * @see relink()
  */
	function createLinks () 
	{
		if (!empty($this->hasMany))
			$this->_hasMany = explode(',', $this->hasMany);
		
		foreach ($this->_hasMany as $model_name) 
		{
			// todo fix strip the model name
			$model_name = Inflector::singularize($model_name);
			$this->$model_name = new $model_name();
		}

		$this->relink();
	}

/**
  * Updates this model's many-to-one links, by emptying the links list, and then linkManyToOne again.
  *
  * @see linkManyToOne()
  */
	function relink () 
	{
		foreach ($this->_hasMany as $model) 
		{
			$name = Inflector::singularize($model);
			$this->$name->clearLinks();
			$this->$name->linkManyToOne(get_class($this), $this->id);
		}
	}

/**
  * Creates a many-to-one link for given $model_name. 
  * First it gets Inflector to derive a table name and a foreign key field name.
  * Then, these are stored in the Model.
  *
  * @param string $model_name Name of model to link to
  * @param unknown_type $value Defaults to NULL.
  */
	function linkManyToOne ($model_name, $value=null) 
	{
		$table_name = Inflector::tableize($model_name);
		$field_name = Inflector::singularize($table_name).'_id';
		$this->_one_to_many[] = array($table_name, $field_name, $value);
	}

/**
  * Removes all one-to-many links to other Models.
  *
  */
	function clearLinks () 
	{
		$this->_one_to_many = array();
	}

/**
  * Sets a custom table for your controller class. Used by your controller to select a database table.
  *
  * @param string $table_name Name of the custom table
  */
	function useTable ($table_name) 
	{
		if (!in_array(strtolower($table_name), $this->db->tables())) 
		{
			trigger_error (sprintf(ERROR_NO_MODEL_TABLE, get_class($this), $table_name), E_USER_ERROR);
			die();
		}
		else
		{
			$this->table = $table_name;
			$this->loadInfo();
		}
	}


/**
  * This function does two things: 1) it scans the array $one for they key 'id',
  * and if that's found, it sets the current id to the value of $one[id].
  * For all other keys than 'id' the keys and values of $one are copied to the 'data' property of this object.
  * 2) Returns an array with all of $one's keys and values.
  * (Alternative indata: two strings, which are mangled to 
  * a one-item, two-dimensional array using $one for a key and $two as its value.)
  *
  * @param mixed $one Array or string of data
  * @param string $two Value string for the alternative indata method
  * @return unknown
  */
	function set ($one, $two=null) 
	{
		$this->validationErrors = null;
		$data = is_array($one)? $one: array($one=>$two);

		foreach ($data as $n => $v) 
		{
/*
			if (!$this->hasField($n)) {
				DEBUG? 
					trigger_error(sprintf(ERROR_NO_FIELD_IN_MODEL_DB, $n, $this->table), E_USER_ERROR):
					trigger_error('Application error occured, trying to set a field name that doesn\'t exist.', E_USER_WARNING);
			}
*/
			$n == 'id'? $this->setId($v): $this->data[$n] = $v;
		}

		return $data;
    }

/**
  * Sets current Model id to given $id.
  *
  * @param int $id Id
  */
	function setId ($id) 
	{
		$this->id = $id;
		$this->relink();
	}

/**
  * Returns an array of table metadata (column names and types) from the database.
  *
  * @return array Array of table metadata
  */
	function loadInfo () 
	{
		if (empty($this->_table_info))
			$this->_table_info = new Narray($this->db->fields($this->table));
		return $this->_table_info;
	}

/**
  * Returns true if given field name exists in this Model's database table.
  * Starts by loading the metadata into the private property table_info if that is not already set. 
  *
  * @param string $name Name of table to look in
  * @return boolean
  */
	function hasField ($name) 
	{
		if (empty($this->_table_info)) $this->loadInfo();
		return $this->_table_info->findIn('name', $name);
	}

/**
  * Returns a list of fields from the database
  *
  * @param mixed $fields String of single fieldname, or an array of fieldnames.
  * @return array Array of database fields
  */
	function read ($fields=null) 
	{
		$this->validationErrors = null;
		return $this->id? $this->find("id = '{$this->id}'", $fields): false;
	}

/**
  * Returns contents of a field in a query matching given conditions.
  *
  * @param string $name Name of field to get
  * @param string $conditions SQL conditions (defaults to NULL)
  * @return field contents
  */
	function field ($name, $conditions=null) 
	{
		if ($conditions) 
		{
			$conditions = $this->parseConditions($conditions);
			$data = $this->find($conditions);
			return $data[$name];
		}
		elseif (isset($this->data[$name]))
		{
			return $this->data[$name];
		}
		else 
		{
			if ($this->id && $data = $this->read($name)) 
			{
				return isset($data[$name])? $data[$name]: false;
			}
			else 
			{
				return false;
			}
		}
	}

/**
  * Saves a single field to the database.
  *
  * @param string $name Name of the table field
  * @param mixed $value Value of the field
  * @return boolean True on success save
  */
	function saveField($name, $value) 
	{
		return $this->save(array($name=>$value), false);
	}

/**
  * Saves model data to the database.
  *
  * @param array $data Data to save. 
  * @param boolean $validate
  * @return boolean success
  */
	function save ($data=null, $validate=true) 
	{
		if ($data) $this->set($data);

		if ($validate && !$this->validates())
			return false;

		$fields = $values = array();
		foreach ($this->data as $n=>$v) 
		{
			if ($this->hasField($n)) 
			{
				$fields[] = $n;
				$values[] = $this->db->prepare($v);
			}
		}

		if (empty($this->id) && $this->hasField('created') && !in_array('created', $fields)) 
		{
			$fields[] = 'created';
			$values[] = date("'Y-m-d H:i:s'");
		}
		if ($this->hasField('modified') && !in_array('modified', $fields)) 
		{
			$fields[] = 'modified';
			$values[] = 'NOW()';
		}

		if(count($fields))
		{
			if($this->id){
				$sql = array();
				foreach (array_combine($fields, $values) as $field=>$value) 
				{
					$sql[] = $field.'='.$value;
				}
				
				$sql = "UPDATE {$this->table} SET ".join(',', $sql)." WHERE id = '{$this->id}'";
				
				if ($this->db->query($sql) && $this->db->lastAffected())
				{
					$this->data = false;
					return true;
				}
				else 
				{
					return $this->db->hasAny($this->table, "id = '{$this->id}'");
				}
			}
			else 
			{
				$fields = join(',', $fields);
				$values = join(',', $values);

				$sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$values})";

				if($this->db->query($sql)) 
				{
					$this->id = $this->db->lastInsertId($this->table, 'id');
					return true;
				}
				else 
				{
					return false;
				}
			}
		}
		else 
		{
			return false;
		}

	}

/**
  * Synonym for del().
  *
  * @param mixed $id
  * @see function del
  * @return boolean True on success
  */
	function remove ($id=null) 
	{
		return $this->del($id);
	}

/**
  * Removes record for given id. If no id is given, the current id is used. Returns true on success.
  *
  * @param mixed $id Id of database record to delete
  * @return boolean True on success
  */
	function del ($id=null) 
	{
		if ($id) $this->id = $id;
		if ($this->id && $this->db->query("DELETE FROM {$this->table} WHERE id = '{$this->id}'")) 
		{
			$this->id = false;
			return true;
		}
		else
			return false;
	}

/**
  * Returns true if a record with set id exists.
  *
  * @return boolean True if such a record exists
  */
	function exists () 
	{
		return $this->id? $this->db->hasAny($this->table, "id = '{$this->id}'"): false;
	}


/**
  * Returns true if a record that meets given conditions exists
  *
  * @return boolean True if such a record exists
  */
	function hasAny ($sql_conditions = null) 
	{
		return $this->db->hasAny($this->table, $sql_conditions);
	}


/**
  * Return a single row as a resultset array.
  *
  * @param string $conditions SQL conditions
  * @param mixed $fields Either a single string of a field name, or an array of field names
  * @return array Array of records
  */
	function find ($conditions = null, $fields = null) 
	{
		$data = $this->findAll($conditions, $fields, null, 1);
		return empty($data[0])? false: $data[0];
	}

/** parses conditions array (or just passes it if it's a string)
  * @return string
  *
  */
	function parseConditions ($conditions) 
	{
		if (is_string($conditions)) 
		{
			return $conditions;
		}
		elseif (is_array($conditions)) 
		{
			$out = array();
			foreach ($conditions as $key=>$value) 
			{
				$out[] = "{$key}=".($value===null? 'null': $this->db->prepare($value));
			}
			return join(' and ', $out);
		}
		else 
		{
			return null;
		}
	}

/**
  * Returns a resultset array with specified fields from database matching given conditions.
  *
  * @param mixed $conditions SQL conditions as a string or as an array('field'=>'value',...)
  * @param mixed $fields Either a single string of a field name, or an array of field names
  * @param string $order SQL ORDER BY conditions (e.g. "DESC" or "ASC")
  * @param int $limit SQL LIMIT clause, for calculating items per page
  * @param int $page Page number
  * @return array Array of records
  */
	function findAll ($conditions = null, $fields = null, $order = null, $limit=50, $page=1) 
	{
		$conditions = $this->parseConditions($conditions);

		if (is_array($fields))
			$f = $fields;
		elseif ($fields)
			$f = array($fields);
		else
			$f = array('*');

		$joins = $whers = array();

		foreach ($this->_oneToMany as $rule) 
		{
			list($table, $field, $value) = $rule;
			$joins[] = "LEFT JOIN {$table} ON {$this->table}.{$field} = {$table}.id";
			$whers[] = "{$this->table}.{$field} = '{$value}'";
		}

		$joins = count($joins)? join(' ', $joins): null;
		$whers = count($whers)? '('.join(' AND ', $whers).')': null;
		$conditions .= ($conditions && $whers? ' AND ': null).$whers;

		$offset = $page > 1? $page*$limit: 0;

		$limit_str = $limit
			? $this->db->selectLimit($limit, $offset)
			: '';
		
		$sql = 
			"SELECT "
			.join(', ', $f)
			." FROM {$this->table} {$joins}"
			.($conditions? " WHERE {$conditions}":null)
			.($order? " ORDER BY {$order}": null)
			.$limit_str;

		$data = $this->db->all($sql);			

		return $data;
	}

/**
  * Returns an array of all rows for given SQL statement.
  *
  * @param string $sql SQL query
  * @return array
  */
	function findBySql ($sql) 
	{
		return $this->db->all($sql);
	}

/**
  * Returns number of rows matching given SQL condition. 
  *
  * @param string $conditions SQL conditions (WHERE clause conditions)
  * @return int Number of matching rows
  */
	function findCount ($conditions)
	{
		list($data) = $this->findAll($conditions, 'COUNT(id) AS count');
		return $data['count'];
	}

/**
  * Enter description here...
  *
  * @param string $conditions SQL conditions (WHERE clause conditions)
  * @param unknown_type $fields
  * @return unknown
  */
	function findAllThreaded ($conditions=null, $fields=null, $sort=null) 
	{
		return $this->_doThread($this->findAll($conditions, $fields, $sort), null);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data 
  * @param unknown_type $root NULL or id for root node of operation
  * @return array
  */
	function _doThread ($data, $root) 
	{
		$out = array();
		
		for ($ii=0; $ii<sizeof($data); $ii++) 
		{
			if ($data[$ii]['parent_id'] == $root) 
			{
				$tmp = $data[$ii];
				$tmp['children'] = isset($data[$ii]['id'])? $this->_doThread($data, $data[$ii]['id']): null;
				$out[] = $tmp;
			}
		}
		
		return $out;
	}

/**
  * Returns an array with keys "prev" and "next" that holds the id's of neighbouring data,
  * which is useful when creating paged lists.
  *
  * @param string $conditions SQL conditions for matching rows
  * @param unknown_type $field
  * @param unknown_type $value
  * @return array Array with keys "prev" and "next" that holds the id's
  */
	function findNeighbours ($conditions, $field, $value) 
	{
		list($prev) = $this->findAll($conditions." AND {$field} < '{$value}'", $field, "{$field} DESC", 1);
		list($next) = $this->findAll($conditions." AND {$field} > '{$value}'", $field, "{$field} ASC", 1);
		
		return array('prev'=>$prev['id'], 'next'=>$next['id']);
	}

/**
  * Returns a resultset for given SQL statement.
  *
  * @param string $sql SQL statement
  * @return array Resultset
  */
	function query ($sql) 
	{
		return $this->db->query($sql);
	}

/**
  * Returns true if all fields pass validation.
  *
  * @param array $data POST data
  * @return boolean True if there are no errors
  */
	function validates ($data=null)
	{
		$errors = count($this->invalidFields($data? $data: $this->data));
		
		return $errors == 0;
	}

/**
  * Returns an array of invalid fields.
  *
  * @param array $data Posted data
  * @return array Array of invalid fields
  */
	function invalidFields ($data=null) 
	{
		return $this->_invalidFields($data);
	}

/**
  * Returns an array of invalid fields.
  *
  * @param array $data 
  * @return array Array of invalid fields
  */
	function _invalidFields ($data=null) 
	{
		if (!isset($this->validate))
			return true;

		if (is_array($this->validationErrors))
			return $this->validationErrors;

		$data = ($data? $data: (isset($this->data)? $this->data: array()));
		$errors = array();

		foreach ($this->validate as $field_name=>$validator) 
		{
			if (!isset($data[$field_name]) || !preg_match($validator, $data[$field_name]))
					$errors[$field_name] = 1;
		}

		$this->validationErrors = $errors;
		return $errors;
	}

}

?>