<?php
/**
 * Send mail using mail() function
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;
use Cake\Network\Exception\SocketException;

/**
 * Send mail using mail() function
 */
class MailTransport extends AbstractTransport
{
    /**
     * Send mail
     *
     * @param \Cake\Mailer\Email $email Cake Email
     * @return array
     */
    public function send(Email $email)
    {
        $eol = PHP_EOL;
        if (isset($this->_config['eol'])) {
            $eol = $this->_config['eol'];
        }
        $headers = $email->getHeaders(['from', 'sender', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc']);
        $to = $headers['To'];
        unset($headers['To']);
        foreach ($headers as $key => $header) {
            $headers[$key] = str_replace(["\r", "\n"], '', $header);
        }
        $headers = $this->_headersToString($headers, $eol);
        $subject = str_replace(["\r", "\n"], '', $email->getSubject());
        $to = str_replace(["\r", "\n"], '', $to);

        $message = implode($eol, $email->message());

        $params = isset($this->_config['additionalParameters']) ? $this->_config['additionalParameters'] : null;
        $this->_mail($to, $subject, $message, $headers, $params);

        $headers .= $eol . 'To: ' . $to;
        $headers .= $eol . 'Subject: ' . $subject;

        return ['headers' => $headers, 'message' => $message];
    }

    /**
     * Wraps internal function mail() and throws exception instead of errors if anything goes wrong
     *
     * @param string $to email's recipient
     * @param string $subject email's subject
     * @param string $message email's body
     * @param string $headers email's custom headers
     * @param string|null $params additional params for sending email
     * @throws \Cake\Network\Exception\SocketException if mail could not be sent
     * @return void
     */
    protected function _mail($to, $subject, $message, $headers, $params = null)
    {
        //@codingStandardsIgnoreStart
        if (!@mail($to, $subject, $message, $headers, $params)) {
            $error = error_get_last();
            $msg = 'Could not send email: ' . (isset($error['message']) ? $error['message'] : 'unknown');
            throw new SocketException($msg);
        }
        //@codingStandardsIgnoreEnd
    }
}
