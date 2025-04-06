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
    protected function setUp(): void
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
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
        if (file_exists($this->pluginsListPath)) {
            unlink($this->pluginsListPath);
        }
        file_put_contents($this->pluginsConfigPath, $this->originalPluginsConfigContent);
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
                'events' => true,
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
                'events' => true,
            ],
        ];
        $this->assertSame($result, PluginConfig::getAppConfig());
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
                'events' => true,
            ],
            'OtherPlugin' => [
                'isLoaded' => false,
            ],
        ];
        $this->assertSame($result, PluginConfig::getAppConfig());
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
    'AnotherPlugin' => ['bootstrap' => false, 'console' => false, 'middleware' => false, 'routes' => false, 'services' => false, 'events' => false],
];
PHP;
        file_put_contents($this->pluginsConfigPath, $config);

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
                'events' => true,
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
                'events' => false,
            ],
        ];
        $this->assertSame($result, PluginConfig::getAppConfig());
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
        ], PluginConfig::getAppConfig());
    }

    public function testNoPluginConfig(): void
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
        unlink($this->pluginsConfigPath);

        $this->assertSame([
            'TestPlugin' => [
                'isLoaded' => false,
            ],
            'OtherPlugin' => [
                'isLoaded' => false,
            ],
        ], PluginConfig::getAppConfig());
    }

    public function testGetVersions(): void
    {
        $test = PluginConfig::getVersions(ROOT . DS . 'tests' . DS . 'composer.lock');
        $expected = [
            'packages' => [
                'cakephp/chronos' => '3.0.4',
                'psr/simple-cache' => '3.0.0',
            ],
            'devPackages' => [
                'cakephp/cakephp-codesniffer' => '5.1.1',
                'squizlabs/php_codesniffer' => '3.8.1',
                'theseer/tokenizer' => '1.2.2',
            ],
        ];
        $this->assertEquals($expected, $test);
    }

    public function testSimpleConfiWithVersions(): void
    {
        $file = <<<PHP
<?php
return [
    'plugins' => [
        'Chronos' => ROOT .  DS . 'vendor' . DS . 'cakephp' . DS . 'chronos',
        'CodeSniffer' => ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp-codesniffer'
    ]
];
PHP;
        file_put_contents($this->pluginsListPath, $file);

        $config = <<<PHP
<?php
return [
    'Chronos',
    'CodeSniffer'
];
PHP;
        file_put_contents($this->pluginsConfigPath, $config);

        Configure::delete('plugins');
        $pathToRootVendor = ROOT . DS . 'vendor' . DS;
        $result = [
            'Chronos' => [
                'isLoaded' => true,
                'onlyDebug' => false,
                'onlyCli' => false,
                'optional' => false,
                'bootstrap' => true,
                'console' => true,
                'middleware' => true,
                'routes' => true,
                'services' => true,
                'events' => true,
                'packagePath' => $pathToRootVendor . 'cakephp' . DS . 'chronos',
                'package' => 'cakephp/chronos',
                'version' => '3.0.4',
                'isDevPackage' => false,
            ],
            'CodeSniffer' => [
                'isLoaded' => true,
                'onlyDebug' => false,
                'onlyCli' => false,
                'optional' => false,
                'bootstrap' => true,
                'console' => true,
                'middleware' => true,
                'routes' => true,
                'services' => true,
                'events' => true,
                'packagePath' => $pathToRootVendor . 'cakephp' . DS . 'cakephp-codesniffer',
                'package' => 'cakephp/cakephp-codesniffer',
                'version' => '5.1.1',
                'isDevPackage' => true,
            ],
        ];
        $this->assertSame($result, PluginConfig::getAppConfig(ROOT . DS . 'tests' . DS . 'composer.lock'));
    }

    public function testInvalidComposerLock(): void
    {
        $path = ROOT . DS . 'tests' . DS . 'unknown_composer.lock';
        $this->assertSame([], PluginConfig::getAppConfig($path));

        file_put_contents($path, 'invalid-json');
        $this->assertSame([], PluginConfig::getAppConfig($path));
        unlink($path);
    }

    public function testInvalidComposerJson(): void
    {
        $pathToTestPlugin = ROOT . DS . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS;
        $file = <<<PHP
<?php
return [
    'plugins' => [
        'TestPlugin' => ROOT . DS . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS,
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

        file_put_contents($pathToTestPlugin . 'composer.json', 'invalid-json');

        $this->assertSame([
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
                'events' => true,
            ],
        ], PluginConfig::getAppConfig());
        unlink($pathToTestPlugin . 'composer.json');
    }
}
