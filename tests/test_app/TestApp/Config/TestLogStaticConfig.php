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
     * Log engine class map.
     *
     * @var array
     */
    protected static $_dsnClassMap = [
        'console' => ConsoleLog::class,
        'file' => FileLog::class,
        'syslog' => SyslogLog::class,
    ];
}
