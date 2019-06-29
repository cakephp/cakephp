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
 * @link          https://book.cakephp.org/3.0/en/development/errors.html#error-exception-configuration
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Exception;

use Throwable;

/**
 * Exception raised with suggestions
 */
class NoOptionException extends ConsoleException
{
    /**
     * The suggestions for the error.
     *
     * @var string[]
     */
    protected $suggestions = [];

    /**
     * Constructor.
     *
     * @param string $message The string message.
     * @param array $suggestions The code of the error, is also the HTTP status code for the error.
     * @param int|null $code Either the string of the error message, or an array of attributes
     * @param \Throwable|null $previous the previous exception.
     */
    public function __construct(
        string $message = '',
        array $suggestions = [],
        ?int $code = null,
        ?Throwable $previous = null
    ) {
        $this->suggestions = $suggestions;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the message with suggestions
     *
     * @return string
     */
    public function getFullMessage(): string
    {
        $out = $this->getMessage();
        if ($this->suggestions) {
            $suggestions = array_map(function ($item) {
                return '`' . $item . '`';
            }, $this->suggestions);
            $out .= ' Did you mean: ' . implode(', ', $suggestions) . '?';
        }

        return $out;
    }

    /**
     * Get suggestions from exception.
     *
     * @return string[]
     */
    public function getSuggetions()
    {
        return $this->suggestions;
    }
}
