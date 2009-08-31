<?php
/* SVN FILE: $Id$ */
/**
 * Object-relational mapper.
 *
 * DBO-backed object data model, for mapping database tables to Cake objects.
 *
 * PHP versions 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.model
 * @since         CakePHP(tm) v 0.10.0.0
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libs
 */
App::import('Core', array('ClassRegistry', 'Overloadable', 'Validation', 'Behavior', 'ConnectionManager', 'Set', 'String'));
/**
 * Object-relational mapper.
 *
 * DBO-backed object data model.
 * Automatically selects a database table name based on a pluralized lowercase object class name
 * (i.e. class 'User' => table 'users'; class 'Man' => table 'men')
 * The table is required to have at least 'id auto_increment' primary key.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model
 * @link          http://book.cakephp.org/view/66/Models
 */
class Model extends Overloadable {
/**
 * The name of the DataSource connection that this Model uses
 *
 * @var string
 * @access public
 * @link http://book.cakephp.org/view/435/useDbConfig
 */
	var $useDbConfig = 'default';
/**
 * Custom database table name, or null/false if no table association is desired.
 *
 * @var string
 * @access public
 * @link http://book.cakephp.org/view/436/useTable
 */
	var $useTable = null;
/**
 * Custom display field name. Display fields are used by Scaffold, in SELECT boxes' OPTION elements.
 *
 * @var string
 * @access public
 * @link http://book.cakephp.org/view/438/displayField
 */
	var $displayField = null;
/**
 * Value of the primary key ID of the record that this model is currently pointing to.
 * Automatically set after database insertions.
 *
 * @var mixed
 * @access public
 */
	var $id = false;
/**
 * Container for the data that this model gets from persistent storage (usually, a database).
 *
 * @var array
 * @access public
 * @link http://book.cakephp.org/view/441/data
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
 * The name of the primary key field for this model.
 *
 * @var string
 * @access public
 * @link http://book.cakephp.org/view/437/primaryKey
 */
	var $primaryKey = null;
/**
 * Field-by-field table metadata.
 *
 * @var array
 * @access protected
 * @link http://book.cakephp.org/view/442/_schema
 */
	var $_schema = null;
/**
 * List of validation rules. Append entries for validation as ('field_name' => '/^perl_compat_regexp$/')
 * that have to match with preg_match(). Use these rules with Model::validate()
 *
 * @var array
 * @access public
 * @link http://book.cakephp.org/view/443/validate
 * @link http://book.cakephp.org/view/125/Data-Validation
 */
	var $validate = array();
/**
 * List of validation errors.
 *
 * @var array
 * @access public
 * @link http://book.cakephp.org/view/410/Validating-Data-from-the-Controller
 */
	var $validationErrors = array();
/**
 * Database table prefix for tables in model.
 *
 * @var string
 * @access public
 * @link http://book.cakephp.org/view/475/tablePrefix
 */
	var $tablePrefix = null;
/**
 * Name of the model.
 *
 * @var string
 * @access public
 * @link http://book.cakephp.org/view/444/name
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
 * List of table names included in the model description. Used for associations.
 *
 * @var array
 * @access public
 */
	var $tableToModel = array();
/**
 * Whether or not to log transactions for this model.
 *
 * @var boolean
 * @access public
 */
	var $logTransactions = false;
/**
 * Whether or not to enable transactions for this model (i.e. BEGIN/COMMIT/ROLLBACK statements)
 *
 * @var boolean
 * @access public
 */
	var $transactional = false;
/**
 * Whether or not to cache queries for this model.  This enables in-memory
 * caching only, the results are not stored beyond the current request.
 *
 * @var boolean
 * @access public
 * @link http://book.cakephp.org/view/445/cacheQueries
 */
	var $cacheQueries = false;
/**
 * Detailed list of belongsTo associations.
 *
 * @var array
 * @access public
 * @link http://book.cakephp.org/view/81/belongsTo
 */
	var $belongsTo = array();
/**
 * Detailed list of hasOne associations.
 *
 * @var array
 * @access public
 * @link http://book.cakephp.org/view/80/hasOne
 */
	var $hasOne = array();
/**
 * Detailed list of hasMany associations.
 *
 * @var array
 * @access public
 * @link http://book.cakephp.org/view/82/hasMany
 */
	var $hasMany = array();
/**
 * Detailed list of hasAndBelongsToMany associations.
 *
 * @var array
 * @access public
 * @link http://book.cakephp.org/view/83/hasAndBelongsToMany-HABTM
 */
	var $hasAndBelongsToMany = array();
/**
 * List of behaviors to load when the model object is initialized. Settings can be
 * passed to behaviors by using the behavior name as index. Eg:
 *
 * var $actsAs = array('Translate', 'MyBehavior' => array('setting1' => 'value1'))
 *
 * @var array
 * @access public
 * @link http://book.cakephp.org/view/90/Using-Behaviors
 */
	var $actsAs = null;
/**
 * Holds the Behavior objects currently bound to this model.
 *
 * @var BehaviorCollection
 * @access public
 */
	var $Behaviors = null;
/**
 * Whitelist of fields allowed to be saved.
 *
 * @var array
 * @access public
 */
	var $whitelist = array();
/**
 * Whether or not to cache sources for this model.
 *
 * @var boolean
 * @access public
 */
	var $cacheSources = true;
/**
 * Type of find query currently executing.
 *
 * @var string
 * @access public
 */
	var $findQueryType = null;
/**
 * Number of associations to recurse through during find calls. Fetches only
 * the first level by default.
 *
 * @var integer
 * @access public
 * @link http://book.cakephp.org/view/439/recursive
 */
	var $recursive = 1;
/**
 * The column name(s) and direction(s) to order find results by default.
 *
 * var $order = "Post.created DESC";
 * var $order = array("Post.view_count DESC", "Post.rating DESC");
 *
 * @var string
 * @access public
 * @link http://book.cakephp.org/view/440/order
 */
	var $order = null;
/**
 * Whether or not the model record exists, set by Model::exists().
 *
 * @var bool
 * @access private
 */
	var $__exists = null;
/**
 * Default list of association keys.
 *
 * @var array
 * @access private
 */
	var $__associationKeys = array(
		'belongsTo' => array('className', 'foreignKey', 'conditions', 'fields', 'order', 'counterCache'),
		'hasOne' => array('className', 'foreignKey','conditions', 'fields','order', 'dependent'),
		'hasMany' => array('className', 'foreignKey', 'conditions', 'fields', 'order', 'limit', 'offset', 'dependent', 'exclusive', 'finderQuery', 'counterQuery'),
		'hasAndBelongsToMany' => array('className', 'joinTable', 'with', 'foreignKey', 'associationForeignKey', 'conditions', 'fields', 'order', 'limit', 'offset', 'unique', 'finderQuery', 'deleteQuery', 'insertQuery')
	);
/**
 * Holds provided/generated association key names and other data for all associations.
 *
 * @var array
 * @access private
 */
	var $__associations = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');
/**
 * Holds model associations temporarily to allow for dynamic (un)binding.
 *
 * @var array
 * @access private
 */
	var $__backAssociation = array();
/**
 * The ID of the model record that was last inserted.
 *
 * @var integer
 * @access private
 */
	var $__insertID = null;
/**
 * The number of records returned by the last query.
 *
 * @var integer
 * @access private
 */
	var $__numRows = null;
/**
 * The number of records affected by the last query.
 *
 * @var integer
 * @access private
 */
	var $__affectedRows = null;
/**
 * List of valid finder method options, supplied as the first parameter to find().
 *
 * @var array
 * @access protected
 */
	var $_findMethods = array(
		'all' => true, 'first' => true, 'count' => true,
		'neighbors' => true, 'list' => true, 'threaded' => true
	);
/**
 * Constructor. Binds the model's database table to the object.
 *
 * @param integer $id Set this ID for this model on startup
 * @param string $table Name of database table to use.
 * @param object $ds DataSource connection object.
 */
	function __construct($id = false, $table = null, $ds = null) {
		parent::__construct();

		if (is_array($id)) {
			extract(array_merge(
				array(
					'id' => $this->id, 'table' => $this->useTable, 'ds' => $this->useDbConfig,
					'name' => $this->name, 'alias' => $this->alias
				),
				$id
			));
		}

		if ($this->name === null) {
			$this->name = (isset($name) ? $name : get_class($this));
		}

		if ($this->alias === null) {
			$this->alias = (isset($alias) ? $alias : $this->name);
		}

		if ($this->primaryKey === null) {
			$this->primaryKey = 'id';
		}

		ClassRegistry::addObject($this->alias, $this);

		$this->id = $id;
		unset($id);

		if ($table === false) {
			$this->useTable = false;
		} elseif ($table) {
			$this->useTable = $table;
		}

		if ($ds !== null) {
			$this->useDbConfig = $ds;
		}

		if (is_subclass_of($this, 'AppModel')) {
			$appVars = get_class_vars('AppModel');
			$merge = array('_findMethods');

			if ($this->actsAs !== null || $this->actsAs !== false) {
				$merge[] = 'actsAs';
			}
			$parentClass = get_parent_class($this);
			if (strtolower($parentClass) !== 'appmodel') {
				$parentVars = get_class_vars($parentClass);
				foreach ($merge as $var) {
					if (isset($parentVars[$var]) && !empty($parentVars[$var])) {
						$appVars[$var] = Set::merge($appVars[$var], $parentVars[$var]);
					}
				}
			}

			foreach ($merge as $var) {
				if (isset($appVars[$var]) && !empty($appVars[$var]) && is_array($this->{$var})) {
					$this->{$var} = Set::merge($appVars[$var], $this->{$var});
				}
			}
		}
		$this->Behaviors = new BehaviorCollection();

		if ($this->useTable !== false) {
			$this->setDataSource($ds);

			if ($this->useTable === null) {
				$this->useTable = Inflector::tableize($this->name);
			}
			if (method_exists($this, 'setTablePrefix')) {
				$this->setTablePrefix();
			}
			$this->setSource($this->useTable);

			if ($this->displayField == null) {
				$this->displayField = $this->hasField(array('title', 'name', $this->primaryKey));
			}
		} elseif ($this->table === false) {
			$this->table = Inflector::tableize($this->name);
		}
		$this->__createLinks();
		$this->Behaviors->init($this->alias, $this->actsAs);
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
		$result = $this->Behaviors->dispatchMethod($this, $method, $params);

		if ($result !== array('unhandled')) {
			return $result;
		}
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$return = $db->query($method, $params, $this);

		if (!PHP5) {
			$this->resetAssociations();
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
 * @return void
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
 * @link http://book.cakephp.org/view/86/Creating-and-Destroying-Associations-on-the-Fly
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
 * @link http://book.cakephp.org/view/86/Creating-and-Destroying-Associations-on-the-Fly
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
 * Create a set of associations.
 *
 * @return void
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

			if (!empty($this->{$type})) {
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
					$className =  $assoc;

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
	}
/**
 * Private helper method to create associated models of a given class.
 *
 * @param string $assoc Association name
 * @param string $className Class name
 * @deprecated $this->$className use $this->$assoc instead. $assoc is the 'key' in the associations array;
 * 	examples: var $hasMany = array('Assoc' => array('className' => 'ModelName'));
 * 					usage: $this->Assoc->modelMethods();
 *
 * 				var $hasMany = array('ModelName');
 * 					usage: $this->ModelName->modelMethods();
 * @return void
 * @access private
 */
	function __constructLinkedModel($assoc, $className = null) {
		if (empty($className)) {
			$className = $assoc;
		}

		if (!isset($this->{$assoc}) || $this->{$assoc}->name !== $className) {
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
	}
/**
 * Build an array-based association from string.
 *
 * @param string $type 'belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany'
 * @return void
 * @access private
 */
	function __generateAssociation($type) {
		foreach ($this->{$type} as $assocKey => $assocData) {
			$class = $assocKey;
			$dynamicWith = false;

			foreach ($this->__associationKeys[$type] as $key) {

				if (!isset($this->{$type}[$assocKey][$key]) || $this->{$type}[$assocKey][$key] === null) {
					$data = '';

					switch ($key) {
						case 'fields':
							$data = '';
						break;

						case 'foreignKey':
							$data = (($type == 'belongsTo') ? Inflector::underscore($assocKey) : Inflector::singularize($this->table)) . '_id';
						break;

						case 'associationForeignKey':
							$data = Inflector::singularize($this->{$class}->table) . '_id';
						break;

						case 'with':
							$data = Inflector::camelize(Inflector::singularize($this->{$type}[$assocKey]['joinTable']));
							$dynamicWith = true;
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

			if (!empty($this->{$type}[$assocKey]['with'])) {
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

				if (!ClassRegistry::isKeySet($joinClass) && $dynamicWith === true) {
					$this->{$joinClass} = new AppModel(array(
						'name' => $joinClass,
						'table' => $this->{$type}[$assocKey]['joinTable'],
						'ds' => $this->useDbConfig
					));
				} else {
					$this->__constructLinkedModel($joinClass, $plugin . $joinClass);
					$this->{$type}[$assocKey]['joinTable'] = $this->{$joinClass}->table;
				}

				if (count($this->{$joinClass}->schema()) <= 2 && $this->{$joinClass}->primaryKey !== false) {
					$this->{$joinClass}->primaryKey = $this->{$type}[$assocKey]['foreignKey'];
				}
			}
		}
	}
/**
 * Sets a custom table for your controller class. Used by your controller to select a database table.
 *
 * @param string $tableName Name of the custom table
 * @return void
 * @access public
 */
	function setSource($tableName) {
		$this->setDataSource($this->useDbConfig);
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$db->cacheSources = ($this->cacheSources && $db->cacheSources);

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
			$data = $one;
			if (empty($one[$this->alias])) {
				if ($this->getAssociated(key($one)) === null) {
					$data = array($this->alias => $one);
				}
			}
		} else {
			$data = array($this->alias => array($one => $two));
		}

		foreach ($data as $modelName => $fieldSet) {
			if (is_array($fieldSet)) {

				foreach ($fieldSet as $fieldName => $fieldValue) {
					if (isset($this->validationErrors[$fieldName])) {
						unset ($this->validationErrors[$fieldName]);
					}

					if ($modelName === $this->alias) {
						if ($fieldName === $this->primaryKey) {
							$this->id = $fieldValue;
						}
					}
					if (is_array($fieldValue) || is_object($fieldValue)) {
						$fieldValue = $this->deconstruct($fieldName, $fieldValue);
					}
					$this->data[$modelName][$fieldName] = $fieldValue;
				}
			}
		}
		return $data;
	}
/**
 * Deconstructs a complex data type (array or object) into a single field value.
 *
 * @param string $field The name of the field to be deconstructed
 * @param mixed $data An array or object to be deconstructed into a field
 * @return mixed The resulting data that should be assigned to a field
 * @access public
 */
	function deconstruct($field, $data) {
		if (!is_array($data)) {
			return $data;
		}

		$copy = $data;
		$type = $this->getColumnType($field);

		if (in_array($type, array('datetime', 'timestamp', 'date', 'time'))) {
			$useNewDate = (isset($data['year']) || isset($data['month']) ||
				isset($data['day']) || isset($data['hour']) || isset($data['minute']));

			$dateFields = array('Y' => 'year', 'm' => 'month', 'd' => 'day', 'H' => 'hour', 'i' => 'min', 's' => 'sec');
			$timeFields = array('H' => 'hour', 'i' => 'min', 's' => 'sec');

			$db =& ConnectionManager::getDataSource($this->useDbConfig);
			$format = $db->columns[$type]['format'];
			$date = array();

			if (isset($data['hour']) && isset($data['meridian']) && $data['hour'] != 12 && 'pm' == $data['meridian']) {
				$data['hour'] = $data['hour'] + 12;
			}
			if (isset($data['hour']) && isset($data['meridian']) && $data['hour'] == 12 && 'am' == $data['meridian']) {
				$data['hour'] = '00';
			}
			if ($type == 'time') {
				foreach ($timeFields as $key => $val) {
					if (!isset($data[$val]) || $data[$val] === '0' || $data[$val] === '00') {
						$data[$val] = '00';
					} elseif ($data[$val] === '') {
						$data[$val] = '';
					} else {
						$data[$val] = sprintf('%02d', $data[$val]);
					}
					if (!empty($data[$val])) {
						$date[$key] = $data[$val];
					} else {
						return null;
					}
				}
			}

			if ($type == 'datetime' || $type == 'timestamp' || $type == 'date') {
				foreach ($dateFields as $key => $val) {
					if ($val == 'hour' || $val == 'min' || $val == 'sec') {
						if (!isset($data[$val]) || $data[$val] === '0' || $data[$val] === '00') {
							$data[$val] = '00';
						} else {
							$data[$val] = sprintf('%02d', $data[$val]);
						}
					}
					if (!isset($data[$val]) || isset($data[$val]) && (empty($data[$val]) || $data[$val][0] === '-')) {
						return null;
					}
					if (isset($data[$val]) && !empty($data[$val])) {
						$date[$key] = $data[$val];
					}
				}
			}
			$date = str_replace(array_keys($date), array_values($date), $format);
			if ($useNewDate && !empty($date)) {
				return $date;
			}
		}
		return $data;
	}
/**
 * Returns an array of table metadata (column names and types) from the database.
 * $field => keys(type, null, default, key, length, extra)
 *
 * @param mixed $field Set to true to reload schema, or a string to return a specific field
 * @return array Array of table metadata
 * @access public
 */
	function schema($field = false) {
		if (!is_array($this->_schema) || $field === true) {
			$db =& ConnectionManager::getDataSource($this->useDbConfig);
			$db->cacheSources = ($this->cacheSources && $db->cacheSources);
			if ($db->isInterfaceSupported('describe') && $this->useTable !== false) {
				$this->_schema = $db->describe($this, $field);
			} elseif ($this->useTable === false) {
				$this->_schema = array();
			}
		}
		if (is_string($field)) {
			if (isset($this->_schema[$field])) {
				return $this->_schema[$field];
			} else {
				return null;
			}
		}
		return $this->_schema;
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
 * Returns the column type of a column in the model.
 *
 * @param string $column The name of the model column
 * @return string Column type
 * @access public
 */
	function getColumnType($column) {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$cols = $this->schema();
		$model = null;

		$column = str_replace(array($db->startQuote, $db->endQuote), '', $column);

		if (strpos($column, '.')) {
			list($model, $column) = explode('.', $column);
		}
		if ($model != $this->alias && isset($this->{$model})) {
			return $this->{$model}->getColumnType($column);
		}
		if (isset($cols[$column]) && isset($cols[$column]['type'])) {
			return $cols[$column]['type'];
		}
		return null;
	}
/**
 * Returns true if the supplied field exists in the model's database table.
 *
 * @param mixed $name Name of field to look for, or an array of names
 * @return mixed If $name is a string, returns a boolean indicating whether the field exists.
 *               If $name is an array of field names, returns the first field that exists,
 *               or false if none exist.
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
 * for those fields that are not defined in $data. Especially helpful for
 * saving data in loops.
 *
 * @param mixed $data Optional data array to assign to the model after it is created.  If null or false,
 *                    schema data defaults are not merged.
 * @param boolean $filterKey If true, overwrites any primary key input with an empty value
 * @return array The current Model::data; after merging $data and/or defaults from database
 * @access public
 * @link http://book.cakephp.org/view/75/Saving-Your-Data
 */
	function create($data = array(), $filterKey = false) {
		$defaults = array();
		$this->id = false;
		$this->data = array();
		$this->__exists = null;
		$this->validationErrors = array();

		if ($data !== null && $data !== false) {
			foreach ($this->schema() as $field => $properties) {
				if ($this->primaryKey !== $field && isset($properties['default'])) {
					$defaults[$field] = $properties['default'];
				}
			}
			$this->set(Set::filter($defaults));
			$this->set($data);
		}
		if ($filterKey) {
			$this->set($this->primaryKey, false);
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
			$this->data = $this->find('first', array(
				'conditions' => array($this->alias . '.' . $this->primaryKey => $id),
				'fields' => $fields
			));
			return $this->data;
		} else {
			return false;
		}
	}
/**
 * Returns the contents of a single field given the supplied conditions, in the
 * supplied order.
 *
 * @param string $name Name of field to get
 * @param array $conditions SQL conditions (defaults to NULL)
 * @param string $order SQL ORDER BY fragment
 * @return string field contents, or false if not found
 * @access public
 * @link http://book.cakephp.org/view/453/field
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
 * Saves the value of a single field to the database, based on the current
 * model ID.
 *
 * @param string $name Name of the table field
 * @param mixed $value Value of the field
 * @param array $validate See $options param in Model::save(). Does not respect 'fieldList' key if passed
 * @return boolean See Model::save()
 * @access public
 * @see Model::save()
 * @link http://book.cakephp.org/view/75/Saving-Your-Data
 */
	function saveField($name, $value, $validate = false) {
		$id = $this->id;
		$this->create(false);

		if (is_array($validate)) {
			$options = array_merge(array('validate' => false, 'fieldList' => array($name)), $validate);
		} else {
			$options = array('validate' => $validate, 'fieldList' => array($name));
		}
		return $this->save(array($this->alias => array($this->primaryKey => $id, $name => $value)), $options);
	}
/**
 * Saves model data (based on white-list, if supplied) to the database. By
 * default, validation occurs before save.
 *
 * @param array $data Data to save.
 * @param mixed $validate Either a boolean, or an array.
 * 			If a boolean, indicates whether or not to validate before saving.
 *			If an array, allows control of validate, callbacks, and fieldList
 * @param array $fieldList List of fields to allow to be written
 * @return mixed On success Model::$data if its not empty or true, false on failure
 * @access public
 * @link http://book.cakephp.org/view/75/Saving-Your-Data
 */
	function save($data = null, $validate = true, $fieldList = array()) {
		$defaults = array('validate' => true, 'fieldList' => array(), 'callbacks' => true);
		$_whitelist = $this->whitelist;
		$fields = array();

		if (!is_array($validate)) {
			$options = array_merge($defaults, compact('validate', 'fieldList', 'callbacks'));
		} else {
			$options = array_merge($defaults, $validate);
		}

		if (!empty($options['fieldList'])) {
			$this->whitelist = $options['fieldList'];
		} elseif ($options['fieldList'] === null) {
			$this->whitelist = array();
		}
		$this->set($data);

		if (empty($this->data) && !$this->hasField(array('created', 'updated', 'modified'))) {
			return false;
		}

		foreach (array('created', 'updated', 'modified') as $field) {
			$keyPresentAndEmpty = (
				isset($this->data[$this->alias]) &&
				array_key_exists($field, $this->data[$this->alias]) &&
				$this->data[$this->alias][$field] === null
			);
			if ($keyPresentAndEmpty) {
				unset($this->data[$this->alias][$field]);
			}
		}

		$this->exists();
		$dateFields = array('modified', 'updated');

		if (!$this->__exists) {
			$dateFields[] = 'created';
		}
		if (isset($this->data[$this->alias])) {
			$fields = array_keys($this->data[$this->alias]);
		}
		if ($options['validate'] && !$this->validates($options)) {
			$this->whitelist = $_whitelist;
			return false;
		}

		$db =& ConnectionManager::getDataSource($this->useDbConfig);

		foreach ($dateFields as $updateCol) {
			if ($this->hasField($updateCol) && !in_array($updateCol, $fields)) {
				$default = array('formatter' => 'date');
				$colType = array_merge($default, $db->columns[$this->getColumnType($updateCol)]);
				if (!array_key_exists('format', $colType)) {
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

		if ($options['callbacks'] === true || $options['callbacks'] === 'before') {
			$result = $this->Behaviors->trigger($this, 'beforeSave', array($options), array(
				'break' => true, 'breakOn' => false
			));
			if (!$result || !$this->beforeSave($options)) {
				$this->whitelist = $_whitelist;
				return false;
			}
		}
		$fields = $values = array();

		if (isset($this->data[$this->alias][$this->primaryKey]) && empty($this->data[$this->alias][$this->primaryKey])) {
			unset($this->data[$this->alias][$this->primaryKey]);
		}

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

		if (!$this->__exists && $count > 0) {
			$this->id = false;
		}
		$success = true;
		$created = false;

		if ($count > 0) {
			$cache = $this->_prepareUpdateFields(array_combine($fields, $values));

			if (!empty($this->id)) {
				$success = (bool)$db->update($this, $fields, $values);
			} else {
				foreach ($this->_schema as $field => $properties) {
					if ($this->primaryKey === $field) {
						$fInfo = $this->_schema[$field];
						$isUUID = ($fInfo['length'] == 36 &&
							($fInfo['type'] === 'string' || $fInfo['type'] === 'binary')
						);
						if (empty($this->data[$this->alias][$this->primaryKey]) && $isUUID) {
							list($fields[], $values[]) = array($this->primaryKey, String::uuid());
						}
						break;
					}
				}

				if (!$db->create($this, $fields, $values)) {
					$success = $created = false;
				} else {
					$created = true;
				}
			}

			if ($success && !empty($this->belongsTo)) {
				$this->updateCounterCache($cache, $created);
			}
		}

		if (!empty($joined) && $success === true) {
			$this->__saveMulti($joined, $this->id);
		}

		if ($success && $count > 0) {
			if (!empty($this->data)) {
				$success = $this->data;
			}
			if ($options['callbacks'] === true || $options['callbacks'] === 'after') {
				$this->Behaviors->trigger($this, 'afterSave', array($created, $options));
				$this->afterSave($created);
			}
			if (!empty($this->data)) {
				$success = Set::merge($success, $this->data);
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

		foreach ($joined as $assoc => $data) {

			if (isset($this->hasAndBelongsToMany[$assoc])) {
				list($join) = $this->joinModel($this->hasAndBelongsToMany[$assoc]['with']);

				$conditions = array($join . '.' . $this->hasAndBelongsToMany[$assoc]['foreignKey'] => $id);

				$links = $this->{$join}->find('all', array(
					'conditions' => $conditions,
					'recursive' => -1,
					'fields' => $this->hasAndBelongsToMany[$assoc]['associationForeignKey']
				));

				$isUUID = !empty($this->{$join}->primaryKey) && (
						$this->{$join}->_schema[$this->{$join}->primaryKey]['length'] == 36 && (
						$this->{$join}->_schema[$this->{$join}->primaryKey]['type'] === 'string' ||
						$this->{$join}->_schema[$this->{$join}->primaryKey]['type'] === 'binary'
					)
				);

				$newData = $newValues = array();
				$primaryAdded = false;

				$fields =  array(
					$db->name($this->hasAndBelongsToMany[$assoc]['foreignKey']),
					$db->name($this->hasAndBelongsToMany[$assoc]['associationForeignKey'])
				);

				$idField = $db->name($this->{$join}->primaryKey);
				if ($isUUID && !in_array($idField, $fields)) {
					$fields[] = $idField;
					$primaryAdded = true;
				}

				foreach ((array)$data as $row) {
					if ((is_string($row) && (strlen($row) == 36 || strlen($row) == 16)) || is_numeric($row)) {
						$values = array(
							$db->value($id, $this->getColumnType($this->primaryKey)),
							$db->value($row)
						);
						if ($isUUID && $primaryAdded) {
							$values[] = $db->value(String::uuid());
						}
						$values = join(',', $values);
						$newValues[] = "({$values})";
						unset($values);
					} elseif (isset($row[$this->hasAndBelongsToMany[$assoc]['associationForeignKey']])) {
						$newData[] = $row;
					} elseif (isset($row[$join]) && isset($row[$join][$this->hasAndBelongsToMany[$assoc]['associationForeignKey']])) {
						$newData[] = $row[$join];
					}
				}

				if ($this->hasAndBelongsToMany[$assoc]['unique']) {
					$associationForeignKey = "{$join}." . $this->hasAndBelongsToMany[$assoc]['associationForeignKey'];
					$oldLinks = Set::extract($links, "{n}.{$associationForeignKey}");
					if (!empty($oldLinks)) {
 						$conditions[$associationForeignKey] = $oldLinks;
						$db->delete($this->{$join}, $conditions);
					}
				}

				if (!empty($newData)) {
					foreach ($newData as $data) {
						$data[$this->hasAndBelongsToMany[$assoc]['foreignKey']] = $id;
						$this->{$join}->create($data);
						$this->{$join}->save();
					}
				}

				if (!empty($newValues)) {
					$fields =  join(',', $fields);
					$db->insertMulti($this->{$join}, $fields, $newValues);
				}
			}
		}
	}
/**
 * Updates the counter cache of belongsTo associations after a save or delete operation
 *
 * @param array $keys Optional foreign key data, defaults to the information $this->data
 * @param boolean $created True if a new record was created, otherwise only associations with
 *				  'counterScope' defined get updated
 * @return void
 * @access public
 */
	function updateCounterCache($keys = array(), $created = false) {
		$keys = empty($keys) ? $this->data[$this->alias] : $keys;
		$keys['old'] = isset($keys['old']) ? $keys['old'] : array();

		foreach ($this->belongsTo as $parent => $assoc) {
			$foreignKey = $assoc['foreignKey'];
			$fkQuoted = $this->escapeField($assoc['foreignKey']);

			if (!empty($assoc['counterCache'])) {
				if ($assoc['counterCache'] === true) {
					$assoc['counterCache'] = Inflector::underscore($this->alias) . '_count';
				}
				if (!$this->{$parent}->hasField($assoc['counterCache'])) {
					continue;
				}

				if (!array_key_exists($foreignKey, $keys)) {
					$keys[$foreignKey] = $this->field($foreignKey);
				}
				$recursive = (isset($assoc['counterScope']) ? 1 : -1);
				$conditions = ($recursive == 1) ? (array)$assoc['counterScope'] : array();

				if (isset($keys['old'][$foreignKey])) {
					if ($keys['old'][$foreignKey] != $keys[$foreignKey]) {
						$conditions[$fkQuoted] = $keys['old'][$foreignKey];
						$count = intval($this->find('count', compact('conditions', 'recursive')));

						$this->{$parent}->updateAll(
							array($assoc['counterCache'] => $count),
							array($this->{$parent}->escapeField() => $keys['old'][$foreignKey])
						);
					}
				}
				$conditions[$fkQuoted] = $keys[$foreignKey];

				if ($recursive == 1) {
					$conditions = array_merge($conditions, (array)$assoc['counterScope']);
				}
				$count = intval($this->find('count', compact('conditions', 'recursive')));

				$this->{$parent}->updateAll(
					array($assoc['counterCache'] => $count),
					array($this->{$parent}->escapeField() => $keys[$foreignKey])
				);
			}
		}
	}
/**
 * Helper method for Model::updateCounterCache().  Checks the fields to be updated for
 *
 * @param array $data The fields of the record that will be updated
 * @return array Returns updated foreign key values, along with an 'old' key containing the old
 *               values, or empty if no foreign keys are updated.
 * @access protected
 */
	function _prepareUpdateFields($data) {
		$foreignKeys = array();
		foreach ($this->belongsTo as $assoc => $info) {
			if ($info['counterCache']) {
				$foreignKeys[$assoc] = $info['foreignKey'];
			}
		}
		$included = array_intersect($foreignKeys, array_keys($data));

		if (empty($included) || empty($this->id)) {
			return array();
		}
		$old = $this->find('first', array(
			'conditions' => array($this->primaryKey => $this->id),
			'fields' => array_values($included),
			'recursive' => -1
		));
		return array_merge($data, array('old' => $old[$this->alias]));
	}
/**
 * Saves multiple individual records for a single model; Also works with a single record, as well as
 * all its associated records.
 *
 * @param array $data Record data to save.  This can be either a numerically-indexed array (for saving multiple
 * 						records of the same type), or an array indexed by association name.
 * @param array $options Options to use when saving record data, which are as follows:
 * 							- validate: Set to false to disable validation, true to validate each record before
 * 							  saving, 'first' to validate *all* records before any are saved, or 'only' to only
 * 							  validate the records, but not save them.
 * 							- atomic: If true (default), will attempt to save all records in a single transaction.
 *							  Should be set to false if database/table does not support transactions.
 *								If false, we return an array similar to the $data array passed, but values are set to true/false
 *								depending on whether each record saved successfully.
 *							- fieldList: Equivalent to the $fieldList parameter in Model::save()
 * @return mixed True on success, or false on failure
 * @access public
 * @link http://book.cakephp.org/view/84/Saving-Related-Model-Data-hasOne-hasMany-belongsTo
 * @link http://book.cakephp.org/view/75/Saving-Your-Data
 */
	function saveAll($data = null, $options = array()) {
		if (empty($data)) {
			$data = $this->data;
		}
		$db =& ConnectionManager::getDataSource($this->useDbConfig);

		$options = array_merge(array('validate' => true, 'atomic' => true), $options);
		$this->validationErrors = $validationErrors = array();
		$validates = true;
		$return = array();

		if ($options['atomic'] && $options['validate'] !== 'only') {
			$db->begin($this);
		}

		if (Set::numeric(array_keys($data))) {
			while ($validates) {
				foreach ($data as $key => $record) {
					if (!$currentValidates = $this->__save($record, $options)) {
						$validationErrors[$key] = $this->validationErrors;
					}

					if ($options['validate'] === 'only' || $options['validate'] === 'first') {
						$validating = true;
						if ($options['atomic']) {
							$validates = $validates && $currentValidates;
						} else {
							$validates = $currentValidates;
						}
					} else {
						$validating = false;
						$validates = $currentValidates;
					}

					if (!$options['atomic']) {
						$return[] = $validates;
					} elseif (!$validates && !$validating) {
						break;
					}
				}
				$this->validationErrors = $validationErrors;

				switch (true) {
					case ($options['validate'] === 'only'):
						return ($options['atomic'] ? $validates : $return);
					break;
					case ($options['validate'] === 'first'):
						$options['validate'] = true;
						continue;
					break;
					default:
						if ($options['atomic']) {
							if ($validates && ($db->commit($this) !== false)) {
								return true;
							}
							$db->rollback($this);
							return false;
						}
						return $return;
					break;
				}
			}
			return $return;
		}
		$associations = $this->getAssociated();

		while ($validates) {
			foreach ($data as $association => $values) {
				if (isset($associations[$association])) {
					switch ($associations[$association]) {
						case 'belongsTo':
							if ($this->{$association}->__save($values, $options)) {
								$data[$this->alias][$this->belongsTo[$association]['foreignKey']] = $this->{$association}->id;
							} else {
								$validationErrors[$association] = $this->{$association}->validationErrors;
								$validates = false;
							}
							if (!$options['atomic']) {
								$return[$association][] = $validates;
							}
						break;
					}
				}
			}
			if (!$this->__save($data, $options)) {
				$validationErrors[$this->alias] = $this->validationErrors;
				$validates = false;
			}
			if (!$options['atomic']) {
				$return[$this->alias] = $validates;
			}
			$validating = ($options['validate'] === 'only' || $options['validate'] === 'first');

			foreach ($data as $association => $values) {
				if (!$validates && !$validating) {
					break;
				}
				if (isset($associations[$association])) {
					$type = $associations[$association];
					switch ($type) {
						case 'hasOne':
							$values[$this->{$type}[$association]['foreignKey']] = $this->id;
							if (!$this->{$association}->__save($values, $options)) {
								$validationErrors[$association] = $this->{$association}->validationErrors;
								$validates = false;
							}
							if (!$options['atomic']) {
								$return[$association][] = $validates;
							}
						break;
						case 'hasMany':
							foreach ($values as $i => $value) {
								$values[$i][$this->{$type}[$association]['foreignKey']] =  $this->id;
							}
							$_options = array_merge($options, array('atomic' => false));

							if ($_options['validate'] === 'first') {
								$_options['validate'] = 'only';
							}
							$_return = $this->{$association}->saveAll($values, $_options);

							if ($_return === false || (is_array($_return) && in_array(false, $_return, true))) {
								$validationErrors[$association] = $this->{$association}->validationErrors;
								$validates = false;
							}
							if (is_array($_return)) {
								foreach ($_return as $val) {
									if (!isset($return[$association])) {
										$return[$association] = array();
									} elseif (!is_array($return[$association])) {
										$return[$association] = array($return[$association]);
									}
									$return[$association][] = $val;
								}
							} else {
								$return[$association] = $_return;
							}
						break;
					}
				}
			}
			$this->validationErrors = $validationErrors;

			if (isset($validationErrors[$this->alias])) {
				$this->validationErrors = $validationErrors[$this->alias];
			}

			switch (true) {
				case ($options['validate'] === 'only'):
					return ($options['atomic'] ? $validates : $return);
				break;
				case ($options['validate'] === 'first'):
					$options['validate'] = true;
					continue;
				break;
				default:
					if ($options['atomic']) {
						if ($validates) {
							return ($db->commit($this) !== false);
						} else {
							$db->rollback($this);
						}
					}
					return $return;
				break;
			}
		}
	}
/**
 * Private helper method used by saveAll.
 *
 * @return boolean Success
 * @access private
 * @see Model::saveAll()
 */
	function __save($data, $options) {
		if ($options['validate'] === 'first' || $options['validate'] === 'only') {
			if (!($this->create($data) && $this->validates($options))) {
				return false;
			}
		} elseif (!($this->create(null) !== null && $this->save($data, $options))) {
			return false;
		}
		return true;
	}
/**
 * Updates multiple model records based on a set of conditions.
 *
 * @param array $fields Set of fields and values, indexed by fields.
 * 						Fields are treated as SQL snippets, to insert literal values manually escape your data.
 * @param mixed $conditions Conditions to match, true for all records
 * @return boolean True on success, false on failure
 * @access public
 * @link http://book.cakephp.org/view/75/Saving-Your-Data
 */
	function updateAll($fields, $conditions = true) {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		return $db->update($this, $fields, null, $conditions);
	}
/**
 * Alias for del().
 *
 * @param mixed $id ID of record to delete
 * @param boolean $cascade Set to true to delete records that depend on this record
 * @return boolean True on success
 * @access public
 * @see Model::del()
 * @link http://book.cakephp.org/view/691/remove
 */
	function remove($id = null, $cascade = true) {
		return $this->del($id, $cascade);
	}
/**
 * Removes record for given ID. If no ID is given, the current ID is used. Returns true on success.
 *
 * @param mixed $id ID of record to delete
 * @param boolean $cascade Set to true to delete records that depend on this record
 * @return boolean True on success
 * @access public
 * @link http://book.cakephp.org/view/690/del
 */
	function del($id = null, $cascade = true) {
		if (!empty($id)) {
			$this->id = $id;
		}
		$id = $this->id;

		if ($this->exists() && $this->beforeDelete($cascade)) {
			$db =& ConnectionManager::getDataSource($this->useDbConfig);
			if (!$this->Behaviors->trigger($this, 'beforeDelete', array($cascade), array('break' => true, 'breakOn' => false))) {
				return false;
			}
			$this->_deleteDependent($id, $cascade);
			$this->_deleteLinks($id);
			$this->id = $id;

			if (!empty($this->belongsTo)) {
				$keys = $this->find('first', array('fields' => $this->__collectForeignKeys()));
			}

			if ($db->delete($this)) {
				if (!empty($this->belongsTo)) {
					$this->updateCounterCache($keys[$this->alias]);
				}
				$this->Behaviors->trigger($this, 'afterDelete');
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
 * Alias for del().
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
 * Cascades model deletes through associated hasMany and hasOne child records.
 *
 * @param string $id ID of record that was deleted
 * @param boolean $cascade Set to true to delete records that depend on this record
 * @return void
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
				$conditions = array($model->escapeField($data['foreignKey']) => $id);
				if ($data['conditions']) {
					$conditions = array_merge($data['conditions'], $conditions);
				}
				$model->recursive = -1;

				if (isset($data['exclusive']) && $data['exclusive']) {
					$model->deleteAll($conditions);
				} else {
					$records = $model->find('all', array('conditions' => $conditions, 'fields' => $model->primaryKey));

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
 * Cascades model deletes through HABTM join keys.
 *
 * @param string $id ID of record that was deleted
 * @return void
 * @access protected
 */
	function _deleteLinks($id) {
		foreach ($this->hasAndBelongsToMany as $assoc => $data) {
			$records = $this->{$data['with']}->find('all', array(
				'conditions' => array_merge(array($this->{$data['with']}->escapeField($data['foreignKey']) => $id)),
				'fields' => $this->{$data['with']}->primaryKey,
				'recursive' => -1
			));
			if (!empty($records)) {
				foreach ($records as $record) {
					$this->{$data['with']}->delete($record[$this->{$data['with']}->alias][$this->{$data['with']}->primaryKey]);
				}
			}
		}
	}
/**
 * Deletes multiple model records based on a set of conditions.
 *
 * @param mixed $conditions Conditions to match
 * @param boolean $cascade Set to true to delete records that depend on this record
 * @param boolean $callbacks Run callbacks (not being used)
 * @return boolean True on success, false on failure
 * @access public
 * @link http://book.cakephp.org/view/692/deleteAll
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
				return true;
			}

			if ($callbacks) {
				$_id = $this->id;
				$result = true;
				foreach ($ids as $id) {
					$result = ($result && $this->delete($id, $cascade));
				}
				$this->id = $_id;
				return $result;
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
 * Collects foreign keys from associations.
 *
 * @return array
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
 * Returns true if a record with the currently set ID exists.
 *
 * @param boolean $reset if true will force database query
 * @return boolean True if such a record exists
 * @access public
 */
	function exists($reset = false) {
		if (is_array($reset)) {
			extract($reset, EXTR_OVERWRITE);
		}

		if ($this->getID() === false || $this->useTable === false) {
			return false;
		}
		if (!empty($this->__exists) && $reset !== true) {
			return $this->__exists;
		}
		$conditions = array($this->alias . '.' . $this->primaryKey => $this->getID());
		$query = array('conditions' => $conditions, 'recursive' => -1, 'callbacks' => false);

		if (is_array($reset)) {
			$query = array_merge($query, $reset);
		}
		return $this->__exists = ($this->find('count', $query) > 0);
	}
/**
 * Returns true if a record that meets given conditions exists.
 *
 * @param array $conditions SQL conditions array
 * @return boolean True if such a record exists
 * @access public
 */
	function hasAny($conditions = null) {
		return ($this->find('count', array('conditions' => $conditions, 'recursive' => -1)) != false);
	}
/**
 * Returns a result set array.
 *
 * Also used to perform new-notation finds, where the first argument is type of find operation to perform
 * (all / first / count / neighbors / list / threaded ),
 * second parameter options for finding ( indexed array, including: 'conditions', 'limit',
 * 'recursive', 'page', 'fields', 'offset', 'order')
 *
 * Eg: find('all', array(
 * 					'conditions' => array('name' => 'Thomas Anderson'),
 * 					'fields' => array('name', 'email'),
 * 					'order' => 'field3 DESC',
 * 					'recursive' => 2,
 * 					'group' => 'type'));
 *
 * Specifying 'fields' for new-notation 'list':
 *  - If no fields are specified, then 'id' is used for key and 'model->displayField' is used for value.
 *  - If a single field is specified, 'id' is used for key and specified field is used for value.
 *  - If three fields are specified, they are used (in order) for key, value and group.
 *  - Otherwise, first and second fields are used for key and value.
 *
 * @param array $conditions SQL conditions array, or type of find operation (all / first / count / neighbors / list / threaded)
 * @param mixed $fields Either a single string of a field name, or an array of field names, or options for matching
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
 * @param integer $recursive The number of levels deep to fetch associated records
 * @return array Array of records
 * @access public
 * @link http://book.cakephp.org/view/449/find
 */
	function find($conditions = null, $fields = array(), $order = null, $recursive = null) {
		if (!is_string($conditions) || (is_string($conditions) && !array_key_exists($conditions, $this->_findMethods))) {
			$type = 'first';
			$query = array_merge(compact('conditions', 'fields', 'order', 'recursive'), array('limit' => 1));
		} else {
			list($type, $query) = array($conditions, $fields);
		}

		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$this->findQueryType = $type;
		$this->id = $this->getID();

		$query = array_merge(
			array(
				'conditions' => null, 'fields' => null, 'joins' => array(), 'limit' => null,
				'offset' => null, 'order' => null, 'page' => null, 'group' => null, 'callbacks' => true
			),
			(array)$query
		);

		if ($type != 'all') {
			if ($this->_findMethods[$type] === true) {
				$query = $this->{'_find' . ucfirst($type)}('before', $query);
			}
		}

		if (!is_numeric($query['page']) || intval($query['page']) < 1) {
			$query['page'] = 1;
		}
		if ($query['page'] > 1 && !empty($query['limit'])) {
			$query['offset'] = ($query['page'] - 1) * $query['limit'];
		}
		if ($query['order'] === null && $this->order !== null) {
			$query['order'] = $this->order;
		}
		$query['order'] = array($query['order']);

		if ($query['callbacks'] === true || $query['callbacks'] === 'before') {
			$return = $this->Behaviors->trigger($this, 'beforeFind', array($query), array(
				'break' => true, 'breakOn' => false, 'modParams' => true
			));
			$query = (is_array($return)) ? $return : $query;

			if ($return === false) {
				return null;
			}

			$return = $this->beforeFind($query);
			$query = (is_array($return)) ? $return : $query;

			if ($return === false) {
				return null;
			}
		}

		$results = $db->read($this, $query);
		$this->resetAssociations();
		$this->findQueryType = null;

		if ($query['callbacks'] === true || $query['callbacks'] === 'after') {
			$results = $this->__filterResults($results);
		}

		if ($type === 'all') {
			return $results;
		} else {
			if ($this->_findMethods[$type] === true) {
				return $this->{'_find' . ucfirst($type)}('after', $query, $results);
			}
		}
	}
/**
 * Handles the before/after filter logic for find('first') operations.  Only called by Model::find().
 *
 * @param string $state Either "before" or "after"
 * @param array $query
 * @param array $data
 * @return array
 * @access protected
 * @see Model::find()
 */
	function _findFirst($state, $query, $results = array()) {
		if ($state == 'before') {
			$query['limit'] = 1;
			if (empty($query['conditions']) && !empty($this->id)) {
				$query['conditions'] = array($this->escapeField() => $this->id);
			}
			return $query;
		} elseif ($state == 'after') {
			if (empty($results[0])) {
				return false;
			}
			return $results[0];
		}
	}
/**
 * Handles the before/after filter logic for find('count') operations.  Only called by Model::find().
 *
 * @param string $state Either "before" or "after"
 * @param array $query
 * @param array $data
 * @return int The number of records found, or false
 * @access protected
 * @see Model::find()
 */
	function _findCount($state, $query, $results = array()) {
		if ($state == 'before') {
			$db =& ConnectionManager::getDataSource($this->useDbConfig);
			if (empty($query['fields'])) {
				$query['fields'] = $db->calculate($this, 'count');
			} elseif (is_string($query['fields'])  && !preg_match('/count/i', $query['fields'])) {
				$query['fields'] = $db->calculate($this, 'count', array(
					$db->expression($query['fields']), 'count'
				));
			}
			$query['order'] = false;
			return $query;
		} elseif ($state == 'after') {
			if (isset($results[0][0]['count'])) {
				return intval($results[0][0]['count']);
			} elseif (isset($results[0][$this->alias]['count'])) {
				return intval($results[0][$this->alias]['count']);
			}
			return false;
		}
	}
/**
 * Handles the before/after filter logic for find('list') operations.  Only called by Model::find().
 *
 * @param string $state Either "before" or "after"
 * @param array $query
 * @param array $data
 * @return array Key/value pairs of primary keys/display field values of all records found
 * @access protected
 * @see Model::find()
 */
	function _findList($state, $query, $results = array()) {
		if ($state == 'before') {
			if (empty($query['fields'])) {
				$query['fields'] = array("{$this->alias}.{$this->primaryKey}", "{$this->alias}.{$this->displayField}");
				$list = array("{n}.{$this->alias}.{$this->primaryKey}", "{n}.{$this->alias}.{$this->displayField}", null);
			} else {
				if (!is_array($query['fields'])) {
					$query['fields'] = String::tokenize($query['fields']);
				}

				if (count($query['fields']) == 1) {
					if (strpos($query['fields'][0], '.') === false) {
						$query['fields'][0] = $this->alias . '.' . $query['fields'][0];
					}

					$list = array("{n}.{$this->alias}.{$this->primaryKey}", '{n}.' . $query['fields'][0], null);
					$query['fields'] = array("{$this->alias}.{$this->primaryKey}", $query['fields'][0]);
				} elseif (count($query['fields']) == 3) {
					for ($i = 0; $i < 3; $i++) {
						if (strpos($query['fields'][$i], '.') === false) {
							$query['fields'][$i] = $this->alias . '.' . $query['fields'][$i];
						}
					}

					$list = array('{n}.' . $query['fields'][0], '{n}.' . $query['fields'][1], '{n}.' . $query['fields'][2]);
				} else {
					for ($i = 0; $i < 2; $i++) {
						if (strpos($query['fields'][$i], '.') === false) {
							$query['fields'][$i] = $this->alias . '.' . $query['fields'][$i];
						}
					}

					$list = array('{n}.' . $query['fields'][0], '{n}.' . $query['fields'][1], null);
				}
			}
			if (!isset($query['recursive']) || $query['recursive'] === null) {
				$query['recursive'] = -1;
			}
			list($query['list']['keyPath'], $query['list']['valuePath'], $query['list']['groupPath']) = $list;
			return $query;
		} elseif ($state == 'after') {
			if (empty($results)) {
				return array();
			}
			$lst = $query['list'];
			return Set::combine($results, $lst['keyPath'], $lst['valuePath'], $lst['groupPath']);
		}
	}
/**
 * Detects the previous field's value, then uses logic to find the 'wrapping'
 * rows and return them.
 *
 * @param string $state Either "before" or "after"
 * @param mixed $query
 * @param array $results
 * @return array
 * @access protected
 */
	function _findNeighbors($state, $query, $results = array()) {
		if ($state == 'before') {
			$query = array_merge(array('recursive' => 0), $query);
			extract($query);
			$conditions = (array)$conditions;
			if (isset($field) && isset($value)) {
				if (strpos($field, '.') === false) {
					$field = $this->alias . '.' . $field;
				}
			} else {
				$field = $this->alias . '.' . $this->primaryKey;
				$value = $this->id;
			}
			$query['conditions'] = 	array_merge($conditions, array($field . ' <' => $value));
			$query['order'] = $field . ' DESC';
			$query['limit'] = 1;
			$query['field'] = $field;
			$query['value'] = $value;
			return $query;
		} elseif ($state == 'after') {
			extract($query);
			unset($query['conditions'][$field . ' <']);
			$return = array();
			if (isset($results[0])) {
				$prevVal = Set::extract('/' . str_replace('.', '/', $field), $results[0]);
				$query['conditions'][$field . ' >='] = $prevVal[0];
				$query['conditions'][$field . ' !='] = $value;
				$query['limit'] = 2;
			} else {
				$return['prev'] = null;
				$query['conditions'][$field . ' >'] = $value;
				$query['limit'] = 1;
			}
			$query['order'] = $field . ' ASC';
			$return2 = $this->find('all', $query);
			if (!array_key_exists('prev', $return)) {
				$return['prev'] = $return2[0];
			}
			if (count($return2) == 2) {
				$return['next'] = $return2[1];
			} elseif (count($return2) == 1 && !$return['prev']) {
				$return['next'] = $return2[0];
			} else {
				$return['next'] = null;
			}
			return $return;
		}
	}
/**
 * In the event of ambiguous results returned (multiple top level results, with different parent_ids)
 * top level results with different parent_ids to the first result will be dropped
 *
 * @param mixed $state
 * @param mixed $query
 * @param array $results
 * @return array Threaded results
 * @access protected
 */
	function _findThreaded($state, $query, $results = array()) {
		if ($state == 'before') {
			return $query;
		} elseif ($state == 'after') {
			$return = $idMap = array();
			$ids = Set::extract($results, '{n}.' . $this->alias . '.' . $this->primaryKey);

			foreach ($results as $result) {
				$result['children'] = array();
				$id = $result[$this->alias][$this->primaryKey];
				$parentId = $result[$this->alias]['parent_id'];
				if (isset($idMap[$id]['children'])) {
					$idMap[$id] = array_merge($result, (array)$idMap[$id]);
				} else {
					$idMap[$id] = array_merge($result, array('children' => array()));
				}
				if (!$parentId || !in_array($parentId, $ids)) {
					$return[] =& $idMap[$id];
				} else {
					$idMap[$parentId]['children'][] =& $idMap[$id];
				}
			}
			if (count($return) > 1) {
				$ids = array_unique(Set::extract('/' . $this->alias . '/parent_id', $return));
				if (count($ids) > 1) {
					$root = $return[0][$this->alias]['parent_id'];
					foreach ($return as $key => $value) {
						if ($value[$this->alias]['parent_id'] != $root) {
							unset($return[$key]);
						}
					}
				}
			}
			return $return;
		}
	}
/**
 * Passes query results through model and behavior afterFilter() methods.
 *
 * @param array Results to filter
 * @param boolean $primary If this is the primary model results (results from model where the find operation was performed)
 * @return array Set of filtered results
 * @access private
 */
	function __filterResults($results, $primary = true) {
		$return = $this->Behaviors->trigger($this, 'afterFind', array($results, $primary), array('modParams' => true));
		if ($return !== true) {
			$results = $return;
		}
		return $this->afterFind($results, $primary);
	}
/**
 * Called only when bindTo<ModelName>() is used.
 * This resets the association arrays for the model back
 * to those originally defined in the model.
 *
 * @return boolean Success
 * @access public
 */
	function resetAssociations() {
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
					$this->{$key}->resetAssociations();
				}
			}
		}
		$this->__backAssociation = array();
		return true;
	}
/**
 * Returns false if any fields passed match any (by default, all if $or = false) of their matching values.
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
		if (!empty($this->id)) {
			$fields[$this->alias . '.' . $this->primaryKey . ' !='] =  $this->id;
		}
		return ($this->find('count', array('conditions' => $fields, 'recursive' => -1)) == 0);
	}
/**
 * Returns a resultset for a given SQL statement. Custom SQL queries should be performed with this method.
 *
 * @param string $sql SQL statement
 * @return array Resultset
 * @access public
 * @link http://book.cakephp.org/view/456/query
 */
	function query() {
		$params = func_get_args();
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		return call_user_func_array(array(&$db, 'query'), $params);
	}
/**
 * Returns true if all fields pass validation.
 *
 * @param string $options An optional array of custom options to be made available in the beforeValidate callback
 * @return boolean True if there are no errors
 * @access public
 * @link http://book.cakephp.org/view/410/Validating-Data-from-the-Controller
 */
	function validates($options = array()) {
		$errors = $this->invalidFields($options);
		if (is_array($errors)) {
			return count($errors) === 0;
		}
		return $errors;
	}
/**
 * Returns an array of fields that have failed validation.
 *
 * @param string $options An optional array of custom options to be made available in the beforeValidate callback
 * @return array Array of invalid fields
 * @access public
 * @link http://book.cakephp.org/view/410/Validating-Data-from-the-Controller
 */
	function invalidFields($options = array()) {
		if (
			!$this->Behaviors->trigger(
				$this,
				'beforeValidate',
				array($options),
				array('break' => true, 'breakOn' => false)
			) ||
			$this->beforeValidate($options) === false
		) {
			return $this->validationErrors;
		}

		if (!isset($this->validate) || empty($this->validate)) {
			return $this->validationErrors;
		}

		$data = $this->data;
		$methods = array_map('strtolower', get_class_methods($this));
		$behaviorMethods = array_keys($this->Behaviors->methods());

		if (isset($data[$this->alias])) {
			$data = $data[$this->alias];
		} elseif (!is_array($data)) {
			$data = array();
		}

		$Validation =& Validation::getInstance();
		$this->exists();

		$_validate = $this->validate;
		$whitelist = $this->whitelist;

		if (array_key_exists('fieldList', $options)) {
			$whitelist = $options['fieldList'];
		}

		if (!empty($whitelist)) {
			$validate = array();
			foreach ((array)$whitelist as $f) {
				if (!empty($this->validate[$f])) {
					$validate[$f] = $this->validate[$f];
				}
			}
			$this->validate = $validate;
		}

		foreach ($this->validate as $fieldName => $ruleSet) {
			if (!is_array($ruleSet) || (is_array($ruleSet) && isset($ruleSet['rule']))) {
				$ruleSet = array($ruleSet);
			}
			$default = array(
				'allowEmpty' => null,
				'required' => null,
				'rule' => 'blank',
				'last' => false,
				'on' => null
			);

			foreach ($ruleSet as $index => $validator) {
				if (!is_array($validator)) {
					$validator = array('rule' => $validator);
				}
				$validator = array_merge($default, $validator);

				if (isset($validator['message'])) {
					$message = $validator['message'];
				} else {
					$message = __('This field cannot be left blank', true);
				}

				if (
					empty($validator['on']) || ($validator['on'] == 'create' &&
					!$this->__exists) || ($validator['on'] == 'update' && $this->__exists
				)) {
					$required = (
						(!isset($data[$fieldName]) && $validator['required'] === true) ||
						(
							isset($data[$fieldName]) && (empty($data[$fieldName]) &&
							!is_numeric($data[$fieldName])) && $validator['allowEmpty'] === false
						)
					);

					if ($required) {
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

						if (in_array(strtolower($rule), $methods)) {
							$ruleParams[] = $validator;
							$ruleParams[0] = array($fieldName => $ruleParams[0]);
							$valid = $this->dispatchMethod($rule, $ruleParams);
						} elseif (in_array($rule, $behaviorMethods) || in_array(strtolower($rule), $behaviorMethods)) {
							$ruleParams[] = $validator;
							$ruleParams[0] = array($fieldName => $ruleParams[0]);
							$valid = $this->Behaviors->dispatchMethod($this, $rule, $ruleParams);
						} elseif (method_exists($Validation, $rule)) {
							$valid = $Validation->dispatchMethod($rule, $ruleParams);
						} elseif (!is_array($validator['rule'])) {
							$valid = preg_match($rule, $data[$fieldName]);
						}

						if (!$valid || (is_string($valid) && strlen($valid) > 0)) {
							if (is_string($valid) && strlen($valid) > 0) {
								$validator['message'] = $valid;
							} elseif (!isset($validator['message'])) {
								if (is_string($index)) {
									$validator['message'] = $index;
								} elseif (is_numeric($index) && count($ruleSet) > 1) {
									$validator['message'] = $index + 1;
								} else {
									$validator['message'] = $message;
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
		$this->validate = $_validate;
		return $this->validationErrors;
	}
/**
 * Marks a field as invalid, optionally setting the name of validation
 * rule (in case of multiple validation for field) that was broken.
 *
 * @param string $field The name of the field to invalidate
 * @param mixed $value Name of validation rule that was not failed, or validation message to
 *                     be returned. If no validation key is provided, defaults to true.
 * @access public
 */
	function invalidate($field, $value = true) {
		if (!is_array($this->validationErrors)) {
			$this->validationErrors = array();
		}
		$this->validationErrors[$field] = $value;
	}
/**
 * Returns true if given field name is a foreign key in this model.
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
 * Returns the display field for this model.
 *
 * @return string The name of the display field for this Model (i.e. 'name', 'title').
 * @access public
 * @deprecated
 */
	function getDisplayField() {
		return $this->displayField;
	}
/**
 * Escapes the field name and prepends the model name. Escaping is done according to the current database driver's rules.
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
		return $db->name($alias . '.' . $field);
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
/**
 * Returns the ID of the last record this model inserted.
 *
 * @return mixed Last inserted ID
 * @access public
 */
	function getLastInsertID() {
		return $this->getInsertID();
	}
/**
 * Returns the ID of the last record this model inserted.
 *
 * @return mixed Last inserted ID
 * @access public
 */
	function getInsertID() {
		return $this->__insertID;
	}
/**
 * Sets the ID of the last record this model inserted
 *
 * @param mixed Last inserted ID
 * @access public
 */
	function setInsertID($id) {
		$this->__insertID = $id;
	}
/**
 * Returns the number of rows returned from the last query.
 *
 * @return int Number of rows
 * @access public
 */
	function getNumRows() {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		return $db->lastNumRows();
	}
/**
 * Returns the number of rows affected by the last query.
 *
 * @return int Number of rows
 * @access public
 */
	function getAffectedRows() {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		return $db->lastAffected();
	}
/**
 * Sets the DataSource to which this model is bound.
 *
 * @param string $dataSource The name of the DataSource, as defined in app/config/database.php
 * @return boolean True on success
 * @access public
 */
	function setDataSource($dataSource = null) {
		$oldConfig = $this->useDbConfig;

		if ($dataSource != null) {
			$this->useDbConfig = $dataSource;
		}
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		if (!empty($oldConfig) && isset($db->config['prefix'])) {
			$oldDb =& ConnectionManager::getDataSource($oldConfig);

			if (!isset($this->tablePrefix) || (!isset($oldDb->config['prefix']) || $this->tablePrefix == $oldDb->config['prefix'])) {
				$this->tablePrefix = $db->config['prefix'];
			}
		} elseif (isset($db->config['prefix'])) {
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
 * Gets all the models with which this model is associated.
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
 * @access public
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
 * Called before each find operation. Return false if you want to halt the find
 * call, otherwise return the (modified) query data.
 *
 * @param array $queryData Data used to execute this query, i.e. conditions, order, etc.
 * @return mixed true if the operation should continue, false if it should abort; or, modified $queryData to continue with new $queryData
 * @access public
 * @link http://book.cakephp.org/view/680/beforeFind
 */
	function beforeFind($queryData) {
		return true;
	}
/**
 * Called after each find operation. Can be used to modify any results returned by find().
 * Return value should be the (modified) results.
 *
 * @param mixed $results The results of the find operation
 * @param boolean $primary Whether this model is being queried directly (vs. being queried as an association)
 * @return mixed Result of the find operation
 * @access public
 * @link http://book.cakephp.org/view/681/afterFind
 */
	function afterFind($results, $primary = false) {
		return $results;
	}
/**
 * Called before each save operation, after validation. Return a non-true result
 * to halt the save.
 *
 * @return boolean True if the operation should continue, false if it should abort
 * @access public
 * @link http://book.cakephp.org/view/683/beforeSave
 */
	function beforeSave($options = array()) {
		return true;
	}
/**
 * Called after each successful save operation.
 *
 * @param boolean $created True if this save created a new record
 * @access public
 * @link http://book.cakephp.org/view/684/afterSave
 */
	function afterSave($created) {
	}
/**
 * Called before every deletion operation.
 *
 * @param boolean $cascade If true records that depend on this record will also be deleted
 * @return boolean True if the operation should continue, false if it should abort
 * @access public
 * @link http://book.cakephp.org/view/685/beforeDelete
 */
	function beforeDelete($cascade = true) {
		return true;
	}
/**
 * Called after every deletion operation.
 *
 * @access public
 * @link http://book.cakephp.org/view/686/afterDelete
 */
	function afterDelete() {
	}
/**
 * Called during save operations, before validation. Please note that custom
 * validation rules can be defined in $validate.
 *
 * @return boolean True if validate operation should continue, false to abort
 * @param $options array Options passed from model::save(), see $options of model::save().
 * @access public
 * @link http://book.cakephp.org/view/682/beforeValidate
 */
	function beforeValidate($options = array()) {
		return true;
	}
/**
 * Called when a DataSource-level error occurs.
 *
 * @access public
 * @link http://book.cakephp.org/view/687/onError
 */
	function onError() {
	}
/**
 * Private method. Clears cache for this model.
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
				$assoc[] = strtolower(Inflector::underscore(Inflector::pluralize($this->alias)));
				foreach ($this->__associations as $key => $association) {
					foreach ($this->$association as $key => $className) {
						$check = strtolower(Inflector::pluralize($className['className']));
						if (!in_array($check, $assoc)) {
							$assoc[] = strtolower(Inflector::pluralize($className['className']));
							$assoc[] = strtolower(Inflector::underscore(Inflector::pluralize($className['className'])));
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
 * Called when serializing a model.
 *
 * @return array Set of object variable names this model has
 * @access private
 */
	function __sleep() {
		$return = array_keys(get_object_vars($this));
		return $return;
	}
/**
 * Called when de-serializing a model.
 *
 * @access private
 * @todo
 */
	function __wakeup() {
	}
/**
 * @deprecated
 * @see Model::find('all')
 */
	function findAll($conditions = null, $fields = null, $order = null, $limit = null, $page = 1, $recursive = null) {
		//trigger_error(__('(Model::findAll) Deprecated, use Model::find("all")', true), E_USER_WARNING);
		return $this->find('all', compact('conditions', 'fields', 'order', 'limit', 'page', 'recursive'));
	}
/**
 * @deprecated
 * @see Model::find('count')
 */
	function findCount($conditions = null, $recursive = 0) {
		//trigger_error(__('(Model::findCount) Deprecated, use Model::find("count")', true), E_USER_WARNING);
		return $this->find('count', compact('conditions', 'recursive'));
	}
/**
 * @deprecated
 * @see Model::find('threaded')
 */
	function findAllThreaded($conditions = null, $fields = null, $order = null) {
		//trigger_error(__('(Model::findAllThreaded) Deprecated, use Model::find("threaded")', true), E_USER_WARNING);
		return $this->find('threaded', compact('conditions', 'fields', 'order'));
	}
/**
 * @deprecated
 * @see Model::find('neighbors')
 */
	function findNeighbours($conditions = null, $field, $value) {
		//trigger_error(__('(Model::findNeighbours) Deprecated, use Model::find("neighbors")', true), E_USER_WARNING);
		$query = compact('conditions', 'field', 'value');
		$query['fields'] = $field;
		if (is_array($field)) {
			$query['field'] = $field[0];
		}
		return $this->find('neighbors', $query);
	}
}
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	Overloadable::overload('Model');
}
?>