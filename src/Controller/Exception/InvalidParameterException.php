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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Exception;

use Cake\Core\Exception\CakeException;
use Cake\Core\Exception\HttpErrorCodeInterface;
use Throwable;

/**
 * Used when a passed parameter or action parameter type declaration is missing or invalid.
 */
class InvalidParameterException extends CakeException implements HttpErrorCodeInterface
{
    /**
     * @inheritDoc
     */
    protected int $_defaultCode = 404;

    /**
     * @var array<string, string>
     */
    protected array $templates = [
        'failed_coercion' => 'Unable to coerce `%s` to `%s` for `%s` in action `%s::%s()`.',
        'missing_dependency' => 'Failed to inject dependency from service container for parameter `%s` ' .
            'with type `%s` in action `%s::%s()`.',
        'missing_parameter' => 'Missing passed parameter for `%s` in action `%s::%s()`.',
        'unsupported_type' => 'Type declaration for `%s` in action `%s::%s()` is unsupported.',
    ];

    /**
     * Switches message template based on `template` key in message array.
     *
     * @param array|string $message Either the string of the error message, or an array of attributes
     *   that are made available in the view, and sprintf()'d into Exception::$_messageTemplate
     * @param int|null $code The error code
     * @param \Throwable|null $previous the previous exception.
     */
    public function __construct(array|string $message = '', ?int $code = null, ?Throwable $previous = null)
    {
        if (is_array($message)) {
            $this->_messageTemplate = $this->templates[$message['template']] ?? '';
            unset($message['template']);
        }
        parent::__construct($message, $code, $previous);
    }
}
