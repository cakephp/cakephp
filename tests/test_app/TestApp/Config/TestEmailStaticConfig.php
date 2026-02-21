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
     * Returns the default DSN class map.
     *
     * @return array<string, class-string>
     */
    protected static function initDsnClassMap(): array
    {
        return [
            'debug' => DebugTransport::class,
            'mail' => MailTransport::class,
            'smtp' => SmtpTransport::class,
        ];
    }
}
