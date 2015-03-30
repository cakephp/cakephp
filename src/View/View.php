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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Event\EventManager;
use Cake\Event\EventManagerTrait;
use Cake\Log\LogTrait;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\RequestActionTrait;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\CellTrait;
use Cake\View\ViewVarsTrait;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

/**
 * View, the V in the MVC triad. View interacts with Helpers and view variables passed
 * in from the controller to render the results of the controller action. Often this is HTML,
 * but can also take the form of JSON, XML, PDF's or streaming files.
 *
 * CakePHP uses a two-step-view pattern. This means that the view content is rendered first,
 * and then inserted into the selected layout. This also means you can pass data from the view to the
 * layout using `$this->set()`
 *
 * View class supports using plugins as themes. You can set
 * `$this->theme = 'SuperHot'` in your Controller to use plugin `SuperHot` as a
 * theme. Eg. If current action is Posts::index() then View class will look for
 * template file `plugins/SuperHot/Template/Posts/index.ctp`. If a theme template
 * is not found for the current action the default app template file is used.
 *
 * @property      \Cake\View\Helper\FormHelper $Form
 * @property      \Cake\View\Helper\HtmlHelper $Html
 * @property      \Cake\View\Helper\NumberHelper $Number
 * @property      \Cake\View\Helper\PaginatorHelper $Paginator
 * @property      \Cake\View\Helper\RssHelper $Rss
 * @property      \Cake\View\Helper\SessionHelper $Session
 * @property      \Cake\View\Helper\TextHelper $Text
 * @property      \Cake\View\Helper\TimeHelper $Time
 * @property      \Cake\View\ViewBlock $Blocks
 */
class View
{

    use CellTrait;
    use EventManagerTrait;
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
    public $Blocks;

    /**
     * The name of the plugin.
     *
     * @var string
     */
    public $plugin = null;

    /**
     * Name of the controller that created the View if any.
     *
     * @var string
     */
    public $name = null;

    /**
     * Current passed params. Passed to View from the creating Controller for convenience.
     *
     * @var array
     */
    public $passedArgs = [];

    /**
     * An array of names of built-in helpers to include.
     *
     * @var mixed
     */
    public $helpers = [];

    /**
     * The name of the views subfolder containing views for this View.
     *
     * @var string
     */
    public $viewPath = null;

    /**
     * The name of the view file to render. The name specified
     * is the filename in /app/Template/<SubFolder> without the .ctp extension.
     *
     * @var string
     */
    public $view = null;

    /**
     * The name of the layout file to render the view inside of. The name specified
     * is the filename of the layout in /app/Template/Layout without the .ctp
     * extension.
     *
     * @var string
     */
    public $layout = 'default';

    /**
     * The name of the layouts subfolder containing layouts for this View.
     *
     * @var string
     */
    public $layoutPath = null;

    /**
     * Turns on or off CakePHP's conventional mode of applying layout files. On by default.
     * Setting to off means that layouts will not be automatically applied to rendered views.
     *
     * @var bool
     */
    public $autoLayout = true;

    /**
     * File extension. Defaults to CakePHP's template ".ctp".
     *
     * @var string
     */
    protected $_ext = '.ctp';

    /**
     * Sub-directory for this view file. This is often used for extension based routing.
     * Eg. With an `xml` extension, $subDir would be `xml/`
     *
     * @var string
     */
    public $subDir = null;

    /**
     * The view theme to use.
     *
     * @var string
     */
    public $theme = null;

    /**
     * True when the view has been rendered.
     *
     * @var bool
     */
    public $hasRendered = false;

    /**
     * List of generated DOM UUIDs.
     *
     * @var array
     */
    public $uuids = [];

    /**
     * An instance of a Cake\Network\Request object that contains information about the current request.
     * This object contains all the information about a request and several methods for reading
     * additional information about the request.
     *
     * @var \Cake\Network\Request
     */
    public $request;

    /**
     * Reference to the Response object
     *
     * @var \Cake\Network\Response
     */
    public $response;

    /**
     * The Cache configuration View will use to store cached elements. Changing this will change
     * the default configuration elements are stored under. You can also choose a cache config
     * per element.
     *
     * @var string
     * @see View::element()
     */
    public $elementCache = 'default';

    /**
     * List of variables to collect from the associated controller.
     *
     * @var array
     */
    protected $_passedVars = [
        'viewVars', 'autoLayout', 'helpers', 'view', 'layout', 'name', 'theme',
        'layoutPath', 'viewPath', 'plugin', 'passedArgs'
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
    protected $_current = null;

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
     * Constant for view file type 'view'
     *
     * @var string
     */
    const TYPE_VIEW = 'view';

    /**
     * Constant for view file type 'element'
     *
     * @var string
     */
    const TYPE_ELEMENT = 'element';

    /**
     * Constant for view file type 'layout'
     *
     * @var string
     */
    const TYPE_LAYOUT = 'layout';

    /**
     * Constructor
     *
     * @param \Cake\Network\Request|null $request Request instance.
     * @param \Cake\Network\Response|null $response Response instance.
     * @param \Cake\Event\EventManager|null $eventManager Event manager instance.
     * @param array $viewOptions View options. See View::$_passedVars for list of
     *   options which get set as class properties.
     */
    public function __construct(
        Request $request = null,
        Response $response = null,
        EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        foreach ($this->_passedVars as $var) {
            if (isset($viewOptions[$var])) {
                $this->{$var} = $viewOptions[$var];
            }
        }
        $this->eventManager($eventManager);
        $this->request = $request;
        $this->response = $response;
        if (empty($this->request)) {
            $this->request = Router::getRequest(true);
        }
        if (empty($this->request)) {
            $this->request = new Request();
            $this->request->base = '';
            $this->request->here = $this->request->webroot = '/';
        }
        if (empty($this->response)) {
            $this->response = new Response();
        }
        $this->Blocks = new ViewBlock();
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
     * Renders a piece of PHP with provided parameters and returns HTML, XML, or any other string.
     *
     * This realizes the concept of Elements, (or "partial layouts") and the $params array is used to send
     * data to be used in the element. Elements can be cached improving performance by using the `cache` option.
     *
     * @param string $name Name of template file in the/app/Template/Element/ folder,
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
     * @return string Rendered Element
     * @throws \Cake\View\Exception\MissingElementException When an element is missing and `ignoreMissing`
     *   is false.
     */
    public function element($name, array $data = [], array $options = [])
    {
        $options += ['callbacks' => false, 'cache' => null];
        if (isset($options['cache'])) {
            $options['cache'] = $this->_elementCache($name, $data, $options);
        }

        $file = $this->_getElementFilename($name);
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
            $name = str_replace('/', DS, $name);
            $file = $plugin . 'Element' . DS . $name . $this->_ext;
            throw new Exception\MissingElementException($file);
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
     * @param string $name Name of template file in the /app/Template/Element/ folder,
     *   or `MyPlugin.template` to check the template element from MyPlugin. If the element
     *   is not found in the plugin, the normal view path cascade will be searched.
     * @return bool Success
     */
    public function elementExists($name)
    {
        return (bool)$this->_getElementFilename($name);
    }

    /**
     * Renders view for given view file and layout.
     *
     * Render triggers helper callbacks, which are fired before and after the view are rendered,
     * as well as before and after the layout. The helper callbacks are called:
     *
     * - `beforeRender`
     * - `afterRender`
     * - `beforeLayout`
     * - `afterLayout`
     *
     * If View::$autoRender is false and no `$layout` is provided, the view will be returned bare.
     *
     * View and layout names can point to plugin views/layouts. Using the `Plugin.view` syntax
     * a plugin view/layout can be used instead of the app ones. If the chosen plugin is not found
     * the view will be located along the regular view path cascade.
     *
     * @param string|null $view Name of view file to use
     * @param string|null $layout Layout to use.
     * @return string|void Rendered content or null if content already rendered and returned earlier.
     * @throws \Cake\Core\Exception\Exception If there is an error in the view.
     * @triggers View.beforeRender $this, [$viewFileName]
     * @triggers View.afterRender $this, [$viewFileName]
     */
    public function render($view = null, $layout = null)
    {
        if ($this->hasRendered) {
            return;
        }

        if ($view !== false && $viewFileName = $this->_getViewFileName($view)) {
            $this->_currentType = static::TYPE_VIEW;
            $this->dispatchEvent('View.beforeRender', [$viewFileName]);
            $this->Blocks->set('content', $this->_render($viewFileName));
            $this->dispatchEvent('View.afterRender', [$viewFileName]);
        }

        if ($layout === null) {
            $layout = $this->layout;
        }
        if ($layout && $this->autoLayout) {
            $this->Blocks->set('content', $this->renderLayout('', $layout));
        }
        $this->hasRendered = true;
        return $this->Blocks->get('content');
    }

    /**
     * Renders a layout. Returns output from _render(). Returns false on error.
     * Several variables are created for use in layout.
     *
     * @param string $content Content to render in a view, wrapped by the surrounding layout.
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

        if (empty($content)) {
            $content = $this->Blocks->get('content');
        } else {
            $this->Blocks->set('content', $content);
        }
        $this->dispatchEvent('View.beforeLayout', [$layoutFileName]);

        $title = $this->Blocks->get('title');
        if ($title === '') {
            $title = Inflector::humanize($this->viewPath);
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
     * @return array Array of the set view variable names.
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
     * @see ViewBlock::keys()
     */
    public function blocks()
    {
        return $this->Blocks->keys();
    }

    /**
     * Start capturing output for a 'block'
     *
     * @param string $name The name of the block to capture for.
     * @return void
     * @see ViewBlock::start()
     */
    public function start($name)
    {
        $this->Blocks->start($name);
    }

    /**
     * Append to an existing or new block.
     *
     * Appending to a new block will create the block.
     *
     * @param string $name Name of the block
     * @param mixed $value The content for the block.
     * @return void
     * @see ViewBlock::concat()
     */
    public function append($name, $value = null)
    {
        if ($value !== null) {
            $this->Blocks->concat($name, $value);
            return;
        }
        $this->Blocks->start($name);
        echo $this->Blocks->get($name);
    }

    /**
     * Prepend to an existing or new block.
     *
     * Prepending to a new block will create the block.
     *
     * @param string $name Name of the block
     * @param mixed $value The content for the block.
     * @return void
     * @see ViewBlock::concat()
     */
    public function prepend($name, $value)
    {
        $this->Blocks->concat($name, $value, ViewBlock::PREPEND);
    }

    /**
     * Set the content for a block. This will overwrite any
     * existing content.
     *
     * @param string $name Name of the block
     * @param mixed $value The content for the block.
     * @return void
     * @see ViewBlock::set()
     */
    public function assign($name, $value)
    {
        $this->Blocks->set($name, $value);
    }

    /**
     * Fetch the content for a block. If a block is
     * empty or undefined '' will be returned.
     *
     * @param string $name Name of the block
     * @param string $default Default text
     * @return string default The block content or $default if the block does not exist.
     * @see ViewBlock::get()
     */
    public function fetch($name, $default = '')
    {
        return $this->Blocks->get($name, $default);
    }

    /**
     * End a capturing block. The compliment to View::start()
     *
     * @return void
     * @see ViewBlock::end()
     */
    public function end()
    {
        $this->Blocks->end();
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
     * Provides view or element extension/inheritance. Views can extends a
     * parent view and populate blocks in the parent template.
     *
     * @param string $name The view or element to 'extend' the current one with.
     * @return void
     * @throws \LogicException when you extend a view with itself or make extend loops.
     * @throws \LogicException when you extend an element which doesn't exist
     */
    public function extend($name)
    {
        if ($name[0] === '/' || $this->_currentType === static::TYPE_VIEW) {
            $parent = $this->_getViewFileName($name);
        } else {
            switch ($this->_currentType) {
                case static::TYPE_ELEMENT:
                    $parent = $this->_getElementFileName($name);
                    if (!$parent) {
                        list($plugin, $name) = $this->pluginSplit($name);
                        $paths = $this->_paths($plugin);
                        $defaultPath = $paths[0] . 'Element' . DS;
                        throw new \LogicException(sprintf(
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
            throw new \LogicException('You cannot have views extend themselves.');
        }
        if (isset($this->_parents[$parent]) && $this->_parents[$parent] == $this->_current) {
            throw new \LogicException('You cannot have views extend in a loop.');
        }
        $this->_parents[$this->_current] = $parent;
    }

    /**
     * Generates a unique, non-random DOM ID for an object, based on the object type and the target URL.
     *
     * @param string $object Type of object, i.e. 'form' or 'link'
     * @param string $url The object's target URL
     * @return string
     */
    public function uuid($object, $url)
    {
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
        $registry = $this->helpers();
        if (isset($registry->{$name})) {
            $this->{$name} = $registry->{$name};
            return $registry->{$name};
        }
        return $this->{$name};
    }

    /**
     * Interact with the HelperRegistry to load all the helpers.
     *
     * @return void
     */
    public function loadHelpers()
    {
        $registry = $this->helpers();
        $helpers = $registry->normalizeArray($this->helpers);
        foreach ($helpers as $properties) {
            $this->loadHelper($properties['class'], $properties['config']);
        }
    }

    /**
     * Renders and returns output for given view filename with its
     * array of data. Handles parent/extended views.
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
        if (isset($afterEvent->result)) {
            $content = $afterEvent->result;
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
     *    If empty the current View::$viewVars will be used.
     * @return string Rendered output
     */
    protected function _evaluate($viewFile, $dataForView)
    {
        $this->__viewFile = $viewFile;
        extract($dataForView);
        ob_start();

        include $this->__viewFile;

        unset($this->__viewFile);
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
     * @return Helper a constructed helper object.
     * @see HelperRegistry::load()
     */
    public function loadHelper($name, array $config = [])
    {
        list(, $class) = pluginSplit($name);
        $helpers = $this->helpers();
        return $this->{$class} = $helpers->load($name, $config);
    }

    /**
     * Returns filename of given action's template file (.ctp) as a string.
     * CamelCased action names will be under_scored! This means that you can have
     * LongActionNames that refer to long_action_names.ctp views.
     *
     * @param string|null $name Controller action to find template filename for
     * @return string Template filename
     * @throws \Cake\View\Exception\MissingTemplateException when a view file could not be found.
     */
    protected function _getViewFileName($name = null)
    {
        $viewPath = $subDir = '';

        if ($this->subDir !== null) {
            $subDir = $this->subDir . DS;
        }
        if ($this->viewPath) {
            $viewPath = $this->viewPath . DS;
        }

        if ($name === null) {
            $name = $this->view;
        }

        list($plugin, $name) = $this->pluginSplit($name);
        $name = str_replace('/', DS, $name);

        if (strpos($name, DS) === false && $name[0] !== '.') {
            $name = $viewPath . $subDir . Inflector::underscore($name);
        } elseif (strpos($name, DS) !== false) {
            if ($name[0] === DS || $name[1] === ':') {
                if (is_file($name)) {
                    return $name;
                }
                $name = trim($name, DS);
            } elseif (!$plugin || $this->viewPath !== $this->name) {
                $name = $viewPath . $subDir . $name;
            } else {
                $name = DS . $subDir . $name;
            }
        }

        foreach ($this->_paths($plugin) as $path) {
            if (file_exists($path . $name . $this->_ext)) {
                return $this->_checkFilePath($path . $name . $this->_ext, $path);
            }
        }
        throw new Exception\MissingTemplateException(['file' => $name . $this->_ext]);
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
        if (Plugin::loaded($first) === true) {
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

        if ($this->layoutPath !== null) {
            $subDir = $this->layoutPath . DS;
        }
        list($plugin, $name) = $this->pluginSplit($name);

        $layoutPaths = $this->_getSubPaths('Layout' . DS . $subDir);

        foreach ($this->_paths($plugin) as $path) {
            foreach ($layoutPaths as $layoutPath) {
                $currentPath = $path . $layoutPath;
                if (file_exists($currentPath . $name . $this->_ext)) {
                    return $this->_checkFilePath($currentPath . $name . $this->_ext, $currentPath);
                }
            }
        }
        throw new Exception\MissingLayoutException([
            'file' => $layoutPaths[0] . $name . $this->_ext
        ]);
    }

    /**
     * Finds an element filename, returns false on failure.
     *
     * @param string $name The name of the element to find.
     * @return mixed Either a string to the element filename or false when one can't be found.
     */
    protected function _getElementFileName($name)
    {
        list($plugin, $name) = $this->pluginSplit($name);

        $paths = $this->_paths($plugin);
        $elementPaths = $this->_getSubPaths('Element');

        foreach ($paths as $path) {
            foreach ($elementPaths as $elementPath) {
                if (file_exists($path . $elementPath . DS . $name . $this->_ext)) {
                    return $path . $elementPath . DS . $name . $this->_ext;
                }
            }
        }
        return false;
    }

    /**
     * Find all sub templates path, based on $basePath
     * If a prefix is defined in the current request, this method will prepend
     * the prefixed template path to the $basePath.
     * This is essentially used to find prefixed template paths for elements
     * and layouts.
     *
     * @param string $basePath Base path on which to get the prefixed one.
     * @return array Array with all the templates paths.
     */
    protected function _getSubPaths($basePath)
    {
        $paths = [$basePath];
        if (!empty($this->request->params['prefix'])) {
            $prefixPath = array_map(
                'Cake\Utility\Inflector::camelize',
                explode('/', $this->request->params['prefix'])
            );
            array_unshift(
                $paths,
                implode('/', $prefixPath) . DS . $basePath
            );
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
        $viewPaths = App::path('Template');
        $pluginPaths = $themePaths = [];
        if (!empty($plugin)) {
            for ($i = 0, $count = count($viewPaths); $i < $count; $i++) {
                $pluginPaths[] = $viewPaths[$i] . 'Plugin' . DS . $plugin . DS;
            }
            $pluginPaths = array_merge($pluginPaths, App::path('Template', $plugin));
        }

        if (!empty($this->theme)) {
            $themePaths = App::path('Template', Inflector::camelize($this->theme));

            if ($plugin) {
                for ($i = 0, $count = count($viewPaths); $i < $count; $i++) {
                    array_unshift($themePaths, $themePaths[$i] . 'Plugin' . DS . $plugin . DS);
                }
            }
        }

        $paths = array_merge(
            $themePaths,
            $pluginPaths,
            $viewPaths,
            [dirname(__DIR__) . DS . 'Template' . DS]
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
        $plugin = null;
        list($plugin, $name) = $this->pluginSplit($name);

        $underscored = null;
        if ($plugin) {
            $underscored = Inflector::underscore($plugin);
        }
        $keys = array_merge(
            [$underscored, $name],
            array_keys($options),
            array_keys($data)
        );
        $config = [
            'config' => $this->elementCache,
            'key' => implode('_', $keys)
        ];
        if (is_array($options['cache'])) {
            $defaults = [
                'config' => $this->elementCache,
                'key' => $config['key']
            ];
            $config = $options['cache'] + $defaults;
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
