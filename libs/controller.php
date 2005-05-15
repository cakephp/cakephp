<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <http://sputnik.pl/cake>                                    + //
// + Copyright: (c) 2005 Michal Tatarynowicz                          + //
// +                                                                  + //
// + Author(s): (c) 2005 Michal Tatarynowicz <tatarynowicz@gmail.com> + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under the Public Domain Licence                         + //
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
  * @modifiedby $LastChangedBy$  
  * @lastmodified $Date$
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @copyright Copyright (c) 2005, Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @license Public_Domain
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

        if ($this->uses === false) {
            if (!$DB)
            die("Controller::__construct() : ".$this->name." controller needs database access, exiting.");

            $model_class = Inflector::singularize($this->name);
            if (class_exists($model_class))
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
  * @param unknown_type $url
  * @return unknown
  */
    function url_for ($url=null) {
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
    function parse_html_options ($options, $insert_before=' ', $insert_after=null) {
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
  * @param unknown_type $confirm
  * @return unknown
  */
    function link_to ($title, $url, $html_options=null, $confirm=false) {
        $confirm? $html_options['onClick'] = "return confirm('{$confirm}')": null;
        return sprintf(TAG_LINK, $this->url_for($url), $this->parse_html_options($html_options), $title);
    }

/**
  * Enter description here...
  *
  * @param unknown_type $title
  * @param unknown_type $url
  * @param unknown_type $html_options
  * @return unknown
  */
    function link_out ($title, $url=null, $html_options=null) {
        $url = $url? $url: $title;
        return sprintf(TAG_LINK, $url, $this->parse_html_options($html_options), $title);
    }

/**
  * Enter description here...
  *
  * @param unknown_type $target
  * @param unknown_type $type
  * @param unknown_type $html_options
  * @return unknown
  */
    function form_tag ($target=null, $type='post', $html_options=null) {
        $html_options['action'] = $this->url_for($target);
        $html_options['method'] = $type=='get'? 'get': 'post';
        $type == 'file'? $html_options['enctype'] = 'multipart/form-data': null;

        return sprintf(TAG_FORM, $this->parse_html_options($html_options, ''));
    }

/**
  * Enter description here...
  *
  * @param unknown_type $caption
  * @param unknown_type $html_options
  * @return unknown
  */
    function submit_tag ($caption='Submit', $html_options=null) {
        $html_options['value'] = $caption;
        return sprintf(TAG_SUBMIT, $this->parse_html_options($html_options, '', ' '));
    }

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $size
  * @param unknown_type $html_options
  * @return unknown
  */
    function input_tag ($tag_name, $size=20, $html_options=null) {
        $html_options['size'] = $size;
        $html_options['value'] = isset($html_options['value'])? $html_options['value']: $this->tag_value($tag_name);
        $this->tag_is_invalid($tag_name)? $html_options['class'] = 'form_error': null;
        return sprintf(TAG_INPUT, $tag_name, $this->parse_html_options($html_options, '', ' '));
    }

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $value
  * @param unknown_type $html_options
  * @return unknown
  */
    function hidden_tag ($tag_name, $value=null, $html_options=null) {
        $html_options['value'] = $value? $value: $this->tag_value($tag_name);
        return sprintf(TAG_HIDDEN, $tag_name, $this->parse_html_options($html_options, '', ' '));
    }

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $size
  * @param unknown_type $html_options
  * @return unknown
  */
    function password_tag ($tag_name, $size=20, $html_options=null) {
        $html_options['size'] = $size;
        $html_options['value'] = $value? $value: $this->tag_value($tag_name);
        return sprintf(TAG_PASSWORD, $tag_name, $this->parse_html_options($html_options, '', ' '));
    }

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $html_options
  * @return unknown
  */
    function file_tag ($tag_name, $html_options=null) {
        return sprintf(TAG_FILE, $tag_name, $this->parse_html_options($html_options, '', ' '));
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
    function area_tag ($tag_name, $cols=60, $rows=10, $html_options=null) {
        $value = $value? $value: $this->tag_value($tag_name);
        $html_options['cols'] = $cols;
        $html_options['rows'] = $rows;
        return sprintf(TAG_AREA, $tag_name, $this->parse_html_options($html_options, ' '), $value);
    }

/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @param unknown_type $title
  * @param unknown_type $html_options
  * @return unknown
  */
    function checkbox_tag ($tag_name, $title=null, $html_options=null) {
        $this->tag_value($tag_name)? $html_options['checked'] = 'checked ': null;
        $title = $title? $title: ucfirst($tag_name);
        return sprintf(TAG_CHECKBOX, $tag_name, $tag_name, $tag_name, $this->parse_html_options($html_options, '', ' '), $title);
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
    function radio_tags ($tag_name, $options, $inbetween=null, $html_options=null) {
        $value = isset($html_options['value'])? $html_options['value']: $this->tag_value($tag_name);
        $out = array();
        foreach ($options as $opt_value=>$opt_title) {
            $options_here = array('value' => $opt_value);
            $opt_value==$value? $options_here['checked'] = 'checked': null;
            $parsed_options = $this->parse_html_options(array_merge($html_options, $options_here), '', ' ');
            $individual_tag_name = "{$tag_name}_{$opt_value}";
            $out[] = sprintf(TAG_RADIOS, $individual_tag_name, $tag_name, $individual_tag_name, $parsed_options, $opt_title);
        }

        return join($inbetween, $out);
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
    function select_tag ($tag_name, $options, $outer_options=null, $inner_options=null) {
        $selected = isset($html_options['value'])? $html_options['value']: $this->tag_value($tag_name);
        $select[] = sprintf(TAG_SELECT_START, $tag_name, $this->parse_html_options($outer_options));
        $select[] = sprintf(TAG_SELECT_EMPTY, $this->parse_html_options($inner_options));

        foreach ($options as $name=>$title) {
            $options_here = $selected==$name? array_merge($inner_options, array('selected'=>'selected')): $inner_options;
            $select[] = sprintf(TAG_SELECT_OPTION, $name, $this->parse_html_options($options_here), $title);
        }

        $select[] = sprintf(TAG_SELECT_END);

        return implode("\n", $select);
    }

/**
  * Enter description here...
  *
  * @param unknown_type $names
  * @param unknown_type $tr_options
  * @param unknown_type $th_options
  * @return unknown
  */
    function table_headers ($names, $tr_options=null, $th_options=null) {
        $args = func_get_args();

        $out = array();
        foreach ($names as $arg)
        $out[] = sprintf(TAG_TABLE_HEADER, $this->parse_html_options($th_options), $arg);

        return sprintf(TAG_TABLE_HEADERS, $this->parse_html_options($tr_options), join(' ', $out));
    }

/**
  * Enter description here...
  *
  * @param unknown_type $data
  * @param unknown_type $tr_options
  * @param unknown_type $td_options
  * @return unknown
  */
    function table_cells ($data, $tr_options=null, $td_options=null) {
        if (empty($data[0]) || !is_array($data[0]))
        $data = array($data);

        foreach ($data as $line) {
            $cells_out = array();
            foreach ($line as $cell) $cells_out[] = sprintf(TAG_TABLE_CELL, $this->parse_html_options($td_options), $cell);
            $out[] = join(' ', $cells_out);
        }

        return sprintf(TAG_TABLE_ROW, $this->parse_html_options($tr_options), join("\n", $out));
    }

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @param unknown_type $alt
  * @param unknown_type $html_options
  * @return unknown
  */
    function image_tag ($name, $alt=null, $html_options=null) {
        $url = "{$this->base}/images/{$name}";
        return sprintf(TAG_IMAGE, $url, $alt, $this->parse_html_options($html_options, '', ' '));
    }



/**
  * Enter description here...
  *
  * @param unknown_type $tag_name
  * @return unknown
  */
    function tag_value ($tag_name) {
        return isset($this->params['data'][$tag_name])? $this->params['data'][$tag_name]: null;
    }

/**
  * Enter description here...
  *
  * @param unknown_type $field
  * @return unknown
  */
    function tag_is_invalid ($field) {
        return !empty($this->validation_errors[$field]);
    }

/**
  * Enter description here...
  *
  * @param unknown_type $field
  * @param unknown_type $text
  * @return unknown
  */
    function validation_error ($field, $text) {
        return $this->tag_is_invalid($field)? sprintf(SHORT_ERROR_MESSAGE, $text): null;
    }



/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @param unknown_type $link
  */
    function add_crumb ($name, $link) {
        $this->_crumbs[] = array ($name, $link);
    }

/**
  * Enter description here...
  *
  * @return unknown
  */
    function get_crumbs () {

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
  * @param unknown_type $action
  */
    function set_action ($action) {
        $this->action = $action;

        $args = func_get_args();
        call_user_func_array(array(&$this, $action), $args);
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

/**
  * Enter description here...
  *
  * @return unknown
  */
    function validates () {
        $args = func_get_args();
        $errors = call_user_func_array(array(&$this, 'validation_errors'), $args);

        return count($errors);
    }

/**
  * Enter description here...
  *
  * @return unknown
  */
    function validation_errors () {
        $objects = func_get_args();
        if (!count($objects)) return false;

        $errors = array();
        foreach ($objects as $object) {
            $errors = array_merge($errors, $object->invalid_fields());
        }

        return $this->validation_errors = (count($errors)? $errors: false);
    }
}

?>