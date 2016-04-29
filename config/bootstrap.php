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

// @deprecated Backwards compatibility with earler 3.x versions.
class_alias('Cake\Http\Client', 'Cake\Network\Http\Client');
class_alias('Cake\Http\Client\CookieCollection', 'Cake\Network\Http\CookieCollection');
class_alias('Cake\Http\Client\FormData', 'Cake\Network\Http\FormData');
class_alias('Cake\Http\Client\Message', 'Cake\Network\Http\Message');
class_alias('Cake\Http\Client\Request', 'Cake\Network\Http\Request');
class_alias('Cake\Http\Client\Response', 'Cake\Network\Http\Response');
class_alias('Cake\Http\Client\Adapter\Stream', 'Cake\Network\Http\Adapter\Stream');
class_alias('Cake\Http\Client\Auth\Basic', 'Cake\Network\Http\Auth\Basic');
class_alias('Cake\Http\Client\Auth\Digest', 'Cake\Network\Http\Auth\Digest');
class_alias('Cake\Http\Client\Auth\Oauth', 'Cake\Network\Http\Auth\Oauth');
class_alias('Cake\Http\Client\FormDataPart', 'Cake\Network\Http\FormData\Part');

require CAKE . 'basics.php';

// Sets the initial router state so future reloads work.
Router::reload();
