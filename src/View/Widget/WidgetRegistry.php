<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Widget;

use Cake\Core\App;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\View\StringTemplate;
use Cake\View\View;
use Cake\View\Widget\WidgetInterface;
use ReflectionClass;

/**
 * A registry/factory for input widgets.
 *
 * Can be used by helpers/view logic to build form widgets
 * and other HTML widgets.
 *
 * This class handles the mapping between names and concrete classes.
 * It also has a basic name based dependency resolver that allows
 * widgets to depend on each other.
 *
 * Each widget should expect a StringTemplate instance as their first
 * argument. All other dependencies will be included after.
 *
 * Widgets can ask for the current view by using the `_view` widget.
 */
class WidgetRegistry
{

    /**
     * Array of widgets + widget configuration.
     *
     * @var array
     */
    protected $_widgets = [];

    /**
     * Templates to use.
     *
     * @var \Cake\View\StringTemplate
     */
    protected $_templates;

    /**
     * Constructor
     *
     * @param \Cake\View\StringTemplate $templates Templates instance to use.
     * @param \Cake\View\View $view The view instance to set as a widget.
     * @param string|array $widgets See add() method for more information.
     */
    public function __construct(StringTemplate $templates, View $view, $widgets = [])
    {
        $this->_templates = $templates;
        if (!empty($widgets)) {
            $this->add($widgets);
            foreach ($this->_widgets as $key => $widget) {
                if (is_string($widget) && !class_exists($widget)) {
                    $this->load($widget);
                    unset($this->_widgets[$key]);
                }
            }
        }
        $this->_widgets['_view'] = $view;
    }

    /**
     * Load a config file containing widgets.
     *
     * Widget files should define a `$config` variable containing
     * all the widgets to load. Loaded widgets will be merged with existing
     * widgets.
     *
     * @param string $file The file to load
     * @return void
     */
    public function load($file)
    {
        $loader = new PhpConfig();
        $widgets = $loader->read($file);
        $this->add($widgets);
    }

    /**
     * Adds or replaces existing widget instances/configuration with new ones.
     *
     * Widget arrays can either be descriptions or instances. For example:
     *
     * ```
     * $registry->add([
     *   'label' => new MyLabelWidget($templates),
     *   'checkbox' => ['Fancy.MyCheckbox', 'label']
     * ]);
     * ```
     *
     * The above shows how to define widgets as instances or as
     * descriptions including dependencies. Classes can be defined
     * with plugin notation, or fully namespaced class names.
     *
     * @param array $widgets Array of widgets to use.
     * @return void
     * @throws \RuntimeException When class does not implement WidgetInterface.
     */
    public function add(array $widgets)
    {
        foreach ($widgets as $object) {
            if (gettype($object) === 'object' &&
                !($object instanceof WidgetInterface)
            ) {
                throw new \RuntimeException(
                    'Widget objects must implement Cake\View\Widget\WidgetInterface.'
                );
            }
        }
        $this->_widgets = $widgets + $this->_widgets;
    }

    /**
     * Get a widget.
     *
     * Will either fetch an already created widget, or create a new instance
     * if the widget has been defined. If the widget is undefined an instance of
     * the `_default` widget will be returned. An exception will be thrown if
     * the `_default` widget is undefined.
     *
     * @param string $name The widget name to get.
     * @return WidgetInterface widget interface class.
     * @throws \RuntimeException when widget is undefined.
     */
    public function get($name)
    {
        if (!isset($this->_widgets[$name]) && empty($this->_widgets['_default'])) {
            throw new \RuntimeException(sprintf('Unknown widget "%s"', $name));
        }
        if (!isset($this->_widgets[$name])) {
            $name = '_default';
        }
        $this->_widgets[$name] = $this->_resolveWidget($this->_widgets[$name]);
        return $this->_widgets[$name];
    }

    /**
     * Clear the registry and reset the widgets.
     *
     * @return void
     */
    public function clear()
    {
        $this->_widgets = [];
    }

    /**
     * Resolves a widget spec into an instance.
     *
     * @param mixed $widget The widget to get
     * @return WidgetInterface
     * @throws \RuntimeException when class cannot be loaded or does not
     *   implement WidgetInterface.
     */
    protected function _resolveWidget($widget)
    {
        $type = gettype($widget);
        if ($type === 'object') {
            return $widget;
        }

        if ($type === 'string') {
            $widget = [$widget];
        }

        $class = array_shift($widget);
        $className = App::className($class, 'View/Widget', 'Widget');
        if ($className === false || !class_exists($className)) {
            throw new \RuntimeException(sprintf('Unable to locate widget class "%s"', $class));
        }
        if ($type === 'array' && count($widget)) {
            $reflection = new ReflectionClass($className);
            $arguments = [$this->_templates];
            foreach ($widget as $requirement) {
                $arguments[] = $this->get($requirement);
            }
            $instance = $reflection->newInstanceArgs($arguments);
        } else {
            $instance = new $className($this->_templates);
        }
        if (!($instance instanceof WidgetInterface)) {
            throw new \RuntimeException(sprintf('"%s" does not implement the WidgetInterface', $className));
        }
        return $instance;
    }
}
