<?php
declare(strict_types=1);

namespace TestApp\Mailer;

use Cake\Mailer\MailerAwareTrait;

class Stub
{
    use MailerAwareTrait {
        getMailer as public;
    }
}
