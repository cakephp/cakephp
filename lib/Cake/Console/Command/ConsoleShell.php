<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppShell', 'Console/Command');

/**
 * Provides a very basic 'interactive' console for CakePHP apps.
 *
 * @package       Cake.Console.Command
 */
class ConsoleShell extends AppShell {

/**
 * Available binding types
 *
 * @var array
 */
	public $associations = array('hasOne', 'hasMany', 'belongsTo', 'hasAndBelongsToMany');

/**
 * Chars that describe invalid commands
 *
 * @var array
 */
	public $badCommandChars = array('$', ';');

/**
 * Available models
 *
 * @var array
 */
	public $models = array();

/**
 * Override startup of the Shell
 *
 * @return void
 */
	public function startup() {
		App::uses('Dispatcher', 'Routing');
		$this->Dispatcher = new Dispatcher();
		$this->models = App::objects('Model');

		foreach ($this->models as $model) {
			$class = $model;
			$this->models[$model] = $class;
			App::uses($class, 'Model');
			$this->{$class} = new $class();
		}
		$this->out(__d('cake_console', 'Model classes:'));
		$this->hr();

		foreach ($this->models as $model) {
			$this->out(" - {$model}");
		}

		if (!$this->_loadRoutes()) {
			$message = __d(
				'cake_console',
				'There was an error loading the routes config. Please check that the file exists and contains no errors.'
			);
			$this->err($message);
		}
	}

	public function getOptionParser() {
		$description = array(
			'The interactive console is a tool for testing parts of your',
			'app before you write code.',
			'',
			'See below for a list of supported commands.'
		);

		$epilog = array(
			'<info>Model testing</info>',
			'',
			'To test model results, use the name of your model without a leading $',
			'e.g. Foo->find("all")',
			"",
			'To dynamically set associations, you can do the following:',
			'',
			"\tModelA bind <association> ModelB",
			'',
			"where the supported associations are hasOne, hasMany, belongsTo, hasAndBelongsToMany",
			"",
			'To dynamically remove associations, you can do the following:',
			'',
			"\t ModelA unbind <association> ModelB",
			'',
			"where the supported associations are the same as above",
			"",
			"To save a new field in a model, you can do the following:",
			'',
			"\tModelA->save(array('foo' => 'bar', 'baz' => 0))",
			'',
			"where you are passing a hash of data to be saved in the format",
			"of field => value pairs",
			"",
			"To get column information for a model, use the following:",
			'',
			"\tModelA columns",
			'',
			"which returns a list of columns and their type",
			"",
			'<info>Route testing</info>',
			"",
			'To test URLs against your app\'s route configuration, type:',
			"",
			"\tRoute <url>",
			"",
			"where url is the path to your your action plus any query parameters,",
			"minus the application's base path. For example:",
			"",
			"\tRoute /posts/view/1",
			"",
			"will return something like the following:",
			"",
			"\tarray(",
			"\t  [...]",
			"\t  'controller' => 'posts',",
			"\t  'action' => 'view',",
			"\t  [...]",
			"\t)",
			"",
			'Alternatively, you can use simple array syntax to test reverse',
			'To reload your routes config (Config/routes.php), do the following:',
			"",
			"\tRoutes reload",
			"",
			'To show all connected routes, do the following:',
			'',
			"\tRoutes show",
		);
		return parent::getOptionParser()
			->description($description)
			->epilog($epilog);
	}
/**
 * Prints the help message
 *
 * @return void
 */
	public function help() {
		$optionParser = $this->getOptionParser();
		$this->out($optionParser->epilog());
	}

/**
 * Override main() to handle action
 *
 * @param string $command
 * @return void
 */
	public function main($command = null) {
		while (true) {
			if (empty($command)) {
				$command = trim($this->in(''));
			}

			switch ($command) {
				case 'help':
					$this->help();
				break;
				case 'quit':
				case 'exit':
					return true;
				case 'models':
					$this->out(__d('cake_console', 'Model classes:'));
					$this->hr();
					foreach ($this->models as $model) {
						$this->out(" - {$model}");
					}
				break;
				case preg_match("/^(\w+) bind (\w+) (\w+)/", $command, $tmp):
					foreach ($tmp as $data) {
						$data = strip_tags($data);
						$data = str_replace($this->badCommandChars, "", $data);
					}

					$modelA = $tmp[1];
					$association = $tmp[2];
					$modelB = $tmp[3];

					if ($this->_isValidModel($modelA) && $this->_isValidModel($modelB) && in_array($association, $this->associations)) {
						$this->{$modelA}->bindModel(array($association => array($modelB => array('className' => $modelB))), false);
						$this->out(__d('cake_console', "Created %s association between %s and %s",
							$association, $modelA, $modelB));
					} else {
						$this->out(__d('cake_console', "Please verify you are using valid models and association types"));
					}
				break;
				case preg_match("/^(\w+) unbind (\w+) (\w+)/", $command, $tmp):
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

					if ($this->_isValidModel($modelA) && $this->_isValidModel($modelB) && in_array($association, $this->associations) && $validCurrentAssociation) {
						$this->{$modelA}->unbindModel(array($association => array($modelB)));
						$this->out(__d('cake_console', "Removed %s association between %s and %s",
							$association, $modelA, $modelB));
					} else {
						$this->out(__d('cake_console', "Please verify you are using valid models, valid current association, and valid association types"));
					}
				break;
				case (strpos($command, "->find") > 0):
					// Remove any bad info
					$command = strip_tags($command);
					$command = str_replace($this->badCommandChars, "", $command);

					// Do we have a valid model?
					list($modelToCheck, $tmp) = explode('->', $command);

					if ($this->_isValidModel($modelToCheck)) {
						$findCommand = "\$data = \$this->$command;";
						//@codingStandardsIgnoreStart
						@eval($findCommand);
						//@codingStandardsIgnoreEnd

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

												$this->out();
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

											$this->out();
										} else {
											$this->out("\t$field: $value");
										}
									}
								}
							}
						} else {
							$this->out();
							$this->out(__d('cake_console', "No result set found"));
						}
					} else {
						$this->out(__d('cake_console', "%s is not a valid model", $modelToCheck));
					}

				break;
				case (strpos($command, '->save') > 0):
					// Validate the model we're trying to save here
					$command = strip_tags($command);
					$command = str_replace($this->badCommandChars, "", $command);
					list($modelToSave, $tmp) = explode("->", $command);

					if ($this->_isValidModel($modelToSave)) {
						// Extract the array of data we are trying to build
						list(, $data) = explode("->save", $command);
						$data = preg_replace('/^\(*(array)?\(*(.+?)\)*$/i', '\\2', $data);
						$saveCommand = "\$this->{$modelToSave}->save(array('{$modelToSave}' => array({$data})));";
						//@codingStandardsIgnoreStart
						@eval($saveCommand);
						//@codingStandardsIgnoreEnd
						$this->out(__d('cake_console', 'Saved record for %s', $modelToSave));
					}
				break;
				case preg_match("/^(\w+) columns/", $command, $tmp):
					$modelToCheck = strip_tags(str_replace($this->badCommandChars, "", $tmp[1]));

					if ($this->_isValidModel($modelToCheck)) {
						// Get the column info for this model
						$fieldsCommand = "\$data = \$this->{$modelToCheck}->getColumnTypes();";
						//@codingStandardsIgnoreStart
						@eval($fieldsCommand);
						//@codingStandardsIgnoreEnd

						if (is_array($data)) {
							foreach ($data as $field => $type) {
								$this->out("\t{$field}: {$type}");
							}
						}
					} else {
						$this->out(__d('cake_console', "Please verify that you selected a valid model"));
					}
				break;
				case preg_match("/^routes\s+reload/i", $command, $tmp):
					if (!$this->_loadRoutes()) {
						$this->err(__d('cake_console', "There was an error loading the routes config. Please check that the file exists and is free of parse errors."));
						break;
					}
					$this->out(__d('cake_console', "Routes configuration reloaded, %d routes connected", count(Router::$routes)));
				break;
				case preg_match("/^routes\s+show/i", $command, $tmp):
					$this->out(print_r(Hash::combine(Router::$routes, '{n}.template', '{n}.defaults'), true));
				break;
				case (preg_match("/^route\s+(\(.*\))$/i", $command, $tmp) == true):
					//@codingStandardsIgnoreStart
					if ($url = eval('return array' . $tmp[1] . ';')) {
						//@codingStandardsIgnoreEnd
						$this->out(Router::url($url));
					}
				break;
				case preg_match("/^route\s+(.*)/i", $command, $tmp):
					$this->out(var_export(Router::parse($tmp[1]), true));
				break;
				default:
					$this->out(__d('cake_console', "Invalid command"));
					$this->out();
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
 */
	protected function _isValidModel($modelToCheck) {
		return in_array($modelToCheck, $this->models);
	}

/**
 * Reloads the routes configuration from app/Config/routes.php, and compiles
 * all routes found
 *
 * @return boolean True if config reload was a success, otherwise false
 */
	protected function _loadRoutes() {
		Router::reload();
		extract(Router::getNamedExpressions());

		//@codingStandardsIgnoreStart
		if (!@include APP . 'Config' . DS . 'routes.php') {
			//@codingStandardsIgnoreEnd
			return false;
		}
		CakePlugin::routes();

		Router::parse('/');
		return true;
	}

}
