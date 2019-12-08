<?php
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
    protected $method;

    /**
     * Constructor
     *
     * @param int $at At
     * @param string $method Method
     * @return void
     */
    public function __construct($at = null, $method = null)
    {
        if ($method) {
            $this->method = $method;
        }
        parent::__construct($at);
    }

    /**
     * Checks constraint
     *
     * @param mixed $other Constraint check
     * @return bool
     */
    public function matches($other)
    {
        $emails = $this->getEmails();
        foreach ($emails as $email) {
            $value = $email->{'get' . ucfirst($this->method)}();
            if (
                in_array($this->method, ['to', 'cc', 'bcc', 'from'])
                && array_key_exists($other, $value)
            ) {
                return true;
            }
            if ($value === $other) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString()
    {
        if ($this->at) {
            return sprintf('is in email #%d `%s`', $this->at, $this->method);
        }

        return sprintf('is in an email `%s`', $this->method);
    }
}
