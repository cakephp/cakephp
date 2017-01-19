<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Http\Auth;

class_alias(\Cake\Http\Client\Auth\Oauth::class, \Cake\Network\Http\Auth\Oauth::class);

if (class_exists(Oauth::class)) {
    return;
}

/**
 * @deprecated Use Cake\Http\Client\Auth\Oauth instead.
 */
class Oauth extends \Cake\Http\Client\Auth\Oauth
{
}
