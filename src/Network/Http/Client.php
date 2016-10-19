<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Http;

class_alias(\Cake\Http\Client::class, Client::class);

if (class_exists(Client::class)) {
    return;
}

/**
 * @deprecated Use \Cake\Http\Client instead.
 */
class Client extends \Cake\Http\Client
{

}
