<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Mailer;

use Cake\Mailer\Mailer;

/**
 * Test Suite Test App Mailer class.
 */
class TestMailer extends Mailer
{
    protected $messageClass = TestMessage::class;

    public $boundary = null;

    public function deliver(string $content = '')
    {
        $result = parent::deliver($content);
        $this->boundary = $this->message->getBoundary();

        return $result;
    }

    public function send(?string $action = null, array $args = [], array $headers = []): array
    {
        $result = parent::send($action, $args, $headers);
        $this->boundary = $this->message->getBoundary();

        return $result;
    }
}
