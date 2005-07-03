<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: View
  * 
  * 
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.9.1
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

uses('object');

/**
 * 
 *
 * @package cake
 * @subpackage cake.libs
 * @since Cake v 0.9.1
 */
class View extends Object
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





	var $params;
	var $hasRendered = null;

	var $modelsLoaded = false;
	
	function View(){
	}

	function getInstance()
	{
		static $instance;
		if (!isset($instance))
		{
			$instance[0] =& new View();
		}
		return $instance[0];
	}

	/**
	 * Displays a flash message. A flash message is feedback to the user that displays after editing actions, among other things.
	 *
	 * @param string $message Text to display to the user
	 * @param string $url URL fragment
	 * @param int $time Display time, in seconds
	 */
	function flash($message, $url, $time=1)
	{
		$this->autoRender = false;
		$this->autoLayout = false;

		$this->set('url', $this->base.$url);
		$this->set('message', $message);
		$this->set('time', $time);

		$this->render(null,false,VIEWS.'layouts'.DS.'flash.thtml');
	}

	/**
	 * Render view for given action and layout. If $file is given, that is used 
	 * for a view filename (e.g. customFunkyView.thtml).
	 *
	 * @param string $action Name of action to render for
	 * @param string $layout 
	 * @param string $file Custom filename for view
	 */
	function render($action=null, $layout=null, $file=null)
	{


		if ($this->modelsLoaded!==true)
		{
			foreach ($this->models as $modelName => $model)
			{
				$this->$modelName = $model;
			}
		}
		// What is reason for these being the same?
		if (isset($this->hasRendered) && $this->hasRendered)
		{
		//echo "<pre>";
		//print_r($this);
		//echo "</pre>";
			return true;
		}
		else
		{
			$this->hasRendered = false;
		}

		$this->autoRender = false;

		if (!$action)
		{
			$action = $this->action;
		}
		if ($layout)
		{
			$this->setLayout($layout);
		}

		$viewFileName = $file? $file: $this->_getViewFileName($action);

		if (!is_file($viewFileName))
		{
			if (strtolower(get_class($this)) == 'template')
			{
				return array('action' => $action, 'layout' => $layout, 'viewFn' => $viewFileName);
			}

			// check to see if the missing view is due to a custom missingAction
			if (strpos($action, 'missingAction') !== false)
			{
				$errorAction = 'missingAction';
			}
			else
			{
				$errorAction = 'missingView';
			}

			// check for controller-level view handler
			foreach(array($this->name, 'errors') as $viewDir)
			{
				$missingViewFileName = VIEWS.$viewDir.DS.Inflector::underscore($errorAction).'.thtml';
				$missingViewExists = is_file($missingViewFileName);
				if ($missingViewExists)
				{
					break;
				}
			}

			if (strpos($action, 'missingView') === false)
			{
				$controller = $this;
				$controller->missingView = $viewFileName;
				$controller->action      = $action;
				call_user_func_array(array(&$controller, 'missingView'), empty($params['pass'])? null: $params['pass']);
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
				// app/view/errors/missing_view.thtml view is missing!
				if (DEBUG)
				{
					trigger_error(sprintf(ERROR_NO_VIEW, $action, $viewFileName), E_USER_ERROR);
				}
				else
				{
					$this->error('404', 'Not found', sprintf(ERROR_404, '', "missing view \"{$action}\""));
				}

				die();
			}
		}

		if ($viewFileName && !$this->hasRendered)
		{
			$out = $this->_render($viewFileName, $this->_viewVars, 0);
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
				$out = $this->_render($viewFileName, $this->_viewVars, false);
				trigger_error(sprintf(ERROR_IN_VIEW, $viewFileName, $out), E_USER_ERROR);
			}

			return true;
		}
	}

	/**
	 * Renders a piece of PHP with provided parameters and returns HTML, XML, or any other string.
	 *
	 * @param string $name Name of template file
	 * @param array $params Array of data for rendered view 
	 * @return string Rendered output
	 */
	function renderElement($name, $params=array())
	{
		$fn = ELEMENTS.$name.'.thtml';

		if (!file_exists($fn))
		{
			return "(Error rendering {$name})";
		}
		return $this->_render($fn, array_merge($this->_viewVars, $params));
	}

	/**
	 * Renders a layout. Returns output from _render(). Returns false on error.
	 *
	 * @param string $content_for_layout Content to render in a view
	 * @return string Rendered output
	 */
	function renderLayout($content_for_layout)
	{
		$layout_fn = $this->_getLayoutFileName();

		$data_for_layout = array_merge($this->_viewVars, array(
		'title_for_layout'=>$this->pageTitle !== false? $this->pageTitle: Inflector::humanize($this->viewPath),
		'content_for_layout'=>$content_for_layout));

		if (is_file($layout_fn))
		{
			$out = $this->_render($layout_fn, $data_for_layout);

			if ($out === false)
			{
				$out = $this->_render($layout_fn, $data_for_layout, false);
				trigger_error(sprintf(ERROR_IN_LAYOUT, $layout_fn, $out), E_USER_ERROR);
				return false;
			}
			else
			{
				return $out;
			}
		}
		else
		{
			trigger_error(sprintf(ERROR_NO_LAYOUT, $this->layout, $layout_fn), E_USER_ERROR);
			return false;
		}
	}

	/**
	 * Choose the layout to be used when rendering.
	 *
	 * @param string $layout
	 */
	function setLayout($layout)
	{
		$this->layout = $layout;
	}

	/**
	 * Displays an error page to the user. Uses layouts/error.html to render the page.
	 *
	 * @param int $code Error code (for instance: 404)
	 * @param string $name Name of the error (for instance: Not Found)
	 * @param string $message Error message
	 */
	function error ($code, $name, $message)
	{
		header ("HTTP/1.0 {$code} {$name}");
		print ($this->_render(VIEWS.'layouts/error.thtml', array('code'=>$code,'name'=>$name,'message'=>$message)));
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


	/**************************************************************************
	* Private methods.
	*************************************************************************/


	/**
	 * Returns filename of given action's template file (.thtml) as a string. CamelCased action names will be under_scored! This means that you can have LongActionNames that refer to long_action_names.thtml views.
	 *
	 * @param string $action Controller action to find template filename for
	 * @return string Template filename
	 */
	function _getViewFileName($action)
	{
		$action = Inflector::underscore($action);
		$viewFileName = VIEWS.$this->viewPath.DS."{$action}.thtml";
		$viewPath = explode(DS, $viewFileName);

		$i = array_search('..', $viewPath);

		unset($viewPath[$i-1]);
		unset($viewPath[$i]);

		return '/'.implode('/', $viewPath);
	}

	/**
	 * Returns layout filename for this template as a string.
	 *
	 * @return string Filename for layout file (.thtml).
	 */
	function _getLayoutFileName()
	{
		return VIEWS."layouts".DS."{$this->layout}.thtml";
	}

	/**
	 * Renders and returns output for given view filename with its 
	 * array of data.
	 *
	 * @param string $___viewFn Filename of the view
	 * @param array $___data_for_view Data to include in rendered view
	 * @param boolean $___play_safe If set to false, the include() of the $__viewFn is done without suppressing output of errors
	 * @return string Rendered output
	 */
	function _render($___viewFn, $___data_for_view, $___play_safe = true)
	{
		/**
		 * Fetching helpers
		 */
		if ($this->helpers !== false)
		{
			foreach ($this->helpers as $helper)
			{
				$helperFn = LIBS.'helpers'.DS.Inflector::underscore($helper).'.php';
				$helperCn = ucfirst($helper).'Helper';
				if (is_file($helperFn))
				{
					require_once $helperFn;
					if(class_exists($helperCn)===true);
					{
						${$helper} = new $helperCn;
						${$helper}->base   = $this->base;
						${$helper}->here   = $this->here;
						${$helper}->params = $this->params;
						${$helper}->action = $this->action;
						${$helper}->data   = $this->data;
					}
				}
			}
		}

		extract($___data_for_view, EXTR_SKIP); # load all view variables
		/**
		 * Local template variables.
		 */
		$BASE       = $this->base;
		$params     = &$this->params;
		$page_title = $this->pageTitle;

		/**
		 * Start caching output (eval outputs directly so we need to cache).
		 */
		ob_start();

		/**
		 * Include the template.
		 */
		$___play_safe? @include($___viewFn): include($___viewFn);

		$out = ob_get_clean();

		return $out;
	}

}

?>