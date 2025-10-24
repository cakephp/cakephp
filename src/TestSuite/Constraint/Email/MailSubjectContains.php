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
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Email;

use InvalidArgumentException;

/**
 * MailSubjectContains
 *
 * @internal
 */
class MailSubjectContains extends MailConstraintBase
{
    /**
     * Checks constraint
     *
     * @param mixed $other Constraint check
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        if (!is_string($other)) {
            throw new InvalidArgumentException(
                'Invalid data type, must be a string.',
            );
        }
        $messages = $this->getMessages();
        foreach ($messages as $message) {
            $subject = $message->getOriginalSubject();
            if (str_contains($subject, $other)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the subjects of all messages
     * respects $this->at
     *
     * @return string
     */
    protected function getAssertedMessages(): string
    {
        $messageMembers = [];
        $messages = $this->getMessages();
        foreach ($messages as $message) {
            $messageMembers[] = $message->getSubject();
        }
        if ($this->at && isset($messageMembers[$this->at - 1])) {
            $messageMembers = [$messageMembers[$this->at - 1]];
        }
        $result = implode(PHP_EOL, $messageMembers);

        return PHP_EOL . 'was: ' . mb_substr($result, 0, 1000);
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        if ($this->at) {
            return sprintf('is in an email subject #%d', $this->at) . $this->getAssertedMessages();
        }

        return 'is in an email subject' . $this->getAssertedMessages();
    }
}
