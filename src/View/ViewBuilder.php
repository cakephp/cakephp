<?php
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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\App;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\Exception\MissingViewException;
use JsonSerializable;
use Serializable;

/**
 * Provides an API for iteratively building a view up.
 *
 * Once you have configured the view and established all the context
 * you can create a view instance with `build()`.
 */
class ViewBuilder implements JsonSerializable, Serializable
{

    /**
     * The subdirectory to the template.
     *
     * @var string
     */
    protected $_templatePath;

    /**
     * The template file to render.
     *
     * @var string
     */
    protected $_template;

    /**
     * The plugin name to use.
     *
     * @var string|null|false
     */
    protected $_plugin;

    /**
     * The theme name to use.
     *
     * @var string|null|false
     */
    protected $_theme;

    /**
     * The layout name to render.
     *
     * @var string
     */
    protected $_layout;

    /**
     * Whether or not autoLayout should be enabled.
     *
     * @var bool
     */
    protected $_autoLayout;

    /**
     * The layout path to build the view with.
     *
     * @var string
     */
    protected $_layoutPath;

    /**
     * The view variables to use
     *
     * @var string
     */
    protected $_name;

    /**
     * The view class name to use.
     * Can either use plugin notation, a short name
     * or a fully namespaced classname.
     *
     * @var string
     */
    protected $_className;

    /**
     * Additional options used when constructing the view.
     *
     * This options array lets you provide custom constructor
     * arguments to application/plugin view classes.
     *
     * @var array
     */
    protected $_options = [];

    /**
     * The helpers to use
     *
     * @var array
     */
    protected $_helpers = [];

    /**
     * Sets path for template files.
     *
     * @param string $path Path for view files.
     * @return $this
     */
    public function setTemplatePath($path)
    {
        $this->_templatePath = $path;

        return $this;
    }

    /**
     * Gets path for template files.
     *
     * @return string
     */
    public function getTemplatePath()
    {
        return $this->_templatePath;
    }

    /**
     * Get/set path for template files.
     *
     * @deprecated 3.4.0 Use setTemplatePath()/getTemplatePath() instead.
     * @param string|null $path Path for view files. If null returns current path.
     * @return string|$this
     */
    public function templatePath($path = null)
    {
        deprecationWarning('ViewBuilder::templatePath() is deprecated. Use ViewBuilder::setTemplatePath() or ViewBuilder::getTemplatePath() instead.');
        if ($path !== null) {
            return $this->setTemplatePath($path);
        }

        return $this->getTemplatePath();
    }

    /**
     * Sets path for layout files.
     *
     * @param string $path Path for layout files.
     * @return $this
     */
    public function setLayoutPath($path)
    {
        $this->_layoutPath = $path;

        return $this;
    }

    /**
     * Gets path for layout files.
     *
     * @return string
     */
    public function getLayoutPath()
    {
        return $this->_layoutPath;
    }

    /**
     * Get/set path for layout files.
     *
     * @deprecated 3.4.0 Use setLayoutPath()/getLayoutPath() instead.
     * @param string|null $path Path for layout files. If null returns current path.
     * @return string|$this
     */
    public function layoutPath($path = null)
    {
        deprecationWarning('ViewBuilder::layoutPath() is deprecated. Use ViewBuilder::setLayoutPath() or ViewBuilder::getLayoutPath() instead.');
        if ($path !== null) {
            return $this->setLayoutPath($path);
        }

        return $this->getLayoutPath();
    }

    /**
     * Turns on or off CakePHP's conventional mode of applying layout files.
     * On by default. Setting to off means that layouts will not be
     * automatically applied to rendered views.
     *
     * @param bool $enable Boolean to turn on/off.
     * @return $this
     */
    public function enableAutoLayout($enable = true)
    {
        $this->_autoLayout = (bool)$enable;

        return $this;
    }

    /**
     * Returns if CakePHP's conventional mode of applying layout files is enabled.
     * Disabled means that layouts will not be automatically applied to rendered views.
     *
     * @return bool
     */
    public function isAutoLayoutEnabled()
    {
        return $this->_autoLayout;
    }

    /**
     * Turns on or off CakePHP's conventional mode of applying layout files.
     * On by default. Setting to off means that layouts will not be
     * automatically applied to rendered views.
     *
     * @deprecated 3.4.0 Use enableAutoLayout()/isAutoLayoutEnabled() instead.
     * @param bool|null $enable Boolean to turn on/off. If null returns current value.
     * @return bool|$this
     */
    public function autoLayout($enable = null)
    {
        deprecationWarning('ViewBuilder::autoLayout() is deprecated. Use ViewBuilder::enableAutoLayout() or ViewBuilder::isAutoLayoutEnable() instead.');
        if ($enable !== null) {
            return $this->enableAutoLayout($enable);
        }

        return $this->isAutoLayoutEnabled();
    }

    /**
     * Sets the plugin name to use.
     *
     * `False` to remove current plugin name is deprecated as of 3.4.0. Use directly `null` instead.
     *
     * @param string|null|false $name Plugin name.
     *   Use null or false to remove the current plugin name.
     * @return $this
     */
    public function setPlugin($name)
    {
        $this->_plugin = $name;

        return $this;
    }

    /**
     * Gets the plugin name to use.
     *
     * @return string|null|false
     */
    public function getPlugin()
    {
        return $this->_plugin;
    }

    /**
     * The plugin name to use
     *
     * @deprecated 3.4.0 Use setPlugin()/getPlugin() instead.
     * @param string|null|false $name Plugin name. If null returns current plugin.
     *   Use false to remove the current plugin name.
     * @return string|false|null|$this
     */
    public function plugin($name = null)
    {
        deprecationWarning('ViewBuilder::plugin() is deprecated. Use ViewBuilder::setPlugin() or ViewBuilder::getPlugin() instead.');
        if ($name !== null) {
            return $this->setPlugin($name);
        }

        return $this->getPlugin();
    }

    /**
     * Sets the helpers to use.
     *
     * @param array $helpers Helpers to use.
     * @param bool $merge Whether or not to merge existing data with the new data.
     * @return $this
     */
    public function setHelpers(array $helpers, $merge = true)
    {
        if ($merge) {
            $helpers = array_merge($this->_helpers, $helpers);
        }
        $this->_helpers = $helpers;

        return $this;
    }

    /**
     * Gets the helpers to use.
     *
     * @return array
     */
    public function getHelpers()
    {
        return $this->_helpers;
    }

    /**
     * The helpers to use
     *
     * @deprecated 3.4.0 Use setHelpers()/getHelpers() instead.
     * @param array|null $helpers Helpers to use.
     * @param bool $merge Whether or not to merge existing data with the new data.
     * @return array|$this
     */
    public function helpers(array $helpers = null, $merge = true)
    {
        deprecationWarning('ViewBuilder::helpers() is deprecated. Use ViewBuilder::setHelpers() or ViewBuilder::getHelpers() instead.');
        if ($helpers !== null) {
            return $this->setHelpers($helpers, $merge);
        }

        return $this->getHelpers();
    }

    /**
     * Sets the view theme to use.
     *
     * `False` to remove current theme is deprecated as of 3.4.0. Use directly `null` instead.
     *
     * @param string|null|false $theme Theme name.
     *   Use null or false to remove the current theme.
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->_theme = $theme;

        return $this;
    }

    /**
     * Gets the view theme to use.
     *
     * @return string|null|false
     */
    public function getTheme()
    {
        return $this->_theme;
    }

    /**
     * The view theme to use.
     *
     * @deprecated 3.4.0 Use setTheme()/getTheme() instead.
     * @param string|null|false $theme Theme name. If null returns current theme.
     *   Use false to remove the current theme.
     * @return string|false|null|$this
     */
    public function theme($theme = null)
    {
        deprecationWarning('ViewBuilder::theme() is deprecated. Use ViewBuilder::setTheme() or ViewBuilder::getTheme() instead.');
        if ($theme !== null) {
            return $this->setTheme($theme);
        }

        return $this->getTheme();
    }

    /**
     * Sets the name of the view file to render. The name specified is the
     * filename in /src/Template/<SubFolder> without the .ctp extension.
     *
     * @param string $name View file name to set.
     * @return $this
     */
    public function setTemplate($name)
    {
        $this->_template = $name;

        return $this;
    }

    /**
     * Gets the name of the view file to render. The name specified is the
     * filename in /src/Template/<SubFolder> without the .ctp extension.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->_template;
    }

    /**
     * Get/set the name of the view file to render. The name specified is the
     * filename in /src/Template/<SubFolder> without the .ctp extension.
     *
     * @deprecated 3.4.0 Use setTemplate()/getTemplate()
     * @param string|null $name View file name to set. If null returns current name.
     * @return string|$this
     */
    public function template($name = null)
    {
        deprecationWarning('ViewBuilder::template() is deprecated. Use ViewBuilder::setTemplate() or ViewBuilder::getTemplate() instead.');
        if ($name !== null) {
            return $this->setTemplate($name);
        }

        return $this->getTemplate();
    }

    /**
     * Sets the name of the layout file to render the view inside of.
     * The name specified is the filename of the layout in /src/Template/Layout
     * without the .ctp extension.
     *
     * @param string $name Layout file name to set.
     * @return $this
     */
    public function setLayout($name)
    {
        $this->_layout = $name;

        return $this;
    }

    /**
     * Gets the name of the layout file to render the view inside of.
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Get/set the name of the layout file to render the view inside of.
     * The name specified is the filename of the layout in /src/Template/Layout
     * without the .ctp extension.
     *
     * @deprecated 3.4.0 Use setLayout()/getLayout() instead.
     * @param string|null $name Layout file name to set. If null returns current name.
     * @return string|$this
     */
    public function layout($name = null)
    {
        deprecationWarning('ViewBuilder::layout() is deprecated. Use ViewBuilder::setLayout() or ViewBuilder::getLayout() instead.');
        if ($name !== null) {
            return $this->setLayout($name);
        }

        return $this->getLayout();
    }

    /**
     * Sets additional options for the view.
     *
     * This lets you provide custom constructor arguments to application/plugin view classes.
     *
     * @param array $options An array of options.
     * @param bool $merge Whether or not to merge existing data with the new data.
     * @return $this
     */
    public function setOptions(array $options, $merge = true)
    {
        if ($merge) {
            $options = array_merge($this->_options, $options);
        }
        $this->_options = $options;

        return $this;
    }

    /**
     * Gets additional options for the view.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Set additional options for the view.
     *
     * This lets you provide custom constructor arguments to application/plugin view classes.
     *
     * @deprecated 3.4.0 Use setOptions()/getOptions() instead.
     * @param array|null $options Either an array of options or null to get current options.
     * @param bool $merge Whether or not to merge existing data with the new data.
     * @return array|$this
     */
    public function options(array $options = null, $merge = true)
    {
        deprecationWarning('ViewBuilder::options() is deprecated. Use ViewBuilder::setOptions() or ViewBuilder::getOptions() instead.');
        if ($options !== null) {
            return $this->setOptions($options, $merge);
        }

        return $this->getOptions();
    }

    /**
     * Sets the view name.
     *
     * @param string $name The name of the view.
     * @return $this
     */
    public function setName($name)
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * Gets the view name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get/set the view name
     *
     * @deprecated 3.4.0 Use setName()/getName() instead.
     * @param string|null $name The name of the view
     * @return string|$this
     */
    public function name($name = null)
    {
        deprecationWarning('ViewBuilder::name() is deprecated. Use ViewBuilder::setName() or ViewBuilder::getName() instead.');
        if ($name !== null) {
            return $this->setName($name);
        }

        return $this->getName();
    }

    /**
     * Sets the view classname.
     *
     * Accepts either a short name (Ajax) a plugin name (MyPlugin.Ajax)
     * or a fully namespaced name (App\View\AppView).
     *
     * @param string $name The class name for the view.
     * @return $this
     */
    public function setClassName($name)
    {
        $this->_className = $name;

        return $this;
    }

    /**
     * Gets the view classname.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->_className;
    }

    /**
     * Get/set the view classname.
     *
     * Accepts either a short name (Ajax) a plugin name (MyPlugin.Ajax)
     * or a fully namespaced name (App\View\AppView).
     *
     * @deprecated 3.4.0 Use setClassName()/getClassName() instead.
     * @param string|null $name The class name for the view. Can
     *   be a plugin.class name reference, a short alias, or a fully
     *   namespaced name.
     * @return string|$this
     */
    public function className($name = null)
    {
        deprecationWarning('ViewBuilder::className() is deprecated. Use ViewBuilder::setClassName() or ViewBuilder::getClassName() instead.');
        if ($name !== null) {
            return $this->setClassName($name);
        }

        return $this->getClassName();
    }

    /**
     * Using the data in the builder, create a view instance.
     *
     * If className() is null, App\View\AppView will be used.
     * If that class does not exist, then Cake\View\View will be used.
     *
     * @param array $vars The view variables/context to use.
     * @param \Cake\Http\ServerRequest|null $request The request to use.
     * @param \Cake\Http\Response|null $response The response to use.
     * @param \Cake\Event\EventManager|null $events The event manager to use.
     * @return \Cake\View\View
     * @throws \Cake\View\Exception\MissingViewException
     */
    public function build($vars = [], ServerRequest $request = null, Response $response = null, EventManager $events = null)
    {
        $className = $this->_className;
        if ($className === null) {
            $className = App::className('App', 'View', 'View') ?: 'Cake\View\View';
        }
        if ($className === 'View') {
            $className = App::className($className, 'View');
        } else {
            $className = App::className($className, 'View', 'View');
        }
        if (!$className) {
            throw new MissingViewException(['class' => $this->_className]);
        }

        $data = [
            'name' => $this->_name,
            'templatePath' => $this->_templatePath,
            'template' => $this->_template,
            'plugin' => $this->_plugin,
            'theme' => $this->_theme,
            'layout' => $this->_layout,
            'autoLayout' => $this->_autoLayout,
            'layoutPath' => $this->_layoutPath,
            'helpers' => $this->_helpers,
            'viewVars' => $vars,
        ];
        $data += $this->_options;

        return new $className($request, $response, $events, $data);
    }

    /**
     * Serializes the view builder object to a value that can be natively
     * serialized and re-used to clone this builder instance.
     *
     * @return array Serializable array of configuration properties.
     */
    public function jsonSerialize()
    {
        $properties = [
            '_templatePath', '_template', '_plugin', '_theme', '_layout', '_autoLayout',
            '_layoutPath', '_name', '_className', '_options', '_helpers'
        ];

        $array = [];

        foreach ($properties as $property) {
            $array[$property] = $this->{$property};
        }

        return array_filter($array, function ($i) {
            return !is_array($i) && strlen($i) || !empty($i);
        });
    }

    /**
     * Configures a view builder instance from serialized config.
     *
     * @param array $config View builder configuration array.
     * @return $this Configured view builder instance.
     */
    public function createFromArray($config)
    {
        foreach ($config as $property => $value) {
            $this->{$property} = $value;
        }

        return $this;
    }

    /**
     * Serializes the view builder object.
     *
     * @return string
     */
    public function serialize()
    {
        $array = $this->jsonSerialize();

        return serialize($array);
    }

    /**
     * Unserializes the view builder object.
     *
     * @param string $data Serialized string.
     * @return $this Configured view builder instance.
     */
    public function unserialize($data)
    {
        return $this->createFromArray(unserialize($data));
    }
}
