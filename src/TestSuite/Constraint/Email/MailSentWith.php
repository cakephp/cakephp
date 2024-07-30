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
 * MailSentWith
 *
 * @internal
 */
class MailSentWith extends MailConstraintBase
{
    protected string $method;

    /**
     * Constructor
     *
     * @param int|null $at At
     * @param string|null $method Method
     * @return void
     */
    public function __construct(?int $at = null, ?string $method = null)
    {
        if ($method !== null) {
            $this->method = $method;
        }

        parent::__construct($at);
    }

    /**
     * Checks constraint
     *
     * @param mixed $other Constraint check
     */
    protected function matches(mixed $other): bool
    {
        $emails = $this->getMessages();
        foreach ($emails as $email) {
            $value = $email->{'get' . ucfirst($this->method)}();
            if ($value === $other) {
                return true;
            }

            if (
                !is_array($other)
                && in_array($this->method, ['to', 'cc', 'bcc', 'from', 'replyTo', 'sender'])
                && array_key_exists($other, $value)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assertion message string
     */
    public function toString(): string
    {
        if ($this->at) {
            return sprintf('is in email #%d `%s`', $this->at, $this->method);
        }

        return sprintf('is in an email `%s`', $this->method);
    }
}
