<?php
/* SVN FILE: $Id$ */

/**
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs.controller
 * @since        CakePHP v TBD
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

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
        if ($this->controller->components !== false)
        {
            $loaded =  array();
            $loaded = $this->_loadComponents($loaded,$this->controller->components);
            foreach(array_keys($loaded) as $component)
            {
                $tempComponent =& $loaded[$component];
                if(isset($tempComponent->components) && is_array($tempComponent->components))
                {
                    foreach($tempComponent->components as $subComponent)
                    {
                        $this->controller->{$component}->{$subComponent} =& $loaded[$subComponent];
                    }
                }
            }
        }
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
                        if($componentCn == 'SessionComponent')
                        {
                            $param = $this->controller->base.'/';
                        }
                        else
                        {
                            $param = null;
                        }
                        $this->controller->{$component}  =& new $componentCn($param);
                        $loaded[$component] =& $this->controller->{$component};
                        if (isset($this->controller->{$component}->components) && is_array($this->controller->{$component}->components))
                        {
                            $loaded =& $this->_loadComponents($loaded, $this->controller->{$component}->components);
                        }
                    }
                    else
                    {
                        return $this->cakeError('missingComponentClass',
                                    array(array('className' => $this->controller->name,
                                                'component' => $component,
                                                'file' => $componentFn)));
                    }
                }
                else
                {
                    return $this->cakeError('missingComponentFile',
                                array(array('className' => $this->controller->name,
                                            'component' => $component,
                                            'file' => $componentFn)));
                }
            }
        }
        return $loaded;
    }
}

?>