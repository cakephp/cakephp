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
  * Purpose: Controller
  * Application controller (controllers are where you put all the actual code) based on RoR (www.rubyonrails.com)
  * Provides basic functionality, such as rendering views (aka displaying templates).
  * Automatically selects model name from on singularized object class name 
  * and creates the model object if proper class exists.
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
uses('model', 'template', 'inflector');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Controller extends Template {
    
/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
    var $name = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
    var $parent = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
    var $action = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access public
  */
    var $use_model = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
    var $uses = false;

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
    var $_crumbs = array();

/**
  * Enter description here...
  *
  */
	function __construct () {
		global $DB;

		$r = null;
		if (!preg_match('/(.*)Controller/i', get_class($this), $r))
			die("Controller::__construct() : Can't get or parse my own class name, exiting.");

		$this->name = strtolower($r[1]);

		$model_class = Inflector::singularize($this->name);
		if (($this->uses === false) && class_exists($model_class)) {
			if (!$DB)
				die("Controller::__construct() : ".$this->name." controller needs database access, exiting.");

			$this->$model_class = new $model_class ();
		}
		elseif ($this->uses) {
			if (!$DB)
				die("Controller::__construct() : ".$this->name." controller needs database access, exiting.");

			$uses = is_array($this->uses)? $this->uses: array($this->uses);

			foreach ($uses as $model_name) {
				$model_class = ucfirst(strtolower($model_name));
				if (class_exists($model_class))
					$this->$model_name = new $model_name (false);
				else
					die("Controller::__construct() : ".ucfirst($this->name)." requires missing model {$model_class}, exiting.");
			}
		}

		parent::__construct();
	}

/**
  * Enter description here...
  *
  * @param unknown_type $url
  */
	function redirect ($url) {
		$this->auto_render = false;
		header ('Location: '.$this->base.$url);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $action
  */
	function setAction ($action) {
		$this->action = $action;

		$args = func_get_args();
		call_user_func_array(array(&$this, $action), $args);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $url
  * @return unknown
  */
	function urlFor ($url=null) {
		if (empty($url)) {
			return $this->base.'/'.strtolower($this->params['controller']).'/'.strtolower($this->params['action']);
		}
		elseif ($url[0] == '/') {
			return $this->base . $url;
		}
		else {
			return $this->base . '/' . strtolower($this->params['controller']) . '/' . $url;
		}
	}

/**
  * Enter description here...
  *
  * @param unknown_type $options
  * @param unknown_type $insert_before
  * @param unknown_type $insert_after
  * @return unknown
  */
	function parseHtmlOptions ($options, $insert_before=' ', $insert_after=null) {
		if (is_array($options)) {
			$out = array();
			foreach ($options as $k=>$v) {
				$out[] = "{$k}=\"{$v}\"";
			}
			$out = join(' ', $out);
			return $out? $insert_before.$out.$insert_after: null;
		}
		else {
			return $options? $insert_before.$options.$insert_after: null;
		}
	}

/**
  * Enter description here...
  *
  * @param unknown_type $title
  * @param unknown_type $url
  * @param unknown_type $html_options
  * @param unknown_type $confirm_message
  * @return unknown
  */
	function linkTo ($title, $url, $html_options=null, $confirm_message=false) {
		$confirm_message? $html_options['onClick'] = "return confirm('{$confirm_message}')": null;
		return sprintf(TAG_LINK, $this->UrlFor($url), $this->parseHtmlOptions($html_options), $title);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $title
  * @param unknown_type $url
  * @param unknown_type $html_options
  * @return unknown
  */
	function linkOut ($title, $url=null, $html_options=null) {
		$url = $url? $url: $title;
		return sprintf(TAG_LINK, $url, $this->parseHtmlOptions($html_options), $title);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $target
  * @param unknown_type $type
  * @param unknown_type $html_options
  * @return unknown
  */
	function formTag ($target=null, $type='post', $html_options=null) {
		$html_options['action'] = $this->UrlFor($target);
		$html_options['method'] = $type=='get'? 'get': 'post';
		$type == 'file'? $html_options['enctype'] = 'multipart/form-data': null;

		return sprintf(TAG_FORM, $this->parseHtmlOptions($html_options, ''));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $caption
  * @param unknown_type $html_options
  * @return unknown
  */
	function submitTag ($caption='Submit', $html_options=null) {
		$html_options['value'] = $caption;
		return sprintf(TAG_SUBMIT, $this->parseHtmlOptions($html_options, '', ' '));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $size
  * @param unknown_type $html_options
  * @return unknown
  */
	function inputTag ($tag_name, $size=20, $html_options=null) {
		$html_options['size'] = $size;
		$html_options['value'] = isset($html_options['value'])? $html_options['value']: $this->tagValue($tag_name);
		$this->tagIsInvalid($tag_name)? $html_options['class'] = 'form_error': null;
		return sprintf(TAG_INPUT, $tag_name, $this->parseHtmlOptions($html_options, '', ' '));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $size
  * @param unknown_type $html_options
  * @return unknown
  */
	function passwordTag ($tag_name, $size=20, $html_options=null) {
		$html_options['size'] = $size;
		empty($html_options['value'])? $html_options['value'] = $this->tagValue($tag_name): null;
		return sprintf(TAG_PASSWORD, $tag_name, $this->parseHtmlOptions($html_options, '', ' '));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $value
  * @param unknown_type $html_options
  * @return unknown
  */
	function hiddenTag ($tag_name, $value=null, $html_options=null) {
		$html_options['value'] = $value? $value: $this->tagValue($tag_name);
		return sprintf(TAG_HIDDEN, $tag_name, $this->parseHtmlOptions($html_options, '', ' '));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $html_options
  * @return unknown
  */
	function fileTag ($tag_name, $html_options=null) {
		return sprintf(TAG_FILE, $tag_name, $this->parseHtmlOptions($html_options, '', ' '));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $cols
  * @param unknown_type $rows
  * @param unknown_type $html_options
  * @return unknown
  */
	function areaTag ($tag_name, $cols=60, $rows=10, $html_options=null) {
		$value = empty($html_options['value'])? $this->tagValue($tag_name): empty($html_options['value']);
		$html_options['cols'] = $cols;
		$html_options['rows'] = $rows;
		return sprintf(TAG_AREA, $tag_name, $this->parseHtmlOptions($html_options, ' '), $value);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $title
  * @param unknown_type $html_options
  * @return unknown
  */
	function checkboxTag ($tag_name, $title=null, $html_options=null) {
		$this->tagValue($tag_name)? $html_options['checked'] = 'checked': null;
		$title = $title? $title: ucfirst($tag_name);
		return sprintf(TAG_CHECKBOX, $tag_name, $tag_name, $tag_name, $this->parseHtmlOptions($html_options, '', ' '), $title); 
	}

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $options
  * @param unknown_type $inbetween
  * @param unknown_type $html_options
  * @return unknown
  */
	function radioTags ($tag_name, $options, $inbetween=null, $html_options=null) {
		$value = isset($html_options['value'])? $html_options['value']: $this->tagValue($tag_name);
		$out = array();
		foreach ($options as $opt_value=>$opt_title) {
			$options_here = array('value' => $opt_value);
			$opt_value==$value? $options_here['checked'] = 'checked': null;
			$parsed_options = $this->parseHtmlOptions(array_merge($html_options, $options_here), '', ' ');
			$individual_tag_name = "{$tag_name}_{$opt_value}";
			$out[] = sprintf(TAG_RADIOS, $individual_tag_name, $tag_name, $individual_tag_name, $parsed_options, $opt_title);
		}
		
		$out = join($inbetween, $out);
		return $out? $out: null;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $options
  * @param unknown_type $outer_options
  * @param unknown_type $inner_options
  * @return unknown
  */
	function selectTag ($tag_name, $options, $outer_options=null, $inner_options=null) { 
		if (!is_array($options) || !count($options))
			return null;
		$selected = isset($html_options['value'])? $html_options['value']: $this->tagValue($tag_name);
		$select[] = sprintf(TAG_SELECT_START, $tag_name, $this->parseHtmlOptions($outer_options));
		$select[] = sprintf(TAG_SELECT_EMPTY, $this->parseHtmlOptions($inner_options));
	
		foreach ($options as $name=>$title) {
			$options_here = $selected==$name? array_merge($inner_options, array('selected'=>'selected')): $inner_options;
			$select[] = sprintf(TAG_SELECT_OPTION, $name, $this->parseHtmlOptions($options_here), $title);
		} 

		$select[] = sprintf(TAG_SELECT_END);

		return implode("\n", $select); 
	}

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @param unknown_type $alt
  * @param unknown_type $html_options
  * @return unknown
  */
	function imageTag ($path, $alt=null, $html_options=null) {
		$url = "{$this->base}/images/{$path}";
		return sprintf(TAG_IMAGE, $url, $alt, $this->parseHtmlOptions($html_options, '', ' '));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $names
  * @param unknown_type $tr_options
  * @param unknown_type $th_options
  * @return unknown
  */
	function tableHeaders ($names, $tr_options=null, $th_options=null) {
		$args = func_get_args();

		$out = array();
		foreach ($names as $arg)
			$out[] = sprintf(TAG_TABLE_HEADER, $this->parseHtmlOptions($th_options), $arg);

		return sprintf(TAG_TABLE_HEADERS, $this->parseHtmlOptions($tr_options), join(' ', $out));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $data
  * @param unknown_type $tr_options
  * @param unknown_type $td_options
  * @return unknown
  */
	function tableCells ($data, $odd_tr_options=null, $even_tr_options=null) {
		if (empty($data[0]) || !is_array($data[0]))
			$data = array($data);

		$count=0;
		foreach ($data as $line) {
			$count++;
			$cells_out = array();
			foreach ($line as $cell)
				$cells_out[] = sprintf(TAG_TABLE_CELL, null, $cell);

			$options = $this->parseHtmlOptions($count%2? $odd_tr_options: $even_tr_options);
			$out[] = sprintf(TAG_TABLE_ROW, $options, join(' ', $cells_out));
		}

		return join("\n", $out);
	}


/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @return unknown
  */
	function tagValue ($tag_name) {
		return isset($this->params['data'][$tag_name])? $this->params['data'][$tag_name]: false;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function validate () {
		$args = func_get_args();
		$errors = call_user_func_array(array(&$this, 'validateErrors'), $args);

		return count($errors);
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function validateErrors () {
		$objects = func_get_args();
		if (!count($objects)) return false;

		$errors = array();
		foreach ($objects as $object) {
			$errors = array_merge($errors, $object->invalidFields());
		}

		return $this->validation_errors = (count($errors)? $errors: false);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $field
  * @param unknown_type $text
  * @return unknown
  */
	function tagErrorMsg ($field, $text) {
		return $this->tagIsInvalid($field)? sprintf(SHORT_ERROR_MESSAGE, $text): null;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $field
  * @return unknown
  */
	function tagIsInvalid ($field) {
		return !empty($this->validation_errors[$field]);
	}



/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @param unknown_type $link
  */
	function addCrumb ($name, $link) {
		$this->_crumbs[] = array ($name, $link);
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function getCrumbs () {

		if (count($this->_crumbs)) {

			$out = array("<a href=\"{$this->base}\">START</a>");
			foreach ($this->_crumbs as $crumb) {
				$out[] = "<a href=\"{$this->base}{$crumb[1]}\">{$crumb[0]}</a>";
			}
		
			return join(' &raquo; ', $out);
		}
		else
			return null;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $code
  * @param unknown_type $name
  * @param unknown_type $message
  */
	function error ($code, $name, $message) {
		header ("HTTP/1.0 {$code} {$name}");
		print ($this->_do_render(VIEWS.'layouts/error.thtml', array('code'=>$code,'name'=>$name,'message'=>$message)));
	}
}

?>