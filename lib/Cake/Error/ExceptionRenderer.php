<?php
/**
 * Exception Renderer
 *
 * Provides Exception rendering features. Which allow exceptions to be rendered
 * as HTML pages.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Error
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Sanitize', 'Utility');
App::uses('Router', 'Routing');
App::uses('CakeResponse', 'Network');
App::uses('Controller', 'Controller');

/**
 * Exception Renderer.
 *
 * Captures and handles all unhandled exceptions. Displays helpful framework errors when debug > 1.
 * When debug < 1 a CakeException will render 404 or 500 errors. If an uncaught exception is thrown
 * and it is a type that ExceptionHandler does not know about it will be treated as a 500 error.
 *
 * ### Implementing application specific exception rendering
 *
 * You can implement application specific exception handling in one of a few ways:
 *
 * - Create a AppController::appError();
 * - Create a subclass of ExceptionRenderer and configure it to be the `Exception.renderer`
 *
 * #### Using AppController::appError();
 *
 * This controller method is called instead of the default exception handling. It receives the
 * thrown exception as its only argument. You should implement your error handling in that method.
 *
 * #### Using a subclass of ExceptionRenderer
 *
 * Using a subclass of ExceptionRenderer gives you full control over how Exceptions are rendered, you
 * can configure your class in your core.php, with `Configure::write('Exception.renderer', 'MyClass');`
 * You should place any custom exception renderers in `app/Lib/Error`.
 *
 * @package       Cake.Error
 */
class ExceptionRenderer {

/**
 * Controller instance.
 *
 * @var Controller
 */
	public $controller = null;

/**
 * template to render for CakeException
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
 * @var Exception
 */
	public $error = null;

/**
 * Creates the controller to perform rendering on the error response.
 * If the error is a CakeException it will be converted to either a 400 or a 500
 * code error depending on the code used to construct the error.
 *
 * @param Exception $exception Exception
 */
	public function __construct(Exception $exception) {
		$this->controller = $this->_getController($exception);

		if (method_exists($this->controller, 'appError')) {
			$this->controller->appError($exception);
			return;
		}
		$method = $template = Inflector::variable(str_replace('Exception', '', get_class($exception)));
		$code = $exception->getCode();

		$methodExists = method_exists($this, $method);

		if ($exception instanceof CakeException && !$methodExists) {
			$method = '_cakeError';
			if (empty($template) || $template === 'internalError') {
				$template = 'error500';
			}
		} elseif ($exception instanceof PDOException) {
			$method = 'pdoError';
			$template = 'pdo_error';
			$code = 500;
		} elseif (!$methodExists) {
			$method = 'error500';
			if ($code >= 400 && $code < 500) {
				$method = 'error400';
			}
		}

		$isNotDebug = !Configure::read('debug');
		if ($isNotDebug && $method === '_cakeError') {
			$method = 'error400';
		}
		if ($isNotDebug && $code == 500) {
			$method = 'error500';
		}
		$this->template = $template;
		$this->method = $method;
		$this->error = $exception;
	}

/**
 * Get the controller instance to handle the exception.
 * Override this method in subclasses to customize the controller used.
 * This method returns the built in `CakeErrorController` normally, or if an error is repeated
 * a bare controller will be used.
 *
 * @param Exception $exception The exception to get a controller for.
 * @return Controller
 */
	protected function _getController($exception) {
		App::uses('AppController', 'Controller');
		App::uses('CakeErrorController', 'Controller');
		if (!$request = Router::getRequest(true)) {
			$request = new CakeRequest();
		}
		$response = new CakeResponse();

		if (method_exists($exception, 'responseHeader')) {
			$response->header($exception->responseHeader());
		}

		if (class_exists('AppController')) {
			try {
				$controller = new CakeErrorController($request, $response);
				$controller->startupProcess();
			} catch (Exception $e) {
				if (!empty($controller) && $controller->Components->enabled('RequestHandler')) {
					$controller->RequestHandler->startup($controller);
				}
			}
		}
		if (empty($controller)) {
			$controller = new Controller($request, $response);
			$controller->viewPath = 'Errors';
		}
		return $controller;
	}

/**
 * Renders the response for the exception.
 *
 * @return void
 */
	public function render() {
		if ($this->method) {
			call_user_func_array(array($this, $this->method), array($this->error));
		}
	}

/**
 * Generic handler for the internal framework errors CakePHP can generate.
 *
 * @param CakeException $error The exception to render.
 * @return void
 */
	protected function _cakeError(CakeException $error) {
		$url = $this->controller->request->here();
		$code = ($error->getCode() >= 400 && $error->getCode() < 506) ? $error->getCode() : 500;
		$this->controller->response->statusCode($code);
		$this->controller->set(array(
			'code' => $code,
			'name' => h($error->getMessage()),
			'message' => h($error->getMessage()),
			'url' => h($url),
			'error' => $error,
			'_serialize' => array('code', 'name', 'message', 'url')
		));
		$this->controller->set($error->getAttributes());
		$this->_outputMessage($this->template);
	}

/**
 * Convenience method to display a 400 series page.
 *
 * @param Exception $error The exception to render.
 * @return void
 */
	public function error400($error) {
		$message = $error->getMessage();
		if (!Configure::read('debug') && $error instanceof CakeException) {
			$message = __d('cake', 'Not Found');
		}
		$url = $this->controller->request->here();
		$this->controller->response->statusCode($error->getCode());
		$this->controller->set(array(
			'name' => h($message),
			'message' => h($message),
			'url' => h($url),
			'error' => $error,
			'_serialize' => array('name', 'message', 'url')
		));
		$this->_outputMessage('error400');
	}

/**
 * Convenience method to display a 500 page.
 *
 * @param Exception $error The exception to render.
 * @return void
 */
	public function error500($error) {
		$message = $error->getMessage();
		if (!Configure::read('debug')) {
			$message = __d('cake', 'An Internal Error Has Occurred.');
		}
		$url = $this->controller->request->here();
		$code = ($error->getCode() > 500 && $error->getCode() < 506) ? $error->getCode() : 500;
		$this->controller->response->statusCode($code);
		$this->controller->set(array(
			'name' => h($message),
			'message' => h($message),
			'url' => h($url),
			'error' => $error,
			'_serialize' => array('name', 'message', 'url')
		));
		$this->_outputMessage('error500');
	}

/**
 * Convenience method to display a PDOException.
 *
 * @param PDOException $error The exception to render.
 * @return void
 */
	public function pdoError(PDOException $error) {
		$url = $this->controller->request->here();
		$code = 500;
		$this->controller->response->statusCode($code);
		$this->controller->set(array(
			'code' => $code,
			'name' => h($error->getMessage()),
			'message' => h($error->getMessage()),
			'url' => h($url),
			'error' => $error,
			'_serialize' => array('code', 'name', 'message', 'url', 'error')
		));
		$this->_outputMessage($this->template);
	}

/**
 * Generate the response using the controller object.
 *
 * @param string $template The template to render.
 * @return void
 */
	protected function _outputMessage($template) {
		try {
			$this->controller->render($template);
			$this->controller->afterFilter();
			$this->controller->response->send();
		} catch (MissingViewException $e) {
			$attributes = $e->getAttributes();
			if (isset($attributes['file']) && strpos($attributes['file'], 'error500') !== false) {
				$this->_outputMessageSafe('error500');
			} else {
				$this->_outputMessage('error500');
			}
		} catch (MissingPluginException $e) {
			$attributes = $e->getAttributes();
			if (isset($attributes['plugin']) && $attributes['plugin'] === $this->controller->plugin) {
				$this->controller->plugin = null;
			}
			$this->_outputMessageSafe('error500');
		} catch (Exception $e) {
			$this->_outputMessageSafe('error500');
		}
	}

/**
 * A safer way to render error messages, replaces all helpers, with basics
 * and doesn't call component methods.
 *
 * @param string $template The template to render
 * @return void
 */
	protected function _outputMessageSafe($template) {
		$this->controller->layoutPath = null;
		$this->controller->subDir = null;
		$this->controller->viewPath = 'Errors';
		$this->controller->layout = 'error';
		$this->controller->helpers = array('Form', 'Html', 'Session');

		$view = new View($this->controller);
		$this->controller->response->body($view->render($template, 'error'));
		$this->controller->response->type('html');
		$this->controller->response->send();
	}

}
