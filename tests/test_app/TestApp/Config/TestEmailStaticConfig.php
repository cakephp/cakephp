<?php

namespace TestApp\Config;

use Cake\Core\StaticConfigTrait;

class TestEmailStaticConfig
{
    use StaticConfigTrait;

    /**
     * Email driver class map.
     *
     * @var array
     */
    protected static $_dsnClassMap = [
        'debug' => \Cake\Mailer\Transport\DebugTransport::class,
        'mail' => \Cake\Mailer\Transport\MailTransport::class,
        'smtp' => \Cake\Mailer\Transport\SmtpTransport::class,
    ];
}
