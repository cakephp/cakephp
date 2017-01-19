<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Email;

class_alias(\Cake\Mailer\Email::class, Email::class);

if (class_exists(Email::class)) {
    return;
}

/**
 * @deprecated Use Cake\Mailer\Email instead
 */
class Email extends \Cake\Mailer\Email
{

}
