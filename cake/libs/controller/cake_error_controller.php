<?php
/**
 * Error Handling Controller
 *
 * Controller used by ErrorHandler to render error views.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class CakeErrorController extends AppController {
	public $name = 'CakeError';

/**
 * Uses Property
 *
 * @var array
 */
	public $uses = array();

/**
 * __construct
 *
 * @access public
 * @return void
 */
	function __construct() {
		parent::__construct();
		$this->_set(Router::getPaths());
		$this->request = $this->params = Router::getRequest();
		$this->constructClasses();
		$this->Components->trigger('initialize', array(&$this));
		$this->_set(array('cacheAction' => false, 'viewPath' => 'errors'));
	}
}