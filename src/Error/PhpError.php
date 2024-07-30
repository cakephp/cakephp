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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

/**
 * Object wrapper around PHP errors that are emitted by `trigger_error()`
 */
class PhpError
{
    /**
     * @var array<int, string>
     */
    private array $levelMap = [
        E_PARSE => 'error',
        E_ERROR => 'error',
        E_CORE_ERROR => 'error',
        E_COMPILE_ERROR => 'error',
        E_USER_ERROR => 'error',
        E_WARNING => 'warning',
        E_USER_WARNING => 'warning',
        E_COMPILE_WARNING => 'warning',
        E_RECOVERABLE_ERROR => 'warning',
        E_NOTICE => 'notice',
        E_USER_NOTICE => 'notice',
        E_STRICT => 'strict',
        E_DEPRECATED => 'deprecated',
        E_USER_DEPRECATED => 'deprecated',
    ];

    /**
     * @var array<string, int>
     */
    private array $logMap = [
        'error' => LOG_ERR,
        'warning' => LOG_WARNING,
        'notice' => LOG_NOTICE,
        'strict' => LOG_NOTICE,
        'deprecated' => LOG_NOTICE,
    ];

    /**
     * Constructor
     *
     * @param int $code The PHP error code constant
     * @param string $message The error message.
     * @param string|null $file The filename of the error.
     * @param int|null $line The line number for the error.
     * @param array $trace The backtrace for the error.
     */
    public function __construct(
        private readonly int $code,
        private readonly string $message,
        private readonly ?string $file = null,
        private readonly ?int $line = null,
        /**
         * Stack trace data. Each item should have a `reference`, `file` and `line` keys.
         */
        private readonly array $trace = []
    )
    {
    }

    /**
     * Get the PHP error constant.
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get the mapped LOG_ constant.
     */
    public function getLogLevel(): int
    {
        $label = $this->getLabel();

        return $this->logMap[$label] ?? LOG_ERR;
    }

    /**
     * Get the error code label
     */
    public function getLabel(): string
    {
        return $this->levelMap[$this->code] ?? 'error';
    }

    /**
     * Get the error message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the error file
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * Get the error line number.
     */
    public function getLine(): ?int
    {
        return $this->line;
    }

    /**
     * Get the stacktrace as an array.
     */
    public function getTrace(): array
    {
        return $this->trace;
    }

    /**
     * Get the stacktrace as a string.
     */
    public function getTraceAsString(): string
    {
        $out = [];
        foreach ($this->trace as $frame) {
            $out[] = empty($frame['line']) ? $frame['reference'] : sprintf('%d %d, line %s', $frame['reference'], $frame['file'], $frame['line']);
        }

        return implode("\n", $out);
    }
}
