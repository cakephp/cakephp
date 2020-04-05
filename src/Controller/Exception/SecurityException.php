<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Exception;

use Cake\Http\Exception\BadRequestException;

/**
 * Security exception - used when SecurityComponent detects any issue with the current request
 */
class SecurityException extends BadRequestException
{
    /**
     * Security Exception type
     *
     * @var string
     */
    protected $_type = 'secure';

    /**
     * Reason for request blackhole
     *
     * @var string|null
     */
    protected $_reason;

    /**
     * Getter for type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->_type;
    }

    /**
     * Set Message
     *
     * @param string $message Exception message
     * @return void
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Set Reason
     *
     * @param string|null $reason Reason details
     * @return $this
     */
    public function setReason(?string $reason = null)
    {
        $this->_reason = $reason;

        return $this;
    }

    /**
     * Get Reason
     *
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->_reason;
    }
}
