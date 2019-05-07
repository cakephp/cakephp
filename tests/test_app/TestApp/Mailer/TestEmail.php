<?php
declare(strict_types=1);

namespace TestApp\Mailer;

use Cake\Mailer\Email;

/**
 * Help to test Email
 */
class TestEmail extends Email
{
    protected $messageClass = TestMessage::class;
}
