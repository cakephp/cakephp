<?php
/* SVN FILE: $Id$ */

/**
 * Object class, allowing __construct and __destruct in PHP4.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
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
 * @subpackage   cake.cake.libs
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Object class, allowing __construct and __destruct in PHP4.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
 *
 * @package    cake
 * @subpackage cake.cake.libs
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

       if (method_exists($this, '__destruct'))
       {
           register_shutdown_function(array(&$this, '__destruct'));
       }

       call_user_func_array(array(&$this, '__construct'), $args);
   }

/**
 * Class constructor, overridden in descendant classes.
 */
   function __construct()
   {
   }

/**
 * Object-to-string conversion.
 * Each class can override this method as necessary.
 *
 * @return string The name of this class
 */
   function toString()
   {
      return get_class($this);
   }

/**
 * Calls a controller's method from any location.
 *
 * @param string $url  URL in the form of Cake URL ("/controller/method/parameter")
 * @param array $extra If array includes the key "render" it sets the AutoRender to true.
 * @return boolean  Success
 */
    function requestAction ($url, $extra = array())
    {
        $dispatcher =& new Dispatcher();
        if(in_array('return', $extra))
        {
            $extra['return'] = 0;
            $extra['bare']   = 1;
                ob_start();
                $out = $dispatcher->dispatch($url, $extra);
                $out = ob_get_clean();
                return $out;
        }
        else
        {
            $extra['return'] = 1;
            $extra['bare']   = 1;
                return $dispatcher->dispatch($url, $extra);
        }
    }

/**
 * API for logging events.
 *
 * @param string $msg Log message
 * @param int $type Error type constant. Defined in /libs/log.php.
 */
   function log ($msg, $type=LOG_ERROR)
   {
       if(!class_exists('Log'))
       {
           uses('log');
       }
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

/**
 * Enter description here...
 *
 * @param unknown_type $method
 * @param unknown_type $messages
 * @return unknown
 */
   function cakeError($method, $messages)
   {
       if(!class_exists('ErrorHandler'))
       {
           uses('error');
       }
       return new ErrorHandler($method, $messages);
   }
}

?>