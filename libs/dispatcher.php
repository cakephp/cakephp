<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Dispatcher
  * Dispatches the request, creating aproppriate models and controllers.
  * 
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
  */

/**
 * Enter description here...
 *
 */
uses('error_messages', 'object', 'router', 'cache', 'controller');

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

        if (CACHE_PAGES) {
            $Cache = new Cache($url);
            if ($Cache->has()) return print $Cache->restore();
        }

        $this->base = $this->parse_base_url();

        $params = $this->parse_params($url);

        if (empty($params['controller'])) {
            DEBUG?
            trigger_error (ERROR_NO_CONTROLLER_SET, E_USER_ERROR):
            $this->error('404', 'Not found', "The requested URL /{$url} was not found on this server (no controller set).");
            exit;
        }

        $controller_class = ucfirst($params['controller']).'Controller';

        if (!class_exists($controller_class)) {
            DEBUG?
            trigger_error (sprintf(ERROR_UNKNOWN_CONTROLLER, $controller_class), E_USER_ERROR):
            $this->error('404', 'Not found', sprintf(ERROR_404, $url, "missing controller \"{$params['controller']}\""));
            exit;
        }

        $controller = new $controller_class ($this);
        $controller->cache = &$Cache;
        $controller->base = $this->base;

        // if action is not set, and the default Controller::index() method doesn't exist
        if (!$params['action'] && !method_exists($controller, 'index')) {
            DEBUG?
            trigger_error (ERROR_NO_ACTION_SET, E_USER_ERROR):
            $this->error('404', 'Not found', "The requested URL /{$url} was not found on this server (no action set).");
            exit;
        }
        elseif (empty($params['action'])) {
            $params['action'] = 'index';
        }

        // if the requested action doesn't exist
        if (!method_exists($controller, $params['action'])) {
            DEBUG?
            trigger_error (sprintf(ERROR_NO_ACTION, $params['action'], $controller_class), E_USER_ERROR):
            $this->error('404', 'Not found', sprintf(ERROR_404, $url, "missing controller \"{$params['controller']}\""));
            exit;
        }

        $controller->params = $params;
        empty($params['data'])? null: $controller->data = $params['data'];
        $controller->action = $params['action'];
        $controller->passed_args = empty($params['pass'])? null: $params['pass'];

        // EXECUTE THE REQUESTED ACTION
        call_user_func_array(array(&$controller, $params['action']), empty($params['pass'])? null: $params['pass']);

        if ($controller->auto_render)
        $controller->render();

        if (CACHE_PAGES) $Cache->remember(null);

        return $params;
    }

/**
  * Enter description here...
  *
  * @param unknown_type $from_url
  * @return unknown
  */
    function parse_params ($from_url) {
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
    function parse_base_url () {
        global $_SERVER;
        //non mod_rewrite use:
		if (defined('BASE_URL')) return BASE_URL;


        $doc_root = $_SERVER['DOCUMENT_ROOT'];
        $script_name = $_SERVER['SCRIPT_NAME'];

        // if document root ends with 'public', it's probably correctly set
        $r = null;
        if (!ereg('/^.*/public(\/)?$/', $doc_root))
        return preg_match('/^(.*)\/public\/dispatch\.php$/', $script_name, $r)? $r[1]: false;
        // document root is probably not set to Cake 'public' dir
        else
        return preg_match('/^(.*)\/dispatch\.php$/', $script_name, $r)? $r[1]: false;
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
}

?>
