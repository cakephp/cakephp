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
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * Enter description here...
  */
uses('log');

/**
 * Object class, allowing __construct and __destruct in PHP4.
 *
 * Long description for class
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      CakePHP v 0.2.9
 */
class Object
{

/**
 * Log object
 *
 * @var object
 */
   var $_log = null;

/**
 * A hack to support __construct() on PHP 4
 * Hint: descendant classes have no PHP4 class_name() constructors,
 * so this constructor gets called first and calls the top-layer __construct()
 * which (if present) should call parent::__construct()
 *
 * @return Object
 */
   function Object()
   {
      $args = func_get_args();
      register_shutdown_function(array(&$this, '__destruct'));
      call_user_func_array(array(&$this, '__construct'), $args);
   }

/**
 * Class constructor, overridden in descendant classes.
 */
   function __construct()
   {
   }

/**
 * Class destructor, overridden in descendant classes.
 */
   function __destruct()
   {
   }

/**
 * Object-to-string conversion.
 * Each class can override it as necessary.
 *
 * @return string This name of this class
 */
   function toString()
   {
      return get_class($this);
   }

/**
 * 
 * Allow calling a controllers method from any location
 * 
 *
 * @param unknown_type $url
 * @param unknown_type $extra
 * @return unknown
 */
    function requestAction ($url, $extra = array())
    {
        if(in_array('render', $extra))
        {
            $extra['render'] = 0;
        }
        else
        {
          $extra['render'] = 1; 
        }
        $extra = array_merge($extra, array('bare'=>1));
        $dispatcher =& new Dispatcher();
        return $dispatcher->dispatch($url, $extra);
    }
    
/**
 * API for logging events.
 *
 * @param string $msg Log message
 * @param int $type Error type constant. Defined in /libs/log.php.
 */
   function log ($msg, $type=LOG_ERROR)
   {
      if (is_null($this->_log))
      {
         $this->_log = new Log ();
      }

      switch ($type)
      {
         case LOG_DEBUG:
            return $this->_log->write('debug', $msg);
         default:
            return $this->_log->write('error', $msg);
      }
   }
}

?>