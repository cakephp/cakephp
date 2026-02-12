<?php

namespace TestApp\Config;

use Cake\Core\StaticConfigTrait;
use Cake\Log\Engine\ConsoleLog;
use Cake\Log\Engine\FileLog;
use Cake\Log\Engine\SyslogLog;

class TestLogStaticConfig
{
    use StaticConfigTrait;

    /**
     * Returns the default DSN class map.
     *
     * @return array<string, class-string>
     */
    protected static function buildDsnClassMap(): array
    {
        return [
            'console' => ConsoleLog::class,
            'file' => FileLog::class,
            'syslog' => SyslogLog::class,
        ];
    }
}
