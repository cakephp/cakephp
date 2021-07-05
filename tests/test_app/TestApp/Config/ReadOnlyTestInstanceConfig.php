<?php

namespace TestApp\Config;

use Cake\Core\InstanceConfigTrait;
use Exception;

class ReadOnlyTestInstanceConfig
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

    /**
     * Example of how to prevent modifying config at run time
     *
     * @throws \Exception
     * @param array|string $key
     * @param mixed $value
     */
    protected function _configWrite($key, $value): void
    {
        throw new Exception('This Instance is readonly');
    }
}
