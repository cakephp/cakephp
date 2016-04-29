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
    class_alias('Cake\Utility\Text', 'Cake\Utility\String', false);
}

// @deprecated Backward compatibility with 2.x, 3.0.x
class_alias('Cake\Mailer\AbstractTransport', 'Cake\Network\Email\AbstractTransport', false);
class_alias('Cake\Mailer\Transport\DebugTransport', 'Cake\Network\Email\DebugTransport', false);
class_alias('Cake\Mailer\Email', 'Cake\Network\Email\Email', false);
class_alias('Cake\Mailer\Transport\MailTransport', 'Cake\Network\Email\MailTransport', false);
class_alias('Cake\Mailer\Transport\SmtpTransport', 'Cake\Network\Email\SmtpTransport', false);

// @deprecated Backwards compatibility with earler 3.x versions.
class_alias('Cake\Http\Client', 'Cake\Network\Http\Client', false);
class_alias('Cake\Http\Client\CookieCollection', 'Cake\Network\Http\CookieCollection', false);
class_alias('Cake\Http\Client\FormData', 'Cake\Network\Http\FormData', false);
class_alias('Cake\Http\Client\Message', 'Cake\Network\Http\Message', false);
class_alias('Cake\Http\Client\Request', 'Cake\Network\Http\Request', false);
class_alias('Cake\Http\Client\Response', 'Cake\Network\Http\Response', false);
class_alias('Cake\Http\Client\Adapter\Stream', 'Cake\Network\Http\Adapter\Stream', false);
class_alias('Cake\Http\Client\Auth\Basic', 'Cake\Network\Http\Auth\Basic', false);
class_alias('Cake\Http\Client\Auth\Digest', 'Cake\Network\Http\Auth\Digest', false);
class_alias('Cake\Http\Client\Auth\Oauth', 'Cake\Network\Http\Auth\Oauth', false);
class_alias('Cake\Http\Client\FormDataPart', 'Cake\Network\Http\FormData\Part', false);

require CAKE . 'basics.php';

// Sets the initial router state so future reloads work.
Router::reload();
