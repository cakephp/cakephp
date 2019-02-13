<?php

namespace TestApp\Config;

use Cake\Core\InstanceConfigTrait;

class TestInstanceConfig
{
    use InstanceConfigTrait;

    /**
     * _defaultConfig
     *
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
