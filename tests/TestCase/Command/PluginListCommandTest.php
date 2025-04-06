<?php
declare(strict_types=1);

/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Console\CommandInterface;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Exception\MissingPluginException;
use Cake\TestSuite\TestCase;

/**
 * PluginListCommandTest class.
 */
class PluginListCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected string $pluginsListPath;

    protected string $pluginsConfigPath;

    protected string $originalPluginsConfigContent = '';

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setAppNamespace();
        $this->pluginsListPath = ROOT . DS . 'cakephp-plugins.php';
        if (file_exists($this->pluginsListPath)) {
            unlink($this->pluginsListPath);
        }
        $this->pluginsConfigPath = CONFIG . DS . 'plugins.php';
        if (file_exists($this->pluginsConfigPath)) {
            $this->originalPluginsConfigContent = file_get_contents($this->pluginsConfigPath);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->pluginsListPath)) {
            unlink($this->pluginsListPath);
        }
        if (file_exists($this->pluginsConfigPath)) {
            file_put_contents($this->pluginsConfigPath, $this->originalPluginsConfigContent);
        }
    }

    /**
     * Test generating help succeeds
     */
    public function testHelp(): void
    {
        $this->exec('plugin list --help');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('plugin list');
    }

    /**
     * Test plugin names are being displayed correctly
     */
    public function testList(): void
    {
        $file = <<<PHP
<?php
declare(strict_types=1);
return [
    'plugins' => [
        'TestPlugin' => '/config/path',
        'OtherPlugin' => '/config/path'
    ]
];
PHP;
        file_put_contents($this->pluginsListPath, $file);

        $this->exec('plugin list');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestPlugin');
        $this->assertOutputContains('OtherPlugin');
    }

    /**
     * Test empty plugins array
     */
    public function testListEmpty(): void
    {
        $file = <<<PHP
<?php
declare(strict_types=1);
return [];
PHP;
        file_put_contents($this->pluginsListPath, $file);

        $this->exec('plugin list');
        $this->assertExitCode(CommandInterface::CODE_ERROR);
        $this->assertErrorContains('No plugins have been found.');
    }

    /**
     * Test enabled plugins are being flagged as enabled
     */
    public function testListEnabled(): void
    {
        $file = <<<PHP
<?php
declare(strict_types=1);
return [
    'plugins' => [
        'TestPlugin' => '/config/path',
        'OtherPlugin' => '/config/path'
    ]
];
PHP;
        file_put_contents($this->pluginsListPath, $file);

        $config = <<<PHP
<?php
declare(strict_types=1);
return [
    'TestPlugin',
    'OtherPlugin' => ['onlyDebug' => true, 'onlyCli' => true, 'optional' => true]
];
PHP;
        file_put_contents($this->pluginsConfigPath, $config);

        $this->exec('plugin list');
        $this->assertExitCode(CommandInterface::CODE_SUCCESS);
        $this->assertOutputContains('TestPlugin');
        $this->assertOutputContains('OtherPlugin');
    }

    /**
     * Test listing unknown plugins throws an exception
     */
    public function testListUnknown(): void
    {
        $file = <<<PHP
<?php
declare(strict_types=1);
return [
    'plugins' => [
        'TestPlugin' => '/config/path',
        'OtherPlugin' => '/config/path'
    ]
];
PHP;
        file_put_contents($this->pluginsListPath, $file);

        $config = <<<PHP
<?php
declare(strict_types=1);
return [
    'Unknown'
];
PHP;
        file_put_contents($this->pluginsConfigPath, $config);

        $this->expectException(MissingPluginException::class);
        $this->expectExceptionMessage('Plugin `Unknown` could not be found.');

        $this->exec('plugin list');
    }

    /**
     * Test listing vendor plugins with versions
     */
    public function testListWithVersions(): void
    {
        $file = <<<PHP
<?php
declare(strict_types=1);
return [
    'plugins' => [
        'Chronos' => ROOT . '/vendor/cakephp/chronos',
        'CodeSniffer' => ROOT . '/vendor/cakephp/cakephp-codesniffer'
    ]
];
PHP;
        file_put_contents($this->pluginsListPath, $file);

        $config = <<<PHP
<?php
declare(strict_types=1);
return [
    'Chronos',
    'CodeSniffer'
];
PHP;
        file_put_contents($this->pluginsConfigPath, $config);

        $path = ROOT . DS . 'tests' . DS . 'composer.lock';
        $this->exec(sprintf('plugin list --composer-path="%s"', $path));
        $this->assertOutputContains('| Chronos     | X         |            |          |          | 3.0.4   |');
        $this->assertOutputContains('| CodeSniffer | X         |            |          |          | 5.1.1   |');
    }
}
