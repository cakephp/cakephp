<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\HttpApplicationInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Core\TestSuite\ContainerStubTrait;
use Cake\Database\Exception\DatabaseException;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Form\FormProtector;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\Session;
use Cake\Routing\Router;
use Cake\Routing\RoutingApplicationInterface;
use Cake\TestSuite\Constraint\Response\BodyContains;
use Cake\TestSuite\Constraint\Response\BodyEmpty;
use Cake\TestSuite\Constraint\Response\BodyEquals;
use Cake\TestSuite\Constraint\Response\BodyNotContains;
use Cake\TestSuite\Constraint\Response\BodyNotEmpty;
use Cake\TestSuite\Constraint\Response\BodyNotEquals;
use Cake\TestSuite\Constraint\Response\BodyNotRegExp;
use Cake\TestSuite\Constraint\Response\BodyRegExp;
use Cake\TestSuite\Constraint\Response\ContentType;
use Cake\TestSuite\Constraint\Response\CookieEncryptedEquals;
use Cake\TestSuite\Constraint\Response\CookieEquals;
use Cake\TestSuite\Constraint\Response\CookieNotSet;
use Cake\TestSuite\Constraint\Response\CookieSet;
use Cake\TestSuite\Constraint\Response\FileSent;
use Cake\TestSuite\Constraint\Response\FileSentAs;
use Cake\TestSuite\Constraint\Response\HeaderContains;
use Cake\TestSuite\Constraint\Response\HeaderEquals;
use Cake\TestSuite\Constraint\Response\HeaderNotContains;
use Cake\TestSuite\Constraint\Response\HeaderNotSet;
use Cake\TestSuite\Constraint\Response\HeaderSet;
use Cake\TestSuite\Constraint\Response\StatusCode;
use Cake\TestSuite\Constraint\Response\StatusError;
use Cake\TestSuite\Constraint\Response\StatusFailure;
use Cake\TestSuite\Constraint\Response\StatusOk;
use Cake\TestSuite\Constraint\Response\StatusSuccess;
use Cake\TestSuite\Constraint\Session\FlashParamContains;
use Cake\TestSuite\Constraint\Session\FlashParamEquals;
use Cake\TestSuite\Constraint\Session\SessionEquals;
use Cake\TestSuite\Constraint\Session\SessionHasKey;
use Cake\TestSuite\Constraint\View\LayoutFileEquals;
use Cake\TestSuite\Constraint\View\TemplateFileEquals;
use Cake\TestSuite\Stub\TestExceptionRenderer;
use Cake\Utility\CookieCryptTrait;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use Exception;
use Laminas\Diactoros\Uri;
use PHPUnit\Exception as PHPUnitException;
use PHPUnit\Framework\Attributes\After;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * A trait intended to make integration tests of your controllers easier.
 *
 * This test class provides a number of helper methods and features
 * that make dispatching requests and checking their responses simpler.
 * It favours full integration tests over mock objects as you can test
 * more of your code easily and avoid some of the maintenance pitfalls
 * that mock objects create.
 */
trait IntegrationTestTrait
{
    use CookieCryptTrait;
    use ContainerStubTrait;

    /**
     * The data used to build the next request.
     *
     * @var array
     */
    protected array $request = [];

    /**
     * The response for the most recent request.
     *
     * @var \Psr\Http\Message\ResponseInterface|null
     */
    protected ?ResponseInterface $response = null;

    /**
     * The exception being thrown if the case.
     *
     * @var \Throwable|null
     */
    protected ?Throwable $exception = null;

    /**
     * Session data to use in the next request.
     *
     * @var array
     */
    protected array $session = [];

    /**
     * Cookie data to use in the next request.
     *
     * @var array
     */
    protected array $cookie = [];

    /**
     * The controller used in the last request.
     *
     * @var \Cake\Controller\Controller|null
     */
    protected ?Controller $controller = null;

    /**
     * The last rendered view
     *
     * @var string|null
     */
    protected ?string $viewName = null;

    /**
     * The last rendered layout
     *
     * @var string|null
     */
    protected ?string $layoutName = null;

    /**
     * The session instance from the last request
     *
     * @var \Cake\Http\Session|null
     */
    protected ?Session $requestSession = null;

    /**
     * Boolean flag for whether the request should have
     * a FormProtectionComponent token added.
     *
     * @var bool
     */
    protected bool $securityToken = false;

    /**
     * Boolean flag for whether the request should have
     * a CSRF token added.
     *
     * @var bool
     */
    protected bool $csrfToken = false;

    /**
     * Boolean flag for whether the request should re-store
     * flash messages
     *
     * @var bool
     */
    protected bool $retainFlashMessages = false;

    /**
     * Stored flash messages before render
     *
     * @var array
     */
    protected array $flashMessages = [];

    /**
     * @var string|null
     */
    protected ?string $cookieEncryptionKey = null;

    /**
     * List of fields that are excluded from field validation.
     *
     * @var array<string>
     */
    protected array $unlockedFields = [];

    /**
     * The name that will be used when retrieving the csrf token.
     *
     * @var string
     */
    protected string $csrfKeyName = 'csrfToken';

    /**
     * Clears the state used for requests.
     *
     * @return void
     */
    #[After]
    public function cleanup(): void
    {
        $this->request = [];
        $this->session = [];
        $this->cookie = [];
        $this->response = null;
        $this->exception = null;
        $this->controller = null;
        $this->viewName = null;
        $this->layoutName = null;
        $this->requestSession = null;
        $this->securityToken = false;
        $this->csrfToken = false;
        $this->retainFlashMessages = false;
        $this->flashMessages = [];
    }

    /**
     * Calling this method will enable a FormProtectionComponent
     * compatible token to be added to request data. This
     * lets you easily test actions protected by FormProtectionComponent.
     *
     * @return void
     */
    public function enableSecurityToken(): void
    {
        $this->securityToken = true;
    }

    /**
     * Set list of fields that are excluded from field validation.
     *
     * @param array<string> $unlockedFields List of fields that are excluded from field validation.
     * @return void
     */
    public function setUnlockedFields(array $unlockedFields = []): void
    {
        $this->unlockedFields = $unlockedFields;
    }

    /**
     * Calling this method will add a CSRF token to the request.
     *
     * Both the POST data and cookie will be populated when this option
     * is enabled. The default parameter names will be used.
     *
     * @param string $cookieName The name of the csrf token cookie.
     * @return void
     */
    public function enableCsrfToken(string $cookieName = 'csrfToken'): void
    {
        $this->csrfToken = true;
        $this->csrfKeyName = $cookieName;
    }

    /**
     * Calling this method will re-store flash messages into the test session
     * after being removed by the FlashHelper
     *
     * @return void
     */
    public function enableRetainFlashMessages(): void
    {
        $this->retainFlashMessages = true;
    }

    /**
     * Configures the data for the *next* request merging with existing state.
     *
     * This data is cleared in the tearDown() method.
     *
     * You can call this method multiple times to append into
     * the current state. Sub-keys will be merged with existing
     * state.
     *
     * @param array $data The request data to use.
     * @return void
     */
    public function configRequest(array $data): void
    {
        $this->request = array_merge_recursive($data, $this->request);
    }

    /**
     * Configures the data for the *next* request replacing existing state.
     *
     * @param array $data The request data to use.
     * @return void
     */
    public function replaceRequest(array $data): void
    {
        $this->request = $data;
    }

    /**
     * Sets HTTP headers for the *next* request to be identified as JSON request.
     *
     * @return void
     */
    public function requestAsJson(): void
    {
        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Sets session data.
     *
     * This method lets you configure the session data
     * you want to be used for requests that follow. The session
     * state is reset in each tearDown().
     *
     * You can call this method multiple times to append into
     * the current state.
     *
     * @param array $data The session data to use.
     * @return void
     */
    public function session(array $data): void
    {
        $this->session = $data + $this->session;
    }

    /**
     * Sets a request cookie for future requests.
     *
     * This method lets you configure the session data
     * you want to be used for requests that follow. The session
     * state is reset in each tearDown().
     *
     * You can call this method multiple times to append into
     * the current state.
     *
     * @param string $name The cookie name to use.
     * @param string $value The value of the cookie.
     * @return void
     */
    public function cookie(string $name, string $value): void
    {
        $this->cookie[$name] = $value;
    }

    /**
     * Returns the encryption key to be used.
     *
     * @return string
     */
    protected function getCookieEncryptionKey(): string
    {
        return $this->cookieEncryptionKey ?? Security::getSalt();
    }

    /**
     * Sets a encrypted request cookie for future requests.
     *
     * The difference from cookie() is this encrypts the cookie
     * value like the CookieComponent.
     *
     * @param string $name The cookie name to use.
     * @param array|string $value The value of the cookie.
     * @param string|false $encrypt Encryption mode to use.
     * @param string|null $key Encryption key used. Defaults
     *   to Security.salt.
     * @return void
     * @see \Cake\Utility\CookieCryptTrait::encrypt()
     */
    public function cookieEncrypted(
        string $name,
        array|string $value,
        string|false $encrypt = 'aes',
        ?string $key = null,
    ): void {
        $this->cookieEncryptionKey = $key;
        $this->cookie[$name] = $this->encrypt($value, $encrypt);
    }

    /**
     * Performs a GET request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @return void
     */
    public function get(array|string $url): void
    {
        $this->sendRequest($url, 'GET');
    }

    /**
     * Performs a POST request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @param array|string $data The data for the request.
     * @return void
     */
    public function post(array|string $url, array|string $data = []): void
    {
        $this->sendRequest($url, 'POST', $data);
    }

    /**
     * Performs a PATCH request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @param array|string $data The data for the request.
     * @return void
     */
    public function patch(array|string $url, array|string $data = []): void
    {
        $this->sendRequest($url, 'PATCH', $data);
    }

    /**
     * Performs a PUT request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @param array|string $data The data for the request.
     * @return void
     */
    public function put(array|string $url, array|string $data = []): void
    {
        $this->sendRequest($url, 'PUT', $data);
    }

    /**
     * Performs a DELETE request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @param array|string $data The data for the request.
     * @return void
     */
    public function delete(array|string $url, array|string $data = []): void
    {
        $this->sendRequest($url, 'DELETE', $data);
    }

    /**
     * Performs a HEAD request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @return void
     */
    public function head(array|string $url): void
    {
        $this->sendRequest($url, 'HEAD');
    }

    /**
     * Performs an OPTIONS request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @return void
     */
    public function options(array|string $url): void
    {
        $this->sendRequest($url, 'OPTIONS');
    }

    /**
     * Creates and send the request into a Dispatcher instance.
     *
     * Receives and stores the response for future inspection.
     *
     * @param array|string $url The URL
     * @param string $method The HTTP method
     * @param array|string $data The request data.
     * @return void
     * @throws \PHPUnit\Exception|\Throwable
     */
    protected function sendRequest(array|string $url, string $method, array|string $data = []): void
    {
        $url = $this->resolveUrl($url);
        $dispatcher = $this->makeDispatcher();

        try {
            $request = $this->buildRequest($url, $method, $data);
            $response = $dispatcher->execute($request);
            $this->requestSession = $request['session'];
            if ($this->retainFlashMessages && $this->flashMessages) {
                $_SESSION['Flash'] = $this->flashMessages;
                $this->requestSession->write($_SESSION);
            }
            $this->response = $response;
        } catch (PHPUnitException | DatabaseException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->exception = $e;
            // Simulate the global exception handler being invoked.
            $this->handleError($e);
        }
    }

    /**
     * Resolve the provided URL into a string.
     *
     * @param array|string $url The URL array/string to resolve.
     * @return string
     * @since 5.1.0
     */
    public function resolveUrl(array|string $url): string
    {
        // If we need to resolve a Route URL but there are no routes, load routes.
        if (is_array($url) && Router::getRouteCollection()->routes() === []) {
            return $this->resolveRoute($url);
        }

        return Router::url($url);
    }

    /**
     * Convert a URL array into a string URL via routing.
     *
     * @param array $url The url to resolve
     * @return string
     * @since 5.1.0
     */
    protected function resolveRoute(array $url): string
    {
        $app = $this->createApp();

        // Simulate application bootstrap and route loading.
        // We need both to ensure plugins are loaded.
        $app->bootstrap();
        if ($app instanceof PluginApplicationInterface) {
            $app->pluginBootstrap();
        }
        $builder = Router::createRouteBuilder('/');

        if ($app instanceof RoutingApplicationInterface) {
            $app->routes($builder);
        }
        if ($app instanceof PluginApplicationInterface) {
            $app->pluginRoutes($builder);
        }

        $out = Router::url($url);
        Router::resetRoutes();

        return $out;
    }

    /**
     * Get the correct dispatcher instance.
     *
     * @return \Cake\TestSuite\MiddlewareDispatcher A dispatcher instance
     */
    protected function makeDispatcher(): MiddlewareDispatcher
    {
        EventManager::instance()->on('Controller.initialize', $this->controllerSpy(...));
        $app = $this->createApp();
        assert($app instanceof HttpApplicationInterface);

        return new MiddlewareDispatcher($app);
    }

    /**
     * Adds additional event spies to the controller/view event manager.
     *
     * @param \Cake\Event\EventInterface $event A dispatcher event.
     * @param \Cake\Controller\Controller|null $controller Controller instance.
     * @return void
     */
    public function controllerSpy(EventInterface $event, ?Controller $controller = null): void
    {
        if (!$controller) {
            $controller = $event->getSubject();
            assert($controller instanceof Controller);
        }
        $this->controller = $controller;
        $events = $controller->getEventManager();
        $flashCapture = function (EventInterface $event): void {
            if (!$this->retainFlashMessages) {
                return;
            }
            $controller = $event->getSubject();
            $this->flashMessages = Hash::merge(
                $this->flashMessages,
                $controller->getRequest()->getSession()->read('Flash'),
            );
        };
        $events->on('Controller.beforeRedirect', $flashCapture, ['priority' => -100]);
        $events->on('Controller.beforeRender', $flashCapture, ['priority' => -100]);
        $events->on('View.beforeRender', function ($event, $viewFile): void {
            if (!$this->viewName) {
                $this->viewName = $viewFile;
            }
        });
        $events->on('View.beforeLayout', function ($event, $viewFile): void {
            $this->layoutName = $viewFile;
        });
    }

    /**
     * Attempts to render an error response for a given exception.
     *
     * This method will attempt to use the configured exception renderer.
     * If that class does not exist, the built-in renderer will be used.
     *
     * @param \Throwable $exception Exception to handle.
     * @return void
     */
    protected function handleError(Throwable $exception): void
    {
        $class = Configure::read('Error.exceptionRenderer');
        if (!$class || !class_exists($class)) {
            $class = WebExceptionRenderer::class;
        }
        /** @var \Cake\Error\Renderer\WebExceptionRenderer $instance */
        $instance = new $class($exception);
        $this->response = $instance->render();
    }

    /**
     * Creates a request object with the configured options and parameters.
     *
     * @param string $url The URL
     * @param string $method The HTTP method
     * @param array|string $data The request data.
     * @return array The request context
     */
    protected function buildRequest(string $url, string $method, array|string $data = []): array
    {
        $sessionConfig = (array)Configure::read('Session') + [
            'defaults' => 'php',
        ];
        $session = Session::create($sessionConfig);
        [$url, $query, $hostInfo] = $this->url($url);
        $tokenUrl = $url;

        if ($query) {
            $tokenUrl .= '?' . $query;
        }

        parse_str($query, $queryData);

        $env = [
            'REQUEST_METHOD' => $method,
            'QUERY_STRING' => $query,
            'REQUEST_URI' => $url,
        ];
        if (!empty($hostInfo['https'])) {
            $env['HTTPS'] = 'on';
        }
        if (isset($hostInfo['host'])) {
            $env['HTTP_HOST'] = $hostInfo['host'];
        }
        if (isset($this->request['headers'])) {
            foreach ($this->request['headers'] as $k => $v) {
                $name = strtoupper(str_replace('-', '_', $k));
                if (!in_array($name, ['CONTENT_LENGTH', 'CONTENT_TYPE'], true)) {
                    $name = 'HTTP_' . $name;
                }
                $env[$name] = $v;
            }
            unset($this->request['headers']);
        }
        $props = [
            'url' => $url,
            'session' => $session,
            'query' => $queryData,
            'files' => [],
            'environment' => $env,
        ];

        if (is_string($data)) {
            $props['input'] = $data;
        } elseif (
            is_array($data) &&
            isset($props['environment']['CONTENT_TYPE']) &&
            $props['environment']['CONTENT_TYPE'] === 'application/x-www-form-urlencoded'
        ) {
            $props['input'] = http_build_query($data);
        } else {
            if ($method !== 'GET' || $data !== []) {
                $data = $this->addTokens($tokenUrl, $data, $method);
            }
            $props['post'] = $this->castToString($data);
        }

        $props['cookies'] = $this->cookie;
        $session->write($this->session);

        return Hash::merge($props, $this->request);
    }

    /**
     * Add the CSRF and FormProtectionComponent tokens if necessary.
     *
     * @param string $url The URL the form is being submitted on.
     * @param array $data The request body data.
     * @param string $method The request method.
     * @return array The request body with tokens added.
     */
    protected function addTokens(string $url, array $data, string $method): array
    {
        if ($this->securityToken === true) {
            $fields = array_diff_key($data, array_flip($this->unlockedFields));

            $keys = array_map(function (int|string $field) {
                return preg_replace('/(\.\d+)+$/', '', (string)$field);
            }, array_keys(Hash::flatten($fields)));

            $formProtector = new FormProtector(['unlockedFields' => $this->unlockedFields]);
            foreach ($keys as $field) {
                $formProtector->addField($field);
            }
            $tokenData = $formProtector->buildTokenData($url, 'cli');

            $data['_Token'] = $tokenData;

            /** @see \Cake\Form\FormProtector::extractToken() */
            if (Configure::read('debug')) {
                $data['_Token']['debug'] = 'FormProtector debug data would be added here';
            } elseif (isset($data['_Token']['debug'])) {
                unset($data['_Token']['debug']);
            }
        }

        if ($this->csrfToken === true) {
            $middleware = new CsrfProtectionMiddleware();
            if (!isset($this->cookie[$this->csrfKeyName]) && !isset($this->session[$this->csrfKeyName])) {
                $token = $middleware->createToken();
            } elseif (isset($this->cookie[$this->csrfKeyName])) {
                $token = $this->cookie[$this->csrfKeyName];
            } else {
                $token = $this->session[$this->csrfKeyName];
            }

            // Add the token to both the session and cookie to cover
            // both types of CSRF tokens. We generate the token with the cookie
            // middleware as cookie tokens will be accepted by session csrf, but not
            // the inverse.
            $this->session[$this->csrfKeyName] = $token;
            $this->cookie[$this->csrfKeyName] = $token;
            if (!isset($data['_csrfToken']) && !in_array($method, ['GET', 'OPTIONS'])) {
                $data['_csrfToken'] = $token;
            }
        }

        return $data;
    }

    /**
     * Recursively casts all data to string as that is how data would be POSTed in
     * the real world
     *
     * @param array $data POST data
     * @return array
     */
    protected function castToString(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $data[$key] = $value === false ? '0' : (string)$value;

                continue;
            }

            if (is_array($value)) {
                $looksLikeFile = isset($value['error'], $value['tmp_name'], $value['size']);
                if ($looksLikeFile) {
                    continue;
                }

                $data[$key] = $this->castToString($value);
            }
        }

        return $data;
    }

    /**
     * Creates a valid request url and parameter array more like Request::_url()
     *
     * @param string $url The URL
     * @return array Qualified URL, the query parameters, and host data
     */
    protected function url(string $url): array
    {
        $uri = new Uri($url);
        $path = $uri->getPath();
        $query = $uri->getQuery();

        $hostData = [];
        if ($uri->getHost()) {
            $hostData['host'] = $uri->getHost();
        }
        if ($uri->getScheme()) {
            $hostData['https'] = $uri->getScheme() === 'https';
        }

        return [$path, $query, $hostData];
    }

    /**
     * Get the response body as string
     *
     * @return string The response body.
     */
    protected function getBodyAsString(): string
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert content.');
        }

        return (string)$this->response->getBody();
    }

    /**
     * Fetches a view variable by name.
     *
     * If the view variable does not exist, null will be returned.
     *
     * @param string $name The view variable to get.
     * @return mixed The view variable if set.
     */
    public function viewVariable(string $name): mixed
    {
        return $this->controller?->viewBuilder()->getVar($name);
    }

    /**
     * Asserts that the response status code is in the 2xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseOk(string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new StatusOk($this->response), $verboseMessage);
    }

    /**
     * Asserts that the response status code is in the 2xx/3xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseSuccess(string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new StatusSuccess($this->response), $verboseMessage);
    }

    /**
     * Asserts that the response status code is in the 4xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseError(string $message = ''): void
    {
        $this->assertThat(null, new StatusError($this->response), $message);
    }

    /**
     * Asserts that the response status code is in the 5xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseFailure(string $message = ''): void
    {
        $this->assertThat(null, new StatusFailure($this->response), $message);
    }

    /**
     * Asserts a specific response status code.
     *
     * @param int $code Status code to assert.
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseCode(int $code, string $message = ''): void
    {
        $this->assertThat($code, new StatusCode($this->response), $message);
    }

    /**
     * Asserts that the Location header is correct.
     *
     * This method normalizes both the expected URL and Location header value to absolute URLs
     * for comparison. This accommodates differences between authentication plugins and core
     * framework behavior, where some parts return relative URLs and others return absolute URLs.
     *
     * @param array|string|null $url The URL you expected the client to go to. This
     *   can either be a string URL or an array compatible with Router::url(). Use null to
     *   simply check for the existence of this header.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirect(array|string|null $url = null, string $message = ''): void
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->response, 'Location'), $verboseMessage);

        if ($url) {
            // Normalize both URLs to absolute for comparison
            $expectedUrl = Router::url($url, true);
            $actualUrl = Router::url($this->response->getHeaderLine('Location'), true);

            // Create a response with the normalized URL for proper error messages
            $tempResponse = $this->response->withHeader('Location', $actualUrl);

            $this->assertThat(
                $expectedUrl,
                new HeaderEquals($tempResponse, 'Location'),
                $verboseMessage,
            );
        }
    }

    /**
     * Assert whether the response is redirecting back to the previous location.
     *
     * @param int|null $code Specific status code to validate against, defaults to success (2xx-3xx) range.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirectBack(?int $code = null, string $message = ''): void
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->response, 'Location'), $verboseMessage);
        if ($code !== null) {
            $this->assertThat($code, new StatusCode($this->response), $message);
        } else {
            $this->assertThat(null, new StatusSuccess($this->response), $verboseMessage);
        }

        $url = $this->request['url'] ?? null;
        if (!$url) {
            $this->fail('No `url` set in request, cannot assert header.');
        }

        $this->assertThat(
            Router::url($url, true),
            new HeaderEquals($this->response, 'Location'),
            $verboseMessage,
        );
    }

    /**
     * Assert whether the response is redirecting back to the referer.
     *
     * @param int|null $code Specific status code to validate against, defaults to success (2xx-3xx) range.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirectBackToReferer(?int $code = null, string $message = ''): void
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->response, 'Location'), $verboseMessage);
        if ($code !== null) {
            $this->assertThat($code, new StatusCode($this->response), $message);
        } else {
            $this->assertThat(null, new StatusSuccess($this->response), $verboseMessage);
        }

        $referer = $this->request['environment']['HTTP_REFERER'] ?? null;
        if (!$referer) {
            $this->fail('No `HTTP_REFERER` set in request environment, cannot assert header.');
        }

        $this->assertThat(
            Router::url($referer, true),
            new HeaderEquals($this->response, 'Location'),
            $verboseMessage,
        );
    }

    /**
     * Asserts that the Location header is correct. Comparison is made against exactly the URL provided.
     *
     * @param array|string|null $url The URL you expected the client to go to. This
     *   can either be a string URL or an array compatible with Router::url(). Use null to
     *   simply check for the existence of this header.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirectEquals(array|string|null $url = null, string $message = ''): void
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->response, 'Location'), $verboseMessage);

        if ($url) {
            // Normalize both URLs to absolute for comparison
            $expectedUrl = Router::url($url, true);
            $actualUrl = Router::url($this->response->getHeaderLine('Location'), true);

            // Create a response with the normalized URL for proper error messages
            $tempResponse = $this->response->withHeader('Location', $actualUrl);

            $this->assertThat(
                $expectedUrl,
                new HeaderEquals($tempResponse, 'Location'),
                $verboseMessage,
            );
        }
    }

    /**
     * Asserts that the Location header contains a substring
     *
     * @param string $url The URL you expected the client to go to.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirectContains(string $url, string $message = ''): void
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->response, 'Location'), $verboseMessage);
        $this->assertThat($url, new HeaderContains($this->response, 'Location'), $verboseMessage);
    }

    /**
     * Asserts that the Location header does not contain a substring
     *
     * @param string $url The URL you expected the client to go to.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirectNotContains(string $url, string $message = ''): void
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->response, 'Location'), $verboseMessage);
        $this->assertThat($url, new HeaderNotContains($this->response, 'Location'), $verboseMessage);
    }

    /**
     * Asserts that the Location header is not set.
     *
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertNoRedirect(string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderNotSet($this->response, 'Location'), $verboseMessage);
    }

    /**
     * Asserts response headers
     *
     * @param string $header The header to check
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertHeader(string $header, string $content, string $message = ''): void
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->response, $header), $verboseMessage);
        $this->assertThat($content, new HeaderEquals($this->response, $header), $verboseMessage);
    }

    /**
     * Asserts response header contains a string
     *
     * @param string $header The header to check
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertHeaderContains(string $header, string $content, string $message = ''): void
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->response, $header), $verboseMessage);
        $this->assertThat($content, new HeaderContains($this->response, $header), $verboseMessage);
    }

    /**
     * Asserts response header does not contain a string
     *
     * @param string $header The header to check
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertHeaderNotContains(string $header, string $content, string $message = ''): void
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->response, $header), $verboseMessage);
        $this->assertThat($content, new HeaderNotContains($this->response, $header), $verboseMessage);
    }

    /**
     * Asserts content type
     *
     * @param string $type The content-type to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertContentType(string $type, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($type, new ContentType($this->response), $verboseMessage);
    }

    /**
     * Asserts content in the response body equals.
     *
     * @param mixed $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseEquals(mixed $content, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($content, new BodyEquals($this->response), $verboseMessage);
    }

    /**
     * Asserts content in the response body not equals.
     *
     * @param mixed $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseNotEquals(mixed $content, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($content, new BodyNotEquals($this->response), $verboseMessage);
    }

    /**
     * Asserts content exists in the response body.
     *
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @param bool $ignoreCase A flag to check whether we should ignore case or not.
     * @return void
     */
    public function assertResponseContains(string $content, string $message = '', bool $ignoreCase = false): void
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert content.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($content, new BodyContains($this->response, $ignoreCase), $verboseMessage);
    }

    /**
     * Asserts content does not exist in the response body.
     *
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @param bool $ignoreCase A flag to check whether we should ignore case or not.
     * @return void
     */
    public function assertResponseNotContains(string $content, string $message = '', bool $ignoreCase = false): void
    {
        if (!$this->response) {
            $this->fail('No response set, cannot assert content.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($content, new BodyNotContains($this->response, $ignoreCase), $verboseMessage);
    }

    /**
     * Asserts that the response body matches a given regular expression.
     *
     * @param string $pattern The pattern to compare against.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseRegExp(string $pattern, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($pattern, new BodyRegExp($this->response), $verboseMessage);
    }

    /**
     * Asserts that the response body does not match a given regular expression.
     *
     * @param string $pattern The pattern to compare against.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseNotRegExp(string $pattern, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        if ($this->isDebug()) {
            $verboseMessage .= $this->responseBody();
        }
        $this->assertThat($pattern, new BodyNotRegExp($this->response), $verboseMessage);
    }

    /**
     * Assert response content is not empty.
     *
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseNotEmpty(string $message = ''): void
    {
        if ($this->isDebug()) {
            $message .= $this->responseBody();
        }
        $this->assertThat(null, new BodyNotEmpty($this->response), $message);
    }

    /**
     * Assert response content is empty.
     *
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseEmpty(string $message = ''): void
    {
        if ($this->isDebug()) {
            $message .= $this->responseBody();
        }
        $this->assertThat(null, new BodyEmpty($this->response), $message);
    }

    /**
     * Asserts that the search string was in the template name.
     *
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertTemplate(string $content, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($content, new TemplateFileEquals($this->viewName), $verboseMessage);
    }

    /**
     * Asserts that the search string was in the layout name.
     *
     * @param string $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertLayout(string $content, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($content, new LayoutFileEquals($this->layoutName), $verboseMessage);
    }

    /**
     * Asserts session contents
     *
     * @param mixed $expected The expected contents.
     * @param string $path The session data path. Uses Hash::get() compatible notation
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertSession(mixed $expected, string $path, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($expected, new SessionEquals($path), $verboseMessage);
    }

    /**
     * Asserts session key exists.
     *
     * @param string $path The session data path. Uses Hash::get() compatible notation.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertSessionHasKey(string $path, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($path, new SessionHasKey($path), $verboseMessage);
    }

    /**
     * Asserts a session key does not exist.
     *
     * @param string $path The session data path. Uses Hash::get() compatible notation.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertSessionNotHasKey(string $path, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($path, $this->logicalNot(new SessionHasKey($path)), $verboseMessage);
    }

    /**
     * Asserts a flash message was set
     *
     * @param string $expected Expected message
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertFlashMessage(string $expected, string $key = 'flash', string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($expected, new FlashParamEquals($this->requestSession, $key, 'message'), $verboseMessage);
    }

    /**
     * Asserts a flash message was set at a certain index
     *
     * @param int $at Flash index
     * @param string $expected Expected message
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertFlashMessageAt(int $at, string $expected, string $key = 'flash', string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(
            $expected,
            new FlashParamEquals($this->requestSession, $key, 'message', $at),
            $verboseMessage,
        );
    }

    /**
     * Asserts a flash message contains a substring
     *
     * @param string $expected Expected substring in message
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @param bool $ignoreCase Whether to ignore case
     * @return void
     */
    public function assertFlashMessageContains(
        string $expected,
        string $key = 'flash',
        string $message = '',
        bool $ignoreCase = false,
    ): void {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(
            $expected,
            new FlashParamContains($this->requestSession, $key, 'message', null, $ignoreCase),
            $verboseMessage,
        );
    }

    /**
     * Asserts a flash message contains a substring at a certain index
     *
     * @param int $at Flash index
     * @param string $expected Expected substring in message
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @param bool $ignoreCase Whether to ignore case
     * @return void
     */
    public function assertFlashMessageContainsAt(
        int $at,
        string $expected,
        string $key = 'flash',
        string $message = '',
        bool $ignoreCase = false,
    ): void {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(
            $expected,
            new FlashParamContains($this->requestSession, $key, 'message', $at, $ignoreCase),
            $verboseMessage,
        );
    }

    /**
     * Asserts a flash element was set
     *
     * @param string $expected Expected element name
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertFlashElement(string $expected, string $key = 'flash', string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(
            $expected,
            new FlashParamEquals($this->requestSession, $key, 'element'),
            $verboseMessage,
        );
    }

    /**
     * Asserts a flash element was set at a certain index
     *
     * @param int $at Flash index
     * @param string $expected Expected element name
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertFlashElementAt(int $at, string $expected, string $key = 'flash', string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(
            $expected,
            new FlashParamEquals($this->requestSession, $key, 'element', $at),
            $verboseMessage,
        );
    }

    /**
     * Asserts cookie values
     *
     * @param mixed $expected The expected contents.
     * @param string $name The cookie name.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertCookie(mixed $expected, string $name, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($name, new CookieSet($this->response), $verboseMessage);
        $this->assertThat($expected, new CookieEquals($this->response, $name), $verboseMessage);
    }

    /**
     * Asserts that a cookie is set.
     *
     * Useful when you're working with cookies that have obfuscated values
     * but the cookie being set is important.
     *
     * @param string $name The cookie name.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertCookieIsSet(string $name, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($name, new CookieSet($this->response), $verboseMessage);
    }

    /**
     * Asserts a cookie has not been set in the response
     *
     * @param string $cookie The cookie name to check
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertCookieNotSet(string $cookie, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($cookie, new CookieNotSet($this->response), $verboseMessage);
    }

    /**
     * Disable the error handler middleware.
     *
     * By using this function, exceptions are no longer caught by the ErrorHandlerMiddleware
     * and are instead re-thrown by the TestExceptionRenderer. This can be helpful
     * when trying to diagnose/debug unexpected failures in test cases.
     *
     * @return void
     */
    public function disableErrorHandlerMiddleware(): void
    {
        Configure::write('Error.exceptionRenderer', TestExceptionRenderer::class);
    }

    /**
     * Asserts cookie values which are encrypted by the
     * CookieComponent.
     *
     * The difference from assertCookie() is this decrypts the cookie
     * value like the CookieComponent for this assertion.
     *
     * @param mixed $expected The expected contents.
     * @param string $name The cookie name.
     * @param string $encrypt Encryption mode to use.
     * @param string|null $key Encryption key used. Defaults
     *   to Security.salt.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     * @see \Cake\Utility\CookieCryptTrait::encrypt()
     */
    public function assertCookieEncrypted(
        mixed $expected,
        string $name,
        string $encrypt = 'aes',
        ?string $key = null,
        string $message = '',
    ): void {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($name, new CookieSet($this->response), $verboseMessage);

        $this->cookieEncryptionKey = $key;
        $this->assertThat(
            $expected,
            new CookieEncryptedEquals($this->response, $name, $encrypt, $this->getCookieEncryptionKey()),
        );
    }

    /**
     * Asserts that a file with the given name was sent in the response
     *
     * @param string $expected The absolute file path that should be sent in the response.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertFileResponse(string $expected, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new FileSent($this->response), $verboseMessage);
        $this->assertThat($expected, new FileSentAs($this->response), $verboseMessage);

        if (!$this->response) {
            return;
        }
        $this->response->getBody()->close();
    }

    /**
     * Inspect controller to extract possible causes of the failed assertion
     *
     * @param string $message Original message to use as a base
     * @return string
     */
    protected function extractVerboseMessage(string $message): string
    {
        if ($this->exception instanceof Exception) {
            $message .= $this->extractExceptionMessage($this->exception);
        }
        if ($this->controller === null) {
            return $message;
        }
        $error = $this->controller->viewBuilder()->getVar('error');
        if ($error instanceof Exception) {
            $message .= $this->extractExceptionMessage($this->viewVariable('error'));
        }

        return $message;
    }

    /**
     * Extract verbose message for existing exception
     *
     * @param \Exception $exception Exception to extract
     * @return string
     */
    protected function extractExceptionMessage(Exception $exception): string
    {
        $exceptions = [$exception];
        $previous = $exception->getPrevious();
        while ($previous !== null) {
            $exceptions[] = $previous;
            $previous = $previous->getPrevious();
        }
        $message = PHP_EOL;
        foreach ($exceptions as $i => $error) {
            if ($i === 0) {
                $message .= sprintf('Possibly related to `%s`: "%s"', $error::class, $error->getMessage());
                $message .= PHP_EOL;
            } else {
                $message .= sprintf('Caused by `%s`: "%s"', $error::class, $error->getMessage());
                $message .= PHP_EOL;
            }
            $message .= $error->getTraceAsString();
            $message .= PHP_EOL;
        }

        return $message;
    }

    /**
     * @return \Cake\TestSuite\TestSession
     */
    protected function getSession(): TestSession
    {
        return new TestSession($_SESSION);
    }

    /**
     * Checks if debug flag is set.
     *
     * Flag is set via `--debug`.
     * Allows additional stuff like non-mocking when enabling debug. Or displaying of response body.
     *
     * @return bool Success
     */
    protected function isDebug(): bool
    {
        return !empty($_SERVER['argv']) && in_array('--debug', $_SERVER['argv'], true);
    }

    /**
     * Debug content of response body.
     *
     * @return string
     */
    protected function responseBody(): string
    {
        return PHP_EOL . '------' . PHP_EOL . $this->response->getBody() . PHP_EOL . '------' . PHP_EOL;
    }
}
