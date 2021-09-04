<?php
declare(strict_types=1);

namespace TestApp\View\Helper;

use Cake\View\Helper;

class TestHelper extends Helper
{
    /**
     * Settings for this helper.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'key1' => 'val1',
        'key2' => ['key2.1' => 'val2.1', 'key2.2' => 'val2.2'],
    ];

    /**
     * Helpers for this helper.
     *
     * @var array
     */
    public array $helpers = ['Html', 'TestPlugin.OtherHelper'];
}
