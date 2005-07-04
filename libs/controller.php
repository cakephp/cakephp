<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * Purpose: Controller
 * Application controller (controllers are where you put all the actual code) 
 * Provides basic functionality, such as rendering views (aka displaying templates).
 * Automatically selects model name from on singularized object class name 
 * and creates the model object if proper class exists.
 * 
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

/**
 * Enter description here...
 */
uses('model', 'inflector', 'folder', 'view');

/**
 * Enter description here...
 *
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 *
 */
class Controller extends Object
{
	/**
	 * Name of the controller.
	 *
	 * @var unknown_type
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
	 * Enter description here...
	 *
	 * @var unknown_type
	 * @access public
	 */
	var $parent = null;

	/**
	 * Action to be performed.
	 *
	 * @var string
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
	var $helpers = array('html');

	var $viewPath;

	/**
	 * Variables for the view
	 *
	 * @var array
	 * @access private
	 */
	var $_viewVars = array();

	/**
	 * Enter description here...
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
	 * Enter description here...
	 *
	 * @var unknown_type
	 * @access public
	 */
	var $base = null;

	/**
	 * Enter description here...
	 *
	 * @var string
	 * @access public
	 */
	var $layout = 'default';

	/**
	 * Enter description here...
	 *
	 * @var boolean
	 * @access public
	 */
	var $autoRender = true;

	/**
	 * Enter description here...
	 *
	 * @var boolean
	 * @access public
	 */
	var $autoLayout = true;


	/**
	 * Constructor. 
	 *
	 */
	function __construct ($params=null)
	{
		parent::__construct();

		$this->params = $params;

		$r = null;
		if (!preg_match('/(.*)Controller/i', get_class($this), $r))
		{
			die("Controller::__construct() : Can't get or parse my own class name, exiting.");
		}

		$this->name = strtolower($r[1]);
		$this->viewPath = Inflector::underscore($r[1]);

		$model_class = Inflector::singularize($this->name);

		//Is this needed?
		$this->db = DboFactory::getInstance();

		if (class_exists($model_class) && ($this->uses === false))
		{
			$this->models[$model_class] = new $model_class();
		}
		elseif ($this->uses)
		{
			if (!$this->db)
			{
				die("Controller::__construct() : ".$this->name." controller needs database access, exiting.");
			}

			$uses = is_array($this->uses)? $this->uses: array($this->uses);

			foreach ($uses as $model_name)
			{
				$model_class = ucfirst(strtolower($model_name));

				if (class_exists($model_class))
				{
					$this->models[$model_name] = new $model_class(false);
				}
				else
				{
					die("Controller::__construct() : ".ucfirst($this->name)." requires missing model {$model_class}, exiting.");
				}
			}
		}
	}

	/**
	 * Redirects to given $url, after turning off $this->autoRender.
	 *
	 * @param unknown_type $url
	 */
	function redirect ($url)
	{
		$this->autoRender = false;
		header ('Location: '.$this->base.$url);
	}

	/**
	 * Saves a variable to use inside a template.
	 *
	 * @param mixed $one A string or an array of data.
	 * @param string $two Value in case $one is a string (which then works as the key), otherwise unused.
	 * @return unknown
	 */
	function set($one, $two=null)
	{
		return $this->_setArray(is_array($one)? $one: array($one=>$two));
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $action
	 */
	function setAction ($action)
	{
		$this->action = $action;

		$args = func_get_args();
		call_user_func_array(array(&$this, $action), $args);
	}

	/**
	 * Returns number of errors in a submitted FORM.
	 *
	 * @return int Number of errors
	 */
	function validate ()
	{
		$args = func_get_args();
		$errors = call_user_func_array(array(&$this, 'validateErrors'), $args);

		return count($errors);
	}

	/**
	 * Validates a FORM according to the rules set up in the Model.
	 *
	 * @return int Number of errors
	 */
	function validateErrors ()
	{
		$objects = func_get_args();
		if (!count($objects)) return false;

		$errors = array();
		foreach ($objects as $object)
		{
			$errors = array_merge($errors, $object->invalidFields($object->data));
		}

		return $this->validationErrors = (count($errors)? $errors: false);
	}

	function render($action=null, $layout=null, $file=null)
	{
		$view =& View::getInstance();
		$view->_viewVars  =& $this->_viewVars;
		$view->action     =& $this->action;
		$view->autoLayout =& $this->autoLayout;
		$view->autoRender =& $this->autoRender;
		$view->base       =& $this->base;
		$view->helpers    =& $this->helpers;
		$view->here       =& $this->here;
		$view->layout     =& $this->layout;
		$view->models     =& $this->models;
		$view->name       =& $this->name;
		$view->pageTitle  =& $this->pageTitle;
		$view->parent     =& $this->parent;
		$view->viewPath   =& $this->viewPath;
		$view->params     =& $this->params;
		$view->data       =& $this->data;
		
		if(!empty($this->models))
		{
		   foreach ($this->models as $key => $value)
		   {
		      if(!empty($this->models[$key]->validationErrors))
		      {
		         $view->validationErrors[$key] =& $this->models[$key]->validationErrors;
		      }
		   }
		}

		return  $view->render($action, $layout, $file);
	}

	function missingController()
	{
		//We are simulating action call below, this is not a filename!
		$this->render('../errors/missingController');
	}

	function missingAction()
	{
		//We are simulating action call below, this is not a filename!
		$this->render('../errors/missingAction');
	}

	function missingView()
	{
		//We are simulating action call below, this is not a filename!
		$this->render('../errors/missingView');
	}

	//		/**
	//		 * Displays an error page to the user. Uses layouts/error.html to render the page.
	//		 *
	//		 * @param int $code Error code (for instance: 404)
	//		 * @param string $name Name of the error (for instance: Not Found)
	//		 * @param string $message Error message
	//		 */
	//		function error ($code, $name, $message)
	//		{
	//			header ("HTTP/1.0 {$code} {$name}");
	//			print ($this->_render(VIEWS.'layouts/error.thtml', array('code'=>$code,'name'=>$name,'message'=>$message)));
	//		}

	/**
	 * Sets data for this view. Will set title if the key "title" is in given $data array.
	 *
	 * @param array $data Array of 
	 */
	function _setArray($data)
	{
		foreach ($data as $name => $value)
		{
			if ($name == 'title')
			$this->_setTitle($value);
			else
			$this->_viewVars[$name] = $value;
		}
	}

	/**
	 * Set the title element of the page.
	 *
	 * @param string $pageTitle Text for the title
	 */
	function _setTitle($pageTitle)
	{
		$this->pageTitle = $pageTitle;
	}
	
	function flash($message, $url, $time=1)
	{
		$this->autoRender = false;
		$this->autoLayout = false;

		$this->set('url', $this->base.$url);
		$this->set('message', $message);
		$this->set('time', $time);

		$this->render(null,false,VIEWS.'layouts'.DS.'flash.thtml');
	}
}

?>