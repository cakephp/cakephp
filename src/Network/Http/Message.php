<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Http;

class_alias(\Cake\Http\Client\Message::class, Message::class);

if (class_exists(Message::class)) {
    return;
}

/**
 * @deprecated Use Cake\Http\Client\Message instead.
 */
class Message extends \Cake\Http\Client\Message
{
}
