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
use InvalidArgumentException;
use JsonSerializable;
use PDO;
use function Cake\Core\pluginSplit;

/**
 * Provides an API for iteratively building a view up.
 *
 * Once you have configured the view and established all the context
 * you can create a view instance with `build()`.
 */
class ViewBuilder implements JsonSerializable
{
    /**
     * Deep merge strategy - recursively merges nested arrays (default for BC).
     *
     * @var string
     */
    public const MERGE_DEEP = 'deep';

    /**
     * Shallow merge strategy - simple array merge, replaces array values.
     *
     * @var string
     */
    public const MERGE_SHALLOW = 'shallow';

    /**
     * The subdirectory to the template.
     *
     * @var string|null
     */
    protected ?string $templatePath = null;

    /**
     * The template file to render.
     *
     * @var string|null
     */
    protected ?string $template = null;

    /**
     * The plugin name to use.
     *
     * @var string|null
     */
    protected ?string $plugin = null;

    /**
     * The theme name to use.
     *
     * @var string|null
     */
    protected ?string $theme = null;

    /**
     * The layout name to render.
     *
     * @var string|null
     */
    protected ?string $layout = null;

    /**
     * Whether autoLayout should be enabled.
     *
     * @var bool
     */
    protected bool $autoLayout = true;

    /**
     * The layout path to build the view with.
     *
     * @var string|null
     */
    protected ?string $layoutPath = null;

    /**
     * The view variables to use
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * The view class name to use.
     * Can either use plugin notation, a short name
     * or a fully namespaced classname.
     *
     * @var string|null
     * @phpstan-var class-string<\Cake\View\View>|string|null
     */
    protected ?string $className = null;

    /**
     * Additional options used when constructing the view.
     *
     * These options array lets you provide custom constructor
     * arguments to application/plugin view classes.
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * The merge strategy for config options.
     * Can be MERGE_DEEP (recursive merge, default for BC) or MERGE_SHALLOW (simple merge).
     *
     * @var self::MERGE_DEEP|self::MERGE_SHALLOW
     */
    protected string $configMergeStrategy = self::MERGE_DEEP;

    /**
     * The helpers to use
     *
     * @var array
     */
    protected array $helpers = [];

    /**
     * View vars
     *
     * @var array<string, mixed>
     */
    protected array $vars = [];

    /**
     * Saves a variable for use inside a template.
     *
     * @param string $name A string or an array of data.
     * @param mixed $value Value.
     * @return $this
     */
    public function setVar(string $name, mixed $value = null): static
    {
        $this->vars[$name] = $value;

        return $this;
    }

    /**
     * Saves view vars for use inside templates.
     *
     * @param array<string, mixed> $data Array of data.
     * @param bool $merge Whether to merge with existing vars, default true.
     * @return $this
     */
    public function setVars(array $data, bool $merge = true): static
    {
        if ($merge) {
            $this->vars = $data + $this->vars;
        } else {
            $this->vars = $data;
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
        return array_key_exists($name, $this->vars);
    }

    /**
     * Get view var
     *
     * @param string $name Var name
     * @return mixed The var value or null if unset.
     */
    public function getVar(string $name): mixed
    {
        return $this->vars[$name] ?? null;
    }

    /**
     * Get all view vars.
     *
     * @return array<string, mixed>
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * Sets path for template files.
     *
     * @param string|null $path Path for view files.
     * @return $this
     */
    public function setTemplatePath(?string $path): static
    {
        $this->templatePath = $path;

        return $this;
    }

    /**
     * Gets path for template files.
     *
     * @return string|null
     */
    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    /**
     * Sets path for layout files.
     *
     * @param string|null $path Path for layout files.
     * @return $this
     */
    public function setLayoutPath(?string $path): static
    {
        $this->layoutPath = $path;

        return $this;
    }

    /**
     * Gets path for layout files.
     *
     * @return string|null
     */
    public function getLayoutPath(): ?string
    {
        return $this->layoutPath;
    }

    /**
     * Turns on or off CakePHP's conventional mode of applying layout files.
     * On by default. Setting to off means that layouts will not be
     * automatically applied to rendered views.
     *
     * @param bool $enable Boolean to turn on/off.
     * @return $this
     */
    public function enableAutoLayout(bool $enable = true): static
    {
        $this->autoLayout = $enable;

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
    public function disableAutoLayout(): static
    {
        $this->autoLayout = false;

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
        return $this->autoLayout;
    }

    /**
     * Sets the plugin name to use.
     *
     * @param string|null $name Plugin name.
     *   Use null to remove the current plugin name.
     * @return $this
     */
    public function setPlugin(?string $name): static
    {
        $this->plugin = $name;

        return $this;
    }

    /**
     * Gets the plugin name to use.
     *
     * @return string|null
     */
    public function getPlugin(): ?string
    {
        return $this->plugin;
    }

    /**
     * Adds a helper to use, overwriting any existing one with that name.
     *
     * @param string $helper Helper to use.
     * @param array<string, mixed> $options Options.
     * @return $this
     * @since 4.1.0
     */
    public function addHelper(string $helper, array $options = [])
    {
        [$plugin, $name] = pluginSplit($helper);
        if ($plugin) {
            $options['className'] = $helper;
        }

        $this->helpers[$name] = $options;

        return $this;
    }

    /**
     * Adds helpers to use, overwriting any existing one with that name.
     *
     * @param array $helpers Helpers to use.
     * @return $this
     * @since 4.3.0
     */
    public function addHelpers(array $helpers): static
    {
        foreach ($helpers as $helper => $config) {
            if (is_int($helper)) {
                $helper = $config;
                $config = [];
            }
            $this->addHelper($helper, $config);
        }

        return $this;
    }

    /**
     * Sets the helpers to use, resetting the helpers config.
     *
     * @param array $helpers Helpers to use.
     * @return $this
     */
    public function setHelpers(array $helpers): static
    {
        $this->helpers = [];

        foreach ($helpers as $helper => $config) {
            if (is_int($helper)) {
                $helper = $config;
                $config = [];
            }
            $this->addHelper($helper, $config);
        }

        return $this;
    }

    /**
     * Gets the helpers to use.
     *
     * @return array
     */
    public function getHelpers(): array
    {
        return $this->helpers;
    }

    /**
     * Sets the view theme to use.
     *
     * @param string|null $theme Theme name.
     *   Use null to remove the current theme.
     * @return $this
     */
    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Gets the view theme to use.
     *
     * @return string|null
     */
    public function getTheme(): ?string
    {
        return $this->theme;
    }

    /**
     * Sets the name of the view file to render. The name specified is the
     * filename in `templates/<SubFolder>/` without the .php extension.
     *
     * @param string|null $name View file name to set, or null to remove the template name.
     * @return $this
     */
    public function setTemplate(?string $name): static
    {
        $this->template = $name;

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
        return $this->template;
    }

    /**
     * Sets the name of the layout file to render the view inside of.
     * The name specified is the filename of the layout in `templates/layout/`
     * without the .php extension.
     *
     * @param string|null $name Layout file name to set.
     * @return $this
     */
    public function setLayout(?string $name): static
    {
        $this->layout = $name;

        return $this;
    }

    /**
     * Gets the name of the layout file to render the view inside.
     *
     * @return string|null
     */
    public function getLayout(): ?string
    {
        return $this->layout;
    }

    /**
     * Get view option.
     *
     * @param string $name The name of the option.
     * @return mixed
     */
    public function getOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Set view option.
     *
     * @param string $name The name of the option.
     * @param mixed $value Value to set.
     * @return $this
     */
    public function setOption(string $name, mixed $value): static
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Sets additional options for the view.
     *
     * This lets you provide custom constructor arguments to application/plugin view classes.
     *
     * @param array<string, mixed> $options An array of options.
     * @param bool $merge Whether to merge existing data with the new data.
     * @return $this
     */
    public function setOptions(array $options, bool $merge = true): static
    {
        if ($merge) {
            $options += $this->options;
        }
        $this->options = $options;

        return $this;
    }

    /**
     * Gets additional options for the view.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set the config merge strategy for view options.
     *
     * Can be:
     *  - 'deep': Recursive merge (default for BC, merges nested arrays)
     *  - 'shallow': Simple array merge (replaces array values)
     *
     * This controls how options set via ViewBuilder are merged with
     * the View class's default configuration.
     *
     * @param self::MERGE_DEEP|self::MERGE_SHALLOW $strategy The merge strategy.
     * @return $this
     */
    public function setConfigMergeStrategy(string $strategy)
    {
        if (!in_array($strategy, [self::MERGE_DEEP, self::MERGE_SHALLOW], true)) {
            throw new InvalidArgumentException('Invalid merge strategy. Valid options are: `deep`, `shallow`.');
        }

        $this->configMergeStrategy = $strategy;

        return $this;
    }

    /**
     * Get the config merge strategy.
     *
     * @return self::MERGE_DEEP|self::MERGE_SHALLOW
     */
    public function getConfigMergeStrategy(): string
    {
        return $this->configMergeStrategy;
    }

    /**
     * Sets the view name.
     *
     * @param string|null $name The name of the view, or null to remove the current name.
     * @return $this
     */
    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the view name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
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
    public function setClassName(?string $name): static
    {
        $this->className = $name;

        return $this;
    }

    /**
     * Gets the view classname.
     *
     * @return string|null
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * Using the data in the builder, create a view instance.
     *
     * If className() is null, App\View\AppView will be used.
     * If that class does not exist, then {@link \Cake\View\View} will be used.
     *
     * @param \Cake\Http\ServerRequest|null $request The request to use.
     * @param \Cake\Http\Response|null $response The response to use.
     * @param \Cake\Event\EventManagerInterface|null $events The event manager to use.
     * @return \Cake\View\View
     * @throws \Cake\View\Exception\MissingViewException
     */
    public function build(
        ?ServerRequest $request = null,
        ?Response $response = null,
        ?EventManagerInterface $events = null,
    ): View {
        $className = $this->className ?? App::className('App', 'View', 'View') ?? View::class;
        if ($className === 'View') {
            $className = App::className($className, 'View');
        } else {
            $className = App::className($className, 'View', 'View');
        }
        if ($className === null) {
            throw new MissingViewException(['class' => $this->className]);
        }

        $data = [
            'name' => $this->name,
            'templatePath' => $this->templatePath,
            'template' => $this->template,
            'plugin' => $this->plugin,
            'theme' => $this->theme,
            'layout' => $this->layout,
            'autoLayout' => $this->autoLayout,
            'layoutPath' => $this->layoutPath,
            'helpers' => $this->helpers,
            'viewVars' => $this->vars,
            'configMergeStrategy' => $this->configMergeStrategy,
        ];
        $data += $this->options;

        /** @var \Cake\View\View */
        return new $className($request, $response, $events, $data);
    }

    /**
     * Serializes the view builder object to a value that can be natively
     * serialized and re-used to clone this builder instance.
     *
     * There are  limitations for viewVars that are good to know:
     *
     * - ORM\Query executed and stored as result set
     * - SimpleXMLElements stored as associative array
     * - Exceptions stored as strings
     * - Resources, \Closure and \PDO are not supported.
     *
     * @return array Serializable array of configuration properties.
     */
    public function jsonSerialize(): array
    {
        $properties = [
            'templatePath', 'template', 'plugin', 'theme', 'layout', 'autoLayout',
            'layoutPath', 'name', 'className', 'options', 'helpers', 'vars', 'configMergeStrategy',
        ];

        $array = [];

        foreach ($properties as $property) {
            $array[$property] = $this->{$property};
        }

        array_walk_recursive($array['vars'], $this->checkViewVars(...));

        return array_filter($array, function (array|bool|string|null $i) {
            return !is_array($i) && strlen((string)$i) || !empty($i);
        });
    }

    /**
     * Iterates through hash to clean up and normalize.
     *
     * @param mixed $item Reference to the view var value.
     * @param string $key View var key.
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function checkViewVars(mixed &$item, string $key): void
    {
        if ($item instanceof Exception) {
            $item = (string)$item;
        }

        if (
            is_resource($item) ||
            $item instanceof Closure ||
            $item instanceof PDO
        ) {
            throw new InvalidArgumentException(sprintf(
                'Failed serializing the `%s` %s in the `%s` view var',
                is_resource($item) ? get_resource_type($item) : $item::class,
                is_resource($item) ? 'resource' : 'object',
                $key,
            ));
        }
    }

    /**
     * Configures a view builder instance from serialized config.
     *
     * @param array<string, mixed> $config View builder configuration array.
     * @return $this
     */
    public function createFromArray(array $config): static
    {
        foreach ($config as $property => $value) {
            $this->{$property} = $value;
        }

        return $this;
    }

    /**
     * Magic method used for serializing the view builder object.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Magic method used to rebuild the view builder object.
     *
     * @param array<string, mixed> $data Data array.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->createFromArray($data);
    }
}
