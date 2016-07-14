<?php
/**
 * Emulates the email sending process for testing purposes
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;

/**
 * Debug Transport class, useful for emulate the email sending process and inspect the resulted
 * email message before actually send it during development
 *
 */
class DebugTransport extends AbstractTransport
{

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Email $email Cake Email
     * @return array
     */
    public function send(Email $email)
    {
        $headers = $email->getHeaders(['from', 'sender', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'subject']);
        $headers = $this->_headersToString($headers);
        $message = implode("\r\n", (array)$email->message());

        return ['headers' => $headers, 'message' => $message];
    }
}
