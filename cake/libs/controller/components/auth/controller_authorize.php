<?php


class ControllerAuthorize {
/**
 * Controller for the request.
 *
 * @var Controller
 */
	protected $_controller = null;

/**
 * Constructor
 *
 * @param Controller $controller The controller for this request.
 * @param string $settings An array of settings.  This class does not use any settings.
 */
	public function __construct(Controller $controller, $settings = array()) {
		$this->controller($controller);
	}

/**
 * Checks user authorization using a controller callback.
 *
 * @param array $user Active user data
 * @param CakeRequest $request 
 * @return boolean
 */
	public function authorize($user, CakeRequest $request) {
		return (bool) $this->_controller->isAuthorized($user);
	}

/**
 * Accessor to the controller object.
 *
 * @param mixed $controller null to get, a controller to set.
 * @return mixed.
 */
	public function controller($controller = null) {
		if ($controller) {
			if (!$controller instanceof Controller) {
				throw new CakeException(__('$controller needs to be an instance of Controller'));
			}
			if (!method_exists($controller, 'isAuthorized')) {
				throw new CakeException(__('$controller does not implement an isAuthorized() method.'));
			}
			$this->_controller = $controller;
			return true;
		}
		return $this->_controller;
	}
}