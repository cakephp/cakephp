<?php
/**
 * jQuery Engine Helper for JsHelper
 *
 * Provides jQuery specific Javascript for JsHelper.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright       Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link            http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package         cake
 * @subpackage      cake.
 * @version         
 * @modifiedby      
 * @lastmodified    
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Helper', 'Js');

class JqueryEngineHelper extends JsBaseEngineHelper {
/**
 * Option mappings for jQuery
 *
 * @var array
 **/
	var $_optionMap = array(
		'request' => array(
			'type' => 'dataType',
			'before' => 'beforeSend',
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
		)
	);
/**
 * Create javascript selector for a CSS rule
 *
 * @param string $selector The selector that is targeted
 * @return object instance of $this. Allows chained methods.
 **/
	function get($selector) {
		if ($selector == 'window' || $selector == 'document') {
			$this->selection = '$(' . $selector .')';
		} else {
			$this->selection = '$("' . $selector . '")';
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
 **/
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
 * Create a domReady event. This is a special event in many libraries
 *
 * @param string $functionBody The code to run on domReady
 * @return string completed domReady method
 **/
	function domReady($functionBody) {
		return $this->get('document')->event('ready', $functionBody, array('stop' => false));
	}
/**
 * Create an iteration over the current selection result.
 *
 * @param string $method The method you want to apply to the selection
 * @param string $callback The function body you wish to apply during the iteration.
 * @return string completed iteration
 **/
	function each($callback) {
		return $this->selection . '.each(function () {' . $callback . '});';
	}
/**
 * Trigger an Effect.
 *
 * @param string $name The name of the effect to trigger.
 * @param array $options Array of options for the effect.
 * @return string completed string with effect.
 * @see JsBaseEngineHelper::effect()
 **/
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
 * @param array $options
 * @return string The completed ajax call.
 **/
	function request($url, $options = array()) {
		$url = $this->url($url);
		$options = $this->_mapOptions('request', $options);
		if (isset($options['data']) && is_array($options['data'])) {
			$options['data'] = $this->_toQuerystring($options['data']);
		}
		$options['url'] = $url;
		if (isset($options['update'])) {
			$options['success'] = 'function (msg, status) {$("' . $options['update'] . '").html(msg);}';
			unset($options['update']);
		}
		$callbacks = array('success', 'error', 'beforeSend', 'complete');
		$options = $this->_parseOptions($options, $callbacks);
		return '$.ajax({' . $options .'});';
	}
/**
 * Create a sortable element.
 *
 * Requires both Ui.Core and Ui.Sortables to be loaded.
 *
 * @param array $options Array of options for the sortable.
 * @return string Completed sortable script.
 * @see JsHelper::sortable() for options list.
 **/
	function sortable($options = array()) {
		$options = $this->_mapOptions('sortable', $options);
		$callbacks = array('start', 'sort', 'change', 'beforeStop', 'stop', 'update', 'receive', 'remove', 
			'over', 'out', 'activate', 'deactivate');
		$options = $this->_parseOptions($options, $callbacks);
		return $this->selection . '.sortable({' . $options . '});';
	}
/**
 * Create a Draggable element
 * 
 * Requires both Ui.Core and Ui.Draggable to be loaded.
 *
 * @param array $options Array of options for the draggable element.
 * @return string Completed Draggable script.
 * @see JsHelper::drag() for options list.
 **/
	function drag($options = array()) {
		$options = $this->_mapOptions('drag', $options);
		$callbacks = array('start', 'drag', 'stop');
		$options = $this->_parseOptions($options, $callbacks);
		return $this->selection . '.draggable({' . $options . '});';
	}
/**
 * Create a Droppable element
 * 
 * Requires both Ui.Core and Ui.Droppable to be loaded.
 *
 * @param array $options Array of options for the droppable element.
 * @return string Completed Droppable script.
 * @see JsHelper::drop() for options list.
 **/
	function drop($options = array()) {
		$options = $this->_mapOptions('drop', $options);
		$callbacks = array('activate', 'deactivate', 'over', 'out', 'drop');
		$options = $this->_parseOptions($options, $callbacks);
		return $this->selection . '.droppable({' . $options . '});';
	}
}
?>