<?php
/**
 * jQuery Engine Helper for JsHelper
 *
 * Provides jQuery specific Javascript for JsHelper.
 *
 * Implements the JsHelper interface for jQuery.  All $options arrays
 * support all options found in the JsHelper, as well as those in the jQuery
 * documentation.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright       Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link            http://cakephp.org CakePHP Project
 * @package         cake
 * @subpackage      cake.view.helpers
 * @license         MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Helper', 'Js');

class JqueryEngineHelper extends JsBaseEngineHelper {
/**
 * Option mappings for jQuery
 *
 * @var array
 * @access protected
 */
	var $_optionMap = array(
		'request' => array(
			'type' => 'dataType',
			'before' => 'beforeSend',
			'method' => 'type',
		),
		'sortable' => array(
			'complete' => 'stop',
		),
		'drag' => array(
			'snapGrid' => 'grid',
			'container' => 'containment',
		),
		'drop' => array(
			'leave' => 'out',
			'hover' => 'over'
		),
		'slider' => array(
			'complete' => 'stop',
			'direction' => 'orientation'
		)
	);

/**
 * Callback arguments lists
 *
 * @var string
 * @access protected
 */
	var $_callbackArguments = array(
		'slider' => array(
			'start' => 'event, ui',
			'slide' => 'event, ui',
			'change' => 'event, ui',
			'stop' => 'event, ui'
		),
		'sortable' => array(
			'start' => 'event, ui',
			'sort' => 'event, ui',
			'change' => 'event, ui',
			'beforeStop' => 'event, ui',
			'stop' => 'event, ui',
			'update' => 'event, ui',
			'receive' => 'event, ui',
			'remove' => 'event, ui',
			'over' => 'event, ui',
			'out' => 'event, ui',
			'activate' => 'event, ui',
			'deactivate' => 'event, ui'
		),
		'drag' => array(
			'start' => 'event, ui',
			'drag' => 'event, ui',
			'stop' => 'event, ui',
		),
		'drop' => array(
			'activate' => 'event, ui',
			'deactivate' => 'event, ui',
			'over' => 'event, ui',
			'out' => 'event, ui',
			'drop' => 'event, ui'
		),
		'request' => array(
			'beforeSend' => 'XMLHttpRequest',
			'error' => 'XMLHttpRequest, textStatus, errorThrown',
			'success' => 'data, textStatus',
			'complete' => 'XMLHttpRequest, textStatus',
			'xhr' => ''
		)
	);

/**
 * The variable name of the jQuery Object, useful
 * when jQuery is put into noConflict() mode.
 *
 * @var string
 * @access public
 */
	 var $jQueryObject = '$';

/**
 * Helper function to wrap repetitive simple method templating.
 *
 * @param string $method The method name being generated.
 * @param string $template The method template
 * @param string $selection the selection to apply
 * @param string $options Array of options for method
 * @param string $callbacks Array of callback / special options.
 * @return string Composed method string
 * @access public
 */
	function _methodTemplate($method, $template, $options, $extraSafeKeys = array()) {
		$options = $this->_mapOptions($method, $options);
		$options = $this->_prepareCallbacks($method, $options);
		$callbacks = array_keys($this->_callbackArguments[$method]);
		if (!empty($extraSafeKeys)) {
			$callbacks = array_merge($callbacks, $extraSafeKeys);
		}
		$options = $this->_parseOptions($options, $callbacks);
		return sprintf($template, $this->selection, $options);
	}

/**
 * Create javascript selector for a CSS rule
 *
 * @param string $selector The selector that is targeted
 * @return object instance of $this. Allows chained methods.
 * @access public
 */
	function get($selector) {
		if ($selector == 'window' || $selector == 'document') {
			$this->selection = $this->jQueryObject . '(' . $selector .')';
		} else {
			$this->selection = $this->jQueryObject . '("' . $selector . '")';
		}
		return $this;
	}

/**
 * Add an event to the script cache. Operates on the currently selected elements.
 *
 * ### Options
 *
 * - 'wrap' - Whether you want the callback wrapped in an anonymous function. (defaults true)
 * - 'stop' - Whether you want the event to stopped. (defaults true)
 *
 * @param string $type Type of event to bind to the current dom id
 * @param string $callback The Javascript function you wish to trigger or the function literal
 * @param array $options Options for the event.
 * @return string completed event handler
 * @access public
 */
	function event($type, $callback, $options = array()) {
		$defaults = array('wrap' => true, 'stop' => true);
		$options = array_merge($defaults, $options);

		$function = 'function (event) {%s}';
		if ($options['wrap'] && $options['stop']) {
			$callback .= "\nreturn false;";
		}
		if ($options['wrap']) {
			$callback = sprintf($function, $callback);
		}
		return sprintf('%s.bind("%s", %s);', $this->selection, $type, $callback);
	}

/**
 * Create a domReady event. For jQuery. This method does not 
 * bind a 'traditional event' as `$(document).bind('ready', fn)`
 * Works in an entirely different fashion than  `$(document).ready()`
 * The first will not run the function when eval()'d as part of a response
 * The second will.  Because of the way that ajax pagination is done
 * `$().ready()` is used.
 *
 * @param string $functionBody The code to run on domReady
 * @return string completed domReady method
 * @access public
 */
	function domReady($functionBody) {
		return $this->jQueryObject . '(document).ready(function () {' . $functionBody . '});';
	}

/**
 * Create an iteration over the current selection result.
 *
 * @param string $method The method you want to apply to the selection
 * @param string $callback The function body you wish to apply during the iteration.
 * @return string completed iteration
 * @access public
 */
	function each($callback) {
		return $this->selection . '.each(function () {' . $callback . '});';
	}

/**
 * Trigger an Effect.
 *
 * @param string $name The name of the effect to trigger.
 * @param array $options Array of options for the effect.
 * @return string completed string with effect.
 * @access public
 * @see JsBaseEngineHelper::effect()
 */
	function effect($name, $options = array()) {
		$speed = null;
		if (isset($options['speed']) && in_array($options['speed'], array('fast', 'slow'))) {
			$speed = $this->value($options['speed']);
		}
		$effect = '';
		switch ($name) {
			case 'slideIn':
			case 'slideOut':
				$name = ($name == 'slideIn') ? 'slideDown' : 'slideUp';
			case 'hide':
			case 'show':
 			case 'fadeIn':
			case 'fadeOut':
			case 'slideDown':
			case 'slideUp':
				$effect = ".$name($speed);";
			break;
		}
		return $this->selection . $effect;
	}

/**
 * Create an $.ajax() call.
 *
 * If the 'update' key is set, success callback will be overridden.
 *
 * @param mixed $url
 * @param array $options See JsHelper::request() for options.
 * @return string The completed ajax call.
 * @access public
 * @see JsBaseEngineHelper::request() for options list.
 */
	function request($url, $options = array()) {
		$url = $this->url($url);
		$options = $this->_mapOptions('request', $options);
		if (isset($options['data']) && is_array($options['data'])) {
			$options['data'] = $this->_toQuerystring($options['data']);
		}
		$options['url'] = $url;
		if (isset($options['update'])) {
			$wrapCallbacks = isset($options['wrapCallbacks']) ? $options['wrapCallbacks'] : true;
			$success = '';
			if(isset($options['success']) AND !empty($options['success'])) {
				$success .= $options['success'];
			}
			$success .= $this->jQueryObject . '("' . $options['update'] . '").html(data);';
			if (!$wrapCallbacks) {
				$success = 'function (data, textStatus) {' . $success . '}';
			}
			$options['dataType'] = 'html';
			$options['success'] = $success;
			unset($options['update']);
		}
		$callbacks = array('success', 'error', 'beforeSend', 'complete');
		if (!empty($options['dataExpression'])) {
			$callbacks[] = 'data';
			unset($options['dataExpression']);
		}
		$options = $this->_prepareCallbacks('request', $options);
		$options = $this->_parseOptions($options, $callbacks);
		return $this->jQueryObject . '.ajax({' . $options .'});';
	}

/**
 * Create a sortable element.
 *
 * Requires both Ui.Core and Ui.Sortables to be loaded.
 *
 * @param array $options Array of options for the sortable.
 * @return string Completed sortable script.
 * @access public
 * @see JsBaseEngineHelper::sortable() for options list.
 */
	function sortable($options = array()) {
		$template = '%s.sortable({%s});';
		return $this->_methodTemplate('sortable', $template, $options);
	}

/**
 * Create a Draggable element
 *
 * Requires both Ui.Core and Ui.Draggable to be loaded.
 *
 * @param array $options Array of options for the draggable element.
 * @return string Completed Draggable script.
 * @access public
 * @see JsBaseEngineHelper::drag() for options list.
 */
	function drag($options = array()) {
		$template = '%s.draggable({%s});';
		return $this->_methodTemplate('drag', $template, $options);
	}

/**
 * Create a Droppable element
 *
 * Requires both Ui.Core and Ui.Droppable to be loaded.
 *
 * @param array $options Array of options for the droppable element.
 * @return string Completed Droppable script.
 * @access public
 * @see JsBaseEngineHelper::drop() for options list.
 */
	function drop($options = array()) {
		$template = '%s.droppable({%s});';
		return $this->_methodTemplate('drop', $template, $options);
	}

/**
 * Create a Slider element
 *
 * Requires both Ui.Core and Ui.Slider to be loaded.
 *
 * @param array $options Array of options for the droppable element.
 * @return string Completed Slider script.
 * @access public
 * @see JsBaseEngineHelper::slider() for options list.
 */
	function slider($options = array()) {
		$callbacks = array('start', 'change', 'slide', 'stop');
		$template = '%s.slider({%s});';
		return $this->_methodTemplate('slider', $template, $options, $callbacks);
	}

/**
 * Serialize a form attached to $selector. If the current selection is not an input or
 * form, errors will be created in the Javascript.
 *
 * @param array $options Options for the serialization
 * @return string completed form serialization script.
 * @access public
 * @see JsBaseEngineHelper::serializeForm() for option list.
 */
	function serializeForm($options = array()) {
		$options = array_merge(array('isForm' => false, 'inline' => false), $options);
		$selector = $this->selection;
		if (!$options['isForm']) {
			$selector = $this->selection . '.closest("form")';
		}
		$method = '.serialize()';
		if (!$options['inline']) {
			$method .= ';';
		}
		return $selector . $method;
	}
}
