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

use Cake\Log\Log;

/**
 * Defines the default error type when using the log() function. Used for
 * differentiating error logging and debugging. Currently PHP supports LOG_DEBUG.
 */
	define('LOG_ERROR', LOG_ERR);

/**
 * Configures default file logging options
 */
Log::config('debug', array(
	'engine' => 'Cake\Log\Engine\FileLog',
	'types' => array('notice', 'info', 'debug'),
	'file' => 'debug',
));
Log::config('error', array(
	'engine' => 'Cake\Log\Engine\FileLog',
	'types' => array('warning', 'error', 'critical', 'alert', 'emergency'),
	'file' => 'error',
));
