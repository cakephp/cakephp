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

use Cake\TestSuite\TestEmailTransport;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Base class for all mail assertion constraints
 *
 * @internal
 */
abstract class MailConstraintBase extends Constraint
{
    /**
     * @var int|null
     */
    protected $at;

    /**
     * Constructor
     *
     * @param int $at At
     * @return void
     */
    public function __construct($at = null)
    {
        $this->at = $at;
        parent::__construct();
    }

    /**
     * Gets the email or emails to check
     *
     * @return \Cake\Mailer\Email[]
     */
    public function getEmails()
    {
        $emails = TestEmailTransport::getEmails();

        if ($this->at) {
            if (!isset($emails[$this->at])) {
                return [];
            }

            return [$emails[$this->at]];
        }

        return $emails;
    }
}
