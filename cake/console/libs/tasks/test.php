<?php
/* SVN FILE: $Id$ */
/**
 * The TestTask handles creating and updating test files.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 * @since         CakePHP(tm) v 1.2
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
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
 **/
	var $tasks = array('Template');

/**
 * class types that methods can be generated for
 *
 * @var array
 **/
	var $classTypes =  array('Model', 'Controller', 'Component', 'Behavior', 'Helper');

/**
 * Internal list of fixtures that have been added so far.
 *
 * @var string
 **/
	var $_fixtures = array();

/**
 * Flag for interactive mode
 *
 * @var boolean
 **/
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
		$out = $this->Template->generate('objects', 'test');

		if (strpos($this->path, $type) === false) {
			$this->filePath = $this->path . 'cases' . DS . Inflector::tableize($type) . DS;
		}
		$made = $this->createFile($this->filePath . Inflector::underscore($className) . '.test.php', $out);
		if ($made) {
			return $out;
		}
		return false;
	}

/**
 * Interact with the user and get their chosen type. Can exit the script.
 *
 * @return string Users chosen type.
 **/
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
 **/
	function getClassName($objectType) {
		$options = Configure::listObjects(strtolower($objectType));
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
 * @return boolean
 **/
	function typeCanDetectFixtures($type) {
		$type = strtolower($type);
		return ($type == 'controller' || $type == 'model');
	}

/**
 * Check if a class with the given type is loaded or can be loaded.
 *
 * @return boolean
 **/
	function isLoadableClass($type, $class) {
		return App::import($type, $class);
	}

/**
 * Construct an instance of the class to be tested.
 * So that fixtures can be detected
 *
 * @return object
 **/
	function &buildTestSubject($type, $class) {
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
 * @return string Real classname
 **/
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
 **/
	function getTestableMethods($className) {
		$classMethods = get_class_methods($className);
		$parentMethods = get_class_methods(get_parent_class($className));
		$thisMethods = array_diff($classMethods, $parentMethods);
		$out = array();
		foreach ($thisMethods as $method) {
			if (substr($method, 0, 1) != '_') {
				$out[] = $method;
			}
		}
		return $out;
	}

/**
 * Generate the list of fixtures that will be required to run this test based on
 * loaded models.
 *
 * @param object The object you want to generate fixtures for.
 * @return array Array of fixtures to be included in the test.
 **/
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
 * @return void
 * @access protected
 **/
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
 * @return void
 * @access protected
 **/
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
 * @return void
 * @access protected
 **/
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
 * @return void
 **/
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
 * @return boolean
 **/
	function hasMockClass($type) {
		$type = strtolower($type);
		return $type == 'controller';
	}

/**
 * Generate a constructor code snippet for the type and classname
 *
 * @return string Constructor snippet for the thing you are building.
 **/
	function generateConstructor($type, $fullClassName) {
		$type = strtolower($type);
		if ($type == 'model') {
			return "ClassRegistry::init('$fullClassName');\n";
		}
		if ($type == 'controller') {
			return "new Test$fullClassName();\n\t\t\$this->{$fullClassName}->constructClasses();\n";
		}
		return "new $fullClassName()\n";
	}

/**
 * Handles the extra stuff needed
 *
 * @access private
 */
	function __extras($class) {
		$extras = null;
		switch ($class) {
			case 'Model':
				$extras = "\n\tvar \$cacheSources = false;";
				$extras .= "\n\tvar \$useDbConfig = 'test_suite';\n";
			break;
		}
		return $extras;
	}

/**
 * Create a test for a Model object.
 *
 * @return void
 **/
	function bakeModelTest($className) {
		$fixtureInc = 'app';
		if ($this->plugin) {
			$fixtureInc = 'plugin.'.Inflector::underscore($this->plugin);
		}

		$fixture[] = "'{$fixtureInc}." . Inflector::underscore($className) ."'";

		if (!empty($associations)) {
			$assoc[] = Set::extract($associations, 'belongsTo.{n}.className');
			$assoc[] = Set::extract($associations, 'hasOne.{n}.className');
			$assoc[] = Set::extract($associations, 'hasMany.{n}.className');
			foreach ($assoc as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $class) {
						$fixture[] = "'{$fixtureInc}." . Inflector::underscore($class) ."'";
					}
				}
			}
		}
		$fixture = join(", ", $fixture);

		$import = $className;
		if (isset($this->plugin)) {
			$import = $this->plugin . '.' . $className;
		}

		$out = "App::import('Model', '$import');\n\n";
		$out .= "class {$className}TestCase extends CakeTestCase {\n";
		$out .= "\tvar \${$className} = null;\n";
		$out .= "\tvar \$fixtures = array($fixture);\n\n";
		$out .= "\tfunction startTest() {\n";
		$out .= "\t\t\$this->{$className} =& ClassRegistry::init('{$className}');\n";
		$out .= "\t}\n\n";
		$out .= "\tfunction endTest() {\n";
		$out .= "\t\tunset(\$this->{$className});\n";
		$out .= "\t}\n\n";
		$out .= "\tfunction test{$className}Instance() {\n";
		$out .= "\t\t\$this->assertTrue(is_a(\$this->{$className}, '{$className}'));\n";
		$out .= "\t}\n\n";
		$out .= "}\n";

		$path = MODEL_TESTS;
		if (isset($this->plugin)) {
			$pluginPath = 'plugins' . DS . Inflector::underscore($this->plugin) . DS;
			$path = APP . $pluginPath . 'tests' . DS . 'cases' . DS . 'models' . DS;
		}

		$filename = Inflector::underscore($className).'.test.php';
		$this->out("\nBaking unit test for $className...");

		$header = '$Id';
		$content = "<?php \n/* SVN FILE: $header$ */\n/* ". $className ." Test cases generated on: " . date('Y-m-d H:m:s') . " : ". time() . "*/\n{$out}?>";
		return $this->createFile($path . $filename, $content);
	}

/**
 * Create a test case for a controller.
 *
 * @return void
 **/
	function bakeControllerTest() {
		$import = $className;
		if ($this->plugin) {
			$import = $this->plugin . '.' . $className;
		}
		$out = "App::import('Controller', '$import');\n\n";
		$out .= "class Test{$className} extends {$className}Controller {\n";
		$out .= "\tvar \$autoRender = false;\n}\n\n";
		$out .= "class {$className}ControllerTest extends CakeTestCase {\n";
		$out .= "\tvar \${$className} = null;\n\n";
		$out .= "\tfunction startTest() {\n\t\t\$this->{$className} = new Test{$className}();";
		$out .= "\n\t\t\$this->{$className}->constructClasses();\n\t}\n\n";
		$out .= "\tfunction test{$className}ControllerInstance() {\n";
		$out .= "\t\t\$this->assertTrue(is_a(\$this->{$className}, '{$className}Controller'));\n\t}\n\n";
		$out .= "\tfunction endTest() {\n\t\tunset(\$this->{$className});\n\t}\n}\n";

		$path = CONTROLLER_TESTS;
		if (isset($this->plugin)) {
			$pluginPath = 'plugins' . DS . Inflector::underscore($this->plugin) . DS;
			$path = APP . $pluginPath . 'tests' . DS . 'cases' . DS . 'controllers' . DS;
		}

		$filename = Inflector::underscore($className).'_controller.test.php';
		$this->out("\nBaking unit test for $className...");

		$header = '$Id';
		$content = "<?php \n/* SVN FILE: $header$ */\n/* ". $className ."Controller Test cases generated on: " . date('Y-m-d H:m:s') . " : ". time() . "*/\n{$out}?>";
		return $this->createFile($path . $filename, $content);
	}
}
?>