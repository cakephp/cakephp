<?php
/**
 * Index
 *
 * The Front Controller for handling every request
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.webroot
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
// for built-in server
if (php_sapi_name() == 'cli-server') {
	$_SERVER['PHP_SELF'] = '/' . basename(__FILE__);
}
require dirname(__DIR__) . '/Config/bootstrap.php';

use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Dispatcher;

$Dispatcher = new Dispatcher();
$Dispatcher->dispatch(
	Request::createFromGlobals(),
	new Response(array('charset' => Configure::read('App.encoding')))
);
