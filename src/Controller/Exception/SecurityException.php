<?php
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

use Cake\Network\Exception\BadRequestException;

/**
 * Security exception - used when SecurityComponent detects any issue with the current request
 */
class SecurityException extends BadRequestException
{
    /**
     * Security Exception type
     * @var string
     */
    protected $_type = 'secure';

    /**
     * Reason for request blackhole
     *
     * @var string
     */
    protected $_reason;

    /**
     * Getter for type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Set Message
     *
     * @param string $message Exception message
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Set Reason
     *
     * @param string|null $reason Reason details
     * @return void
     */
    public function setReason($reason = null)
    {
        $this->_reason = $reason;
    }

    /**
     * Get Reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->_reason;
    }
}
