<?php
/* SVN FILE: $Id$ */

/**
 * Methods for displaying presentation data in the view.
 *
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
 * @subpackage   cake.cake.libs.view
 * @since        CakePHP v 0.10.0.1076
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Included libraries.
 */
uses(DS.'view'.DS.'helper');

/**
 * View, the V in the MVC triad.
 *
 * Class holding methods for displaying presentation data.
 *
 * @package      cake
 * @subpackage   cake.cake.libs.view
 * @since        CakePHP v 0.10.0.1076
 */
class View extends Object
{
/**
 * Name of the controller.
 *
 * @var string Name of controller
 * @access public
 */
    var $name = null;

/**
 * Stores the current URL (for links etc.)
 *
 * @var string Current URL
 */
    var $here = null;

/**
 * Not used. 2006-09
 *
 * @var unknown_type
 * @access public
 */
    var $parent = null;

/**
 * Action to be performed.
 *
 * @var string Name of action
 * @access public
 */
    var $action = null;

/**
 * An array of names of models the particular controller wants to use.
 *
 * @var mixed A single name as a string or a list of names as an array.
 * @access protected
 */
    var $uses = false;

/**
 * An array of names of built-in helpers to include.
 *
 * @var mixed A single name as a string or a list of names as an array.
 * @access protected
 */
    var $helpers = array('Html');

/**
 * Path to View.
 *
 * @var string Path to View
 */
    var $viewPath;

/**
 * Variables for the view
 *
 * @var array
 * @access private
 */
    var $_viewVars = array();

/**
 * Title HTML element of this View.
 *
 * @var boolean
 * @access private
 */
    var $pageTitle = false;

/**
 * An array of model objects.
 *
 * @var array Array of model objects.
 * @access public
 */
    var $models = array();

/**
 * Path parts for creating links in views.
 *
 * @var string Base URL
 * @access public
 */
    var $base = null;

/**
 * Name of layout to use with this View.
 *
 * @var string
 * @access public
 */
    var $layout = 'default';

/**
 * Turns on or off Cake's conventional mode of rendering views. On by default.
 *
 * @var boolean
 * @access public
 */
    var $autoRender = true;

/**
 * Turns on or off Cake's conventional mode of finding layout files. On by default.
 *
 * @var boolean
 * @access public
 */
    var $autoLayout = true;

/**
 * Array of parameter data
 *
 * @var array Parameter data
 */
    var $params;
/**
 * True when the view has been rendered.
 *
 * @var boolean
 */
    var $hasRendered = null;

/**
 * Reference to the Controller for this view.
 *
 * @var Controller
 */
    var $controller = null;

/**
 * Array of loaded view helpers.
 *
 * @var array
 */
    var $loaded = array();

/**
 * File extension. Defaults to Cake's conventional ".thtml".
 *
 * @var array
 */
    var $ext = '.thtml';

/**
 * Sub-directory for this view file.
 *
 * @var string
 */
    var $subDir = null;

/**
 * Enter description here... Themes. New in Cake RC4.
 *
 * @var array
 */
    var $themeWeb = null;

/**
 * Plugin name. A Plugin is a sub-application. New in Cake RC4.
 *
 * @link http://wiki.cakephp.org/docs:plugins
 * @var string
 */
    var $plugin = null;

/**
 * Constructor
 *
 * @return View
 */
    function __construct (&$controller)
    {
        if ($controller != null)
        {
            $this->controller    =& $controller;
            $this->_viewVars     =& $this->controller->_viewVars;
            $this->action        =& $this->controller->action;
            $this->autoLayout    =& $this->controller->autoLayout;
            $this->autoRender    =& $this->controller->autoRender;
            $this->base          =& $this->controller->base;
            $this->webroot       =& $this->controller->webroot;
            $this->helpers       =& $this->controller->helpers;
            $this->here          =& $this->controller->here;
            $this->layout        =& $this->controller->layout;
            $this->modelNames    =& $this->controller->modelNames;
            $this->name          =& $this->controller->name;
            $this->pageTitle     =& $this->controller->pageTitle;
            $this->parent        =& $this->controller->parent;
            $this->viewPath      =& $this->controller->viewPath;
            $this->params        =& $this->controller->params;
            $this->data          =& $this->controller->data;
            $this->displayFields =& $this->controller->displayFields;
            $this->webservices   =& $this->controller->webservices;
            $this->plugin        =& $this->controller->plugin;
        }
        parent::__construct();
    }

/**
 * Renders view for given action and layout. If $file is given, that is used
 * for a view filename (e.g. customFunkyView.thtml).
 *
 * @param string $action 	Name of action to render for
 * @param string $layout 	Layout to use
 * @param string $file 		Custom filename for view
 */
    function render($action=null, $layout=null, $file=null)
    {
        if (isset($this->hasRendered) && $this->hasRendered)
        {
            return true;
        }
        else
        {
            $this->hasRendered = false;
        }

        if (!$action)
        {
            $action = $this->action;
        }

        if ($layout)
        {
            $this->setLayout($layout);
        }

        if ($file)
        {
            $viewFileName = $file;
        }
        else
        {
            $viewFileName = $this->_getViewFileName($action);
        }

        if(!is_null($this->plugin) && is_null($file))
        {
            return $this->pluginView($action, $layout);
        }

        if (!is_file($viewFileName) && !fileExistsInPath($viewFileName) || $viewFileName === '/' || $viewFileName === '\\')
        {

            if (strpos($action, 'missingAction') !== false)
            {
                $errorAction = 'missingAction';
            }
            else
            {
                $errorAction = 'missingView';
            }

            foreach(array($this->name, 'errors') as $viewDir)
            {
                $errorAction =Inflector::underscore($errorAction);
                if(file_exists(VIEWS.$viewDir.DS.$errorAction.$this->ext))
                {
                    $missingViewFileName = VIEWS.$viewDir.DS.$errorAction.$this->ext;
                }
                elseif($missingViewFileName = fileExistsInPath(LIBS.'view'.DS.'templates'.DS.$viewDir.DS.$errorAction.'.thtml'))
                {

                }
                else
                {
                    $missingViewFileName = false;
                }

                $missingViewExists = is_file($missingViewFileName);
                if ($missingViewExists)
                {
                    break;
                }
            }

            if (strpos($action, 'missingView') === false)
            {
                return $this->cakeError('missingView',
                            array(array('className' => $this->controller->name,
                                        'action' => $action,
                                        'file' => $viewFileName)));

                $isFatal = isset($this->isFatal) ? $this->isFatal : false;
                if (!$isFatal)
                {
                    $viewFileName = $missingViewFileName;
                }
            }
            else
            {
                $missingViewExists = false;
            }

            if (!$missingViewExists || $isFatal)
            {
                if (DEBUG)
                {
                    trigger_error(sprintf(__("No template file for view %s (expected %s), create it first'"), $action, $viewFileName), E_USER_ERROR);
                }
                else
                {
                    $this->error('404', 'Not found', sprintf("The requested address %s was not found on this server.", '', "missing view \"{$action}\""));
                }
                die();
            }
        }

        if ($viewFileName && !$this->hasRendered)
        {
            if(substr($viewFileName, -5) === 'thtml')
            {
                $out = View::_render($viewFileName, $this->_viewVars);
            }
            else
            {
                $out = $this->_render($viewFileName, $this->_viewVars);
            }
            if ($out !== false)
            {
                if ($this->layout && $this->autoLayout)
                {
                    $out = $this->renderLayout($out);
                }
                print $out;
                $this->hasRendered = true;
            }
            else
            {
                $out = $this->_render($viewFileName, $this->_viewVars);
                trigger_error(sprintf(__("Error in view %s, got: <blockquote>%s</blockquote>"), $viewFileName, $out), E_USER_ERROR);
            }
            return true;
        }
    }

/**
 * Renders a piece of PHP with provided parameters and returns HTML, XML, or any other string.
 *
 * This realizes the concept of Elements, (or "partial layouts")
 * and the $params array is used to send data to be used in the
 * Element.
 *
 * @link http://wiki.cakephp.org/docs:view:renderelement
 * @param string $name 		Name of template file in the/app/views/elements/ folder
 * @param array $params 	Array of data to be made available to the for rendered view (i.e. the Element)
 * @return string 			Rendered output
 */
    function renderElement($name, $params=array())
    {
      $fn = ELEMENTS.$name.$this->ext;

      if(!is_null($this->plugin))
      {
          if(file_exists(APP.'plugins'.DS.$this->plugin.'views'.DS.'elements'.DS.$name.$this->ext))
          {
                $fn =  APP.'plugins'.DS.$this->plugin.'views'.DS.'elements'.DS.$name.$this->ext;
                $params = array_merge_recursive($params, $this->loaded);
                return $this->_render($fn, array_merge($this->_viewVars, $params), false);
            }
        }

      if (!file_exists($fn))
      {
         return "(Error rendering {$name})";
      }

      $params = array_merge_recursive($params, $this->loaded);
      return $this->_render($fn, array_merge($this->_viewVars, $params), false);
    }

    function element($name)
    {

    }

/**
 * Renders a layout. Returns output from _render(). Returns false on error.
 *
 * @param string $content_for_layout Content to render in a view, wrapped by the surrounding layout.
 * @return mixed Rendered output, or false on error
 */
    function renderLayout($content_for_layout)
    {
      $layout_fn = $this->_getLayoutFileName();

      if(DEBUG > 2 && $this->controller != null)
      {
          $debug = View::_render(LIBS.'view'.DS.'templates'.DS.'elements'.DS.'dump.thtml', array('controller' => $this->controller), false);
      }
      else
      {
          $debug = '';
      }

      if ($this->pageTitle !== false)
      {
          $pageTitle = $this->pageTitle;
      }
      else
      {
          $pageTitle = Inflector::humanize($this->viewPath);
      }

      $data_for_layout = array_merge($this->_viewVars, array('title_for_layout' => $pageTitle,
                                                             'content_for_layout'=> $content_for_layout,
                                                             'cakeDebug' => $debug));

      if (is_file($layout_fn))
      {
         $data_for_layout = array_merge($data_for_layout, $this->loaded);

          if(substr($layout_fn, -5) === 'thtml')
          {
              $out = View::_render($layout_fn, $data_for_layout, false);
          }
          else
          {
              $out = $this->_render($layout_fn, $data_for_layout, false);
          }

         if ($out === false)
         {
            $out = $this->_render($layout_fn, $data_for_layout);
            trigger_error(sprintf(__("Error in layout %s, got: <blockquote>%s</blockquote>"), $layout_fn, $out), E_USER_ERROR);
            return false;
         }
         else
         {
            return $out;
         }
      }
      else
      {
         return $this->cakeError('missingLayout',
                     array(array('layout' => $this->layout,
                                 'file' => $layout_fn)));
      }
    }

/**
 * Sets layout to be used when rendering.
 *
 * @param string $layout		Name of layout.
 */
    function setLayout($layout)
    {
      $this->layout = $layout;
    }

/**
 * Displays an error page to the user. Uses layouts/error.html to render the page.
 *
 * @param int $code 		HTTP Error code (for instance: 404)
 * @param string $name 		Name of the error (for instance: Not Found)
 * @param string $message 	Error message as a web page
 */
    function error ($code, $name, $message)
    {
      header ("HTTP/1.0 {$code} {$name}");
      print ($this->_render(VIEWS.'layouts/error.thtml', array('code'=>$code,'name'=>$name,'message'=>$message)));
    }


/**************************************************************************
 * Private methods.
 *************************************************************************/


/**
 * Returns filename of given action's template file (.thtml) as a string. CamelCased action names will be under_scored! This means that you can have LongActionNames that refer to long_action_names.thtml views.
 *
 * @param string $action Controller action to find template filename for
 * @return string Template filename
 * @access private
 */
    function _getViewFileName($action)
    {
        $action = Inflector::underscore($action);

        if(!is_null($this->webservices))
        {
            $type =    strtolower($this->webservices).DS;
        }
        else
        {
            $type = null;
        }
        $viewFileName = VIEWS.$this->viewPath.DS.$this->subDir.$type.$action.$this->ext;

        if(file_exists(VIEWS.$this->viewPath.DS.$this->subDir.$type.$action.$this->ext))
        {
            $viewFileName = VIEWS.$this->viewPath.DS.$this->subDir.$type.$action.$this->ext;
        }
        elseif(file_exists(VIEWS.'errors'.DS.$this->subDir.$type.$action.$this->ext))
        {
            $viewFileName = VIEWS.'errors'.DS.$this->subDir.$type.$action.$this->ext;
        }
        elseif($viewFileName = fileExistsInPath(LIBS.'view'.DS.'templates'.DS.'errors'.DS.$type.$action.'.thtml'))
        {

        }
        elseif($viewFileName = fileExistsInPath(LIBS.'view'.DS.'templates'.DS.$this->viewPath.DS.$type.$action.'.thtml'))
        {

        }

        $viewPath = explode(DS, $viewFileName);
        $i = array_search('..', $viewPath);
        unset($viewPath[$i-1]);
        unset($viewPath[$i]);
        $return = '/'.implode('/', $viewPath);
        return $return;
    }

/**
 * Returns layout filename for this template as a string.
 *
 * @return string Filename for layout file (.thtml).
 * @access private
 */
    function _getLayoutFileName()
    {
        if(isset($this->webservices) && !is_null($this->webservices))
        {
            $type =    strtolower($this->webservices).DS;
        }
        else
        {
            $type = null;
        }

        if(isset($this->plugin) && !is_null($this->plugin))
        {
            if(file_exists(APP.'plugins'.DS.$this->plugin.'views'.DS.'layouts'.DS.$this->layout.$this->ext))
            {
                $layoutFileName =  APP.'plugins'.DS.$this->plugin.'views'.DS.'layouts'.DS.$this->layout.$this->ext;
                return $layoutFileName;
            }
        }

        $layoutFileName = LAYOUTS.$type."{$this->layout}$this->ext";

        if(file_exists(LAYOUTS.$this->subDir.$type."{$this->layout}$this->ext"))
        {
            $layoutFileName = LAYOUTS.$this->subDir.$type."{$this->layout}$this->ext";
        }
        elseif($layoutFileName = fileExistsInPath(LIBS.'view'.DS.'templates'.DS."layouts".DS.$type."{$this->layout}.thtml"))
        {
        }
        return $layoutFileName;
    }

/**
 * Renders and returns output for given view filename with its
 * array of data.
 *
 * @param string $___viewFn Filename of the view
 * @param array $___dataForView Data to include in rendered view
 * @return string Rendered output
 * @access private
 */
    function _render($___viewFn, $___dataForView, $loadHelpers = true)
    {
        if ($this->helpers != false && $loadHelpers === true)
        {
            $loadedHelpers =  array();
            $loadedHelpers = $this->_loadHelpers($loadedHelpers, $this->helpers);

            foreach(array_keys($loadedHelpers) as $helper)
            {
                $replace = strtolower(substr($helper, 0, 1));
                $camelBackedHelper = preg_replace('/\\w/', $replace, $helper, 1);

                ${$camelBackedHelper} =& $loadedHelpers[$helper];

                if(isset(${$camelBackedHelper}->helpers) && is_array(${$camelBackedHelper}->helpers))
                {
                    foreach(${$camelBackedHelper}->helpers as $subHelper)
                    {
                        ${$camelBackedHelper}->{$subHelper} =& $loadedHelpers[$subHelper];
                    }
                }
                $this->loaded[$camelBackedHelper] = (${$camelBackedHelper});
            }
        }

        extract($___dataForView, EXTR_SKIP);
        $BASE        = $this->base;
        $params     = &$this->params;
        $page_title = $this->pageTitle;

        ob_start();
        if(DEBUG)
        {
            include($___viewFn);
        }
        else
        {
            @include($___viewFn);
        }
        $out = ob_get_clean();
        return $out;
    }

/**
 * Loads helpers, with their dependencies.
 *
 * @param array $loaded List of helpers that are already loaded.
 * @param array $helpers List of helpers to load.
 * @return array
 */
    function &_loadHelpers(&$loaded, $helpers)
    {
        static $tags;
        if(empty($tags))
        {
            $helperTags = new Helper();
            $tags = $helperTags->loadConfig();
        }

        foreach ($helpers as $helper)
        {
            $helperCn = $helper.'Helper';

            if(in_array($helper, array_keys($loaded)) !== true)
            {
                if(!class_exists($helperCn))
                {
                    $helperFn = Inflector::underscore($helper).'.php';

                    if(file_exists(APP.'plugins'.DS.$this->plugin.'views'.DS.'helpers'.DS.$helperFn))
                    {
                        $helperFn = APP.'plugins'.DS.$this->plugin.'views'.DS.'helpers'.DS.$helperFn;
                    }
                    else if(file_exists(HELPERS.$helperFn))
                    {
                        $helperFn = HELPERS.$helperFn;
                    }
                    else if($helperFn =  fileExistsInPath(LIBS.'view'.DS.'helpers'.DS.$helperFn))
                    {

                    }
                    if (is_file($helperFn))
                    {
                        require $helperFn;
                    }
                    else
                    {
                        return $this->cakeError('missingHelperFile',
                                    array(array('helper' => $helper,
                                                'file' => Inflector::underscore($helper).'.php')));
                    }
                }

                $replace = strtolower(substr($helper, 0, 1));
                $camelBackedHelper = preg_replace('/\\w/', $replace, $helper, 1);

                if(class_exists($helperCn))
                {
                    ${$camelBackedHelper}                       =& new $helperCn;
                    ${$camelBackedHelper}->base                 = $this->base;
                    ${$camelBackedHelper}->webroot              = $this->webroot;
                    ${$camelBackedHelper}->here                 = $this->here;
                    ${$camelBackedHelper}->params               = $this->params;
                    ${$camelBackedHelper}->action               = $this->action;
                    ${$camelBackedHelper}->data                 = $this->data;
                    ${$camelBackedHelper}->themeWeb             = $this->themeWeb;
                    ${$camelBackedHelper}->tags                 = $tags;

                    if(!empty($this->validationErrors))
                    {
                        ${$camelBackedHelper}->validationErrors = $this->validationErrors;
                    }
                    $loaded[$helper] =& ${$camelBackedHelper};
                    if (isset(${$camelBackedHelper}->helpers) && is_array(${$camelBackedHelper}->helpers))
                    {
                        $loaded =& $this->_loadHelpers($loaded, ${$camelBackedHelper}->helpers);
                    }
                }
                else
                {
                    return $this->cakeError('missingHelperClass',
                                array(array('helper' => $helper,
                                            'file' => Inflector::underscore($helper).'.php')));
                }
            }
        }
        return $loaded;
    }

/**
 * Enter description here...
 *
 * @param unknown_type $action
 * @param unknown_type $layout
 * @return unknown
 */
    function pluginView($action, $layout)
    {
        $viewFileName = APP.'plugins'.DS.$this->plugin.'views'.DS.$this->viewPath.DS.$action.$this->ext;
        if(file_exists($viewFileName))
        {
            $this->render($action, $layout, $viewFileName);
        }
        else
        {
            return $this->cakeError('missingView',
                        array(array('className' => $this->controller->name,
                                    'action' => $action,
                                    'file' => $viewFileName)));
        }
    }
}
?>