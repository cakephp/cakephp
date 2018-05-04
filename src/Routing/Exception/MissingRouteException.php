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
namespace Cake\Routing\Exception;

use Cake\Core\Exception\Exception;

/**
 * Exception raised when a URL cannot be reverse routed
 * or when a URL cannot be parsed.
 */
class MissingRouteException extends Exception
{

    /**
     * {@inheritDoc}
     */
    protected $_messageTemplate = 'A route matching "%s" could not be found.';

    /**
     * Message template to use when the requested method is included.
     *
     * @var string
     */
    protected $_messageTemplateWithMethod = 'A "%s" route matching "%s" could not be found.';

    /**
     * {@inheritDoc}
     */
    public function __construct($message, $code = 404, $previous = null)
    {
        if (is_array($message)) {
            if (isset($message['message'])) {
                $this->_messageTemplate = $message['message'];
            } elseif (isset($message['method']) && $message['method']) {
                $this->_messageTemplate = $this->_messageTemplateWithMethod;
            }
        }
        parent::__construct($message, $code, $previous);
    }
}
