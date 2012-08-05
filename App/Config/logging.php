<?php
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
