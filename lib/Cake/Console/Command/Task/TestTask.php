<?php
/**
 * The TestTask handles creating and updating test files.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppShell', 'Console/Command');
App::uses('BakeTask', 'Console/Command/Task');
App::uses('ClassRegistry', 'Utility');

/**
 * Task class for creating and updating test files.
 *
 * @package       Cake.Console.Command.Task
 */
class TestTask extends BakeTask {

/**
 * path to TESTS directory
 *
 * @var string
 */
	public $path = TESTS;

/**
 * Tasks used.
 *
 * @var array
 */
	public $tasks = array('Template');

/**
 * class types that methods can be generated for
 *
 * @var array
 */
	public $classTypes =  array(
		'Model' => 'Model',
		'Controller' => 'Controller',
		'Component' => 'Controller/Component',
		'Behavior' => 'Model/Behavior',
		'Helper' => 'View/Helper'
	);

/**
 * Internal list of fixtures that have been added so far.
 *
 * @var array
 */
	protected $_fixtures = array();

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		parent::execute();
		if (empty($this->args)) {
			$this->_interactive();
		}

		if (count($this->args) == 1) {
			$this->_interactive($this->args[0]);
		}

		if (count($this->args) > 1) {
			$type = Inflector::underscore($this->args[0]);
			if ($this->bake($type, $this->args[1])) {
				$this->out('<success>Done</success>');
			}
		}
	}

/**
 * Handles interactive baking
 *
 * @param string $type
 * @return string|boolean
 */
	protected function _interactive($type = null) {
		$this->interactive = true;
		$this->hr();
		$this->out(__d('cake_console', 'Bake Tests'));
		$this->out(__d('cake_console', 'Path: %s', $this->path));
		$this->hr();

		if ($type) {
			$type = Inflector::camelize($type);
			if (!isset($this->classTypes[$type])) {
				$this->error(__d('cake_console', 'Incorrect type provided. Please choose one of %s', implode(', ', array_keys($this->classTypes))));
			}
		} else {
			$type = $this->getObjectType();
		}
		$className = $this->getClassName($type);
		return $this->bake($type, $className);
	}

/**
 * Completes final steps for generating data to create test case.
 *
 * @param string $type Type of object to bake test case for ie. Model, Controller
 * @param string $className the 'cake name' for the class ie. Posts for the PostsController
 * @return string|boolean
 */
	public function bake($type, $className) {
		$plugin = null;
		if ($this->plugin) {
			$plugin = $this->plugin . '.';
		}

		$realType = $this->mapType($type, $plugin);
		$fullClassName = $this->getRealClassName($type, $className);

		if ($this->typeCanDetectFixtures($type) && $this->isLoadableClass($realType, $fullClassName)) {
			$this->out(__d('cake_console', 'Bake is detecting possible fixtures...'));
			$testSubject = $this->buildTestSubject($type, $className);
			$this->generateFixtureList($testSubject);
		} elseif ($this->interactive) {
			$this->getUserFixtures();
		}
		App::uses($fullClassName, $realType);

		$methods = array();
		if (class_exists($fullClassName)) {
			$methods = $this->getTestableMethods($fullClassName);
		}
		$mock = $this->hasMockClass($type, $fullClassName);
		$construction = $this->generateConstructor($type, $fullClassName);

		$this->out("\n" . __d('cake_console', 'Baking test case for %s %s ...', $className, $type), 1, Shell::QUIET);

		$this->Template->set('fixtures', $this->_fixtures);
		$this->Template->set('plugin', $plugin);
		$this->Template->set(compact(
			'className', 'methods', 'type', 'fullClassName', 'mock',
			'construction', 'realType'
		));
		$out = $this->Template->generate('classes', 'test');

		$filename = $this->testCaseFileName($type, $className);
		$made = $this->createFile($filename, $out);
		if ($made) {
			return $out;
		}
		return false;
	}

/**
 * Interact with the user and get their chosen type. Can exit the script.
 *
 * @return string Users chosen type.
 */
	public function getObjectType() {
		$this->hr();
		$this->out(__d('cake_console', 'Select an object type:'));
		$this->hr();

		$keys = array();
		$i = 0;
		foreach ($this->classTypes as $option => $package) {
			$this->out(++$i . '. ' . $option);
			$keys[] = $i;
		}
		$keys[] = 'q';
		$selection = $this->in(__d('cake_console', 'Enter the type of object to bake a test for or (q)uit'), $keys, 'q');
		if ($selection == 'q') {
			return $this->_stop();
		}
		$types = array_keys($this->classTypes);
		return $types[$selection - 1];
	}

/**
 * Get the user chosen Class name for the chosen type
 *
 * @param string $objectType Type of object to list classes for i.e. Model, Controller.
 * @return string Class name the user chose.
 */
	public function getClassName($objectType) {
		$type = ucfirst(strtolower($objectType));
		$typeLength = strlen($type);
		$type = $this->classTypes[$type];
		if ($this->plugin) {
			$plugin = $this->plugin . '.';
			$options = App::objects($plugin . $type);
		} else {
			$options = App::objects($type);
		}
		$this->out(__d('cake_console', 'Choose a %s class', $objectType));
		$keys = array();
		foreach ($options as $key => $option) {
			$this->out(++$key . '. ' . $option);
			$keys[] = $key;
		}
		while (empty($selection)) {
			$selection = $this->in(__d('cake_console', 'Choose an existing class, or enter the name of a class that does not exist'));
			if (is_numeric($selection) && isset($options[$selection - 1])) {
				$selection = $options[$selection - 1];
			}
			if ($type !== 'Model') {
				$selection = substr($selection, 0, $typeLength * - 1);
			}
		}
		return $selection;
	}

/**
 * Checks whether the chosen type can find its own fixtures.
 * Currently only model, and controller are supported
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @return boolean
 */
	public function typeCanDetectFixtures($type) {
		$type = strtolower($type);
		return in_array($type, array('controller', 'model'));
	}

/**
 * Check if a class with the given package is loaded or can be loaded.
 *
 * @param string $package The package of object you are generating tests for eg. controller
 * @param string $class the Classname of the class the test is being generated for.
 * @return boolean
 */
	public function isLoadableClass($package, $class) {
		App::uses($class, $package);
		return class_exists($class);
	}

/**
 * Construct an instance of the class to be tested.
 * So that fixtures can be detected
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $class the Classname of the class the test is being generated for.
 * @return object And instance of the class that is going to be tested.
 */
	public function &buildTestSubject($type, $class) {
		ClassRegistry::flush();
		App::import($type, $class);
		$class = $this->getRealClassName($type, $class);
		if (strtolower($type) == 'model') {
			$instance = ClassRegistry::init($class);
		} else {
			$instance = new $class();
		}
		return $instance;
	}

/**
 * Gets the real class name from the cake short form. If the class name is already
 * suffixed with the type, the type will not be duplicated.
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $class the Classname of the class the test is being generated for.
 * @return string Real classname
 */
	public function getRealClassName($type, $class) {
		if (strtolower($type) == 'model' || empty($this->classTypes[$type])) {
			return $class;
		}
		if (strlen($class) - strpos($class, $type) == strlen($type)) {
			return $class;
		}
		return $class . $type;
	}

/**
 * Map the types that TestTask uses to concrete types that App::uses can use.
 *
 * @param string $type The type of thing having a test generated.
 * @param string $plugin The plugin name.
 * @return string
 */
	public function mapType($type, $plugin) {
		$type = ucfirst($type);
		if (empty($this->classTypes[$type])) {
			throw new CakeException(__d('cake_dev', 'Invalid object type.'));
		}
		$real = $this->classTypes[$type];
		if ($plugin) {
			$real = trim($plugin, '.') . '.' . $real;
		}
		return $real;
	}

/**
 * Get methods declared in the class given.
 * No parent methods will be returned
 *
 * @param string $className Name of class to look at.
 * @return array Array of method names.
 */
	public function getTestableMethods($className) {
		$classMethods = get_class_methods($className);
		$parentMethods = get_class_methods(get_parent_class($className));
		$thisMethods = array_diff($classMethods, $parentMethods);
		$out = array();
		foreach ($thisMethods as $method) {
			if (substr($method, 0, 1) != '_' && $method != strtolower($className)) {
				$out[] = $method;
			}
		}
		return $out;
	}

/**
 * Generate the list of fixtures that will be required to run this test based on
 * loaded models.
 *
 * @param object $subject The object you want to generate fixtures for.
 * @return array Array of fixtures to be included in the test.
 */
	public function generateFixtureList($subject) {
		$this->_fixtures = array();
		if (is_a($subject, 'Model')) {
			$this->_processModel($subject);
		} elseif (is_a($subject, 'Controller')) {
			$this->_processController($subject);
		}
		return array_values($this->_fixtures);
	}

/**
 * Process a model recursively and pull out all the
 * model names converting them to fixture names.
 *
 * @param Model $subject A Model class to scan for associations and pull fixtures off of.
 * @return void
 */
	protected function _processModel($subject) {
		$this->_addFixture($subject->name);
		$associated = $subject->getAssociated();
		foreach ($associated as $alias => $type) {
			$className = $subject->{$alias}->name;
			if (!isset($this->_fixtures[$className])) {
				$this->_processModel($subject->{$alias});
			}
			if ($type == 'hasAndBelongsToMany') {
				$joinModel = Inflector::classify($subject->hasAndBelongsToMany[$alias]['joinTable']);
				if (!isset($this->_fixtures[$joinModel])) {
					$this->_processModel($subject->{$joinModel});
				}
			}
		}
	}

/**
 * Process all the models attached to a controller
 * and generate a fixture list.
 *
 * @param Controller $subject A controller to pull model names off of.
 * @return void
 */
	protected function _processController($subject) {
		$subject->constructClasses();
		$models = array(Inflector::classify($subject->name));
		if (!empty($subject->uses)) {
			$models = $subject->uses;
		}
		foreach ($models as $model) {
			$this->_processModel($subject->{$model});
		}
	}

/**
 * Add classname to the fixture list.
 * Sets the app. or plugin.plugin_name. prefix.
 *
 * @param string $name Name of the Model class that a fixture might be required for.
 * @return void
 */
	protected function _addFixture($name) {
		$parent = get_parent_class($name);
		$prefix = 'app.';
		if (strtolower($parent) != 'appmodel' && strtolower(substr($parent, -8)) == 'appmodel') {
			$pluginName = substr($parent, 0, strlen($parent) -8);
			$prefix = 'plugin.' . Inflector::underscore($pluginName) . '.';
		}
		$fixture = $prefix . Inflector::underscore($name);
		$this->_fixtures[$name] = $fixture;
	}

/**
 * Interact with the user to get additional fixtures they want to use.
 *
 * @return array Array of fixtures the user wants to add.
 */
	public function getUserFixtures() {
		$proceed = $this->in(__d('cake_console', 'Bake could not detect fixtures, would you like to add some?'), array('y', 'n'), 'n');
		$fixtures = array();
		if (strtolower($proceed) == 'y') {
			$fixtureList = $this->in(__d('cake_console', "Please provide a comma separated list of the fixtures names you'd like to use.\nExample: 'app.comment, app.post, plugin.forums.post'"));
			$fixtureListTrimmed = str_replace(' ', '', $fixtureList);
			$fixtures = explode(',', $fixtureListTrimmed);
		}
		$this->_fixtures = array_merge($this->_fixtures, $fixtures);
		return $fixtures;
	}

/**
 * Is a mock class required for this type of test?
 * Controllers require a mock class.
 *
 * @param string $type The type of object tests are being generated for eg. controller.
 * @return boolean
 */
	public function hasMockClass($type) {
		$type = strtolower($type);
		return $type == 'controller';
	}

/**
 * Generate a constructor code snippet for the type and classname
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $fullClassName The Classname of the class the test is being generated for.
 * @return string Constructor snippet for the thing you are building.
 */
	public function generateConstructor($type, $fullClassName) {
		$type = strtolower($type);
		if ($type == 'model') {
			return "ClassRegistry::init('$fullClassName');\n";
		}
		if ($type == 'controller') {
			$className = substr($fullClassName, 0, strlen($fullClassName) - 10);
			return "new Test$fullClassName();\n\t\t\$this->{$className}->constructClasses();\n";
		}
		return "new $fullClassName();\n";
	}

/**
 * Make the filename for the test case. resolve the suffixes for controllers
 * and get the plugin path if needed.
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $className the Classname of the class the test is being generated for.
 * @return string filename the test should be created on.
 */
	public function testCaseFileName($type, $className) {
		$path = $this->getPath() . 'Case' . DS;
		$type = Inflector::camelize($type);
		if (isset($this->classTypes[$type])) {
			$path .= $this->classTypes[$type] . DS;
		}
		$className = $this->getRealClassName($type, $className);
		return str_replace('/', DS, $path) . Inflector::camelize($className) . 'Test.php';
	}

/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(__d('cake_console', 'Bake test case skeletons for classes.'))
			->addArgument('type', array(
				'help' => __d('cake_console', 'Type of class to bake, can be any of the following: controller, model, helper, component or behavior.'),
				'choices' => array(
					'Controller', 'controller',
					'Model', 'model',
					'Helper', 'helper',
					'Component', 'component',
					'Behavior', 'behavior'
				)
			))->addArgument('name', array(
				'help' => __d('cake_console', 'An existing class to bake tests for.')
			))->addOption('plugin', array(
				'short' => 'p',
				'help' => __d('cake_console', 'CamelCased name of the plugin to bake tests for.')
			))->epilog(__d('cake_console', 'Omitting all arguments and options will enter into an interactive mode.'));
	}
}
