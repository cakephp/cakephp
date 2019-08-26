<?php
declare(strict_types=1);

/**
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
namespace Cake\Mailer;

use Cake\Core\Exception\Exception;
use Cake\Core\InstanceConfigTrait;

/**
 * Abstract transport for sending email
 */
abstract class AbstractTransport
{
    use InstanceConfigTrait;

    /**
     * Default config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Message $message Email mesage.
     * @return array
     * @psalm-return array{headers: string, message: string}
     */
    abstract public function send(Message $message): array;

    /**
     * Constructor
     *
     * @param array $config Configuration options.
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Check that at least one destination header is set.
     *
     * @param \Cake\Mailer\Message $message
     * @return void
     * @throws \Cake\Core\Exception\Exception If at least one of to, cc or bcc is not specified.
     */
    protected function checkRecipient(Message $message): void
    {
        if ($message->getTo() === []
            && $message->getCc() === []
            && $message->getBcc() === []
        ) {
            throw new Exception('You must specify at least one recipient. Use one of `setTo`, `setCc` or `setBcc` to define a recipient.');
        }
    }

    /**
     * Help to convert headers in string
     *
     * @param array $headers Headers in format key => value
     * @param string $eol End of line string.
     * @return string
     */
    protected function _headersToString(array $headers, string $eol = "\r\n"): string
    {
        $out = '';
        foreach ($headers as $key => $value) {
            if ($value === false || $value === null || $value === '') {
                continue;
            }

            foreach ((array)$value as $val) {
                $out .= $key . ': ' . $val . $eol;
            }
        }
        if (!empty($out)) {
            $out = substr($out, 0, -1 * strlen($eol));
        }

        return $out;
    }
}
