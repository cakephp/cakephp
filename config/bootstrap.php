<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

define('TIME_START', microtime(true));

// Compatibility aliases. These will be removed for the first RC release.
class_alias('Cake\Error\Debugger', 'Cake\Utility\Debugger');
class_alias('Cake\Core\Configure\Engine\PhpConfig', 'Cake\Configure\Engine\PhpConfig');
class_alias('Cake\Core\Configure\Engine\IniConfig', 'Cake\Configure\Engine\IniConfig');
class_alias('Cake\Error\BadRequestException', 'Cake\Network\Exception\BadRequestException');
class_alias('Cake\Error\ForbiddenException', 'Cake\Network\Exception\ForbiddenException');
class_alias('Cake\Error\HttpException', 'Cake\Network\Exception\HttpException');
class_alias('Cake\Error\InternalErrorException', 'Cake\Network\Exception\InternalErrorException');
class_alias('Cake\Error\MethodNotAllowedException', 'Cake\Network\Exception\MethodNotAllowedException');
class_alias('Cake\Error\NotFoundException', 'Cake\Network\Exception\NotFoundException');
class_alias('Cake\Error\NotImplementedException', 'Cake\Network\Exception\NotImplementedException');
class_alias('Cake\Error\SocketException', 'Cake\Network\Exception\SocketException');
class_alias('Cake\Error\UnauthorizedException', 'Cake\Network\Exception\UnauthorizedException');
class_alias('Cake\Error\Exception', 'Cake\Core\Exception\Exception');

require CAKE . 'basics.php';
