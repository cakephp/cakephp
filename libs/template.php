<?PHP 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Renderer
  * Templating for Controller class.
  *
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
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
	var $auto_render = true;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
	var $auto_layout = true;

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
	function set_layout ($layout) {
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
		return $this->_set_array(is_array($one)? $one: array($one=>$two));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $value
  */
	function set_title ($value) {
		$this->_page_title = $value;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
  */
	function _set_array($data) {
		foreach ($data as $name => $value) {
			if ($name == 'title')
				$this->set_title ($value);
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
		$this->auto_render = false;
		$this->auto_layout = false;

		$this->set('url', $this->base.$url);
		$this->set('message', $message);
		$this->set('time', $time);

		$this->render(null,false,VIEWS.'layouts/flash.thtml');
	}

/**
  * Enter description here...
  *
  * @param unknown_type $action
  * @param unknown_type $layout
  * @param unknown_type $file
  */
	function render ($action=null, $layout=null, $file=null) {
		$this->auto_render = false;

		if (!$action) $action = $this->action;
		if ($layout) $this->set_layout($layout);

		$view_fn = $file? $file: $this->_get_view_fn($action);

		if (!is_file($view_fn)) {
			DEBUG? trigger_error (sprintf(ERROR_NO_VIEW, $action, $view_fn), E_USER_ERROR)
			: $this->error('404', 'Not found', sprintf(ERROR_404, '', "missing view \"{$action}\""));
			die();
		}

		$out = $this->_do_render($view_fn, $this->_view_vars, 0);

		if ($out !== false) {
			if ($this->layout && $this->auto_layout) $out = $this->render_layout($out);
			if (CACHE_PAGES) $this->cache->append($out);
			print $out;
		}
		else {
			$out = $this->_do_render($view_fn, $this->_view_vars, false);
			trigger_error (sprintf(ERROR_IN_VIEW, $view_fn, $out), E_USER_ERROR);
		}
	}

/**
  * Enter description here...
  *
  * @param unknown_type $content_for_layout
  * @return unknown
  */
	function render_layout ($content_for_layout) {
		$layout_fn = $this->_get_layout_fn();

		$data_for_layout = array_merge($this->_view_vars, array(
			'title_for_layout'=>$this->_page_title !== false? $this->_page_title: ucfirst($this->name),
			'content_for_layout'=>$content_for_layout));

		if (is_file($layout_fn)) {
			$out = $this->_do_render($layout_fn, $data_for_layout);

			if ($out === false) {
				$out = $this->_do_render($layout_fn, $data_for_layout, false);
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
  * Enter description here...
  *
  * @return unknown
  */
	function _get_layout_fn() {
		return VIEWS."layouts/{$this->layout}.thtml";
	}

/**
  * Enter description here...
  *
  * @param unknown_type $action
  * @return unknown
  */
	function _get_view_fn($action) {
		return VIEWS.$this->name."/{$action}.thtml";
	}

/**
  * Enter description here...
  *
  * @param unknown_type $___view_fn
  * @param unknown_type $___data_for_view
  * @param unknown_type $___play_safe
  * @return unknown
  */
	function _do_render($___view_fn, $___data_for_view, $___play_safe = true) {
		extract($___data_for_view, EXTR_SKIP); # load all view variables
		$BASE = $this->base;
		$params = &$this->params;
		$page_title = $this->_page_title;
		$data = empty($this->data)? false: $this->data;
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
	function trim_to ($string, $length) {
		return substr($string, 0, $length).(strlen($string)>$length? '..': null);
	}
}

?>