<?php
/* SVN FILE: $Id$ */
/**
 * Datasource connection manager
 *
 * Provides an interface for loading and enumerating connections defined in app/config/database.php
 *
 * PHP versions 4 and 5
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
 * @since         CakePHP(tm) v 0.10.x.1402
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses ('model' . DS . 'datasources' . DS . 'datasource');
config('database');

/**
 * Manages loaded instances of DataSource objects
 *
 * Long description for file
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model
 */
class ConnectionManager extends Object {
/**
 * Holds a loaded instance of the Connections object
 *
 * @var DATABASE_CONFIG
 * @access public
 */
	var $config = null;
/**
 * Holds instances DataSource objects
 *
 * @var array
 * @access protected
 */
	var $_dataSources = array();
/**
 * Contains a list of all file and class names used in Connection settings
 *
 * @var array
 * @access protected
 */
	var $_connectionsEnum = array();
/**
 * Constructor.
 *
 */
	function __construct() {
		if (class_exists('DATABASE_CONFIG')) {
			$this->config =& new DATABASE_CONFIG();
		}
	}
/**
 * Gets a reference to the ConnectionManger object instance
 *
 * @return object Instance
 * @access public
 * @static
 */
	function &getInstance() {
		static $instance = array();

		if (!$instance) {
			$instance[0] =& new ConnectionManager();
		}

		return $instance[0];
	}
/**
 * Gets a reference to a DataSource object
 *
 * @param string $name The name of the DataSource, as defined in app/config/database.php
 * @return object Instance
 * @access public
 * @static
 */
	function &getDataSource($name) {
		$_this =& ConnectionManager::getInstance();

		if (!empty($_this->_dataSources[$name])) {
			$return =& $_this->_dataSources[$name];
			return $return;
		}

		$connections = $_this->enumConnectionObjects();
		if (!empty($connections[$name])) {
			$conn = $connections[$name];
			$class = $conn['classname'];
			$_this->loadDataSource($name);
			$_this->_dataSources[$name] =& new $class($_this->config->{$name});
			$_this->_dataSources[$name]->configKeyName = $name;
		} else {
			trigger_error(sprintf(__("ConnectionManager::getDataSource - Non-existent data source %s", true), $name), E_USER_ERROR);
			return null;
		}

		$return =& $_this->_dataSources[$name];
		return $return;
	}
/**
 * Gets the list of available DataSource connections
 *
 * @return array List of available connections
 * @access public
 * @static
 */
	function sourceList() {
		$_this =& ConnectionManager::getInstance();
		return array_keys($_this->_dataSources);
	}
/**
 * Gets a DataSource name from an object reference
 *
 * @param object $source DataSource object
 * @return string Datasource name
 * @access public
 * @static
 */
	function getSourceName(&$source) {
		$_this =& ConnectionManager::getInstance();
		$names = array_keys($_this->_dataSources);
		for ($i = 0; $i < count($names); $i++) {
			if ($_this->_dataSources[$names[$i]] === $source) {
				return $names[$i];
			}
		}
		return null;
	}
/**
 * Loads the DataSource class for the given connection name
 *
 * @param mixed $connName A string name of the connection, as defined in app/config/database.php,
 *                        or an array containing the filename (without extension) and class name of the object,
 *                        to be found in app/models/datasources/ or cake/libs/model/datasources/.
 * @return boolean True on success, null on failure or false if the class is already loaded
 * @access public
 * @static
 */
	function loadDataSource($connName) {
		$_this =& ConnectionManager::getInstance();

		if (is_array($connName)) {
			$conn = $connName;
		} else {
			$connections = $_this->enumConnectionObjects();
			$conn = $connections[$connName];
		}

		if (!empty($conn['parent'])) {
			$_this->loadDataSource($conn['parent']);
		}

		if (class_exists($conn['classname'])) {
			return false;
		}

		if (file_exists(MODELS . 'datasources' . DS . $conn['filename'] . '.php')) {
			require (MODELS . 'datasources' . DS . $conn['filename'] . '.php');
		} elseif (fileExistsInPath(LIBS . 'model' . DS . 'datasources' . DS . $conn['filename'] . '.php')) {
			require (LIBS . 'model' . DS . 'datasources' . DS . $conn['filename'] . '.php');
		} else {
		    $error = __('Unable to load DataSource file %s.php', true);
			trigger_error(sprintf($error, $conn['filename']), E_USER_ERROR);
			return null;
		}
	}
/**
 * Gets a list of class and file names associated with the user-defined DataSource connections
 *
 * @return array An associative array of elements where the key is the connection name
 *               (as defined in Connections), and the value is an array with keys 'filename' and 'classname'.
 * @access public
 * @static
 */
	function enumConnectionObjects() {
		$_this =& ConnectionManager::getInstance();

		if (!empty($_this->_connectionsEnum)) {
			return $_this->_connectionsEnum;
		}
		$connections = get_object_vars($_this->config);

		if ($connections != null) {
			foreach ($connections as $name => $config) {
				$_this->_connectionsEnum[$name] = $_this->__getDriver($config);
			}
			return $_this->_connectionsEnum;
		} else {
			$_this->cakeError('missingConnection', array(array('className' => 'ConnectionManager')));
		}
	}
/**
 * Dynamically creates a DataSource object at runtime, with the given name and settings
 *
 * @param string $name The DataSource name
 * @param array $config The DataSource configuration settings
 * @return object A reference to the DataSource object, or null if creation failed
 * @access public
 * @static
 */
	function &create($name = '', $config = array()) {
		$_this =& ConnectionManager::getInstance();

		if (empty($name) || empty($config) || array_key_exists($name, $_this->_connectionsEnum)) {
			$null = null;
			return $null;
		}

		$_this->config->{$name} = $config;
		$_this->_connectionsEnum[$name] = $_this->__getDriver($config);
		$return =& $_this->getDataSource($name);
		return $return;
	}
/**
 * Returns the file, class name, and parent for the given driver.
 *
 * @return array An indexed array with: filename, classname, and parent
 * @access private
 */
	function __getDriver($config) {
		if (!isset($config['datasource'])) {
			$config['datasource'] = 'dbo';
		}

		if (isset($config['driver']) && $config['driver'] != null && !empty($config['driver'])) {
			$filename = $config['datasource'] . DS . $config['datasource'] . '_' . $config['driver'];
			$classname = Inflector::camelize(strtolower($config['datasource'] . '_' . $config['driver']));
			$parent = $this->__getDriver(array('datasource' => $config['datasource']));
		} else {
			$filename = $config['datasource'] . '_source';
			$classname = Inflector::camelize(strtolower($config['datasource'] . '_source'));
			$parent = null;
		}
		return array('filename'  => $filename, 'classname' => $classname, 'parent' => $parent);
	}
/**
 * Destructor.
 *
 * @access private
 */
	function __destruct() {
		if (Configure::read('Session.save') == 'database' && function_exists('session_write_close')) {
			session_write_close();
		}
	}
}
?>
