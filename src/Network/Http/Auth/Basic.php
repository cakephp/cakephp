<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Http\Auth;

class_alias(\Cake\Http\Client\Auth\Basic::class, Basic::class);

if (class_exists(Basic::class)) {
    return;
}

/**
 * @deprecated Use Cake\Http\Client\Auth\Basic instead.
 */
class Basic extends \Cake\Http\Client\Auth\Basic
{
}
