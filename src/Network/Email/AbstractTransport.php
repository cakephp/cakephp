<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Email;

class_alias(\Cake\Mailer\AbstractTransport::class, AbstractTransport::class);

if (class_exists(AbstractTransport::class)) {
    return;
}

/**
 * @deprecated Use Cake\Mailer\AbstractTransport instead.
 */
abstract class AbstractTransport extends \Cake\Mailer\AbstractTransport
{
}
