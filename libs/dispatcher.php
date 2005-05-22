<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Dispatcher
  * Dispatches the request, creating aproppriate models and controllers.
  * 
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

/**
 * Description:
 * Dispatches the request, creating aproppriate models and controllers.
 */

uses('error_messages', 'object', 'router', 'controller');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Dispatcher extends Object {
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $base = false;
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $passed_args = array();

/**
  * Enter description here...
  *
  */
	function __construct () {
		$this->base = $this->baseUrl();
		parent::__construct();
	}

/**
  * Enter description here...
  *
  * @param unknown_type $url
  * @return unknown
  */
	function dispatch ($url) {
		global $_POST, $_GET, $_FILES, $_SESSION;

		$params = $this->parseParams($url);

		// die if no controller set
		if (empty($params['controller']))
			$this->errorNoController($url);

		$ctrl_name = ucfirst($params['controller']);
		$ctrl_class = $ctrl_name.'Controller';

		// if specified controller class doesn't exist
		if (!loadController($ctrl_name) || !class_exists($ctrl_class))
			$this->errorUnknownController($url, $ctrl_name);

		$controller = new $ctrl_class ($this);
		$controller->cache = &$Cache;
		$controller->base = $this->base;

		// if action is not set, and the default Controller::index() method doesn't exist 
		if (empty($params['action'])) {
			if (method_exists($controller, 'index'))
				$params['action'] = 'index';
			else
				$this->errorNoAction($url);
		}

		// if the requested action doesn't exist
		if (!method_exists($controller, $params['action']))
			$this->errorUnknownAction($url, $ctrl_class, $params['action']);

		$controller->params = $params;
		$controller->action = $params['action'];
		$controller->data = empty($params['data'])? null: $params['data'];
		$controller->passed_args = empty($params['pass'])? null: $params['pass'];			

		// EXECUTE THE REQUESTED ACTION
		call_user_func_array(array(&$controller, $params['action']), empty($params['pass'])? null: $params['pass']);

		if ($controller->autoRender)
			$controller->render();

		return $params;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $from_url
  * @return unknown
  */
	function parseParams ($from_url) {
		global $_POST, $_FILES;

		// load routes config
		$Route = new Router();
		require CONFIGS.'routes.php';
		$params = $Route->parse ('/'.$from_url);

		// add submitted form data
		$params['form'] = $_POST;
		if (isset($_POST['data']))
			$params['data'] = $_POST['data'];

		foreach ($_FILES as $name => $data)
			$params['form'][$name] = $data;

		return $params;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function baseUrl () {
		global $_SERVER;

		//non mod_rewrite use:
		if (defined('BASE_URL')) return BASE_URL;

		$doc_root = $_SERVER['DOCUMENT_ROOT'];
		$script_name = $_SERVER['PHP_SELF'];

		// if document root ends with 'public', it's probably correctly set
		$r = null;
		if (!ereg('/^.*/public(\/)?$/', $doc_root))
			return preg_match('/^(.*)\/public\/index\.php$/', $script_name, $r)? $r[1]: false;
		// document root is probably not set to Cake 'public' dir
		else
			return preg_match('/^(.*)\/index\.php$/', $script_name, $r)? $r[1]: false;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $code
  * @param unknown_type $name
  * @param unknown_type $message
  */
	function error ($code, $name, $message) {
		$controller = new Controller ($this);
		$controller->base = $this->base;
		$controller->error($code, $name, $message);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $url
  * @param unknown_type $message
  */
	function error404 ($url, $message) {
		$this->error('404', 'Not found', sprintf(ERROR_404, $url, $message));
	}

/**
  * Enter description here...
  *
  * @param unknown_type $url
  */
	function errorNoController ($url) {
		DEBUG?
			trigger_error (ERROR_NO_CONTROLLER_SET, E_USER_ERROR): 
			$this->error404($url, "no controller set");
		exit;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $url
  * @param unknown_type $controller_class
  */
	function errorUnknownController ($url, $controller_class) {
		DEBUG? 
			trigger_error (sprintf(ERROR_UNKNOWN_CONTROLLER, $controller_class), E_USER_ERROR): 
			$this->error404($url, "missing controller \"{$controller_class}\"");
		exit;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $url
  */
	function errorNoAction ($url) {
		DEBUG? 
			trigger_error (ERROR_NO_ACTION_SET, E_USER_ERROR):
			$this->error404(sprintf(ERROR_404, $url, "no action set"));
		exit;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $url
  * @param unknown_type $controller_class
  * @param unknown_type $action
  */
	function errorUnknownAction ($url,$controller_class, $action) {
		DEBUG? 
			trigger_error (sprintf(ERROR_NO_ACTION, $action, $controller_class), E_USER_ERROR): 
			$this->error404($url, "missing controller \"{$controller_class}\"");
		exit;
	}
}

?>