<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2006, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs.model
 * @since        CakePHP v 0.10.x.1402
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Manages loaded instances of DataSource objects
 *
 * Long description for file
 *
 * @package    cake
 * @subpackage cake.cake.libs.model
 * @since      CakePHP v 0.10.x.1402
 *
 */

uses('model'.DS.'datasources'.DS.'datasource');
class ConnectionManager extends Object
{

/**
 * Holds a loaded instance of the Connections object
 *
 * @var class:Connections
 * @access public
 */
  var $config = null;

/**
 * Holds instances DataSource objects
 *
 * @var array
 * @access private
 */
  var $_dataSources = array();

/**
 * Constructor.
 *
 */
  function __construct()
  {
      if(class_exists('DATABASE_CONFIG'))
      {
          $this->config = new DATABASE_CONFIG();
      }
  }

/**
 * Gets a reference to the ConnectionManger object instance
 *
 * @return object
 */
  function &getInstance()
  {
     static $instance = null;
     if($instance == null)
     {
        $instance =& new ConnectionManager();
     }
     return $instance;
  }

/**
 * Gets a reference to a DataSource object
 *
 * @param string $name The name of the DataSource, as defined in app/config/connections
 * @return object
 */
  function &getDataSource($name)
  {
     $_this =& ConnectionManager::getInstance();

     if(in_array($name, array_keys($_this->_dataSources)))
     {
        return $_this->_dataSources[$name];
     }

     if(in_array($name, array_keys(get_object_vars($_this->config))))
     {
        $config = $_this->config->{$name};

        if(isset($config['driver']) && $config['driver'] != null && $config['driver'] != '')
        {
           $filename = 'dbo_'.$config['driver'];
           $classname = Inflector::camelize(strtolower('DBO_'.$config['driver']));
        }
        else
        {
           $filename = $config['datasource'].'_source';
           $classname = Inflector::camelize(strtolower($config['datasource'].'_source'));
        }

        $tail = 'dbo'.DS.$filename.'.php';
        if (file_exists(LIBS.'model'.DS.$tail))
        {
            require_once(LIBS.'model'.DS.$tail);
        }
        else if (file_exists(MODELS.$tail))
        {
            require_once(MODELS.$tail);
        }
        else
        {
            trigger_error('Unable to load model file ' . $filename . '.php', E_USER_ERROR);
            return null;
        }
        $_this->_dataSources[$name] =& new $classname($config);
        $_this->_dataSources[$name]->configKeyName = $name;
     }
     else
     {
        trigger_error("ConnectionManager::getDataSource - Non-existent data source {$name}", E_USER_ERROR);
        return null;
     }
     return $_this->_dataSources[$name];
  }
}

?>