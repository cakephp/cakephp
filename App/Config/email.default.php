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
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace App\Config;

use Cake\Core\Configure;
/**
 * Email configuration.
 *
 * You can specify multiple configurations for production, development and testing.
 *
 * transport => The name of a supported transport; valid options are as follows:
 *
 *  Mail   - Send using PHP mail function
 *  Smtp   - Send using SMTP
 *  Debug  - Do not send the email, just return the result
 *
 * You can add custom transports (or override existing transports) by adding the
 * appropriate file to app/Network/Email.  Transports should be named 'YourTransport.php',
 * where 'Your' is the name of the transport.
 *
 * from =>
 * The origin email. See Cake\Network\Email\Email::from() about the valid values
 */
Configure::write('Email.default', [
	'transport' => 'Mail',
	'from' => 'you@localhost',
	//'charset' => 'utf-8',
	//'headerCharset' => 'utf-8',
]);

Configure::write('Email.smtp', [
	'transport' => 'Smtp',
	'host' => 'localhost',
	'port' => 25,
	'timeout' => 30,
	'username' => 'user',
	'password' => 'secret',
	'client' => null,
	'from' => ['site@localhost' => 'My Site'],
	'sender' => null,
	'to' => null,
	'cc' => null,
	'bcc' => null,
	'replyTo' => null,
	'readReceipt' => null,
	'returnPath' => null,
	'messageId' => true,
	'subject' => null,
	'message' => null,
	'headers' => null,
	'viewRender' => null,
	'template' => false,
	'layout' => false,
	'viewVars' => null,
	'attachments' => null,
	'emailFormat' => null,
	'log' => true,
	//'charset' => 'utf-8',
	//'headerCharset' => 'utf-8',
]);
