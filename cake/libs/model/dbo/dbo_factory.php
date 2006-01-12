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
 * Copyright (c) 2005, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs.model.dbo
 * @since        CakePHP v 0.10.0.1076
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Enter description here...
 *
 */
if(!class_exists('Object'))
{
    uses('object');
}
/**
 * Enter description here...
 *
 */
if (!class_exists('DATABASE_CONFIG'))
{
    config('database');
}

/**
 * DbFactory
 *
 * Creates DBO-descendant objects from a given db connection configuration
 *
 * @package    cake
 * @subpackage cake.cake.libs.model.dbo
 * @since      CakePHP v 0.10.0.1076
 *
 */
class DboFactory extends Object
{
/**
 * A semi-singelton. Returns actual instance, or creates a new one with given config.
 *
 * @param string $config Name of key of $dbConfig array to be used.
 * @return mixed
 */
   function getInstance($config = null)
   {
       $configName = $config;
       static $instance = array();
       if ($configName == null && !empty($instance))
       {
           return $instance["default"];
       }
       else if ($configName == null && empty($instance))
       {
           return false;
       }

       if (!key_exists($configName, $instance))
       {
           $configs = get_class_vars('DATABASE_CONFIG');
           $config  = $configs[$configName];

           // special case for AdoDB -- driver name in the form of 'adodb-drivername'
           if (preg_match('#^adodb[\-_](.*)$#i', $config['driver'], $res))
           {
               uses('model'.DS.'dbo'.DS.'dbo_adodb');
               $config['driver'] = $res[1];
               $instance[$configName] =& new DBO_AdoDB($config);
           }
           // special case for PEAR:DB -- driver name in the form of 'pear-drivername'
           elseif (preg_match('#^pear[\-_](.*)$#i', $config['driver'], $res))
           {
               uses('model'.DS.'dbo'.DS.'dbo_pear');
               $config['driver'] = $res[1];
               $instance[$configName] =& new DBO_Pear($config);
           }
           // regular, Cake-native db drivers
           else
           {
               $db_driver_class = 'DBO_'.$config['driver'];
               $db_driver_fn = LIBS.strtolower('model'.DS.'dbo'.DS.$db_driver_class.'.php');
               if (file_exists($db_driver_fn))
               {
                   uses(strtolower('model'.DS.'dbo'.DS.$db_driver_class));
                   $instance[$configName] =& new $db_driver_class($config);
               }
               else
               {
                   return false;
               }
           }
       }
       return $instance[$configName];
   }

/**
 * Sets config to use. If there is already a connection, close it first.
 *
 * @param string $configName Name of the config array key to use.
 * @return mixed
 */
   function setConfig($config)
   {
       $db = DboFactory::getInstance();
       if ($db->isConnected() === true)
       {
           $db->close();
       }
       return $this->getInstance($config);
   }
}

?>