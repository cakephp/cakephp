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
    protected ?int $at = null;

    /**
     * Constructor
     *
     * @param int|null $at At
     */
    public function __construct(?int $at = null)
    {
        $this->at = $at;
    }

    /**
     * Gets the email or emails to check
     *
     * @return array<\Cake\Mailer\Message>
     */
    public function getMessages(): array
    {
        $messages = TestEmailTransport::getMessages();

        if ($this->at !== null) {
            if (!isset($messages[$this->at])) {
                return [];
            }

            return [$messages[$this->at]];
        }

        return $messages;
    }
}
