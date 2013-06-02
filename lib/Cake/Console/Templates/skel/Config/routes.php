<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * PHP 5
 *
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 */
namespace App\Config;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Routing\Router;

/**
 * Uncomment the define below to use CakePHP prefix routes.
 *
 * The value of the define determines the names of the routes
 * and their associated controller actions:
 *
 * Set to an array of prefixes you want to use in your application. Use for
 * admin or other prefixed routes.
 *
 * Routing.prefixes = array('admin', 'manager');
 *
 * Enables:
 *  `App\Controller\Admin` and `/admin/controller/index`
 *  `App\Controller\Manager` and `/manager/controller/index`
 *
 */
	// Configure::write('Routing.prefixes', array('admin'));

/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/View/Pages/home.ctp)...
 */
	Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
/**
 * ...and connect the rest of 'Pages' controller's URLs.
 */
	Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));

/**
 * Load all plugin routes. See the Plugin documentation on
 * how to customize the loading of plugin routes.
 */
	Plugin::routes();

/**
 * Load the CakePHP default routes. Only remove this if you do not want to use
 * the built-in default routes.
 */
	require CAKE . 'Config/routes.php';
