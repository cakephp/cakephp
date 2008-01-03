<?php
/* SVN FILE: $Id$ */
/**
 * Object-relational mapper.
 *
 * DBO-backed object data model, for mapping database tables to Cake objects.
 *
 * PHP versions 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.model
 * @since			CakePHP(tm) v 0.10.0.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libs
 */
App::import('Core', array('ClassRegistry', 'Overloadable', 'Validation', 'Behavior', 'ConnectionManager', 'Set'));
/**
 * Object-relational mapper.
 *
 * DBO-backed object data model.
 * Automatically selects a database table name based on a pluralized lowercase object class name
 * (i.e. class 'User' => table 'users'; class 'Man' => table 'men')
 * The table is required to have at least 'id auto_increment', 'created datetime',
 * and 'modified datetime' fields.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model
 */
class Model extends Overloadable {
/**
 * The name of the DataSource connection that this Model uses
 *
 * @var string
 * @access public
 */
	var $useDbConfig = 'default';
/**
 * Custom database table name.
 *
 * @var string
 * @access public
 */
	var $useTable = null;
/**
 * Custom display field name. Display fields are used by Scaffold, in SELECT boxes' OPTION elements.
 *
 * @var string
 * @access public
 */
	var $displayField = null;
/**
 * Value of the primary key ID of the record that this model is currently pointing to
 *
 * @var mixed
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
/**
 * The name of the ID field for this Model.
 *
 * @var string
 * @access public
 */
	var $primaryKey = null;
/**
 * Table metadata
 *
 * @var array
 * @access protected
 */
	var $_schema = null;
/**
 * List of validation rules. Append entries for validation as ('field_name' => '/^perl_compat_regexp$/')
 * that have to match with preg_match(). Use these rules with Model::validate()
 *
 * @var array
 * @access public
 */
	var $validate = array();
/**
 * Errors in validation
 *
 * @var array
 * @access public
 */
	var $validationErrors = array();
/**
 * Database table prefix for tables in model.
 *
 * @var string
 * @access public
 */
	var $tablePrefix = null;
/**
 * Name of the model.
 *
 * @var string
 * @access public
 */
	var $name = null;
/**
 * Alias name for model.
 *
 * @var string
 * @access public
 */
	var $alias = null;
/**
 * List of table names included in the Model description. Used for associations.
 *
 * @var array
 * @access public
 */
	var $tableToModel = array();
/**
 * Whether or not transactions for this model should be logged
 *
 * @var boolean
 * @access public
 */
	var $logTransactions = false;
/**
 * Whether or not to enable transactions for this model (i.e. BEGIN/COMMIT/ROLLBACK)
 *
 * @var boolean
 * @access public
 */
	var $transactional = false;
/**
 * Whether or not to cache queries for this model.  This enables in-memory
 * caching only, the results are not stored beyond this execution.
 *
 * @var boolean
 * @access public
 */
	var $cacheQueries = false;
/**
 * belongsTo association
 *
 * @var array
 * @access public
 */
	var $belongsTo = array();
/**
 * hasOne association
 *
 * @var array
 * @access public
 */
	var $hasOne = array();
/**
 * hasMany association
 *
 * @var array
 * @access public
 */
	var $hasMany = array();
/**
 * hasAndBelongsToMany association
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array();
/**
 * List of behaviors to use. Settings can be passed to behaviors
 * by using the behavior name as index. Eg:
 *
 * array('Translate', 'MyBehavior' => array('setting1' => 'value1'))
 *
 * @var array
 * @access public
 */
	var $actsAs = null;
/**
 * Behavior objects
 *
 * @var array
 * @access public
 */
	var $behaviors = array();
/**
 * Whitelist of fields allowed to be saved
 *
 * @var array
 * @access public
 */
	var $whitelist = array();
/**
 * Should sources for this model be cached.
 *
 * @var boolean
 * @access public
 */
	var $cacheSources = true;
/**
 * Type of find query currently executing
 *
 * @var string
 * @access public
 */
	var $findQueryType = null;
/**
 * Mapped behavior methods
 *
 * @var array
 * @access private
 */
	var $__behaviorMethods = array();
/**
 * Depth of recursive association
 *
 * @var integer
 * @access public
 */
	var $recursive = 1;
/**
 * Default ordering of model records
 *
 * @var string
 * @access public
 */
	var $order = null;
/**
 * Whether or not the model record exists, set by Model::exists()
 *
 * @var bool
 * @access private
 */
	var $__exists = null;
/**
 * Default association keys
 *
 * @var array
 * @access private
 */
	var $__associationKeys = array(
			'belongsTo' => array('className', 'foreignKey', 'conditions', 'fields', 'order', 'counterCache'),
			'hasOne' => array('className', 'foreignKey','conditions', 'fields','order', 'dependent'),
			'hasMany' => array('className', 'foreignKey', 'conditions', 'fields', 'order', 'limit', 'offset', 'dependent', 'exclusive', 'finderQuery', 'counterQuery'),
			'hasAndBelongsToMany' => array('className', 'joinTable', 'with', 'foreignKey', 'associationForeignKey', 'conditions', 'fields', 'order', 'limit', 'offset', 'unique', 'finderQuery', 'deleteQuery', 'insertQuery'));
/**
 * Holds provided/generated association key names and other data for all associations
 *
 * @var array
 * @access private
 */
	var $__associations = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');
/**
 * Holds model associations temporarily to allow for dynamic (un)binding
 *
 * @var array
 * @access private
 */
	var $__backAssociation = array();
/**
 * The last inserted ID of the data that this model created
 *
 * @var integer
 * @access private
 */
	var $__insertID = null;
/**
 * The number of records returned by the last query
 *
 * @var integer
 * @access private
 */
	var $__numRows = null;
/**
 * The number of records affected by the last query
 *
 * @var integer
 * @access private
 */
	var $__affectedRows = null;
/**
 * List of valid finder method options
 *
 * @var array
 * @access private
 */
	var $__findMethods = array('all' => true, 'first' => true, 'count' => true, 'neighbors' => true, 'list' => true);
/**
 * Constructor. Binds the Model's database table to the object.
 *
 * @param integer $id Set this ID for this model on startup
 * @param string $table Name of database table to use.
 * @param object $ds DataSource connection object.
 */
	function __construct($id = false, $table = null, $ds = null) {
		parent::__construct();

		if (is_array($id) && isset($id['name'])) {
			$options = array_merge(array('id' => false, 'table' => null, 'ds' => null, 'alias' => null), $id);
			list($id, $table, $ds) = array($options['id'], $options['table'], $options['ds']);
			$this->name = $options['name'];
		}

		if ($this->name === null) {
			$this->name = get_class($this);
		}

		if ($this->primaryKey === null) {
			$this->primaryKey = 'id';
		}

		if (isset($options['alias']) || !empty($options['alias'])) {
			$this->alias = $options['alias'];
			unset($options);
		} else {
			$this->alias = $this->name;
		}
		ClassRegistry::addObject($this->alias, $this);

		$this->id = $id;
		unset($id);

		if ($table === false) {
			$this->useTable = false;
		} elseif ($table) {
			$this->useTable = $table;
		}

		if ($this->useTable !== false) {
			$this->setDataSource($ds);

			if ($this->useTable === null) {
				$this->useTable = Inflector::tableize($this->name);
			}

			if (in_array('settableprefix', get_class_methods($this))) {
				$this->setTablePrefix();
			}

			$this->setSource($this->useTable);
			$this->__createLinks();

			if ($this->displayField == null) {
				if ($this->hasField('title')) {
					$this->displayField = 'title';
				}

				if ($this->hasField('name')) {
					$this->displayField = 'name';
				}

				if ($this->displayField == null) {
					$this->displayField = $this->primaryKey;
				}
			}
		}

		if (is_subclass_of($this, 'AppModel')) {
			$appVars = get_class_vars('AppModel');
			$merge = array();

			if ($this->actsAs !== null || $this->actsAs !== false) {
				$merge[] = 'actsAs';
			}

			foreach ($merge as $var) {
				if (isset($appVars[$var]) && !empty($appVars[$var]) && is_array($this->{$var})) {
					$this->{$var} = Set::merge($appVars[$var], $this->{$var});
				}
			}
		}

		if ($this->actsAs !== null && empty($this->behaviors)) {
			$callbacks = array('setup', 'beforeFind', 'afterFind', 'beforeSave', 'afterSave', 'beforeDelete', 'afterDelete', 'afterError');
			$this->actsAs = Set::normalize($this->actsAs);

			foreach ($this->actsAs as $behavior => $config) {
				$className = $behavior . 'Behavior';

				if (!App::import('Behavior', $behavior)) {
					// Raise an error
				} else {
					if (ClassRegistry::isKeySet($className)) {
						if (PHP5) {
							$this->behaviors[$behavior] = ClassRegistry::getObject($className);
						} else {
							$this->behaviors[$behavior] =& ClassRegistry::getObject($className);
						}
					} else {
						if (PHP5) {
							$this->behaviors[$behavior] = new $className;
						} else {
							$this->behaviors[$behavior] =& new $className;
						}
						ClassRegistry::addObject($className, $this->behaviors[$behavior]);
					}
					$this->behaviors[$behavior]->setup($this, $config);

					$methods = $this->behaviors[$behavior]->mapMethods;
					foreach ($methods as $method => $alias) {
						if (!array_key_exists($method, $this->__behaviorMethods)) {
							$this->__behaviorMethods[$method] = array($alias, $behavior);
						}
					}

					$methods = get_class_methods($this->behaviors[$behavior]);
					$parentMethods = get_class_methods('ModelBehavior');

					foreach ($methods as $m) {
						if (!in_array($m, $parentMethods)) {
							if (strpos($m, '_') !== 0 && !array_key_exists($m, $this->__behaviorMethods) && !in_array($m, $callbacks)) {
								$this->__behaviorMethods[$m] = array($m, $behavior);
							}
						}
					}
				}
			}
		}
	}
/**
 * Handles custom method calls, like findBy<field> for DB models,
 * and custom RPC calls for remote data sources.
 *
 * @param string $method Name of method to call.
 * @param array $params Parameters for the method.
 * @return mixed Whatever is returned by called method
 * @access protected
 */
	function call__($method, $params) {
		$methods = array_map('strtolower', array_keys($this->__behaviorMethods));
		$call = array_values($this->__behaviorMethods);
		$map = array();

		if (!empty($methods) && !empty($call)) {
			$map = array_combine($methods, $call);
		}
		$count = count($call);
		$pass = array(&$this);

		if (!in_array(strtolower($method), $methods)) {
			$pass[] = $method;
		}
		foreach ($params as $param) {
			$pass[] = $param;
		}

		if (in_array(strtolower($method), $methods)) {
			$it = $map[strtolower($method)];
			return call_user_func_array(array(&$this->behaviors[$it[1]], $it[0]), $pass);
		}

		for ($i = 0; $i < $count; $i++) {
			if (strpos($methods[$i], '/') === 0 && preg_match($methods[$i] . 'i', $method)) {
				return call_user_func_array(array($this->behaviors[$call[$i][1]], $call[$i][0]), $pass);
			}
		}

		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$return = $db->query($method, $params, $this);

		if (!PHP5) {
			$this->__resetAssociations();
		}
		return $return;
	}
/**
 * Bind model associations on the fly.
 *
 * If $permanent is true, association will not be reset
 * to the originals defined in the model.
 *
 * @param mixed $model A model or association name (string) or set of binding options (indexed by model name type)
 * @param array $options If $model is a string, this is the list of association properties with which $model will
 * 						 be bound
 * @param boolean $permanent Set to true to make the binding permanent
 * @access public
 * @todo
 */
	function bind($model, $options = array(), $permanent = true) {
		if (!is_array($model)) {
			$model = array($model => $options);
		}

		foreach ($model as $name => $options) {
			if (isset($options['type'])) {
				$assoc = $options['type'];
			} elseif (isset($options[0])) {
				$assoc = $options[0];
			} else {
				$assoc = 'belongsTo';
			}

			if (!$permanent) {
				$this->__backAssociation[$assoc] = $this->{$assoc};
			}
			foreach ($model as $key => $value) {
				$assocName = $modelName = $key;

				if (isset($this->{$assoc}[$assocName])) {
					$this->{$assoc}[$assocName] = array_merge($this->{$assoc}[$assocName], $options);
				} else {
					if (isset($value['className'])) {
						$modelName = $value['className'];
					}

					$this->__constructLinkedModel($assocName, $modelName);
					$this->{$assoc}[$assocName] = $model[$assocName];
					$this->__generateAssociation($assoc);
				}
				unset($this->{$assoc}[$assocName]['type'], $this->{$assoc}[$assocName][0]);
			}
		}
	}
/**
 * Bind model associations on the fly.
 *
 * If $reset is false, association will not be reset
 * to the originals defined in the model
 *
 * Example: Add a new hasOne binding to the Profile model not
 * defined in the model source code:
 * <code>
 * $this->User->bindModel( array('hasOne' => array('Profile')) );
 * </code>
 *
 * @param array $params Set of bindings (indexed by binding type)
 * @param boolean $reset Set to false to make the binding permanent
 * @return boolean Success
 * @access public
 */
	function bindModel($params, $reset = true) {
		foreach ($params as $assoc => $model) {
			if ($reset === true) {
				$this->__backAssociation[$assoc] = $this->{$assoc};
			}

			foreach ($model as $key => $value) {
				$assocName = $key;

				if (is_numeric($key)) {
					$assocName = $value;
					$value = array();
				}
				$modelName = $assocName;
				$this->{$assoc}[$assocName] = $value;
			}
		}
		$this->__createLinks();
		return true;
	}
/**
 * Turn off associations on the fly.
 *
 * If $reset is false, association will not be reset
 * to the originals defined in the model
 *
 * Example: Turn off the associated Model Support request,
 * to temporarily lighten the User model:
 * <code>
 * $this->User->unbindModel( array('hasMany' => array('Supportrequest')) );
 * </code>
 *
 * @param array $params Set of bindings to unbind (indexed by binding type)
 * @param boolean $reset  Set to false to make the unbinding permanent
 * @return boolean Success
 * @access public
 */
	function unbindModel($params, $reset = true) {
		foreach ($params as $assoc => $models) {
			if ($reset === true) {
				$this->__backAssociation[$assoc] = $this->{$assoc};
			}

			foreach ($models as $model) {
				$this->__backAssociation = array_merge($this->__backAssociation, $this->{$assoc});
				unset ($this->__backAssociation[$model]);
				unset ($this->{$assoc}[$model]);
			}
		}
		return true;
	}
/**
 * Create a set of associations
 *
 * @access private
 */
	function __createLinks() {
		foreach ($this->__associations as $type) {
			if (!is_array($this->{$type})) {
				$this->{$type} = explode(',', $this->{$type});

				foreach ($this->{$type} as $i => $className) {
					$className = trim($className);
					unset ($this->{$type}[$i]);
					$this->{$type}[$className] = array();
				}
			}

			foreach ($this->{$type} as $assoc => $value) {
				$plugin = null;
				if (is_numeric($assoc)) {
					unset ($this->{$type}[$assoc]);
					$assoc = $value;
					$value = array();
					$this->{$type}[$assoc] = $value;

					if (strpos($assoc, '.') !== false) {
						$value = $this->{$type}[$assoc];
						unset($this->{$type}[$assoc]);
						list($plugin, $assoc) = explode('.', $assoc);
						$this->{$type}[$assoc] = $value;
						$plugin = $plugin . '.';
					}
				}
				$className = $plugin . $assoc;

				if (isset($value['className']) && !empty($value['className'])) {
					$className = $value['className'];
					if (strpos($className, '.') !== false) {
						list($plugin, $className) = explode('.', $className);
						$plugin = $plugin . '.';
						$this->{$type}[$assoc]['className'] = $className;
					}
				}
				$this->__constructLinkedModel($assoc, $plugin . $className);
			}
			$this->__generateAssociation($type);
		}
	}
/**
 * Private helper method to create associated models of given class.
 *
 * @param string $assoc Association name
 * @param string $className Class name
 * @deprecated $this->$className use $this->$assoc instead. $assoc is the 'key' in the associations array;
 * 	examples: var $hasMany = array('Assoc' => array('className' => 'ModelName'));
 * 					usage: $this->Assoc->modelMethods();
 *
 * 				var $hasMany = array('ModelName');
 * 					usage: $this->ModelName->modelMethods();
 * @access private
 */
	function __constructLinkedModel($assoc, $className = null) {
		if(empty($className)) {
			$className = $assoc;
		}
		$model = array('class' => $className, 'alias' => $assoc);

		if (PHP5) {
			$this->{$assoc} = ClassRegistry::init($model);
		} else {
			$this->{$assoc} =& ClassRegistry::init($model);
		}
		if ($assoc) {
			$this->tableToModel[$this->{$assoc}->table] = $assoc;
		}
	}
/**
 * Build array-based association from string.
 *
 * @param string $type 'belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany'
 * @access private
 */
	function __generateAssociation($type) {
		foreach ($this->{$type} as $assocKey => $assocData) {
			$class = $assocKey;

			foreach ($this->__associationKeys[$type] as $key) {
				if (!isset($this->{$type}[$assocKey][$key]) || $this->{$type}[$assocKey][$key] == null) {
					$data = '';

					switch($key) {
						case 'fields':
							$data = '';
						break;

						case 'foreignKey':
							$data = ife($type == 'belongsTo', Inflector::underscore($assocKey) . '_id', Inflector::singularize($this->table) . '_id');
						break;

						case 'associationForeignKey':
							$data = Inflector::singularize($this->{$class}->table) . '_id';
						break;

						case 'with':
							$data = Inflector::camelize(Inflector::singularize($this->{$type}[$assocKey]['joinTable']));
						break;

						case 'joinTable':
							$tables = array($this->table, $this->{$class}->table);
							sort ($tables);
							$data = $tables[0] . '_' . $tables[1];
						break;

						case 'className':
							$data = $class;
						break;

						case 'unique':
							$data = true;
						break;
					}
					$this->{$type}[$assocKey][$key] = $data;
				}
			}

			if (isset($this->{$type}[$assocKey]['with']) && !empty($this->{$type}[$assocKey]['with'])) {
				$joinClass = $this->{$type}[$assocKey]['with'];
				if (is_array($joinClass)) {
					$joinClass = key($joinClass);
				}
				$plugin = null;

				if (strpos($joinClass, '.') !== false) {
					list($plugin, $joinClass) = explode('.', $joinClass);
					$plugin = $plugin . '.';
					$this->{$type}[$assocKey]['with'] = $joinClass;
				}

				if (!App::import('Model', $plugin . $joinClass)) {
					$this->{$joinClass} = new AppModel(array(
						'name' => $joinClass,
						'table' => $this->{$type}[$assocKey]['joinTable'],
						'ds' => $this->useDbConfig
					));
					$this->{$joinClass}->primaryKey = $this->{$type}[$assocKey]['foreignKey'];

				} else {
					$this->__constructLinkedModel($plugin . $joinClass);
					$this->{$joinClass}->primaryKey = $this->{$type}[$assocKey]['foreignKey'];
					$this->{$type}[$assocKey]['joinTable'] = $this->{$joinClass}->table;
				}

				if (count($this->{$joinClass}->_schema) > 2) {
					if (isset($this->{$joinClass}->_schema['id'])) {
						$this->{$joinClass}->primaryKey = 'id';
					}
				}
			}
		}
	}
/**
 * Sets a custom table for your controller class. Used by your controller to select a database table.
 *
 * @param string $tableName Name of the custom table
 * @access public
 */
	function setSource($tableName) {
		$this->setDataSource($this->useDbConfig);
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$db->cacheSources = $this->cacheSources;

		if ($db->isInterfaceSupported('listSources')) {
			$sources = $db->listSources();
			if (is_array($sources) && !in_array(strtolower($this->tablePrefix . $tableName), array_map('strtolower', $sources))) {
				return $this->cakeError('missingTable', array(array(
					'className' => $this->alias,
					'table' => $this->tablePrefix . $tableName
				)));

			}
			$this->_schema = null;
		}
		$this->table = $this->useTable = $tableName;
		$this->tableToModel[$this->table] = $this->alias;
		$this->schema();
	}
/**
 * This function does two things: 1) it scans the array $one for the primary key,
 * and if that's found, it sets the current id to the value of $one[id].
 * For all other keys than 'id' the keys and values of $one are copied to the 'data' property of this object.
 * 2) Returns an array with all of $one's keys and values.
 * (Alternative indata: two strings, which are mangled to
 * a one-item, two-dimensional array using $one for a key and $two as its value.)
 *
 * @param mixed $one Array or string of data
 * @param string $two Value string for the alternative indata method
 * @return array Data with all of $one's keys and values
 * @access public
 */
	function set($one, $two = null) {
		if (!$one) {
			return;
		}
		if (is_object($one)) {
			$one = Set::reverse($one);
		}

		if (is_array($one)) {
			if (Set::countDim($one) == 1) {
				$data = array($this->alias => $one);
			} else {
				$data = $one;
			}
		} else {
			$data = array($this->alias => array($one => $two));
		}

		foreach ($data as $n => $v) {
			if (is_array($v)) {

				foreach ($v as $x => $y) {
					if (isset($this->validationErrors[$x])) {
						unset ($this->validationErrors[$x]);
					}

					if ($n === $this->alias) {
						if ($x === $this->primaryKey) {
							$this->id = $y;
						}
					}
					if (is_array($y) || is_object($y)) {
						$y = $this->deconstruct($x, $y);
					}
					$this->data[$n][$x] = $y;
				}
			}
		}
		return $data;
	}
/**
 * Deconstructs a complex data type (array or object) into a single field value
 *
 * @param string $field The name of the field to be deconstructed
 * @param mixed $data An array or object to be deconstructed into a field
 * @return mixed The resulting data that should be assigned to a field
 * @access public
 */
	function deconstruct($field, $data) {
		$copy = $data;
		$type = $this->getColumnType($field);
		$db =& ConnectionManager::getDataSource($this->useDbConfig);

		if (in_array($type, array('datetime', 'timestamp', 'date', 'time'))) {
			$useNewDate = (isset($data['year']) || isset($data['month']) || isset($data['day']) || isset($data['hour']) || isset($data['minute']));
			$dateFields = array('Y' => 'year', 'm' => 'month', 'd' => 'day', 'H' => 'hour', 'i' => 'min', 's' => 'sec');
			$format = $db->columns[$type]['format'];
			$date = array();

			if (isset($data['hour']) && isset($data['meridian']) && $data['hour'] != 12 && 'pm' == $data['meridian']) {
				$data['hour'] = $data['hour'] + 12;
			}
			if (isset($data['hour']) && isset($data['meridian']) && $data['hour'] == 12 && 'am' == $data['meridian']) {
				$data['hour'] = '00';
			}

			foreach ($dateFields as $key => $val) {
				if (in_array($val, array('hour', 'min', 'sec'))) {
					if (!isset($data[$val]) || $data[$val] === '0' || empty($data[$val])) {
						$data[$val] = '00';
					} else {
						$data[$val] = sprintf('%02d', $data[$val]);
					}
				}
				if (in_array($type, array('datetime', 'timestamp', 'date')) && !isset($data[$val]) || isset($data[$val]) && (empty($data[$val]) || $data[$val][0] === '-')) {
					return null;
				} elseif (isset($data[$val]) && !empty($data[$val])) {
					$date[$key] = $data[$val];
				}
			}
			$date = str_replace(array_keys($date), array_values($date), $format);

			if ($useNewDate && (!empty($date))) {
				return $date;
			}
		}
		return $data;
	}
/**
 * Returns an array of table metadata (column names and types) from the database.
 * $field => keys(type, null, default, key, length, extra)
 *
 * @param boolean $clear Set to true to reload schema
 * @return array Array of table metadata
 * @access public
 */
	function schema($clear = false) {
		if (!is_array($this->_schema) || $clear) {
			$db =& ConnectionManager::getDataSource($this->useDbConfig);
			$db->cacheSources = $this->cacheSources;
			if ($db->isInterfaceSupported('describe') && $this->useTable !== false) {
				$this->_schema = $db->describe($this, $clear);
			} elseif ($this->useTable === false) {
				$this->_schema = array();
			}
		}
		return $this->_schema;
	}
/**
 * See Model::schema
 *
 * @deprecated
 * @see Model::schema()
 */
	function loadInfo($clear = false) {
		$info = $this->schema($clear);
		if (is_array($info)) {
			$fields = array();
			foreach($info as $field => $value) {
				$fields[] = array_merge(array('name'=> $field), $value);
			}
			unset($info);
			return new Set($fields);
		}
	}
/**
 * Returns an associative array of field names and column types.
 *
 * @return array Field types indexed by field name
 * @access public
 */
	function getColumnTypes() {
		$columns = $this->schema();
		if (empty($columns)) {
		    trigger_error(__('(Model::getColumnTypes) Unable to build model field data. If you are using a model without a database table, try implementing schema()', true), E_USER_WARNING);
		}
		$cols = array();
		foreach ($columns as $field => $values) {
			$cols[$field] = $values['type'];
		}
		return $cols;
	}
/**
 * Returns the column type of a column in the model
 *
 * @param string $column The name of the model column
 * @return string Column type
 * @access public
 */
	function getColumnType($column) {
		$cols = $this->schema();
		if (empty($cols)) {
		    trigger_error(__('(Model::getColumnType) Unable to locate model field data. If you are using a model without a database table, try implementing schema()', true), E_USER_WARNING);
		}
		if (isset($cols[$column]['type'])) {
			return $cols[$column]['type'];
		}
		return null;
	}
/**
 * Returns true if this Model has given field in its database table.
 *
 * @param string $name Name of field to look for
 * @return bool Success
 * @access public
 */
	function hasField($name) {
		if (is_array($name)) {
			foreach ($name as $n) {
				if ($this->hasField($n)) {
					return $n;
				}
			}
			return false;
		}

		if (empty($this->_schema)) {
			$this->schema();
		}

		if ($this->_schema != null) {
			return isset($this->_schema[$name]);
		}
		return false;
	}
/**
 * Initializes the model for writing a new record, loading the default values
 * for those fields that are not defined in $data.
 *
 * @param array $data Optional data to assign to the model after it is created
 * @return array The current data of the model
 * @access public
 */
	function create($data = array()) {
		$this->id = false;
		$this->data = array();
		$defaults = array();
		$fields = $this->schema();
		foreach ($fields as $field => $properties) {
			if ($this->primaryKey !== $field && isset($properties['default'])) {
				$defaults[$field] = $properties['default'];
			}
		}
		$this->validationErrors = array();

		if ($data !== null && $data !== false) {
			$this->set(Set::filter($defaults));
			$this->set($data);
		}
		return $this->data;
	}
/**
 * Returns a list of fields from the database, and sets the current model
 * data (Model::$data) with the record found.
 *
 * @param mixed $fields String of single fieldname, or an array of fieldnames.
 * @param mixed $id The ID of the record to read
 * @return array Array of database fields, or false if not found
 * @access public
 */
	function read($fields = null, $id = null) {
		$this->validationErrors = array();

		if ($id != null) {
			$this->id = $id;
		}

		$id = $this->id;

		if (is_array($this->id)) {
			$id = $this->id[0];
		}

		if ($id !== null && $id !== false) {
			$this->data = $this->find(array($this->alias . '.' . $this->primaryKey => $id), $fields);
			return $this->data;
		} else {
			return false;
		}
	}
/**
 * Returns contents of a field in a query matching given conditions.
 *
 * @param string $name Name of field to get
 * @param array $conditions SQL conditions (defaults to NULL)
 * @param string $order SQL ORDER BY fragment
 * @return string field contents, or false if not found
 * @access public
 */
	function field($name, $conditions = null, $order = null) {
		if ($conditions === null && $this->id !== false) {
			$conditions = array($this->alias . '.' . $this->primaryKey => $this->id);
		}
		if ($this->recursive >= 1) {
			$recursive = -1;
		} else {
			$recursive = $this->recursive;
		}
		if ($data = $this->find($conditions, $name, $order, $recursive)) {
			if (strpos($name, '.') === false) {
				if (isset($data[$this->alias][$name])) {
					return $data[$this->alias][$name];
				}
			} else {
				$name = explode('.', $name);
				if (isset($data[$name[0]][$name[1]])) {
					return $data[$name[0]][$name[1]];
				}
			}
			if (isset($data[0]) && count($data[0]) > 0) {
				$name = key($data[0]);
				return $data[0][$name];
			}
		} else {
			return false;
		}
	}
/**
 * Saves a single field to the database.
 *
 * @param string $name Name of the table field
 * @param mixed $value Value of the field
 * @param boolean $validate Whether or not this model should validate before saving (defaults to false)
 * @return boolean True on success save
 * @access public
 * @see Model::save()
 */
	function saveField($name, $value, $validate = false) {
		return $this->save(array($this->alias => array($name => $value)), $validate, array($name));
	}
/**
 * Saves model data to the database. By default, validation occurs before save.
 *
 * @param array $data Data to save.
 * @param boolean $validate If set, validation will be done before the save
 * @param array $fieldList List of fields to allow to be written
 * @return mixed On success Model::$data if its not empty or true, false on failure
 * @access public
 */
	function save($data = null, $validate = true, $fieldList = array()) {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$_whitelist = $this->whitelist;
		$fields = array();

		if (!empty($fieldList)) {
			$this->whitelist = $fieldList;
		} elseif ($fieldList === null) {
			$this->whitelist = array();
		}
		$this->set($data);

		if (empty($this->data) && !$this->hasField(array('created', 'updated', 'modified'))) {
			return false;
		}

		foreach (array('created', 'updated', 'modified') as $field) {
			if (isset($this->data[$this->alias]) && array_key_exists($field, $this->data[$this->alias]) && $this->data[$this->alias][$field] === null) {
				unset($this->data[$this->alias][$field]);
			}
		}
		$exists = $this->exists();

		$dateFields = array('modified', 'updated');
		if (!$exists) {
			$dateFields[] = 'created';
		}

		if (isset($this->data[$this->alias])) {
			$fields = array_keys($this->data[$this->alias]);
		}

		if ($validate && !$this->validates()) {
			$this->whitelist = $_whitelist;
			return false;
		}

		foreach ($dateFields as $updateCol) {
			if ($this->hasField($updateCol) && !in_array($updateCol, $fields)) {
				$colType = array_merge(array('formatter' => 'date'), $db->columns[$this->getColumnType($updateCol)]);
				if (!array_key_exists('formatter', $colType) || !array_key_exists('format', $colType)) {
					$time = strtotime('now');
				} else {
					$time = $colType['formatter']($colType['format']);
				}
				if (!empty($this->whitelist)) {
					$this->whitelist[] = $updateCol;
				}
				$this->set($updateCol, $time);
			}
		}

		if (!empty($this->behaviors)) {
			$behaviors = array_keys($this->behaviors);
			$ct = count($behaviors);
			for ($i = 0; $i < $ct; $i++) {
				if ($this->behaviors[$behaviors[$i]]->beforeSave($this) === false) {
					$this->whitelist = $_whitelist;
					return false;
				}
			}
		}

		if (!$this->beforeSave()) {
			$this->whitelist = $_whitelist;
			return false;
		}
		$fields = $values = array();

		foreach ($this->data as $n => $v) {
			if (isset($this->hasAndBelongsToMany[$n])) {
				if (isset($v[$n])) {
					$v = $v[$n];
				}
				$joined[$n] = $v;
			} else {
				if ($n === $this->alias) {
					foreach (array('created', 'updated', 'modified') as $field) {
						if (array_key_exists($field, $v) && empty($v[$field])) {
							unset($v[$field]);
						}
					}

					foreach ($v as $x => $y) {
						if ($this->hasField($x) && (empty($this->whitelist) || in_array($x, $this->whitelist))) {
							list($fields[], $values[]) = array($x, $y);
						}
					}
				}
			}
		}
		$count = count($fields);

		if (!$exists && $count > 0) {
			$this->id = false;
		}
		$success = true;
		$created = false;

		if ($count > 0) {
			if (!empty($this->id)) {
				if (!$db->update($this, $fields, $values)) {
					$success = false;
				}
			} else {
				foreach ($this->_schema as $field => $properties) {
					if ($this->primaryKey === $field) {
						if (empty($this->data[$this->alias][$this->primaryKey]) && $this->_schema[$field]['type'] === 'string' && $this->_schema[$field]['length'] === 36) {
							list($fields[], $values[]) = array($this->primaryKey, String::uuid());
						}
						break;
					}
				}

				if (!$db->create($this, $fields, $values)) {
					$success = $created = false;
				} else {
					$created = true;
					if (!empty($this->belongsTo)) {
						$this->updateCounterCache();
					}
				}
			}
		}

		if (!empty($joined) && $success === true) {
			$this->__saveMulti($joined, $this->id);
		}

		if ($success && $count > 0) {
			if (!empty($this->data)) {
				$success = $this->data;
			}
			if (!empty($this->behaviors)) {
				$behaviors = array_keys($this->behaviors);
				$ct = count($behaviors);
				for ($i = 0; $i < $ct; $i++) {
					$this->behaviors[$behaviors[$i]]->afterSave($this, $created);
				}
			}
			$this->afterSave($created);
			if (!empty($this->data)) {
				$success = Set::pushDiff($success, $this->data);
			}
			$this->data = false;
			$this->__exists = null;
			$this->_clearCache();
			$this->validationErrors = array();
		}
		$this->whitelist = $_whitelist;
		return $success;
	}
/**
 * Saves model hasAndBelongsToMany data to the database.
 *
 * @param array $joined Data to save
 * @param mixed $id ID of record in this model
 * @access private
 */
	function __saveMulti($joined, $id) {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);

		foreach ($joined as $assoc => $value) {
			$newValues = array();
			if (isset($this->hasAndBelongsToMany[$assoc])) {
				list($join) = $this->joinModel($this->hasAndBelongsToMany[$assoc]['with']);
				$conditions = array($this->hasAndBelongsToMany[$assoc]['foreignKey'] => $id);
				$links = array();

				if ($this->hasAndBelongsToMany[$assoc]['unique']) {
					$this->{$join}->deleteAll($conditions);
				} else {
					list($recursive, $fields) = array(-1, $this->hasAndBelongsToMany[$assoc]['associationForeignKey']);
					$links = Set::extract(
						$this->{$join}->find('all', compact('conditions', 'recursive', 'fields')),
						"{n}.{$join}." . $this->hasAndBelongsToMany[$assoc]['associationForeignKey']
					);
				}

				foreach ($value as $update) {
					if (!empty($update)) {
						if (is_array($update)) {
							$update[$this->hasAndBelongsToMany[$assoc]['foreignKey']] = $id;
							$this->{$join}->create($update);
							$this->{$join}->save();
						} elseif (!in_array($update, $links)) {
							$values  = join(',', array(
								$db->value($id, $this->getColumnType($this->primaryKey)),
								$db->value($update)
							));
							$newValues[] = "({$values})";
							unset($values);
						}
					}
				}

				if (!empty($newValues)) {
					$fields = join(',', array(
						$db->name($this->hasAndBelongsToMany[$assoc]['foreignKey']),
						$db->name($this->hasAndBelongsToMany[$assoc]['associationForeignKey'])
					));
					$db->insertMulti($this->{$join}, $fields, $newValues);
				}
			}
		}
	}
/**
 * Updates the counter cache of belongsTo associations after a save or delete operation
 *
 * @param array $keys Optional foreign key data, defaults to the information $this->data
 * @return void
 * @access public
 */
	function updateCounterCache($keys = array()) {
		if (empty($keys)) {
			$keys = $this->data[$this->alias];
		}
		foreach ($this->belongsTo as $parent => $assoc) {
			if (!empty($assoc['counterCache'])) {
				if ($assoc['counterCache'] === true) {
					$assoc['counterCache'] = Inflector::underscore($this->alias) . '_count';
				}
				if ($this->{$parent}->hasField($assoc['counterCache'])) {
					$conditions = array($this->escapeField($assoc['foreignKey']) => $keys[$assoc['foreignKey']]);
					if (isset($assoc['counterScope'])) {
						$conditions = array_merge($conditions, (array)$assoc['counterScope']);
					}
					$this->{$parent}->updateAll(
						array($assoc['counterCache'] => intval($this->find('count', compact('conditions')))),
						array($this->{$parent}->escapeField() => $keys[$assoc['foreignKey']])
					);
				}
			}
		}
	}
/**
 * Saves (a) multiple individual records for a single model or (b) this record, as well as
 * all associated records
 *
 * @param array $data Record data to save
 * @param array $options
 * @return mixed True on success, or an array of validation errors on failure
 * @access public
 */
	function saveAll($data = null, $options = array()) {
		if (empty($data)) {
			return false;
		}
		$db =& ConnectionManager::getDataSource($this->useDbConfig);

		$options = array_merge(
			array('validate' => true, 'fieldList' => array(), 'atomic' => true),
			$options
		);

		if ($options['atomic']) {
			$db->begin($this);
		}

		if (Set::numeric(array_keys($data))) {
			foreach ($data as $record) {
				if (!($this->create($record) && $this->save(null, $options['validate'], $options['fieldList'])) && $options['atomic']) {
					$db->rollback($this);
					return false;
				}
			}
		} else {
			$associations = $this->getAssociated();

			foreach ($data as $association => $values) {
				if (isset($associations[$association]) && ($type = $associations[$association]) == 'belongsTo') {
					$alias = $this->{$association}->alias;
					$foreignKey = $this->{$type}[$association]['foreignKey'];

					if (!$result = $this->{$association}->save($values, $options['validate'], $options['fieldList'])) {
						$db->rollback($this);
						return false;
					} elseif (!isset($data[$foreignKey]) || empty($data[$foreignKey])) {
						$data[$this->alias][$foreignKey] = $this->{$association}->id;
					}
				}
			}

			if (!$this->save($data[$this->alias], $options['validate'], $options['fieldList'])) {
				$db->rollback($this);
				return false;
			}

			foreach ($data as $association => $values) {
				if (isset($associations[$association])) {
					switch ($associations[$association]) {
						case 'hasMany':
							$this->{$association}->saveAll($values);
						break;
					}
				}
			}
		}

		if ($options['atomic']) {
			$db->commit($this);
		}
	}
/**
 * Allows model records to be updated based on a set of conditions
 *
 * @param array $fields Set of fields and values, indexed by fields
 * @param mixed $conditions Conditions to match, true for all records
 * @return boolean True on success, false on failure
 * @access public
 */
	function updateAll($fields, $conditions = true) {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		return $db->update($this, $fields, null, $conditions);
	}
/**
 * Synonym for del().
 *
 * @param mixed $id ID of record to delete
 * @param boolean $cascade Set to true to delete records that depend on this record
 * @return boolean True on success
 * @access public
 * @see Model::del()
 */
	function remove($id = null, $cascade = true) {
		return $this->del($id, $cascade);
	}
/**
 * Removes record for given id. If no id is given, the current id is used. Returns true on success.
 *
 * @param mixed $id ID of record to delete
 * @param boolean $cascade Set to true to delete records that depend on this record
 * @return boolean True on success
 * @access public
 */
	function del($id = null, $cascade = true) {
		if (!empty($id)) {
			$this->id = $id;
		}
		$id = $this->id;

		if ($this->exists() && $this->beforeDelete($cascade)) {
			$db =& ConnectionManager::getDataSource($this->useDbConfig);

			if (!empty($this->behaviors)) {
				$behaviors = array_keys($this->behaviors);
				$ct = count($behaviors);
				for ($i = 0; $i < $ct; $i++) {
					if ($this->behaviors[$behaviors[$i]]->beforeDelete($this, $cascade) === false) {
						return false;
					}
				}
			}
			$this->_deleteDependent($id, $cascade);
			$this->_deleteLinks($id);
			$this->id = $id;

			if (!empty($this->belongsTo)) {
				$keys = $this->find('first', array('fields', $this->__collectForeignKeys()));
			}

			if ($db->delete($this)) {
				if (!empty($this->belongsTo)) {
					$this->updateCounterCache($keys[$this->alias]);
				}
				if (!empty($this->behaviors)) {
					for ($i = 0; $i < $ct; $i++) {
						$this->behaviors[$behaviors[$i]]->afterDelete($this);
					}
				}
				$this->afterDelete();
				$this->_clearCache();
				$this->id = false;
				$this->__exists = null;
				return true;
			}
		}
		return false;
	}
/**
 * Synonym for del().
 *
 * @param mixed $id ID of record to delete
 * @param boolean $cascade Set to true to delete records that depend on this record
 * @return boolean True on success
 * @access public
 * @see Model::del()
 */
	function delete($id = null, $cascade = true) {
		return $this->del($id, $cascade);
	}
/**
 * Cascades model deletes to hasMany and hasOne relationships.
 *
 * @param string $id ID of record that was deleted
 * @param boolean $cascade Set to true to delete records that depend on this record
 * @access protected
 */
	function _deleteDependent($id, $cascade) {
		if (!empty($this->__backAssociation)) {
			$savedAssociatons = $this->__backAssociation;
			$this->__backAssociation = array();
		}
		foreach (array_merge($this->hasMany, $this->hasOne) as $assoc => $data) {
			if ($data['dependent'] === true && $cascade === true) {

				$model =& $this->{$assoc};
				$field = $model->escapeField($data['foreignKey']);
				$model->recursive = -1;

				if (isset($data['exclusive']) && $data['exclusive']) {
					$model->deleteAll(array($field => $id));
				} else {
					$records = $model->findAll(array($field => $id), $model->primaryKey);

					if (!empty($records)) {
						foreach ($records as $record) {
							$model->delete($record[$model->alias][$model->primaryKey]);
						}
					}
				}
			}
		}
		if (isset($savedAssociatons)) {
			$this->__backAssociation = $savedAssociatons;
		}
	}
/**
 * Cascades model deletes to HABTM join keys.
 *
 * @param string $id ID of record that was deleted
 * @access protected
 */
	function _deleteLinks($id) {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		foreach ($this->hasAndBelongsToMany as $assoc => $data) {
			if (isset($data['with'])) {
				$model =& $this->{$data['with']};
				$records = $model->findAll(array($data['foreignKey'] => $id), $model->primaryKey, null, null, null, -1);

				if (!empty($records)) {
					foreach ($records as $record) {
						$model->delete($record[$model->alias][$model->primaryKey]);
					}
				}
			} else {
				$table = $db->name($db->fullTableName($data['joinTable']));
				$conditions = $db->name($data['foreignKey']) . ' = ' . $db->value($id);
				$db->query("DELETE FROM {$table} WHERE {$conditions}");
			}
		}
	}
/**
 * Allows model records to be deleted based on a set of conditions
 *
 * @param mixed $conditions Conditions to match
 * @param boolean $cascade Set to true to delete records that depend on this record
 * @param boolean $callbacks Run callbacks (not being used)
 * @return boolean True on success, false on failure
 * @access public
 */
	function deleteAll($conditions, $cascade = true, $callbacks = false) {
		if (empty($conditions)) {
			return false;
		}
		$db =& ConnectionManager::getDataSource($this->useDbConfig);

		if (!$cascade && !$callbacks) {
			return $db->delete($this, $conditions);
		} else {
			$ids = Set::extract(
				$this->find('all', array_merge(array('fields' => "{$this->alias}.{$this->primaryKey}", 'recursive' => 0), compact('conditions'))),
				"{n}.{$this->alias}.{$this->primaryKey}"
			);

			if (empty($ids)) {
				return false;
			}

			if ($callbacks) {
				$_id = $this->id;

				foreach ($ids as $id) {
					$this->delete($id, $cascade);
				}
				$this->id = $_id;
			} else {
				foreach ($ids as $id) {
					$this->_deleteLinks($id);
					if ($cascade) {
						$this->_deleteDependent($id, $cascade);
					}
				}
				return $db->delete($this, array($this->alias . '.' . $this->primaryKey => $ids));
			}
		}
	}
/**
 * Collects foreign keys from associations
 *
 * @access private
 */
	function __collectForeignKeys($type = 'belongsTo') {
		$result = array();

		foreach ($this->{$type} as $assoc => $data) {
			if (isset($data['foreignKey']) && is_string($data['foreignKey'])) {
				$result[$assoc] = $data['foreignKey'];
			}
		}
		return $result;
	}
/**
 * Returns true if a record with set id exists.
 *
 * @param boolean $reset if true will force database query
 * @return boolean True if such a record exists
 * @access public
 */
	function exists($reset = false) {
		if ($this->getID() === false) {
			return false;
		}
		if ($this->__exists !== null && $reset !== true) {
			return $this->__exists;
		}
		return $this->__exists = ($this->findCount(array($this->alias . '.' . $this->primaryKey => $this->getID()), -1) > 0);
	}
/**
 * Returns true if a record that meets given conditions exists
 *
 * @param array $conditions SQL conditions array
 * @return boolean True if such a record exists
 * @access public
 */
	function hasAny($conditions = null) {
		return ($this->find('count', array('conditions' => $conditions, 'recursive' => -1)) != false);
	}
/**
 * Return a single row as a resultset array.
 * By using the $recursive parameter, the call can access further "levels of association" than
 * the ones this model is directly associated to.
 *
 * Eg: find(array('name' => 'mariano.iglesias'), array('name', 'email'), 'field3 DESC', 2);
 *
 * Also used to perform new-notation finds, where the first argument is type of find operation to perform
 * (all / first / count), second parameter options for finding (indexed array, including: 'conditions', 'limit',
 * 'recursive', 'page', 'fields', 'offset', 'order')
 *
 * Eg: find('all', array(
 * 					'conditions' => array('name' => 'mariano.iglesias'),
 * 					'fields' => array('name', 'email'),
 * 					'order' => 'field3 DESC',
 * 					'recursive' => 2));
 *
 * @param array $conditions SQL conditions array, or type of find operation (all / first / count)
 * @param mixed $fields Either a single string of a field name, or an array of field names, or options for matching
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
 * @param integer $recursive The number of levels deep to fetch associated records
 * @return array Array of records
 * @access public
 */
	function find($conditions = null, $fields = array(), $order = null, $recursive = null) {
		if (!is_string($conditions) || (is_string($conditions) && !array_key_exists($conditions, $this->__findMethods))) {
			$type = 'first';
			$query = array_merge(compact('conditions', 'fields', 'order', 'recursive'), array('limit' => 1));
		} else {
			$type = $conditions;
			$query = $fields;
		}
		$this->findQueryType = $type;

		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$this->id = $this->getID();

		$query = array_merge(
			array(
				'conditions' => null, 'fields' => null, 'joins' => array(),
				'limit' => null, 'offset' => null, 'order' => null, 'page' => null
			),
			$query
		);

		switch ($type) {
			case 'count' :
				if (empty($query['fields'])) {
					$query['fields'] = 'COUNT(*) AS ' . $db->name('count');
				}
				$query['order'] = false;
			break;
			case 'first' :
				$query['limit'] = 1;
				if (empty($query['conditions']) && !empty($this->id)) {
					$query['conditions'] = array($this->escapeField() => $this->id);
				}
			break;
			case 'list' :
				if (empty($query['fields'])) {
					$query['fields'] = array("{$this->alias}.{$this->primaryKey}", "{$this->alias}.{$this->displayField}");
				}
				if (!isset($query['recursive']) || $query['recursive'] === null) {
					$query['recursive'] = -1;
				}
				$keyPath = "{n}.{$this->alias}.{$this->primaryKey}";
				$valuePath = "{n}.{$this->alias}.{$this->displayField}";
			break;
		}

		if (!is_numeric($query['page']) || intval($query['page']) < 1) {
			$query['page'] = 1;
		}

		if ($query['page'] > 1 && $query['limit'] != null) {
			$query['offset'] = ($query['page'] - 1) * $query['limit'];
		}

		if ($query['order'] == null && $query['order'] !== false) {
			if ($this->order == null) {
				$query['order'] = array();
			} else {
				$query['order'] = array($this->order);
			}
		} else {
			$query['order'] = array($query['order']);
		}

		if (!empty($this->behaviors)) {
			$behaviors = array_keys($this->behaviors);
			$ct = count($behaviors);

			for ($i = 0; $i < $ct; $i++) {
				$return = $this->behaviors[$behaviors[$i]]->beforeFind($this, $query);
				if (is_array($return)) {
					$query = $return;
				} elseif ($return === false) {
					return null;
				}
			}
		}

		$return = $this->beforeFind($query);

		if (is_array($return)) {
			$query = $return;
		} elseif ($return === false) {
			return null;
		}
		$results = $db->read($this, $query);
		$this->__resetAssociations();
		$this->findQueryType = null;

		switch ($type) {
			case 'all':
				return $this->__filterResults($results, true);
			break;
			case 'first':
				$results = $this->__filterResults($results, true);
				if (empty($results[0])) {
					return false;
				}
				return $results[0];
			break;
			case 'count':
				if (isset($results[0][0]['count'])) {
					return intval($results[0][0]['count']);
				} elseif (isset($results[0][$this->alias]['count'])) {
					return intval($results[0][$this->alias]['count']);
				}
				return false;
			break;
			case 'list':
				if (empty($results)) {
					return array();
				}
				return Set::combine($results, $keyPath, $valuePath);
			break;
		}
	}
/**
 * Returns a resultset array with specified fields from database matching given conditions.
 * By using the $recursive parameter, the call can access further "levels of association" than
 * the ones this model is directly associated to.
 *
 * @param mixed $conditions SQL conditions as a string or as an array('field' =>'value',...)
 * @param mixed $fields Either a single string of a field name, or an array of field names
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
 * @param integer $limit SQL LIMIT clause, for calculating items per page.
 * @param integer $page Page number, for accessing paged data
 * @param integer $recursive The number of levels deep to fetch associated records
 * @return array Array of records
 * @access public
 * @see Model::find()
 */
	function findAll($conditions = null, $fields = null, $order = null, $limit = null, $page = 1, $recursive = null) {
		return $this->find('all', compact('conditions', 'fields', 'order', 'limit', 'page', 'recursive'));
	}
/**
 * Passes query results through model and behavior afterFilter() methods
 *
 * @param array Results to filter
 * @param boolean $primary If this is the primary model results (results from model where the find operation was performed)
 * @return array Set of filtered results
 * @access private
 */
	function __filterResults($results, $primary = true) {
		if (!empty($this->behaviors)) {
			$b = array_keys($this->behaviors);
			$c = count($b);

			for ($i = 0; $i < $c; $i++) {
				$return = $this->behaviors[$b[$i]]->afterFind($this, $results, $primary);
				if (is_array($return)) {
					$results = $return;
				}
			}
		}
		return $this->afterFind($results, $primary);
	}
/**
 * Method is called only when bindTo<ModelName>() is used.
 * This resets the association arrays for the model back
 * to the original as set in the model.
 *
 * @return boolean Success
 * @access private
 */
	function __resetAssociations() {
		if (!empty($this->__backAssociation)) {
			foreach ($this->__associations as $type) {
				if (isset($this->__backAssociation[$type])) {
					$this->{$type} = $this->__backAssociation[$type];
				}
			}
			$this->__backAssociation = array();
		}

		foreach ($this->__associations as $type) {
			foreach ($this->{$type} as $key => $name) {
				if (!empty($this->{$key}->__backAssociation)) {
					$this->{$key}->__resetAssociations();
				}
			}
		}
		$this->__backAssociation = array();
		return true;
	}
/**
 * Runs a direct query against the bound DataSource, and returns the result.
 *
 * @param string $data Query data
 * @return array Result of the query
 * @access public
 */
	function execute($data) {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$data = $db->fetchAll($data, $this->cacheQueries);

		foreach ($data as $key => $value) {
			foreach ($this->tableToModel as $key1 => $value1) {
				if (isset($data[$key][$key1])) {
					$newData[$key][$value1] = $data[$key][$key1];
				}
			}
		}

		if (!empty($newData)) {
			return $newData;
		}
		return $data;
	}
/**
 * Returns number of rows matching given SQL condition.
 *
 * @param array $conditions SQL conditions array for findAll
 * @param integer $recursive The number of levels deep to fetch associated records
 * @return integer Number of matching rows
 * @access public
 * @see Model::find()
 */
	function findCount($conditions = null, $recursive = 0) {
		return $this->find('count', compact('conditions', 'recursive'));
	}
/**
 * False if any fields passed match any (by default, all if $or = false) of their matching values.
 *
 * @param array $fields Field/value pairs to search (if no values specified, they are pulled from $this->data)
 * @param boolean $or If false, all fields specified must match in order for a false return value
 * @return boolean False if any records matching any fields are found
 * @access public
 */
	function isUnique($fields, $or = true) {
		if (!is_array($fields)) {
			$fields = func_get_args();
			if (is_bool($fields[count($fields) - 1])) {
				$or = $fields[count($fields) - 1];
				unset($fields[count($fields) - 1]);
			}
		}

		foreach ($fields as $field => $value) {
			if (is_numeric($field)) {
				unset($fields[$field]);

				$field = $value;
				if (isset($this->data[$this->alias][$field])) {
					$value = $this->data[$this->alias][$field];
				} else {
					$value = null;
				}
			}

			if (strpos($field, '.') === false) {
				unset($fields[$field]);
				$fields[$this->alias . '.' . $field] = $value;
			}
		}
		if ($or) {
			$fields = array('or' => $fields);
		}
		return ($this->find('count', array('conditions' => $fields)) == 0);
	}
/**
 * Special findAll variation for tables joined to themselves.
 * The table needs the fields id and parent_id to work.
 *
 * @param array $conditions Conditions for the findAll() call
 * @param array $fields Fields for the findAll() call
 * @param string $sort SQL ORDER BY statement
 * @return array Threaded results
 * @access public
 * @todo Perhaps create a Component with this logic
 */
	function findAllThreaded($conditions = null, $fields = null, $sort = null) {
		return $this->__doThread(Model::findAll($conditions, $fields, $sort), null);
	}
/**
 * Private, recursive helper method for findAllThreaded.
 *
 * @param array $data Results of find operation
 * @param string $root NULL or id for root node of operation
 * @param integer $index last processed index of $data
 * @return array Threaded results
 * @access private
 * @see Model::findAllThreaded()
 */
	function __doThread($data, $root, $index = 0) {
		$out = array();
		$sizeOf = sizeof($data);

		for ($ii = $index; $ii < $sizeOf; $ii++) {
			if (($data[$ii][$this->alias]['parent_id'] == $root) || (($root === null) && ($data[$ii][$this->alias]['parent_id'] == '0'))) {
				$tmp = $data[$ii];

				if (isset($data[$ii][$this->alias][$this->primaryKey])) {
					$tmp['children'] = $this->__doThread($data, $data[$ii][$this->alias][$this->primaryKey], $ii);
				} else {
					$tmp['children'] = null;
				}

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
 * @param string $field Field name (parameter for findAll)
 * @param integer $value Value from where to find neighbours
 * @return array Array with keys "prev" and "next" that holds the id's
 * @access public
 */
	function findNeighbours($conditions = null, $field, $value) {
		$conditions = (array)$conditions;

		if (is_array($field)) {
			$fields = $field;
			$field = $fields[0];
		} else {
			$fields = $field;
		}

		$prev = $next = null;

		@list($prev) = $this->findAll(array_filter(array_merge($conditions, array($field => '< ' . $value))), $fields, $field . ' DESC', 1, null, 0);
		@list($next) = $this->findAll(array_filter(array_merge($conditions, array($field => '> ' . $value))), $fields, $field . ' ASC', 1, null, 0);

		return compact('prev', 'next');
	}
/**
 * Returns a resultset for given SQL statement. Generic SQL queries should be made with this method.
 *
 * @param string $sql SQL statement
 * @return array Resultset
 * @access public
 */
	function query() {
		$params = func_get_args();
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		return call_user_func_array(array(&$db, 'query'), $params);
	}
/**
 * Returns true if all fields pass validation, otherwise false.
 *
 * @param array $data Parameter usage is deprecated, set Model::$data instead
 * @return boolean True if there are no errors
 * @access public
 */
	function validates($data = array()) {
		if (!empty($data)) {
			trigger_error(__('(Model::validates) Parameter usage is deprecated, use Model::set() to update your fields first', true), E_USER_WARNING);
		}
		$errors = $this->invalidFields($data);
		if (is_array($errors)) {
			return count($errors) === 0;
		}
		return $errors;
	}
/**
 * Returns an array of fields that do not meet validation.
 *
 * @param array $data Parameter usage is deprecated, set Model::$data instead
 * @return array Array of invalid fields
 * @access public
 */
	function invalidFields($data = array()) {
		if (!empty($this->behaviors)) {
			$behaviors = array_keys($this->behaviors);
			$ct = count($behaviors);
			for ($i = 0; $i < $ct; $i++) {
				if ($this->behaviors[$behaviors[$i]]->beforeValidate($this) === false) {
					return $this->validationErrors;
				}
			}
		}

		if (!$this->beforeValidate()) {
			return $this->validationErrors;
		}

		if (empty($data)) {
			$data = $this->data;
		} else {
			trigger_error(__('(Model::invalidFields) Parameter usage is deprecated, set the $data property instead', true), E_USER_WARNING);
		}

		if (!isset($this->validate) || empty($this->validate)) {
			return $this->validationErrors;
		}

		if (isset($data[$this->alias])) {
			$data = $data[$this->alias];
		}

		$Validation =& Validation::getInstance();
		$exists = $this->exists();

		foreach ($this->validate as $fieldName => $ruleSet) {
			if (!is_array($ruleSet) || (is_array($ruleSet) && isset($ruleSet['rule']))) {
				$ruleSet = array($ruleSet);
			}

			foreach ($ruleSet as $index => $validator) {
				if (!is_array($validator)) {
					$validator = array('rule' => $validator);
				}

				$default = array('allowEmpty' => null, 'required' => null, 'rule' => 'blank', 'last' => false, 'on' => null);
				$validator = array_merge($default, $validator);

				if (isset($validator['message'])) {
					$message = $validator['message'];
				} else {
					$message = __('This field cannot be left blank', true);
				}

				if (empty($validator['on']) || ($validator['on'] == 'create' && !$exists) || ($validator['on'] == 'update' && $exists)) {
					if ((!isset($data[$fieldName]) && $validator['required'] === true) || (isset($data[$fieldName]) && (empty($data[$fieldName]) && !is_numeric($data[$fieldName])) && $validator['allowEmpty'] === false)) {
						$this->invalidate($fieldName, $message);
						if ($validator['last']) {
							break;
						}
					} elseif (array_key_exists($fieldName, $data)) {
						if (empty($data[$fieldName]) && $data[$fieldName] != '0' && $validator['allowEmpty'] === true) {
							break;
						}
						if (is_array($validator['rule'])) {
							$rule = $validator['rule'][0];
							unset($validator['rule'][0]);
							$ruleParams = array_merge(array($data[$fieldName]), array_values($validator['rule']));
						} else {
							$rule = $validator['rule'];
							$ruleParams = array($data[$fieldName]);
						}

						$valid = true;
						$msg   = null;

						if (method_exists($this, $rule) || isset($this->__behaviorMethods[$rule]) || isset($this->__behaviorMethods[strtolower($rule)])) {
							$ruleParams[] = array_diff_key($validator, $default);
							$ruleParams[0] = array($fieldName => $ruleParams[0]);
							$valid = call_user_func_array(array(&$this, $rule), $ruleParams);
						} elseif (method_exists($Validation, $rule)) {
							$valid = call_user_func_array(array(&$Validation, $rule), $ruleParams);
						} elseif (!is_array($validator['rule'])) {
							$valid = preg_match($rule, $data[$fieldName]);
						}
						if (!$valid) {
							if (!isset($validator['message'])) {
								if (is_string($index)) {
									$validator['message'] = $index;
								} else {
									$validator['message'] = ife(is_numeric($index) && count($ruleSet) > 1, ($index + 1), $message);
								}
							}

							$this->invalidate($fieldName, $validator['message']);
							if ($validator['last']) {
								break;
							}
						}
					}
				}
			}
		}
		return $this->validationErrors;
	}
/**
 * Sets a field as invalid, optionally setting the name of validation
 * rule (in case of multiple validation for field) that was broken
 *
 * @param string $field The name of the field to invalidate
 * @param string $value Name of validation rule that was not met
 * @access public
 */
	function invalidate($field, $value = null) {
		if (!is_array($this->validationErrors)) {
			$this->validationErrors = array();
		}
		if (empty($value)) {
			$value = true;
		}
		$this->validationErrors[$field] = $value;
	}
/**
 * Returns true if given field name is a foreign key in this Model.
 *
 * @param string $field Returns true if the input string ends in "_id"
 * @return boolean True if the field is a foreign key listed in the belongsTo array.
 * @access public
 */
	function isForeignKey($field) {
		$foreignKeys = array();
		if (!empty($this->belongsTo)) {
			foreach ($this->belongsTo as $assoc => $data) {
				$foreignKeys[] = $data['foreignKey'];
			}
		}
		return in_array($field, $foreignKeys);
	}
/**
 * Gets the display field for this model
 *
 * @return string The name of the display field for this Model (i.e. 'name', 'title').
 * @access public
 */
	function getDisplayField() {
		return $this->displayField;
	}
/**
 * Returns a resultset array with specified fields from database matching given conditions.
 * Method can be used to generate option lists for SELECT elements.
 *
 * @param mixed $conditions SQL conditions as a string or as an array('field' =>'value',...)
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
 * @param integer $limit SQL LIMIT clause, for calculating items per page
 * @param string $keyPath A string path to the key, i.e. "{n}.Post.id"
 * @param string $valuePath A string path to the value, i.e. "{n}.Post.title"
 * @param string $groupPath A string path to a value to group the elements by, i.e. "{n}.Post.category_id"
 * @return array An associative array of records, where the id is the key, and the display field is the value
 * @access public
 */
	function generateList($conditions = null, $order = null, $limit = null, $keyPath = null, $valuePath = null, $groupPath = null) {
		trigger_error(__('(Model::generateList) Deprecated, use Model::find("list") or Model::find("all") and Set::combine()', true), E_USER_WARNING);

		if ($keyPath == null && $valuePath == null && $groupPath == null && $this->hasField($this->displayField)) {
			$fields = array($this->primaryKey, $this->displayField);
		} else {
			$fields = null;
		}
		$recursive = $this->recursive;

		if ($groupPath == null && $recursive >= 1) {
			$this->recursive = -1;
		} elseif ($groupPath && $recursive >= 1) {
			$this->recursive = 0;
		}
		$result = $this->findAll($conditions, $fields, $order, $limit);
		$this->recursive = $recursive;

		if (!$result) {
			return false;
		}

		if ($keyPath == null) {
			$keyPath = "{n}.{$this->alias}.{$this->primaryKey}";
		}

		if ($valuePath == null) {
			$valuePath = "{n}.{$this->alias}.{$this->displayField}";
		}

		return Set::combine($result, $keyPath, $valuePath, $groupPath);
	}
/**
 * Escapes the field name and prepends the model name. Escaping will be done according to the current database driver's rules.
 *
 * @param string $field Field to escape (e.g: id)
 * @param string $alias Alias for the model (e.g: Post)
 * @return string The name of the escaped field for this Model (i.e. id becomes `Post`.`id`).
 * @access public
 */
	function escapeField($field = null, $alias = null) {
		if (empty($alias)) {
			$alias = $this->alias;
		}
		if (empty($field)) {
			$field = $this->primaryKey;
		}
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		if (strpos($field, $db->name($alias)) === 0) {
			return $field;
		}
		return $db->name($alias) . '.' . $db->name($field);
	}
/**
 * Returns the current record's ID
 *
 * @param integer $list Index on which the composed ID is located
 * @return mixed The ID of the current record, false if no ID
 * @access public
 */
	function getID($list = 0) {
		if (empty($this->id) || (is_array($this->id) && isset($this->id[0]) && empty($this->id[0]))) {
			return false;
		}

		if (!is_array($this->id)) {
			return $this->id;
		}

		if (empty($this->id)) {
			return false;
		}

		if (isset($this->id[$list]) && !empty($this->id[$list])) {
			return $this->id[$list];
		} elseif (isset($this->id[$list])) {
			return false;
		}

		foreach ($this->id as $id) {
			return $id;
		}

		return false;
	}

	function normalizeFindParams($type, $data, $altType = null, $r = array(), $_this = null) {
		if ($_this == null) {
			$_this = $this;
			$root = true;
		}

		foreach ((array)$data as $name => $children) {
			if (is_numeric($name)) {
				$name = $children;
				$children = array();
			}

			if (strpos($name, '.') !== false) {
				$chain = explode('.', $name);
				$name = array_shift($chain);
				$children = array(join('.', $chain) => $children);
			}

			if (!empty($children)) {
				if ($_this->name == $name) {
					$r = array_merge($r, $this->normalizeFindParams($type, $children, $altType, $r, $_this));
				} else {
					if (!$_this->getAssociated($name)) {
						$r[$altType][$name] = $children;
					} else {
						$r[$name] = $this->normalizeFindParams($type, $children, $altType, @$r[$name], $_this->{$name});;
					}
				}
			} else {
				if ($_this->getAssociated($name)) {
					$r[$name] = array($type => null);
				} else {
					if ($altType != null) {
						$r[$type][] = $name;
					} else {
						$r[$type] = $name;
					}
				}
			}
		}

		if (isset($root)) {
			return array($this->name => $r);
		}

		return $r;
	}
/**
 * Returns the ID of the last record this Model inserted
 *
 * @return mixed Last inserted ID
 * @access public
 */
	function getLastInsertID() {
		return $this->getInsertID();
	}
/**
 * Returns the ID of the last record this Model inserted
 *
 * @return mixed Last inserted ID
 * @access public
 */
	function getInsertID() {
		return $this->__insertID;
	}
/**
 * Sets the ID of the last record this Model inserted
 *
 * @param mixed Last inserted ID
 * @access public
 */
	function setInsertID($id) {
		$this->__insertID = $id;
	}
/**
 * Returns the number of rows returned from the last query
 *
 * @return int Number of rows
 * @access public
 */
	function getNumRows() {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		return $db->lastNumRows();
	}
/**
 * Returns the number of rows affected by the last query
 *
 * @return int Number of rows
 * @access public
 */
	function getAffectedRows() {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		return $db->lastAffected();
	}
/**
 * Sets the DataSource to which this model is bound
 *
 * @param string $dataSource The name of the DataSource, as defined in Connections.php
 * @return boolean True on success
 * @access public
 */
	function setDataSource($dataSource = null) {
		if ($dataSource != null) {
			$this->useDbConfig = $dataSource;
		}
		$db =& ConnectionManager::getDataSource($this->useDbConfig);

		if (!empty($db->config['prefix']) && $this->tablePrefix === null) {
			$this->tablePrefix = $db->config['prefix'];
		}

		if (empty($db) || $db == null || !is_object($db)) {
			return $this->cakeError('missingConnection', array(array('className' => $this->alias)));
		}
	}
/**
 * Gets the DataSource to which this model is bound.
 * Not safe for use with some versions of PHP4, because this class is overloaded.
 *
 * @return object A DataSource object
 * @access public
 */
	function &getDataSource() {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		return $db;
	}
/**
 * Gets all the models with which this model is associated
 *
 * @param string $type Only result associations of this type
 * @return array Associations
 * @access public
 */
	function getAssociated($type = null) {
		if ($type == null) {
			$associated = array();
			foreach ($this->__associations as $assoc) {
				if (!empty($this->{$assoc})) {
					$models = array_keys($this->{$assoc});
					foreach ($models as $m) {
						$associated[$m] = $assoc;
					}
				}
			}
			return $associated;
		} elseif (in_array($type, $this->__associations)) {
			if (empty($this->{$type})) {
				return array();
			}
			return array_keys($this->{$type});
		} else {
			$assoc = array_merge($this->hasOne, $this->hasMany, $this->belongsTo, $this->hasAndBelongsToMany);
			if (array_key_exists($type, $assoc)) {
				foreach ($this->__associations as $a) {
					if (isset($this->{$a}[$type])) {
						$assoc[$type]['association'] = $a;
						break;
					}
				}
				return $assoc[$type];
			}
			return null;
		}
	}
/**
 * Gets the name and fields to be used by a join model.  This allows specifying join fields in the association definition.
 *
 * @param object $model The model to be joined
 * @param mixed $with The 'with' key of the model association
 * @param array $keys Any join keys which must be merged with the keys queried
 * @return array
 */
	function joinModel($assoc, $keys = array()) {
		if (is_string($assoc)) {
			return array($assoc, array_keys($this->{$assoc}->schema()));
		} elseif (is_array($assoc)) {
			$with = key($assoc);
			return array($with, array_unique(array_merge($assoc[$with], $keys)));
		} else {
			trigger_error(sprintf(__('Invalid join model settings in %s', true), $model->alias), E_USER_WARNING);
		}
	}
/**
 * Before find callback
 *
 * @param array $queryData Data used to execute this query, i.e. conditions, order, etc.
 * @return mixed true if the operation should continue, false if it should abort; or, modified $queryData to continue with new $queryData
 * @access public
 */
	function beforeFind($queryData) {
		return true;
	}
/**
 * After find callback. Can be used to modify any results returned by find and findAll.
 *
 * @param mixed $results The results of the find operation
 * @param boolean $primary Whether this model is being queried directly (vs. being queried as an association)
 * @return mixed Result of the find operation
 * @access public
 */
	function afterFind($results, $primary = false) {
		return $results;
	}
/**
 * Before save callback
 *
 * @return boolean True if the operation should continue, false if it should abort
 * @access public
 */
	function beforeSave() {
		return true;
	}
/**
 * After save callback
 *
 * @param boolean $created True if this save created a new record
 * @access public
 */
	function afterSave($created) {
	}
/**
 * Before delete callback
 *
 * @param boolean $cascade If true records that depend on this record will also be deleted
 * @return boolean True if the operation should continue, false if it should abort
 * @access public
 */
	function beforeDelete($cascade = true) {
		return true;
	}
/**
 * After delete callback
 *
 * @access public
 */
	function afterDelete() {
	}
/**
 * Before validate callback
 *
 * @return boolean True if validate operation should continue, false to abort
 * @access public
 */
	function beforeValidate() {
		return true;
	}
/**
 * DataSource error callback
 *
 * @access public
 */
	function onError() {
	}
/**
 * Private method. Clears cache for this model
 *
 * @param string $type If null this deletes cached views if Cache.check is true
 *                     Will be used to allow deleting query cache also
 * @return boolean true on delete
 * @access protected
 * @todo
 */
	function _clearCache($type = null) {
		if ($type === null) {
			if (Configure::read('Cache.check') === true) {
				$assoc[] = strtolower(Inflector::pluralize($this->alias));
				foreach ($this->__associations as $key => $association) {
					foreach ($this->$association as $key => $className) {
						$check = strtolower(Inflector::pluralize($className['className']));
						if (!in_array($check, $assoc)) {
							$assoc[] = strtolower(Inflector::pluralize($className['className']));
						}
					}
				}
				clearCache($assoc);
				return true;
			}
		} else {
			//Will use for query cache deleting
		}
	}
/**
 * Called when serializing a model
 *
 * @return array Set of object variable names this model has
 * @access private
 */
	function __sleep() {
		$return = array_keys(get_object_vars($this));
		return $return;
	}
/**
 * Called when unserializing a model
 *
 * @access private
 */
	function __wakeup() {
	}
}
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	Overloadable::overload('Model');
}
?>