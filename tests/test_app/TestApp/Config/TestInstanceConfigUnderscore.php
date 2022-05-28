<?php

namespace TestApp\Config;

use Cake\Core\InstanceConfigTrait;

class TestInstanceConfigUnderscore
{
    use InstanceConfigTrait;

    /**
     * Some default config
     *
     * @var array
     */
    protected $_defaultConfig = [
        'some' => 'string',
        'a' => [
            'nested' => 'value',
            'other' => 'value',
        ],
    ];
}
