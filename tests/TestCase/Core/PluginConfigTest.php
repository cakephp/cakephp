<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Core;

use Cake\Core\Configure;
use Cake\Core\PluginConfig;
use Cake\TestSuite\TestCase;

class PluginConfigTest extends TestCase
{
    protected string $pluginsListPath;

    protected string $pluginsConfigPath;

    protected string $originalPluginsConfigContent = '';

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->clearPlugins();
        $this->pluginsListPath = ROOT . DS . 'cakephp-plugins.php';
        if (file_exists($this->pluginsListPath)) {
            unlink($this->pluginsListPath);
        }
        $this->pluginsConfigPath = CONFIG . DS . 'plugins.php';
        if (file_exists($this->pluginsConfigPath)) {
            $this->originalPluginsConfigContent = file_get_contents($this->pluginsConfigPath);
        }
    }

    /**
     * Reverts the changes done to the environment while testing
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
        if (file_exists($this->pluginsListPath)) {
            unlink($this->pluginsListPath);
        }
        if (file_exists($this->pluginsConfigPath)) {
            file_put_contents($this->pluginsConfigPath, $this->originalPluginsConfigContent);
        }
    }

    public function testSimpleConfig(): void
    {
        $file = <<<PHP
<?php
return [
    'plugins' => [
        'TestPlugin' => '/config/path',
        'OtherPlugin' => '/config/path',
    ]
];
PHP;
        file_put_contents($this->pluginsListPath, $file);

        $config = <<<PHP
<?php
return [
    'TestPlugin',
    'OtherPlugin',
];
PHP;
        file_put_contents($this->pluginsConfigPath, $config);

        Configure::delete('plugins');
        $config = new PluginConfig();
        $result = [
            'TestPlugin' => [
                'isLoaded' => true,
                'onlyDebug' => false,
                'onlyCli' => false,
                'optional' => false,
                'bootstrap' => true,
                'console' => true,
                'middleware' => true,
                'routes' => true,
                'services' => true,
            ],
            'OtherPlugin' => [
                'isLoaded' => true,
                'onlyDebug' => false,
                'onlyCli' => false,
                'optional' => false,
                'bootstrap' => true,
                'console' => true,
                'middleware' => true,
                'routes' => true,
                'services' => true,
            ],
        ];
        $this->assertSame($result, $config->getAppConfig());
    }

    public function testOnlyOnePlugin(): void
    {
        $file = <<<PHP
<?php
return [
    'plugins' => [
        'TestPlugin' => '/config/path',
        'OtherPlugin' => '/config/path',
    ]
];
PHP;
        file_put_contents($this->pluginsListPath, $file);

        $config = <<<PHP
<?php
return [
    'TestPlugin',
];
PHP;
        file_put_contents($this->pluginsConfigPath, $config);

        $config = new PluginConfig();
        $result = [
            'TestPlugin' => [
                'isLoaded' => true,
                'onlyDebug' => false,
                'onlyCli' => false,
                'optional' => false,
                'bootstrap' => true,
                'console' => true,
                'middleware' => true,
                'routes' => true,
                'services' => true,
            ],
            'OtherPlugin' => [
                'isLoaded' => false,
            ],
        ];
        $this->assertSame($result, $config->getAppConfig());
    }

    public function testSpecificEnvironmentsAndHooks(): void
    {
        $file = <<<PHP
<?php
return [
    'plugins' => [
        'OtherPlugin' => '/config/path',
        'AnotherPlugin' => '/config/path'
    ]
];
PHP;
        file_put_contents($this->pluginsListPath, $file);

        $config = <<<PHP
<?php
return [
    'OtherPlugin' => ['onlyDebug' => true, 'onlyCli' => false, 'optional' => true],
    'AnotherPlugin' => ['bootstrap' => false, 'console' => false, 'middleware' => false, 'routes' => false, 'services' => false]
];
PHP;
        file_put_contents($this->pluginsConfigPath, $config);

        $config = new PluginConfig();
        $result = [
            'OtherPlugin' => [
                'isLoaded' => true,
                'onlyDebug' => true,
                'onlyCli' => false,
                'optional' => true,
                'bootstrap' => true,
                'console' => true,
                'middleware' => true,
                'routes' => true,
                'services' => true,
            ],
            'AnotherPlugin' => [
                'isLoaded' => true,
                'onlyDebug' => false,
                'onlyCli' => false,
                'optional' => false,
                'bootstrap' => false,
                'console' => false,
                'middleware' => false,
                'routes' => false,
                'services' => false,
            ],
        ];
        $this->assertSame($result, $config->getAppConfig());
    }

    public function testUnknownPlugin(): void
    {
        $file = <<<PHP
<?php
return [
    'plugins' => [
        'TestPlugin' => '/config/path',
        'OtherPlugin' => '/config/path',
    ]
];
PHP;
        file_put_contents($this->pluginsListPath, $file);

        $config = <<<PHP
<?php
return [
    'UnknownPlugin',
];
PHP;
        file_put_contents($this->pluginsConfigPath, $config);

        $config = new PluginConfig();
        $this->assertSame([
            'TestPlugin' => [
                'isLoaded' => false,
            ],
            'OtherPlugin' => [
                'isLoaded' => false,
            ],
            'UnknownPlugin' => [
                'isLoaded' => false,
                'isUnknown' => true,
            ],
        ], $config->getAppConfig());
    }
}
