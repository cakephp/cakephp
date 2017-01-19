<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Http\Auth;

class_alias(\Cake\Http\Client\Auth\Digest::class, \Cake\Network\Http\Auth\Digest::class);

if (class_exists(Digest::class)) {
    return;
}

/**
 * @deprecated Use Cake\Http\Client\Auth\Digest instead.
 */
class Digest extends \Cake\Http\Client\Auth\Digest
{
}
