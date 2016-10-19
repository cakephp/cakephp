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
 * @since         3.4.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

class_alias(\Cake\Http\ServerRequest::class, \Cake\Network\Request::class);
class_alias(\Cake\Mailer\AbstractTransport::class, \Cake\Network\Email\AbstractTransport::class);
class_alias(\Cake\Mailer\Transport\DebugTransport::class, \Cake\Network\Email\DebugTransport::class);
class_alias(\Cake\Mailer\Email::class, \Cake\Network\Email\Email::class);
class_alias(\Cake\Mailer\Transport\MailTransport::class, \Cake\Network\Email\MailTransport::class);
class_alias(\Cake\Mailer\Transport\SmtpTransport::class, \Cake\Network\Email\SmtpTransport::class);
class_alias(\Cake\Http\Client::class, \Cake\Network\Http\Client::class);
class_alias(\Cake\Http\Client\CookieCollection::class, \Cake\Network\Http\CookieCollection::class);
class_alias(\Cake\Http\Client\FormData::class, \Cake\Network\Http\FormData::class);
class_alias(\Cake\Http\Client\Message::class, \Cake\Network\Http\Message::class);
class_alias(\Cake\Http\Client\Request::class, \Cake\Network\Http\Request::class);
class_alias(\Cake\Http\Client\Response::class, \Cake\Network\Http\Response::class);
class_alias(\Cake\Http\Client\Adapter\Stream::class, \Cake\Network\Http\Adapter\Stream::class);
class_alias(\Cake\Http\Client\Auth\Basic::class, \Cake\Network\Http\Auth\Basic::class);
class_alias(\Cake\Http\Client\Auth\Digest::class, \Cake\Network\Http\Auth\Digest::class);
class_alias(\Cake\Http\Client\Auth\Oauth::class, \Cake\Network\Http\Auth\Oauth::class);
class_alias(\Cake\Http\Client\FormDataPart::class, \Cake\Network\Http\FormData\Part::class);
