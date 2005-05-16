<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Bake
  * Creates controller, model, view files, and the required directories on demand.
  * Used by scripts/add.php
  *
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
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
    var $model_template = "<?PHP

class %s extends AppModel {
}

?>
";

/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $action_template = "
	function %s () {
	}
";

/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $controller_template = "<?PHP

class %s extends AppController {
%s
}

?>
";

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
            $this->create_model($model_name);
            break;

            case 'controller':
            case 'ctrl':
            $controller = array_shift($names);

            $add_actions = array();
            foreach ($names as $action) {
                $add_actions[] = $action;
                $this->create_view($controller, $action);
            }

            $this->create_controller($controller, $add_actions);
            break;

            case 'view':
            case 'views':
            $r = null;
            foreach ($names as $model_name) {
                if (preg_match('/^([a-z0-9_]+(?:\/[a-z0-9_]+)*)\/([a-z0-9_]+)$/i', $model_name, $r)) {
                    $this->create_view($r[1], $r[2]);
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
    function create_view ($controller, $name) {
        $dir = Inflector::underscore($controller);
        $this->create_dir(VIEWS.$dir);
        $this->create_file(VIEWS.$dir.'/'.strtolower($name).'.thtml', '');
        $this->actions++;
    }

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @param unknown_type $actions
  */
    function create_controller ($name, $actions=array()) {
        $class_name = Inflector::camelize($name).'Controller';
        $content = array();
        foreach ($actions as $action)
        $content[] = sprintf($this->action_template, ($action));

        $this->create_file($this->controller_fn($name), sprintf($this->controller_template, $class_name, join('', $content)));
        $this->actions++;
    }

/**
  * Enter description here...
  *
  * @param unknown_type $name
  */
    function create_model ($name) {
        $class_name = Inflector::camelize($name);
        $this->create_file($this->model_fn($name), sprintf($this->model_template, $class_name));
        $this->actions++;
    }

/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
    function model_fn ($name) {
        return MODELS.Inflector::underscore($name).'.php';
    }
    
/**
  * Enter description here...
  *
  * @param unknown_type $name
  * @return unknown
  */
    function controller_fn ($name) {
        return CONTROLLERS.Inflector::underscore($name).'_controller.php';
    }

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @param unknown_type $contents
  * @return unknown
  */
    function create_file ($path, $contents) {

        if (is_file($path)) {
            fwrite($this->stdout, "File {$path} exists, overwrite? (y/N) ");
            $key = fgets($this->stdin);

            if (preg_match("/^q/", $key)) {
                exit;
            }
            if (!preg_match("/^y/", $key)) {
                fwrite($this->stdout, "Skip   {$path}\n");
                return false;
            }
        }

        if ($f = fopen($path, 'w')) {
            fwrite($f, $contents);
            fclose($f);
            fwrite($this->stdout, "Wrote   {$path}\n");
            return true;
        }
        else {
            fwrite($this->stderr, "Error! Couldn't open {$path} for writing.\n");
            return false;
        }
    }

/**
  * Enter description here...
  *
  * @param unknown_type $path
  */
    function create_dir ($path) {
        if (!is_dir($path)) {
            if (mkdir($path)) {
                fwrite($this->stdout, "Created {$path}\n");
            }
            else {
                fwrite($this->stderr, "Error! Couldn't create dir {$path}\n");
            }
        }
    }
}

?>