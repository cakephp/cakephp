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
class jqueryEngineHelper extends AppHelper {
/**
 * Create javascript selector for a CSS rule
 *
 * @param string $selector The selector that is targeted
 * @param boolean $multiple Whether or not the selector could target more than one element.
 * @return object instance of $this. Allows chained methods.
 **/
	function get($selector, $multiple = false) {
		if ($selector == 'window' || $selector == 'document') {
			$this->selection = "$(" . $selector .")";
		} else {
			$this->selection = "$('" . $selector ."')";
		}
		return $this;
	}
/**
 * Add an event to the script cache. Operates on the currently selected elements.
 *
 * @param string $type Type of event to bind to the current dom id
 * @param string $callback The Javascript function you wish to trigger or the function literal
 * @param boolean $wrap Whether you want your callback wrapped in ```function (event) { }```
 * @return string completed event handler
 **/
	function event($type, $callback, $wrap = false) {
		if ($wrap) {
			$callback = 'function (event) {' . $callback . '}';
		}
		$out = $this->selection . ".bind('{$type}', $callback);";
		return $out;
	}
/**
 * Create a domReady event. This is a special event in many libraries
 *
 * @param string $functionBody The code to run on domReady
 * @return string completed domReady method
 **/
	function domReady($functionBody) {
		return $this->get('document')->event('ready', $functionBody, true);
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
}
?>