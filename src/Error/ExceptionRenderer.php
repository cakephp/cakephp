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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception as CakeException;
use Cake\Core\Exception\MissingPluginException;
use Cake\Event\Event;
use Cake\Http\Exception\HttpException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\DispatcherFactory;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingTemplateException;
use Exception;
use PDOException;

/**
 * Exception Renderer.
 *
 * Captures and handles all unhandled exceptions. Displays helpful framework errors when debug is true.
 * When debug is false a CakeException will render 404 or 500 errors. If an uncaught exception is thrown
 * and it is a type that ExceptionHandler does not know about it will be treated as a 500 error.
 *
 * ### Implementing application specific exception rendering
 *
 * You can implement application specific exception handling by creating a subclass of
 * ExceptionRenderer and configure it to be the `exceptionRenderer` in config/error.php
 *
 * #### Using a subclass of ExceptionRenderer
 *
 * Using a subclass of ExceptionRenderer gives you full control over how Exceptions are rendered, you
 * can configure your class in your config/app.php.
 */
class ExceptionRenderer implements ExceptionRendererInterface
{

    /**
     * The exception being handled.
     *
     * @var \Exception
     */
    protected $error;

    /**
     * Controller instance.
     *
     * @var \Cake\Controller\Controller
     */
    protected $controller;

    /**
     * Template to render for Cake\Core\Exception\Exception
     *
     * @var string
     */
    protected $template = '';

    /**
     * The method corresponding to the Exception this object is for.
     *
     * @var string
     */
    protected $method = '';

    /**
     * If set, this will be request used to create the controller that will render
     * the error.
     *
     * @var \Cake\Http\ServerRequest|null
     */
    protected $request = null;

    /**
     * Creates the controller to perform rendering on the error response.
     * If the error is a Cake\Core\Exception\Exception it will be converted to either a 400 or a 500
     * code error depending on the code used to construct the error.
     *
     * @param \Exception $exception Exception.
     * @param \Cake\Http\ServerRequest $request The request - if this is set it will be used instead of creating a new one
     */
    public function __construct(Exception $exception, ServerRequest $request = null)
    {
        $this->error = $exception;
        $this->request = $request;
        $this->controller = $this->_getController();
    }

    /**
     * Returns the unwrapped exception object in case we are dealing with
     * a PHP 7 Error object
     *
     * @param \Exception $exception The object to unwrap
     * @return \Exception|\Error
     */
    protected function _unwrap($exception)
    {
        return $exception instanceof PHP7ErrorException ? $exception->getError() : $exception;
    }

    /**
     * Get the controller instance to handle the exception.
     * Override this method in subclasses to customize the controller used.
     * This method returns the built in `ErrorController` normally, or if an error is repeated
     * a bare controller will be used.
     *
     * @return \Cake\Controller\Controller
     * @triggers Controller.startup $controller
     */
    protected function _getController()
    {
        $request = $this->request;
        $routerRequest = Router::getRequest(true);
        // Fallback to the request in the router or make a new one from
        // $_SERVER
        if ($request === null) {
            $request = $routerRequest ?: ServerRequestFactory::fromGlobals();
        }

        // If the current request doesn't have routing data, but we
        // found a request in the router context copy the params over
        if ($request->getParam('controller') === false && $routerRequest !== null) {
            $request = $request->withAttribute('params', $routerRequest->getAttribute('params'));
        }

        $response = new Response();
        $controller = null;

        try {
            $namespace = 'Controller';
            $prefix = $request->getParam('prefix');
            if ($prefix) {
                if (strpos($prefix, '/') === false) {
                    $namespace .= '/' . Inflector::camelize($prefix);
                } else {
                    $prefixes = array_map(
                        'Cake\Utility\Inflector::camelize',
                        explode('/', $prefix)
                    );
                    $namespace .= '/' . implode('/', $prefixes);
                }
            }

            $class = App::className('Error', $namespace, 'Controller');
            if (!$class && $namespace !== 'Controller') {
                $class = App::className('Error', 'Controller', 'Controller');
            }

            /* @var \Cake\Controller\Controller $controller */
            $controller = new $class($request, $response);
            $controller->startupProcess();
            $startup = true;
        } catch (Exception $e) {
            $startup = false;
        }

        // Retry RequestHandler, as another aspect of startupProcess()
        // could have failed. Ignore any exceptions out of startup, as
        // there could be userland input data parsers.
        if ($startup === false && !empty($controller) && isset($controller->RequestHandler)) {
            try {
                $event = new Event('Controller.startup', $controller);
                $controller->RequestHandler->startup($event);
            } catch (Exception $e) {
            }
        }
        if (empty($controller)) {
            $controller = new Controller($request, $response);
        }

        return $controller;
    }

    /**
     * Renders the response for the exception.
     *
     * @return \Cake\Http\Response The response to be sent.
     */
    public function render()
    {
        $exception = $this->error;
        $code = $this->_code($exception);
        $method = $this->_method($exception);
        $template = $this->_template($exception, $method, $code);
        $unwrapped = $this->_unwrap($exception);

        if (method_exists($this, $method)) {
            return $this->_customMethod($method, $unwrapped);
        }

        $message = $this->_message($exception, $code);
        $url = $this->controller->getRequest()->getRequestTarget();
        $response = $this->controller->getResponse();

        if ($exception instanceof CakeException) {
            foreach ((array)$exception->responseHeader() as $key => $value) {
                $response = $response->withHeader($key, $value);
            }
        }
        $response = $response->withStatus($code);

        $viewVars = [
            'message' => $message,
            'url' => h($url),
            'error' => $unwrapped,
            'code' => $code,
            '_serialize' => ['message', 'url', 'code']
        ];

        $isDebug = Configure::read('debug');
        if ($isDebug) {
            $viewVars['trace'] = Debugger::formatTrace($unwrapped->getTrace(), [
                'format' => 'array',
                'args' => false
            ]);
            $viewVars['file'] = $exception->getFile() ?: 'null';
            $viewVars['line'] = $exception->getLine() ?: 'null';
            $viewVars['_serialize'][] = 'file';
            $viewVars['_serialize'][] = 'line';
        }
        $this->controller->set($viewVars);

        if ($unwrapped instanceof CakeException && $isDebug) {
            $this->controller->set($unwrapped->getAttributes());
        }
        $this->controller->response = $response;

        return $this->_outputMessage($template);
    }

    /**
     * Render a custom error method/template.
     *
     * @param string $method The method name to invoke.
     * @param \Exception $exception The exception to render.
     * @return \Cake\Http\Response The response to send.
     */
    protected function _customMethod($method, $exception)
    {
        $result = call_user_func([$this, $method], $exception);
        $this->_shutdown();
        if (is_string($result)) {
            $result = $this->controller->response->withStringBody($result);
        }

        return $result;
    }

    /**
     * Get method name
     *
     * @param \Exception $exception Exception instance.
     * @return string
     */
    protected function _method(Exception $exception)
    {
        $exception = $this->_unwrap($exception);
        list(, $baseClass) = namespaceSplit(get_class($exception));

        if (substr($baseClass, -9) === 'Exception') {
            $baseClass = substr($baseClass, 0, -9);
        }

        $method = Inflector::variable($baseClass) ?: 'error500';

        return $this->method = $method;
    }

    /**
     * Get error message.
     *
     * @param \Exception $exception Exception.
     * @param int $code Error code.
     * @return string Error message
     */
    protected function _message(Exception $exception, $code)
    {
        $exception = $this->_unwrap($exception);
        $message = $exception->getMessage();

        if (!Configure::read('debug') &&
            !($exception instanceof HttpException)
        ) {
            if ($code < 500) {
                $message = __d('cake', 'Not Found');
            } else {
                $message = __d('cake', 'An Internal Error Has Occurred.');
            }
        }

        return $message;
    }

    /**
     * Get template for rendering exception info.
     *
     * @param \Exception $exception Exception instance.
     * @param string $method Method name.
     * @param int $code Error code.
     * @return string Template name
     */
    protected function _template(Exception $exception, $method, $code)
    {
        $exception = $this->_unwrap($exception);
        $isHttpException = $exception instanceof HttpException;

        if (!Configure::read('debug') && !$isHttpException || $isHttpException) {
            $template = 'error500';
            if ($code < 500) {
                $template = 'error400';
            }

            return $this->template = $template;
        }

        $template = $method ?: 'error500';

        if ($exception instanceof PDOException) {
            $template = 'pdo_error';
        }

        return $this->template = $template;
    }

    /**
     * Get HTTP status code.
     *
     * @param \Exception $exception Exception.
     * @return int A valid HTTP error status code.
     */
    protected function _code(Exception $exception)
    {
        $code = 500;

        $exception = $this->_unwrap($exception);
        $errorCode = $exception->getCode();
        if ($errorCode >= 400 && $errorCode < 600) {
            $code = $errorCode;
        }

        return $code;
    }

    /**
     * Generate the response using the controller object.
     *
     * @param string $template The template to render.
     * @return \Cake\Http\Response A response object that can be sent.
     */
    protected function _outputMessage($template)
    {
        try {
            $this->controller->render($template);

            return $this->_shutdown();
        } catch (MissingTemplateException $e) {
            $attributes = $e->getAttributes();
            if (isset($attributes['file']) && strpos($attributes['file'], 'error500') !== false) {
                return $this->_outputMessageSafe('error500');
            }

            return $this->_outputMessage('error500');
        } catch (MissingPluginException $e) {
            $attributes = $e->getAttributes();
            if (isset($attributes['plugin']) && $attributes['plugin'] === $this->controller->getPlugin()) {
                $this->controller->setPlugin(null);
            }

            return $this->_outputMessageSafe('error500');
        } catch (Exception $e) {
            return $this->_outputMessageSafe('error500');
        }
    }

    /**
     * A safer way to render error messages, replaces all helpers, with basics
     * and doesn't call component methods.
     *
     * @param string $template The template to render.
     * @return \Cake\Http\Response A response object that can be sent.
     */
    protected function _outputMessageSafe($template)
    {
        $helpers = ['Form', 'Html'];
        $this->controller->helpers = $helpers;
        $builder = $this->controller->viewBuilder();
        $builder->setHelpers($helpers, false)
            ->setLayoutPath('')
            ->setTemplatePath('Error');
        $view = $this->controller->createView('View');

        $this->controller->response = $this->controller->response
            ->withType('html')
            ->withStringBody($view->render($template, 'error'));

        return $this->controller->response;
    }

    /**
     * Run the shutdown events.
     *
     * Triggers the afterFilter and afterDispatch events.
     *
     * @return \Cake\Http\Response The response to serve.
     */
    protected function _shutdown()
    {
        $this->controller->dispatchEvent('Controller.shutdown');
        $dispatcher = DispatcherFactory::create();
        $eventManager = $dispatcher->getEventManager();
        foreach ($dispatcher->filters() as $filter) {
            $eventManager->on($filter);
        }
        $args = [
            'request' => $this->controller->request,
            'response' => $this->controller->response
        ];
        $result = $dispatcher->dispatchEvent('Dispatcher.afterDispatch', $args);

        return $result->getData('response');
    }

    /**
     * Magic accessor for properties made protected.
     *
     * @param string $name Name of the attribute to get.
     * @return mixed
     */
    public function __get($name)
    {
        $protected = [
            'error',
            'controller',
            'template',
            'method',
        ];
        if (in_array($name, $protected, true)) {
            deprecationWarning(sprintf(
                'ExceptionRenderer::$%s is now protected and should no longer be accessed in public context.',
                $name
            ));
        }

        return $this->{$name};
    }

    /**
     * Magic setter for properties made protected.
     *
     * @param string $name Name to property.
     * @param mixed $value Value for property.
     * @return void
     */
    public function __set($name, $value)
    {
        $protected = [
            'error',
            'controller',
            'template',
            'method',
        ];
        if (in_array($name, $protected, true)) {
            deprecationWarning(sprintf(
                'ExceptionRenderer::$%s is now protected and should no longer be accessed in public context.',
                $name
            ));
        }

        $this->{$name} = $value;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'error' => $this->error,
            'request' => $this->request,
            'controller' => $this->controller,
            'template' => $this->template,
            'method' => $this->method,
        ];
    }
}
