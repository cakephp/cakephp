<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace App\Config;

use Cake\Model\Datasource\Session;

/**
 * Session configuration.
 *
 * Contains an array of settings to use for session configuration. The defaults key is
 * used to define a default preset to use for sessions, any settings declared here will override
 * the settings of the default config.
 *
 * ## Options
 *
 * - `cookie` - The name of the cookie to use. Defaults to 'CAKEPHP'
 * - `timeout` - The number of minutes you want sessions to live for. This timeout is handled by CakePHP
 * - `cookieTimeout` - The number of minutes you want session cookies to live for.
 * - `checkAgent` - Do you want the user agent to be checked when starting sessions? You might want to set the
 *    value to false, when dealing with older versions of IE, Chrome Frame or certain web-browsing devices and AJAX
 * - `defaults` - The default configuration set to use as a basis for your session.
 *    There are four builtins: php, cake, cache, database.
 * - `handler` - Can be used to enable a custom session handler.  Expects an array of of callables,
 *    that can be used with `session_save_handler`.  Using this option will automatically add `session.save_handler`
 *    to the ini array.
 * - `autoRegenerate` - Enabling this setting, turns on automatic renewal of sessions, and
 *    sessionids that change frequently. See Cake\Model\Datasource\Session::$requestCountdown.
 * - `ini` - An associative array of additional ini values to set.
 *
 * The built in defaults are:
 *
 * - 'php' - Uses settings defined in your php.ini.
 * - 'cake' - Saves session files in CakePHP's /tmp directory.
 * - 'database' - Uses CakePHP's database sessions.
 * - 'cache' - Use the Cache class to save sessions.
 *
 * To define a custom session handler, save it at /app/Model/Datasource/Session/<name>.php.
 * Make sure the class implements `Cake\Model\Datasource\Session\SessionHandlerInterface` 
 * and set Session.handler to <name>
 *
 * To use database sessions, run the app/Config/Schema/sessions.php schema using
 * the cake shell command: cake schema create Sessions
 *
 */
	Session::config(array(
		'defaults' => 'php'
	));
