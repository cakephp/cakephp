<?php
/* SVN FILE: $Id$ */

/**
 * 
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, CakePHP Authors/Developers
 *
 * Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com>
 *            Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The MIT License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.cake.libs.controller
 * @since        CakePHP v TBD
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * Enter description here...
  */
uses('object');

/**
 *
 *
 * @package    cake
 * @subpackage cake.cake.libs.controller
 * @since      CakePHP v TBD
 */
class Component extends Object
{

/**
 * Enter description here...
 *
 * @var unknown_type
 */
    var $components = array();
    
/**
 * Enter description here...
 *
 * @var unknown_type
 */
    var $controller = null;

/**
 * Constructor
 *
 * @return Component
 */
    function Component(&$controller)
    {
        $this->controller =& $controller;
        $loaded = array();
        return $this->_loadComponents($loaded,$this->controller->components);
    }

/**
 * Enter description here...
 *
 * @param unknown_type $loaded
 * @param unknown_type $components
 * @return unknown
 */
    function &_loadComponents(&$loaded, $components)
    {
        foreach ($components as $component)
        {
            if(in_array($component, array_keys($loaded)) !== true)
            {
                $componentFn = Inflector::underscore($component).'.php';
                
                if(file_exists(COMPONENTS.$componentFn))
                {
                    $componentFn = COMPONENTS.$componentFn;
                }
                else if(file_exists(LIBS.'controller'.DS.'components'.DS.$componentFn))
                {
                    $componentFn = LIBS.'controller'.DS.'components'.DS.$componentFn;
                }
                
                $componentCn = $component.'Component';
                
                if (is_file($componentFn))
                {
                    require_once $componentFn;
                    
                    if(class_exists($componentCn)===true)
                    {
                        $this->controller->{$component}  =& new $componentCn;
                        $loaded[$component] =& $this->controller->{$component};
                        if (isset($this->controller->{$component}->components) && is_array($this->controller->{$component}->components))
                        {
                            $loaded =& $this->_loadComponents($loaded, $this->controller->{$component}->components);
                        }
                    }
                    else
                    {
                        $error =& new AppController();
                        $error->autoLayout = true;
                        $error->base = $this->controller->base;
                        call_user_func_array(array(&$error, 'missingComponentClass'), $component);
                        exit();
                    }
                }
                else
                {
                    $error =& new AppController();
                    $error->autoLayout = true;
                    $error->base = $this->controller->base;
                    call_user_func_array(array(&$error, 'missingComponentFile'), Inflector::underscore($component));
                    exit();
                }
            }
        }
        return $loaded;
    }
}

?>