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
 * @since         0.10.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Exception\CakeException;
use Cake\Core\InstanceConfigTrait;
use Cake\Core\Plugin;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManagerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Log\LogTrait;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingElementException;
use Cake\View\Exception\MissingLayoutException;
use Cake\View\Exception\MissingTemplateException;
use Generator;
use InvalidArgumentException;
use LogicException;
use Throwable;
use function Cake\Core\pluginSplit;

/**
 * View, the V in the MVC triad. View interacts with Helpers and view variables passed
 * in from the controller to render the results of the controller action. Often this is HTML,
 * but can also take the form of JSON, XML, PDF's or streaming files.
 *
 * CakePHP uses a two-step-view pattern. This means that the template content is rendered first,
 * and then inserted into the selected layout. This also means you can pass data from the template to the
 * layout using `$this->set()`
 *
 * View class supports using plugins as themes. You can set
 *
 * ```
 * public function beforeRender(\Cake\Event\EventInterface $event)
 * {
 *      $this->viewBuilder()->setTheme('SuperHot');
 * }
 * ```
 *
 * in your Controller to use plugin `SuperHot` as a theme. Eg. If current action
 * is PostsController::index() then View class will look for template file
 * `plugins/SuperHot/templates/Posts/index.php`. If a theme template
 * is not found for the current action the default app template file is used.
 *
 * @property \Cake\View\Helper\BreadcrumbsHelper $Breadcrumbs
 * @property \Cake\View\Helper\FlashHelper $Flash
 * @property \Cake\View\Helper\FormHelper $Form
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\NumberHelper $Number
 * @property \Cake\View\Helper\PaginatorHelper $Paginator
 * @property \Cake\View\Helper\TextHelper $Text
 * @property \Cake\View\Helper\TimeHelper $Time
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \Cake\View\ViewBlock $Blocks
 * @template TSubject of \Cake\View\View
 * @implements \Cake\Event\EventDispatcherInterface<TSubject>
 */
class View implements EventDispatcherInterface
{
    use CellTrait {
        cell as public;
    }
    /**
     * @use \Cake\Event\EventDispatcherTrait<TSubject>
     */
    use EventDispatcherTrait;
    use InstanceConfigTrait;
    use LogTrait;

    /**
     * Helpers collection
     *
     * @var \Cake\View\HelperRegistry|null
     */
    protected ?HelperRegistry $_helpers = null;

    /**
     * ViewBlock instance.
     *
     * @var \Cake\View\ViewBlock
     */
    protected ViewBlock $Blocks;

    /**
     * The name of the plugin.
     *
     * @var string|null
     */
    protected ?string $plugin = null;

    /**
     * Name of the controller that created the View if any.
     *
     * @var string
     */
    protected string $name = '';

    /**
     * A configuration array for helpers to be loaded.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $helpers = [];

    /**
     * The name of the subfolder containing templates for this View.
     *
     * @var string
     */
    protected string $templatePath = '';

    /**
     * The name of the template file to render. The name specified
     * is the filename in `templates/<SubFolder>/` without the .php extension.
     *
     * @var string
     */
    protected string $template = '';

    /**
     * The name of the layout file to render the template inside of. The name specified
     * is the filename of the layout in `templates/layout/` without the .php
     * extension.
     *
     * @var string
     */
    protected string $layout = 'default';

    /**
     * The name of the layouts subfolder containing layouts for this View.
     *
     * @var string
     */
    protected string $layoutPath = '';

    /**
     * Turns on or off CakePHP's conventional mode of applying layout files. On by default.
     * Setting to off means that layouts will not be automatically applied to rendered templates.
     *
     * @var bool
     */
    protected bool $autoLayout = true;

    /**
     * An array of variables
     *
     * @var array<string, mixed>
     */
    protected array $viewVars = [];

    /**
     * File extension. Defaults to ".php".
     *
     * @var string
     */
    protected string $_ext = '.php';

    /**
     * Sub-directory for this template file. This is often used for extension based routing.
     * Eg. With an `xml` extension, $subDir would be `xml/`
     *
     * @var string
     */
    protected string $subDir = '';

    /**
     * The view theme to use.
     *
     * @var string|null
     */
    protected ?string $theme = null;

    /**
     * An instance of a \Cake\Http\ServerRequest object that contains information about the current request.
     * This object contains all the information about a request and several methods for reading
     * additional information about the request.
     *
     * @var \Cake\Http\ServerRequest
     */
    protected ServerRequest $request;

    /**
     * Reference to the Response object
     *
     * @var \Cake\Http\Response
     */
    protected Response $response;

    /**
     * The Cache configuration View will use to store cached elements. Changing this will change
     * the default configuration elements are stored under. You can also choose a cache config
     * per element.
     *
     * @var string
     * @see \Cake\View\View::element()
     */
    protected string $elementCache = 'default';

    /**
     * The merge strategy for config options.
     * Can be MERGE_DEEP (recursive merge, default for BC) or MERGE_SHALLOW (simple merge).
     *
     * @var string
     */
    protected string $configMergeStrategy = ViewBuilder::MERGE_DEEP;

    /**
     * List of variables to collect from the associated controller.
     *
     * @var array<string>
     */
    protected array $_passedVars = [
        'viewVars', 'autoLayout', 'helpers', 'template', 'layout', 'name', 'theme',
        'layoutPath', 'templatePath', 'plugin', 'configMergeStrategy',
    ];

    /**
     * Default custom config options.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [];

    /**
     * Holds an array of paths.
     *
     * @var array<string>
     */
    protected array $_paths = [];

    /**
     * Holds an array of plugin paths.
     *
     * @var array<string, array<string>>
     */
    protected array $_pathsForPlugin = [];

    /**
     * The names of views and their parents used with View::extend();
     *
     * @var array<string, string>
     */
    protected array $_parents = [];

    /**
     * The currently rendering view file. Used for resolving parent files.
     *
     * @var string
     */
    protected string $_current = '';

    /**
     * Currently rendering an element. Used for finding parent fragments
     * for elements.
     *
     * @var string
     */
    protected string $_currentType = '';

    /**
     * Content stack, used for nested templates that all use View::extend();
     *
     * @var array<string>
     */
    protected array $_stack = [];

    /**
     * ViewBlock class.
     *
     * @var string
     * @phpstan-var class-string<\Cake\View\ViewBlock>
     */
    protected string $_viewBlockClass = ViewBlock::class;

    /**
     * Constant for view file type 'template'.
     *
     * @var string
     */
    public const TYPE_TEMPLATE = 'template';

    /**
     * Constant for view file type 'element'
     *
     * @var string
     */
    public const TYPE_ELEMENT = 'element';

    /**
     * Constant for view file type 'layout'
     *
     * @var string
     */
    public const TYPE_LAYOUT = 'layout';

    /**
     * Constant for type used for App::path().
     *
     * @var string
     */
    public const NAME_TEMPLATE = 'templates';

    /**
     * Constant for folder name containing files for overriding plugin templates.
     *
     * @var string
     */
    public const PLUGIN_TEMPLATE_FOLDER = 'plugin';

    /**
     * The magic 'match-all' content type that views can use to
     * behave as a fallback during content-type negotiation.
     *
     * @var string
     */
    public const TYPE_MATCH_ALL = '_match_all_';

    /**
     * Constructor
     *
     * @param \Cake\Http\ServerRequest|null $request Request instance.
     * @param \Cake\Http\Response|null $response Response instance.
     * @param \Cake\Event\EventManagerInterface|null $eventManager Event manager instance.
     * @param array<string, mixed> $viewOptions View options. See {@link View::$_passedVars} for list of
     *   options which get set as class properties.
     */
    public function __construct(
        ?ServerRequest $request = null,
        ?Response $response = null,
        ?EventManagerInterface $eventManager = null,
        array $viewOptions = [],
    ) {
        if ($eventManager !== null) {
            // Set the event manager before accessing the helper registry below
            // to ensure that helpers are registered as listeners with the manager when loaded.
            $this->setEventManager($eventManager);
        }

        foreach ($this->_passedVars as $var) {
            if (isset($viewOptions[$var])) {
                $this->{$var} = $viewOptions[$var];
            }
        }
        if ($this->helpers) {
            $this->helpers = $this->helpers()->normalizeArray($this->helpers);
        }

        $config = array_diff_key(
            $viewOptions,
            array_flip($this->_passedVars),
        );

        if ($this->configMergeStrategy === ViewBuilder::MERGE_SHALLOW) {
            $this->configShallow($config);
        } else {
            $this->setConfig($config);
        }

        $request ??= Router::getRequest() ?: new ServerRequest(['base' => '', 'url' => '', 'webroot' => '/']);
        $this->request = $request;
        $this->response = $response ?: new Response();
        $this->Blocks = new $this->_viewBlockClass();
        $this->initialize();
        $this->loadHelpers();
    }

    /**
     * Initialization hook method.
     *
     * Properties like $helpers etc. cannot be initialized statically in your custom
     * view class as they are overwritten by values from controller in constructor.
     * So this method allows you to manipulate them as required after view instance
     * is constructed.
     *
     * Helpers can be added using {@link addHelper()} method.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->setContentType();
    }

    /**
     * Set the response content-type based on the view's contentType()
     *
     * @return void
     */
    protected function setContentType(): void
    {
        $viewContentType = static::contentType();
        if (!$viewContentType || $viewContentType === static::TYPE_MATCH_ALL) {
            return;
        }
        $response = $this->getResponse();
        $responseType = $response->getHeaderLine('Content-Type');
        if ($responseType === '' || str_starts_with($responseType, 'text/html')) {
            $response = $response->withType($viewContentType);
        }
        $this->setResponse($response);
    }

    /**
     * Mime-type this view class renders as.
     *
     * @return string Either the content type or '' which means no type.
     */
    public static function contentType(): string
    {
        return '';
    }

    /**
     * Gets the request instance.
     *
     * @return \Cake\Http\ServerRequest
     * @since 3.7.0
     */
    public function getRequest(): ServerRequest
    {
        return $this->request;
    }

    /**
     * Sets the request objects and configures a number of controller properties
     * based on the contents of the request. The properties that get set are:
     *
     * - $this->request - To the $request parameter
     * - $this->plugin - To the value returned by $request->getParam('plugin')
     *
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @return $this
     */
    public function setRequest(ServerRequest $request)
    {
        $this->request = $request;
        $this->plugin = $request->getParam('plugin');

        return $this;
    }

    /**
     * Gets the response instance.
     *
     * @return \Cake\Http\Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Sets the response instance.
     *
     * @param \Cake\Http\Response $response Response instance.
     * @return $this
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Get path for templates files.
     *
     * @return string
     */
    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    /**
     * Set path for templates files.
     *
     * @param string $path Path for template files.
     * @return $this
     */
    public function setTemplatePath(string $path)
    {
        $this->templatePath = $path;

        return $this;
    }

    /**
     * Get path for layout files.
     *
     * @return string
     */
    public function getLayoutPath(): string
    {
        return $this->layoutPath;
    }

    /**
     * Set path for layout files.
     *
     * @param string $path Path for layout files.
     * @return $this
     */
    public function setLayoutPath(string $path)
    {
        $this->layoutPath = $path;

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
     * Turns on or off CakePHP's conventional mode of applying layout files.
     * On by default. Setting to off means that layouts will not be
     * automatically applied to rendered views.
     *
     * @param bool $enable Boolean to turn on/off.
     * @return $this
     */
    public function enableAutoLayout(bool $enable = true)
    {
        $this->autoLayout = $enable;

        return $this;
    }

    /**
     * Turns off CakePHP's conventional mode of applying layout files.
     * Layouts will not be automatically applied to rendered views.
     *
     * @return $this
     */
    public function disableAutoLayout()
    {
        $this->autoLayout = false;

        return $this;
    }

    /**
     * Get the current view theme.
     *
     * @return string|null
     */
    public function getTheme(): ?string
    {
        return $this->theme;
    }

    /**
     * Set the view theme to use.
     *
     * @param string|null $theme Theme name.
     * @return $this
     */
    public function setTheme(?string $theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get the name of the template file to render. The name specified is the
     * filename in `templates/<SubFolder>/` without the .php extension.
     *
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Set the name of the template file to render. The name specified is the
     * filename in `templates/<SubFolder>/` without the .php extension.
     *
     * @param string $name Template file name to set.
     * @return $this
     */
    public function setTemplate(string $name)
    {
        $this->template = $name;

        return $this;
    }

    /**
     * Get the name of the layout file to render the template inside of.
     * The name specified is the filename of the layout in `templates/layout/`
     * without the .php extension.
     *
     * @return string
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * Set the name of the layout file to render the template inside of.
     * The name specified is the filename of the layout in `templates/layout/`
     * without the .php extension.
     *
     * @param string $name Layout file name to set.
     * @return $this
     */
    public function setLayout(string $name)
    {
        $this->layout = $name;

        return $this;
    }

    /**
     * Renders a piece of PHP with provided parameters and returns HTML, XML, or any other string.
     *
     * This realizes the concept of Elements, (or "partial layouts") and the $params array is used to send
     * data to be used in the element. Elements can be cached improving performance by using the `cache` option.
     *
     * @param string $name Name of template file in the `templates/element/` folder,
     *   or `MyPlugin.template` to use the template element from MyPlugin. If the element
     *   is not found in the plugin, the normal view path cascade will be searched.
     * @param array $data Array of data to be made available to the rendered view (i.e. the Element)
     * @param array<string, mixed> $options Array of options. Possible keys are:
     *
     * - `cache` - Can either be `true`, to enable caching using the config in View::$elementCache. Or an array
     *   If an array, the following keys can be used:
     *
     *   - `config` - Used to store the cached element in a custom cache configuration.
     *   - `key` - Used to define the key used in the Cache::write(). It will be prefixed with `element_`
     *
     * - `callbacks` - Set to true to fire beforeRender and afterRender helper callbacks for this element.
     *   Defaults to false.
     * - `ignoreMissing` - Used to allow missing elements. Set to true to not throw exceptions.
     * - `plugin` - setting to false will force to use the application's element from plugin templates, when the
     *   plugin has element with same name. Defaults to true
     * @return string Rendered Element
     * @throws \Cake\View\Exception\MissingElementException When an element is missing and `ignoreMissing`
     *   is false.
     * @phpstan-param array{cache?:array|true, callbacks?:bool, plugin?:string|false, ignoreMissing?:bool} $options
     */
    public function element(string $name, array $data = [], array $options = []): string
    {
        $options += ['callbacks' => false, 'cache' => null, 'plugin' => null, 'ignoreMissing' => false];
        if (isset($options['cache'])) {
            $options['cache'] = $this->_elementCache(
                $name,
                $data,
                array_diff_key($options, ['callbacks' => false, 'plugin' => null, 'ignoreMissing' => null]),
            );
        }

        $pluginCheck = $options['plugin'] !== false;
        $file = $this->_getElementFileName($name, $pluginCheck);
        if ($file && $options['cache']) {
            return $this->cache(function () use ($file, $data, $options): void {
                echo $this->_renderElement($file, $data, $options);
            }, $options['cache']);
        }
        if ($file) {
            return $this->_renderElement($file, $data, $options);
        }

        if ($options['ignoreMissing']) {
            return '';
        }

        [$plugin, $elementName] = $this->pluginSplit($name, $pluginCheck);
        $paths = iterator_to_array($this->getElementPaths($plugin));
        throw new MissingElementException([$name . $this->_ext, $elementName . $this->_ext], $paths);
    }

    /**
     * Create a cached block of view logic.
     *
     * This allows you to cache a block of view output into the cache
     * defined in `elementCache`.
     *
     * This method will attempt to read the cache first. If the cache
     * is empty, the $block will be run and the output stored.
     *
     * @param callable $block The block of code that you want to cache the output of.
     * @param array<string, mixed> $options The options defining the cache key etc.
     * @return string The rendered content.
     * @throws \InvalidArgumentException When $options is lacking a 'key' option.
     */
    public function cache(callable $block, array $options = []): string
    {
        $options += ['key' => '', 'config' => $this->elementCache];
        if (empty($options['key'])) {
            throw new InvalidArgumentException('Cannot cache content with an empty key');
        }
        $result = Cache::read($options['key'], $options['config']);
        if ($result) {
            return $result;
        }

        $bufferLevel = ob_get_level();
        ob_start();

        try {
            $block();
        } catch (Throwable $exception) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            throw $exception;
        }

        $result = (string)ob_get_clean();

        Cache::write($options['key'], $result, $options['config']);

        return $result;
    }

    /**
     * Checks if an element exists
     *
     * @param string $name Name of template file in the `templates/element/` folder,
     *   or `MyPlugin.template` to check the template element from MyPlugin. If the element
     *   is not found in the plugin, the normal view path cascade will be searched.
     * @return bool Success
     */
    public function elementExists(string $name): bool
    {
        return (bool)$this->_getElementFileName($name);
    }

    /**
     * Renders view for given template file and layout.
     *
     * Render triggers helper callbacks, which are fired before and after the template are rendered,
     * as well as before and after the layout. The helper callbacks are called:
     *
     * - `beforeRender`
     * - `afterRender`
     * - `beforeLayout`
     * - `afterLayout`
     *
     * If View::$autoLayout is set to `false`, the template will be returned bare.
     *
     * Template and layout names can point to plugin templates or layouts. Using the `Plugin.template` syntax
     * a plugin template/layout/ can be used instead of the app ones. If the chosen plugin is not found
     * the template will be located along the regular view path cascade.
     *
     * @param string|null $template Name of template file to use
     * @param string|false|null $layout Layout to use. False to disable.
     * @return string Rendered content.
     * @throws \Cake\Core\Exception\CakeException If there is an error in the view.
     * @triggers View.beforeRender $this, [$templateFileName]
     * @triggers View.afterRender $this, [$templateFileName]
     */
    public function render(?string $template = null, string|false|null $layout = null): string
    {
        $defaultLayout = '';
        $defaultAutoLayout = null;
        if ($layout === false) {
            $defaultAutoLayout = $this->autoLayout;
            $this->autoLayout = false;
        } elseif ($layout !== null) {
            $defaultLayout = $this->layout;
            $this->layout = $layout;
        }

        $templateFileName = $this->_getTemplateFileName($template);
        $this->_currentType = static::TYPE_TEMPLATE;
        $this->dispatchEvent('View.beforeRender', [$templateFileName]);
        $this->Blocks->set('content', $this->_render($templateFileName));
        $this->dispatchEvent('View.afterRender', [$templateFileName]);

        if ($this->autoLayout) {
            if (!$this->layout) {
                throw new CakeException(
                    'View::$layout must be a non-empty string.' .
                    'To disable layout rendering use method `View::disableAutoLayout()` instead.',
                );
            }

            $this->Blocks->set('content', $this->renderLayout('', $this->layout));
        }
        if ($layout !== null) {
            $this->layout = $defaultLayout;
        }
        if ($defaultAutoLayout !== null) {
            $this->autoLayout = $defaultAutoLayout;
        }

        return $this->Blocks->get('content');
    }

    /**
     * Renders a layout. Returns output from _render().
     *
     * Several variables are created for use in layout.
     *
     * @param string $content Content to render in a template, wrapped by the surrounding layout.
     * @param string|null $layout Layout name
     * @return string Rendered output.
     * @throws \Cake\Core\Exception\CakeException if there is an error in the view.
     * @triggers View.beforeLayout $this, [$layoutFileName]
     * @triggers View.afterLayout $this, [$layoutFileName]
     */
    public function renderLayout(string $content, ?string $layout = null): string
    {
        $layoutFileName = $this->_getLayoutFileName($layout);

        if ($content) {
            $this->Blocks->set('content', $content);
        }

        $this->dispatchEvent('View.beforeLayout', [$layoutFileName]);

        $title = $this->Blocks->get('title');
        if ($title === '') {
            $title = Inflector::humanize(str_replace(DIRECTORY_SEPARATOR, '/', $this->templatePath));
            $this->Blocks->set('title', $title);
        }

        $this->_currentType = static::TYPE_LAYOUT;
        $this->Blocks->set('content', $this->_render($layoutFileName));

        $this->dispatchEvent('View.afterLayout', [$layoutFileName]);

        return $this->Blocks->get('content');
    }

    /**
     * Returns a list of variables available in the current View context
     *
     * @return array<string> Array of the set view variable names.
     */
    public function getVars(): array
    {
        return array_keys($this->viewVars);
    }

    /**
     * Returns the contents of the given View variable.
     *
     * @param string $var The view var you want the contents of.
     * @param mixed $default The default/fallback content of $var.
     * @return mixed The content of the named var if its set, otherwise $default.
     */
    public function get(string $var, mixed $default = null): mixed
    {
        return $this->viewVars[$var] ?? $default;
    }

    /**
     * Saves a variable or an associative array of variables for use inside a template.
     *
     * @param array|string $name A string or an array of data.
     * @param mixed $value Value in case $name is a string (which then works as the key).
     *   Unused if $name is an associative array, otherwise serves as the values to $name's keys.
     * @return $this
     * @throws \Cake\Core\Exception\CakeException If the array combine operation failed.
     */
    public function set(array|string $name, mixed $value = null)
    {
        if (is_array($name)) {
            if (is_array($value)) {
                $data = array_combine($name, $value);
                if ($data === false) {
                    throw new CakeException(
                        'Invalid data provided for array_combine() to work: Both $name and $value require same count.',
                    );
                }
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
     * Get the names of all the existing blocks.
     *
     * @return array<string> An array containing the blocks.
     * @see \Cake\View\ViewBlock::keys()
     */
    public function blocks(): array
    {
        return $this->Blocks->keys();
    }

    /**
     * Start capturing output for a 'block'
     *
     * You can use start on a block multiple times to
     * append or prepend content in a capture mode.
     *
     * ```
     * // Append content to an existing block.
     * $this->start('content');
     * echo $this->fetch('content');
     * echo 'Some new content';
     * $this->end();
     *
     * // Prepend content to an existing block
     * $this->start('content');
     * echo 'Some new content';
     * echo $this->fetch('content');
     * $this->end();
     * ```
     *
     * @param string $name The name of the block to capture for.
     * @return $this
     * @see \Cake\View\ViewBlock::start()
     */
    public function start(string $name)
    {
        $this->Blocks->start($name);

        return $this;
    }

    /**
     * Append to an existing or new block.
     *
     * Appending to a new block will create the block.
     *
     * @param string $name Name of the block
     * @param mixed $value The content for the block. Value will be type cast
     *   to string.
     * @return $this
     * @see \Cake\View\ViewBlock::concat()
     */
    public function append(string $name, mixed $value = null)
    {
        $this->Blocks->concat($name, $value);

        return $this;
    }

    /**
     * Prepend to an existing or new block.
     *
     * Prepending to a new block will create the block.
     *
     * @param string $name Name of the block
     * @param mixed $value The content for the block. Value will be type cast
     *   to string.
     * @return $this
     * @see \Cake\View\ViewBlock::concat()
     */
    public function prepend(string $name, mixed $value)
    {
        $this->Blocks->concat($name, $value, ViewBlock::PREPEND);

        return $this;
    }

    /**
     * Set the content for a block. This will overwrite any
     * existing content.
     *
     * @param string $name Name of the block
     * @param mixed $value The content for the block. Value will be type cast
     *   to string.
     * @return $this
     * @see \Cake\View\ViewBlock::set()
     */
    public function assign(string $name, mixed $value)
    {
        $this->Blocks->set($name, $value);

        return $this;
    }

    /**
     * Reset the content for a block. This will overwrite any
     * existing content.
     *
     * @param string $name Name of the block
     * @return $this
     * @see \Cake\View\ViewBlock::set()
     */
    public function reset(string $name)
    {
        $this->assign($name, '');

        return $this;
    }

    /**
     * Fetch the content for a block. If a block is
     * empty or undefined '' will be returned.
     *
     * @param string $name Name of the block
     * @param string $default Default text
     * @return string The block content or $default if the block does not exist.
     * @see \Cake\View\ViewBlock::get()
     */
    public function fetch(string $name, string $default = ''): string
    {
        return $this->Blocks->get($name, $default);
    }

    /**
     * End a capturing block. The compliment to View::start()
     *
     * @return $this
     * @see \Cake\View\ViewBlock::end()
     */
    public function end()
    {
        $this->Blocks->end();

        return $this;
    }

    /**
     * Check if a block exists
     *
     * @param string $name Name of the block
     * @return bool
     */
    public function exists(string $name): bool
    {
        return $this->Blocks->exists($name);
    }

    /**
     * Provides template or element extension/inheritance. Templates can extends a
     * parent template and populate blocks in the parent template.
     *
     * @param string $name The template or element to 'extend' the current one with.
     * @return $this
     * @throws \LogicException when you extend a template with itself or make extend loops.
     * @throws \LogicException when you extend an element which doesn't exist
     */
    public function extend(string $name)
    {
        $type = str_starts_with($name, '/') ? static::TYPE_TEMPLATE : $this->_currentType;
        switch ($type) {
            case static::TYPE_ELEMENT:
                $parent = $this->_getElementFileName($name);
                if (!$parent) {
                    [$plugin, $name] = $this->pluginSplit($name);
                    $paths = $this->_paths($plugin);
                    $defaultPath = $paths[0] . static::TYPE_ELEMENT . DIRECTORY_SEPARATOR;
                    throw new LogicException(sprintf(
                        'You cannot extend an element which does not exist (%s).',
                        $defaultPath . $name . $this->_ext,
                    ));
                }
                break;
            case static::TYPE_LAYOUT:
                $parent = $this->_getLayoutFileName($name);
                break;
            default:
                $parent = $this->_getTemplateFileName($name);
        }

        if ($parent === $this->_current) {
            throw new LogicException('You cannot have templates extend themselves.');
        }
        if (isset($this->_parents[$parent]) && $this->_parents[$parent] === $this->_current) {
            throw new LogicException('You cannot have templates extend in a loop.');
        }
        $this->_parents[$this->_current] = $parent;

        return $this;
    }

    /**
     * Retrieve the current template type
     *
     * @return string
     */
    public function getCurrentType(): string
    {
        return $this->_currentType;
    }

    /**
     * Magic accessor for helpers.
     *
     * @param string $name Name of the attribute to get.
     * @return \Cake\View\Helper|null
     */
    public function __get(string $name): ?Helper
    {
        return $this->helpers()->{$name};
    }

    /**
     * Interact with the HelperRegistry to load all the helpers.
     *
     * @return $this
     */
    public function loadHelpers()
    {
        foreach ($this->helpers as $name => $config) {
            $this->loadHelper($name, $config);
        }

        return $this;
    }

    /**
     * Renders and returns output for given template filename with its
     * array of data. Handles parent/extended templates.
     *
     * @param string $templateFile Filename of the template
     * @param array $data Data to include in rendered view. If empty the current
     *   View::$viewVars will be used.
     * @return string Rendered output
     * @throws \LogicException When a block is left open.
     * @triggers View.beforeRenderFile $this, [$templateFile]
     * @triggers View.afterRenderFile $this, [$templateFile, $content]
     */
    protected function _render(string $templateFile, array $data = []): string
    {
        if (!$data) {
            $data = $this->viewVars;
        }
        $this->_current = $templateFile;
        $initialBlocks = count($this->Blocks->unclosed());

        $this->dispatchEvent('View.beforeRenderFile', [$templateFile]);

        $content = $this->_evaluate($templateFile, $data);

        $afterEvent = $this->dispatchEvent('View.afterRenderFile', [$templateFile, $content]);
        if ($afterEvent->getResult() !== null) {
            $content = $afterEvent->getResult();
        }

        if (isset($this->_parents[$templateFile])) {
            $this->_stack[] = $this->fetch('content');
            $this->assign('content', $content);

            $content = $this->_render($this->_parents[$templateFile]);
            $this->assign('content', array_pop($this->_stack));
        }

        $remainingBlocks = count($this->Blocks->unclosed());

        if ($initialBlocks !== $remainingBlocks) {
            throw new LogicException(sprintf(
                'The `%s` block was left open. Blocks are not allowed to cross files.',
                (string)$this->Blocks->active(),
            ));
        }

        return $content;
    }

    /**
     * Sandbox method to evaluate a template / view script in.
     *
     * @param string $templateFile Filename of the template.
     * @param array $dataForView Data to include in rendered view.
     * @return string Rendered output
     */
    protected function _evaluate(string $templateFile, array $dataForView): string
    {
        extract($dataForView);

        $bufferLevel = ob_get_level();
        ob_start();

        try {
            // Avoiding $templateFile here due to collision with extract() vars.
            include func_get_arg(0);
        } catch (Throwable $exception) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            throw $exception;
        }

        return (string)ob_get_clean();
    }

    /**
     * Get the helper registry in use by this View class.
     *
     * @return \Cake\View\HelperRegistry
     */
    public function helpers(): HelperRegistry
    {
        return $this->_helpers ??= new HelperRegistry($this);
    }

    /**
     * Adds a helper from within `initialize()` method.
     *
     * @param string $helper Helper.
     * @param array<string, mixed> $config Config.
     * @return void
     */
    protected function addHelper(string $helper, array $config = []): void
    {
        [$plugin, $name] = pluginSplit($helper);
        if ($plugin) {
            $config['className'] = $helper;
        }

        $this->helpers[$name] = $config;
    }

    /**
     * Loads a helper. Delegates to the `HelperRegistry::load()` to load the helper.
     *
     * You should use `addHelper()` instead of this method from the `initialize()` hook of `AppView` or other custom View classes.
     *
     * @param string $name Name of the helper to load.
     * @param array<string, mixed> $config Settings for the helper
     * @return \Cake\View\Helper a constructed helper object.
     * @see \Cake\View\HelperRegistry::load()
     */
    public function loadHelper(string $name, array $config = []): Helper
    {
        /** @var \Cake\View\Helper */
        return $this->helpers()->load($name, $config);
    }

    /**
     * Set sub-directory for this template files.
     *
     * @param string $subDir Sub-directory name.
     * @return $this
     * @see \Cake\View\View::$subDir
     * @since 3.7.0
     */
    public function setSubDir(string $subDir)
    {
        $this->subDir = $subDir;

        return $this;
    }

    /**
     * Get sub-directory for this template files.
     *
     * @return string
     * @see \Cake\View\View::$subDir
     * @since 3.7.0
     */
    public function getSubDir(): string
    {
        return $this->subDir;
    }

    /**
     * Returns the View's controller name.
     *
     * @return string
     * @since 3.7.7
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the plugin name.
     *
     * @return string|null
     * @since 3.7.0
     */
    public function getPlugin(): ?string
    {
        return $this->plugin;
    }

    /**
     * Sets the plugin name.
     *
     * @param string|null $name Plugin name.
     * @return $this
     * @since 3.7.0
     */
    public function setPlugin(?string $name)
    {
        $this->plugin = $name;

        return $this;
    }

    /**
     * Set The cache configuration View will use to store cached elements
     *
     * @param string $elementCache Cache config name.
     * @return $this
     * @see \Cake\View\View::$elementCache
     * @since 3.7.0
     */
    public function setElementCache(string $elementCache)
    {
        $this->elementCache = $elementCache;

        return $this;
    }

    /**
     * Returns filename of given action's template file as a string.
     * CamelCased action names will be under_scored by default.
     * This means that you can have LongActionNames that refer to
     * long_action_names.php templates. You can change the inflection rule by
     * overriding _inflectTemplateFileName.
     *
     * @param string|null $name Controller action to find template filename for
     * @return string Template filename
     * @throws \Cake\View\Exception\MissingTemplateException when a template file could not be found.
     * @throws \Cake\Core\Exception\CakeException When template name not provided.
     */
    protected function _getTemplateFileName(?string $name = null): string
    {
        $templatePath = '';
        $subDir = '';
        if ($this->templatePath) {
            $templatePath = $this->templatePath . DIRECTORY_SEPARATOR;
        }
        if ($this->subDir !== '') {
            $subDir = $this->subDir . DIRECTORY_SEPARATOR;
            // Check if templatePath already terminates with subDir
            if ($templatePath !== $subDir && str_ends_with($templatePath, $subDir)) {
                $subDir = '';
            }
        }

        $name ??= $this->template;

        if (!$name) {
            throw new MissingTemplateException('');
        }

        [$plugin, $name] = $this->pluginSplit($name);
        $name = str_replace('/', DIRECTORY_SEPARATOR, $name);

        if (!str_contains($name, DIRECTORY_SEPARATOR) && $name !== '' && !str_starts_with($name, '.')) {
            $name = $templatePath . $subDir . $this->_inflectTemplateFileName($name);
        } elseif (str_contains($name, DIRECTORY_SEPARATOR)) {
            if (str_starts_with($name, DIRECTORY_SEPARATOR) || $name[1] === ':') {
                $name = trim($name, DIRECTORY_SEPARATOR);
            } elseif (!$plugin || $this->templatePath !== $this->name) {
                $name = $templatePath . $subDir . $name;
            } else {
                $name = $subDir . $name;
            }
        }

        $name .= $this->_ext;
        $paths = $this->_paths($plugin);
        foreach ($paths as $path) {
            if (is_file($path . $name)) {
                return $this->_checkFilePath($path . $name, $path);
            }
        }

        throw new MissingTemplateException($name, $paths);
    }

    /**
     * Change the name of a view template file into underscored format.
     *
     * @param string $name Name of file which should be inflected.
     * @return string File name after conversion
     */
    protected function _inflectTemplateFileName(string $name): string
    {
        return Inflector::underscore($name);
    }

    /**
     * Check that a view file path does not go outside of the defined template paths.
     *
     * Only paths that contain `..` will be checked, as they are the ones most likely to
     * have the ability to resolve to files outside of the template paths.
     *
     * @param string $file The path to the template file.
     * @param string $path Base path that $file should be inside of.
     * @return string The file path
     * @throws \InvalidArgumentException
     */
    protected function _checkFilePath(string $file, string $path): string
    {
        if (!str_contains($file, '..')) {
            return $file;
        }
        $absolute = realpath($file);
        if ($absolute === false || !str_starts_with($absolute, $path)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot use `%s` as a template, it is not within any view template path.',
                $file,
            ));
        }

        return $absolute;
    }

    /**
     * Splits a dot syntax plugin name into its plugin and filename.
     * If $name does not have a dot, then index 0 will be null.
     * It checks if the plugin is loaded, else filename will stay unchanged for filenames containing dot
     *
     * @param string $name The name you want to plugin split.
     * @param bool $fallback If true uses the plugin set in the current Request when parsed plugin is not loaded
     * @return array Array with 2 indexes. 0 => plugin name, 1 => filename.
     * @phpstan-return array{string|null, string}
     */
    public function pluginSplit(string $name, bool $fallback = true): array
    {
        $plugin = null;
        [$first, $second] = pluginSplit($name);
        if ($first && Plugin::isLoaded($first)) {
            $name = $second;
            $plugin = $first;
        }
        if ($this->plugin !== null && !$plugin && $fallback) {
            $plugin = $this->plugin;
        }

        return [$plugin, $name];
    }

    /**
     * Returns layout filename for this template as a string.
     *
     * @param string|null $name The name of the layout to find.
     * @return string Filename for layout file.
     * @throws \Cake\View\Exception\MissingLayoutException when a layout cannot be located
     * @throws \Cake\Core\Exception\CakeException
     */
    protected function _getLayoutFileName(?string $name = null): string
    {
        if ($name === null) {
            if (!$this->layout) {
                throw new CakeException(
                    'View::$layout must be a non-empty string.' .
                    'To disable layout rendering use method `View::disableAutoLayout()` instead.',
                );
            }
            $name = $this->layout;
        }
        [$plugin, $name] = $this->pluginSplit($name);
        $name .= $this->_ext;

        foreach ($this->getLayoutPaths($plugin) as $path) {
            if (is_file($path . $name)) {
                return $this->_checkFilePath($path . $name, $path);
            }
        }

        $paths = iterator_to_array($this->getLayoutPaths($plugin));
        throw new MissingLayoutException($name, $paths);
    }

    /**
     * Get an iterator for layout paths.
     *
     * @param string|null $plugin The plugin to fetch paths for.
     * @return \Generator
     */
    protected function getLayoutPaths(?string $plugin): Generator
    {
        $subDir = '';
        if ($this->layoutPath) {
            $subDir = $this->layoutPath . DIRECTORY_SEPARATOR;
        }
        $layoutPaths = $this->_getSubPaths(static::TYPE_LAYOUT . DIRECTORY_SEPARATOR . $subDir);

        foreach ($layoutPaths as $layoutPath) {
            foreach ($this->_paths($plugin) as $path) {
                yield $path . $layoutPath;
            }
        }
    }

    /**
     * Finds an element filename, returns false on failure.
     *
     * @param string $name The name of the element to find.
     * @param bool $pluginCheck - if false will ignore the request's plugin if parsed plugin is not loaded
     * @return string|false Either a string to the element filename or false when one can't be found.
     */
    protected function _getElementFileName(string $name, bool $pluginCheck = true): string|false
    {
        [$plugin, $name] = $this->pluginSplit($name, $pluginCheck);

        $name .= $this->_ext;
        foreach ($this->getElementPaths($plugin) as $path) {
            if (is_file($path . $name)) {
                return $path . $name;
            }
        }

        return false;
    }

    /**
     * Get an iterator for element paths.
     *
     * @param string|null $plugin The plugin to fetch paths for.
     * @return \Generator
     */
    protected function getElementPaths(?string $plugin): Generator
    {
        $elementPaths = $this->_getSubPaths(static::TYPE_ELEMENT);
        foreach ($elementPaths as $subDir) {
            foreach ($this->_paths($plugin) as $path) {
                yield $path . $subDir . DIRECTORY_SEPARATOR;
            }
        }
    }

    /**
     * Find all sub templates path, based on $basePath
     * If a prefix is defined in the current request, this method will prepend
     * the prefixed template path to the $basePath, cascading up in case the prefix
     * is nested.
     * This is essentially used to find prefixed template paths for elements
     * and layouts.
     *
     * @param string $basePath Base path on which to get the prefixed one.
     * @return array<string> Array with all the templates paths.
     */
    protected function _getSubPaths(string $basePath): array
    {
        $paths = [$basePath];
        if ($this->request->getParam('prefix')) {
            $prefixPath = explode('/', $this->request->getParam('prefix'));
            $path = '';
            foreach ($prefixPath as $prefixPart) {
                $path .= Inflector::camelize($prefixPart) . DIRECTORY_SEPARATOR;

                array_unshift(
                    $paths,
                    $path . $basePath,
                );
            }
        }

        return $paths;
    }

    /**
     * Return all possible paths to find view files in order
     *
     * @param string|null $plugin Optional plugin name to scan for view files.
     * @param bool $cached Set to false to force a refresh of view paths. Default true.
     * @return array<string> paths
     */
    protected function _paths(?string $plugin = null, bool $cached = true): array
    {
        if ($cached) {
            if ($plugin === null && $this->_paths !== []) {
                return $this->_paths;
            }
            if ($plugin !== null && isset($this->_pathsForPlugin[$plugin])) {
                return $this->_pathsForPlugin[$plugin];
            }
        }
        $templatePaths = array_values(App::path(static::NAME_TEMPLATE));
        $pluginPaths = [];
        $themePaths = [];
        if ($plugin) {
            foreach ($templatePaths as $templatePath) {
                $pluginPaths[] = $templatePath
                    . static::PLUGIN_TEMPLATE_FOLDER
                    . DIRECTORY_SEPARATOR
                    . $plugin
                    . DIRECTORY_SEPARATOR;
            }
            $pluginPaths[] = Plugin::templatePath($plugin);
        }

        if ($this->theme) {
            $themePath = Plugin::templatePath(Inflector::camelize($this->theme));

            if ($plugin) {
                $themePaths[] = $themePath
                    . static::PLUGIN_TEMPLATE_FOLDER
                    . DIRECTORY_SEPARATOR
                    . $plugin
                    . DIRECTORY_SEPARATOR;
            }

            $themePaths[] = $themePath;
        }

        $paths = array_merge(
            $themePaths,
            $pluginPaths,
            $templatePaths,
            App::core('templates'),
        );

        if ($plugin !== null) {
            return $this->_pathsForPlugin[$plugin] = $paths;
        }

        return $this->_paths = $paths;
    }

    /**
     * Generate the cache configuration options for an element.
     *
     * @param string $name Element name
     * @param array $data Data
     * @param array<string, mixed> $options Element options
     * @return array<string, mixed> Element Cache configuration.
     * @phpstan-return array{key:string, config:string}
     */
    protected function _elementCache(string $name, array $data, array $options): array
    {
        if (isset($options['cache']['key'], $options['cache']['config'])) {
            /** @phpstan-var array{key:string, config:string} $cache */
            $cache = $options['cache'];
            $cache['key'] = 'element_' . $cache['key'];

            return $cache;
        }

        [$plugin, $name] = $this->pluginSplit($name);

        $pluginKey = null;
        if ($plugin) {
            $pluginKey = str_replace('/', '_', Inflector::underscore($plugin));
        }
        $elementKey = str_replace(['\\', '/'], '_', $name);

        $cache = $options['cache'];
        unset($options['cache']);
        $keys = array_merge(
            [$pluginKey, $elementKey],
            array_keys($options),
            array_keys($data),
        );
        $config = [
            'config' => $this->elementCache,
            'key' => implode('_', $keys),
        ];
        if (is_array($cache)) {
            $config = $cache + $config;
        }
        $config['key'] = 'element_' . $config['key'];

        /** @var array{config: string, key: string} */
        return $config;
    }

    /**
     * Renders an element and fires the before and afterRender callbacks for it
     * and writes to the cache if a cache is used
     *
     * @param string $file Element file path
     * @param array $data Data to render
     * @param array<string, mixed> $options Element options
     * @return string
     * @triggers View.beforeRender $this, [$file]
     * @triggers View.afterRender $this, [$file, $element]
     */
    protected function _renderElement(string $file, array $data, array $options): string
    {
        $current = $this->_current;
        $restore = $this->_currentType;
        $this->_currentType = static::TYPE_ELEMENT;

        if ($options['callbacks']) {
            $this->dispatchEvent('View.beforeRender', [$file]);
        }

        $element = $this->_render($file, array_merge($this->viewVars, $data));

        if ($options['callbacks']) {
            $this->dispatchEvent('View.afterRender', [$file, $element]);
        }

        $this->_currentType = $restore;
        $this->_current = $current;

        return $element;
    }
}
