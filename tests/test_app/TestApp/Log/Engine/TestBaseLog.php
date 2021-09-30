<?php
declare(strict_types=1);

namespace TestApp\Log\Engine;

use Cake\Log\Engine\BaseLog;
use Stringable;

/**
 * Class BaseLogImpl
 * Implementation of abstract class {@see \Cake\Log\Engine\BaseLog},
 * required by test case {@see \Cake\Test\TestCase\Log\Engine\BaseLogTest}.
 */
class TestBaseLog extends BaseLog
{
    /**
     * @var string
     */
    protected $message = '';

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param \Stringable|string $message
     * @param array $context
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->message = $this->interpolate($message, $context);
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
