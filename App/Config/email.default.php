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

use Cake\Network\Email\Email;
/**
 * Email configuration.
 *
 * You can configure email transports and email delivery profiles here.
 *
 * By defining transports separately from delivery profiles you can eaisly re-use transport
 * configuration across multiple profiles.
 *
 * You can specify multiple configurations for production, development and testing.
 *
 * ### Configuring transports
 *
 * Each transport needs a `className`. Valid options are as follows:
 *
 *  Mail   - Send using PHP mail function
 *  Smtp   - Send using SMTP
 *  Debug  - Do not send the email, just return the result
 *
 * You can add custom transports (or override existing transports) by adding the
 * appropriate file to app/Network/Email.  Transports should be named 'YourTransport.php',
 * where 'Your' is the name of the transport.
 *
 * ### Configuring delivery profiles
 *
 * Delivery profiles allow you to predefine various properties about email messages
 * from your application and give the settings a name. This saves duplication across your
 * application and makes maintenance and development easier. Each profile accepts a number of keys
 * See Cake\Network\Email\Email for more information.
 */
Email::configTransport('default', [
	'className' => 'Mail',
	// The following keys are used in SMTP transports
	'host' => 'localhost',
	'port' => 25,
	'timeout' => 30,
	'username' => 'user',
	'password' => 'secret',
	'client' => null,
	'tls' => null,
]);

Email::config('default', [
	'transport' => 'default',
	'charset' => 'utf-8',
	'headerCharset' => 'utf-8',
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
]);
