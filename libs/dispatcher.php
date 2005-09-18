<?php
/* SVN FILE: $Id$ */

/**
 * Dispatcher takes the URL information, parses it for paramters and 
 * tells the involved controllers what to do.
 * 
 * This is the heart of Cake's operation. 
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
 * Add Description
 */
define('DISPATCH_NO_CONTROLLER',      'missingController');
define('DISPATCH_UNKNOWN_CONTROLLER', 'missingController');
define('DISPATCH_NO_ACTION',          'missingAction');
define('DISPATCH_UNKNOWN_ACTION',     'missingAction');
define('DISPATCHER_UNKNOWN_VIEW',     'missingView');

/**
 * Add Description
 */
uses('error_messages', 'object', 'router', 'controller', 'scaffold');

/**
 * Short description for class.
 * 
 * Dispatches the request, creating appropriate models and controllers.
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      CakePHP v 0.2.9
 */
class Dispatcher extends Object
{
/**
 * Base URL
 * @var string
 */
   var $base = false;

/**
 * Constructor.
 */
   function __construct()
   {
      $this->base = $this->baseUrl();
      parent::__construct();
   }


/**
 * Dispatches and invokes given URL, handing over control to the involved controllers, and then renders the results (if autoRender is set).
 *
 * If no controller of given name can be found, invoke() shows error messages in 
 * the form of Missing Controllers information. It does the same with Actions (methods of Controllers are called 
 * Actions).
 *
 * @param string $url	URL information to work on.
 * @return boolean		Success
 */
   function dispatch($url)
   {
      $params = $this->parseParams($url);
      $missingController = false;
      $missingAction     = false;
      $missingView       = false;

      if (empty($params['controller']))
      {
         $missingController = true;
      }
      else
      {
         $ctrlName  = Inflector::camelize($params['controller']);
         $ctrlClass = $ctrlName.'Controller';

         if (!loadController($ctrlName) || !class_exists($ctrlClass))
         {
            $missingController = true;
         }
      }

      if ($missingController)
      {
         $ctrlClass        = 'AppController';
         $controller       = new $ctrlClass($this);
         $params['action'] = 'missingController';
         $params['controller'] = Inflector::camelize($params['controller']."Controller");
         $controller->missingController = $params['controller'];
      }
      else
      {
         $controller = new $ctrlClass($this);
      }

      if (empty($params['action']))
      {
         if (method_exists($controller, 'index'))
         {
            $params['action'] = 'index';
         }
         else
         {
            $missingAction = true;
         }
      }

      if (!method_exists($controller, $params['action']))
      {
         $missingAction = true;
      }
      
      $controller->base        = $this->base;
      $controller->here        = $this->base.'/'.$url;
      $controller->params      = $params;
      $controller->action      = $params['action'];
      $controller->data        = empty($params['data'])? null: $params['data'];
      $controller->passed_args = empty($params['pass'])? null: $params['pass'];
      
      foreach (get_object_vars($controller) as $name => $value)
      {
          if(($name === 'scaffold' && $missingAction === true) 
              || ($name === 'scaffold' && !empty($params['action'])))
          {
              if (!method_exists($controller, $params['action']))
              { 
                  if(empty($params['action']))
                  {
                      $params['action'] = 'index';
                  }
                  $this->scaffoldView($url, $controller, $params);
                  exit;
              }
          }
      }
      
      $controller->constructClasses();
      
      if ($missingAction)
      {
          $params['action'] = 'missingAction';
          $controller->missingAction = $params['action'];
      }

      call_user_func_array(array(&$controller, $params['action']), empty($params['pass'])? null: $params['pass']);

      $isFatal = isset($controller->isFatal) ? $controller->isFatal : false;

      if ($isFatal)
      {
         switch($params['action'])
         {
            case 'missingController':
            $this->errorUnknownController($url, $ctrlName);
            break;

            case 'missingAction':
            $this->errorUnknownAction($url, $ctrlClass, $controller->missingAction);
            break;
         }
      }

      if ($controller->autoRender)
      {
         $controller->render();
      }

      return true;
   }

/**
 * Returns array of GET and POST parameters. GET parameters are taken from given URL.
 *
 * @param string $from_url	URL to mine for parameter information.
 * @return array Parameters found in POST and GET.
 */
   function parseParams($from_url)
   {
      // load routes config
      $Route = new Router();
      include CONFIGS.'routes.php';
      $params = $Route->parse ($from_url);

      // add submitted form data
      $params['form'] = $_POST;
      if (isset($_POST['data']))
      {
         $params['data'] = (ini_get('magic_quotes_gpc') == 1)?
         	$this->stripslashes_deep($_POST['data']) : $_POST['data'];
      }
      if (isset($_GET))
      {
         $params['url'] = $this->urldecode_deep($_GET);
         $params['url'] = (ini_get('magic_quotes_gpc') == 1)?
         	$this->stripslashes_deep($params['url']) : $params['url'];
      }

      foreach ($_FILES as $name => $data)
      {
         $params['form'][$name] = $data;
      }

      return $params;
   }

/**
 * Recursively strips slashes.
 *
 */
   function stripslashes_deep($val)
   {
      return (is_array($val)) ? 
        array_map(array('Dispatcher','stripslashes_deep'), $val) : stripslashes($val);
   }

/**
 * Recursively performs urldecode.
 *
 */
   function urldecode_deep($val)
   {
      return (is_array($val)) ? 
        array_map(array('Dispatcher','urldecode_deep'), $val) : urldecode($val);
   }

/**
 * Returns a base URL.
 *
 * @return string	Base URL
 */
   function baseUrl()
   {

      //non mod_rewrite use:
      if (defined('BASE_URL')) return BASE_URL;

      $doc_root = $_SERVER['DOCUMENT_ROOT'];
      $script_name = $_SERVER['PHP_SELF'];

      // if document root ends with 'public', it's probably correctly set
      $r = null;
      if (ereg('/^.*/public(\/)?$/', $doc_root))
      return preg_match('/^(.*)\/index\.php$/', $script_name, $r)? $r[1]: false;
      else
      // document root is probably not set to Cake 'public' dir
      return preg_match('/^(.*)\/public\/index\.php$/', $script_name, $r)? $r[1]: false;
   }

/**
 * Displays an error page (e.g. 404 Not found).
 *
 * @param int $code 	Error code (e.g. 404)
 * @param string $name 	Name of the error message (e.g. Not found)
 * @param string $message
 */
   function error ($code, $name, $message)
   {
      $controller = new Controller ($this);
      $controller->base = $this->base;
      $controller->error($code, $name, $message);
   }

/**
 * Convenience method to display a 404 page.
 *
 * @param string $url 		URL that spawned this message, to be included in the output.
 * @param string $message 	Message text for the 404 page.
 */
   function error404 ($url, $message)
   {
      $this->error('404', 'Not found', sprintf(ERROR_404, $url, $message));
   }

/**
 * If DEBUG is set, this displays a 404 error with the message that no controller is set. 
 * If DEBUG is not set, nothing happens.
 *
 * @param string $url	URL that spawned this message, to be included in the output.
 */
   function errorNoController ($url)
   {
      DEBUG?
      trigger_error (ERROR_NO_CONTROLLER_SET, E_USER_ERROR):
      $this->error404($url, "no controller set");
      exit;
   }

/**
 * If DEBUG is set, this displays a 404 error with the message that the asked-for controller does not exist. If DEBUG is not set, nothing happens.
 *
 * @param string $url	URL that spawned this message, to be included in the output.
 * @param string $controller_class
 */
   function errorUnknownController ($url, $controller_class)
   {
      DEBUG?
      trigger_error (sprintf(ERROR_UNKNOWN_CONTROLLER, $controller_class), E_USER_ERROR):
      $this->error404($url, "missing controller \"{$controller_class}\"");
      exit;
   }

/**
 * If DEBUG is set, this displays a 404 error with the message that no action is set. If DEBUG is not set, nothing happens.
 *
 * @param string $url	URL that spawned this message, to be included in the output.
 */
   function errorNoAction ($url)
   {
      DEBUG?
      trigger_error (ERROR_NO_ACTION_SET, E_USER_ERROR):
      $this->error404(sprintf(ERROR_404, $url, "no action set"));
      exit;
   }

/**
 * If DEBUG is set, this displays a 404 error with the message that no such action exists. If DEBUG is not set, nothing happens.
 *
 * @param string $url	URL that spawned this message, to be included in the output.
 * @param string $controller_class
 * @param string $action
 */
   function errorUnknownAction ($url,$controller_class, $action)
   {
      DEBUG?
      trigger_error (sprintf(ERROR_NO_ACTION, $action, $controller_class), E_USER_ERROR):
      $this->error404($url, "missing controller \"{$controller_class}\"");
      exit;
   }

/**
  * When methods are now present in a controller
  * scaffoldView is used to call default Scaffold methods if:
  * <code>
  * var $scaffold;
  * </code>
  * is placed in the controller's class definition.
  *
  * @param string $url
  * @param string $controller_class
  * @param array $params
  * @since Cake v 0.10.0.172
  */
   function scaffoldView ($url, &$controller_class, $params)
   {
       $isDataBaseSet = DboFactory::getInstance($controller_class->useDbConfig);
       if(!empty($isDataBaseSet))
       {
       if($params['action'] === 'index'  || $params['action'] === 'list' ||
         $params['action'] === 'show'   || $params['action'] === 'new' || 
         $params['action'] === 'create' || $params['action'] === 'edit' ||  
         $params['action'] === 'update' || $params['action'] === 'destroy')
         {
            $scaffolding =& new Scaffold($controller_class);
            
            switch ($params['action'])
            {
               case 'index':
               $scaffolding->scaffoldIndex($params);
               break;
               
               case 'show':
               $scaffolding->scaffoldShow($params);
               break;
			
               case 'list':
               $scaffolding->scaffoldList($params);
               break;
   					
               case 'new':
               $scaffolding->scaffoldNew($params);
               break;
               
               case 'edit':
               $scaffolding->scaffoldEdit($params);
               break;
   								
               case 'create':
               $scaffolding->scaffoldCreate($params);
               break;
   			
               case 'update':
               $scaffolding->scaffoldUpdate($params);
               break;
   			
               case 'destroy':
               $scaffolding->scaffoldDestroy($params);
               break;
            }
         } 
         else
         {
             $controller_class->missingAction = $params['action'];
             call_user_func_array(array(&$controller_class, 'missingAction'), null);
         }
         exit;
       }
       else
       {
           call_user_func_array(array(&$controller_class, 'missingDatabase'), null);
       }
   }
}
?>