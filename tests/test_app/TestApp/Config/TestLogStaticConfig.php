<?php

namespace TestApp\Config;

use Cake\Core\StaticConfigTrait;

class TestLogStaticConfig
{
    use StaticConfigTrait;

    /**
     * Log engine class map.
     *
     * @var array
     */
    protected static $_dsnClassMap = [
        'console' => \Cake\Log\Engine\ConsoleLog::class,
        'file' => \Cake\Log\Engine\FileLog::class,
        'syslog' => \Cake\Log\Engine\SyslogLog::class,
    ];
}
