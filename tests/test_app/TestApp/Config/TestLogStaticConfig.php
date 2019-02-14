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
        'console' => 'Cake\Log\Engine\ConsoleLog',
        'file' => 'Cake\Log\Engine\FileLog',
        'syslog' => 'Cake\Log\Engine\SyslogLog',
    ];
}
