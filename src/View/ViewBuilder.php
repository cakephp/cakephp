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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\App;
use Cake\Event\EventManagerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\Exception\MissingViewException;
use Closure;
use Exception;
use JsonSerializable;
use PDO;
use RuntimeException;
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
     * @var string|null
     */
    protected $_templatePath;

    /**
     * The template file to render.
     *
     * @var string|null
     */
    protected $_template;

    /**
     * The plugin name to use.
     *
     * @var string|null
     */
    protected $_plugin;

    /**
     * The theme name to use.
     *
     * @var string|null
     */
    protected $_theme;

    /**
     * The layout name to render.
     *
     * @var string|null
     */
    protected $_layout;

    /**
     * Whether or not autoLayout should be enabled.
     *
     * @var bool
     */
    protected $_autoLayout = true;

    /**
     * The layout path to build the view with.
     *
     * @var string|null
     */
    protected $_layoutPath;

    /**
     * The view variables to use
     *
     * @var string|null
     */
    protected $_name;

    /**
     * The view class name to use.
     * Can either use plugin notation, a short name
     * or a fully namespaced classname.
     *
     * @var string|null
     * @psalm-var class-string<\Cake\View\View>|string|null
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
     * View vars
     *
     * @var array
     */
    protected $_vars = [];

    /**
     * Saves a variable for use inside a template.
     *
     * @param string $name A string or an array of data.
     * @param mixed $value Value.
     * @return $this
     */
    public function setVar(string $name, $value = null)
    {
        $this->_vars[$name] = $value;

        return $this;
    }

    /**
     * Saves view vars for use inside templates.
     *
     * @param array $data Array of data.
     * @param bool $merge Whether to merge with existing vars, default true.
     * @return $this
     */
    public function setVars(array $data, bool $merge = true)
    {
        if ($merge) {
            $this->_vars = $data + $this->_vars;
        } else {
            $this->_vars = $data;
        }

        return $this;
    }

    /**
     * Check if view var is set.
     *
     * @param string $name Var name
     * @return bool
     */
    public function hasVar(string $name): bool
    {
        return array_key_exists($name, $this->_vars);
    }

    /**
     * Get view var
     *
     * @param string $name Var name
     * @return mixed The var value or null if unset.
     */
    public function getVar(string $name)
    {
        return $this->_vars[$name] ?? null;
    }

    /**
     * Get all view vars.
     *
     * @return array
     */
    public function getVars(): array
    {
        return $this->_vars;
    }

    /**
     * Sets path for template files.
     *
     * @param string|null $path Path for view files.
     * @return $this
     */
    public function setTemplatePath(?string $path)
    {
        $this->_templatePath = $path;

        return $this;
    }

    /**
     * Gets path for template files.
     *
     * @return string|null
     */
    public function getTemplatePath(): ?string
    {
        return $this->_templatePath;
    }

    /**
     * Sets path for layout files.
     *
     * @param string|null $path Path for layout files.
     * @return $this
     */
    public function setLayoutPath(?string $path)
    {
        $this->_layoutPath = $path;

        return $this;
    }

    /**
     * Gets path for layout files.
     *
     * @return string|null
     */
    public function getLayoutPath(): ?string
    {
        return $this->_layoutPath;
    }

    /**
     * Turns on or off CakePHP's conventional mode of applying layout files.
     * On by default. Setting to off means that layouts will not be
     * automatically applied to rendered views.
     *
     * @param bool $enable Boolean to turn on/off.
     * @return $this
     */
    public function enableAutoLayout(bool $enable = true)
    {
        $this->_autoLayout = $enable;

        return $this;
    }

    /**
     * Turns off CakePHP's conventional mode of applying layout files.
     *
     * Setting to off means that layouts will not be automatically applied to
     * rendered views.
     *
     * @return $this
     */
    public function disableAutoLayout()
    {
        $this->_autoLayout = false;

        return $this;
    }

    /**
     * Returns if CakePHP's conventional mode of applying layout files is enabled.
     * Disabled means that layouts will not be automatically applied to rendered views.
     *
     * @return bool
     */
    public function isAutoLayoutEnabled(): bool
    {
        return $this->_autoLayout;
    }

    /**
     * Sets the plugin name to use.
     *
     * @param string|null $name Plugin name.
     *   Use null to remove the current plugin name.
     * @return $this
     */
    public function setPlugin(?string $name)
    {
        $this->_plugin = $name;

        return $this;
    }

    /**
     * Gets the plugin name to use.
     *
     * @return string|null
     */
    public function getPlugin(): ?string
    {
        return $this->_plugin;
    }

    /**
     * Adds a helper to use.
     *
     * @param string $helper Helper to use.
     * @param array $options Options.
     * @return $this
     * @since 4.1.0
     */
    public function addHelper(string $helper, array $options = [])
    {
        if ($options) {
            $array = [$helper => $options];
        } else {
            $array = [$helper];
        }

        return $this->setHelpers($array);
    }

    /**
     * Sets the helpers to use.
     *
     * @param array $helpers Helpers to use.
     * @param bool $merge Whether or not to merge existing data with the new data.
     * @return $this
     */
    public function setHelpers(array $helpers, bool $merge = true)
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
    public function getHelpers(): array
    {
        return $this->_helpers;
    }

    /**
     * Sets the view theme to use.
     *
     * @param string|null $theme Theme name.
     *   Use null to remove the current theme.
     * @return $this
     */
    public function setTheme(?string $theme)
    {
        $this->_theme = $theme;

        return $this;
    }

    /**
     * Gets the view theme to use.
     *
     * @return string|null
     */
    public function getTheme(): ?string
    {
        return $this->_theme;
    }

    /**
     * Sets the name of the view file to render. The name specified is the
     * filename in `templates/<SubFolder>/` without the .php extension.
     *
     * @param string|null $name View file name to set, or null to remove the template name.
     * @return $this
     */
    public function setTemplate(?string $name)
    {
        $this->_template = $name;

        return $this;
    }

    /**
     * Gets the name of the view file to render. The name specified is the
     * filename in `templates/<SubFolder>/` without the .php extension.
     *
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->_template;
    }

    /**
     * Sets the name of the layout file to render the view inside of.
     * The name specified is the filename of the layout in `templates/layout/`
     * without the .php extension.
     *
     * @param string|null $name Layout file name to set.
     * @return $this
     */
    public function setLayout(?string $name)
    {
        $this->_layout = $name;

        return $this;
    }

    /**
     * Gets the name of the layout file to render the view inside of.
     *
     * @return string|null
     */
    public function getLayout(): ?string
    {
        return $this->_layout;
    }

    /**
     * Get view option.
     *
     * @param string $name The name of the option.
     * @return mixed
     */
    public function getOption(string $name)
    {
        return $this->_options[$name] ?? null;
    }

    /**
     * Set view option.
     *
     * @param string $name The name of the option.
     * @param mixed $value Value to set.
     * @return $this
     */
    public function setOption(string $name, $value)
    {
        $this->_options[$name] = $value;

        return $this;
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
    public function setOptions(array $options, bool $merge = true)
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
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * Sets the view name.
     *
     * @param string|null $name The name of the view, or null to remove the current name.
     * @return $this
     */
    public function setName(?string $name)
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * Gets the view name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->_name;
    }

    /**
     * Sets the view classname.
     *
     * Accepts either a short name (Ajax) a plugin name (MyPlugin.Ajax)
     * or a fully namespaced name (App\View\AppView) or null to use the
     * View class provided by CakePHP.
     *
     * @param string|null $name The class name for the view.
     * @return $this
     */
    public function setClassName(?string $name)
    {
        $this->_className = $name;

        return $this;
    }

    /**
     * Gets the view classname.
     *
     * @return string|null
     */
    public function getClassName(): ?string
    {
        return $this->_className;
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
     * @param \Cake\Event\EventManagerInterface|null $events The event manager to use.
     * @return \Cake\View\View
     * @throws \Cake\View\Exception\MissingViewException
     */
    public function build(
        array $vars = [],
        ?ServerRequest $request = null,
        ?Response $response = null,
        ?EventManagerInterface $events = null
    ): View {
        $className = $this->_className;
        if ($className === null) {
            $className = App::className('App', 'View', 'View') ?? View::class;
        } elseif ($className === 'View') {
            $className = App::className($className, 'View');
        } else {
            $className = App::className($className, 'View', 'View');
        }
        if ($className === null) {
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
            'viewVars' => $vars + $this->_vars,
        ];
        $data += $this->_options;

        /** @var \Cake\View\View */
        return new $className($request, $response, $events, $data);
    }

    /**
     * Serializes the view builder object to a value that can be natively
     * serialized and re-used to clone this builder instance.
     *
     * There are  limitations for viewVars that are good to know:
     *
     * - ORM\Query executed and stored as resultset
     * - SimpleXMLElements stored as associative array
     * - Exceptions stored as strings
     * - Resources, \Closure and \PDO are not supported.
     *
     * @return array Serializable array of configuration properties.
     */
    public function jsonSerialize(): array
    {
        $properties = [
            '_templatePath', '_template', '_plugin', '_theme', '_layout', '_autoLayout',
            '_layoutPath', '_name', '_className', '_options', '_helpers', '_vars',
        ];

        $array = [];

        foreach ($properties as $property) {
            $array[$property] = $this->{$property};
        }

        array_walk_recursive($array['_vars'], [$this, '_checkViewVars']);

        return array_filter($array, function ($i) {
            return !is_array($i) && strlen((string)$i) || !empty($i);
        });
    }

    /**
     * Iterates through hash to clean up and normalize.
     *
     * @param mixed $item Reference to the view var value.
     * @param string $key View var key.
     * @return void
     * @throws \RuntimeException
     */
    protected function _checkViewVars(&$item, string $key): void
    {
        if ($item instanceof Exception) {
            $item = (string)$item;
        }

        if (
            is_resource($item) ||
            $item instanceof Closure ||
            $item instanceof PDO
        ) {
            throw new RuntimeException(sprintf(
                'Failed serializing the `%s` %s in the `%s` view var',
                is_resource($item) ? get_resource_type($item) : get_class($item),
                is_resource($item) ? 'resource' : 'object',
                $key
            ));
        }
    }

    /**
     * Configures a view builder instance from serialized config.
     *
     * @param array $config View builder configuration array.
     * @return $this Configured view builder instance.
     */
    public function createFromArray(array $config)
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
    public function serialize(): string
    {
        $array = $this->jsonSerialize();

        return serialize($array);
    }

    /**
     * Unserializes the view builder object.
     *
     * @param string $data Serialized string.
     * @return void
     */
    public function unserialize($data): void
    {
        $this->createFromArray(unserialize($data));
    }
}
