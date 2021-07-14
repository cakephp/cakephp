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
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Mailer\Message;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Mailer\TransportFactory;

/**
 * TestEmailTransport
 *
 * Set this as the email transport to capture emails for later assertions
 *
 * @see \Cake\TestSuite\EmailTrait
 */
class TestEmailTransport extends DebugTransport
{
    /**
     * @var array
     */
    private static $messages = [];

    /**
     * Stores email for later assertions
     *
     * @param \Cake\Mailer\Message $message Message
     * @return array
     * @psalm-return array{headers: string, message: string}
     */
    public function send(Message $message): array
    {
        static::$messages[] = $message;

        return parent::send($message);
    }

    /**
     * Replaces all currently configured transports with this one
     *
     * @return void
     */
    public static function replaceAllTransports(): void
    {
        $configuredTransports = TransportFactory::configured();

        foreach ($configuredTransports as $configuredTransport) {
            $config = TransportFactory::getConfig($configuredTransport);
            $config['className'] = self::class;
            TransportFactory::drop($configuredTransport);
            TransportFactory::setConfig($configuredTransport, $config);
        }
    }

    /**
     * Gets emails sent
     *
     * @return array<\Cake\Mailer\Message>
     */
    public static function getMessages()
    {
        return static::$messages;
    }

    /**
     * Clears list of emails that have been sent
     *
     * @return void
     */
    public static function clearMessages(): void
    {
        static::$messages = [];
    }
}
