<?PHP 
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
  * Purpose: Renderer
  * Templating for Controller class.
  *
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

/**
  * Enter description here...
  *
  */
uses('object');

/**
  * Templating for Controller class. Takes care of rendering views.
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  */
class Template extends Object 
{
    
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
  * Variables for the view
  *
  * @var array
  * @access private
  */
	var $_view_vars = array();

/**
  * Enter description here...
  *
  * @var boolean
  * @access private
  */
	var $pageTitle = false;

/**
  * Choose the layout to be used when rendering.
  *
  * @param string $layout
  */
	function setLayout ($layout) {
		$this->layout = $layout;
	}

/**
  * Saves a variable to use inside a template.
  *
  * @param mixed $one A string or an array of data.
  * @param string $two Value in case $one is a string (which then works as the key), otherwise unused.
  * @return unknown
  */
	function set($one, $two=null) {
		return $this->_setArray(is_array($one)? $one: array($one=>$two));
	}

/**
  * Set the title element of the page.
  *
  * @param string $pageTitle Text for the title
  */
	function setTitle ($pageTitle) {
		$this->pageTitle = $pageTitle;
	}

/**
  * Sets data for this view. Will set title is the key "title" is in given $data array.
  *
  * @param array $data Array of 
  */
	function _setArray($data) {
		foreach ($data as $name => $value) {
			if ($name == 'title')
				$this->setTitle ($value);
			else
				$this->_view_vars[$name] = $value;
		}
	}

/**
  * Displays a flash message. A flash message is feedback to the user that displays after editing actions, among other things.
  *
  * @param string $message Text to display to the user
  * @param string $url URL fragment
  * @param int $time Display time, in seconds
  */
	function flash ($message, $url, $time=1) {
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
	function render ($action=null, $layout=null, $file=null) 
	{
		if (isset($this->hasRendered) && $this->hasRendered) 
		{
			return true;
		} 
		else 
		{
			$this->hasRendered = false;
		}
		
		$this->autoRender = false;
		
		if (!$action) $action = $this->action;
		if ($layout) $this->setLayout($layout);
		
		//$isFatal = isset($this->isFatal) ? $this->isFatal : false;
		
		$view_fn = $file? $file: $this->_getViewFn($action);
		
		if (!is_file($view_fn)) 
		{
			if (strtolower(get_class($this)) == 'template') 
			{
				return array('action' => $action, 'layout' => $layout, 'view_fn' => $view_fn);
			}  
			
			// check to see if the missing view is due to a custom missing_action
			if (strpos($action, 'missing_action') !== false) 
			{
				$error_action = 'missing_action';
			} 
			else 
			{
				$error_action = 'missing_view';
			}
			
			
			// check for controller-level view handler 
			foreach(array($this->name, 'errors') as $view_dir) 
			{
				$missing_view_fn = VIEWS.$view_dir.DS.$error_action.'.thtml';
				$missing_view_exists = is_file($missing_view_fn);
				if ($missing_view_exists)  
				{
					break;
				}
			}
			
			if (strpos($action, 'missing_view') === false) 
			{
				$controller =& $this;
				$controller->missing_view = $view_fn;
				$controller->action       = $action;
				call_user_func_array(array(&$controller, 'missing_view'), empty($params['pass'])? null: $params['pass']);
				$isFatal = isset($this->isFatal) ? $this->isFatal : false;
				if (!$isFatal) 
				{
					$view_fn = $missing_view_fn;
				}    
			} 
			else 
			{
				$missing_view_exists = false;
			}
			
			if (!$missing_view_exists || $isFatal) 
			{
				// app/errors/missing_view.thtml view is missing!
				if (DEBUG)
				{
					trigger_error (sprintf(ERROR_NO_VIEW, $action, $view_fn), E_USER_ERROR);
				}
				else 
				{
					$this->error('404', 'Not found', sprintf(ERROR_404, '', "missing view \"{$action}\""));
				}
	
				die();                  
			}
		}
		
		if ($view_fn && !$this->hasRendered) 
		{
			$out = $this->_render($view_fn, $this->_view_vars, 0);
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
				$out = $this->_render($view_fn, $this->_view_vars, false);
				trigger_error (sprintf(ERROR_IN_VIEW, $view_fn, $out), E_USER_ERROR);
			}
			
			return true;
		}
	}

/**
  * Enter description here... Renders a layout. Returns output from _render(). Returns false on error.
  *
  * @param string $content_for_layout Content to render in a view
  * @return string Rendered output
  */
	function renderLayout ($content_for_layout) {
		$layout_fn = $this->_getLayoutFn();

		$data_for_layout = array_merge($this->_view_vars, array(
			'title_for_layout'=>$this->pageTitle !== false? $this->pageTitle: Inflector::humanize($this->viewpath),
			'content_for_layout'=>$content_for_layout));

		if (is_file($layout_fn)) {
			$out = $this->_render($layout_fn, $data_for_layout);

			if ($out === false) {
				$out = $this->_render($layout_fn, $data_for_layout, false);
				trigger_error (sprintf(ERROR_IN_LAYOUT, $layout_fn, $out), E_USER_ERROR);
				return false;
			}
			else {
				return $out;
			}
		}
		else {
			trigger_error (sprintf(ERROR_NO_LAYOUT, $this->layout, $layout_fn), E_USER_ERROR);
			return false;
		}
	}

/**
  * Renders a piece of PHP with provided parameters and returns HTML, XML, or any other string.
  *
  * @param string $name Name of template file
  * @param array $params Array of data for rendered view 
  * @return string Rendered output
  */
	function renderElement ($name, $params=array()) {
		$fn = ELEMENTS.$name.'.thtml';

		if (!file_exists($fn))
			return "(Error rendering {$name})";

		return $this->_render($fn, array_merge($this->_view_vars, $params));
	}

/**
  * Returns layout filename for this template as a string.
  *
  * @return string Filename for layout file (.thtml).
  */
	function _getLayoutFn() {
		return VIEWS."layouts".DS."{$this->layout}.thtml";
	}

/**
  * Returns filename of given action's template file (.thtml) as a string.
  *
  * @param string $action Controller action to find template filename for
  * @return string Template filename
  */
	function _getViewFn($action) {
		return VIEWS.$this->viewpath.DS."{$action}.thtml";
	}

/**
  * Renders and returns output for given view filename with its 
  * array of data.
  *
  * @param string $___view_fn Filename of the view
  * @param array $___data_for_view Data to include in rendered view
  * @param boolean $___play_safe If set to false, the include() of the $__view_fn is done without suppressing output of errors
  * @return string Rendered output
  */
	function _render($___view_fn, $___data_for_view, $___play_safe = true) 
	{
		extract($___data_for_view, EXTR_SKIP); # load all view variables
		$BASE = $this->base;
		$params = &$this->params;
		$page_title = $this->pageTitle;
		ob_start(); # start caching output (eval outputs directly so we need to cache)
		
		# include the template
		$___play_safe? @include($___view_fn): include($___view_fn);

		$out = ob_get_contents(); # retrieve cached output
		ob_end_clean(); # end caching output

		return $out;
	}

/**
  * Returns given string trimmed to given length, adding an elipsis '..' if necessary.
  *
  * @param string $string String to trim
  * @param int $length Length of returned string, excluding ellipsis
  * @return string Trimmed string
  */
	function trimTo ($string, $length) {
		return substr($string, 0, $length).(strlen($string)>$length? '..': null);
	}
}

?>