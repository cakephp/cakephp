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
   function dispatch($url, $additionalParams=array())
   {
      $params = array_merge($this->parseParams($url), $additionalParams);
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
      
      $classMethods = get_class_methods($controller);
      $classVars = get_object_vars($controller);
      
      if (empty($params['action']))
      {
          $params['action'] = 'index';
      }
      
      if(!in_array($params['action'], $classMethods))
      {
          $missingAction = true;
      }
      
      $controller->base        = $this->base;
      $controller->here        = $this->base.'/'.$url;
      $controller->params      = $params;
      $controller->action      = $params['action'];
      $controller->data        = empty($params['data'])? null: $params['data'];
      $controller->passed_args = empty($params['pass'])? null: $params['pass'];
      $controller->viewpath = Inflector::underscore($ctrlName);
      $controller->autoLayout = !$params['bare'];

      if((in_array('scaffold', array_keys($classVars))) && ($missingAction === true))
      {
          $this->scaffoldView($url, $controller, $params);
          exit;
      }

      $controller->constructClasses();
      
      if ($missingAction)
      {
          $controller->missingAction = $params['action'];
          $params['action'] = 'missingAction';
      }
      
      return $this->_invoke($controller, $params );
   }
   
/**
 * Enter description here...
 *
 * @param unknown_type $controller
 * @param unknown_type $params
 * @return unknown
 */
   function _invoke (&$controller, $params )
   {
       
       $output = call_user_func_array(array(&$controller, $params['action']), empty($params['pass'])? null: $params['pass']);
       if ($controller->autoRender)
       {
           $controller->render();
           exit;
       }
       return $output;
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
       $params['bare'] = empty($params['ajax'])? (empty($params['bare'])? 0: 1): 1;
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
 * @return unknown
 */
   function error ($code, $name, $message)
	{
        $controller = new Controller ($this);
        $controller->base = $this->base;
        $controller->autoLayout = false;
        $controller->set(array('code'=>$code, 'name'=>$name, 'message'=>$message));
		return $controller->render('layouts/error');
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
   function scaffoldView ($url, &$controllerClass, $params)
   {
       $isDataBaseSet = DboFactory::getInstance($controllerClass->useDbConfig);
       if(!empty($isDataBaseSet))
       {
       if($params['action'] === 'index'  || $params['action'] === 'list' ||
         $params['action'] === 'show'   || $params['action'] === 'new' || 
         $params['action'] === 'create' || $params['action'] === 'edit' ||  
         $params['action'] === 'update' || $params['action'] === 'destroy')
         {
            $scaffolding =& new Scaffold($controllerClass);
            
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
             $controllerClass->missingAction = $params['action'];
             call_user_func_array(array(&$controllerClass, 'missingAction'), null);
         }
         exit;
       }
       else
       {
           call_user_func_array(array(&$controllerClass, 'missingDatabase'), null);
       }
   }
}
?>