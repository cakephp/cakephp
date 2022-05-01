<?php
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

use Cake\Core\Configure;
use Cake\Database\Exception as DatabaseException;
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
use Cake\TestSuite\Constraint\View\LayoutFileEquals;
use Cake\TestSuite\Constraint\View\TemplateFileEquals;
use Cake\TestSuite\Stub\TestExceptionRenderer;
use Cake\Utility\CookieCryptTrait;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use Cake\View\Helper\SecureFieldTokenTrait;
use Exception;
use Laminas\Diactoros\Uri;
use LogicException;
use PHPUnit\Exception as PhpunitException;

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
    use SecureFieldTokenTrait;

    /**
     * Track whether or not tests are run against
     * the PSR7 HTTP stack.
     *
     * @var bool
     */
    protected $_useHttpServer = false;

    /**
     * The customized application class name.
     *
     * @var string|null
     */
    protected $_appClass;

    /**
     * The customized application constructor arguments.
     *
     * @var array|null
     */
    protected $_appArgs;

    /**
     * The data used to build the next request.
     *
     * @var array
     */
    protected $_request = [];

    /**
     * The response for the most recent request.
     *
     * @var \Cake\Http\Response|null
     */
    protected $_response;

    /**
     * The exception being thrown if the case.
     *
     * @var \Exception|null
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
     * @var string|null
     */
    protected $_viewName;

    /**
     * The last rendered layout
     *
     * @var string|null
     */
    protected $_layoutName;

    /**
     * The session instance from the last request
     *
     * @var \Cake\Http\Session|null
     */
    protected $_requestSession;

    /**
     * Boolean flag for whether or not the request should have
     * a SecurityComponent token added.
     *
     * @var bool
     */
    protected $_securityToken = false;

    /**
     * Boolean flag for whether or not the request should have
     * a CSRF token added.
     *
     * @var bool
     */
    protected $_csrfToken = false;

    /**
     * Boolean flag for whether or not the request should re-store
     * flash messages
     *
     * @var bool
     */
    protected $_retainFlashMessages = false;

    /**
     * Stored flash messages before render
     *
     * @var array|null
     */
    protected $_flashMessages;

    /**
     * @var string|null
     */
    protected $_cookieEncryptionKey;

    /**
     * List of fields that are excluded from field validation.
     *
     * @var string[]
     */
    protected $_unlockedFields = [];

    /**
     * Auto-detect if the HTTP middleware stack should be used.
     *
     * @before
     * @return void
     */
    public function setupServer()
    {
        $namespace = Configure::read('App.namespace');
        $this->_useHttpServer = class_exists($namespace . '\Application');
    }

    /**
     * Clears the state used for requests.
     *
     * @after
     * @return void
     */
    public function cleanup()
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
        $this->_appClass = null;
        $this->_appArgs = null;
        $this->_securityToken = false;
        $this->_csrfToken = false;
        $this->_retainFlashMessages = false;
        $this->_useHttpServer = false;
    }

    /**
     * Toggle whether or not you want to use the HTTP Server stack.
     *
     * @param bool $enable Enable/disable the usage of the HTTP Stack.
     * @return void
     */
    public function useHttpServer($enable)
    {
        $this->_useHttpServer = (bool)$enable;
    }

    /**
     * Configure the application class to use in integration tests.
     *
     * Combined with `useHttpServer()` to customize the class name and constructor arguments
     * of your application class.
     *
     * @param string $class The application class name.
     * @param array|null $constructorArgs The constructor arguments for your application class.
     * @return void
     */
    public function configApplication($class, $constructorArgs)
    {
        $this->_appClass = $class;
        $this->_appArgs = $constructorArgs;
    }

    /**
     * Calling this method will enable a SecurityComponent
     * compatible token to be added to request data. This
     * lets you easily test actions protected by SecurityComponent.
     *
     * @return void
     */
    public function enableSecurityToken()
    {
        $this->_securityToken = true;
    }

    /**
     * Set list of fields that are excluded from field validation.
     *
     * @param string[] $unlockedFields List of fields that are excluded from field validation.
     * @return void
     */
    public function setUnlockedFields(array $unlockedFields = [])
    {
        $this->_unlockedFields = $unlockedFields;
    }

    /**
     * Calling this method will add a CSRF token to the request.
     *
     * Both the POST data and cookie will be populated when this option
     * is enabled. The default parameter names will be used.
     *
     * @return void
     */
    public function enableCsrfToken()
    {
        $this->_csrfToken = true;
    }

    /**
     * Calling this method will re-store flash messages into the test session
     * after being removed by the FlashHelper
     *
     * @return void
     */
    public function enableRetainFlashMessages()
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
     *
     * @param array $data The request data to use.
     * @return void
     */
    public function configRequest(array $data)
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
    public function session(array $data)
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
    public function cookie($name, $value)
    {
        $this->_cookie[$name] = $value;
    }

    /**
     * Returns the encryption key to be used.
     *
     * @return string
     */
    protected function _getCookieEncryptionKey()
    {
        if (isset($this->_cookieEncryptionKey)) {
            return $this->_cookieEncryptionKey;
        }

        return Security::getSalt();
    }

    /**
     * Sets a encrypted request cookie for future requests.
     *
     * The difference from cookie() is this encrypts the cookie
     * value like the CookieComponent.
     *
     * @param string $name The cookie name to use.
     * @param mixed $value The value of the cookie.
     * @param string|bool $encrypt Encryption mode to use.
     * @param string|null $key Encryption key used. Defaults
     *   to Security.salt.
     * @return void
     * @see \Cake\Utility\CookieCryptTrait::_encrypt()
     */
    public function cookieEncrypted($name, $value, $encrypt = 'aes', $key = null)
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
     * @param string|array $url The URL to request.
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function get($url)
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
     * @param string|array $url The URL to request.
     * @param string|array|null $data The data for the request.
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function post($url, $data = [])
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
     * @param string|array $url The URL to request.
     * @param string|array|null $data The data for the request.
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function patch($url, $data = [])
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
     * @param string|array $url The URL to request.
     * @param string|array|null $data The data for the request.
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function put($url, $data = [])
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
     * @param string|array $url The URL to request.
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function delete($url)
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
     * @param string|array $url The URL to request.
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function head($url)
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
     * @param string|array $url The URL to request.
     * @return void
     * @throws \PHPUnit\Exception
     */
    public function options($url)
    {
        $this->_sendRequest($url, 'OPTIONS');
    }

    /**
     * Creates and send the request into a Dispatcher instance.
     *
     * Receives and stores the response for future inspection.
     *
     * @param string|array $url The URL
     * @param string $method The HTTP method
     * @param string|array|null $data The request data.
     * @return void
     * @throws \PHPUnit\Exception
     */
    protected function _sendRequest($url, $method, $data = [])
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
        } catch (PhpUnitException $e) {
            throw $e;
        } catch (DatabaseException $e) {
            throw $e;
        } catch (LogicException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->_exception = $e;
            // Simulate the global exception handler being invoked.
            $this->_handleError($e);
        }
    }

    /**
     * Get the correct dispatcher instance.
     *
     * @return \Cake\TestSuite\MiddlewareDispatcher|\Cake\TestSuite\LegacyRequestDispatcher A dispatcher instance
     */
    protected function _makeDispatcher()
    {
        if ($this->_useHttpServer) {
            return new MiddlewareDispatcher($this, $this->_appClass, $this->_appArgs);
        }

        return new LegacyRequestDispatcher($this);
    }

    /**
     * Adds additional event spies to the controller/view event manager.
     *
     * @param \Cake\Event\Event $event A dispatcher event.
     * @param \Cake\Controller\Controller|null $controller Controller instance.
     * @return void
     */
    public function controllerSpy($event, $controller = null)
    {
        if (!$controller) {
            /** @var \Cake\Controller\Controller $controller */
            $controller = $event->getSubject();
        }
        $this->_controller = $controller;
        $events = $controller->getEventManager();
        $events->on('View.beforeRender', function ($event, $viewFile) use ($controller) {
            if (!$this->_viewName) {
                $this->_viewName = $viewFile;
            }
            if ($this->_retainFlashMessages) {
                $this->_flashMessages = $controller->getRequest()->getSession()->read('Flash');
            }
        });
        $events->on('View.beforeLayout', function ($event, $viewFile) {
            $this->_layoutName = $viewFile;
        });
    }

    /**
     * Attempts to render an error response for a given exception.
     *
     * This method will attempt to use the configured exception renderer.
     * If that class does not exist, the built-in renderer will be used.
     *
     * @param \Exception $exception Exception to handle.
     * @return void
     * @throws \Exception
     */
    protected function _handleError($exception)
    {
        $class = Configure::read('Error.exceptionRenderer');
        if (empty($class) || !class_exists($class)) {
            $class = 'Cake\Error\ExceptionRenderer';
        }
        /** @var \Cake\Error\ExceptionRenderer $instance */
        $instance = new $class($exception);
        $this->_response = $instance->render();
    }

    /**
     * Creates a request object with the configured options and parameters.
     *
     * @param string|array $url The URL
     * @param string $method The HTTP method
     * @param string|array|null $data The request data.
     * @return array The request context
     */
    protected function _buildRequest($url, $method, $data)
    {
        $sessionConfig = (array)Configure::read('Session') + [
            'defaults' => 'php',
        ];
        $session = Session::create($sessionConfig);
        $session->write($this->_session);
        list($url, $query, $hostInfo) = $this->_url($url);
        $tokenUrl = $url;

        if ($query) {
            $tokenUrl .= '?' . $query;
        }

        parse_str($query, $queryData);
        $props = [
            'url' => $url,
            'session' => $session,
            'query' => $queryData,
            'files' => [],
        ];
        if (is_string($data)) {
            $props['input'] = $data;
        }
        if (!isset($props['input'])) {
            $data = $this->_addTokens($tokenUrl, $data);
            $props['post'] = $this->_castToString($data);
        }
        $props['cookies'] = $this->_cookie;

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
                if (!in_array($name, ['CONTENT_LENGTH', 'CONTENT_TYPE'])) {
                    $name = 'HTTP_' . $name;
                }
                $env[$name] = $v;
            }
            unset($this->_request['headers']);
        }
        $props['environment'] = $env;
        $props = Hash::merge($props, $this->_request);

        return $props;
    }

    /**
     * Add the CSRF and Security Component tokens if necessary.
     *
     * @param string $url The URL the form is being submitted on.
     * @param array $data The request body data.
     * @return array The request body with tokens added.
     */
    protected function _addTokens($url, $data)
    {
        if ($this->_securityToken === true) {
            $fields = array_diff_key($data, array_flip($this->_unlockedFields));

            $keys = array_map(function ($field) {
                return preg_replace('/(\.\d+)+$/', '', $field);
            }, array_keys(Hash::flatten($fields)));

            $tokenData = $this->_buildFieldToken($url, array_unique($keys), $this->_unlockedFields);

            $data['_Token'] = $tokenData;
            $data['_Token']['debug'] = 'SecurityComponent debug data would be added here';
        }

        if ($this->_csrfToken === true) {
            // While most applications will not be using verify tokens, we enable
            // it for tests so that if applications upgrade they don't face testing failures.
            $middleware = new CsrfProtectionMiddleware(['verifyTokenSource' => true]);
            if (!isset($this->_cookie['csrfToken'])) {
                $this->_cookie['csrfToken'] = $middleware->createToken();
            }
            if (!isset($data['_csrfToken'])) {
                $data['_csrfToken'] = $this->_cookie['csrfToken'];
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
    protected function _castToString($data)
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
     * @param string|array $url The URL
     * @return array Qualified URL, the query parameters, and host data
     */
    protected function _url($url)
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
    protected function _getBodyAsString()
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
    public function viewVariable($name)
    {
        if (empty($this->_controller->viewVars)) {
            $this->fail('There are no view variables, perhaps you need to run a request?');
        }
        if (isset($this->_controller->viewVars[$name])) {
            return $this->_controller->viewVars[$name];
        }

        return null;
    }

    /**
     * Asserts that the response status code is in the 2xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseOk($message = null)
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
    public function assertResponseSuccess($message = null)
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
    public function assertResponseError($message = null)
    {
        $this->assertThat(null, new StatusError($this->_response), $message);
    }

    /**
     * Asserts that the response status code is in the 5xx range.
     *
     * @param string $message Custom message for failure.
     * @return void
     */
    public function assertResponseFailure($message = null)
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
    public function assertResponseCode($code, $message = null)
    {
        $this->assertThat($code, new StatusCode($this->_response), $message);
    }

    /**
     * Asserts that the Location header is correct. Comparison is made against a full URL.
     *
     * @param string|array|null $url The URL you expected the client to go to. This
     *   can either be a string URL or an array compatible with Router::url(). Use null to
     *   simply check for the existence of this header.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirect($url = null, $message = '')
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new HeaderSet($this->_response, 'Location'), $verboseMessage);

        if ($url) {
            $this->assertThat(Router::url($url, ['_full' => true]), new HeaderEquals($this->_response, 'Location'), $verboseMessage);
        }
    }

    /**
     * Asserts that the Location header is correct. Comparison is made against exactly the URL provided.
     *
     * @param string|array|null $url The URL you expected the client to go to. This
     *   can either be a string URL or an array compatible with Router::url(). Use null to
     *   simply check for the existence of this header.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertRedirectEquals($url = null, $message = '')
    {
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
    public function assertRedirectContains($url, $message = '')
    {
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
    public function assertRedirectNotContains($url, $message = '')
    {
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
    public function assertNoRedirect($message = '')
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
    public function assertHeader($header, $content, $message = '')
    {
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
    public function assertHeaderContains($header, $content, $message = '')
    {
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
    public function assertHeaderNotContains($header, $content, $message = '')
    {
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
    public function assertContentType($type, $message = '')
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
    public function assertResponseEquals($content, $message = '')
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
    public function assertResponseNotEquals($content, $message = '')
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
    public function assertResponseContains($content, $message = '', $ignoreCase = false)
    {
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
    public function assertResponseNotContains($content, $message = '', $ignoreCase = false)
    {
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
    public function assertResponseRegExp($pattern, $message = '')
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
    public function assertResponseNotRegExp($pattern, $message = '')
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
    public function assertResponseNotEmpty($message = '')
    {
        $this->assertThat(null, new BodyNotEmpty($this->_response), $message);
    }

    /**
     * Assert response content is empty.
     *
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertResponseEmpty($message = '')
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
    public function assertTemplate($content, $message = '')
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
    public function assertLayout($content, $message = '')
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($content, new LayoutFileEquals($this->_layoutName), $verboseMessage);
    }

    /**
     * Asserts session contents
     *
     * @param string $expected The expected contents.
     * @param string $path The session data path. Uses Hash::get() compatible notation
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertSession($expected, $path, $message = '')
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($expected, new SessionEquals($this->_requestSession, $path), $verboseMessage);
    }

    /**
     * Asserts a flash message was set
     *
     * @param string $expected Expected message
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertFlashMessage($expected, $key = 'flash', $message = '')
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
    public function assertFlashMessageAt($at, $expected, $key = 'flash', $message = '')
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($expected, new FlashParamEquals($this->_requestSession, $key, 'message', $at), $verboseMessage);
    }

    /**
     * Asserts a flash element was set
     *
     * @param string $expected Expected element name
     * @param string $key Flash key
     * @param string $message Assertion failure message
     * @return void
     */
    public function assertFlashElement($expected, $key = 'flash', $message = '')
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($expected, new FlashParamEquals($this->_requestSession, $key, 'element'), $verboseMessage);
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
    public function assertFlashElementAt($at, $expected, $key = 'flash', $message = '')
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($expected, new FlashParamEquals($this->_requestSession, $key, 'element', $at), $verboseMessage);
    }

    /**
     * Asserts cookie values
     *
     * @param string $expected The expected contents.
     * @param string $name The cookie name.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertCookie($expected, $name, $message = '')
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
    public function assertCookieNotSet($cookie, $message = '')
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
    public function disableErrorHandlerMiddleware()
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
     * @param string $expected The expected contents.
     * @param string $name The cookie name.
     * @param string|bool $encrypt Encryption mode to use.
     * @param string|null $key Encryption key used. Defaults
     *   to Security.salt.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     * @see \Cake\Utility\CookieCryptTrait::_encrypt()
     */
    public function assertCookieEncrypted($expected, $name, $encrypt = 'aes', $key = null, $message = '')
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat($name, new CookieSet($this->_response), $verboseMessage);

        $this->_cookieEncryptionKey = $key;
        $this->assertThat($expected, new CookieEncryptedEquals($this->_response, $name, $encrypt, $this->_getCookieEncryptionKey()));
    }

    /**
     * Asserts that a file with the given name was sent in the response
     *
     * @param string $expected The absolute file path that should be sent in the response.
     * @param string $message The failure message that will be appended to the generated message.
     * @return void
     */
    public function assertFileResponse($expected, $message = '')
    {
        $verboseMessage = $this->extractVerboseMessage($message);
        $this->assertThat(null, new FileSent($this->_response), $verboseMessage);
        $this->assertThat($expected, new FileSentAs($this->_response), $verboseMessage);
    }

    /**
     * Inspect controller to extract possible causes of the failed assertion
     *
     * @param string $message Original message to use as a base
     * @return string|null
     */
    protected function extractVerboseMessage($message = null)
    {
        if ($this->_exception instanceof \Exception) {
            $message .= $this->extractExceptionMessage($this->_exception);
        }
        if ($this->_controller === null) {
            return $message;
        }
        $error = Hash::get($this->_controller->viewVars, 'error');
        if ($error instanceof \Exception) {
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
    protected function extractExceptionMessage(\Exception $exception)
    {
        return PHP_EOL .
            sprintf('Possibly related to %s: "%s" ', get_class($exception), $exception->getMessage()) .
            PHP_EOL .
            $exception->getTraceAsString();
    }
}
