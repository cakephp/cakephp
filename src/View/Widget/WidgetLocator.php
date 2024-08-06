<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Widget;

use Cake\Core\App;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\View\StringTemplate;
use Cake\View\View;
use InvalidArgumentException;
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
class WidgetLocator
{
    /**
     * Array of widgets + widget configuration.
     *
     * @var array
     */
    protected array $_widgets = [];

    /**
     * Templates to use.
     *
     * @var \Cake\View\StringTemplate
     */
    protected StringTemplate $_templates;

    /**
     * View instance.
     *
     * @var \Cake\View\View
     */
    protected View $_view;

    /**
     * Constructor
     *
     * @param \Cake\View\StringTemplate $templates Templates instance to use.
     * @param \Cake\View\View $view The view instance to set as a widget.
     * @param array $widgets See add() method for more information.
     */
    public function __construct(StringTemplate $templates, View $view, array $widgets = [])
    {
        $this->_templates = $templates;
        $this->_view = $view;

        $this->add($widgets);
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
    public function load(string $file): void
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
     */
    public function add(array $widgets): void
    {
        $files = [];

        foreach ($widgets as $key => $widget) {
            if (is_int($key)) {
                $files[] = $widget;
                continue;
            }

            if (is_object($widget)) {
                assert(
                    $widget instanceof WidgetInterface,
                    sprintf(
                        'Widget objects must implement `%s`. Got `%s` instance instead.',
                        WidgetInterface::class,
                        get_debug_type($widget)
                    )
                );
            }

            $this->_widgets[$key] = $widget;
        }

        foreach ($files as $file) {
            $this->load($file);
        }
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
     * @return \Cake\View\Widget\WidgetInterface WidgetInterface instance.
     * @throws \InvalidArgumentException when widget is undefined.
     */
    public function get(string $name): WidgetInterface
    {
        if (!isset($this->_widgets[$name])) {
            if (empty($this->_widgets['_default'])) {
                throw new InvalidArgumentException(sprintf('Unknown widget `%s`', $name));
            }

            $name = '_default';
        }

        if ($this->_widgets[$name] instanceof WidgetInterface) {
            return $this->_widgets[$name];
        }

        return $this->_widgets[$name] = $this->_resolveWidget($this->_widgets[$name]);
    }

    /**
     * Clear the registry and reset the widgets.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->_widgets = [];
    }

    /**
     * Resolves a widget spec into an instance.
     *
     * @param array|string $config The widget config.
     * @return \Cake\View\Widget\WidgetInterface Widget instance.
     * @throws \InvalidArgumentException
     */
    protected function _resolveWidget(array|string $config): WidgetInterface
    {
        if (is_string($config)) {
            $config = [$config];
        }

        $class = array_shift($config);
        $className = App::className($class, 'View/Widget', 'Widget');
        if ($className === null) {
            throw new InvalidArgumentException(sprintf('Unable to locate widget class `%s`.', $class));
        }
        if ($config !== []) {
            $reflection = new ReflectionClass($className);
            $arguments = [$this->_templates];
            foreach ($config as $requirement) {
                if ($requirement === '_view') {
                    $arguments[] = $this->_view;
                } else {
                    $arguments[] = $this->get($requirement);
                }
            }

            /** @var \Cake\View\Widget\WidgetInterface */
            return $reflection->newInstanceArgs($arguments);
        }

        /** @var \Cake\View\Widget\WidgetInterface */
        return new $className($this->_templates);
    }
}
