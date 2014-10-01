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
class_alias('Cake\ORM\Exception\RecordNotFoundException', 'Cake\ORM\Error\RecordNotFoundException');
class_alias('Cake\Network\Exception\BadRequestException', 'Cake\Error\BadRequestException');
class_alias('Cake\Network\Exception\ForbiddenException', 'Cake\Error\ForbiddenException');
class_alias('Cake\Network\Exception\MethodNotAllowedException', 'Cake\Error\MethodNotAllowedException');
class_alias('Cake\Network\Exception\NotFoundException', 'Cake\Error\NotFoundException');
class_alias('Cake\Network\Exception\NotImplementedException', 'Cake\Error\NotImplementedException');
class_alias('Cake\Network\Exception\SocketException', 'Cake\Error\SocketException');
class_alias('Cake\Network\Exception\UnauthorizedException', 'Cake\Error\UnauthorizedException');
class_alias('Cake\Filesystem\File', 'Cake\Utility\File');
class_alias('Cake\Filesystem\Folder', 'Cake\Utility\Folder');
class_alias('Cake\I18n\Time', 'Cake\Utility\Time');
class_alias('Cake\I18n\Number', 'Cake\Utility\Number');

require CAKE . 'basics.php';
