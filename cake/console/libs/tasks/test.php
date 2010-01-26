<?php
/**
 * The TestTask handles creating and updating test files.
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
 * @subpackage    cake.cake.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Task class for creating and updating test files.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class TestTask extends Shell {

/**
 * Name of plugin
 *
 * @var string
 * @access public
 */
	var $plugin = null;

/**
 * path to TESTS directory
 *
 * @var string
 * @access public
 */
	var $path = TESTS;

/**
 * Tasks used.
 *
 * @var array
 * @access public
 */
	var $tasks = array('Template');

/**
 * class types that methods can be generated for
 *
 * @var array
 * @access public
 */
	var $classTypes =  array('Model', 'Controller', 'Component', 'Behavior', 'Helper');

/**
 * Internal list of fixtures that have been added so far.
 *
 * @var string
 * @access protected
 */
	var $_fixtures = array();

/**
 * Flag for interactive mode
 *
 * @var boolean
 */
	var $interactive = false;

/**
 * Execution method always used for tasks
 *
 * @access public
 */
	function execute() {
		if (empty($this->args)) {
			$this->__interactive();
		}

		if (count($this->args) == 1) {
			$this->__interactive($this->args[0]);
		}

		if (count($this->args) > 1) {
			$type = Inflector::underscore($this->args[0]);
			if ($this->bake($type, $this->args[1])) {
				$this->out('done');
			}
		}
	}

/**
 * Handles interactive baking
 *
 * @access private
 */
	function __interactive($type = null) {
		$this->interactive = true;
		$this->hr();
		$this->out(__('Bake Tests', true));
		$this->out(sprintf(__("Path: %s", true), $this->path));
		$this->hr();

		$selection = null;
		if ($type) {
			$type = Inflector::camelize($type);
			if (!in_array($type, $this->classTypes)) {
				unset($type);
			}
		}
		if (!$type) {
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
 * @access public
 */
	function bake($type, $className) {
		if ($this->typeCanDetectFixtures($type) && $this->isLoadableClass($type, $className)) {
			$this->out(__('Bake is detecting possible fixtures..', true));
			$testSubject =& $this->buildTestSubject($type, $className);
			$this->generateFixtureList($testSubject);
		} elseif ($this->interactive) {
			$this->getUserFixtures();
		}
		$fullClassName = $this->getRealClassName($type, $className);

		$methods = array();
		if (class_exists($fullClassName)) {
			$methods = $this->getTestableMethods($fullClassName);
		}
		$mock = $this->hasMockClass($type, $fullClassName);
		$construction = $this->generateConstructor($type, $fullClassName);

		$plugin = null;
		if ($this->plugin) {
			$plugin = $this->plugin . '.';
		}

		$this->Template->set('fixtures', $this->_fixtures);
		$this->Template->set('plugin', $plugin);
		$this->Template->set(compact('className', 'methods', 'type', 'fullClassName', 'mock', 'construction'));
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
 * @access public
 */
	function getObjectType() {
		$this->hr();
		$this->out(__("Select an object type:", true));
		$this->hr();

		$keys = array();
		foreach ($this->classTypes as $key => $option) {
			$this->out(++$key . '. ' . $option);
			$keys[] = $key;
		}
		$keys[] = 'q';
		$selection = $this->in(__("Enter the type of object to bake a test for or (q)uit", true), $keys, 'q');
		if ($selection == 'q') {
			return $this->_stop();
		}
		return $this->classTypes[$selection - 1];
	}

/**
 * Get the user chosen Class name for the chosen type
 *
 * @param string $objectType Type of object to list classes for i.e. Model, Controller.
 * @return string Class name the user chose.
 * @access public
 */
	function getClassName($objectType) {
		$options = App::objects(strtolower($objectType));
		$this->out(sprintf(__('Choose a %s class', true), $objectType));
		$keys = array();
		foreach ($options as $key => $option) {
			$this->out(++$key . '. ' . $option);
			$keys[] = $key;
		}
		$selection = $this->in(__('Choose an existing class, or enter the name of a class that does not exist', true));
		if (isset($options[$selection - 1])) {
			return $options[$selection - 1];
		}
		return $selection;
	}

/**
 * Checks whether the chosen type can find its own fixtures.
 * Currently only model, and controller are supported
 * 
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $className the Classname of the class the test is being generated for.
 * @return boolean
 * @access public
 */
	function typeCanDetectFixtures($type) {
		$type = strtolower($type);
		return ($type == 'controller' || $type == 'model');
	}

/**
 * Check if a class with the given type is loaded or can be loaded.
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $className the Classname of the class the test is being generated for.
 * @return boolean
 * @access public
 */
	function isLoadableClass($type, $class) {
		return App::import($type, $class);
	}

/**
 * Construct an instance of the class to be tested.
 * So that fixtures can be detected
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $class the Classname of the class the test is being generated for.
 * @return object And instance of the class that is going to be tested.
 * @access public
 */
	function &buildTestSubject($type, $class) {
		ClassRegistry::flush();
		App::import($type, $class);
		$class = $this->getRealClassName($type, $class);
		if (strtolower($type) == 'model') {
			$instance =& ClassRegistry::init($class);
		} else {
			$instance =& new $class();
		}
		return $instance;
	}

/**
 * Gets the real class name from the cake short form.
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $class the Classname of the class the test is being generated for.
 * @return string Real classname
 * @access public
 */
	function getRealClassName($type, $class) {
		if (strtolower($type) == 'model') {
			return $class;
		}
		return $class . $type;
	}

/**
 * Get methods declared in the class given.
 * No parent methods will be returned
 *
 * @param string $className Name of class to look at.
 * @return array Array of method names.
 * @access public
 */
	function getTestableMethods($className) {
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
 * @access public
 */
	function generateFixtureList(&$subject) {
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
 * @access protected
 */
	function _processModel(&$subject) {
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
 * @access protected
 */
	function _processController(&$subject) {
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
 * @access protected
 */
	function _addFixture($name) {
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
 * @access public
 */
	function getUserFixtures() {
		$proceed = $this->in(__('Bake could not detect fixtures, would you like to add some?', true), array('y','n'), 'n');
		$fixtures = array();
		if (strtolower($proceed) == 'y') {
			$fixtureList = $this->in(__("Please provide a comma separated list of the fixtures names you'd like to use.\nExample: 'app.comment, app.post, plugin.forums.post'", true));
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
 * @access public
 */
	function hasMockClass($type) {
		$type = strtolower($type);
		return $type == 'controller';
	}

/**
 * Generate a constructor code snippet for the type and classname
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $className the Classname of the class the test is being generated for.
 * @return string Constructor snippet for the thing you are building.
 * @access public
 */
	function generateConstructor($type, $fullClassName) {
		$type = strtolower($type);
		if ($type == 'model') {
			return "ClassRegistry::init('$fullClassName');\n";
		}
		if ($type == 'controller') {
			$className = substr($fullClassName, 0, strlen($fullClassName) - 10);
			return "new Test$fullClassName();\n\t\t\$this->{$className}->constructClasses();\n";
		}
		return "new $fullClassName()\n";
	}

/**
 * Make the filename for the test case. resolve the suffixes for controllers
 * and get the plugin path if needed.
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $className the Classname of the class the test is being generated for.
 * @return string filename the test should be created on.
 * @access public
 */
	function testCaseFileName($type, $className) {
		$path = $this->path;
		if (isset($this->plugin)) {
			$path = $this->_pluginPath($this->plugin) . 'tests' . DS;
		}
		$path .= 'cases' . DS . Inflector::tableize($type) . DS;
		if (strtolower($type) == 'controller') {
			$className = $this->getRealClassName($type, $className);
		}
		return $path . Inflector::underscore($className) . '.test.php';
	}

/**
 * Show help file.
 *
 * @return void
 * @access public
 */
	function help() {
		$this->hr();
		$this->out("Usage: cake bake test <type> <class>");
		$this->hr();
		$this->out('Commands:');
		$this->out("");
		$this->out("test model post\n\tbakes a test case for the post model.");
		$this->out("");
		$this->out("test controller comments\n\tbakes a test case for the comments controller.");
		$this->out("");
		$this->out('Arguments:');
		$this->out("\t<type>   Can be any of the following 'controller', 'model', 'helper',\n\t'component', 'behavior'.");
		$this->out("\t<class>  Any existing class for the chosen type.");
		$this->out("");
		$this->out("Parameters:");
		$this->out("\t-plugin  CamelCased name of plugin to bake tests for.");
		$this->out("");
		$this->_stop();
	}
}
?>