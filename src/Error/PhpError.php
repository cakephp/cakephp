<?php
declare(strict_types=1);

namespace Cake\Error;

/**
 * Object wrapper around PHP errors that are emitted by `trigger_error()`
 */
class PhpError
{
    /**
     * @var int
     */
    private $level;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string|null
     */
    private $file;

    /**
     * @var int|null
     */
    private $line;

    /**
     * Stack trace data. Each item should have a `reference`, `file` and `line` keys.
     *
     * @var array<array<string, int>>
     */
    private $trace;

    /**
     * Constructor
     *
     * @param int $level The PHP error constant
     * @param string $message The error message.
     * @param string|null $file The filename of the error.
     * @param int|null $line The line number for the error.
     * @param array $trace The backtrace for the error.
     */
    public function __construct(
        int $level,
        string $message,
        ?string $file = null,
        ?int $line = null,
        array $trace = [],
    ) {
        $this->level = $level;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->trace = $trace;
    }

    /**
     * Get the PHP error constant.
     *
     * @return string
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Get the error message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the error file
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * Get the error line number.
     *
     * @return int|null
     */
    public function getLine(): ?int
    {
        return $this->line;
    }

    /**
     * Get the stacktrace as an array.
     *
     * @return array
     */
    public function getTrace(): array
    {
        return $this->trace;
    }

    /**
     * Get the stacktrace as a string.
     *
     * @return string
     */
    public function getTraceAsString(): string
    {
        $out = [];
        foreach ($this->trace as $frame) {
            $out[] = "{$frame['reference']} {$frame['file']}, line {$frame['line']}";
        }

        return implode("\n", $out);
    }
}
