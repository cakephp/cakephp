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

$logConfig = [];

/**
 * Configures default file logging options
 */
$logConfig['debug'] = [
	'engine' => 'Cake\Log\Engine\FileLog',
	'levels' => ['notice', 'info', 'debug'],
	'file' => 'debug',
];
$logConfig['error'] = [
	'engine' => 'Cake\Log\Engine\FileLog',
	'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
	'file' => 'error',
];

Log::config($logConfig);
