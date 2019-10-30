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
 * @since         0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller;

use Cake\Controller\Exception\MissingActionException;
use Cake\Datasource\ModelAwareTrait;
use Cake\Event\Event;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\RequestActionTrait;
use Cake\Routing\Router;
use Cake\Utility\MergeVariablesTrait;
use Cake\View\ViewVarsTrait;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;

/**
 * Application controller class for organization of business logic.
 * Provides basic functionality, such as rendering views inside layouts,
 * automatic model availability, redirection, callbacks, and more.
 *
 * Controllers should provide a number of 'action' methods. These are public
 * methods on a controller that are not inherited from `Controller`.
 * Each action serves as an endpoint for performing a specific action on a
 * resource or collection of resources. For example adding or editing a new
 * object, or listing a set of objects.
 *
 * You can access request parameters, using `$this->request`. The request object
 * contains all the POST, GET and FILES that were part of the request.
 *
 * After performing the required action, controllers are responsible for
 * creating a response. This usually takes the form of a generated `View`, or
 * possibly a redirection to another URL. In either case `$this->response`
 * allows you to manipulate all aspects of the response.
 *
 * Controllers are created by `Dispatcher` based on request parameters and
 * routing. By default controllers and actions use conventional names.
 * For example `/posts/index` maps to `PostsController::index()`. You can re-map
 * URLs using Router::connect() or RouterBuilder::connect().
 *
 * ### Life cycle callbacks
 *
 * CakePHP fires a number of life cycle callbacks during each request.
 * By implementing a method you can receive the related events. The available
 * callbacks are:
 *
 * - `beforeFilter(Event $event)`
 *   Called before each action. This is a good place to do general logic that
 *   applies to all actions.
 * - `beforeRender(Event $event)`
 *   Called before the view is rendered.
 * - `beforeRedirect(Event $event, $url, Response $response)`
 *    Called before a redirect is done.
 * - `afterFilter(Event $event)`
 *   Called after each action is complete and after the view is rendered.
 *
 * @property \Cake\Controller\Component\AuthComponent $Auth
 * @property \Cake\Controller\Component\CookieComponent $Cookie
 * @property \Cake\Controller\Component\CsrfComponent $Csrf
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \Cake\Controller\Component\PaginatorComponent $Paginator
 * @property \Cake\Controller\Component\RequestHandlerComponent $RequestHandler
 * @property \Cake\Controller\Component\SecurityComponent $Security
 * @method bool isAuthorized($user)
 * @link https://book.cakephp.org/3/en/controllers.html
 */
class Controller implements EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;
    use LocatorAwareTrait;
    use LogTrait;
    use MergeVariablesTrait;
    use ModelAwareTrait;
    use RequestActionTrait;
    use ViewVarsTrait;

    /**
     * The name of this controller. Controller names are plural, named after the model they manipulate.
     *
     * Set automatically using conventions in Controller::__construct().
     *
     * @var string
     */
    protected $name;

    /**
     * An array containing the names of helpers this controller uses. The array elements should
     * not contain the "Helper" part of the class name.
     *
     * Example:
     * ```
     * public $helpers = ['Form', 'Html', 'Time'];
     * ```
     *
     * @var array
     * @link https://book.cakephp.org/3/en/controllers.html#configuring-helpers-to-load
     *
     * @deprecated 3.0.0 You should configure helpers in your AppView::initialize() method.
     */
    public $helpers = [];

    /**
     * An instance of a \Cake\Http\ServerRequest object that contains information about the current request.
     * This object contains all the information about a request and several methods for reading
     * additional information about the request.
     *
     * Deprecated 3.6.0: The property will become protected in 4.0.0. Use getRequest()/setRequest instead.
     *
     * @var \Cake\Http\ServerRequest
     * @link https://book.cakephp.org/3/en/controllers/request-response.html#request
     */
    public $request;

    /**
     * An instance of a Response object that contains information about the impending response
     *
     * Deprecated 3.6.0: The property will become protected in 4.0.0. Use getResponse()/setResponse instead.

     * @var \Cake\Http\Response
     * @link https://book.cakephp.org/3/en/controllers/request-response.html#response
     */
    public $response;

    /**
     * The class name to use for creating the response object.
     *
     * @var string
     */
    protected $_responseClass = Response::class;

    /**
     * Settings for pagination.
     *
     * Used to pre-configure pagination preferences for the various
     * tables your controller will be paginating.
     *
     * @var array
     * @see \Cake\Controller\Component\PaginatorComponent
     */
    public $paginate = [];

    /**
     * Set to true to automatically render the view
     * after action logic.
     *
     * @var bool
     */
    protected $autoRender = true;

    /**
     * Instance of ComponentRegistry used to create Components
     *
     * @var \Cake\Controller\ComponentRegistry
     */
    protected $_components;

    /**
     * Array containing the names of components this controller uses. Component names
     * should not contain the "Component" portion of the class name.
     *
     * Example:
     * ```
     * public $components = ['RequestHandler', 'Acl'];
     * ```
     *
     * @var array
     * @link https://book.cakephp.org/3/en/controllers/components.html
     *
     * @deprecated 3.0.0 You should configure components in your Controller::initialize() method.
     */
    public $components = [];

    /**
     * Instance of the View created during rendering. Won't be set until after
     * Controller::render() is called.
     *
     * @var \Cake\View\View
     * @deprecated 3.1.0 Use viewBuilder() instead.
     */
    public $View;

    /**
     * These Controller properties will be passed from the Controller to the View as options.
     *
     * @var array
     * @see \Cake\View\View
     * @deprecated 3.7.0 Use ViewBuilder::setOptions() or any one of it's setter methods instead.
     */
    protected $_validViewOptions = [
        'passedArgs'
    ];

    /**
     * Automatically set to the name of a plugin.
     *
     * @var string|null
     */
    protected $plugin;

    /**
     * Holds all passed params.
     *
     * @var array
     * @deprecated 3.1.0 Use `$this->request->getParam('pass')` instead.
     */
    public $passedArgs = [];

    /**
     * Constructor.
     *
     * Sets a number of properties based on conventions if they are empty. To override the
     * conventions CakePHP uses you can define properties in your class declaration.
     *
     * @param \Cake\Http\ServerRequest|null $request Request object for this controller. Can be null for testing,
     *   but expect that features that use the request parameters will not work.
     * @param \Cake\Http\Response|null $response Response object for this controller.
     * @param string|null $name Override the name useful in testing when using mocks.
     * @param \Cake\Event\EventManager|null $eventManager The event manager. Defaults to a new instance.
     * @param \Cake\Controller\ComponentRegistry|null $components The component registry. Defaults to a new instance.
     */
    public function __construct(ServerRequest $request = null, Response $response = null, $name = null, $eventManager = null, $components = null)
    {
        if ($name !== null) {
            $this->name = $name;
        }

        if ($this->name === null && $request && $request->getParam('controller')) {
            $this->name = $request->getParam('controller');
        }

        if ($this->name === null) {
            list(, $name) = namespaceSplit(get_class($this));
            $this->name = substr($name, 0, -10);
        }

        $this->setRequest($request ?: new ServerRequest());
        $this->response = $response ?: new Response();

        if ($eventManager !== null) {
            $this->setEventManager($eventManager);
        }

        $this->modelFactory('Table', [$this->getTableLocator(), 'get']);
        $plugin = $this->request->getParam('plugin');
        $modelClass = ($plugin ? $plugin . '.' : '') . $this->name;
        $this->_setModelClass($modelClass);

        if ($components !== null) {
            $this->components($components);
        }

        $this->initialize();

        $this->_mergeControllerVars();
        $this->_loadComponents();
        $this->getEventManager()->on($this);
    }

    /**
     * Initialization hook method.
     *
     * Implement this method to avoid having to overwrite
     * the constructor and call parent.
     *
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * Get the component registry for this controller.
     *
     * If called with the first parameter, it will be set as the controller $this->_components property
     *
     * @param \Cake\Controller\ComponentRegistry|null $components Component registry.
     *
     * @return \Cake\Controller\ComponentRegistry
     */
    public function components($components = null)
    {
        if ($components === null && $this->_components === null) {
            $this->_components = new ComponentRegistry($this);
        }
        if ($components !== null) {
            $components->setController($this);
            $this->_components = $components;
        }

        return $this->_components;
    }

    /**
     * Add a component to the controller's registry.
     *
     * This method will also set the component to a property.
     * For example:
     *
     * ```
     * $this->loadComponent('Acl.Acl');
     * ```
     *
     * Will result in a `Toolbar` property being set.
     *
     * @param string $name The name of the component to load.
     * @param array $config The config for the component.
     * @return \Cake\Controller\Component
     * @throws \Exception
     */
    public function loadComponent($name, array $config = [])
    {
        list(, $prop) = pluginSplit($name);

        return $this->{$prop} = $this->components()->load($name, $config);
    }

    /**
     * Magic accessor for model autoloading.
     *
     * @param string $name Property name
     * @return bool|object The model instance or false
     */
    public function __get($name)
    {
        $deprecated = [
            'name' => 'getName',
            'plugin' => 'getPlugin',
            'autoRender' => 'isAutoRenderEnabled',
        ];
        if (isset($deprecated[$name])) {
            $method = $deprecated[$name];
            deprecationWarning(sprintf('Controller::$%s is deprecated. Use $this->%s() instead.', $name, $method));

            return $this->{$method}();
        }

        $deprecated = [
            'layout' => 'getLayout',
            'view' => 'getTemplate',
            'theme' => 'getTheme',
            'autoLayout' => 'isAutoLayoutEnabled',
            'viewPath' => 'getTemplatePath',
            'layoutPath' => 'getLayoutPath',
        ];
        if (isset($deprecated[$name])) {
            $method = $deprecated[$name];
            deprecationWarning(sprintf('Controller::$%s is deprecated. Use $this->viewBuilder()->%s() instead.', $name, $method));

            return $this->viewBuilder()->{$method}();
        }

        list($plugin, $class) = pluginSplit($this->modelClass, true);
        if ($class === $name) {
            return $this->loadModel($plugin . $class);
        }

        $trace = debug_backtrace();
        $parts = explode('\\', get_class($this));
        trigger_error(
            sprintf(
                'Undefined property: %s::$%s in %s on line %s',
                array_pop($parts),
                $name,
                $trace[0]['file'],
                $trace[0]['line']
            ),
            E_USER_NOTICE
        );

        return false;
    }

    /**
     * Magic setter for removed properties.
     *
     * @param string $name Property name.
     * @param mixed $value Value to set.
     * @return void
     */
    public function __set($name, $value)
    {
        $deprecated = [
            'name' => 'setName',
            'plugin' => 'setPlugin'
        ];
        if (isset($deprecated[$name])) {
            $method = $deprecated[$name];
            deprecationWarning(sprintf('Controller::$%s is deprecated. Use $this->%s() instead.', $name, $method));
            $this->{$method}($value);

            return;
        }
        if ($name === 'autoRender') {
            $value ? $this->enableAutoRender() : $this->disableAutoRender();
            deprecationWarning(sprintf('Controller::$%s is deprecated. Use $this->enableAutoRender/disableAutoRender() instead.', $name));

            return;
        }
        $deprecated = [
            'layout' => 'setLayout',
            'view' => 'setTemplate',
            'theme' => 'setTheme',
            'autoLayout' => 'enableAutoLayout',
            'viewPath' => 'setTemplatePath',
            'layoutPath' => 'setLayoutPath',
        ];
        if (isset($deprecated[$name])) {
            $method = $deprecated[$name];
            deprecationWarning(sprintf('Controller::$%s is deprecated. Use $this->viewBuilder()->%s() instead.', $name, $method));

            $this->viewBuilder()->{$method}($value);

            return;
        }

        $this->{$name} = $value;
    }

    /**
     * Returns the controller name.
     *
     * @return string
     * @since 3.6.0
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the controller name.
     *
     * @param string $name Controller name.
     * @return $this
     * @since 3.6.0
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the plugin name.
     *
     * @return string|null
     * @since 3.6.0
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
     * @since 3.6.0
     */
    public function setPlugin($name)
    {
        $this->plugin = $name;

        return $this;
    }

    /**
     * Returns true if an action should be rendered automatically.
     *
     * @return bool
     * @since 3.6.0
     */
    public function isAutoRenderEnabled()
    {
        return $this->autoRender;
    }

    /**
     * Enable automatic action rendering.
     *
     * @return $this
     * @since 3.6.0
     */
    public function enableAutoRender()
    {
        $this->autoRender = true;

        return $this;
    }

    /**
     * Disable automatic action rendering.
     *
     * @return $this
     * @since 3.6.0
     */
    public function disableAutoRender()
    {
        $this->autoRender = false;

        return $this;
    }

    /**
     * Gets the request instance.
     *
     * @return \Cake\Http\ServerRequest
     * @since 3.6.0
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Sets the request objects and configures a number of controller properties
     * based on the contents of the request. Controller acts as a proxy for certain View variables
     * which must also be updated here. The properties that get set are:
     *
     * - $this->request - To the $request parameter
     * - $this->passedArgs - Same as $request->params['pass]
     *
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @return $this
     */
    public function setRequest(ServerRequest $request)
    {
        $this->request = $request;
        $this->plugin = $request->getParam('plugin') ?: null;

        if ($request->getParam('pass')) {
            $this->passedArgs = $request->getParam('pass');
        }

        return $this;
    }

    /**
     * Gets the response instance.
     *
     * @return \Cake\Http\Response
     * @since 3.6.0
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
     * @since 3.6.0
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Dispatches the controller action. Checks that the action
     * exists and isn't private.
     *
     * @return mixed The resulting response.
     * @throws \ReflectionException
     */
    public function invokeAction()
    {
        $request = $this->request;
        if (!$request) {
            throw new LogicException('No Request object configured. Cannot invoke action');
        }
        if (!$this->isAction($request->getParam('action'))) {
            throw new MissingActionException([
                'controller' => $this->name . 'Controller',
                'action' => $request->getParam('action'),
                'prefix' => $request->getParam('prefix') ?: '',
                'plugin' => $request->getParam('plugin'),
            ]);
        }
        /* @var callable $callable */
        $callable = [$this, $request->getParam('action')];

        $result = $callable(...array_values($request->getParam('pass')));
        if ($result instanceof Response) {
            $this->response = $result;
        }

        return $result;
    }

    /**
     * Merge components, helpers vars from
     * parent classes.
     *
     * @return void
     */
    protected function _mergeControllerVars()
    {
        $this->_mergeVars(
            ['components', 'helpers'],
            ['associative' => ['components', 'helpers']]
        );
    }

    /**
     * Returns a list of all events that will fire in the controller during its lifecycle.
     * You can override this function to add your own listener callbacks
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Controller.initialize' => 'beforeFilter',
            'Controller.beforeRender' => 'beforeRender',
            'Controller.beforeRedirect' => 'beforeRedirect',
            'Controller.shutdown' => 'afterFilter',
        ];
    }

    /**
     * Loads the defined components using the Component factory.
     *
     * @return void
     */
    protected function _loadComponents()
    {
        if (empty($this->components)) {
            return;
        }
        $registry = $this->components();
        $components = $registry->normalizeArray($this->components);
        foreach ($components as $properties) {
            $this->loadComponent($properties['class'], $properties['config']);
        }
    }

    /**
     * Perform the startup process for this controller.
     * Fire the Components and Controller callbacks in the correct order.
     *
     * - Initializes components, which fires their `initialize` callback
     * - Calls the controller `beforeFilter`.
     * - triggers Component `startup` methods.
     *
     * @return \Cake\Http\Response|null
     */
    public function startupProcess()
    {
        $event = $this->dispatchEvent('Controller.initialize');
        if ($event->getResult() instanceof Response) {
            return $event->getResult();
        }
        $event = $this->dispatchEvent('Controller.startup');
        if ($event->getResult() instanceof Response) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Perform the various shutdown processes for this controller.
     * Fire the Components and Controller callbacks in the correct order.
     *
     * - triggers the component `shutdown` callback.
     * - calls the Controller's `afterFilter` method.
     *
     * @return \Cake\Http\Response|null
     */
    public function shutdownProcess()
    {
        $event = $this->dispatchEvent('Controller.shutdown');
        if ($event->getResult() instanceof Response) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Redirects to given $url, after turning off $this->autoRender.
     *
     * @param string|array $url A string or array-based URL pointing to another location within the app,
     *     or an absolute URL
     * @param int $status HTTP status code (eg: 301)
     * @return \Cake\Http\Response|null
     * @link https://book.cakephp.org/3/en/controllers.html#Controller::redirect
     */
    public function redirect($url, $status = 302)
    {
        $this->autoRender = false;

        if ($status) {
            $this->response = $this->response->withStatus($status);
        }

        $event = $this->dispatchEvent('Controller.beforeRedirect', [$url, $this->response]);
        if ($event->getResult() instanceof Response) {
            return $this->response = $event->getResult();
        }
        if ($event->isStopped()) {
            return null;
        }
        $response = $this->response;

        if (!$response->getHeaderLine('Location')) {
            $response = $response->withLocation(Router::url($url, true));
        }

        return $this->response = $response;
    }

    /**
     * Internally redirects one action to another. Does not perform another HTTP request unlike Controller::redirect()
     *
     * Examples:
     *
     * ```
     * setAction('another_action');
     * setAction('action_with_parameters', $parameter1);
     * ```
     *
     * @param string $action The new action to be 'redirected' to.
     *   Any other parameters passed to this method will be passed as parameters to the new action.
     * @param array ...$args Arguments passed to the action
     * @return mixed Returns the return value of the called action
     */
    public function setAction($action, ...$args)
    {
        $this->setRequest($this->request->withParam('action', $action));

        return $this->$action(...$args);
    }

    /**
     * Instantiates the correct view class, hands it its data, and uses it to render the view output.
     *
     * @param string|null $view View to use for rendering
     * @param string|null $layout Layout to use
     * @return \Cake\Http\Response A response object containing the rendered view.
     * @link https://book.cakephp.org/3/en/controllers.html#rendering-a-view
     */
    public function render($view = null, $layout = null)
    {
        $builder = $this->viewBuilder();
        if (!$builder->getTemplatePath()) {
            $builder->setTemplatePath($this->_viewPath());
        }

        if ($this->request->getParam('bare')) {
            $builder->disableAutoLayout();
        }
        $this->autoRender = false;

        $event = $this->dispatchEvent('Controller.beforeRender');
        if ($event->getResult() instanceof Response) {
            return $event->getResult();
        }
        if ($event->isStopped()) {
            return $this->response;
        }

        if ($builder->getTemplate() === null && $this->request->getParam('action')) {
            $builder->setTemplate($this->request->getParam('action'));
        }

        $this->View = $this->createView();
        $contents = $this->View->render($view, $layout);
        $this->setResponse($this->View->getResponse()->withStringBody($contents));

        return $this->response;
    }

    /**
     * Get the viewPath based on controller name and request prefix.
     *
     * @return string
     */
    protected function _viewPath()
    {
        $viewPath = $this->name;
        if ($this->request->getParam('prefix')) {
            $prefixes = array_map(
                'Cake\Utility\Inflector::camelize',
                explode('/', $this->request->getParam('prefix'))
            );
            $viewPath = implode(DIRECTORY_SEPARATOR, $prefixes) . DIRECTORY_SEPARATOR . $viewPath;
        }

        return $viewPath;
    }

    /**
     * Returns the referring URL for this request.
     *
     * @param string|array|null $default Default URL to use if HTTP_REFERER cannot be read from headers
     * @param bool $local If true, restrict referring URLs to local server
     * @return string Referring URL
     */
    public function referer($default = null, $local = false)
    {
        if (!$this->request) {
            return Router::url($default, !$local);
        }

        $referer = $this->request->referer($local);
        if ($referer === '/' && $default && $default !== $referer) {
            $url = Router::url($default, !$local);
            $base = $this->request->getAttribute('base');
            if ($local && $base && strpos($url, $base) === 0) {
                $url = substr($url, strlen($base));
                if ($url[0] !== '/') {
                    $url = '/' . $url;
                }

                return $url;
            }

            return $url;
        }

        return $referer;
    }

    /**
     * Handles pagination of records in Table objects.
     *
     * Will load the referenced Table object, and have the PaginatorComponent
     * paginate the query using the request date and settings defined in `$this->paginate`.
     *
     * This method will also make the PaginatorHelper available in the view.
     *
     * @param \Cake\ORM\Table|string|\Cake\ORM\Query|null $object Table to paginate
     * (e.g: Table instance, 'TableName' or a Query object)
     * @param array $settings The settings/configuration used for pagination.
     * @return \Cake\ORM\ResultSet|\Cake\Datasource\ResultSetInterface Query results
     * @link https://book.cakephp.org/3/en/controllers.html#paginating-a-model
     * @throws \RuntimeException When no compatible table object can be found.
     */
    public function paginate($object = null, array $settings = [])
    {
        if (is_object($object)) {
            $table = $object;
        }

        if (is_string($object) || $object === null) {
            $try = [$object, $this->modelClass];
            foreach ($try as $tableName) {
                if (empty($tableName)) {
                    continue;
                }
                $table = $this->loadModel($tableName);
                break;
            }
        }

        $this->loadComponent('Paginator');
        if (empty($table)) {
            throw new RuntimeException('Unable to locate an object compatible with paginate.');
        }
        $settings += $this->paginate;

        return $this->Paginator->paginate($table, $settings);
    }

    /**
     * Method to check that an action is accessible from a URL.
     *
     * Override this method to change which controller methods can be reached.
     * The default implementation disallows access to all methods defined on Cake\Controller\Controller,
     * and allows all public methods on all subclasses of this class.
     *
     * @param string $action The action to check.
     * @return bool Whether or not the method is accessible from a URL.
     * @throws \ReflectionException
     */
    public function isAction($action)
    {
        $baseClass = new ReflectionClass('Cake\Controller\Controller');
        if ($baseClass->hasMethod($action)) {
            return false;
        }
        try {
            $method = new ReflectionMethod($this, $action);
        } catch (ReflectionException $e) {
            return false;
        }

        return $method->isPublic();
    }

    /**
     * Called before the controller action. You can use this method to configure and customize components
     * or perform logic that needs to happen before each controller action.
     *
     * @param \Cake\Event\Event $event An Event instance
     * @return \Cake\Http\Response|null
     * @link https://book.cakephp.org/3/en/controllers.html#request-life-cycle-callbacks
     */
    public function beforeFilter(Event $event)
    {
        return null;
    }

    /**
     * Called after the controller action is run, but before the view is rendered. You can use this method
     * to perform logic or set view variables that are required on every request.
     *
     * @param \Cake\Event\Event $event An Event instance
     * @return \Cake\Http\Response|null
     * @link https://book.cakephp.org/3/en/controllers.html#request-life-cycle-callbacks
     */
    public function beforeRender(Event $event)
    {
        return null;
    }

    /**
     * The beforeRedirect method is invoked when the controller's redirect method is called but before any
     * further action.
     *
     * If the event is stopped the controller will not continue on to redirect the request.
     * The $url and $status variables have same meaning as for the controller's method.
     * You can set the event result to response instance or modify the redirect location
     * using controller's response instance.
     *
     * @param \Cake\Event\Event $event An Event instance
     * @param string|array $url A string or array-based URL pointing to another location within the app,
     *     or an absolute URL
     * @param \Cake\Http\Response $response The response object.
     * @return \Cake\Http\Response|null
     * @link https://book.cakephp.org/3/en/controllers.html#request-life-cycle-callbacks
     */
    public function beforeRedirect(Event $event, $url, Response $response)
    {
        return null;
    }

    /**
     * Called after the controller action is run and rendered.
     *
     * @param \Cake\Event\Event $event An Event instance
     * @return \Cake\Http\Response|null
     * @link https://book.cakephp.org/3/en/controllers.html#request-life-cycle-callbacks
     */
    public function afterFilter(Event $event)
    {
        return null;
    }
}
