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
 * MailContains
 *
 * @internal
 */
class MailContains extends MailConstraintBase
{
    /**
     * Mail type to check contents of
     *
     * @var string
     */
    protected $type;

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
            $message = implode("\r\n", (array)$email->message($this->type));

            $other = preg_quote($other, '/');
            if (preg_match("/$other/", $message) > 0) {
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
            return sprintf('is in email #%d', $this->at);
        }

        return 'is in an email';
    }
}
