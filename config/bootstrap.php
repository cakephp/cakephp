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

use Cake\Datasource\FactoryLocator;
use Cake\Routing\Router;

define('TIME_START', microtime(true));

// @deprecated Backward compatibility with 2.x series
if (PHP_VERSION_ID < 70000) {
    class_alias('Cake\Utility\Text', 'Cake\Utility\String');
}

// @deprecated Backward compatibility with 2.x, 3.0.x
class_alias('Cake\Mailer\AbstractTransport', 'Cake\Network\Email\AbstractTransport');
class_alias('Cake\Mailer\Transport\DebugTransport', 'Cake\Network\Email\DebugTransport');
class_alias('Cake\Mailer\Email', 'Cake\Network\Email\Email');
class_alias('Cake\Mailer\Transport\MailTransport', 'Cake\Network\Email\MailTransport');
class_alias('Cake\Mailer\Transport\SmtpTransport', 'Cake\Network\Email\SmtpTransport');

require CAKE . 'basics.php';

// Sets the initial router state so future reloads work.
Router::reload();
