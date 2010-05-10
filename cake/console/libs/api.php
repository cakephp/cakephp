<?php
/**
 * API shell to get CakePHP core method signatures.
 *
 * Implementation of a Cake Shell to show CakePHP core method signatures.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * API shell to show method signatures of CakePHP core classes.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs
 */
class ApiShell extends Shell {

/**
 * Map between short name for paths and real paths.
 *
 * @var array
 * @access public
 */
	var $paths = array();

/**
 * Override intialize of the Shell
 *
 * @access public
 */
	function initialize() {
		$this->paths = array_merge($this->paths, array(
			'behavior' => LIBS . 'model' . DS . 'behaviors' . DS,
			'cache' => LIBS . 'cache' . DS,
			'controller' => LIBS . 'controller' . DS,
			'component' => LIBS . 'controller' . DS . 'components' . DS,
			'helper' => LIBS . 'view' . DS . 'helpers' . DS,
			'model' => LIBS . 'model' . DS,
			'view' => LIBS . 'view' . DS,
			'core' => LIBS
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

		$type = strtolower($this->args[0]);

		if (isset($this->paths[$type])) {
			$path = $this->paths[$type];
		} else {
			$path = $this->paths['core'];
		}

		if (count($this->args) == 1) {
			$file = $type;
			$class = Inflector::camelize($type);
		} elseif (count($this->args) > 1) {
			$file = Inflector::underscore($this->args[1]);
			$class = Inflector::camelize($file);
		}

		$objects = App::objects('class', $path);
		if (in_array($class, $objects)) {
			if (in_array($type, array('behavior', 'component', 'helper')) && $type !== $file) {
				if (!preg_match('/' . Inflector::camelize($type) . '$/', $class)) {
					$class .= Inflector::camelize($type);
				}
			}

		} else {
			$this->err(sprintf(__("%s not found", true), $class));
			$this->_stop();
		}

		$parsed = $this->__parseClass($path . $file .'.php');

		if (!empty($parsed)) {
			if (isset($this->params['m'])) {
				if (!isset($parsed[$this->params['m']])) {
					$this->err(sprintf(__("%s::%s() could not be found", true), $class, $this->params['m']));
					$this->_stop();
				}
				$method = $parsed[$this->params['m']];
				$this->out($class .'::'.$method['method'] . $method['parameters']);
				$this->hr();
				$this->out($method['comment'], true);
			} else {
				$this->out(ucwords($class));
				$this->hr();
				$i = 0;
				foreach ($parsed as $method) {
					$list[] = ++$i . ". " . $method['method'] . $method['parameters'];
				}
				$this->out($list);

				$methods = array_keys($parsed);
				while ($number = strtolower($this->in(__('Select a number to see the more information about a specific method. q to quit. l to list.', true), null, 'q'))) {
					if ($number === 'q') {
						$this->out(__('Done', true));
						$this->_stop();
					}

					if ($number === 'l') {
						$this->out($list);
					}

					if (isset($methods[--$number])) {
						$method = $parsed[$methods[$number]];
						$this->hr();
						$this->out($class .'::'.$method['method'] . $method['parameters']);
						$this->hr();
						$this->out($method['comment'], true);
					}
				}
			}
		}
	}

/**
 * Show help for this shell.
 *
 * @access public
 */
	function help() {
		$head  = "Usage: cake api [<type>] <className> [-m <method>]\n";
		$head .= "-----------------------------------------------\n";
		$head .= "Parameters:\n\n";

		$commands = array(
			'path' => "\t<type>\n" .
				"\t\tEither a full path or type of class (model, behavior, controller, component, view, helper).\n".
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
		} elseif (isset($commands[strtolower($this->args[1])])) {
			$this->out($commands[strtolower($this->args[1])] . "\n\n");
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
	function __parseClass($path) {
		$parsed = array();

		$File = new File($path);
		if (!$File->exists()) {
			$this->err(sprintf(__("%s could not be found", true), $File->name));
			$this->_stop();
		}

		$contents = $File->read();

		if (preg_match_all('%(/\\*\\*[\\s\\S]*?\\*/)(\\s+function\\s+\\w+)(\\(.*\\))%', $contents, $result, PREG_PATTERN_ORDER)) {
			foreach ($result[2] as $key => $method) {
				$method = str_replace('function ', '', trim($method));

				if (strpos($method, '__') === false && $method[0] != '_') {
					$parsed[$method] = array(
						'comment' => str_replace(array('/*', '*/', '*'), '', trim($result[1][$key])),
						'method' => $method,
						'parameters' => trim($result[3][$key])
					);
				}
			}
		}
		ksort($parsed);
		return $parsed;
	}
}
