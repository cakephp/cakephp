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
namespace Cake\Routing\Exception;

use Cake\Core\Exception\CakeException;
use Cake\Core\Exception\HttpErrorCodeInterface;
use Throwable;

/**
 * Exception raised when a URL cannot be reverse routed
 * or when a URL cannot be parsed.
 */
class MissingRouteException extends CakeException implements HttpErrorCodeInterface
{
    /**
     * @inheritDoc
     */
    protected int $_defaultCode = 404;

    /**
     * @inheritDoc
     */
    protected string $_messageTemplate = 'A route matching `%s` could not be found.';

    /**
     * Message template to use when the requested method is included.
     *
     * @var string
     */
    protected string $_messageTemplateWithMethod = 'A `%s` route matching `%s` could not be found.';

    /**
     * Constructor.
     *
     * @param array<string, mixed>|string $message Either the string of the error message, or an array of attributes
     *   that are made available in the view, and sprintf()'d into Exception::$_messageTemplate
     * @param int|null $code The code of the error, is also the HTTP status code for the error. Defaults to 404.
     * @param \Throwable|null $previous the previous exception.
     */
    public function __construct(array|string $message, ?int $code = 404, ?Throwable $previous = null)
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
