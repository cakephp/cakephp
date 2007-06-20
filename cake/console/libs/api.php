<?php
/* SVN FILE: $Id$ */
/**
 * API shell to get CakePHP core method signatures.
 *
 * Implementation of a Cake Shell to show CakePHP core method signatures.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.console.libs
 * @since			CakePHP(tm) v 1.2.0.5012
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * API shell to show method signatures of CakePHP core classes.
 *
 * @package		cake
 * @subpackage	cake.cake.console.libs
 */
class ApiShell extends Shell {
/**
 * Map between short name for paths and real paths.
 *
 * @var array
 */
	var $paths = array();
/**
 * Override intialize of the Shell
 *
 * @access public
 */
	function initialize () {
		$this->paths = am($this->paths, array(
			'behavior' => LIBS . 'model' . DS . 'behaviors' . DS,
			'cache' => LIBS . 'cache' . DS,
			'controller' => LIBS . 'controller' . DS,
			'component' => LIBS . 'controller' . DS . 'components' . DS,
			'helper' => LIBS . 'view' . DS . 'helpers' . DS,
			'model' => LIBS . 'model' . DS,
			'view' => LIBS . 'view' . DS
		));
	}
/**
 * Override main() to handle action
 *
 * @access public
 */
	function main() {
		if (empty($this->args)) {
			return $this->help();
		}
		if (count($this->args) == 1 && in_array($this->args[0], array_keys($this->paths))) {
			$this->args[1] = $this->args[0];
		}

		if (count($this->args) > 1) {
			$path = $this->args[0];
			$class = $this->args[1];

			$this->__loadDependencies($path);

			if (in_array(low($path), array('behavior', 'component', 'helper')) && low($path) !== low($class)) {
				if (!preg_match('/' . Inflector::camelize($path) . '$/', $class)) {
					$class .= Inflector::camelize($path);
				}
			} elseif (low($path) === low($class)){
				$class = Inflector::camelize($path);
			}

			if (isset($this->paths[low($path)])) {
				$path = $this->paths[low($path)];
			}
		} else {
			$class = $this->args[0];
			$path = LIBS;
		}

		if (!is_readable($path) || !is_dir($path)) {
			$this->err(sprintf(__('Path %s could not be accessed', true), $path));
			return;
		}

		$File = null;

		$candidates = array(
			Inflector::underscore($class),
			substr(Inflector::underscore($class), 0, strpos(Inflector::underscore($class), '_'))
		);

		foreach ($candidates as $candidate) {
			$File =& new File($path . $candidate . '.php');

			if ($File->exists()) {
				if (!class_exists($class)) {
					include($File->getFullPath());
				}
				if (class_exists($class)) {
					break;
				}
			}

			$File = null;
		}

		if (empty($File)) {
			$this->err(sprintf(__('No file for class %s could be found', true), $class));
			return;
		}

		$parsed = $this->__parseClass($File, $class);

		if (!empty($parsed)) {
			$this->out(ucwords($class));
			$this->hr();

			foreach ($parsed as $method) {
				$this->out("\t" . $method['method'] . "(" . $method['parameters'] . ")", true);
			}
		}
	}

/**
 * Show help for this shell.
 *
 * @access public
 */
	function help() {
		$head  = "Usage: cake api [<path>] <className>\n";
		$head .= "-----------------------------------------------\n";
		$head .= "Parameters:\n\n";

		$commands = array(
			'path' => "\t<path>\n" .
						"\t\tEither a full path or an indicator on where the class is stored.\n".
						"\t\tAvailable values:\n\n".
						"\t\tbehavior\tLook for class in CakePHP behavior path\n".
						"\t\tcache\tLook for class in CakePHP cache path\n".
						"\t\tcontroller\tLook for class in CakePHP controller path\n".
						"\t\tcomponent\tLook for class in CakePHP component path\n".
						"\t\thelper\tLook for class in CakePHP helper path\n".
						"\t\tmodel\tLook for class in CakePHP model path\n".
						"\t\tview\tLook for class in CakePHP view path\n",
			'className' => "\t<className>\n" .
						"\t\tA CakePHP core class name (e.g: Component, HtmlHelper).\n"
		);

		$this->out($head);
		if (!isset($this->args[1])) {
			foreach ($commands as $cmd) {
				$this->out("{$cmd}\n\n");
			}
		} elseif (isset($commands[low($this->args[1])])) {
			$this->out($commands[low($this->args[1])] . "\n\n");
		} else {
			$this->out("Command '" . $this->args[1] . "' not found");
		}
	}

/**
 * Parse a given class (located on given file) and get public methods and their
 * signatures.
 *
 * @param object $File File object
 * @param string $class Class name
 * @return array Methods and signatures indexed by method name
 * @access private
 */
	function __parseClass(&$File, $class) {
		$parsed = array();

		$methods = am(array(), array_diff(get_class_methods($class), get_class_methods(get_parent_class($class))));

		$contents = $File->read();

		foreach ($methods as $method) {
			if (strpos($method, '__') !== 0 && strpos($method, '_') !== 0) {
				$regex = '/\s+function\s+(' . preg_quote($method, '/') . ')\s*\(([^{]*)\)\s*{/is';

				if (preg_match($regex, $contents, $matches)) {
					$parsed[$method] = array(
							'method' => trim($matches[1]),
							'parameters' => trim($matches[2])
					);
				}
			}
		}
		sort($parsed);
		return $parsed;
	}

/**
 * Load dependencies for given element (controller/component/helper)
 *
 * @param string $element Element to load dependency for
 * @access private
 */
	function __loadDependencies($element) {
		switch(low($element)) {
			case 'behavior':
				loadModel(null);
				loadBehavior(null);
				break;
			case 'controller':
				loadController(null);
				break;
			case 'component':
				loadComponent(null);
				break;
			case 'helper':
				loadHelper(null);
				break;
			case 'model':
				loadModel(null);
				break;
		}
	}
}

?>