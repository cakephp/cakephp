<?php
declare(strict_types=1);

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

use Cake\Core\Exception\Exception;
use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Message;

/**
 * Send mail using mail() function
 */
class MailTransport extends AbstractTransport
{
    /**
     * @inheritDoc
     */
    public function send(Message $message): array
    {
        $this->checkRecipient($message);

        $eol = PHP_EOL;
        if (isset($this->_config['eol'])) {
            $eol = $this->_config['eol'];
        }
        $to = $message->getHeaders(['to'])['To'];
        $headers = $message->getHeadersString(
            [
                'from',
                'sender',
                'replyTo',
                'readReceipt',
                'returnPath',
                'cc',
                'bcc',
            ],
            function ($val) {
                return str_replace(["\r", "\n"], '', $val);
            },
            $eol
        );

        $subject = str_replace(["\r", "\n"], '', $message->getSubject());
        $to = str_replace(["\r", "\n"], '', $to);

        $message = implode($eol, (array)$message->getBody());

        $params = $this->_config['additionalParameters'] ?? '';
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
     * @param string $params additional params for sending email
     * @throws \Cake\Network\Exception\SocketException if mail could not be sent
     * @return void
     */
    protected function _mail(
        string $to,
        string $subject,
        string $message,
        string $headers = '',
        string $params = ''
    ): void {
        // phpcs:disable
        if (!@mail($to, $subject, $message, $headers, $params)) {
            $error = error_get_last();
            $msg = 'Could not send email: ' . ($error['message'] ?? 'unknown');
            throw new Exception($msg);
        }
        // phpcs:enable
    }
}
