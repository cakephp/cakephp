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
namespace Cake\TestSuite\Constraint\Email;

use Cake\Mailer\Message;

/**
 * MailContainsText
 *
 * @internal
 */
class MailContainsText extends MailContains
{
    /**
     * @inheritDoc
     */
    protected $type = Message::MESSAGE_TEXT;

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        if ($this->at) {
            return sprintf('is in the text message of email #%d', $this->at) . $this->getAssertedMessages();
        }

        return 'is in the text message of an email' . $this->getAssertedMessages();
    }
}
