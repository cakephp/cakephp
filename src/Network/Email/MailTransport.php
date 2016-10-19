<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Email;

class_alias(\Cake\Mailer\Transport\MailTransport::class, MailTransport::class);

if (class_exists(MailTransport::class)) {
    return;
}

/**
 * @deprecated Use \Cake\Mailer\Transport\MailTransport instead.
 */
class MailTransport extends \Cake\Mailer\Transport\MailTransport
{

}
