<?php

namespace TestApp\Config;

use Cake\Core\StaticConfigTrait;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Mailer\Transport\MailTransport;
use Cake\Mailer\Transport\SmtpTransport;

class TestEmailStaticConfig
{
    use StaticConfigTrait;

    /**
     * Email driver class map.
     *
     * @var array
     */
    protected static $_dsnClassMap = [
        'debug' => DebugTransport::class,
        'mail' => MailTransport::class,
        'smtp' => SmtpTransport::class,
    ];
}
