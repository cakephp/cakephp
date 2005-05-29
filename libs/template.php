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
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Template extends Object {
    
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
  * @var unknown_type
  * @access public
  */
	var $layout = 'default';

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $autoRender = true;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $autoLayout = true;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
	var $_view_vars = array();

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
	var $_page_title = false;

/**
  * Enter description here...
  *
  */
	function __construct () {
		parent::__construct();
	}

/**
  * Enter description here...
  *
  * @param unknown_type $layout
  */
	function setLayout ($layout) {
		$this->layout = $layout;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $one
  * @param unknown_type $two
  * @return unknown
  */
	function set($one, $two=null) {
		return $this->_setArray(is_array($one)? $one: array($one=>$two));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $value
  */
	function setTitle ($value) {
		$this->_page_title = $value;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
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
  * Enter description here...
  *
  * @param unknown_type $message
  * @param unknown_type $url
  * @param unknown_type $time
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
  * Enter description here...
  *
  * @param unknown_type $action
  * @param unknown_type $layout
  * @param unknown_type $file
  */
	function render ($action=null, $layout=null, $file=null) {
		$this->autoRender = false;

		if (!$action) $action = $this->action;
		if ($layout) $this->setLayout($layout);

		$view_fn = $file? $file: $this->_getViewFn($action);

		if (!is_file($view_fn)) {
			DEBUG? trigger_error (sprintf(ERROR_NO_VIEW, $action, $view_fn), E_USER_ERROR)
			: $this->error('404', 'Not found', sprintf(ERROR_404, '', "missing view \"{$action}\""));
			die();
		}

		$out = $this->_render($view_fn, $this->_view_vars, 0);

		if ($out !== false) {
			if ($this->layout && $this->autoLayout) 
				$out = $this->renderLayout($out);
			print $out;
		}
		else {
			$out = $this->_render($view_fn, $this->_view_vars, false);
			trigger_error (sprintf(ERROR_IN_VIEW, $view_fn, $out), E_USER_ERROR);
		}
	}

/**
  * Enter description here...
  *
  * @param unknown_type $content_for_layout
  * @return unknown
  */
	function renderLayout ($content_for_layout) {
		$layout_fn = $this->_getLayoutFn();

		$data_for_layout = array_merge($this->_view_vars, array(
			'title_for_layout'=>$this->_page_title !== false? $this->_page_title: Inflector::humanize($this->viewpath),
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
  * Renders a piece of PHP with provided params and returns HTML, XML, or any other string.
  *
  * @param unknown_type $content_for_layout
  * @return unknown
  */
	function renderElement ($name, $params=array()) {
		$fn = ELEMENTS.$name.'.thtml';

		if (!file_exists($fn))
			return "(Error rendering {$name})";

		return $this->_render($fn, $params);
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function _getLayoutFn() {
		return VIEWS."layouts".DS."{$this->layout}.thtml";
	}

/**
  * Enter description here...
  *
  * @param unknown_type $action
  * @return unknown
  */
	function _getViewFn($action) {
		return VIEWS.$this->viewpath.DS."{$action}.thtml";
	}

/**
  * Enter description here...
  *
  * @param unknown_type $___view_fn
  * @param unknown_type $___data_for_view
  * @param unknown_type $___play_safe
  * @return unknown
  */
	function _render($___view_fn, $___data_for_view, $___play_safe = true) {
		extract($___data_for_view, EXTR_SKIP); # load all view variables
		$BASE = $this->base;
		$params = &$this->params;
		$page_title = $this->_page_title;
		ob_start(); # start caching output (eval outputs directly so we need to cache)
		
		# include the template
		$___play_safe? @include($___view_fn): include($___view_fn);

		$out = ob_get_contents(); # retrieve cached output
		ob_end_clean(); # end caching output

		return $out;
	}

/**
  * trims a string to a specified length adding elipsis '..' if necessary
  *
  * @param unknown_type $string
  * @param unknown_type $length
  * @return unknown
  */
	function trimTo ($string, $length) {
		return substr($string, 0, $length).(strlen($string)>$length? '..': null);
	}
}

?>