<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c), Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\App;

/**
 * Provides the set() method for collecting template context.
 *
 * Once collected context data can be passed to another object.
 * This is done in Controller, TemplateTask and View for example.
 *
 */
trait ViewVarsTrait
{

    /**
     * Variables for the view
     *
     * @var array
     */
    public $viewVars = [];

    /**
     * Get view instance
     *
     * @param string|null $viewClass View class name or null to use $viewClass
     * @return \Cake\View\View
     * @throws \Cake\View\Exception\MissingViewException If view class was not found.
     */
    public function getView($viewClass = null)
    {
        if ($viewClass === null && $this->View) {
            return $this->View;
        }

        if ($viewClass === null) {
            $viewClass = $this->viewClass;
        }
        if ($viewClass === null) {
            $viewClass = App::className('App', 'View', 'View');
            if ($viewClass === false) {
                $viewClass = 'Cake\View\View';
            }
        }
        if ($viewClass === 'View') {
            $viewClass = 'Cake\View\View';
        }

        $this->viewClass = $viewClass;
        $className = App::className($this->viewClass, 'View', 'View');
        if (!$className) {
            throw new Exception\MissingViewException(['class' => $viewClass]);
        }

        if ($this->View && $this->View instanceof $className) {
            return $this->View;
        }

        return $this->View = $this->createView();
    }

    /**
     * Constructs the view class instance based on object properties.
     *
     * @param string|null $viewClass Optional namespaced class name of the View class to instantiate.
     * @return \Cake\View\View
     * @throws \Cake\View\Exception\MissingViewException If view class was not found.
     */
    public function createView($viewClass = null)
    {
        if ($viewClass === null) {
            $viewClass = $this->viewClass;
        }
        if ($viewClass === 'View') {
            $className = App::className($viewClass, 'View');
        } else {
            $className = App::className($viewClass, 'View', 'View');
        }
        if (!$className) {
            throw new Exception\MissingViewException([$viewClass]);
        }

        $viewOptions = [];
        foreach ($this->_validViewOptions as $option) {
            if (property_exists($this, $option)) {
                $viewOptions[$option] = $this->{$option};
            }
        }
        return new $className($this->request, $this->response, $this->eventManager(), $viewOptions);
    }

    /**
     * Saves a variable or an associative array of variables for use inside a template.
     *
     * @param string|array $name A string or an array of data.
     * @param string|array|null $value Value in case $name is a string (which then works as the key).
     *   Unused if $name is an associative array, otherwise serves as the values to $name's keys.
     * @return $this
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            if (is_array($value)) {
                $data = array_combine($name, $value);
            } else {
                $data = $name;
            }
        } else {
            $data = [$name => $value];
        }
        $this->viewVars = $data + $this->viewVars;
        return $this;
    }

    /**
     * Get/Set valid view options in the object's _validViewOptions property. The property is
     * created as an empty array if it is not set. If called without any parameters it will
     * return the current list of valid view options. See `createView()`.
     *
     * @param string|array|null $options string or array of string to be appended to _validViewOptions.
     * @param bool $merge Whether to merge with or override existing valid View options.
     *   Defaults to `true`.
     * @return array The updated view options as an array.
     */
    public function viewOptions($options = null, $merge = true)
    {
        if (!isset($this->_validViewOptions)) {
            $this->_validViewOptions = [];
        }

        if ($options === null) {
            return $this->_validViewOptions;
        }

        if (!$merge) {
            return $this->_validViewOptions = (array)$options;
        }

        return $this->_validViewOptions = array_merge($this->_validViewOptions, (array)$options);
    }
}
