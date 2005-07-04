<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
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
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Add Description
 */
define('DISPATCH_NO_CONTROLLER',      'missingController');
define('DISPATCH_UNKNOWN_CONTROLLER', 'missingController');
define('DISPATCH_NO_ACTION',          'missingAction');
define('DISPATCH_UNKNOWN_ACTION',     'missingAction');
define('DISPATCHER_UNKNOWN_VIEW',     'missingView');

/**
 * Add Description
 */
uses('error_messages', 'object', 'router', 'controller');

/**
 * Dispatches the request, creating appropriate models and controllers.
 *
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 */
class Dispatcher extends Object
{
	/**
	 * Base URL
	 * @var string
	 */
	var $base = false;

	/**
	 * Fetches base url.
	 */
	function __construct()
	{
		$this->base = $this->baseUrl();
		parent::__construct();
	}

	/**
	 * Dispatches the request (action).
	 *
	 * @param string $url
	 * @return array
	 */
	function dispatch($url)
	{
		$params = $this->parseParams($url);
		$result = $this->invoke($url);

		return $result === true? $params: array();
	}

	/**
	 * Enter description here...
	 *
	 * @param string $url
	 * @return unknown
	 */
	function invoke($url)
	{
		global $_POST, $_GET, $_FILES, $_SESSION;

		$params = $this->parseParams($url);
		$missingController = false;
		$missingAction     = false;
		$missingView       = false;

		if (empty($params['controller']))
		{
			$missingController = true;
		}
		else
		{
			$ctrlName  = Inflector::camelize($params['controller']);
			$ctrlClass = $ctrlName.'Controller';

			if (!loadController($ctrlName) || !class_exists($ctrlClass))
			{
				$missingController = true;
			}
		}

		if ($missingController)
		{
			$ctrlClass        = 'AppController';
			$controller       = new $ctrlClass($this);
			$params['action'] = 'missingController';
			$controller->missingController = $params['controller'];
		}
		else
		{
			// create controller
			$controller = new $ctrlClass($this);
		}

		// if action is not set, and the default Controller::index() method doesn't exist
		if (empty($params['action']))
		{
			if (method_exists($controller, 'index'))
			{
				$params['action'] = 'index';
			}
			else
			{
				$missingAction = true;
			}
		}

		// if the requested action doesn't exist
		if (!method_exists($controller, $params['action']))
		{
			$missingAction = true;
		}

		if ($missingAction)
		{
			$controller->missingAction = $params['action'];
			$params['action'] = 'missingAction';
		}

		// initialize the controller
		$controller->base        = $this->base;
		$controller->here        = $this->base.'/'.$url;
		$controller->params      = $params;
		$controller->action      = $params['action'];
		$controller->data        = empty($params['data'])? null: $params['data'];
		$controller->passed_args = empty($params['pass'])? null: $params['pass'];

		// EXECUTE THE REQUESTED ACTION
		call_user_func_array(array(&$controller, $params['action']), empty($params['pass'])? null: $params['pass']);

		$isFatal = isset($controller->isFatal) ? $controller->isFatal : false;

		if ($isFatal)
		{
			switch($params['action'])
			{
				case 'missingController':
				$this->errorUnknownController($url, $ctrlName);
				break;

				case 'missingAction':
				$this->errorUnknownAction($url, $ctrlClass, $controller->missingAction);
				break;
			}
		}

		if ($controller->autoRender)
		{
			$controller->render();
		}

		return true;
	}

	/**
	 * Returns array of GET and POST parameters. GET parameters are taken from given URL.
	 *
	 * @param string $from_url
	 * @return array Parameters found in POST and GET.
	 */
	function parseParams($from_url)
	{
		global $_POST, $_FILES;

		// load routes config
		$Route = new Router();
		include CONFIGS.'routes.php';
		$params = $Route->parse ($from_url);

		// add submitted form data
		$params['form'] = $_POST;
		if (isset($_POST['data']))
		{
			$params['data'] = $_POST['data'];
		}

		foreach ($_FILES as $name => $data)
		{
			$params['form'][$name] = $data;
		}

		return $params;
	}

	/**
	 * Returns a base URL.
	 *
	 * @return string
	 */
	function baseUrl()
	{
		global $_SERVER;

		//non mod_rewrite use:
		if (defined('BASE_URL')) return BASE_URL;

		$doc_root = $_SERVER['DOCUMENT_ROOT'];
		$script_name = $_SERVER['PHP_SELF'];

		// if document root ends with 'public', it's probably correctly set
		$r = null;
		if (ereg('/^.*/public(\/)?$/', $doc_root))
		return preg_match('/^(.*)\/index\.php$/', $script_name, $r)? $r[1]: false;
		else
		// document root is probably not set to Cake 'public' dir
		return preg_match('/^(.*)\/public\/index\.php$/', $script_name, $r)? $r[1]: false;
	}

	/**
	 * Displays an error page (e.g. 404 Not found).
	 *
	 * @param int $code Error code (e.g. 404)
	 * @param string $name Name of the error message (e.g. Not found)
	 * @param string $message
	 */
	function error ($code, $name, $message)
	{
		$controller = new Controller ($this);
		$controller->base = $this->base;
		$controller->error($code, $name, $message);
	}

	/**
	 * Convenience method to display a 404 page.
	 *
	 * @param unknown_type $url
	 * @param unknown_type $message
	 */
	function error404 ($url, $message)
	{
		$this->error('404', 'Not found', sprintf(ERROR_404, $url, $message));
	}

	/**
 * If DEBUG is set, this displays a 404 error with the message that no controller is set. If DEBUG is not set, nothing happens.
 *
 * @param string $url
 */
	function errorNoController ($url)
	{
		DEBUG?
		trigger_error (ERROR_NO_CONTROLLER_SET, E_USER_ERROR):
		$this->error404($url, "no controller set");
		exit;
	}

	/**
 * If DEBUG is set, this displays a 404 error with the message that the asked-for controller does not exist. If DEBUG is not set, nothing happens.
 *
 * @param string $url
 * @param string $controller_class
 */
	function errorUnknownController ($url, $controller_class)
	{
		DEBUG?
		trigger_error (sprintf(ERROR_UNKNOWN_CONTROLLER, $controller_class), E_USER_ERROR):
		$this->error404($url, "missing controller \"{$controller_class}\"");
		exit;
	}

	/**
 * If DEBUG is set, this displays a 404 error with the message that no action is set. If DEBUG is not set, nothing happens.
 *
 * @param string $url
 */
	function errorNoAction ($url)
	{
		DEBUG?
		trigger_error (ERROR_NO_ACTION_SET, E_USER_ERROR):
		$this->error404(sprintf(ERROR_404, $url, "no action set"));
		exit;
	}

	/**
 * If DEBUG is set, this displays a 404 error with the message that no such action exists. If DEBUG is not set, nothing happens.
 *
 * @param string $url
 * @param string $controller_class
 * @param string $action
 */
	function errorUnknownAction ($url,$controller_class, $action)
	{
		DEBUG?
		trigger_error (sprintf(ERROR_NO_ACTION, $action, $controller_class), E_USER_ERROR):
		$this->error404($url, "missing controller \"{$controller_class}\"");
		exit;
	}
}

?>
