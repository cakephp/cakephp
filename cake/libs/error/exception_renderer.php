<?php
/**
 * Exception Renderer
 *
 * Provides Exception rendering features.  Which allow exceptions to be rendered
 * as HTML pages.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.error
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * Exception Renderer.
 *
 * Captures and handles all unhandled exceptions. Displays helpful framework errors when debug > 1.
 * When debug < 1 a CakeException will render 404 or  500 errors.  If an uncaught exception is thrown
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
 * This controller method is called instead of the default exception handling.  It receives the 
 * thrown exception as its only argument.  You should implement your error handling in that method.
 *
 * #### Using a subclass of ExceptionRenderer
 *
 * Using a subclass of ExceptionRenderer gives you full control over how Exceptions are rendered, you 
 * can configure your class in your core.php, with `Configure::write('Exception.renderer', 'MyClass');`
 * You should place any custom exception renderers in `app/libs`.
 *
 * @package       cake.libs.error
 */
class ExceptionRenderer {

/**
 * Controller instance.
 *
 * @var Controller
 * @access public
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
 * @param string $method Method producing the error
 * @param array $messages Error messages
 */
	function __construct(Exception $exception) {
		App::import('Core', 'Sanitize');

		$this->controller = $this->_getController($exception);

		if (method_exists($this->controller, 'apperror')) {
			return $this->controller->appError($exception);
		}
		$method = $template = Inflector::variable(str_replace('Exception', '', get_class($exception)));
		$code = $exception->getCode();

		$methodExists = method_exists($this, $method);

		if ($exception instanceof CakeException && !$methodExists) {
			$method = '_cakeError';
			if ($template == 'internalError') {
				$template = 'error500';
			}
		} elseif (!$methodExists) {
			$method = 'error500';
			if ($code >= 400 && $code < 500) {
				$method = 'error400';
			}
		}

		if (Configure::read('debug') == 0) {
			$parentClass = get_parent_class($this);
			if ($parentClass != __CLASS__) {
				$method = 'error400';
			}
			$parentMethods = (array)get_class_methods($parentClass);
			if (in_array($method, $parentMethods)) {
				$method = 'error400';
			}
			if ($code == 500) {
				$method = 'error500';
			}
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
		static $__previousError = null;
		App::import('Controller', 'CakeError');

		if ($__previousError != $exception) {
			$__previousError = $exception;
			$controller = new CakeErrorController();
		} else {
			$controller = new Controller();
			$controller->viewPath = 'errors';
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
 * @param CakeExeption $error
 * @return void
 */
	protected function _cakeError(CakeException $error) {
		$url = Router::normalize($this->controller->request->here);
		$code = $error->getCode();
		$this->controller->response->statusCode($code);
		$this->controller->set(array(
			'code' => $code,
			'url' => h($url),
			'name' => $error->getMessage(),
			'error' => $error,
		));
		$this->controller->set($error->getAttributes());
		$this->_outputMessage($this->template);
	}

/**
 * Convenience method to display a 400 series page.
 *
 * @param array $params Parameters for controller
 */
	public function error400($error) {
		$message = $error->getMessage();
		if (Configure::read('debug') == 0 && $error instanceof CakeException) {
			$message = __('Not Found');
		}
		$url = Router::normalize($this->controller->request->here);
		$this->controller->response->statusCode($error->getCode());
		$this->controller->set(array(
			'name' => $message,
			'url' => h($url),
			'error' => $error,
		));
		$this->_outputMessage('error400');
	}

/**
 * Convenience method to display a 500 page.
 *
 * @param array $params Parameters for controller
 */
	public function error500($error) {
		$url = Router::normalize($this->controller->request->here);
		$code = ($error->getCode() > 500) ? $error->getCode() : 500;
		$this->controller->response->statusCode($code);
		$this->controller->set(array(
			'name' => __('An Internal Error Has Occurred'),
			'message' => h($url),
			'error' => $error,
		));
		$this->_outputMessage('error500');
	}

/**
 * Generate the response using the controller object.
 *
 * @param string $template The template to render.
 */
	protected function _outputMessage($template) {
		$this->controller->render($template);
		$this->controller->afterFilter();
		$this->controller->response->send();
	}
}