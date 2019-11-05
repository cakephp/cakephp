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
     * @param \Cake\Mailer\Message $message Message instance.
     * @return void
     * @throws \Cake\Core\Exception\Exception If at least one of to, cc or bcc is not specified.
     */
    protected function checkRecipient(Message $message): void
    {
        if (
            $message->getTo() === []
            && $message->getCc() === []
            && $message->getBcc() === []
        ) {
            throw new Exception(
                'You must specify at least one recipient.'
                . ' Use one of `setTo`, `setCc` or `setBcc` to define a recipient.'
            );
        }
    }
}
