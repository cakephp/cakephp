<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Email;

class_alias(\Cake\Mailer\Transport\SmtpTransport::class, SmtpTransport::class);

if (class_exists(SmtpTransport::class)) {
    return;
}

/**
 * @deprecated Use Cake\Mailer\Transport\SmtpTransport instead.
 */
class SmtpTransport extends \Cake\Mailer\Transport\SmtpTransport
{

}
