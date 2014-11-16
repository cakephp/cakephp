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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Cake\Controller\Controller;
use Cake\Controller\ErrorController;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception as CakeException;
use Cake\Core\Exception\MissingPluginException;
use Cake\Event\Event;
use Cake\Network\Exception\HttpException;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingTemplateException;
use Exception;

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
class ExceptionRenderer {

/**
 * Controller instance.
 *
 * @var Controller
 */
	public $controller = null;

/**
 * Template to render for Cake\Core\Exception\Exception
 *
 * @var string
 */
	public $template = '';

/**
 * The method corresponding to the Exception this object is for.
 *
 * @var string
 */
	public $method = '';

/**
 * The exception being handled.
 *
 * @var \Exception
 */
	public $error = null;

/**
 * Creates the controller to perform rendering on the error response.
 * If the error is a Cake\Core\Exception\Exception it will be converted to either a 400 or a 500
 * code error depending on the code used to construct the error.
 *
 * @param \Exception $exception Exception
 */
	public function __construct(Exception $exception) {
		$this->error = $exception;
		$this->controller = $this->_getController();
	}

/**
 * Get the controller instance to handle the exception.
 * Override this method in subclasses to customize the controller used.
 * This method returns the built in `ErrorController` normally, or if an error is repeated
 * a bare controller will be used.
 *
 * @return \Cake\Controller\Controller
 */
	protected function _getController() {
		if (!$request = Router::getRequest(true)) {
			$request = Request::createFromGlobals();
		}
		$response = new Response();

		try {
			$controller = new ErrorController($request, $response);
			$controller->startupProcess();
		} catch (Exception $e) {
			if (!empty($controller) && isset($controller->RequestHandler)) {
				$event = new Event('Controller.startup', $controller);
				$controller->RequestHandler->startup($event);
			}
		}
		if (empty($controller)) {
			$controller = new Controller($request, $response);
			$controller->viewPath = 'Error';
		}
		return $controller;
	}

/**
 * Renders the response for the exception.
 *
 * @return \Cake\Network\Response The response to be sent.
 */
	public function render() {
		$exception = $this->error;
		$code = $this->_code($exception);
		$method = $this->_method($exception);
		$template = $this->_template($exception, $method, $code);

		$isDebug = Configure::read('debug');
		if (($isDebug || $exception instanceof HttpException) &&
			method_exists($this, $method)
		) {
			return $this->_customMethod($method, $exception);
		}

		$message = $this->_message($exception, $code);
		$url = $this->controller->request->here();

		if (method_exists($exception, 'responseHeader')) {
			$this->controller->response->header($exception->responseHeader());
		}
		$this->controller->response->statusCode($code);
		$this->controller->set(array(
			'message' => $message,
			'url' => h($url),
			'error' => $exception,
			'code' => $code,
			'_serialize' => array('message', 'url', 'code')
		));

		if ($exception instanceof CakeException && $isDebug) {
			$this->controller->set($this->error->getAttributes());
		}
		return $this->_outputMessage($template);
	}

/**
 * Render a custom error method/template.
 *
 * @param string $method The method name to invoke.
 * @param \Exception $exception The exception to render.
 * @return \Cake\Network\Response The response to send.
 */
	protected function _customMethod($method, $exception) {
		$result = call_user_func([$this, $method], $exception);
		if (is_string($result)) {
			$this->controller->response->body($result);
			$result = $this->controller->response;
		}
		return $result;
	}
/**
 * Get method name
 *
 * @param \Exception $exception Exception instance.
 * @return string
 */
	protected function _method(\Exception $exception) {
		list(, $baseClass) = namespaceSplit(get_class($exception));
		$baseClass = substr($baseClass, 0, -9);
		$method = Inflector::variable($baseClass) ?: 'error500';
		return $this->method = $method;
	}

/**
 * Get error message.
 *
 * @param \Exception $exception Exception
 * @param int $code Error code
 * @return string Error message
 */
	protected function _message(\Exception $exception, $code) {
		$message = $this->error->getMessage();

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
 * @param string $method Method name
 * @param int $code Error code
 * @return string Template name
 */
	protected function _template(\Exception $exception, $method, $code) {
		$isHttpException = $exception instanceof HttpException;

		if (!Configure::read('debug') && !$isHttpException) {
			$template = 'error500';
			if ($code < 500) {
				$template = 'error400';
			}
			return $this->template = $template;
		}

		if ($isHttpException) {
			$template = 'error500';
			if ($code < 500) {
				$template = 'error400';
			}
			return $this->template = $template;
		}

		$template = $method ?: 'error500';

		if ($exception instanceof \PDOException) {
			$template = 'pdo_error';
		}

		return $this->template = $template;
	}

/**
 * Get an error code value within range 400 to 506
 *
 * @param \Exception $exception Exception
 * @return int Error code value within range 400 to 506
 */
	protected function _code(\Exception $exception) {
		$code = 500;
		$errorCode = $exception->getCode();
		if ($errorCode >= 400 && $errorCode < 506) {
			$code = $errorCode;
		}
		return $code;
	}

/**
 * Generate the response using the controller object.
 *
 * @param string $template The template to render.
 * @return \Cake\Network\Response A response object that can be sent.
 */
	protected function _outputMessage($template) {
		try {
			$this->controller->render($template);
			$event = new Event('Controller.shutdown', $this->controller);
			$this->controller->afterFilter($event);
			return $this->controller->response;
		} catch (MissingTemplateException $e) {
			$attributes = $e->getAttributes();
			if (isset($attributes['file']) && strpos($attributes['file'], 'error500') !== false) {
				return $this->_outputMessageSafe('error500');
			}
			return $this->_outputMessage('error500');
		} catch (MissingPluginException $e) {
			$attributes = $e->getAttributes();
			if (isset($attributes['plugin']) && $attributes['plugin'] === $this->controller->plugin) {
				$this->controller->plugin = null;
			}
			return $this->_outputMessageSafe('error500');
		} catch (\Exception $e) {
			return $this->_outputMessageSafe('error500');
		}
	}

/**
 * A safer way to render error messages, replaces all helpers, with basics
 * and doesn't call component methods.
 *
 * @param string $template The template to render
 * @return \Cake\Network\Response A response object that can be sent.
 */
	protected function _outputMessageSafe($template) {
		$this->controller->layoutPath = null;
		$this->controller->subDir = null;
		$this->controller->viewPath = 'Error';
		$this->controller->layout = 'error';
		$this->controller->helpers = array('Form', 'Html', 'Session');

		$view = $this->controller->createView();
		$this->controller->response->body($view->render($template, 'error'));
		$this->controller->response->type('html');
		return $this->controller->response;
	}

}
