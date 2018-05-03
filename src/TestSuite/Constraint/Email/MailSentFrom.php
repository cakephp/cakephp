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
 * MailSentFromConstraint
 *
 * @internal
 */
class MailSentFrom extends MailSentWith
{
    protected $method = 'from';

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString()
    {
        if ($this->at) {
            return sprintf('sent email #%d', $this->at);
        }

        return 'sent an email';
    }
}
