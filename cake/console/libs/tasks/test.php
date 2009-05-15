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
			$class = Inflector::underscore($this->args[0]);
			if ($this->bake($class, $this->args[1])) {
				$this->out('done');
			}
		}
	}
/**
 * Handles interactive baking
 *
 * @access private
 */
	function __interactive($class = null) {
		$this->hr();
		$this->out(sprintf("Bake Tests\nPath: %s", $this->path));
		$this->hr();

		$key = null;
		$options = array('Behavior', 'Helper', 'Component', 'Model', 'Controller');

		if ($class !== null) {
			$class = Inflector::camelize($class);
			if (in_array($class, $options)) {
				$key = array_search($class);
			}
		}

		while ($class == null) {
			$cases = array();
			$this->hr();
			$this->out("Select a class:");
			$this->hr();

			$keys = array();
			foreach ($options as $key => $option) {
				$this->out(++$key . '. ' . $option);
				$keys[] = $key;
			}
			$keys[] = 'q';

			$key = $this->in(__("Enter the class to test or (q)uit", true), $keys, 'q');

			if ($key != 'q') {
				if (isset($options[--$key])) {
					$class = $options[$key];
				}

				if ($class) {
					$name = $this->in(__("Enter the name for the test or (q)uit", true), null, 'q');
					if ($name !== 'q') {
						$case = null;
						while ($case !== 'q') {
							$case = $this->in(__("Enter a test case or (q)uit", true), null, 'q');
							if ($case !== 'q') {
								$cases[] = $case;
							}
						}
						if ($this->bake($class, $name, $cases)) {
							$this->out(__("Test baked\n", true));
							$type = null;
						}
						$class = null;
					}
				}
			} else {
				$this->_stop();
			}
		}
	}
/**
 * Writes File
 *
 * @access public
 */
	function bake($class, $name = null, $cases = array()) {
		if (!$name) {
			return false;
		}

		if (!is_array($cases)) {
			$cases = array($cases);
		}

		if (strpos($this->path, $class) === false) {
			$this->filePath = $this->path . 'cases' . DS . Inflector::tableize($class) . DS;
		}

		$class = Inflector::classify($class);
		$name = Inflector::classify($name);

		$import = $name;
		if (isset($this->plugin)) {
			$import = $this->plugin . '.' . $name;
		}
		$extras = $this->__extras($class);
		$out = "App::import('$class', '$import');\n";
		if ($class == 'Model') {
			$class = null;
		}
		$out .= "class Test{$name} extends {$name}{$class} {\n";
		$out .= "{$extras}";
		$out .= "}\n\n";
		$out .= "class {$name}{$class}Test extends CakeTestCase {\n";
		$out .= "\n\tfunction startTest() {";
		$out .= "\n\t\t\$this->{$name} = new Test{$name}();";
		$out .= "\n\t}\n";
		$out .= "\n\tfunction test{$name}Instance() {\n";
		$out .= "\t\t\$this->assertTrue(is_a(\$this->{$name}, '{$name}{$class}'));\n\t}\n";
		foreach ($cases as $case) {
			$case = Inflector::classify($case);
			$out .= "\n\tfunction test{$case}() {\n\n\t}\n";
		}
		$out .= "}\n";

		$this->out("Baking unit test for $name...");
		$this->out($out);
		$ok = $this->in(__('Is this correct?', true), array('y', 'n'), 'y');
		if ($ok == 'n') {
			return false;
		}

		$header = '$Id';
		$content = "<?php \n/* SVN FILE: $header$ */\n/* ". $name ." Test cases generated on: " . date('Y-m-d H:m:s') . " : ". time() . "*/\n{$out}?>";
		return $this->createFile($this->filePath . Inflector::underscore($name) . '.test.php', $content);
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
}
?>