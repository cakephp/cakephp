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
 * @link          https://cakephp.org CakePHP Project
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error\Renderer;

use Cake\Controller\Controller;
use Cake\Controller\ControllerFactory;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Container;
use Cake\Core\Exception\CakeException;
use Cake\Core\Exception\HttpErrorCodeInterface;
use Cake\Core\Exception\MissingPluginException;
use Cake\Error\Debugger;
use Cake\Error\ExceptionRendererInterface;
use Cake\Http\Exception\HttpException;
use Cake\Http\Response;
use Cake\Http\ResponseEmitter;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingLayoutException;
use Cake\View\Exception\MissingTemplateException;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use ReflectionMethod;
use Throwable;
use function Cake\Core\deprecationWarning;
use function Cake\Core\h;
use function Cake\Core\namespaceSplit;
use function Cake\I18n\__d;

/**
 * Web Exception Renderer.
 *
 * Captures and handles all unhandled exceptions. Displays helpful framework errors when debug is true.
 * When debug is false, WebExceptionRenderer will render 404 or 500 errors. If an uncaught exception is thrown
 * and it is a type that WebExceptionHandler does not know about it will be treated as a 500 error.
 *
 * ### Implementing application specific exception rendering
 *
 * You can implement application specific exception handling by creating a subclass of
 * WebExceptionRenderer and configure it to be the `exceptionRenderer` in config/error.php
 *
 * #### Using a subclass of WebExceptionRenderer
 *
 * Using a subclass of WebExceptionRenderer gives you full control over how Exceptions are rendered, you
 * can configure your class in your config/app.php.
 */
class WebExceptionRenderer implements ExceptionRendererInterface
{
    /**
     * The exception being handled.
     *
     * @var \Throwable
     */
    protected Throwable $error;

    /**
     * Controller instance.
     *
     * @var \Cake\Controller\Controller
     */
    protected Controller $controller;

    /**
     * Template to render for {@link \Cake\Core\Exception\CakeException}
     *
     * @var string
     */
    protected string $template = '';

    /**
     * The method corresponding to the Exception this object is for.
     *
     * @var string
     */
    protected string $method = '';

    /**
     * If set, this will be request used to create the controller that will render
     * the error.
     *
     * @var \Cake\Http\ServerRequest|null
     */
    protected ?ServerRequest $request;

    /**
     * Map of exceptions to http status codes.
     *
     * This can be customized for users that don't want specific exceptions to throw 404 errors
     * or want their application exceptions to be automatically converted.
     *
     * @var array<class-string<\Throwable>, int>
     * @deprecated 5.2.0 Exceptions returning HTTP error codes should extend
     *   HttpErrorCodeInterface instead of using this array.
     */
    protected array $exceptionHttpCodes = [];

    /**
     * Creates the controller to perform rendering on the error response.
     *
     * @param \Throwable $exception Exception.
     * @param \Cake\Http\ServerRequest|null $request The request if this is set it will be used
     *   instead of creating a new one.
     */
    public function __construct(Throwable $exception, ?ServerRequest $request = null)
    {
        $this->error = $exception;
        $this->request = $request;
        $this->controller = $this->_getController();
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
    protected function _getController(): Controller
    {
        $request = $this->request;
        $routerRequest = Router::getRequest();
        // Fallback to the request in the router or make a new one from
        // $_SERVER
        $request ??= $routerRequest ?: ServerRequestFactory::fromGlobals();

        // If the current request doesn't have routing data, but we
        // found a request in the router context copy the params over
        if ($request->getParam('controller') === null && $routerRequest !== null) {
            $request = $request->withAttribute('params', $routerRequest->getAttribute('params'));
        }

        $class = '';
        try {
            $params = $request->getAttribute('params');
            $params['controller'] = 'Error';

            $factory = new ControllerFactory(new Container());
            // Check including plugin + prefix
            $class = $factory->getControllerClass($request->withAttribute('params', $params));

            if (!$class && !empty($params['prefix']) && !empty($params['plugin'])) {
                unset($params['prefix']);
                // Fallback to only plugin
                $class = $factory->getControllerClass($request->withAttribute('params', $params));
            }

            if (!$class) {
                // Fallback to app/core provided controller.
                /** @var string $class */
                $class = App::className('Error', 'Controller', 'Controller');
            }

            assert(is_subclass_of($class, Controller::class));
            $controller = new $class($request);
            $controller->startupProcess();
        } catch (Throwable $e) {
            Log::warning(
                "Failed to construct or call startup() on the resolved controller class of `{$class}`. " .
                    "Using Fallback Controller instead. Error {$e->getMessage()}" .
                    "\nStack Trace\n: {$e->getTraceAsString()}",
                'cake.error',
            );
            $controller = null;
        }

        if ($controller === null) {
            return new Controller($request);
        }

        return $controller;
    }

    /**
     * Clear output buffers so error pages display properly.
     *
     * @return void
     */
    protected function clearOutput(): void
    {
        if (in_array(PHP_SAPI, ['cli', 'phpdbg'])) {
            return;
        }
        while (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Renders the response for the exception.
     *
     * @return \Psr\Http\Message\ResponseInterface The response to be sent.
     */
    public function render(): ResponseInterface
    {
        $exception = $this->error;
        $code = $this->getHttpCode($exception);
        $method = $this->_method($exception);
        $template = $this->_template($exception, $method, $code);
        $this->clearOutput();

        if (method_exists($this, $method)) {
            return $this->_customMethod($method, $exception);
        }

        $message = $this->_message($exception, $code);
        $url = $this->controller->getRequest()->getRequestTarget();
        $response = $this->controller->getResponse();

        if ($exception instanceof HttpException) {
            foreach ($exception->getHeaders() as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
        }
        $response = $response->withStatus($code);

        $exceptions = [$exception];
        $previous = $exception->getPrevious();
        while ($previous != null) {
            $exceptions[] = $previous;
            $previous = $previous->getPrevious();
        }

        $viewVars = [
            'message' => $message,
            'url' => h($url),
            'error' => $exception,
            'exceptions' => $exceptions,
            'code' => $code,
        ];
        $serialize = ['message', 'url', 'code'];

        $isDebug = Configure::read('debug');
        if ($isDebug) {
            $trace = (array)Debugger::formatTrace($exception->getTrace(), [
                'format' => 'array',
                'args' => true,
            ]);
            $origin = [
                'file' => $exception->getFile() ?: 'null',
                'line' => $exception->getLine() ?: 'null',
            ];
            // Traces don't include the origin file/line.
            array_unshift($trace, $origin);
            $viewVars['trace'] = $trace;
            $viewVars += $origin;
            $serialize[] = 'file';
            $serialize[] = 'line';
        }
        $this->controller->set($viewVars);
        $this->controller->viewBuilder()->setOption('serialize', $serialize);

        if ($exception instanceof CakeException && $isDebug) {
            $this->controller->set($exception->getAttributes());
        }
        $this->controller->setResponse($response);

        return $this->_outputMessage($template);
    }

    /**
     * Emit the response content
     *
     * @param \Psr\Http\Message\ResponseInterface|string $output The response to output.
     * @return void
     */
    public function write(ResponseInterface|string $output): void
    {
        if (is_string($output)) {
            echo $output;

            return;
        }

        $emitter = new ResponseEmitter();
        $emitter->emit($output);
    }

    /**
     * Render a custom error method/template.
     *
     * @param string $method The method name to invoke.
     * @param \Throwable $exception The exception to render.
     * @return \Cake\Http\Response The response to send.
     */
    protected function _customMethod(string $method, Throwable $exception): Response
    {
        $result = $this->{$method}($exception);
        $this->_shutdown();
        if (is_string($result)) {
            return $this->controller->getResponse()->withStringBody($result);
        }

        return $result;
    }

    /**
     * Get method name
     *
     * @param \Throwable $exception Exception instance.
     * @return string
     */
    protected function _method(Throwable $exception): string
    {
        [, $baseClass] = namespaceSplit($exception::class);

        if (str_ends_with($baseClass, 'Exception')) {
            $baseClass = substr($baseClass, 0, -9);
        }

        // $baseClass would be an empty string if the exception class is \Exception.
        $method = $baseClass === '' ? 'error500' : Inflector::variable($baseClass);

        return $this->method = $method;
    }

    /**
     * Get error message.
     *
     * @param \Throwable $exception Exception.
     * @param int $code Error code.
     * @return string Error message
     */
    protected function _message(Throwable $exception, int $code): string
    {
        $message = $exception->getMessage();

        if (
            !Configure::read('debug') &&
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
     * @param \Throwable $exception Exception instance.
     * @param string $method Method name.
     * @param int $code Error code.
     * @return string Template name
     */
    protected function _template(Throwable $exception, string $method, int $code): string
    {
        if ($exception instanceof HttpException || !Configure::read('debug')) {
            return $this->template = $code < 500 ? 'error400' : 'error500';
        }

        if ($exception instanceof PDOException) {
            return $this->template = 'pdo_error';
        }

        return $this->template = $method;
    }

    /**
     * Gets the appropriate http status code for exception.
     *
     * @param \Throwable $exception Exception.
     * @return int A valid HTTP status code.
     */
    protected function getHttpCode(Throwable $exception): int
    {
        if ($exception instanceof HttpErrorCodeInterface) {
            return $exception->getCode();
        }

        if (isset($this->exceptionHttpCodes[$exception::class])) {
            deprecationWarning(
                '5.2.0',
                'Exceptions returning a HTTP error code should implement HttpErrorCodeInterface,'
                . ' instead of using the WebExceptionRenderer::$exceptionHttpCodes property.',
            );

            return $this->exceptionHttpCodes[$exception::class];
        }

        return 500;
    }

    /**
     * Generate the response using the controller object.
     *
     * @param string $template The template to render.
     * @param bool $skipControllerCheck Skip checking controller for existence of
     *   method matching the exception name.
     * @return \Cake\Http\Response A response object that can be sent.
     */
    protected function _outputMessage(string $template, bool $skipControllerCheck = false): Response
    {
        try {
            $method = $this->method ?: $this->_method($this->error);

            if (!$skipControllerCheck && method_exists($this->controller, $method)) {
                $this->controller->viewBuilder()->setTemplate($method);

                $reflectionMethod = new ReflectionMethod($this->controller, $method);
                $result = $reflectionMethod->invoke($this->controller, $this->error);

                if ($result instanceof Response) {
                    $this->controller->setResponse($result);
                } else {
                    $this->controller->render();
                }
            } else {
                $this->controller->render($template);
            }

            return $this->_shutdown();
        } catch (MissingTemplateException $e) {
            Log::warning(
                "MissingTemplateException - Failed to render error template `{$template}` . Error: {$e->getMessage()}" .
                    "\nStack Trace\n: {$e->getTraceAsString()}",
                'cake.error',
            );
            $attributes = $e->getAttributes();
            if (
                $e instanceof MissingLayoutException ||
                str_contains($attributes['file'], 'error500')
            ) {
                return $this->_outputMessageSafe('error500');
            }

            return $this->_outputMessage('error500', true);
        } catch (MissingPluginException $e) {
            Log::warning(
                "MissingPluginException - Failed to render error template `{$template}`. Error: {$e->getMessage()}" .
                    "\nStack Trace\n: {$e->getTraceAsString()}",
                'cake.error',
            );
            $attributes = $e->getAttributes();
            if (isset($attributes['plugin']) && $attributes['plugin'] === $this->controller->getPlugin()) {
                $this->controller->setPlugin(null);
            }

            return $this->_outputMessageSafe('error500');
        } catch (Throwable $outer) {
            Log::warning(
                "Throwable - Failed to render error template `{$template}`. Error: {$outer->getMessage()}" .
                    "\nStack Trace\n: {$outer->getTraceAsString()}",
                'cake.error',
            );
            try {
                return $this->_outputMessageSafe('error500');
            } catch (Throwable) {
                throw $outer;
            }
        }
    }

    /**
     * A safer way to render error messages, replaces all helpers, with basics
     * and doesn't call component methods.
     *
     * @param string $template The template to render.
     * @return \Cake\Http\Response A response object that can be sent.
     */
    protected function _outputMessageSafe(string $template): Response
    {
        $builder = $this->controller->viewBuilder();
        $builder
            ->setHelpers([])
            ->setLayoutPath('')
            ->setTemplatePath('Error');
        $view = $this->controller->createView('View');

        $response = $this->controller->getResponse()
            ->withType('html')
            ->withStringBody($view->render($template, 'error'));
        $this->controller->setResponse($response);

        return $response;
    }

    /**
     * Run the shutdown events.
     *
     * Triggers the afterFilter and afterDispatch events.
     *
     * @return \Cake\Http\Response The response to serve.
     */
    protected function _shutdown(): Response
    {
        $this->controller->dispatchEvent('Controller.shutdown');

        return $this->controller->getResponse();
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
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
