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
 * @since         0.10.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Log\LogTrait;
use Cake\Routing\RequestActionTrait;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingElementException;
use Cake\View\Exception\MissingHelperException;
use Cake\View\Exception\MissingLayoutException;
use Cake\View\Exception\MissingTemplateException;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

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
 * public function beforeRender(\Cake\Event\Event $event)
 * {
 *      $this->viewBuilder()->setTheme('SuperHot');
 * }
 * ```
 *
 * in your Controller to use plugin `SuperHot` as a theme. Eg. If current action
 * is PostsController::index() then View class will look for template file
 * `plugins/SuperHot/Template/Posts/index.ctp`. If a theme template
 * is not found for the current action the default app template file is used.
 *
 * @property \Cake\View\Helper\BreadcrumbsHelper $Breadcrumbs
 * @property \Cake\View\Helper\FlashHelper $Flash
 * @property \Cake\View\Helper\FormHelper $Form
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\NumberHelper $Number
 * @property \Cake\View\Helper\PaginatorHelper $Paginator
 * @property \Cake\View\Helper\RssHelper $Rss
 * @property \Cake\View\Helper\SessionHelper $Session
 * @property \Cake\View\Helper\TextHelper $Text
 * @property \Cake\View\Helper\TimeHelper $Time
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \Cake\View\ViewBlock $Blocks
 * @property string $view
 * @property string $viewPath
 */
class View implements EventDispatcherInterface
{

    use CellTrait {
        cell as public;
    }
    use EventDispatcherTrait;
    use LogTrait;
    use RequestActionTrait;
    use ViewVarsTrait;

    /**
     * Helpers collection
     *
     * @var \Cake\View\HelperRegistry
     */
    protected $_helpers;

    /**
     * ViewBlock instance.
     *
     * @var \Cake\View\ViewBlock
     */
    protected $Blocks;

    /**
     * The name of the plugin.
     *
     * @var string|null
     */
    protected $plugin;

    /**
     * Name of the controller that created the View if any.
     *
     * @var string
     */
    protected $name;

    /**
     * Current passed params. Passed to View from the creating Controller for convenience.
     *
     * @var array
     * @deprecated 3.1.0 Use `$this->request->getParam('pass')` instead.
     */
    public $passedArgs = [];

    /**
     * An array of names of built-in helpers to include.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * The name of the subfolder containing templates for this View.
     *
     * @var string
     */
    protected $templatePath;

    /**
     * The name of the template file to render. The name specified
     * is the filename in /src/Template/<SubFolder> without the .ctp extension.
     *
     * @var string
     */
    protected $template;

    /**
     * The name of the layout file to render the template inside of. The name specified
     * is the filename of the layout in /src/Template/Layout without the .ctp
     * extension.
     *
     * @var string
     */
    protected $layout = 'default';

    /**
     * The name of the layouts subfolder containing layouts for this View.
     *
     * @var string
     */
    protected $layoutPath;

    /**
     * Turns on or off CakePHP's conventional mode of applying layout files. On by default.
     * Setting to off means that layouts will not be automatically applied to rendered templates.
     *
     * @var bool
     */
    protected $autoLayout = true;

    /**
     * File extension. Defaults to CakePHP's template ".ctp".
     *
     * @var string
     */
    protected $_ext = '.ctp';

    /**
     * Sub-directory for this template file. This is often used for extension based routing.
     * Eg. With an `xml` extension, $subDir would be `xml/`
     *
     * @var string
     */
    protected $subDir = '';

    /**
     * The view theme to use.
     *
     * @var string|null
     */
    protected $theme;

    /**
     * True when the view has been rendered.
     *
     * @var bool
     * @deprecated 3.7.0 The property is deprecated and will be removed in 4.0.0.
     */
    public $hasRendered = false;

    /**
     * List of generated DOM UUIDs.
     *
     * @var array
     * @deprecated 3.7.0 The property is deprecated and will be removed in 4.0.0.
     */
    public $uuids = [];

    /**
     * An instance of a \Cake\Http\ServerRequest object that contains information about the current request.
     * This object contains all the information about a request and several methods for reading
     * additional information about the request.
     *
     * @var \Cake\Http\ServerRequest
     */
    protected $request;

    /**
     * Reference to the Response object
     *
     * @var \Cake\Http\Response
     */
    protected $response;

    /**
     * The Cache configuration View will use to store cached elements. Changing this will change
     * the default configuration elements are stored under. You can also choose a cache config
     * per element.
     *
     * @var string
     * @see \Cake\View\View::element()
     */
    protected $elementCache = 'default';

    /**
     * List of variables to collect from the associated controller.
     *
     * @var array
     */
    protected $_passedVars = [
        'viewVars', 'autoLayout', 'helpers', 'template', 'layout', 'name', 'theme',
        'layoutPath', 'templatePath', 'plugin', 'passedArgs'
    ];

    /**
     * Holds an array of paths.
     *
     * @var array
     */
    protected $_paths = [];

    /**
     * Holds an array of plugin paths.
     *
     * @var array
     */
    protected $_pathsForPlugin = [];

    /**
     * The names of views and their parents used with View::extend();
     *
     * @var array
     */
    protected $_parents = [];

    /**
     * The currently rendering view file. Used for resolving parent files.
     *
     * @var string
     */
    protected $_current;

    /**
     * Currently rendering an element. Used for finding parent fragments
     * for elements.
     *
     * @var string
     */
    protected $_currentType = '';

    /**
     * Content stack, used for nested templates that all use View::extend();
     *
     * @var array
     */
    protected $_stack = [];

    /**
     * ViewBlock class.
     *
     * @var string
     */
    protected $_viewBlockClass = ViewBlock::class;

    /**
     * Constant for view file type 'view'
     *
     * @var string
     * @deprecated 3.1.0 Use TYPE_TEMPLATE instead.
     */
    const TYPE_VIEW = 'view';

    /**
     * Constant for view file type 'template'.
     *
     * @var string
     */
    const TYPE_TEMPLATE = 'view';

    /**
     * Constant for view file type 'element'
     *
     * @var string
     */
    const TYPE_ELEMENT = 'element';

    /**
     * Constant for name of view file 'Element'
     *
     * @var string
     */
    const NAME_ELEMENT = 'Element';

    /**
     * Constant for view file type 'layout'
     *
     * @var string
     */
    const TYPE_LAYOUT = 'layout';

    /**
     * Constant for template folder  'Template'
     *
     * @var string
     */
    const NAME_TEMPLATE = 'Template';

    /**
     * Constructor
     *
     * @param \Cake\Http\ServerRequest|null $request Request instance.
     * @param \Cake\Http\Response|null $response Response instance.
     * @param \Cake\Event\EventManager|null $eventManager Event manager instance.
     * @param array $viewOptions View options. See View::$_passedVars for list of
     *   options which get set as class properties.
     */
    public function __construct(
        ServerRequest $request = null,
        Response $response = null,
        EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        if (isset($viewOptions['view'])) {
            $this->setTemplate($viewOptions['view']);
        }
        if (isset($viewOptions['viewPath'])) {
            $this->setTemplatePath($viewOptions['viewPath']);
        }
        foreach ($this->_passedVars as $var) {
            if (isset($viewOptions[$var])) {
                $this->{$var} = $viewOptions[$var];
            }
        }
        if ($eventManager !== null) {
            $this->setEventManager($eventManager);
        }
        $this->request = $request ?: Router::getRequest(true);
        $this->response = $response ?: new Response();
        if (!$this->request) {
            $this->request = new ServerRequest([
                'base' => '',
                'url' => '',
                'webroot' => '/'
            ]);
        }
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
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * Gets the request instance.
     *
     * @return \Cake\Http\ServerRequest
     * @since 3.7.0
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Sets the request objects and configures a number of controller properties
     * based on the contents of the request. The properties that get set are:
     *
     * - $this->request - To the $request parameter
     * - $this->plugin - To the value returned by $request->getParam('plugin')
     * - $this->passedArgs - Same as $request->params['pass]
     *
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @return $this
     * @since 3.7.0
     */
    public function setRequest(ServerRequest $request)
    {
        $this->request = $request;
        $this->plugin = $request->getParam('plugin');

        if ($request->getParam('pass')) {
            $this->passedArgs = $request->getParam('pass');
        }

        return $this;
    }

    /**
     * Gets the response instance.
     *
     * @return \Cake\Http\Response
     * @since 3.7.0
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets the response instance.
     *
     * @param \Cake\Http\Response $response Response instance.
     * @return $this
     * @since 3.7.0
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
    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    /**
     * Set path for templates files.
     *
     * @param string $path Path for template files.
     * @return $this
     */
    public function setTemplatePath($path)
    {
        $this->templatePath = $path;

        return $this;
    }

    /**
     * Get/set path for templates files.
     *
     * @deprecated 3.5.0 Use getTemplatePath()/setTemplatePath() instead.
     * @param string|null $path Path for template files. If null returns current path.
     * @return string|null
     */
    public function templatePath($path = null)
    {
        deprecationWarning(
            'View::templatePath() is deprecated. ' .
            'Use getTemplatePath()/setTemplatePath() instead.'
        );

        if ($path === null) {
            return $this->templatePath;
        }

        $this->templatePath = $path;
    }

    /**
     * Get path for layout files.
     *
     * @return string
     */
    public function getLayoutPath()
    {
        return $this->layoutPath;
    }

    /**
     * Set path for layout files.
     *
     * @param string $path Path for layout files.
     * @return $this
     */
    public function setLayoutPath($path)
    {
        $this->layoutPath = $path;

        return $this;
    }

    /**
     * Get/set path for layout files.
     *
     * @deprecated 3.5.0 Use getLayoutPath()/setLayoutPath() instead.
     * @param string|null $path Path for layout files. If null returns current path.
     * @return string|null
     */
    public function layoutPath($path = null)
    {
        deprecationWarning(
            'View::layoutPath() is deprecated. ' .
            'Use getLayoutPath()/setLayoutPath() instead.'
        );

        if ($path === null) {
            return $this->layoutPath;
        }

        $this->layoutPath = $path;
    }

    /**
     * Returns if CakePHP's conventional mode of applying layout files is enabled.
     * Disabled means that layouts will not be automatically applied to rendered views.
     *
     * @return bool
     */
    public function isAutoLayoutEnabled()
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
    public function enableAutoLayout($enable = true)
    {
        $this->autoLayout = (bool)$enable;

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
     * Turns on or off CakePHP's conventional mode of applying layout files.
     * On by default. Setting to off means that layouts will not be
     * automatically applied to rendered templates.
     *
     * @deprecated 3.5.0 Use isAutoLayoutEnabled()/enableAutoLayout() instead.
     * @param bool|null $autoLayout Boolean to turn on/off. If null returns current value.
     * @return bool|null
     */
    public function autoLayout($autoLayout = null)
    {
        deprecationWarning(
            'View::autoLayout() is deprecated. ' .
            'Use isAutoLayoutEnabled()/enableAutoLayout() instead.'
        );

        if ($autoLayout === null) {
            return $this->autoLayout;
        }

        $this->autoLayout = $autoLayout;
    }

    /**
     * Get the current view theme.
     *
     * @return string|null
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set the view theme to use.
     *
     * @param string|null $theme Theme name.
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * The view theme to use.
     *
     * @deprecated 3.5.0 Use getTheme()/setTheme() instead.
     * @param string|null $theme Theme name. If null returns current theme.
     * @return string|null
     */
    public function theme($theme = null)
    {
        deprecationWarning(
            'View::theme() is deprecated. ' .
            'Use getTheme()/setTheme() instead.'
        );

        if ($theme === null) {
            return $this->theme;
        }

        $this->theme = $theme;
    }

    /**
     * Get the name of the template file to render. The name specified is the
     * filename in /src/Template/<SubFolder> without the .ctp extension.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set the name of the template file to render. The name specified is the
     * filename in /src/Template/<SubFolder> without the .ctp extension.
     *
     * @param string $name Template file name to set.
     * @return $this
     */
    public function setTemplate($name)
    {
        $this->template = $name;

        return $this;
    }

    /**
     * Get/set the name of the template file to render. The name specified is the
     * filename in /src/Template/<SubFolder> without the .ctp extension.
     *
     * @deprecated 3.5.0 Use getTemplate()/setTemplate() instead.
     * @param string|null $name Template file name to set. If null returns current name.
     * @return string|null
     */
    public function template($name = null)
    {
        deprecationWarning(
            'View::template() is deprecated. ' .
            'Use getTemplate()/setTemplate() instead.'
        );

        if ($name === null) {
            return $this->template;
        }

        $this->template = $name;
    }

    /**
     * Get the name of the layout file to render the template inside of.
     * The name specified is the filename of the layout in /src/Template/Layout
     * without the .ctp extension.
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Set the name of the layout file to render the template inside of.
     * The name specified is the filename of the layout in /src/Template/Layout
     * without the .ctp extension.
     *
     * @param string $name Layout file name to set.
     * @return $this
     */
    public function setLayout($name)
    {
        $this->layout = $name;

        return $this;
    }

    /**
     * Get/set the name of the layout file to render the template inside of.
     * The name specified is the filename of the layout in /src/Template/Layout
     * without the .ctp extension.
     *
     * @deprecated 3.5.0 Use getLayout()/setLayout() instead.
     * @param string|null $name Layout file name to set. If null returns current name.
     * @return string|null
     */
    public function layout($name = null)
    {
        deprecationWarning(
            'View::layout() is deprecated. ' .
            'Use getLayout()/setLayout() instead.'
        );

        if ($name === null) {
            return $this->layout;
        }

        $this->layout = $name;
    }

    /**
     * Renders a piece of PHP with provided parameters and returns HTML, XML, or any other string.
     *
     * This realizes the concept of Elements, (or "partial layouts") and the $params array is used to send
     * data to be used in the element. Elements can be cached improving performance by using the `cache` option.
     *
     * @param string $name Name of template file in the /src/Template/Element/ folder,
     *   or `MyPlugin.template` to use the template element from MyPlugin. If the element
     *   is not found in the plugin, the normal view path cascade will be searched.
     * @param array $data Array of data to be made available to the rendered view (i.e. the Element)
     * @param array $options Array of options. Possible keys are:
     * - `cache` - Can either be `true`, to enable caching using the config in View::$elementCache. Or an array
     *   If an array, the following keys can be used:
     *   - `config` - Used to store the cached element in a custom cache configuration.
     *   - `key` - Used to define the key used in the Cache::write(). It will be prefixed with `element_`
     * - `callbacks` - Set to true to fire beforeRender and afterRender helper callbacks for this element.
     *   Defaults to false.
     * - `ignoreMissing` - Used to allow missing elements. Set to true to not throw exceptions.
     * - `plugin` - setting to false will force to use the application's element from plugin templates, when the
     *   plugin has element with same name. Defaults to true
     * @return string Rendered Element
     * @throws \Cake\View\Exception\MissingElementException When an element is missing and `ignoreMissing`
     *   is false.
     */
    public function element($name, array $data = [], array $options = [])
    {
        $options += ['callbacks' => false, 'cache' => null, 'plugin' => null];
        if (isset($options['cache'])) {
            $options['cache'] = $this->_elementCache($name, $data, $options);
        }

        $pluginCheck = $options['plugin'] !== false;
        $file = $this->_getElementFileName($name, $pluginCheck);
        if ($file && $options['cache']) {
            return $this->cache(function () use ($file, $data, $options) {
                echo $this->_renderElement($file, $data, $options);
            }, $options['cache']);
        }
        if ($file) {
            return $this->_renderElement($file, $data, $options);
        }

        if (empty($options['ignoreMissing'])) {
            list ($plugin, $name) = pluginSplit($name, true);
            $name = str_replace('/', DIRECTORY_SEPARATOR, $name);
            $file = $plugin . static::NAME_ELEMENT . DIRECTORY_SEPARATOR . $name . $this->_ext;
            throw new MissingElementException([$file]);
        }
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
     * @param array $options The options defining the cache key etc.
     * @return string The rendered content.
     * @throws \RuntimeException When $options is lacking a 'key' option.
     */
    public function cache(callable $block, array $options = [])
    {
        $options += ['key' => '', 'config' => $this->elementCache];
        if (empty($options['key'])) {
            throw new RuntimeException('Cannot cache content with an empty key');
        }
        $result = Cache::read($options['key'], $options['config']);
        if ($result) {
            return $result;
        }
        ob_start();
        $block();
        $result = ob_get_clean();

        Cache::write($options['key'], $result, $options['config']);

        return $result;
    }

    /**
     * Checks if an element exists
     *
     * @param string $name Name of template file in the /src/Template/Element/ folder,
     *   or `MyPlugin.template` to check the template element from MyPlugin. If the element
     *   is not found in the plugin, the normal view path cascade will be searched.
     * @return bool Success
     */
    public function elementExists($name)
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
     * If View::$autoRender is false and no `$layout` is provided, the template will be returned bare.
     *
     * Template and layout names can point to plugin templates/layouts. Using the `Plugin.template` syntax
     * a plugin template/layout can be used instead of the app ones. If the chosen plugin is not found
     * the template will be located along the regular view path cascade.
     *
     * @param string|false|null $view Name of view file to use
     * @param string|null $layout Layout to use.
     * @return string|null Rendered content or null if content already rendered and returned earlier.
     * @throws \Cake\Core\Exception\Exception If there is an error in the view.
     * @triggers View.beforeRender $this, [$viewFileName]
     * @triggers View.afterRender $this, [$viewFileName]
     */
    public function render($view = null, $layout = null)
    {
        if ($this->hasRendered) {
            return null;
        }

        $defaultLayout = null;
        if ($layout !== null) {
            $defaultLayout = $this->layout;
            $this->layout = $layout;
        }

        $viewFileName = $view !== false ? $this->_getViewFileName($view) : null;
        if ($viewFileName) {
            $this->_currentType = static::TYPE_TEMPLATE;
            $this->dispatchEvent('View.beforeRender', [$viewFileName]);
            $this->Blocks->set('content', $this->_render($viewFileName));
            $this->dispatchEvent('View.afterRender', [$viewFileName]);
        }

        if ($this->layout && $this->autoLayout) {
            $this->Blocks->set('content', $this->renderLayout('', $this->layout));
        }
        if ($layout !== null) {
            $this->layout = $defaultLayout;
        }

        $this->hasRendered = true;

        return $this->Blocks->get('content');
    }

    /**
     * Renders a layout. Returns output from _render(). Returns false on error.
     * Several variables are created for use in layout.
     *
     * @param string $content Content to render in a template, wrapped by the surrounding layout.
     * @param string|null $layout Layout name
     * @return mixed Rendered output, or false on error
     * @throws \Cake\Core\Exception\Exception if there is an error in the view.
     * @triggers View.beforeLayout $this, [$layoutFileName]
     * @triggers View.afterLayout $this, [$layoutFileName]
     */
    public function renderLayout($content, $layout = null)
    {
        $layoutFileName = $this->_getLayoutFileName($layout);
        if (empty($layoutFileName)) {
            return $this->Blocks->get('content');
        }

        if (!empty($content)) {
             $this->Blocks->set('content', $content);
        }

        $this->dispatchEvent('View.beforeLayout', [$layoutFileName]);

        $title = $this->Blocks->get('title');
        if ($title === '') {
            $title = Inflector::humanize($this->templatePath);
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
     * @return string[] Array of the set view variable names.
     */
    public function getVars()
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
    public function get($var, $default = null)
    {
        if (!isset($this->viewVars[$var])) {
            return $default;
        }

        return $this->viewVars[$var];
    }

    /**
     * Get the names of all the existing blocks.
     *
     * @return array An array containing the blocks.
     * @see \Cake\View\ViewBlock::keys()
     */
    public function blocks()
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
    public function start($name)
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
    public function append($name, $value = null)
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
    public function prepend($name, $value)
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
    public function assign($name, $value)
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
    public function reset($name)
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
    public function fetch($name, $default = '')
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
     *
     * @return bool
     */
    public function exists($name)
    {
        return $this->Blocks->exists($name);
    }

    /**
     * Provides template or element extension/inheritance. Views can extends a
     * parent view and populate blocks in the parent template.
     *
     * @param string $name The template or element to 'extend' the current one with.
     * @return $this
     * @throws \LogicException when you extend a template with itself or make extend loops.
     * @throws \LogicException when you extend an element which doesn't exist
     */
    public function extend($name)
    {
        if ($name[0] === '/' || $this->_currentType === static::TYPE_TEMPLATE) {
            $parent = $this->_getViewFileName($name);
        } else {
            switch ($this->_currentType) {
                case static::TYPE_ELEMENT:
                    $parent = $this->_getElementFileName($name);
                    if (!$parent) {
                        list($plugin, $name) = $this->pluginSplit($name);
                        $paths = $this->_paths($plugin);
                        $defaultPath = $paths[0] . static::NAME_ELEMENT . DIRECTORY_SEPARATOR;
                        throw new LogicException(sprintf(
                            'You cannot extend an element which does not exist (%s).',
                            $defaultPath . $name . $this->_ext
                        ));
                    }
                    break;
                case static::TYPE_LAYOUT:
                    $parent = $this->_getLayoutFileName($name);
                    break;
                default:
                    $parent = $this->_getViewFileName($name);
            }
        }

        if ($parent == $this->_current) {
            throw new LogicException('You cannot have views extend themselves.');
        }
        if (isset($this->_parents[$parent]) && $this->_parents[$parent] == $this->_current) {
            throw new LogicException('You cannot have views extend in a loop.');
        }
        $this->_parents[$this->_current] = $parent;

        return $this;
    }

    /**
     * Generates a unique, non-random DOM ID for an object, based on the object type and the target URL.
     *
     * @param string $object Type of object, i.e. 'form' or 'link'
     * @param string $url The object's target URL
     * @return string
     * @deprecated 3.7.0 This method is deprecated and will be removed in 4.0.0.
     */
    public function uuid($object, $url)
    {
        deprecationWarning('View::uuid() is deprecated and will be removed in 4.0.0.');

        $c = 1;
        $url = Router::url($url);
        $hash = $object . substr(md5($object . $url), 0, 10);
        while (in_array($hash, $this->uuids)) {
            $hash = $object . substr(md5($object . $url . $c), 0, 10);
            $c++;
        }
        $this->uuids[] = $hash;

        return $hash;
    }

    /**
     * Retrieve the current view type
     *
     * @return string
     */
    public function getCurrentType()
    {
        return $this->_currentType;
    }

    /**
     * Magic accessor for helpers.
     *
     * @param string $name Name of the attribute to get.
     * @return mixed
     */
    public function __get($name)
    {
        try {
            $registry = $this->helpers();
            if (isset($registry->{$name})) {
                $this->{$name} = $registry->{$name};

                return $registry->{$name};
            }
        } catch (MissingHelperException $exception) {
        }

        $deprecated = [
            'view' => 'getTemplate',
            'viewPath' => 'getTemplatePath',
        ];
        if (isset($deprecated[$name])) {
            $method = $deprecated[$name];
            deprecationWarning(sprintf(
                'View::$%s is deprecated. Use View::%s() instead.',
                $name,
                $method
            ));

            return $this->{$method}();
        }

        $protected = [
            'templatePath' => 'getTemplatePath',
            'template' => 'getTemplate',
            'layout' => 'getLayout',
            'layoutPath' => 'getLayoutPath',
            'autoLayout' => 'isAutoLayoutEnabled',
            'theme' => 'getTheme',
            'request' => 'getRequest',
            'response' => 'getResponse',
            'subDir' => 'getSubdir',
            'plugin' => 'getPlugin',
            'name' => 'getName',
        ];
        if (isset($protected[$name])) {
            $method = $protected[$name];
            deprecationWarning(sprintf(
                'View::$%s is protected now. Use View::%s() instead.',
                $name,
                $method
            ));

            return $this->{$method}();
        }

        if ($name === 'Blocks') {
            deprecationWarning(
                'View::$Blocks is protected now. ' .
                'Use one of the wrapper methods like View::fetch() etc. instead.'
            );

            return $this->Blocks;
        }

        if ($name === 'helpers') {
            deprecationWarning(
                'View::$helpers is protected now. ' .
                'Use the helper registry through View::helpers() to manage helpers.'
            );

            return $this->helpers;
        }

        if (!empty($exception)) {
            throw $exception;
        }

        return $this->{$name};
    }

    /**
     * Magic setter for deprecated properties.
     *
     * @param string $name Name to property.
     * @param mixed $value Value for property.
     * @return void
     */
    public function __set($name, $value)
    {
        $deprecated = [
            'view' => 'setTemplate',
            'viewPath' => 'setTemplatePath',
        ];
        if (isset($deprecated[$name])) {
            $method = $deprecated[$name];
            deprecationWarning(sprintf(
                'View::$%s is deprecated. Use View::%s() instead.',
                $name,
                $method
            ));

            $this->{$method}($value);

            return;
        }

        $protected = [
            'templatePath' => 'setTemplatePath',
            'template' => 'setTemplate',
            'layout' => 'setLayout',
            'layoutPath' => 'setLayoutPath',
            'autoLayout' => 'enableAutoLayout',
            'theme' => 'setTheme',
            'request' => 'setRequest',
            'response' => 'setResponse',
            'subDir' => 'setSubDir',
            'plugin' => 'setPlugin',
            'elementCache' => 'setElementCache',
        ];
        if (isset($protected[$name])) {
            $method = $protected[$name];
            deprecationWarning(sprintf(
                'View::$%s is protected now. Use View::%s() instead.',
                $name,
                $method
            ));

            $this->{$method}($value);

            return;
        }

        if ($name === 'helpers') {
            deprecationWarning(
                'View::$helpers is protected now. ' .
                'Use the helper registry through View::helpers() to manage helpers.'
            );

            return $this->helpers = $value;
        }

        if ($name === 'name') {
            deprecationWarning(
                'View::$name is protected now. ' .
                'You can use viewBuilder()->setName() to change the name a view uses before building it.'
            );
        }

        $this->{$name} = $value;
    }

    /**
     * Interact with the HelperRegistry to load all the helpers.
     *
     * @return $this
     */
    public function loadHelpers()
    {
        $registry = $this->helpers();
        $helpers = $registry->normalizeArray($this->helpers);
        foreach ($helpers as $properties) {
            $this->loadHelper($properties['class'], $properties['config']);
        }

        return $this;
    }

    /**
     * Renders and returns output for given template filename with its
     * array of data. Handles parent/extended templates.
     *
     * @param string $viewFile Filename of the view
     * @param array $data Data to include in rendered view. If empty the current
     *   View::$viewVars will be used.
     * @return string Rendered output
     * @throws \LogicException When a block is left open.
     * @triggers View.beforeRenderFile $this, [$viewFile]
     * @triggers View.afterRenderFile $this, [$viewFile, $content]
     */
    protected function _render($viewFile, $data = [])
    {
        if (empty($data)) {
            $data = $this->viewVars;
        }
        $this->_current = $viewFile;
        $initialBlocks = count($this->Blocks->unclosed());

        $this->dispatchEvent('View.beforeRenderFile', [$viewFile]);

        $content = $this->_evaluate($viewFile, $data);

        $afterEvent = $this->dispatchEvent('View.afterRenderFile', [$viewFile, $content]);
        if ($afterEvent->getResult() !== null) {
            $content = $afterEvent->getResult();
        }

        if (isset($this->_parents[$viewFile])) {
            $this->_stack[] = $this->fetch('content');
            $this->assign('content', $content);

            $content = $this->_render($this->_parents[$viewFile]);
            $this->assign('content', array_pop($this->_stack));
        }

        $remainingBlocks = count($this->Blocks->unclosed());

        if ($initialBlocks !== $remainingBlocks) {
            throw new LogicException(sprintf(
                'The "%s" block was left open. Blocks are not allowed to cross files.',
                $this->Blocks->active()
            ));
        }

        return $content;
    }

    /**
     * Sandbox method to evaluate a template / view script in.
     *
     * @param string $viewFile Filename of the view
     * @param array $dataForView Data to include in rendered view.
     * @return string Rendered output
     */
    protected function _evaluate($viewFile, $dataForView)
    {
        extract($dataForView);
        ob_start();

        include func_get_arg(0);

        return ob_get_clean();
    }

    /**
     * Get the helper registry in use by this View class.
     *
     * @return \Cake\View\HelperRegistry
     */
    public function helpers()
    {
        if ($this->_helpers === null) {
            $this->_helpers = new HelperRegistry($this);
        }

        return $this->_helpers;
    }

    /**
     * Loads a helper. Delegates to the `HelperRegistry::load()` to load the helper
     *
     * @param string $name Name of the helper to load.
     * @param array $config Settings for the helper
     * @return \Cake\View\Helper a constructed helper object.
     * @see \Cake\View\HelperRegistry::load()
     */
    public function loadHelper($name, array $config = [])
    {
        list(, $class) = pluginSplit($name);
        $helpers = $this->helpers();

        return $this->{$class} = $helpers->load($name, $config);
    }

    /**
     * Set sub-directory for this template files.
     *
     * @param string $subDir Sub-directory name.
     * @return $this
     * @see \Cake\View\View::$subDir
     * @since 3.7.0
     */
    public function setSubDir($subDir)
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
    public function getSubDir()
    {
        return $this->subDir;
    }

    /**
     * Returns the View's controller name.
     *
     * @return string|null
     * @since 3.7.7
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the plugin name.
     *
     * @return string|null
     * @since 3.7.0
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * Sets the plugin name.
     *
     * @param string $name Plugin name.
     * @return $this
     * @since 3.7.0
     */
    public function setPlugin($name)
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
    public function setElementCache($elementCache)
    {
        $this->elementCache = $elementCache;

        return $this;
    }

    /**
     * Returns filename of given action's template file (.ctp) as a string.
     * CamelCased action names will be under_scored by default.
     * This means that you can have LongActionNames that refer to
     * long_action_names.ctp views. You can change the inflection rule by
     * overriding _inflectViewFileName.
     *
     * @param string|null $name Controller action to find template filename for
     * @return string Template filename
     * @throws \Cake\View\Exception\MissingTemplateException when a view file could not be found.
     */
    protected function _getViewFileName($name = null)
    {
        $templatePath = $subDir = '';

        if ($this->templatePath) {
            $templatePath = $this->templatePath . DIRECTORY_SEPARATOR;
        }
        if (strlen($this->subDir)) {
            $subDir = $this->subDir . DIRECTORY_SEPARATOR;
            // Check if templatePath already terminates with subDir
            if ($templatePath != $subDir && substr($templatePath, -strlen($subDir)) == $subDir) {
                $subDir = '';
            }
        }

        if ($name === null) {
            $name = $this->template;
        }

        list($plugin, $name) = $this->pluginSplit($name);
        $name = str_replace('/', DIRECTORY_SEPARATOR, $name);

        if (strpos($name, DIRECTORY_SEPARATOR) === false && $name !== '' && $name[0] !== '.') {
            $name = $templatePath . $subDir . $this->_inflectViewFileName($name);
        } elseif (strpos($name, DIRECTORY_SEPARATOR) !== false) {
            if ($name[0] === DIRECTORY_SEPARATOR || $name[1] === ':') {
                $name = trim($name, DIRECTORY_SEPARATOR);
            } elseif (!$plugin || $this->templatePath !== $this->name) {
                $name = $templatePath . $subDir . $name;
            } else {
                $name = DIRECTORY_SEPARATOR . $subDir . $name;
            }
        }

        foreach ($this->_paths($plugin) as $path) {
            if (file_exists($path . $name . $this->_ext)) {
                return $this->_checkFilePath($path . $name . $this->_ext, $path);
            }
        }
        throw new MissingTemplateException(['file' => $name . $this->_ext]);
    }

    /**
     * Change the name of a view template file into underscored format.
     *
     * @param string $name Name of file which should be inflected.
     * @return string File name after conversion
     */
    protected function _inflectViewFileName($name)
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
    protected function _checkFilePath($file, $path)
    {
        if (strpos($file, '..') === false) {
            return $file;
        }
        $absolute = realpath($file);
        if (strpos($absolute, $path) !== 0) {
            throw new InvalidArgumentException(sprintf(
                'Cannot use "%s" as a template, it is not within any view template path.',
                $file
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
     * @return array Array with 2 indexes. 0 => plugin name, 1 => filename
     */
    public function pluginSplit($name, $fallback = true)
    {
        $plugin = null;
        list($first, $second) = pluginSplit($name);
        if (Plugin::isLoaded($first) === true) {
            $name = $second;
            $plugin = $first;
        }
        if (isset($this->plugin) && !$plugin && $fallback) {
            $plugin = $this->plugin;
        }

        return [$plugin, $name];
    }

    /**
     * Returns layout filename for this template as a string.
     *
     * @param string|null $name The name of the layout to find.
     * @return string Filename for layout file (.ctp).
     * @throws \Cake\View\Exception\MissingLayoutException when a layout cannot be located
     */
    protected function _getLayoutFileName($name = null)
    {
        if ($name === null) {
            $name = $this->layout;
        }
        $subDir = null;

        if ($this->layoutPath) {
            $subDir = $this->layoutPath . DIRECTORY_SEPARATOR;
        }
        list($plugin, $name) = $this->pluginSplit($name);

        $layoutPaths = $this->_getSubPaths('Layout' . DIRECTORY_SEPARATOR . $subDir);

        foreach ($this->_paths($plugin) as $path) {
            foreach ($layoutPaths as $layoutPath) {
                $currentPath = $path . $layoutPath;
                if (file_exists($currentPath . $name . $this->_ext)) {
                    return $this->_checkFilePath($currentPath . $name . $this->_ext, $currentPath);
                }
            }
        }
        throw new MissingLayoutException([
            'file' => $layoutPaths[0] . $name . $this->_ext
        ]);
    }

    /**
     * Finds an element filename, returns false on failure.
     *
     * @param string $name The name of the element to find.
     * @param bool $pluginCheck - if false will ignore the request's plugin if parsed plugin is not loaded
     * @return string|false Either a string to the element filename or false when one can't be found.
     */
    protected function _getElementFileName($name, $pluginCheck = true)
    {
        list($plugin, $name) = $this->pluginSplit($name, $pluginCheck);

        $paths = $this->_paths($plugin);
        $elementPaths = $this->_getSubPaths(static::NAME_ELEMENT);

        foreach ($paths as $path) {
            foreach ($elementPaths as $elementPath) {
                if (file_exists($path . $elementPath . DIRECTORY_SEPARATOR . $name . $this->_ext)) {
                    return $path . $elementPath . DIRECTORY_SEPARATOR . $name . $this->_ext;
                }
            }
        }

        return false;
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
     * @return array Array with all the templates paths.
     */
    protected function _getSubPaths($basePath)
    {
        $paths = [$basePath];
        if ($this->request->getParam('prefix')) {
            $prefixPath = explode('/', $this->request->getParam('prefix'));
            $path = '';
            foreach ($prefixPath as $prefixPart) {
                $path .= Inflector::camelize($prefixPart) . DIRECTORY_SEPARATOR;

                array_unshift(
                    $paths,
                    $path . $basePath
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
     * @return array paths
     */
    protected function _paths($plugin = null, $cached = true)
    {
        if ($cached === true) {
            if ($plugin === null && !empty($this->_paths)) {
                return $this->_paths;
            }
            if ($plugin !== null && isset($this->_pathsForPlugin[$plugin])) {
                return $this->_pathsForPlugin[$plugin];
            }
        }
        $templatePaths = App::path(static::NAME_TEMPLATE);
        $pluginPaths = $themePaths = [];
        if (!empty($plugin)) {
            for ($i = 0, $count = count($templatePaths); $i < $count; $i++) {
                $pluginPaths[] = $templatePaths[$i] . 'Plugin' . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR;
            }
            $pluginPaths = array_merge($pluginPaths, App::path(static::NAME_TEMPLATE, $plugin));
        }

        if (!empty($this->theme)) {
            $themePaths = App::path(static::NAME_TEMPLATE, Inflector::camelize($this->theme));

            if ($plugin) {
                for ($i = 0, $count = count($themePaths); $i < $count; $i++) {
                    array_unshift($themePaths, $themePaths[$i] . 'Plugin' . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR);
                }
            }
        }

        $paths = array_merge(
            $themePaths,
            $pluginPaths,
            $templatePaths,
            [dirname(__DIR__) . DIRECTORY_SEPARATOR . static::NAME_TEMPLATE . DIRECTORY_SEPARATOR]
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
     * @param array $options Element options
     * @return array Element Cache configuration.
     */
    protected function _elementCache($name, $data, $options)
    {
        if (isset($options['cache']['key'], $options['cache']['config'])) {
            $cache = $options['cache'];
            $cache['key'] = 'element_' . $cache['key'];

            return $cache;
        }

        $plugin = null;
        list($plugin, $name) = $this->pluginSplit($name);

        $underscored = null;
        if ($plugin) {
            $underscored = Inflector::underscore($plugin);
        }

        $cache = $options['cache'];
        unset($options['cache'], $options['callbacks'], $options['plugin']);
        $keys = array_merge(
            [$underscored, $name],
            array_keys($options),
            array_keys($data)
        );
        $config = [
            'config' => $this->elementCache,
            'key' => implode('_', $keys)
        ];
        if (is_array($cache)) {
            $defaults = [
                'config' => $this->elementCache,
                'key' => $config['key']
            ];
            $config = $cache + $defaults;
        }
        $config['key'] = 'element_' . $config['key'];

        return $config;
    }

    /**
     * Renders an element and fires the before and afterRender callbacks for it
     * and writes to the cache if a cache is used
     *
     * @param string $file Element file path
     * @param array $data Data to render
     * @param array $options Element options
     * @return string
     * @triggers View.beforeRender $this, [$file]
     * @triggers View.afterRender $this, [$file, $element]
     */
    protected function _renderElement($file, $data, $options)
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
