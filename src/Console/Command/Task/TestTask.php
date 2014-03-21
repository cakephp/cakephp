<?php
/**
 * The TestTask handles creating and updating test files.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Error;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * Task class for creating and updating test files.
 *
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
	public $tasks = ['Template'];

/**
 * class types that methods can be generated for
 *
 * @var array
 */
	public $classTypes = [
		'Model' => 'Model',
		'Controller' => 'Controller',
		'Component' => 'Controller/Component',
		'Behavior' => 'Model/Behavior',
		'Helper' => 'View/Helper'
	];

/**
 * Mapping between type names and their namespaces
 *
 * @var array
 */
	public $classNamespaces = [
		'Model' => 'Model',
		'Controller' => 'Controller',
		'Component' => 'Controller\Component',
		'Behavior' => 'Model\Behavior',
		'Helper' => 'View\Helper'
	];

/**
 * Mapping between packages, and their baseclass
 * This is used to create use statements.
 *
 * @var array
 */
	public $baseTypes = [
		'Model' => ['Model', 'Model'],
		'Behavior' => ['ModelBehavior', 'Model'],
		'Controller' => ['Controller', 'Controller'],
		'Component' => ['Component', 'Controller'],
		'Helper' => ['Helper', 'View']
	];

/**
 * Internal list of fixtures that have been added so far.
 *
 * @var array
 */
	protected $_fixtures = [];

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		parent::execute();
		$count = count($this->args);
		if (!$count) {
			$this->_interactive();
		}

		if ($count === 1) {
			$this->_interactive($this->args[0]);
		}

		if ($count > 1) {
			$type = Inflector::classify($this->args[0]);
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
		$this->out(__d('cake_console', 'Path: %s', $this->getPath()));
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

		$methods = [];
		if (class_exists($fullClassName)) {
			$methods = $this->getTestableMethods($fullClassName);
		}
		$mock = $this->hasMockClass($type, $fullClassName);
		list($preConstruct, $construction, $postConstruct) = $this->generateConstructor($type, $fullClassName, $plugin);
		$uses = $this->generateUses($type, $realType, $fullClassName);

		$subject = $className;
		list($namespace, $className) = namespaceSplit($fullClassName);
		list($baseNamespace, $subNamespace) = explode('\\', $namespace, 2);

		$this->out("\n" . __d('cake_console', 'Baking test case for %s ...', $fullClassName), 1, Shell::QUIET);

		$this->Template->set('fixtures', $this->_fixtures);
		$this->Template->set('plugin', $plugin);
		$this->Template->set(compact(
			'subject', 'className', 'methods', 'type', 'fullClassName', 'mock',
			'realType', 'preConstruct', 'postConstruct', 'construction',
			'uses', 'baseNamespace', 'subNamespace', 'namespace'
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

		$keys = [];
		$i = 0;
		foreach ($this->classTypes as $option => $package) {
			$this->out(++$i . '. ' . $option);
			$keys[] = $i;
		}
		$keys[] = 'q';
		$selection = $this->in(__d('cake_console', 'Enter the type of object to bake a test for or (q)uit'), $keys, 'q');
		if ($selection === 'q') {
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
		$keys = [];
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
		return in_array($type, ['controller', 'model']);
	}

/**
 * Check if a class with the given package is loaded or can be loaded.
 *
 * @param string $package The package of object you are generating tests for eg. controller
 * @param string $class the Classname of the class the test is being generated for.
 * @return boolean
 */
	public function isLoadableClass($package, $class) {
		$classname = App::classname($class, $package);
		return !empty($classname);
	}

/**
 * Construct an instance of the class to be tested.
 * So that fixtures can be detected
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $class the Classname of the class the test is being generated for.
 * @return object And instance of the class that is going to be tested.
 */
	public function buildTestSubject($type, $class) {
		TableRegistry::clear();
		$class = $this->getRealClassName($type, $class);
		if (strtolower($type) === 'model') {
			$instance = TableRegistry::get($class);
		} else {
			$instance = new $class();
		}
		return $instance;
	}

/**
 * Gets the real class name from the cake short form. If the class name is already
 * suffixed with the type, the type will not be duplicated.
 *
 * @param string $type The Type of object you are generating tests for eg. controller.
 * @param string $class the Classname of the class the test is being generated for.
 * @param string $plugin The plugin name of the class being generated.
 * @return string Real classname
 */
	public function getRealClassName($type, $class, $plugin = null) {
		if (strpos('\\', $class)) {
			return $class;
		}
		$namespace = Configure::read('App.namespace');
		$loadedPlugin = $plugin && Plugin::loaded($plugin);
		if ($loadedPlugin) {
			$namespace = Plugin::getNamespace($plugin);
		}
		if ($plugin && !$loadedPlugin) {
			$namespace = Inflector::camelize($plugin);
		}
		$subNamespace = $this->classNamespaces[$type];

		$position = strpos($class, $type);

		if (
			strtolower($type) !== 'model' &&
			($position === false || strlen($class) - $position !== strlen($type))
		) {
			$class .= $type;
		}
		return $namespace . '\\' . $subNamespace . '\\' . $class;
	}

/**
 * Map the types that TestTask uses to concrete types that App::classname can use.
 *
 * @param string $type The type of thing having a test generated.
 * @param string $plugin The plugin name.
 * @return string
 * @throws \Cake\Error\Exception When invalid object types are requested.
 */
	public function mapType($type, $plugin) {
		$type = ucfirst($type);
		if (empty($this->classTypes[$type])) {
			throw new Error\Exception('Invalid object type.');
		}
		$real = $this->classTypes[$type];
		if ($plugin) {
			$real = trim($plugin, '.') . '.' . $real;
		}
		return $real;
	}

/**
 * Get the base class and package name for a given type.
 *
 * @param string $type The type the class having a test
 *   generated for is in.
 * @return array Array of (class, type)
 * @throws \Cake\Error\Exception on invalid types.
 */
	public function getBaseType($type) {
		if (empty($this->baseTypes[$type])) {
			throw new Error\Exception('Invalid type name');
		}
		return $this->baseTypes[$type];
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
		$out = [];
		foreach ($thisMethods as $method) {
			if (substr($method, 0, 1) !== '_' && $method != strtolower($className)) {
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
		$this->_fixtures = [];
		if ($subject instanceof Model) {
			$this->_processModel($subject);
		} elseif ($subject instanceof Controller) {
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
			if ($type === 'hasAndBelongsToMany') {
				if (!empty($subject->hasAndBelongsToMany[$alias]['with'])) {
					list(, $joinModel) = pluginSplit($subject->hasAndBelongsToMany[$alias]['with']);
				} else {
					$joinModel = Inflector::classify($subject->hasAndBelongsToMany[$alias]['joinTable']);
				}
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
 * @param \Cake\Controller\Controller $subject A controller to pull model names off of.
 * @return void
 */
	protected function _processController($subject) {
		$subject->constructClasses();
		$models = [Inflector::classify($subject->name)];
		if (!empty($subject->uses)) {
			$models = $subject->uses;
		}
		foreach ($models as $model) {
			list(, $model) = pluginSplit($model);
			$this->_processModel($subject->{$model});
		}
	}

/**
 * Add class name to the fixture list.
 * Sets the app. or plugin.plugin_name. prefix.
 *
 * @param string $name Name of the Model class that a fixture might be required for.
 * @return void
 */
	protected function _addFixture($name) {
		if ($this->plugin) {
			$prefix = 'plugin.' . Inflector::underscore($this->plugin) . '.';
		} else {
			$prefix = 'app.';
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
		$proceed = $this->in(__d('cake_console', 'Bake could not detect fixtures, would you like to add some?'), ['y', 'n'], 'n');
		$fixtures = [];
		if (strtolower($proceed) === 'y') {
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
		return $type === 'controller';
	}

/**
 * Generate a constructor code snippet for the type and class name
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $fullClassName The full classname of the class the test is being generated for.
 * @param string $plugin The plugin name.
 * @return array Constructor snippets for the thing you are building.
 */
	public function generateConstructor($type, $fullClassName, $plugin) {
		list($namespace, $className) = namespaceSplit($fullClassName);
		$type = strtolower($type);
		$pre = $construct = $post = '';
		if ($type === 'model') {
			$construct = "ClassRegistry::init('{$plugin}$className');\n";
		}
		if ($type === 'behavior') {
			$construct = "new {$className}();\n";
		}
		if ($type === 'helper') {
			$pre = "\$View = new View();\n";
			$construct = "new {$className}(\$View);\n";
		}
		if ($type === 'component') {
			$pre = "\$registry = new ComponentRegistry();\n";
			$construct = "new {$className}(\$registry);\n";
		}
		return [$pre, $construct, $post];
	}

/**
 * Generate the uses() calls for a type & class name
 *
 * @param string $type The Type of object you are generating tests for eg. controller
 * @param string $realType The package name for the class.
 * @param string $fullClassName The Classname of the class the test is being generated for.
 * @return array An array containing used classes
 */
	public function generateUses($type, $realType, $fullClassName) {
		$uses = [];
		$type = strtolower($type);
		if ($type == 'component') {
			$uses[] = 'Cake\Controller\ComponentRegistry';
		}
		if ($type == 'helper') {
			$uses[] = 'Cake\View\View';
		}
		$uses[] = $fullClassName;
		return $uses;
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
		$path = $this->getPath() . 'TestCase/';
		$type = Inflector::camelize($type);
		if (isset($this->classTypes[$type])) {
			$path .= $this->classTypes[$type] . DS;
		}
		list($namespace, $className) = namespaceSplit($this->getRealClassName($type, $className));
		return str_replace('/', DS, $path) . Inflector::camelize($className) . 'Test.php';
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console', 'Bake test case skeletons for classes.')
		)->addArgument('type', [
			'help' => __d('cake_console', 'Type of class to bake, can be any of the following: controller, model, helper, component or behavior.'),
			'choices' => [
				'Controller', 'controller',
				'Model', 'model',
				'Helper', 'helper',
				'Component', 'component',
				'Behavior', 'behavior'
			]
		])->addArgument('name', [
			'help' => __d('cake_console', 'An existing class to bake tests for.')
		])->addOption('theme', [
			'short' => 't',
			'help' => __d('cake_console', 'Theme to use when baking code.')
		])->addOption('plugin', [
			'short' => 'p',
			'help' => __d('cake_console', 'CamelCased name of the plugin to bake tests for.')
		])->addOption('force', [
			'short' => 'f',
			'help' => __d('cake_console', 'Force overwriting existing files without prompting.')
		])->epilog(
			__d('cake_console', 'Omitting all arguments and options will enter into an interactive mode.')
		);

		return $parser;
	}

}
