<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Bake
  * Creates controller, model, view files, and the required directories on demand.
  * Used by scripts/add.php
  *
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

/**
  * Enter description here...
  *
  */
uses('object', 'inflector');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Bake extends Object {

/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $stdin = null;
    
/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $stdout = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $stderr = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $actions = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $dont_ask = false;

/**
  * Enter description here...
  *
  * @param unknown_type $type
  * @return unknown
  */
	function template ($type) {
		switch ($type) {
			case 'view': return "%s";
			case 'model': return "<?PHP\n\nclass %s extends AppModel {\n}\n\n?>";
			case 'action': return "\n\tfunction %s () {\n\t}\n";
			case 'ctrl': return "<?PHP\n\nclass %s extends %s {\n%s\n}\n\n?>";
			case 'helper': return "<?PHP\n\nclass %s extends AppController {\n}\n\n?>";
			case 'test': return '<?PHP

class %sTest extends TestCase {
	var $abc;

	// called before the tests
	function setUp() {
		$this->abc = new %s ();
	}

	// called after the tests
	function tearDown() {
		unset($this->abc);
	}
	
/*
	function testFoo () {
		$result = $this->abc->Foo();
		$expected = \'\';
		$this->assertEquals($result, $expected);
	}
*/
}

?>';
			default:
				return false;
		}
	}


/**
  * Enter description here...
  *
  * @param unknown_type $type
  * @param unknown_type $names
  */
	function __construct ($type, $names) {

		$this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');

		switch ($type) {

			case 'model':
			case 'models':
				foreach ($names as $model_name)
					$this->newModel($model_name);
			break;

			case 'controller':
			case 'ctrl':
				$controller = array_shift($names);

				$add_actions = array();
				foreach ($names as $action) {
					$add_actions[] = $action;
					$this->newView($controller, $action);
				}

				$this->newController($controller, $add_actions);
			break;

			case 'view':
			case 'views':
				$r = null;
				foreach ($names as $model_name) {
					if (preg_match('/^([a-z0-9_]+(?:\/[a-z0-9_]+)*)\/([a-z0-9_]+)$/i', $model_name, $r)) {
						$this->newView($r[1], $r[2]);
					}
				}
			break;
		}

		if (!$this->actions)
			fwrite($this->stderr, "Nothing to do, quitting.\n");
				
	}

/**
  * Enter description here...
  *
  * @param unknown_type $controller
  * @param unknown_type $name
  */
	function newView ($controller, $name) {
		$dir = Inflector::underscore($controller);
		$path = "{$dir}/".strtolower($name).".thtml";
		$this->createDir(VIEWS.$dir);
		$fn = VIEWS.$path;
		$this->createFile($fn, sprintf($this->template('view'), "<p>Edit <b>app/views/{$path}</b> to change this message.</p>"));
		$this->actions++;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @param array $actions
  */
	function newController ($name, $actions=array()) {
		$this->makeController($name, $actions);
		$this->makeControllerTest($name);
		$this->makeHelper($name);
		$this->makeHelperTest($name);
		$this->actions++;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @param array $actions
  * @return unknown
  */
	function makeController ($name, $actions) {
		$ctrl = $this->makeControllerName($name);
		$helper = $this->makeHelperName($name);
		$body = sprintf($this->template('ctrl'), $ctrl, $helper, join('', $this->getActions($actions)));
		return $this->createFile($this->makeControllerFn($name), $body);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
	function makeControllerName ($name) {
		return Inflector::camelize($name).'Controller';
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
	function makeControllerFn ($name) {
		return CONTROLLERS.Inflector::underscore($name).'_controller.php';
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
	function makeControllerTest ($name) {
		$fn = CONTROLLER_TESTS.Inflector::underscore($name).'_controller_test.php';
		$body = $this->getTestBody($this->makeControllerName($name));
		return $this->createFile($fn, $body);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
	function makeHelper ($name) {
		$body = sprintf($this->template('helper'), $this->makeHelperName($name));
		return $this->createFile($this->makeHelperFn($name), $body);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
	function makeHelperName ($name) {
		return Inflector::camelize($name).'Helper';
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
	function makeHelperFn ($name) {
		return HELPERS.Inflector::underscore($name).'_helper.php';
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
	function makeHelperTest ($name) {
		$fn = HELPER_TESTS.Inflector::underscore($name).'_helper_test.php';
		$body = $this->getTestBody($this->makeHelperName($name));
		return $this->createFile($fn, $body);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $as
  * @return unknown
  */
	function getActions ($as) {
		$out = array();
		foreach ($as as $a)
			$out[] = sprintf($this->template('action'), $a);
		return $out;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $class
  * @return unknown
  */
	function getTestBody ($class) {
		return sprintf($this->template('test'), $class, $class);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  */
	function newModel ($name) {
		$this->createFile($this->getModelFn($name), sprintf($this->template('model'), $this->getModelName($name)));
		$this->makeModelTest ($name);
		$this->actions++;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
	function getModelFn ($name) {
		return MODELS.Inflector::underscore($name).'.php';
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
	function makeModelTest ($name) {
		$fn = MODEL_TESTS.Inflector::underscore($name).'_test.php';
		$body = $this->getTestBody($this->getModelName($name));
		return $this->createFile($fn, $body);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
	function getModelName ($name) {
		return Inflector::camelize($name);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @param unknown_type $contents
  * @return unknown
  */
	function createFile ($path, $contents) {

		if (is_file($path) && !$this->dont_ask) {
			fwrite($this->stdout, "File {$path} exists, overwrite? (yNaq) "); 
			$key = fgets($this->stdin);
			
			if (preg_match("/^q$/", $key)) {
				fwrite($this->stdout, "Quitting.\n");
				exit;
			}
			elseif (preg_match("/^a$/", $key)) {
				$this->dont_ask = true;
			}
			elseif (preg_match("/^y$/", $key)) {
			}
			else {
				fwrite($this->stdout, "Skip   {$path}\n");
				return false;
			}
		}

		if ($f = fopen($path, 'w')) {
			fwrite($f, $contents);
			fclose($f);
			fwrite($this->stdout, "Wrote   {$path}\n");
//			debug ("Wrote {$path}");
			return true;
		}
		else {
			fwrite($this->stderr, "Error! Couldn't open {$path} for writing.\n");
//			debug ("Error! Couldn't open {$path} for writing.");
			return false;
		}
	}

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @return unknown
  */
	function createDir ($path) {
		if (is_dir($path))
			return true;

		if (mkdir($path)) {
			fwrite($this->stdout, "Created {$path}\n");
//			debug ("Created {$path}");
			return true;
		}
		else {
			fwrite($this->stderr, "Error! Couldn't create dir {$path}\n");
//			debug ("Error! Couldn't create dir {$path}");
			return false;
		}
	}

}

?>