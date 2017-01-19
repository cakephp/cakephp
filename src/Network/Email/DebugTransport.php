<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Email;

class_alias(\Cake\Mailer\Transport\DebugTransport::class, DebugTransport::class);

if (class_exists(DebugTransport::class)) {
    return;
}

/**
 * @deprecated Use Cake\Mailer\Transport\DebugTransport instead.
 */
class DebugTransport extends \Cake\Mailer\Transport\DebugTransport
{

}
