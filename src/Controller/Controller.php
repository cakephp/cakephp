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
 * @since         0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller;

use Cake\Controller\Exception\MissingActionException;
use Cake\Core\App;
use Cake\Datasource\ModelAwareTrait;
use Cake\Datasource\Paging\Exception\PageOutOfBoundsException;
use Cake\Datasource\Paging\NumericPaginator;
use Cake\Datasource\Paging\PaginatorInterface;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManagerInterface;
use Cake\Http\ContentTypeNegotiation;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use Cake\View\View;
use Cake\View\ViewVarsTrait;
use Closure;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use UnexpectedValueException;
use function Cake\Core\deprecationWarning;
use function Cake\Core\getTypeName;
use function Cake\Core\namespaceSplit;
use function Cake\Core\pluginSplit;
use function Cake\Core\triggerWarning;

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
 * You can access request parameters, using `$this->getRequest()`. The request object
 * contains all the POST, GET and FILES that were part of the request.
 *
 * After performing the required action, controllers are responsible for
 * creating a response. This usually takes the form of a generated `View`, or
 * possibly a redirection to another URL. In either case `$this->getResponse()`
 * allows you to manipulate all aspects of the response.
 *
 * Controllers are created based on request parameters and
 * routing. By default controllers and actions use conventional names.
 * For example `/posts/index` maps to `PostsController::index()`. You can re-map
 * URLs using Router::connect() or RouteBuilder::connect().
 *
 * ### Life cycle callbacks
 *
 * CakePHP fires a number of life cycle callbacks during each request.
 * By implementing a method you can receive the related events. The available
 * callbacks are:
 *
 * - `beforeFilter(EventInterface $event)`
 *   Called before each action. This is a good place to do general logic that
 *   applies to all actions.
 * - `beforeRender(EventInterface $event)`
 *   Called before the view is rendered.
 * - `beforeRedirect(EventInterface $event, $url, Response $response)`
 *    Called before a redirect is done.
 * - `afterFilter(EventInterface $event)`
 *   Called after each action is complete and after the view is rendered.
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \Cake\Controller\Component\FormProtectionComponent $FormProtection
 * @property \Cake\Controller\Component\PaginatorComponent $Paginator
 * @property \Cake\Controller\Component\RequestHandlerComponent $RequestHandler
 * @property \Cake\Controller\Component\SecurityComponent $Security
 * @property \Cake\Controller\Component\AuthComponent $Auth
 * @property \Cake\Controller\Component\CheckHttpCacheComponent $CheckHttpCache
 * @link https://book.cakephp.org/4/en/controllers.html
 */
#[\AllowDynamicProperties]
class Controller implements EventListenerInterface, EventDispatcherInterface
{
    use EventDispatcherTrait;
    use LocatorAwareTrait;
    use LogTrait;
    use ModelAwareTrait;
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
     * An instance of a \Cake\Http\ServerRequest object that contains information about the current request.
     * This object contains all the information about a request and several methods for reading
     * additional information about the request.
     *
     * @var \Cake\Http\ServerRequest
     * @link https://book.cakephp.org/4/en/controllers/request-response.html#request
     */
    protected $request;

    /**
     * An instance of a Response object that contains information about the impending response
     *
     * @var \Cake\Http\Response
     * @link https://book.cakephp.org/4/en/controllers/request-response.html#response
     */
    protected $response;

    /**
     * Settings for pagination.
     *
     * Used to pre-configure pagination preferences for the various
     * tables your controller will be paginating.
     *
     * @var array
     * @see \Cake\Datasource\Paging\NumericPaginator
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
     * @var \Cake\Controller\ComponentRegistry|null
     */
    protected $_components;

    /**
     * Automatically set to the name of a plugin.
     *
     * @var string|null
     */
    protected $plugin;

    /**
     * Middlewares list.
     *
     * @var array
     * @psalm-var array<int, array{middleware:\Psr\Http\Server\MiddlewareInterface|\Closure|string, options:array{only?: array|string, except?: array|string}}>
     */
    protected $middlewares = [];

    /**
     * View classes for content negotiation.
     *
     * @var array<string>
     */
    protected $viewClasses = [];

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
     * @param \Cake\Event\EventManagerInterface|null $eventManager The event manager. Defaults to a new instance.
     * @param \Cake\Controller\ComponentRegistry|null $components The component registry. Defaults to a new instance.
     */
    public function __construct(
        ?ServerRequest $request = null,
        ?Response $response = null,
        ?string $name = null,
        ?EventManagerInterface $eventManager = null,
        ?ComponentRegistry $components = null
    ) {
        if ($name !== null) {
            $this->name = $name;
        } elseif ($this->name === null && $request) {
            $this->name = $request->getParam('controller');
        }

        if ($this->name === null) {
            [, $name] = namespaceSplit(static::class);
            $this->name = substr($name, 0, -10);
        }

        $this->setRequest($request ?: new ServerRequest());
        $this->response = $response ?: new Response();

        if ($eventManager !== null) {
            $this->setEventManager($eventManager);
        }

        $this->modelFactory('Table', [$this->getTableLocator(), 'get']);

        if ($this->defaultTable !== null) {
            $this->modelClass = $this->defaultTable;
        }

        if ($this->modelClass === null) {
            $plugin = $this->request->getParam('plugin');
            $modelClass = ($plugin ? $plugin . '.' : '') . $this->name;
            $this->_setModelClass($modelClass);

            $this->defaultTable = $modelClass;
        }

        if ($components !== null) {
            $this->components($components);
        }

        $this->initialize();

        if (isset($this->components)) {
            triggerWarning(
                'Support for loading components using $components property is removed. ' .
                'Use $this->loadComponent() instead in initialize().'
            );
        }

        if (isset($this->helpers)) {
            triggerWarning(
                'Support for loading helpers using $helpers property is removed. ' .
                'Use $this->viewBuilder()->setHelpers() instead.'
            );
        }

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
    public function initialize(): void
    {
    }

    /**
     * Get the component registry for this controller.
     *
     * If called with the first parameter, it will be set as the controller $this->_components property
     *
     * @param \Cake\Controller\ComponentRegistry|null $components Component registry.
     * @return \Cake\Controller\ComponentRegistry
     */
    public function components(?ComponentRegistry $components = null): ComponentRegistry
    {
        if ($components !== null) {
            $components->setController($this);

            return $this->_components = $components;
        }

        if ($this->_components === null) {
            $this->_components = new ComponentRegistry($this);
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
     * $this->loadComponent('Authentication.Authentication');
     * ```
     *
     * Will result in a `Authentication` property being set.
     *
     * @param string $name The name of the component to load.
     * @param array<string, mixed> $config The config for the component.
     * @return \Cake\Controller\Component
     * @throws \Exception
     */
    public function loadComponent(string $name, array $config = []): Component
    {
        [, $prop] = pluginSplit($name);

        return $this->{$prop} = $this->components()->load($name, $config);
    }

    /**
     * Magic accessor for model autoloading.
     *
     * @param string $name Property name
     * @return \Cake\Datasource\RepositoryInterface|null The model instance or null
     */
    public function __get(string $name)
    {
        if (!empty($this->modelClass)) {
            if (strpos($this->modelClass, '\\') === false) {
                [, $class] = pluginSplit($this->modelClass, true);
            } else {
                $class = App::shortName($this->modelClass, 'Model/Table', 'Table');
            }

            if ($class === $name) {
                return $this->fetchModel();
            }
        }

        $trace = debug_backtrace();
        $parts = explode('\\', static::class);
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

        return null;
    }

    /**
     * Magic setter for removed properties.
     *
     * @param string $name Property name.
     * @param mixed $value Value to set.
     * @return void
     */
    public function __set(string $name, $value): void
    {
        if ($name === 'components') {
            triggerWarning(
                'Support for loading components using $components property is removed. ' .
                'Use $this->loadComponent() instead in initialize().'
            );

            return;
        }

        if ($name === 'helpers') {
            triggerWarning(
                'Support for loading helpers using $helpers property is removed. ' .
                'Use $this->viewBuilder()->setHelpers() instead.'
            );

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
    public function getName(): string
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
    public function setName(string $name)
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
    public function getPlugin(): ?string
    {
        return $this->plugin;
    }

    /**
     * Sets the plugin name.
     *
     * @param string|null $name Plugin name.
     * @return $this
     * @since 3.6.0
     */
    public function setPlugin(?string $name)
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
    public function isAutoRenderEnabled(): bool
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
    public function getRequest(): ServerRequest
    {
        return $this->request;
    }

    /**
     * Sets the request objects and configures a number of controller properties
     * based on the contents of the request. Controller acts as a proxy for certain View variables
     * which must also be updated here. The properties that get set are:
     *
     * - $this->request - To the $request parameter
     *
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @return $this
     */
    public function setRequest(ServerRequest $request)
    {
        $this->request = $request;
        $this->plugin = $request->getParam('plugin') ?: null;

        return $this;
    }

    /**
     * Gets the response instance.
     *
     * @return \Cake\Http\Response
     * @since 3.6.0
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
     * @since 3.6.0
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Get the closure for action to be invoked by ControllerFactory.
     *
     * @return \Closure
     * @throws \Cake\Controller\Exception\MissingActionException
     */
    public function getAction(): Closure
    {
        $request = $this->request;
        $action = $request->getParam('action');

        if (!$this->isAction($action)) {
            throw new MissingActionException([
                'controller' => $this->name . 'Controller',
                'action' => $request->getParam('action'),
                'prefix' => $request->getParam('prefix') ?: '',
                'plugin' => $request->getParam('plugin'),
            ]);
        }

        return Closure::fromCallable([$this, $action]);
    }

    /**
     * Dispatches the controller action.
     *
     * @param \Closure $action The action closure.
     * @param array $args The arguments to be passed when invoking action.
     * @return void
     * @throws \UnexpectedValueException If return value of action is not `null` or `ResponseInterface` instance.
     */
    public function invokeAction(Closure $action, array $args): void
    {
        $result = $action(...$args);
        if ($result !== null && !$result instanceof ResponseInterface) {
            throw new UnexpectedValueException(sprintf(
                'Controller actions can only return ResponseInterface instance or null. '
                . 'Got %s instead.',
                getTypeName($result)
            ));
        }
        if ($result === null && $this->isAutoRenderEnabled()) {
            $result = $this->render();
        }
        if ($result) {
            $this->response = $result;
        }
    }

    /**
     * Register middleware for the controller.
     *
     * @param \Psr\Http\Server\MiddlewareInterface|\Closure|string $middleware Middleware.
     * @param array<string, mixed> $options Valid options:
     *  - `only`: (array|string) Only run the middleware for specified actions.
     *  - `except`: (array|string) Run the middleware for all actions except the specified ones.
     * @return void
     * @since 4.3.0
     * @psalm-param array{only?: array|string, except?: array|string} $options
     */
    public function middleware($middleware, array $options = [])
    {
        $this->middlewares[] = [
            'middleware' => $middleware,
            'options' => $options,
        ];
    }

    /**
     * Get middleware to be applied for this controller.
     *
     * @return array
     * @since 4.3.0
     */
    public function getMiddleware(): array
    {
        $matching = [];
        $action = $this->request->getParam('action');

        foreach ($this->middlewares as $middleware) {
            $options = $middleware['options'];
            if (!empty($options['only'])) {
                if (in_array($action, (array)$options['only'], true)) {
                    $matching[] = $middleware['middleware'];
                }

                continue;
            }

            if (
                !empty($options['except']) &&
                in_array($action, (array)$options['except'], true)
            ) {
                continue;
            }

            $matching[] = $middleware['middleware'];
        }

        return $matching;
    }

    /**
     * Returns a list of all events that will fire in the controller during its lifecycle.
     * You can override this function to add your own listener callbacks
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'Controller.initialize' => 'beforeFilter',
            'Controller.beforeRender' => 'beforeRender',
            'Controller.beforeRedirect' => 'beforeRedirect',
            'Controller.shutdown' => 'afterFilter',
        ];
    }

    /**
     * Perform the startup process for this controller.
     * Fire the Components and Controller callbacks in the correct order.
     *
     * - Initializes components, which fires their `initialize` callback
     * - Calls the controller `beforeFilter`.
     * - triggers Component `startup` methods.
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function startupProcess(): ?ResponseInterface
    {
        $event = $this->dispatchEvent('Controller.initialize');
        if ($event->getResult() instanceof ResponseInterface) {
            return $event->getResult();
        }
        $event = $this->dispatchEvent('Controller.startup');
        if ($event->getResult() instanceof ResponseInterface) {
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
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function shutdownProcess(): ?ResponseInterface
    {
        $event = $this->dispatchEvent('Controller.shutdown');
        if ($event->getResult() instanceof ResponseInterface) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Redirects to given $url, after turning off $this->autoRender.
     *
     * @param \Psr\Http\Message\UriInterface|array|string $url A string, array-based URL or UriInterface instance.
     * @param int $status HTTP status code. Defaults to `302`.
     * @return \Cake\Http\Response|null
     * @link https://book.cakephp.org/4/en/controllers.html#Controller::redirect
     */
    public function redirect($url, int $status = 302): ?Response
    {
        $this->autoRender = false;

        if ($status < 300 || $status > 399) {
            throw new InvalidArgumentException(
                sprintf('Invalid status code `%s`. It should be within the range ' .
                    '`300` - `399` for redirect responses.', $status)
            );
        }

        $this->response = $this->response->withStatus($status);
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
     * @param mixed ...$args Arguments passed to the action
     * @return mixed Returns the return value of the called action
     * @deprecated 4.2.0 Refactor your code use `redirect()` instead of forwarding actions.
     */
    public function setAction(string $action, ...$args)
    {
        deprecationWarning(
            'Controller::setAction() is deprecated. Either refactor your code to use `redirect()`, ' .
            'or call the other action as a method.'
        );
        $this->setRequest($this->request->withParam('action', $action));

        return $this->$action(...$args);
    }

    /**
     * Instantiates the correct view class, hands it its data, and uses it to render the view output.
     *
     * @param string|null $template Template to use for rendering
     * @param string|null $layout Layout to use
     * @return \Cake\Http\Response A response object containing the rendered view.
     * @link https://book.cakephp.org/4/en/controllers.html#rendering-a-view
     */
    public function render(?string $template = null, ?string $layout = null): Response
    {
        $builder = $this->viewBuilder();
        if (!$builder->getTemplatePath()) {
            $builder->setTemplatePath($this->_templatePath());
        }

        $this->autoRender = false;

        if ($template !== null) {
            $builder->setTemplate($template);
        }

        if ($layout !== null) {
            $builder->setLayout($layout);
        }

        $event = $this->dispatchEvent('Controller.beforeRender');
        if ($event->getResult() instanceof Response) {
            return $event->getResult();
        }
        if ($event->isStopped()) {
            return $this->response;
        }

        if ($builder->getTemplate() === null) {
            $builder->setTemplate($this->request->getParam('action'));
        }
        $viewClass = $this->chooseViewClass();
        $view = $this->createView($viewClass);

        $contents = $view->render();
        $response = $view->getResponse()->withStringBody($contents);

        return $this->setResponse($response)->response;
    }

    /**
     * Get the View classes this controller can perform content negotiation with.
     *
     * Each view class must implement the `getContentType()` hook method
     * to participate in negotiation.
     *
     * @see Cake\Http\ContentTypeNegotiation
     * @return array<string>
     */
    public function viewClasses(): array
    {
        return $this->viewClasses;
    }

    /**
     * Add View classes this controller can perform content negotiation with.
     *
     * Each view class must implement the `getContentType()` hook method
     * to participate in negotiation.
     *
     * @param array $viewClasses View classes list.
     * @return $this
     * @see Cake\Http\ContentTypeNegotiation
     * @since 4.5.0
     */
    public function addViewClasses(array $viewClasses)
    {
        $this->viewClasses = array_merge($this->viewClasses, $viewClasses);

        return $this;
    }

    /**
     * Use the view classes defined on this controller to view
     * selection based on content-type negotiation.
     *
     * @return string|null The chosen view class or null for no decision.
     */
    protected function chooseViewClass(): ?string
    {
        $possibleViewClasses = $this->viewClasses();
        if (empty($possibleViewClasses)) {
            return null;
        }
        // Controller or component has already made a view class decision.
        // That decision should overwrite the framework behavior.
        if ($this->viewBuilder()->getClassName() !== null) {
            return null;
        }

        $typeMap = [];
        foreach ($possibleViewClasses as $class) {
            $viewContentType = $class::contentType();
            if ($viewContentType && !isset($typeMap[$viewContentType])) {
                $typeMap[$viewContentType] = $class;
            }
        }
        $request = $this->getRequest();

        // Prefer the _ext route parameter if it is defined.
        $ext = $request->getParam('_ext');
        if ($ext) {
            $extTypes = (array)($this->response->getMimeType($ext) ?: []);
            foreach ($extTypes as $extType) {
                if (isset($typeMap[$extType])) {
                    return $typeMap[$extType];
                }
            }

            throw new NotFoundException();
        }

        // Use accept header based negotiation.
        $contentType = new ContentTypeNegotiation();
        $preferredType = $contentType->preferredType($request, array_keys($typeMap));
        if ($preferredType) {
            return $typeMap[$preferredType];
        }

        // Use the match-all view if available or null for no decision.
        return $typeMap[View::TYPE_MATCH_ALL] ?? null;
    }

    /**
     * Get the templatePath based on controller name and request prefix.
     *
     * @return string
     */
    protected function _templatePath(): string
    {
        $templatePath = $this->name;
        if ($this->request->getParam('prefix')) {
            $prefixes = array_map(
                'Cake\Utility\Inflector::camelize',
                explode('/', $this->request->getParam('prefix'))
            );
            $templatePath = implode(DIRECTORY_SEPARATOR, $prefixes) . DIRECTORY_SEPARATOR . $templatePath;
        }

        return $templatePath;
    }

    /**
     * Returns the referring URL for this request.
     *
     * @param array|string|null $default Default URL to use if HTTP_REFERER cannot be read from headers
     * @param bool $local If false, do not restrict referring URLs to local server.
     *   Careful with trusting external sources.
     * @return string Referring URL
     */
    public function referer($default = '/', bool $local = true): string
    {
        $referer = $this->request->referer($local);
        if ($referer === null) {
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
     * Will load the referenced Table object, and have the paginator
     * paginate the query using the request date and settings defined in `$this->paginate`.
     *
     * This method will also make the PaginatorHelper available in the view.
     *
     * @param \Cake\ORM\Table|\Cake\ORM\Query|string|null $object Table to paginate
     * (e.g: Table instance, 'TableName' or a Query object)
     * @param array<string, mixed> $settings The settings/configuration used for pagination.
     * @return \Cake\ORM\ResultSet|\Cake\Datasource\ResultSetInterface Query results
     * @link https://book.cakephp.org/4/en/controllers.html#paginating-a-model
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

        if (empty($table)) {
            throw new RuntimeException('Unable to locate an object compatible with paginate.');
        }

        $settings += $this->paginate;

        if (isset($this->Paginator)) {
            return $this->Paginator->paginate($table, $settings);
        }

        if (isset($settings['paginator'])) {
            $settings['className'] = $settings['paginator'];
            deprecationWarning(
                '`paginator` option is deprecated,'
                . ' use `className` instead a specify a paginator name/FQCN.'
            );
        }

        $paginator = $settings['className'] ?? NumericPaginator::class;
        unset($settings['className']);
        if (is_string($paginator)) {
            $className = App::className($paginator, 'Datasource/Paging', 'Paginator');
            if ($className === null) {
                throw new InvalidArgumentException('Invalid paginator: ' . $paginator);
            }
            $paginator = new $className();
        }
        if (!$paginator instanceof PaginatorInterface) {
            throw new InvalidArgumentException('Paginator must be an instance of ' . PaginatorInterface::class);
        }

        $results = null;
        try {
            $results = $paginator->paginate(
                $table,
                $this->request->getQueryParams(),
                $settings
            );
        } catch (PageOutOfBoundsException $e) {
            // Exception thrown below
        } finally {
            $paging = $paginator->getPagingParams() + (array)$this->request->getAttribute('paging', []);
            $this->request = $this->request->withAttribute('paging', $paging);
        }

        if (isset($e)) {
            throw new NotFoundException(null, null, $e);
        }

        /** @psalm-suppress NullableReturnStatement */
        return $results;
    }

    /**
     * Method to check that an action is accessible from a URL.
     *
     * Override this method to change which controller methods can be reached.
     * The default implementation disallows access to all methods defined on Cake\Controller\Controller,
     * and allows all public methods on all subclasses of this class.
     *
     * @param string $action The action to check.
     * @return bool Whether the method is accessible from a URL.
     * @throws \ReflectionException
     */
    public function isAction(string $action): bool
    {
        $baseClass = new ReflectionClass(self::class);
        if ($baseClass->hasMethod($action)) {
            return false;
        }
        try {
            $method = new ReflectionMethod($this, $action);
        } catch (ReflectionException $e) {
            return false;
        }

        return $method->isPublic() && $method->getName() === $action;
    }

    /**
     * Called before the controller action. You can use this method to configure and customize components
     * or perform logic that needs to happen before each controller action.
     *
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return \Cake\Http\Response|null|void
     * @link https://book.cakephp.org/4/en/controllers.html#request-life-cycle-callbacks
     */
    public function beforeFilter(EventInterface $event)
    {
    }

    /**
     * Called after the controller action is run, but before the view is rendered. You can use this method
     * to perform logic or set view variables that are required on every request.
     *
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return \Cake\Http\Response|null|void
     * @link https://book.cakephp.org/4/en/controllers.html#request-life-cycle-callbacks
     */
    public function beforeRender(EventInterface $event)
    {
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
     * @param \Cake\Event\EventInterface $event An Event instance
     * @param array|string $url A string or array-based URL pointing to another location within the app,
     *     or an absolute URL
     * @param \Cake\Http\Response $response The response object.
     * @return \Cake\Http\Response|null|void
     * @link https://book.cakephp.org/4/en/controllers.html#request-life-cycle-callbacks
     */
    public function beforeRedirect(EventInterface $event, $url, Response $response)
    {
    }

    /**
     * Called after the controller action is run and rendered.
     *
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return \Cake\Http\Response|null|void
     * @link https://book.cakephp.org/4/en/controllers.html#request-life-cycle-callbacks
     */
    public function afterFilter(EventInterface $event)
    {
    }
}
