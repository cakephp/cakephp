<?php
declare(strict_types=1);

namespace TestApp\Log\Engine;

use Cake\Log\Engine\BaseLog;

/**
 * Class BaseLogImpl
 * Implementation of abstract class {@see Cake\Log\Engine\BaseLog},
 * required by test case {@see Cake\Test\TestCase\Log\Engine\BaseLogTest}.
 */
class TestBaseLog extends BaseLog
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return string
     */
    public function log($level, $message, array $context = [])
    {
        return $this->_format($message, $context);
    }
}
