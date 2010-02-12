<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
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
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * @package       cake
 * @subpackage    cake.cake.console.libs
 */
class ConsoleShell extends Shell {
/**
 * Available binding types
 *
 * @var array
 * @access public
 */
	var $associations = array('hasOne', 'hasMany', 'belongsTo', 'hasAndBelongsToMany');
/**
 * Chars that describe invalid commands
 *
 * @var array
 * @access public
 */
	var $badCommandChars = array('$', ';');
/**
 * Available models
 *
 * @var array
 * @access public
 */
	var $models = array();
/**
 * Override intialize of the Shell
 *
 * @access public
 */
	function initialize() {
		require_once CAKE . 'dispatcher.php';
		$this->Dispatcher = new Dispatcher();
		$this->models = Configure::listObjects('model');
		App::import('Model', $this->models);

		foreach ($this->models as $model) {
			$class = Inflector::camelize(r('.php', '', $model));
			$this->models[$model] = $class;
			$this->{$class} =& new $class();
		}
		$this->out('Model classes:');
		$this->out('--------------');

		foreach ($this->models as $model) {
			$this->out(" - {$model}");
		}
		$this->__loadRoutes();
	}
/**
 * Prints the help message
 *
 * @access public
 */
	function help() {
		$this->main('help');
	}
/**
 * Override main() to handle action
 *
 * @access public
 */
	function main($command = null) {
		while (true) {
			if (empty($command)) {
				$command = trim($this->in(''));
			}

			switch ($command) {
				case 'help':
					$this->out('Console help:');
					$this->out('-------------');
					$this->out('The interactive console is a tool for testing parts of your app before you commit code');
					$this->out('');
					$this->out('Model testing:');
					$this->out('To test model results, use the name of your model without a leading $');
					$this->out('e.g. Foo->find("all")');
					$this->out('');
					$this->out('To dynamically set associations, you can do the following:');
					$this->out("\tModelA bind <association> ModelB");
					$this->out("where the supported assocations are hasOne, hasMany, belongsTo, hasAndBelongsToMany");
					$this->out('');
					$this->out('To dynamically remove associations, you can do the following:');
					$this->out("\t ModelA unbind <association> ModelB");
					$this->out("where the supported associations are the same as above");
					$this->out('');
					$this->out("To save a new field in a model, you can do the following:");
					$this->out("\tModelA->save(array('foo' => 'bar', 'baz' => 0))");
					$this->out("where you are passing a hash of data to be saved in the format");
					$this->out("of field => value pairs");
					$this->out('');
					$this->out("To get column information for a model, use the following:");
					$this->out("\tModelA columns");
					$this->out("which returns a list of columns and their type");
					$this->out('');
					$this->out('Route testing:');
					$this->out('To test URLs against your app\'s route configuration, type:');
					$this->out("\tRoute <url>");
					$this->out("where url is the path to your your action plus any query parameters, minus the");
					$this->out("application's base path");
					$this->out('');
					$this->out('To reload your routes config (config/routes.php), do the following:');
					$this->out("\tRoutes reload");
					$this->out('');
					$this->out('');
					$this->out('To show all connected routes, do the following:');
					$this->out("\tRoutes show");
					$this->out('');
				break;
				case 'quit':
				case 'exit':
					return true;
				break;
				case 'models':
					$this->out('Model classes:');
					$this->hr();
					foreach ($this->models as $model) {
						$this->out(" - {$model}");
					}
				break;
				case (preg_match("/^(\w+) bind (\w+) (\w+)/", $command, $tmp) == true):
					foreach ($tmp as $data) {
						$data = strip_tags($data);
						$data = str_replace($this->badCommandChars, "", $data);
					}

					$modelA = $tmp[1];
					$association = $tmp[2];
					$modelB = $tmp[3];

					if ($this->__isValidModel($modelA) && $this->__isValidModel($modelB) && in_array($association, $this->associations)) {
						$this->{$modelA}->bindModel(array($association => array($modelB => array('className' => $modelB))), false);
						$this->out("Created $association association between $modelA and $modelB");
					} else {
						$this->out("Please verify you are using valid models and association types");
					}
				break;
				case (preg_match("/^(\w+) unbind (\w+) (\w+)/", $command, $tmp) == true):
					foreach ($tmp as $data) {
						$data = strip_tags($data);
						$data = str_replace($this->badCommandChars, "", $data);
					}

					$modelA = $tmp[1];
					$association = $tmp[2];
					$modelB = $tmp[3];

					// Verify that there is actually an association to unbind
					$currentAssociations = $this->{$modelA}->getAssociated();
					$validCurrentAssociation = false;

					foreach ($currentAssociations as $model => $currentAssociation) {
						if ($model == $modelB && $association == $currentAssociation) {
							$validCurrentAssociation = true;
						}
					}

					if ($this->__isValidModel($modelA) && $this->__isValidModel($modelB) && in_array($association, $this->associations) && $validCurrentAssociation) {
						$this->{$modelA}->unbindModel(array($association => array($modelB)));
						$this->out("Removed $association association between $modelA and $modelB");
					} else {
						$this->out("Please verify you are using valid models, valid current association, and valid association types");
					}
				break;
				case (strpos($command, "->find") > 0):
					// Remove any bad info
					$command = strip_tags($command);
					$command = str_replace($this->badCommandChars, "", $command);

					// Do we have a valid model?
					list($modelToCheck, $tmp) = explode('->', $command);

					if ($this->__isValidModel($modelToCheck)) {
						$findCommand = "\$data = \$this->$command;";
						@eval($findCommand);

						if (is_array($data)) {
							foreach ($data as $idx => $results) {
								if (is_numeric($idx)) { // findAll() output
									foreach ($results as $modelName => $result) {
										$this->out("$modelName");

										foreach ($result as $field => $value) {
											if (is_array($value)) {
												foreach ($value as $field2 => $value2) {
													$this->out("\t$field2: $value2");
												}

												$this->out("");
											} else {
												$this->out("\t$field: $value");
											}
										}
									}
								} else { // find() output
									$this->out($idx);

									foreach ($results as $field => $value) {
										if (is_array($value)) {
											foreach ($value as $field2 => $value2) {
												$this->out("\t$field2: $value2");
											}

											$this->out("");
										} else {
											$this->out("\t$field: $value");
										}
									}
								}
							}
						} else {
							$this->out("\nNo result set found");
						}
					} else {
						$this->out("$modelToCheck is not a valid model");
					}

				break;
				case (strpos($command, '->save') > 0):
					// Validate the model we're trying to save here
					$command = strip_tags($command);
					$command = str_replace($this->badCommandChars, "", $command);
					list($modelToSave, $tmp) = explode("->", $command);

					if ($this->__isValidModel($modelToSave)) {
						// Extract the array of data we are trying to build
						list($foo, $data) = explode("->save", $command);
						$data = preg_replace('/^\(*(array)?\(*(.+?)\)*$/i', '\\2', $data);
						$saveCommand = "\$this->{$modelToSave}->save(array('{$modelToSave}' => array({$data})));";
						@eval($saveCommand);
						$this->out('Saved record for ' . $modelToSave);
					}
				break;
				case (preg_match("/^(\w+) columns/", $command, $tmp) == true):
					$modelToCheck = strip_tags(str_replace($this->badCommandChars, "", $tmp[1]));

					if ($this->__isValidModel($modelToCheck)) {
						// Get the column info for this model
						$fieldsCommand = "\$data = \$this->{$modelToCheck}->getColumnTypes();";
						@eval($fieldsCommand);

						if (is_array($data)) {
							foreach ($data as $field => $type) {
								$this->out("\t{$field}: {$type}");
							}
						}
					} else {
						$this->out("Please verify that you selected a valid model");
					}
				break;
				case (preg_match("/^routes\s+reload/i", $command, $tmp) == true):
					$router =& Router::getInstance();
					if (!$this->__loadRoutes()) {
						$this->out("There was an error loading the routes config.  Please check that the file");
						$this->out("exists and is free of parse errors.");
						break;
					}
					$this->out("Routes configuration reloaded, " . count($router->routes) . " routes connected");
				break;
				case (preg_match("/^routes\s+show/i", $command, $tmp) == true):
					$router =& Router::getInstance();
					$this->out(implode("\n", Set::extract($router->routes, '{n}.0')));
				break;
				case (preg_match("/^route\s+(.*)/i", $command, $tmp) == true):
					$this->out(var_export(Router::parse($tmp[1]), true));
				break;
				default:
					$this->out("Invalid command\n");
				break;
			}
			$command = '';
		}
	}
/**
 * Tells if the specified model is included in the list of available models
 *
 * @param string $modelToCheck
 * @return boolean true if is an available model, false otherwise
 * @access private
 */
	function __isValidModel($modelToCheck) {
		return in_array($modelToCheck, $this->models);
	}
/**
 * Reloads the routes configuration from config/routes.php, and compiles
 * all routes found
 *
 * @return boolean True if config reload was a success, otherwise false
 * @access private
 */
	function __loadRoutes() {
		$router =& Router::getInstance();

		$router->reload();
		extract($router->getNamedExpressions());

		if (!@include(CONFIGS . 'routes.php')) {
			return false;
		}
		$router->parse('/');

		foreach (array_keys($router->getNamedExpressions()) as $var) {
			unset(${$var});
		}
		for ($i = 0; $i < count($router->routes); $i++) {
			$router->compile($i);
		}
		return true;
	}
}
?>