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
use Cake\Core\TestSuite\ContainerStubTrait;
use Cake\Database\Exception\DatabaseException;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Form\FormProtector;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\Session;
use Cake\Routing\Router;
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
    protected $_request = [];

    /**
     * The response for the most recent request.
     *
     * @var \Psr\Http\Message\ResponseInterface|null
     */
    protected $_response;

    /**
     * The exception being thrown if the case.
     *
     * @var \Throwable|null
     */
    protected $_exception;

    /**
     * Session data to use in the next request.
     *
     * @var array
     */
    protected $_session = [];

    /**
     * Cookie data to use in the next request.
     *
     * @var array
     */
    protected $_cookie = [];

    /**
     * The controller used in the last request.
     *
     * @var \Cake\Controller\Controller|null
     */
    protected $_controller;

    /**
     * The last rendered view
     *
     * @var string
     */
    protected $_viewName;

    /**
     * The last rendered layout
     *
     * @var string
     */
    protected $_layoutName;

    /**
     * The session instance from the last request
     *
     * @var \Cake\Http\Session
     */
    protected $_requestSession;

    /**
     * Boolean flag for whether the request should have
     * a SecurityComponent token added.
     *
     * @var bool
     */
    protected $_securityToken = false;

    /**
     * Boolean flag for whether the request should have
     * a CSRF token added.
     *
     * @var bool
     */
    protected $_csrfToken = false;

    /**
     * Boolean flag for whether the request should re-store
     * flash messages
     *
     * @var bool
     */
    protected $_retainFlashMessages = false;

    /**
     * Stored flash messages before render
     *
     * @var array
     */
    protected $_flashMessages = [];

    /**
     * @var string|null
     */
    protected $_cookieEncryptionKey;

    /**
     * List of fields that are excluded from field validation.
     *
     * @var array<string>
     */
    protected $_unlockedFields = [];

    /**
     * The name that will be used when retrieving the csrf token.
     *
     * @var string
     */
    protected $_csrfKeyName = 'csrfToken';

    /**
     * Clears the state used for requests.
     *
     * @after
     * @return void
     * @psalm-suppress PossiblyNullPropertyAssignmentValue
     */
    public function cleanup(): void
    {
        $this->_request = [];
        $this->_session = [];
        $this->_cookie = [];
        $this->_response = null;
        $this->_exception = null;
        $this->_controller = null;
        $this->_viewName = null;
        $this->_layoutName = null;
        $this->_requestSession = null;
        $this->_securityToken = false;
        $this->_csrfToken = false;
        $this->_retainFlashMessages = false;
        $this->_flashMessages = [];
    }

    /**
     * Calling this method will enable a SecurityComponent
     * compatible token to be added to request data. This
     * lets you easily test actions protected by SecurityComponent.
     *
     * @return void
     */
    public function enableSecurityToken(): void
    {
        $this->_securityToken = true;
    }

    /**
     * Set list of fields that are excluded from field validation.
     *
     * @param array<string> $unlockedFields List of fields that are excluded from field validation.
     * @return void
     */
    public function setUnlockedFields(array $unlockedFields = []): void
    {
        $this->_unlockedFields = $unlockedFields;
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
        $this->_csrfToken = true;
        $this->_csrfKeyName = $cookieName;
    }

    /**
     * Calling this method will re-store flash messages into the test session
     * after being removed by the FlashHelper
     *
     * @return void
     */
    public function enableRetainFlashMessages(): void
    {
        $this->_retainFlashMessages = true;
    }

    /**
     * Configures the data for the *next* request.
     *
     * This data is cleared in the tearDown() method.
     *
     * You can call this method multiple times to append into
     * the current state.
     * Sub-keys like 'headers' will be reset, though.
     *
     * @param array $data The request data to use.
     * @return void
     */
    public function configRequest(array $data): void
    {
        $this->_request = $data + $this->_request;
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
        $this->_session = $data + $this->_session;
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
     * @param mixed $value The value of the cookie.
     * @return void
     */
    public function cookie(string $name, $value): void
    {
        $this->_cookie[$name] = $value;
    }

    /**
     * Returns the encryption key to be used.
     *
     * @return string
     */
    protected function _getCookieEncryptionKey(): string
    {
        return $this->_cookieEncryptionKey ?? Security::getSalt();
    }

    /**
     * Sets a encrypted request cookie for future requests.
     *
     * The difference from cookie() is this encrypts the cookie
     * value like the CookieComponent.
     *
     * @param string $name The cookie name to use.
     * @param mixed $value The value of the cookie.
     * @param string|false $encrypt Encryption mode to use.
     * @param string|null $key Encryption key used. Defaults
     *   to Security.salt.
     * @return void
     * @see \Cake\Utility\CookieCryptTrait::_encrypt()
     */
    public function cookieEncrypted(string $name, $value, $encrypt = 'aes', $key = null): void
    {
        $this->_cookieEncryptionKey = $key;
        $this->_cookie[$name] = $this->_encrypt($value, $encrypt);
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
    public function get($url): void
    {
        $this->_sendRequest($url, 'GET');
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
    public function post($url, $data = []): void
    {
        $this->_sendRequest($url, 'POST', $data);
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
    public function patch($url, $data = []): void
    {
        $this->_sendRequest($url, 'PATCH', $data);
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
    public function put($url, $data = []): void
    {
        $this->_sendRequest($url, 'PUT', $data);
    }

    /**
     * Performs a DELETE request using the current request data.
     *
     * The response of the dispatched request will be stored as
     * a property. You can use various assert methods to check the
     * response.
     *
     * @param array|string $url The URL to request.
     * @return void
     */
    public function delete($url): void
    {
        $this->_sendRequest($url, 'DELETE');
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
    public function head($url): void
    {
        $this->_sendRequest($url, 'HEAD');
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
    public function options($url): void
    {
        $this->_sendRequest($url, 'OPTIONS');
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
    protected function _sendRequest($url, $method, $data = []): void
    {
        $dispatcher = $this->_makeDispatcher();
        $url = $dispatcher->resolveUrl($url);

        try {
            $request = $this->_buildRequest($url, $method, $data);
            $response = $dispatcher->execute($request);
            $this->_requestSession = $request['session'];
            if ($this->_retainFlashMessages && $this->_flashMessages) {
                $this->_requestSession->write('Flash', $this->_flashMessages);
            }
            $this->_response = $response;
        } catch (PHPUnitException | DatabaseException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->_exception = $e;
            // Simulate the global exception handler being invoked.
            $this->_handleError($e);
        }
    }

    /**
     * Get the correct dispatcher instance.
     *
     * @return \Cake\TestSuite\MiddlewareDispatcher A dispatcher instance
     */
    protected function _makeDispatcher(): MiddlewareDispatcher
    {
        EventManager::instance()->on('Controller.initialize', [$this, 'controllerSpy']);
        /** @var \Cake\Core\HttpApplicationInterface $app */
        $app = $this->createApp();

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
            /** @var \Cake\Controller\Controller $controller */
            $controller = $event->getSubject();
        }
        $this->_controller = $controller;
        $events = $controller->getEventManager();
        $flashCapture = function (EventInterface $event): void {
            if (!$this->_retainFlashMessages) {
                return;
            }
            $controller = $event->getSubject();
            $this->_flashMessages = Hash::merge(
                $this->_flashMessages,
                $controller->getRequest()->getSession()->read('Flash')
            );
        };
        $events->on('Controller.beforeRedirect', ['priority' => -100], $flashCapture);
        $events->on('Controller.beforeRender', ['priority' => -100], $flashCapture);
        $events->on('View.beforeRender', function ($event, $viewFile): void {
            if (!$this->_viewName) {
                $this->_viewName = $viewFile;
            }
        });
        $events->on('View.beforeLayout', function ($event, $viewFile): void {
            $this->_layoutName = $viewFile;
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
    protected function _handleError(Throwable $exception): void
    {
        $class = Configure::read('Error.exceptionRenderer');
        if (empty($class) || !class_exists($class)) {
            $class = WebExceptionRenderer::class;
        }
        /** @var \Cake\Error\Renderer\WebExceptionRenderer $instance */
        $instance = new $class($exception);
        $this->_response = $instance->render();
    }

    /**
     * Creates a request object with the configured options and parameters.
     *
     * @param string $url The URL
     * @param string $method The HTTP method
     * @param array|string $data The request data.
     * @return array The request context
     */
    protected function _buildRequest(string $url, $method, $data = []): array
    {
        $sessionConfig = (array)Configure::read('Session') + [
            'defaults' => 'php',
        ];
        $session = Session::create($sessionConfig);
        [$url, $query, $hostInfo] = $this->_url($url);
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
        if (!empty($hostInfo['ssl'])) {
            $env['HTTPS'] = 'on';
        }
        if (isset($hostInfo['host'])) {
            $env['HTTP_HOST'] = $hostInfo['host'];
        }
        if (isset($this->_request['headers'])) {
            foreach ($this->_request['headers'] as $k => $v) {
                $name = strtoupper(str_replace('-', '_', $k));
                if (!in_array($name, ['CONTENT_LENGTH', 'CONTENT_TYPE'], true)) {
                    $name = 'HTTP_' . $name;
                }
                $env[$name] = $v;
            }
            unset($this->_request['headers']);
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
            $data = $this->_addTokens($tokenUrl, $data);
            $props['post'] = $this->_castToString($data);
        }

        $props['cookies'] = $this->_cookie;
        $session->write($this->_session);

        return Hash::merge($props, $this->_request);
    }

    /**
     * Add the CSRF and Security Component tokens if necessary.
     *
     * @param string $url The URL the form is being submitted on.
     * @param array $data The request body data.
     * @return array The request body with tokens added.
     */
    protected function _addTokens(string $url, array $data): array
    {
        if ($this->_securityToken === true) {
            $fields = array_diff_key($data, array_flip($this->_unlockedFields));

            $keys = array_map(function ($field) {
                return preg_replace('/(\.\d+)+$/', '', $field);
            }, array_keys(Hash::flatten($fields)));

            $formProtector = new FormProtector(['unlockedFields' => $this->_unlockedFields]);
            foreach ($keys as $field) {
                $formProtector->addField($field);
            }
            $tokenData = $formProtector->buildTokenData($url, 'cli');

            $data['_Token'] = $tokenData;
            $data['_Token']['debug'] = 'FormProtector debug data would be added here';
        }

        if ($this->_csrfToken === true) {
            $middleware = new CsrfProtectionMiddleware();
            if (!isset($this->_cookie[$this->_csrfKeyName]) && !isset($this->_session[$this->_csrfKeyName])) {
                $token = $middleware->createToken();
            } elseif (isset($this->_cookie[$this->_csrfKeyName])) {
                $token = $this->_cookie[$this->_csrfKeyName];
            } else {
                $token = $this->_session[$this->_csrfKeyName];
            }

            // Add the token to both the session and cookie to cover
            // both types of CSRF tokens. We generate the token with the cookie
            // middleware as cookie tokens will be accepted by session csrf, but not
            // the inverse.
            $this->_session[$this->_csrfKeyName] = $token;
            $this->_cookie[$this->_csrfKeyName] = $token;
            if (!isset($data['_csrfToken'])) {
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
    protected function _castToString(array $data): array
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

                $data[$key] = $this->_castToString($value);
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
    protected function _url(string $url): array
    {
        $uri = new Uri($url);
        $path = $uri->getPath();
        $query = $uri->getQuery();

        $hostData = [];
        if ($uri->getHost()) {
            $hostData['host'] = $uri->getHost();
        }
        if ($uri->getScheme()) {
            $hostData['ssl'] = $uri->getScheme() === 'https';
        }

        return [$path, $query, $hostData];
    }

    /**
     * Get the response body as string
     *
     * @return string The response body.
     */
    protected function _getBodyAsString(): string
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert content.');
        }

        return (string)$this->_response->getBody();
    }

    /**
     * Fetches a view variable by name.
     *
     * If the view variable does not exist, null will be returned.
     *
     * @param string $name The view variable to get.
     * @return mixed The view variable if set.
     */
    public function viewVariable(string $name)
    {
        return $this->_controller ? $this->_controller->viewBuilder()->getVar($name) : null;
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
        $this->assertThat(null, new StatusOk($this->_response), $verboseMessage);
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
        $this->assertThat(null, new StatusSuccess($this->_response), $verboseMessage);
    }

    /**
     * Asserts that the response status code is in the 4xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseError(string $message = ''): void
    {
        $this->assertThat(null, new StatusError($this->_response), $message);
    }

    /**
     * Asserts that the response status code is in the 5xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseFailure(string $message = ''): void
    {
        $this->assertThat(null, new StatusFailure($this->_response), $message);
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
        $this->assertThat($code, new StatusCode($this->_response), $message);
    }

    /**
     * Asserts that the Location header is correct. Comparison is made against a full URL.
     *
     * @param array|string|null $url The URL you expected the client to go to. This
     *   can either be a string URL or an array compatible with Router::url(). Use null to
     *   simply check for the existence of this header.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirect($url = null, $message = ''): void
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->_response, 'Location'), $verboseMessage);

        if ($url) {
            $this->assertThat(
                Router::url($url, true),
                new HeaderEquals($this->_response, 'Location'),
                $verboseMessage
            );
        }
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
    public function assertRedirectEquals($url = null, $message = '')
    {
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->_response, 'Location'), $verboseMessage);

        if ($url) {
            $this->assertThat(Router::url($url), new HeaderEquals($this->_response, 'Location'), $verboseMessage);
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
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->_response, 'Location'), $verboseMessage);
        $this->assertThat($url, new HeaderContains($this->_response, 'Location'), $verboseMessage);
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
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->_response, 'Location'), $verboseMessage);
        $this->assertThat($url, new HeaderNotContains($this->_response, 'Location'), $verboseMessage);
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
        $this->assertThat(null, new HeaderNotSet($this->_response, 'Location'), $verboseMessage);
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
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->_response, $header), $verboseMessage);
        $this->assertThat($content, new HeaderEquals($this->_response, $header), $verboseMessage);
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
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->_response, $header), $verboseMessage);
        $this->assertThat($content, new HeaderContains($this->_response, $header), $verboseMessage);
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
        if (!$this->_response) {
            $this->fail('No response set, cannot assert header.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->_response, $header), $verboseMessage);
        $this->assertThat($content, new HeaderNotContains($this->_response, $header), $verboseMessage);
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
        $this->assertThat($type, new ContentType($this->_response), $verboseMessage);
    }

    /**
     * Asserts content in the response body equals.
     *
     * @param mixed $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseEquals($content, $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($content, new BodyEquals($this->_response), $verboseMessage);
    }

    /**
     * Asserts content in the response body not equals.
     *
     * @param mixed $content The content to check for.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseNotEquals($content, $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($content, new BodyNotEquals($this->_response), $verboseMessage);
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
        if (!$this->_response) {
            $this->fail('No response set, cannot assert content.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($content, new BodyContains($this->_response, $ignoreCase), $verboseMessage);
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
        if (!$this->_response) {
            $this->fail('No response set, cannot assert content.');
        }

        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($content, new BodyNotContains($this->_response, $ignoreCase), $verboseMessage);
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
        $this->assertThat($pattern, new BodyRegExp($this->_response), $verboseMessage);
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
        $this->assertThat($pattern, new BodyNotRegExp($this->_response), $verboseMessage);
    }

    /**
     * Assert response content is not empty.
     *
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseNotEmpty(string $message = ''): void
    {
        $this->assertThat(null, new BodyNotEmpty($this->_response), $message);
    }

    /**
     * Assert response content is empty.
     *
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseEmpty(string $message = ''): void
    {
        $this->assertThat(null, new BodyEmpty($this->_response), $message);
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
        $this->assertThat($content, new TemplateFileEquals($this->_viewName), $verboseMessage);
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
        $this->assertThat($content, new LayoutFileEquals($this->_layoutName), $verboseMessage);
    }

    /**
     * Asserts session contents
     *
     * @param mixed $expected The expected contents.
     * @param string $path The session data path. Uses Hash::get() compatible notation
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertSession($expected, string $path, string $message = ''): void
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
        $this->assertThat($expected, new FlashParamEquals($this->_requestSession, $key, 'message'), $verboseMessage);
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
            new FlashParamEquals($this->_requestSession, $key, 'message', $at),
            $verboseMessage
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
            new FlashParamEquals($this->_requestSession, $key, 'element'),
            $verboseMessage
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
            new FlashParamEquals($this->_requestSession, $key, 'element', $at),
            $verboseMessage
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
    public function assertCookie($expected, string $name, string $message = ''): void
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($name, new CookieSet($this->_response), $verboseMessage);
        $this->assertThat($expected, new CookieEquals($this->_response, $name), $verboseMessage);
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
        $this->assertThat($cookie, new CookieNotSet($this->_response), $verboseMessage);
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
     * @see \Cake\Utility\CookieCryptTrait::_encrypt()
     */
    public function assertCookieEncrypted(
        $expected,
        string $name,
        string $encrypt = 'aes',
        ?string $key = null,
        string $message = ''
    ): void {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($name, new CookieSet($this->_response), $verboseMessage);

        $this->_cookieEncryptionKey = $key;
        $this->assertThat(
            $expected,
            new CookieEncryptedEquals($this->_response, $name, $encrypt, $this->_getCookieEncryptionKey())
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
        $this->assertThat(null, new FileSent($this->_response), $verboseMessage);
        $this->assertThat($expected, new FileSentAs($this->_response), $verboseMessage);

        if (!$this->_response) {
            return;
        }
        $this->_response->getBody()->close();
    }

    /**
     * Inspect controller to extract possible causes of the failed assertion
     *
     * @param string $message Original message to use as a base
     * @return string
     */
    protected function extractVerboseMessage(string $message): string
    {
        if ($this->_exception instanceof Exception) {
            $message .= $this->extractExceptionMessage($this->_exception);
        }
        if ($this->_controller === null) {
            return $message;
        }
        $error = $this->_controller->viewBuilder()->getVar('error');
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
        return PHP_EOL .
            sprintf('Possibly related to %s: "%s" ', get_class($exception), $exception->getMessage()) .
            PHP_EOL .
            $exception->getTraceAsString();
    }

    /**
     * @return \Cake\TestSuite\TestSession
     */
    protected function getSession(): TestSession
    {
        /** @psalm-suppress InvalidScalarArgument */
        return new TestSession($_SESSION);
    }
}
