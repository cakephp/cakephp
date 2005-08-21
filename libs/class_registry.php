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
 * Copyright (c) 2005, CakePHP Authors/Developers
 *
 * Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com>
 *            Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The MIT License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.libs
 * @since        CakePHP v 0.9.2
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Class Collections.
 *
 * Long description for class
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      CakePHP v 0.9.2
 */
   class ClassRegistry
   {
 
/**
 * Enter description here...
 *
 * @var unknown_type
 * @access private
 */
   var $_objects = array();

/**
 * Enter description here...
 *
 * @return ClassRegistry instance
 */
   function &getInstance() {
       
       static $instance = array();
       if (!$instance)
       {
           $instance[0] =& new ClassRegistry; 
       }
       return $instance[0];
   }
   
/**
 * Enter description here...
 *
 * @param unknown_type $key
 * @param unknown_type $object
 */
   function addObject($key, &$object)
   {
      $key = strtolower($key);
      
      if (array_key_exists($key, $this->_objects) === false)
      {
         $this->_objects[$key] =& $object;
      }
   }
   
/**
 * Enter description here...
 *
 * @param unknown_type $key
 * @return unknown
 */
   function isKeySet($key)
   {
      $key = strtolower($key);
      return array_key_exists($key, $this->_objects);
   }

/**
 * Enter description here...
 *
 * @param unknown_type $key
 * @return unknown
 */
   function &getObject($key)
   {
      $key = strtolower($key);
      return $this->_objects[$key];
   }

   
   }
   
?>