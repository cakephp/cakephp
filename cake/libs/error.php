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
 * @subpackage   cake.cake.libs
 * @since        CakePHP v 0.10.5.1732
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package    cake
 * @subpackage cake.cake.libs
 * @since      CakePHP v 0.10.5.1732
 */
class ErrorHandler extends Object
{
    var $controller = null;


/**
 * Class constructor.
 */
    function __construct($method, $messages)
    {
        parent::__construct();
        static $__previousError = null;

        if ($__previousError != array($method, $messages))
        {
            if(!class_exists('AppController'))
            {
                loadController(null);
            }
            $this->controller =& new AppController();
        }
        else
        {
            $this->controller =& new Controller();
        }
        $__previousError = array($method, $messages);

        if(DEBUG > 0 || $method == 'error')
        {
            call_user_func_array(array(&$this, $method), $messages);
        }
        else
        {
            call_user_func_array(array(&$this, 'error404'), $messages);
        }
    }

/**
 * Displays an error page (e.g. 404 Not found).
 *
 * @param int $code     Error code (e.g. 404)
 * @param string $name  Name of the error message (e.g. Not found)
 * @param string $message
 * @return unknown
 */
    function error ($params)
    {
        extract($params);
        $this->controller->webroot = $this->_webroot();
        $this->controller->viewPath = 'errors';
        $this->controller->set(array('code'=>$code,
                                     'name'=>$name,
                                     'message'=>$message,
                                     'title' => $code.' '. $name));
        $this->controller->render('error404');
        exit();
    }

/**
 * Convenience method to display a 404 page.
 *
 * @param string $url         URL that spawned this message, to be included in the output.
 * @param string $message     Message text for the 404 page.
 */
    function error404 ($params)
    {
        extract($params);
        if(!isset($url))
        {
            $url = $action;
        }
        if(!isset($message))
        {
            $message = '';
        }

        header("HTTP/1.0 404 Not Found");
        $this->error(
            array('code'	=> '404',
                  'name'	=> 'Not found',
                  'message'	=> sprintf(__("The requested address %s was not found on this server.", true), $url, $message)
                 )
        );
        exit();
    }

/**
 * Renders the Missing Controller web page.
 *
 */
    function missingController($params)
    {
        extract($params);
        $this->controller->webroot = $webroot;
        $this->controller->set(array('controller' => $className,
                                     'title' => 'Missing Controller'));
        $this->controller->render('../errors/missingController');
        exit();
    }

/**
 * Renders the Missing Action web page.
 *
 */
    function missingAction($params)
    {
        extract($params);
        $this->controller->webroot = $webroot;
        $this->controller->set(array('controller' => $className,
                                     'action' => $action,
                                     'title' => 'Missing Method in Controller'));
        $this->controller->render('../errors/missingAction');
        exit();
    }

/**
 * Renders the Private Action web page.
 *
 */
    function privateAction($params)
    {
        extract($params);
        $this->controller->webroot = $webroot;
        $this->controller->set(array('controller' => $className,
                                     'action' => $action,
                                     'title' => 'Trying to access private method in class'));
        $this->controller->render('../errors/privateAction');
        exit();
    }

/**
 * Renders the Missing Table web page.
 *
 */
    function missingTable($params)
    {
        extract($params);
        $this->controller->webroot = $this->_webroot();
        $this->controller->set(array('model' => $className,
                                     'table' => $table,
                                     'title' => 'Missing Database Table'));
        $this->controller->render('../errors/missingTable');
        exit();
    }

/**
 * Renders the Missing Database web page.
 *
 */
    function missingDatabase($params = array())
    {
        extract($params);
        $this->controller->webroot = $this->_webroot();
        $this->controller->set(array('title' => 'Scaffold Missing Database Connection'));
        $this->controller->render('../errors/missingScaffolddb');
        exit();
    }

/**
 * Renders the Missing View web page.
 *
 */
    function missingView($params)
    {
        extract($params);
        $this->controller->webroot = $this->_webroot();
        $this->controller->set(array('controller' => $className,
                                     'action' => $action,
                                     'file' => $file,
                                     'title' => 'Missing View'));
        $this->controller->render('../errors/missingView');
        exit();
    }

/**
 * Renders the Missing Layout web page.
 *
 */
    function missingLayout($params)
    {
        extract($params);
        $this->controller->webroot = $this->_webroot();
        $this->controller->layout = 'default';
        $this->controller->set(array('file' => $file,
                                     'title' => 'Missing Layout'));
        $this->controller->render('../errors/missingLayout');
        exit();
    }

/**
 * Renders the Missing Table web page.
 *
 */
    function missingConnection($params)
    {
        extract($params);
        $this->controller->webroot = $this->_webroot();
        $this->controller->set(array('model' => $className,
                                     'title' => 'Missing Database Connection'));
        $this->controller->render('../errors/missingConnection');
        exit();
    }


/**
 * Renders the Missing Helper file web page.
 *
 */
    function missingHelperFile($params)
    {
        extract($params);
        $this->controller->webroot = $this->_webroot();
        $this->controller->set(array('helperClass' => Inflector::camelize($helper) . "Helper",
                                     'file' => $file,
                                     'title' => 'Missing Helper File'));
        $this->controller->render('../errors/missingHelperFile');
        exit();
    }

/**
 * Renders the Missing Helper class web page.
 *
 */
    function missingHelperClass($params)
    {
        extract($params);
        $this->controller->webroot = $this->_webroot();
        $this->controller->set(array('helperClass' => Inflector::camelize($helper) . "Helper",
                                     'file' => $file,
                                     'title' => 'Missing Helper Class'));
        $this->controller->render('../errors/missingHelperClass');
        exit();
    }

/**
 * Renders the Missing Component file web page.
 *
 */
    function missingComponentFile($params)
    {
        extract($params);
        $this->controller->webroot = $this->_webroot();
        $this->controller->set(array('controller' => $className,
                                     'component' => $component,
                                     'file' => $file,
                                     'title' => 'Missing Component File'));
        $this->controller->render('../errors/missingComponentFile');
        exit();
    }

/**
 * Renders the Missing Component class web page.
 *
 */
    function missingComponentClass($params)
    {
        extract($params);
        $this->controller->webroot = $this->_webroot();
        $this->controller->set(array('controller' => $className,
                                     'component' => $component,
                                     'file' => $file,
                                     'title' => 'Missing Component Class'));
        $this->controller->render('../errors/missingComponentClass');
        exit();
    }

/**
 * Renders the Missing Model class web page.
 *
 */
    function missingModel($params)
    {
        extract($params);
        $this->controller->webroot = $this->_webroot();
        $this->controller->set(array('model' => $className,
                                     'title' => 'Missing Model'));
        $this->controller->render('../errors/missingModel');
        exit();
    }



/**
 * Enter description here...
 *
 * @return unknown
 */
    function _webroot()
    {
        $dispatcher =& new Dispatcher();
        $dispatcher->baseUrl();
        return $dispatcher->webroot;
    }
}
?>