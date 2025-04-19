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
use Cake\Datasource\Paging\Exception\PageOutOfBoundsException;
use Cake\Datasource\Paging\NumericPaginator;
use Cake\Datasource\Paging\PaginatedInterface;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManagerInterface;
use Cake\Http\ContentTypeNegotiation;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\MimeType;
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
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;
use ReflectionMethod;
use function Cake\Core\namespaceSplit;
use function Cake\Core\pluginSplit;

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
 * @property \Cake\Controller\Component\CheckHttpCacheComponent $CheckHttpCache
 * @link https://book.cakephp.org/5/en/controllers.html
 * @implements \Cake\Event\EventDispatcherInterface<\Cake\Controller\Controller>
 */
class Controller implements EventListenerInterface, EventDispatcherInterface
{
    /**
     * @use \Cake\Event\EventDispatcherTrait<\Cake\Controller\Controller>
     */
    use EventDispatcherTrait;
    use LocatorAwareTrait;
    use LogTrait;
    use ViewVarsTrait;

    /**
     * The name of this controller. Controller names are plural, named after the model they manipulate.
     *
     * Set automatically using conventions in Controller::__construct().
     *
     * @var string
     */
    protected string $name;

    /**
     * An instance of a \Cake\Http\ServerRequest object that contains information about the current request.
     * This object contains all the information about a request and several methods for reading
     * additional information about the request.
     *
     * @var \Cake\Http\ServerRequest
     * @link https://book.cakephp.org/5/en/controllers/request-response.html#request
     */
    protected ServerRequest $request;

    /**
     * An instance of a Response object that contains information about the impending response
     *
     * @var \Cake\Http\Response
     * @link https://book.cakephp.org/5/en/controllers/request-response.html#response
     */
    protected Response $response;

    /**
     * Pagination settings.
     *
     * When calling paginate() these settings will be merged with the configuration
     * you provide. Possible keys:
     *
     * - `maxLimit` - The maximum limit users can choose to view. Defaults to 100
     * - `limit` - The initial number of items per page. Defaults to 20.
     * - `page` - The starting page, defaults to 1.
     * - `allowedParameters` - A list of parameters users are allowed to set using request
     *   parameters. Modifying this list will allow users to have more influence
     *   over pagination, be careful with what you permit.
     * - `className` - The paginator class to use. Defaults to `Cake\Datasource\Paging\NumericPaginator::class`.
     *
     * @var array<string, mixed>
     * @see \Cake\Datasource\Paging\NumericPaginator
     */
    protected array $paginate = [];

    /**
     * Set to true to automatically render the view
     * after action logic.
     *
     * @var bool
     */
    protected bool $autoRender = true;

    /**
     * Instance of ComponentRegistry used to create Components
     *
     * @var \Cake\Controller\ComponentRegistry|null
     */
    protected ?ComponentRegistry $_components = null;

    /**
     * Automatically set to the name of a plugin.
     *
     * @var string|null
     */
    protected ?string $plugin = null;

    /**
     * Middlewares list.
     *
     * @var array
     * @phpstan-var array<int, array{middleware:\Psr\Http\Server\MiddlewareInterface|\Closure|string, options:array{only?: array|string, except?: array|string}}>
     */
    protected array $middlewares = [];

    /**
     * View classes for content negotiation.
     *
     * @var array<string>
     */
    protected array $viewClasses = [];

    /**
     * Constructor.
     *
     * Sets a number of properties based on conventions if they are empty. To override the
     * conventions CakePHP uses you can define properties in your class declaration.
     *
     * @param \Cake\Http\ServerRequest $request Request object for this controller.
     *   but expect that features that use the request parameters will not work.
     * @param string|null $name Override the name useful in testing when using mocks.
     * @param \Cake\Event\EventManagerInterface|null $eventManager The event manager. Defaults to a new instance.
     * @param \Cake\Controller\ComponentRegistry|null $components ComponentRegistry to use. Defaults to a new instance.
     */
    public function __construct(
        ServerRequest $request,
        ?string $name = null,
        ?EventManagerInterface $eventManager = null,
        ?ComponentRegistry $components = null,
    ) {
        if ($name !== null) {
            $this->name = $name;
        } elseif (!isset($this->name)) {
            $controller = $request->getParam('controller');
            if ($controller) {
                $this->name = $controller;
            }
        }

        if (!isset($this->name)) {
            [, $name] = namespaceSplit(static::class);
            $this->name = substr($name, 0, -10);
        }

        $this->setRequest($request);
        $this->response = new Response();

        if ($eventManager !== null) {
            $this->setEventManager($eventManager);
        }
        if ($components !== null) {
            $this->_components = $components;
            $components->setController($this);
        }
        if ($this->defaultTable === null) {
            $plugin = $this->request->getParam('plugin');
            $tableAlias = ($plugin ? $plugin . '.' : '') . $this->name;
            $this->defaultTable = $tableAlias;
        }

        $this->initialize();

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
     * @return \Cake\Controller\ComponentRegistry
     */
    public function components(): ComponentRegistry
    {
        return $this->_components ??= new ComponentRegistry($this);
    }

    /**
     * Add a component to the controller's registry.
     *
     * After loading a component it will be be accessible as a property through Controller::__get().
     * For example:
     *
     * ```
     * $this->loadComponent('Authentication.Authentication');
     * ```
     *
     * Will result in a `$this->Authentication` being a reference to that component.
     *
     * @param string $name The name of the component to load.
     * @param array<string, mixed> $config The config for the component.
     * @return \Cake\Controller\Component
     * @throws \Exception
     */
    public function loadComponent(string $name, array $config = []): Component
    {
        return $this->components()->load($name, $config);
    }

    /**
     * Magic accessor for the default table.
     *
     * @param string $name Property name
     * @return \Cake\Controller\Component|\Cake\ORM\Table|null
     */
    public function __get(string $name): mixed
    {
        if ($this->defaultTable) {
            if (str_contains($this->defaultTable, '\\')) {
                $class = App::shortName($this->defaultTable, 'Model/Table', 'Table');
            } else {
                [, $class] = pluginSplit($this->defaultTable, true);
            }

            if ($class === $name) {
                return $this->fetchTable();
            }
        }

        if ($this->components()->has($name)) {
            return $this->components()->get($name);
        }

        $trace = debug_backtrace();
        $parts = explode('\\', static::class);
        trigger_error(
            sprintf(
                'Undefined property `%s::$%s` in `%s` on line %s',
                array_pop($parts),
                $name,
                $trace[0]['file'] ?? 'unknown',
                $trace[0]['line'] ?? 'unknown',
            ),
            E_USER_NOTICE,
        );

        return null;
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
        $this->plugin = $request->getParam('plugin');

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
        $controller = $this->name . 'Controller';
        if ($request->getParam('prefix')) {
            $controller = $request->getParam('prefix') . '/' . $controller;
        }
        if ($this->plugin) {
            $controller = $this->plugin . '.' . $controller;
        }

        if (!$this->isAction($action)) {
            throw new MissingActionException([
                $controller,
                $action,
            ]);
        }

        return $this->$action(...);
    }

    /**
     * Dispatches the controller action.
     *
     * @param \Closure $action The action closure.
     * @param array $args The arguments to be passed when invoking action.
     * @return void
     */
    public function invokeAction(Closure $action, array $args): void
    {
        $result = $action(...$args);
        if ($result !== null) {
            assert(
                $result instanceof Response,
                sprintf(
                    'Controller actions can only return Response instance or null. '
                    . 'Got %s instead.',
                    get_debug_type($result),
                ),
            );
        } elseif ($this->isAutoRenderEnabled()) {
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
     * @phpstan-param array{only?: array|string, except?: array|string} $options
     */
    public function middleware(MiddlewareInterface|Closure|string $middleware, array $options = []): void
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
        $result = $this->dispatchEvent('Controller.initialize')->getResult();
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        $result = $this->dispatchEvent('Controller.startup')->getResult();
        if ($result instanceof ResponseInterface) {
            return $result;
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
        $result = $this->dispatchEvent('Controller.shutdown')->getResult();
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        return null;
    }

    /**
     * Redirects to given $url, after turning off $this->autoRender.
     *
     * @param \Psr\Http\Message\UriInterface|array|string $url A string, array-based URL or UriInterface instance.
     * @param int $status HTTP status code. Defaults to `302`.
     * @return \Cake\Http\Response|null
     * @link https://book.cakephp.org/5/en/controllers.html#Controller::redirect
     */
    public function redirect(UriInterface|array|string $url, int $status = 302): ?Response
    {
        $this->autoRender = false;

        if ($status < 300 || $status > 399) {
            throw new InvalidArgumentException(
                sprintf('Invalid status code `%s`. It should be within the range ' .
                    '`300` - `399` for redirect responses.', $status),
            );
        }

        $this->response = $this->response->withStatus($status);
        $event = $this->dispatchEvent('Controller.beforeRedirect', [$url, $this->response]);
        $result = $event->getResult();
        if ($result instanceof Response) {
            return $this->response = $result;
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
     * Instantiates the correct view class, hands it its data, and uses it to render the view output.
     *
     * @param string|null $template Template to use for rendering
     * @param string|null $layout Layout to use
     * @return \Cake\Http\Response A response object containing the rendered view.
     * @link https://book.cakephp.org/5/en/controllers.html#rendering-a-view
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
     * @see \Cake\Http\ContentTypeNegotiation
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
     * @param array<string> $viewClasses View classes list.
     * @return $this
     * @see \Cake\Http\ContentTypeNegotiation
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
        if (!$possibleViewClasses) {
            return null;
        }
        // Controller or component has already made a view class decision.
        // That decision should overwrite the framework behavior.
        if ($this->viewBuilder()->getClassName() !== null) {
            return null;
        }

        $typeMap = [];
        foreach ($possibleViewClasses as $class) {
            /** @var string $viewContentType */
            $viewContentType = $class::contentType();
            if ($viewContentType && !isset($typeMap[$viewContentType])) {
                $typeMap[$viewContentType] = $class;
            }
        }
        $request = $this->getRequest();

        // Prefer the _ext route parameter if it is defined.
        $ext = $request->getParam('_ext');
        if ($ext) {
            $extTypes = MimeType::getMimeTypes($ext) ?? [];
            foreach ($extTypes as $extType) {
                if (isset($typeMap[$extType])) {
                    return $typeMap[$extType];
                }
            }

            throw new NotFoundException(sprintf('View class for `%s` extension not found', $ext));
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
                explode('/', $this->request->getParam('prefix')),
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
    public function referer(array|string|null $default = '/', bool $local = true): string
    {
        $referer = $this->request->referer($local);
        if ($referer !== null) {
            return $referer;
        }

        $url = Router::url($default, !$local);
        $base = $this->request->getAttribute('base');
        if ($local && $base && str_starts_with($url, $base)) {
            $url = substr($url, strlen($base));
            if (!str_starts_with($url, '/')) {
                return '/' . $url;
            }

            return $url;
        }

        return $url;
    }

    /**
     * Handles pagination of records in Table objects.
     *
     * Will load the referenced Table object, and have the paginator
     * paginate the query using the request date and settings defined in `$this->paginate`.
     *
     * This method will also make the PaginatorHelper available in the view.
     *
     * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface|string|null $object Table to paginate
     * (e.g: Table instance, 'TableName' or a Query object)
     * @param array<string, mixed> $settings The settings/configuration used for pagination. See {@link \Cake\Controller\Controller::$paginate}.
     * @return \Cake\Datasource\Paging\PaginatedInterface
     * @link https://book.cakephp.org/5/en/controllers.html#paginating-a-model
     * @throws \Cake\Http\Exception\NotFoundException When a page out of bounds is requested.
     */
    public function paginate(
        RepositoryInterface|QueryInterface|string|null $object = null,
        array $settings = [],
    ): PaginatedInterface {
        if (!is_object($object)) {
            $object = $this->fetchTable($object);
        }

        $settings += $this->paginate;

        /** @var class-string<\Cake\Datasource\Paging\PaginatorInterface> $paginator */
        $paginator = App::className(
            $settings['className'] ?? NumericPaginator::class,
            'Datasource/Paging',
            'Paginator',
        );
        $paginator = new $paginator();
        unset($settings['className']);

        try {
            $results = $paginator->paginate(
                $object,
                $this->request->getQueryParams(),
                $settings,
            );
        } catch (PageOutOfBoundsException $exception) {
            throw new NotFoundException(null, null, $exception);
        }

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
     */
    public function isAction(string $action): bool
    {
        if (method_exists(self::class, $action)) {
            return false;
        }

        try {
            $method = new ReflectionMethod($this, $action);
        } catch (ReflectionException) {
            return false;
        }

        return $method->isPublic() && $method->getName() === $action;
    }

    /**
     * Called before the controller action. You can use this method to configure and customize components
     * or perform logic that needs to happen before each controller action.
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event An Event instance
     * @return void
     * @link https://book.cakephp.org/5/en/controllers.html#request-life-cycle-callbacks
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function beforeFilter(EventInterface $event)
    {
    }

    /**
     * Called after the controller action is run, but before the view is rendered. You can use this method
     * to perform logic or set view variables that are required on every request.
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event An Event instance
     * @return void
     * @link https://book.cakephp.org/5/en/controllers.html#request-life-cycle-callbacks
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
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
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event An Event instance
     * @param \Psr\Http\Message\UriInterface|array|string $url A string or array-based URL pointing to another location within the app,
     *     or an absolute URL
     * @param \Cake\Http\Response $response The response object.
     * @return void
     * @link https://book.cakephp.org/5/en/controllers.html#request-life-cycle-callbacks
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function beforeRedirect(EventInterface $event, UriInterface|array|string $url, Response $response)
    {
    }

    /**
     * Called after the controller action is run and rendered.
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event An Event instance
     * @return void
     * @link https://book.cakephp.org/5/en/controllers.html#request-life-cycle-callbacks
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function afterFilter(EventInterface $event)
    {
    }
}
