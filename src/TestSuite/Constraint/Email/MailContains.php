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

/**
 * MailContains
 *
 * @internal
 */
class MailContains extends MailConstraintBase
{
    /**
     * Mail type to check contents of
     *
     * @var string|null
     */
    protected $type;

    /**
     * Checks constraint
     *
     * @param mixed $other Constraint check
     * @return bool
     */
    public function matches($other): bool
    {
        $other = preg_quote($other, '/');
        $messages = $this->getMessages();
        foreach ($messages as $message) {
            $method = $this->getTypeMethod();
            $message = $message->$method();

            if (preg_match("/$other/", $message) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getTypeMethod(): string
    {
        return 'getBody' . ($this->type ? ucfirst($this->type) : 'String');
    }

    /**
     * Returns the type-dependent strings of all messages
     * respects $this->at
     *
     * @return string
     */
    protected function getAssertedMessages(): string
    {
        $messageMembers = [];
        $messages = $this->getMessages();
        foreach ($messages as $message) {
            $method = $this->getTypeMethod();
            $messageMembers[] = $message->$method();
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
            return sprintf('is in email #%d', $this->at) . $this->getAssertedMessages();
        }

        return 'is in an email' . $this->getAssertedMessages();
    }
}
